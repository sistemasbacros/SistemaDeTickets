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
     */
    function require_jefe_area_strict(): void {
        if (user_is_jefe_area()) {
            return;
        }
        @error_log(sprintf(
            'JEFE_AREA_DENY ip=%s user=%s target=%s',
            $_SERVER['REMOTE_ADDR'] ?? '-',
            current_username(),
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

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>403 Forbidden</title>';
        echo '<h1>403 — Acceso restringido</h1>';
        echo '<p>Esta pantalla está restringida a Jefes de Área registrados '
           . 'en el catálogo (dbo.JefesArea). Si necesitas acceso, contacta '
           . 'al área de Sistemas para que te registren.</p>';
        echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
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
