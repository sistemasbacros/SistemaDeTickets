<?php
// LIMPIAR BUFFER Y COMENZAR DESDE CERO
if (ob_get_level()) ob_end_clean();
ob_start();

// Errors solo en dev. En produccion el php-fpm ya tiene display_errors=Off via custom.ini.
$IS_DEV = (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'dev');
if ($IS_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

require_once __DIR__ . '/login_rate_limit.php';

// CONFIGURACIÓN DE SESIÓN SEGURA ANTES DE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS forzado por Cloudflare/nginx
ini_set('session.use_strict_mode', 1);
// SameSite=Lax (no Strict): cuando el usuario llega desde un email link
// (cross-site nav), Strict NO envía la cookie de sesión → el server
// crea una sesión nueva con CSRF distinto → mismatch en el POST de login.
// Lax permite top-level navigation manteniendo protección contra CSRF
// de subrequests (img, iframe, fetch). Es el estándar para forms de login.
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);

// INICIAR SESIÓN CON CONFIGURACIÓN SEGURA
session_start();

// HEADERS PARA PREVENIR CACHE
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// GENERAR TOKEN CSRF Y DE ORIGEN
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['origin_token'])) {
    $_SESSION['origin_token'] = bin2hex(random_bytes(16));
}

// REGENERAR ID DE SESIÓN SI ES NECESARIO
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// VERIFICACIÓN MEJORADA - PREVENIR ACCESO DIRECTO DESDE SESIONES EXISTENTES
$shouldRedirectToDashboard = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['authenticated_from_login']) && $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_token']) && isset($_SESSION['ip_address']) && 
    $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'] &&
    isset($_SESSION['login_source']) && $_SESSION['login_source'] === 'form_login') {
    
    // Verificar tiempo de sesión (8 horas máximo)
    $session_timeout = 8 * 60 * 60; // 8 horas
    if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] <= $session_timeout)) {
        
        // Verificar inactividad (30 minutos máximo)
        $inactivity_timeout = 30 * 60; // 30 minutos
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] <= $inactivity_timeout)) {
            $shouldRedirectToDashboard = true;
        }
    }
}

// Destino tras login: valida solo el nombre del archivo .php, preserva query string
function getRedirectTarget(string $default = 'IniSoport.php'): string {
    $raw = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
    if ($raw) {
        // Separar archivo y query string (ej: "FormNuevoUsuario.php?nombre=Juan&...")
        $parts = explode('?', $raw, 2);
        $file  = $parts[0];
        $query = $parts[1] ?? '';
        // Solo permite nombres de archivo .php sin rutas ni protocolos
        if (preg_match('/^[A-Za-z0-9_\-]+\.php$/', $file)) {
            return $query ? $file . '?' . $query : $file;
        }
    }
    return $default;
}

// SOLO REDIRIGIR SI VIENE DEL FORMULARIO DE LOGIN O SI LA SESIÓN ES VÁLIDA
if ($shouldRedirectToDashboard) {
    // Solo redirigir si no es un acceso directo reciente
    if (!isset($_SESSION['last_direct_access']) || (time() - $_SESSION['last_direct_access'] > 10)) {
        header("Location: " . getRedirectTarget());
        ob_end_flush();
        exit();
    }
}

// Variables para el estado del login
$loginError = '';
$debugInfo = [];

// Mostrar mensaje si la sesión expiró
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $loginError = 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.';
    $debugInfo[] = "ℹ️ Sesión expirada por inactividad";
}

// Procesar login si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['contrasena'])) {

    // RATE LIMIT POR IP (defense-in-depth ante Cloudflare)
    [$throttled, $secondsLeft] = login_throttle_check();
    if ($throttled) {
        $loginError = 'Demasiados intentos fallidos. Intenta de nuevo en '
                    . max(1, (int)ceil($secondsLeft / 60)) . ' minutos.';
    } else if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // VERIFICAR TOKEN CSRF
        $loginError = 'Error de seguridad. Por favor, recarga la página e intenta nuevamente.';
        $debugInfo[] = "❌ Error CSRF: Token inválido";
    } else {
        $usuario = trim($_POST['usuario']);
        $contrasena = trim($_POST['contrasena']);
        
        $debugInfo[] = "🔍 Usuario recibido: " . $usuario;
        $debugInfo[] = "🔍 Contraseña recibida: " . (empty($contrasena) ? 'Vacía' : 'Con datos');
        
        if (empty($usuario) || empty($contrasena)) {
            $loginError = 'Usuario y contraseña son requeridos';
            $debugInfo[] = "❌ Error: Campos vacíos";
        } else {
            // ── Autenticacion via API ──────────────────────────────────
            // PHP corre en Docker: host.docker.internal apunta a la maquina host
            $apiLoginUrl = 'http://host.docker.internal:3000/auth/login';
            $apiPayload  = json_encode([
                'usuario'    => $usuario,
                'contrasena' => $contrasena,
            ], JSON_UNESCAPED_UNICODE);

            $debugInfo[] = "🔑 Autenticando via API: " . $apiLoginUrl;

            $ch = curl_init($apiLoginUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $apiPayload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            $apiResp     = curl_exec($ch);
            $apiCurlErr  = curl_error($ch);
            $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $debugInfo[] = "🔑 API HTTP: " . $apiHttpCode . " | cURL: " . ($apiCurlErr ?: 'OK');

            if ($apiCurlErr) {
                $loginError = 'No se pudo conectar al servidor de autenticación.';
                $debugInfo[] = "❌ cURL error: " . $apiCurlErr;
            } else {
                $apiData = json_decode($apiResp, true);

                if ($apiHttpCode === 200 && !empty($apiData['token'])) {
                    $debugInfo[] = "✅ Login exitoso, JWT obtenido";
                    login_throttle_clear();

                    $token = $apiData['token'];

                    // Extraer datos del usuario del JWT payload (base64)
                    $jwtParts = explode('.', $token);
                    $jwtPayload = json_decode(base64_decode(strtr($jwtParts[1] ?? '', '-_', '+/')), true) ?: [];

                    // Datos del usuario: primero del body de respuesta, luego del JWT
                    $userData = $apiData['user'] ?? $apiData['usuario'] ?? $jwtPayload;

                    $userName   = $userData['nombre']    ?? $userData['nombre_completo'] ?? $userData['Nombre'] ?? $usuario;
                    $userArea   = $userData['area']       ?? $userData['Area']            ?? '';
                    $userId     = $userData['id']         ?? $userData['Id_Empleado']     ?? $userData['id_empleado'] ?? '';
                    $userLogin  = $userData['usuario']    ?? $userData['Usuario']         ?? $usuario;

                    $debugInfo[] = "✅ Usuario: " . $userName . " | Area: " . $userArea;

                    // LIMPIAR SESIÓN EXISTENTE COMPLETAMENTE
                    session_regenerate_id(true);

                    // Configurar sesión
                    $_SESSION = array();
                    $_SESSION['user_id']       = $userId;
                    $_SESSION['user_name']     = $userName;
                    $_SESSION['user_area']     = $userArea;
                    $_SESSION['user_username'] = $userLogin;
                    $_SESSION['api_jwt']       = $token;
                    $_SESSION['logged_in']     = true;
                    $_SESSION['LOGIN_TIME']    = time();
                    $_SESSION['LAST_ACTIVITY'] = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['authenticated_from_login'] = true;
                    $_SESSION['session_token']    = bin2hex(random_bytes(32));
                    $_SESSION['ip_address']       = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent']       = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['initiated']        = true;
                    $_SESSION['last_regeneration'] = time();
                    $_SESSION['login_source']     = 'form_login';
                    $_SESSION['origin_token']     = bin2hex(random_bytes(16));

                    $debugInfo[] = "✅ Sesión configurada con JWT";

                    $redirectTarget = getRedirectTarget();
                    $debugInfo[] = "🔄 Redirigiendo a " . $redirectTarget;

                    header("Location: " . $redirectTarget);
                    ob_end_flush();
                    exit();

                } elseif ($apiHttpCode === 401 || $apiHttpCode === 403) {
                    login_throttle_register_failure();
                    $loginError = 'Usuario o contraseña incorrectos';
                    $debugInfo[] = "❌ Credenciales inválidas (HTTP " . $apiHttpCode . ")";
                } else {
                    $msg = $apiData['message'] ?? $apiData['error'] ?? 'Error desconocido';
                    $loginError = 'Error del servidor de autenticación: ' . htmlspecialchars($msg);
                    $debugInfo[] = "❌ API error: HTTP " . $apiHttpCode . " - " . substr($apiResp ?: '(vacío)', 0, 200);
                }
            }
        }
    }
}

// SI LLEGAMOS AQUÍ, ENTONCES MOSTRAMOS EL HTML DEL LOGIN
// Limpiar cualquier sesión residual
session_unset();
session_destroy();
session_start(); // Reiniciar sesión limpia

// Regenerar CSRF token para el nuevo formulario
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['origin_token'] = bin2hex(random_bytes(16));

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BACROCORP - Portal de Soporte Técnico</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Prevenir cache en el navegador -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <style>
        :root {
            --navy-primary: #0a192f;
            --navy-secondary: #112240;
            --accent-blue: #3b82f6;
            --accent-blue-light: #60a5fa;
            --accent-blue-dark: #1d4ed8;
            --snow-white: #f8fafc;
            --pearl-white: #f1f5f9;
            --glass-bg: rgba(255, 255, 255, 0.12);
            --glass-border: rgba(255, 255, 255, 0.18);
            --gradient-primary: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --gradient-accent: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            --gradient-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            --gradient-gold: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --font-primary: 'Outfit', sans-serif;
            --font-heading: 'Manrope', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        body {
            background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-secondary) 100%);
            font-family: var(--font-primary);
            color: white;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Efectos de partículas animadas mejoradas */
        .particles-container {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            top: 0;
            left: 0;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite linear;
            filter: blur(1px);
        }
        
        .particle:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 5%;
            animation-duration: 25s;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, rgba(59, 130, 246, 0) 70%);
        }
        
        .particle:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 70%;
            left: 80%;
            animation-duration: 30s;
            background: radial-gradient(circle, rgba(30, 58, 138, 0.3) 0%, rgba(30, 58, 138, 0) 70%);
        }
        
        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 10%;
            animation-duration: 20s;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, rgba(96, 165, 250, 0) 70%);
        }
        
        .particle:nth-child(4) {
            width: 150px;
            height: 150px;
            top: 20%;
            left: 85%;
            animation-duration: 35s;
            background: radial-gradient(circle, rgba(29, 78, 216, 0.3) 0%, rgba(29, 78, 216, 0) 70%);
        }
        
        .particle:nth-child(5) {
            width: 80px;
            height: 80px;
            top: 60%;
            left: 20%;
            animation-duration: 28s;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, rgba(59, 130, 246, 0) 70%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 25px 50px rgba(2, 12, 27, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            transition: all 0.4s ease;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
        }
        
        .glass-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .glass-card:hover::after {
            left: 100%;
        }
        
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--navy-primary), var(--navy-secondary));
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 1.5s ease-out;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .loading-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 25px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 48px;
            animation: pulse 2.5s infinite;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 0 30px rgba(59, 130, 246, 0.4),
                inset 0 0 20px rgba(255, 255, 255, 0.1);
        }
        
        .loading-text {
            font-family: var(--font-heading);
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 15px;
            opacity: 0;
            animation: fadeIn 2s forwards 0.5s;
            background: linear-gradient(135deg, #93c5fd 0%, #3b82f6 50%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
            letter-spacing: -0.5px;
        }
        
        .loading-subtext {
            font-size: 1.1rem;
            opacity: 0;
            animation: fadeIn 2s forwards 1s;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.5px;
            font-weight: 300;
        }
        
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            border-top-color: var(--accent-blue);
            margin: 20px auto;
            animation: spin 1s linear infinite;
            opacity: 0;
            animation: spin 1s linear infinite, fadeIn 1s forwards 1.5s;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 30px 25px;
            color: white;
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 60px rgba(2, 12, 27, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }
        
        .company-logo {
            width: 90px;
            height: 90px;
            margin: 0 auto 25px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 38px;
            border: 2px solid rgba(255, 255, 255, 0.25);
            transition: all 0.4s ease;
            box-shadow: 
                0 12px 25px rgba(0, 0, 0, 0.3),
                inset 0 0 15px rgba(255, 255, 255, 0.1);
        }
        
        .company-logo:hover {
            transform: scale(1.05) rotate(3deg);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 15px 30px rgba(59, 130, 246, 0.3),
                inset 0 0 20px rgba(255, 255, 255, 0.15);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
            font-family: var(--font-primary);
            font-size: 1rem;
            font-weight: 400;
            transition: all 0.4s ease;
            backdrop-filter: blur(15px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 300;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent-blue);
            color: white;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.3),
                inset 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            outline: none;
        }
        
        .form-control:hover {
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }
        
        .btn-login {
            background: var(--gradient-blue);
            border: none;
            color: white;
            padding: 18px;
            border-radius: 14px;
            width: 100%;
            font-weight: 700;
            font-family: var(--font-heading);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.1rem;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 12px 25px rgba(59, 130, 246, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            margin-top: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 30px rgba(59, 130, 246, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fecaca;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-weight: 600;
            backdrop-filter: blur(15px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
            animation: shake 0.5s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .session-expired {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fed7aa;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-weight: 600;
            backdrop-filter: blur(15px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .security-notice {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #dbeafe;
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 600;
            backdrop-filter: blur(15px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .debug-info {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ddd;
            padding: 18px;
            border-radius: 14px;
            margin-top: 20px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            backdrop-filter: blur(15px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            line-height: 1.5;
            max-height: 200px;
            overflow-y: auto;
        }

        .debug-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
        }
        
        .form-label {
            font-weight: 600;
            font-family: var(--font-heading);
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 1rem;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-title {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #93c5fd 0%, #3b82f6 50%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.3px;
            text-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            line-height: 1.2;
            text-align: center;
        }

        .login-subtitle {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 30px;
            font-weight: 400;
            line-height: 1.5;
            letter-spacing: 0.2px;
            text-align: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 8px;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            z-index: 5;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-control.with-icon {
            padding-left: 50px;
        }
        
        .form-control:focus + .input-icon {
            color: var(--accent-blue);
            transform: translateY(-50%) scale(1.1);
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            font-weight: 400;
            line-height: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .floating-element {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0) 70%);
            filter: blur(40px);
            z-index: 0;
            animation: floatElement 15s infinite ease-in-out;
        }
        
        .floating-element:nth-child(1) {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            top: 60%;
            right: 5%;
            animation-delay: 5s;
        }
        
        .floating-element:nth-child(3) {
            bottom: 10%;
            left: 15%;
            animation-delay: 10s;
        }

        /* Elementos decorativos */
        .decoration-corner {
            position: fixed;
            width: 150px;
            height: 150px;
            z-index: 0;
            opacity: 0.1;
        }
        
        .decoration-corner.top-left {
            top: 0;
            left: 0;
            background: radial-gradient(circle at top left, var(--accent-blue), transparent 70%);
        }
        
        .decoration-corner.bottom-right {
            bottom: 0;
            right: 0;
            background: radial-gradient(circle at bottom right, var(--accent-blue), transparent 70%);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { 
                transform: scale(1); 
                box-shadow: 
                    0 0 0 0 rgba(59, 130, 246, 0.4),
                    inset 0 0 15px rgba(255, 255, 255, 0.1);
            }
            50% { 
                transform: scale(1.05); 
                box-shadow: 
                    0 0 0 15px rgba(59, 130, 246, 0),
                    inset 0 0 20px rgba(255, 255, 255, 0.15);
            }
            100% { 
                transform: scale(1); 
                box-shadow: 
                    0 0 0 0 rgba(59, 130, 246, 0),
                    inset 0 0 15px rgba(255, 255, 255, 0.1);
            }
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }
        
        @keyframes floatElement {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-15px) scale(1.05);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        /* RESPONSIVE MEJORADO - SIN SCROLL */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                align-items: center;
            }
            
            .glass-card {
                max-width: 420px;
                border-radius: 20px;
            }
            
            .login-container {
                padding: 25px 20px;
                max-width: 100%;
            }
            
            .login-title {
                font-size: 1.9rem;
                margin-bottom: 12px;
            }
            
            .login-subtitle {
                font-size: 0.95rem;
                margin-bottom: 25px;
            }
            
            .company-logo {
                width: 80px;
                height: 80px;
                font-size: 34px;
                margin-bottom: 20px;
            }
            
            .form-control {
                padding: 14px 18px;
                font-size: 0.95rem;
                margin-bottom: 18px;
            }
            
            .btn-login {
                padding: 16px;
                font-size: 1rem;
                margin-bottom: 18px;
            }
            
            .security-notice,
            .error-message,
            .session-expired {
                padding: 14px 18px;
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            
            .footer-text {
                font-size: 0.8rem;
            }
            
            .loading-logo {
                width: 100px;
                height: 100px;
                font-size: 42px;
            }
            
            .loading-text {
                font-size: 2rem;
            }
            
            .loading-subtext {
                font-size: 1rem;
            }
            
            .particle {
                display: none;
            }
            
            .particle:nth-child(1),
            .particle:nth-child(2) {
                display: block;
                width: 80px;
                height: 80px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .glass-card {
                border-radius: 18px;
            }
            
            .login-container {
                padding: 20px 15px;
            }
            
            .login-title {
                font-size: 1.7rem;
            }
            
            .login-subtitle {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            
            .company-logo {
                width: 70px;
                height: 70px;
                font-size: 30px;
                margin-bottom: 15px;
            }
            
            .form-control {
                padding: 12px 16px;
                font-size: 0.9rem;
                margin-bottom: 15px;
                border-radius: 12px;
            }
            
            .btn-login {
                padding: 14px;
                font-size: 0.95rem;
                margin-bottom: 15px;
                border-radius: 12px;
            }
            
            .security-notice,
            .error-message,
            .session-expired {
                padding: 12px 16px;
                font-size: 0.85rem;
                margin-bottom: 18px;
                border-radius: 12px;
            }
            
            .footer-text {
                font-size: 0.75rem;
            }
            
            .form-control.with-icon {
                padding-left: 45px;
            }
            
            .input-icon {
                left: 16px;
                font-size: 1rem;
            }
        }

        @media (max-height: 700px) and (orientation: landscape) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .glass-card {
                max-width: 400px;
                margin-top: 20px;
            }
            
            .login-container {
                padding: 20px;
            }
            
            .company-logo {
                width: 60px;
                height: 60px;
                font-size: 26px;
                margin-bottom: 15px;
            }
            
            .login-title {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            
            .login-subtitle {
                font-size: 0.85rem;
                margin-bottom: 20px;
            }
            
            .form-control {
                padding: 10px 14px;
                margin-bottom: 12px;
                font-size: 0.9rem;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .security-notice,
            .error-message,
            .session-expired {
                padding: 10px 14px;
                margin-bottom: 15px;
                font-size: 0.8rem;
            }
            
            .footer-text {
                margin-top: 15px;
                font-size: 0.7rem;
            }
            
            .debug-info {
                max-height: 120px;
                font-size: 11px;
                padding: 12px;
            }
        }

        @media (max-height: 600px) {
            .footer-text {
                display: none;
            }
            
            .debug-info {
                display: none;
            }
        }

        /* Scrollbar personalizado para debug */
        .debug-info::-webkit-scrollbar {
            width: 6px;
        }

        .debug-info::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .debug-info::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 3px;
        }

        .debug-info::-webkit-scrollbar-thumb:hover {
            background: var(--accent-blue-light);
        }
    </style>
</head>
<body>
    <!-- Elementos decorativos de fondo -->
    <div class="decoration-corner top-left"></div>
    <div class="decoration-corner bottom-right"></div>
    
    <!-- Elementos flotantes de fondo -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
    <!-- Partículas animadas -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Pantalla de carga inicial -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-content">
            <div class="loading-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="loading-text">BACROCORP</h1>
            <p class="loading-subtext">Sistema de Soporte Técnico</p>
            <div class="spinner"></div>
        </div>
    </div>
    
    <!-- Formulario de login -->
    <div class="glass-card">
        <div class="login-container" id="loginContainer">
            <div class="company-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2 class="login-title">Portal de Soporte</h2>
            <p class="login-subtitle">Sistema Técnico Especializado BACROCORP</p>
            
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                Sistema Seguro - Autenticación Requerida
            </div>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'session_expired'): ?>
                <div class="session-expired">
                    <i class="fas fa-clock"></i>
                    Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.
                </div>
            <?php endif; ?>
            
            <?php if ($loginError && !(isset($_GET['error']) && $_GET['error'] === 'session_expired')): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $loginError; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <!-- TOKEN CSRF OCULTO -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="mb-3">
                    <label for="usuario" class="form-label">
                        <i class="fas fa-user"></i>
                        Usuario
                    </label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control with-icon" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autocomplete="username" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="contrasena" class="form-label">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control with-icon" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginButton">
                    <i class="fas fa-sign-in-alt"></i>
                    Acceder al Sistema
                </button>
                
                <div class="footer-text">
                    <i class="fas fa-info-circle"></i>
                    <span>Sesión segura - Se cerrará automáticamente después de 30 minutos de inactividad</span>
                </div>
            </form>

            <!-- Información de debug — solo visible en modo dev -->
            <?php if ($IS_DEV && !empty($debugInfo)): ?>
                <div class="debug-info">
                    <strong>🔧 Información de Debug:</strong><br>
                    <?php foreach($debugInfo as $info): ?>
                        <?php echo htmlspecialchars($info); ?><br>
                    <?php endforeach; ?>
                    <br>
                    <strong>🛡️ Seguridad de Sesión:</strong><br>
                    ✅ Cookie HTTPOnly activada<br>
                    ✅ Modo estricto de sesión activado<br>
                    ✅ SameSite Lax configurado<br>
                    ✅ Token CSRF implementado<br>
                    ✅ Verificación de origen (login_source)<br>
                    ✅ Prevención de cache activa<br>
                    ✅ Headers de seguridad implementados
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ocultar pantalla de carga después de 3 segundos
        setTimeout(function() {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                    
                    // Mostrar formulario de login con animación
                    const loginContainer = document.getElementById('loginContainer');
                    if (loginContainer) {
                        loginContainer.style.opacity = '1';
                        loginContainer.style.transform = 'translateY(0)';
                        
                        // Enfocar el campo de usuario
                        document.getElementById('usuario').focus();
                    }
                }, 1500);
            }
        }, 3000);
        
        // ===== VERSIÓN CORREGIDA - FUNCIONA CON ENTER Y CLIC =====
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const contrasena = document.getElementById('contrasena').value.trim();
            const loginButton = document.getElementById('loginButton');
            
            // Validar campos vacíos
            if (!usuario || !contrasena) {
                e.preventDefault();
                mostrarError('Por favor complete todos los campos');
                return;
            }
            
            // Cambiar estado del botón - UNA SOLA VEZ
            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando credenciales...';
            loginButton.disabled = true;
            
            // IMPORTANTE: No usar setTimeout aquí
            // El formulario se envía naturalmente después de este evento
        });

        // Función para mostrar errores
        function mostrarError(texto) {
            // Eliminar mensajes de error existentes
            const erroresPrevios = document.querySelectorAll('.error-message');
            erroresPrevios.forEach(el => el.remove());
            
            // Crear nuevo mensaje
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + texto;
            
            // Insertar en el lugar correcto
            const securityNotice = document.querySelector('.security-notice');
            if (securityNotice) {
                securityNotice.insertAdjacentElement('afterend', errorDiv);
            } else {
                document.querySelector('form').insertBefore(errorDiv, document.querySelector('form').firstChild);
            }
            
            // Auto-ocultar después de 4 segundos
            setTimeout(() => {
                if (errorDiv && errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 4000);
        }

        // Prevenir que el navegador cachee la página
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Prevenir el uso del botón de retroceso después del login
        window.addEventListener('beforeunload', function() {
            // Limpiar cualquier dato sensible
            document.getElementById('contrasena').value = '';
        });

        // Efectos interactivos para los campos del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                // Efecto al enfocar
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                // Efecto al perder el foco
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });

        // Agregar evento de tecla Enter para enviar formulario
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const focused = document.activeElement;
                if (focused && (focused.id === 'usuario' || focused.id === 'contrasena')) {
                    document.getElementById('loginForm').requestSubmit();
                }
            }
        });
        
        // Efecto de partículas interactivas
        document.addEventListener('mousemove', function(e) {
            const particles = document.querySelectorAll('.particle');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            particles.forEach((particle, index) => {
                const speed = (index + 1) * 0.2;
                const x = (mouseX - 0.5) * speed * 15;
                const y = (mouseY - 0.5) * speed * 15;
                
                particle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Ajustar altura en dispositivos móviles
        function adjustLayout() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
            
            // Si la pantalla es muy pequeña, ocultar elementos no esenciales
            const footerText = document.querySelector('.footer-text');
            if (footerText) {
                if (window.innerHeight < 600) {
                    footerText.style.display = 'none';
                } else {
                    footerText.style.display = 'flex';
                }
            }
        }

        window.addEventListener('resize', adjustLayout);
        window.addEventListener('orientationchange', adjustLayout);
        adjustLayout();
    </script>
</body>
</html>