<?php
/**
 * roles.php — Role-based authorization helper.
 *
 * Use AFTER auth_check.php (or auth_check_api.php) on pages that should be
 * restricted to specific user groups, not just any authenticated user.
 *
 * Usage:
 *     require_once __DIR__ . '/auth_check.php';
 *     require_once __DIR__ . '/roles.php';
 *     require_role('admin');                   // dies 403 if not admin
 *     // or
 *     require_role(['admin', 'soporte']);      // any of these is OK
 *
 * Role membership is currently hardcoded by username. The list lives in
 * $ROLES below. When the user/role model lives in the DB, replace the
 * lookup with an API call — keep the function signatures identical.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // auth_check.php should have started the session already; this is defensive.
    @session_start();
}

/**
 * Username → roles mapping.
 * Keep usernames in lowercase for case-insensitive matching.
 */
$ROLES = [
    'admin' => [
        'luis.vargas',
        'alfredo.rosales',
        'luis.romero',
        'ariel.antonio',
    ],
    'soporte' => [
        // Same admins for now; expand as needed.
        'luis.vargas',
        'alfredo.rosales',
        'luis.romero',
        'ariel.antonio',
    ],
    'rh' => [
        // Add HR users here when you have them.
    ],
];

if (!function_exists('current_username')) {
    function current_username(): string {
        $u = $_SESSION['user_username'] ?? '';
        return is_string($u) ? strtolower(trim($u)) : '';
    }
}

if (!function_exists('user_has_role')) {
    /**
     * @param string|string[] $role one role name or an array of acceptable roles
     */
    function user_has_role(string|array $role): bool {
        global $ROLES;
        $needed = is_array($role) ? $role : [$role];
        $me = current_username();
        if ($me === '') {
            return false;
        }
        foreach ($needed as $r) {
            $list = $ROLES[$r] ?? [];
            if (in_array($me, array_map('strtolower', $list), true)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('require_role')) {
    /**
     * Block the request with HTTP 403 if the current user lacks the role.
     * @param string|string[] $role
     */
    function require_role(string|array $role): void {
        if (user_has_role($role)) {
            return;
        }
        @error_log(sprintf(
            'ROLE_DENY ip=%s user=%s required=%s target=%s',
            $_SERVER['REMOTE_ADDR'] ?? '-',
            current_username(),
            is_array($role) ? implode('|', $role) : $role,
            basename($_SERVER['SCRIPT_NAME'] ?? '-')
        ));

        // Honour content negotiation: API endpoints get JSON, pages get HTML.
        $isJson = (
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        );

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>403 Forbidden</title>';
        echo '<h1>403 — Acceso denegado</h1>';
        echo '<p>No tienes permisos para acceder a esta sección.</p>';
        echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
        exit;
    }
}
