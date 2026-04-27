<?php
/**
 * login_rate_limit.php — Per-IP throttle for the login form.
 *
 * Defense-in-depth: works even if Cloudflare WAF rate-limit is not yet
 * configured. File-based; no external dependencies. Stores tiny JSON
 * files under sys_get_temp_dir()/login_throttle/.
 *
 * Policy:
 *   - Sliding window: 15 minutes.
 *   - Max failed attempts in window: 5.
 *   - On exceed: 10 minute lockout.
 *
 * Usage from Loginti.php:
 *
 *     require_once __DIR__ . '/login_rate_limit.php';
 *
 *     // Before processing POST credentials:
 *     [$blocked, $secondsLeft] = login_throttle_check();
 *     if ($blocked) {
 *         $loginError = 'Demasiados intentos. Espera ' . ceil($secondsLeft/60)
 *                     . ' min antes de volver a intentar.';
 *     } else {
 *         // ...do API auth...
 *         if ($apiHttpCode === 200 && !empty($apiData['token'])) {
 *             login_throttle_clear();
 *         } else if ($apiHttpCode === 401 || $apiHttpCode === 403) {
 *             login_throttle_register_failure();
 *         }
 *     }
 */

declare(strict_types=1);

const LOGIN_THROTTLE_WINDOW   = 900;  // 15 min
const LOGIN_THROTTLE_MAX      = 5;    // attempts in window
const LOGIN_THROTTLE_LOCKOUT  = 600;  // 10 min after exceeding max

if (!function_exists('login_throttle_path')) {
    function login_throttle_path(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Hash the IP so the filesystem doesn't reveal who tried to log in.
        $key = hash('sha256', 'login_throttle_v1:' . $ip);
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'login_throttle';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $key . '.json';
    }
}

if (!function_exists('login_throttle_load')) {
    function login_throttle_load(): array {
        $path = login_throttle_path();
        if (!is_file($path)) {
            return ['attempts' => [], 'locked_until' => 0];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return ['attempts' => [], 'locked_until' => 0];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['attempts' => [], 'locked_until' => 0];
        }
        $data['attempts']     = is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
        $data['locked_until'] = (int)($data['locked_until'] ?? 0);
        return $data;
    }
}

if (!function_exists('login_throttle_save')) {
    function login_throttle_save(array $data): void {
        $path = login_throttle_path();
        // Prune attempts outside the window before saving.
        $now    = time();
        $window = $now - LOGIN_THROTTLE_WINDOW;
        $data['attempts'] = array_values(array_filter(
            $data['attempts'],
            static fn ($t) => is_int($t) && $t >= $window
        ));
        @file_put_contents($path, json_encode($data), LOCK_EX);
        @chmod($path, 0600);
    }
}

if (!function_exists('login_throttle_check')) {
    /**
     * @return array{0: bool, 1: int}  [blocked, seconds_left_in_lockout]
     */
    function login_throttle_check(): array {
        $now  = time();
        $data = login_throttle_load();

        if (!empty($data['locked_until']) && $data['locked_until'] > $now) {
            return [true, $data['locked_until'] - $now];
        }

        // Lockout expired — clear it.
        if (!empty($data['locked_until']) && $data['locked_until'] <= $now) {
            $data['locked_until'] = 0;
            $data['attempts']     = [];
            login_throttle_save($data);
        }

        return [false, 0];
    }
}

if (!function_exists('login_throttle_register_failure')) {
    function login_throttle_register_failure(): void {
        $now  = time();
        $data = login_throttle_load();
        $data['attempts'][] = $now;

        // Count attempts inside the window.
        $window = $now - LOGIN_THROTTLE_WINDOW;
        $recent = array_filter($data['attempts'], static fn ($t) => $t >= $window);

        if (count($recent) >= LOGIN_THROTTLE_MAX) {
            $data['locked_until'] = $now + LOGIN_THROTTLE_LOCKOUT;
            @error_log(sprintf(
                'LOGIN_LOCKOUT ip=%s attempts=%d window=%ds lockout=%ds',
                $_SERVER['REMOTE_ADDR'] ?? '-',
                count($recent),
                LOGIN_THROTTLE_WINDOW,
                LOGIN_THROTTLE_LOCKOUT
            ));
        }

        login_throttle_save($data);
    }
}

if (!function_exists('login_throttle_clear')) {
    function login_throttle_clear(): void {
        $path = login_throttle_path();
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
