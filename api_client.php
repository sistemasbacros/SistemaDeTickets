<?php
/**
 * api_client.php — Centralised cURL helper for calling the Rust API.
 *
 * Always:
 *   - reads PDF_API_URL from env (with the docker default fallback)
 *   - sends Accept: application/json
 *   - sends Authorization: Bearer <session jwt> when a session is active
 *   - returns ['ok' => bool, 'http' => int, 'body' => string, 'json' => array|null,
 *              'error' => string|null]
 *
 * Usage:
 *     require_once __DIR__ . '/api_client.php';
 *     $r = api_call('GET', '/api/TicketBacros/tickets');
 *     if ($r['ok']) { $data = $r['json']['data'] ?? []; }
 *
 * Designed to coexist with the JWT enforcement that the Rust API will get later.
 * Today most TicketBacros endpoints are public, but forwarding the JWT now means
 * no PHP changes will be needed when the API tightens.
 */

declare(strict_types=1);

if (!function_exists('api_base_url')) {
    function api_base_url(): string {
        $url = getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000';
        return rtrim($url, '/');
    }
}

if (!function_exists('api_call')) {
    /**
     * @param string      $method  GET|POST|PUT|PATCH|DELETE
     * @param string      $path    e.g. /api/TicketBacros/tickets
     * @param array|null  $body    associative array, json-encoded (null for GET/DELETE)
     * @param array       $extra_headers
     * @param int         $timeout seconds
     * @return array{ok:bool,http:int,body:string,json:?array,error:?string}
     */
    function api_call(
        string $method,
        string $path,
        ?array $body = null,
        array $extra_headers = [],
        int $timeout = 15
    ): array {
        $url = api_base_url() . '/' . ltrim($path, '/');

        $headers = ['Accept: application/json'];

        // Forward session JWT if available
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['api_jwt'])) {
            $headers[] = 'Authorization: Bearer ' . $_SESSION['api_jwt'];
        }

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        foreach ($extra_headers as $h) {
            $headers[] = $h;
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);

        $raw  = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch) ?: null;
        curl_close($ch);

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) { $json = $decoded; }
        }

        return [
            'ok'    => $err === null && $http >= 200 && $http < 300,
            'http'  => $http,
            'body'  => is_string($raw) ? $raw : '',
            'json'  => $json,
            'error' => $err,
        ];
    }
}

if (!function_exists('web_base_path')) {
    /**
     * Prefijo web del sitio tal como lo ve el NAVEGADOR.
     *
     * En producción Traefik enruta `/tickets` y QUITA el prefijo antes de
     * llegar a nginx, pero el navegador sigue navegando bajo /tickets. Por eso
     * cualquier URL que se renderice al cliente (img src, href, fetch) debe
     * llevar este prefijo; si se emitiera '/api/...' a la raíz del dominio,
     * Traefik no tendría regla y devolvería 404.
     *
     * En test (puerto directo, sin Traefik) devuelve '' — sin prefijo.
     */
    function web_base_path(): string {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir    = rtrim(strtr(dirname($script), '\\', '/'), '/');
        return ($dir === '' || $dir === '.') ? '' : $dir;
    }
}

if (!function_exists('api_web_url')) {
    /** URL de un recurso del API para consumir desde el navegador. */
    function api_web_url(string $path): string {
        return web_base_path() . '/' . ltrim($path, '/');
    }
}
