<?php
/**
 * auth_check_api.php — Authentication guard for XHR / JSON endpoints.
 *
 * Same validation as auth_check.php, but on failure responds with
 * HTTP 401 + JSON body instead of redirecting. Use in endpoints called
 * by JavaScript (fetch / XMLHttpRequest) where a 302 to the login page
 * would be invisible to the caller.
 *
 * Usage at the top of API-style .php files:
 *     <?php require_once __DIR__ . '/auth_check_api.php'; ?>
 *
 * Optional CSRF: if the request is a state-changing method (POST/PUT/DELETE),
 * the client must send X-CSRF-Token matching $_SESSION['csrf_token'].
 */

declare(strict_types=1);

if (function_exists('ob_get_level') && ob_get_level()) { @ob_end_clean(); }

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   '1');
ini_set('session.use_strict_mode', '1');
// Lax para mantener consistencia con Loginti.php / auth_check.php — si esto
// queda en Strict y los otros en Lax, las cookies se pisan y rompen sesión.
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies','1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function auth_api_fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$ABS_TIMEOUT = 8 * 60 * 60;
$INACT_LIMIT = 30 * 60;

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
    @error_log(sprintf(
        'AUTH_FAIL_API ip=%s ua=%s target=%s',
        $_SERVER['REMOTE_ADDR']     ?? '-',
        substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 200),
        basename($_SERVER['SCRIPT_NAME'] ?? '-')
    ));
    auth_api_fail(401, 'unauthorized');
}

// CSRF check on state-changing requests
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $client_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $session_token = $_SESSION['csrf_token'] ?? '';
    if (!$client_token || !$session_token || !hash_equals($session_token, $client_token)) {
        @error_log(sprintf(
            'CSRF_FAIL ip=%s target=%s method=%s',
            $_SERVER['REMOTE_ADDR']     ?? '-',
            basename($_SERVER['SCRIPT_NAME'] ?? '-'),
            $method
        ));
        auth_api_fail(403, 'csrf_invalid');
    }
}

// Touch
$_SESSION['LAST_ACTIVITY'] = time();

$AUTH_USER = [
    'id'       => $_SESSION['user_id']       ?? '',
    'name'     => $_SESSION['user_name']     ?? '',
    'username' => $_SESSION['user_username'] ?? '',
    'area'     => $_SESSION['user_area']     ?? '',
    'jwt'      => $_SESSION['api_jwt']       ?? '',
];
