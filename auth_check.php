<?php
/**
 * auth_check.php — Single source of truth for authentication on protected pages.
 *
 * Usage at the top of any protected .php (BEFORE any output):
 *     <?php require_once __DIR__ . '/auth_check.php'; ?>
 *
 * For XHR/JSON endpoints use auth_check_api.php instead (returns 401 JSON on failure
 * instead of redirecting to the login page).
 *
 * Pages that are public BY DESIGN (FormTic.php, FormSG.php, FirmarLiberacion.php
 * con token, etc.) must NOT include this file.
 *
 * On success: $AUTH_USER is exposed for the page to consume (id, name, username,
 * area, jwt). Session cookie params are hardened. Standard security headers set.
 *
 * On failure: session destroyed, redirect to Loginti.php?error=session_expired
 * preserving the original URL (file + query string) in &redirect=.
 */

declare(strict_types=1);

// Defensive: clean any stray output buffer
if (function_exists('ob_get_level') && ob_get_level()) { @ob_end_clean(); }

// Cookie hardening (must run BEFORE session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies','1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard security headers (HSTS is set at nginx)
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Validation
$ABS_TIMEOUT = 8 * 60 * 60;   // 8h absolute
$INACT_LIMIT = 30 * 60;       // 30min inactivity
$REGEN_EVERY = 5 * 60;        // rotate session id every 5min

$valid = (
    !empty($_SESSION['logged_in'])              && $_SESSION['logged_in'] === true &&
    !empty($_SESSION['authenticated_from_login']) && $_SESSION['authenticated_from_login'] === true &&
    !empty($_SESSION['user_id'])                &&
    !empty($_SESSION['session_token'])          &&
    !empty($_SESSION['ip_address'])             && $_SESSION['ip_address'] === ($_SERVER['REMOTE_ADDR'] ?? '') &&
    !empty($_SESSION['login_source'])           && $_SESSION['login_source'] === 'form_login' &&
    !empty($_SESSION['LOGIN_TIME'])             && (time() - $_SESSION['LOGIN_TIME'])    <= $ABS_TIMEOUT &&
    !empty($_SESSION['LAST_ACTIVITY'])          && (time() - $_SESSION['LAST_ACTIVITY']) <= $INACT_LIMIT
);

if (!$valid) {
    // Capture intended target so login can bounce back
    $self = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $qs   = $_SERVER['QUERY_STRING'] ?? '';
    $back = $self . ($qs !== '' ? ('?' . $qs) : '');
    if (!preg_match('/^[A-Za-z0-9_\-]+\.php(\?.*)?$/', $back)) { $back = ''; }

    // Audit failed access (without leaking user data)
    @error_log(sprintf(
        'AUTH_FAIL ip=%s ua=%s target=%s',
        $_SERVER['REMOTE_ADDR']     ?? '-',
        substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 200),
        $self
    ));

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    $location = 'Loginti.php?error=session_expired' . ($back ? '&redirect=' . urlencode($back) : '');
    header('Location: ' . $location);
    exit;
}

// Touch + periodic id rotation
$_SESSION['LAST_ACTIVITY'] = time();
if (empty($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > $REGEN_EVERY) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Convenience handle for pages
$AUTH_USER = [
    'id'       => $_SESSION['user_id']       ?? '',
    'name'     => $_SESSION['user_name']     ?? '',
    'username' => $_SESSION['user_username'] ?? '',
    'area'     => $_SESSION['user_area']     ?? '',
    'jwt'      => $_SESSION['api_jwt']       ?? '',
];

// CSRF helper (used by mutating pages)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logout shortcut: any page can post logout=1 and be sent home
if (!empty($_POST['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: Loginti.php');
    exit;
}
