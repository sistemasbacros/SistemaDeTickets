<?php
// ============================================
// SSO - PUENTE DEL LOGIN UNIVERSAL (PORTAL BACROCORP)
// ============================================
// El portal emite un código de un solo uso (TTL 60 s) y redirige aquí con
// ?sso_code=<64 hex>. Este puente lo canjea SERVER-SIDE contra el backend
// (POST /api/identidad/sso/canje) y arma la MISMA sesión que Loginti.php,
// luego redirige a IniSoport.php.
//
// - Si el canje falla se redirige a Loginti.php?error=sso (el login propio
//   sigue vivo — transición dual). El código nunca se imprime.
// - URL del backend: variable de entorno SSO_API_URL o 127.0.0.1:3000
//   (el backend corre en el mismo servidor que IIS).

// CONFIGURACIÓN DE SESIÓN SEGURA ANTES DE session_start() (igual que Loginti.php)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS forzado por Cloudflare/nginx (igual que Loginti.php)
ini_set('session.use_strict_mode', 1);
// Lax (no Strict) para coincidir con Loginti.php/auth_check.php: mezclar
// SameSite hacía que el navegador no mandara la cookie al volver del portal
// y la sesión se perdía en la primera página protegida.
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);

session_start();

header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

$code = trim($_GET['sso_code'] ?? '');
// Formato exacto (64 hex); cualquier otra cosa ni siquiera viaja al backend.
if ($code === '' || !preg_match('/^[a-f0-9]{64}$/', $code)) {
    header("Location: Loginti.php?error=sso");
    exit();
}

// Canje server-side contra el backend
$apiUrl = getenv('SSO_API_URL') ?: 'http://127.0.0.1:3000';
$ch = curl_init(rtrim($apiUrl, '/') . '/api/identidad/sso/canje');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'codigo' => $code,
        'plataforma' => 'ticketbacros'
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response ?: '', true);

if ($httpCode < 200 || $httpCode >= 300 || empty($data['token'])) {
    // Sin detalle al usuario: el portal reintenta con código nuevo o se usa
    // el login propio.
    header("Location: Loginti.php?error=sso");
    exit();
}

$identidad = is_array($data['identidad'] ?? null) ? $data['identidad'] : [];

// LIMPIAR SESIÓN EXISTENTE COMPLETAMENTE (mismo patrón que Loginti.php)
session_regenerate_id(true);

$_SESSION = array(); // Limpiar todo primero
$_SESSION['user_id'] = $identidad['id_empleado'] ?? null;
$_SESSION['user_name'] = $identidad['nombre'] ?? '';
$_SESSION['user_area'] = $data['area'] ?? '';
$_SESSION['user_username'] = $identidad['usuario'] ?? '';
$_SESSION['logged_in'] = true;
$_SESSION['LOGIN_TIME'] = time();
$_SESSION['LAST_ACTIVITY'] = time();
$_SESSION['last_activity'] = time();
$_SESSION['authenticated_from_login'] = true; // BANDERA CRÍTICA
$_SESSION['session_token'] = bin2hex(random_bytes(32));
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_SESSION['initiated'] = true;
$_SESSION['last_regeneration'] = time();
$_SESSION['login_source'] = 'sso_portal'; // Origen: Portal Bacrocorp
$_SESSION['origin_token'] = bin2hex(random_bytes(16));

// JWT del canje: sin esto quien entra por SSO se queda sin credencial para el
// API (roles.php devolvía [] y caía a la lista hardcodeada de admins).
$_SESSION['api_jwt'] = $data['token'];

header("Location: IniSoport.php");
exit();
