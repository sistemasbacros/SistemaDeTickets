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
 * Role membership is resolved DYNAMICALLY from the dbo.JefesArea table in the
 * Ticket database via the Rust API endpoint /api/TicketBacros/jefes-area.
 *
 * Reglas:
 *   - Cualquier NombreUsuario con Activo=1 en dbo.JefesArea es admin Y soporte.
 *   - Si la API falla (timeout, 5xx, sin JWT en sesion), se cae al fallback
 *     hardcoded $ROLES_FALLBACK para no bloquear a usuarios criticos.
 *   - El resultado de la API se cachea por request (static var) para no
 *     hacer N llamadas en una misma pagina.
 *
 * Para AGREGAR/QUITAR un admin:
 *   - Recomendado: agregar/quitar fila en dbo.JefesArea (via AdminJefesArea.php)
 *   - Excepcional: editar $ROLES_FALLBACK abajo.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/config.php';

/**
 * Lista hardcoded de respaldo — SOLO se usa si la API esta caida.
 * Mantener al minimo (1-3 superadmins de TI). Para todo lo demas,
 * usar dbo.JefesArea.
 */
$ROLES_FALLBACK = [
    'admin' => [
        'luis.vargas',
        'alfredo.rosales',
        'luis.romero',
    ],
    'soporte' => [
        'luis.vargas',
        'alfredo.rosales',
        'luis.romero',
    ],
    'rh' => [
        // Add HR users here if/when the table doesn't cover them.
    ],
];

if (!function_exists('current_username')) {
    function current_username(): string {
        $u = $_SESSION['user_username'] ?? '';
        return is_string($u) ? strtolower(trim($u)) : '';
    }
}

if (!function_exists('roles_api_url')) {
    /**
     * Resuelve la URL base del API Rust (mismo patron que AdminLiberaciones.php).
     */
    function roles_api_url(): string {
        $url = getenv('PDF_API_URL') ?: '';
        if (!$url) {
            $envFile = __DIR__ . '/.env';
            if (file_exists($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                    [$k, $v] = explode('=', $line, 2);
                    if (trim($k) === 'PDF_API_URL') { $url = trim($v); break; }
                }
            }
        }
        return rtrim($url ?: 'http://localhost:3000', '/');
    }
}

if (!function_exists('jefes_area_usernames')) {
    /**
     * Devuelve la lista de NombreUsuario activos en dbo.JefesArea.
     * Cacheada por request en una static var. Si la API falla, devuelve []
     * y user_has_role() caera al fallback.
     *
     * @return string[] usernames en lowercase
     */
    function jefes_area_usernames(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $jwt = $_SESSION['api_jwt'] ?? '';
        if (!$jwt) {
            // Sin JWT no podemos llamar el endpoint protegido.
            // Esto ocurre si auth_check.php no se incluyo antes que roles.php.
            $cache = [];
            return $cache;
        }

        $url = roles_api_url() . '/api/TicketBacros/jefes-area';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwt],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code !== 200 || !$resp) {
            @error_log(sprintf(
                'ROLES_API_FAIL code=%d err=%s url=%s',
                $code, $err, $url
            ));
            $cache = [];
            return $cache;
        }

        $data = json_decode($resp, true);
        if (!is_array($data)) { $cache = []; return $cache; }

        // El endpoint puede responder como array directo o {data: [...]}
        $rows = $data['data'] ?? $data;
        if (!is_array($rows)) { $cache = []; return $cache; }

        $usernames = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $u  = strtolower(trim((string)($r['nombre_usuario'] ?? '')));
            // El endpoint listar ya filtra por Activo=1, pero validamos por si acaso
            $on = !isset($r['activo']) || (bool)$r['activo'];
            if ($u !== '' && $on) $usernames[] = $u;
        }
        $cache = array_values(array_unique($usernames));
        return $cache;
    }
}

if (!function_exists('user_has_role')) {
    /**
     * Verifica si el usuario actual tiene el rol pedido.
     *
     * Resolucion:
     *   1) Si esta en dbo.JefesArea (Activo=1) -> admin Y soporte (cualquiera de los 2).
     *   2) Si esta en $ROLES_FALLBACK[$rol] -> ese rol.
     *   3) Sino -> false.
     *
     * @param string|string[] $role one role name or an array of acceptable roles
     */
    function user_has_role(string|array $role): bool {
        global $ROLES_FALLBACK;
        $needed = is_array($role) ? $role : [$role];
        $me = current_username();
        if ($me === '') return false;

        // Fuente principal: dbo.JefesArea via API
        $jefes = jefes_area_usernames();
        if (in_array($me, $jefes, true)) {
            // Cualquier jefe de area tiene permisos admin y soporte
            foreach ($needed as $r) {
                if ($r === 'admin' || $r === 'soporte') return true;
            }
        }

        // Fallback: lista hardcoded (resiliencia si la API esta caida o
        // si el rol pedido no es admin/soporte — ej: 'rh').
        foreach ($needed as $r) {
            $list = $ROLES_FALLBACK[$r] ?? [];
            if (in_array($me, array_map('strtolower', $list), true)) return true;
        }
        return false;
    }
}

if (!function_exists('user_is_jefe_area')) {
    /**
     * Estricto: el usuario actual está en dbo.JefesArea (Activo=1)?
     * NO usa fallback hardcoded — solo la tabla cuenta. Útil para pantallas
     * de detalle/listado de solicitudes de baja, donde queremos que solo
     * los jefes registrados las vean (no los superadmins de TI por defecto).
     */
    function user_is_jefe_area(): bool {
        $me = current_username();
        if ($me === '') return false;
        return in_array($me, jefes_area_usernames(), true);
    }
}

if (!function_exists('require_jefe_area_strict')) {
    /**
     * Block 403 si el usuario NO está en dbo.JefesArea (Activo=1).
     * Más estricto que require_role('admin') porque no respeta el fallback
     * hardcoded. Para usar en DetalleSolicitudBaja y ListadoSolicitudesBaja.
     *
     * Si el usuario actual NO es jefe de área:
     *   - API/JSON: 403 con {error: forbidden_not_jefe_area}
     *   - Pantalla HTML: 403 con página estilizada que explica al usuario
     *     que solicite acceso a su administrador.
     */
    function require_jefe_area_strict(): void {
        if (user_is_jefe_area()) {
            return;
        }
        $me = current_username();
        @error_log(sprintf(
            'JEFE_AREA_DENY ip=%s user=%s target=%s',
            $_SERVER['REMOTE_ADDR'] ?? '-',
            $me,
            basename($_SERVER['SCRIPT_NAME'] ?? '-')
        ));

        $isJson = (
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        );

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['error' => 'forbidden_not_jefe_area'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $userDisplay  = $_SESSION['user_name'] ?? $me;
        $pantalla     = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $userDisplayE = htmlspecialchars($userDisplay, ENT_QUOTES, 'UTF-8');
        $pantallaE    = htmlspecialchars($pantalla,    ENT_QUOTES, 'UTF-8');

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Acceso restringido — BACROCORP</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:#f4f6fb;color:#1a1a2e;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{background:#fff;max-width:560px;width:100%;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.08);padding:36px 38px;text-align:center;border-top:6px solid #dc3545}
.icon-wrap{width:80px;height:80px;background:linear-gradient(135deg,#fde2e4,#fecdd2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px}
.icon-wrap i{color:#b00020;font-size:2.2rem}
h1{font-size:1.5rem;color:#001550;margin:0 0 8px}
.subtitle{color:#666;margin:0 0 22px;font-size:.97rem}
.user-tag{background:#f0f3f8;border-radius:8px;padding:10px 14px;font-size:.86rem;color:#444;margin:0 0 22px;display:inline-flex;align-items:center;gap:8px}
.user-tag strong{color:#0033cc}
.msg{background:#fff8e1;border-left:4px solid #f4a300;padding:14px 18px;border-radius:6px;font-size:.92rem;color:#7a4f00;text-align:left;margin:0 0 22px}
.msg strong{color:#5c3c00;display:block;margin-bottom:4px}
.contact-info{font-size:.88rem;color:#555;margin:0 0 24px;line-height:1.6}
.contact-info a{color:#0033cc;text-decoration:none;font-weight:500}
.contact-info a:hover{text-decoration:underline}
.actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:8px;font-weight:600;font-size:.92rem;text-decoration:none;transition:.2s;border:0;cursor:pointer}
.btn-primary{background:linear-gradient(120deg,#0033cc,#0a3dd4);color:#fff;box-shadow:0 4px 12px rgba(0,51,204,.28)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,51,204,.4)}
.btn-secondary{background:#fff;color:#0033cc;border:2px solid #0033cc}
.btn-secondary:hover{background:#f0f4ff}
.footer{margin-top:24px;font-size:.78rem;color:#999;border-top:1px solid #eee;padding-top:14px}
.footer code{background:#f0f3f8;padding:2px 6px;border-radius:3px;font-family:Consolas,monospace}
</style>
</head>
<body>
<div class="box">
  <div class="icon-wrap"><i class="fas fa-lock"></i></div>
  <h1>No tienes permiso para acceder</h1>
  <p class="subtitle">Esta pantalla es exclusiva para Jefes de Área registrados.</p>

  <div class="user-tag">
    <i class="fas fa-user"></i>
    Sesión activa: <strong>{$userDisplayE}</strong>
  </div>

  <div class="msg">
    <strong><i class="fas fa-info-circle"></i> ¿Qué puedo hacer?</strong>
    Solicita a tu administrador que te registre como Jefe de Área en el
    catálogo del sistema. Solo los usuarios incluidos ahí (con estatus
    Activo) pueden ver y gestionar las solicitudes de baja.
  </div>

  <div class="contact-info">
    <strong>Contacto para solicitar acceso:</strong><br>
    <i class="fas fa-envelope"></i>
    <a href="mailto:soporte@bacrocorp.com?subject=Solicitud%20de%20acceso%20a%20{$pantallaE}">soporte@bacrocorp.com</a>
    <br>
    <span style="font-size:.82rem;color:#888">Indica tu usuario, área y la pantalla a la que necesitas acceso.</span>
  </div>

  <div class="actions">
    <a class="btn btn-primary" href="IniSoport.php">
      <i class="fas fa-house"></i> Volver al inicio
    </a>
    <a class="btn btn-secondary" href="javascript:history.back()">
      <i class="fas fa-arrow-left"></i> Atrás
    </a>
  </div>

  <div class="footer">
    Pantalla: <code>{$pantallaE}</code>
    &nbsp;·&nbsp; Estado: <code>403 Forbidden</code>
  </div>
</div>
</body>
</html>
HTML;
        exit;
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
