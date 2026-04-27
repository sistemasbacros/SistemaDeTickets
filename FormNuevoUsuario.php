<?php
/**
 * @file FormNuevoUsuario.php
 * @brief Crea un usuario en Snipe-IT, asigna foto, selecciona múltiples assets
 *        y genera carta de resguardo PDF vía API local (puerto 3000).
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/roles.php';
require_role('admin');

require_once __DIR__ . '/config.php';

// ── Snipe-IT: leer vars del .env aunque Docker haya omitido el loader ─────────
function getSnipeVars(): array
{
    $url   = getenv('SNIPE_IT_URL')  ?: '';
    $token = getenv('SNIPE_IT_TEST') ?: '';

    if (!$url || !$token) {
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k); $v = trim($v);
                if ($k === 'SNIPE_IT_URL'  && !$url)   $url   = $v;
                if ($k === 'SNIPE_IT_TEST' && !$token) $token = $v;
            }
        }
    }
    return [$url, $token];
}

function snipeUploadAvatar(int $userId, string $filePath): array
{
    [$snipeUrl, $snipeToken] = getSnipeVars();
    if (!$snipeUrl || !$snipeToken) return ['error' => 'Snipe-IT no configurado.'];
    if (!file_exists($filePath))    return ['error' => 'Archivo de avatar no encontrado.'];

    $mime = mime_content_type($filePath) ?: 'image/jpeg';

    $ch = curl_init($snipeUrl . '/api/v1/users/' . $userId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $snipeToken,
            'Accept: application/json',
            // Sin Content-Type manual: cURL lo genera automático con boundary para multipart
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => [
            'avatar' => new CURLFile($filePath, $mime, basename($filePath)),
        ],
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err)       return ['error' => 'cURL avatar: ' . $err];
    if ($http >= 400) return ['error' => 'Snipe-IT avatar HTTP ' . $http . ': ' . $resp];

    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : ['error' => 'Respuesta inválida al subir avatar.'];
}

function snipeRequest(string $endpoint, string $method = 'GET', array $body = []): array
{
    [$snipeUrl, $snipeToken] = getSnipeVars();
    if (!$snipeUrl || !$snipeToken) return ['error' => 'Snipe-IT no configurado.'];

    $ch = curl_init($snipeUrl . '/api/v1' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $snipeToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) return ['error' => 'cURL: ' . $err];
    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : ['error' => 'Respuesta inválida de Snipe-IT.'];
}

// ── POST: crear usuario + checkout assets + PDF ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $f = fn($k) => trim($_POST[$k] ?? '');

    // ── Subida de foto ───────────────────────────────────────────────────────
    $fotoRuta = '';
    $self    = basename(__FILE__);
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '-';
    $errCode = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;

    // Solo procesar si efectivamente hubo archivo. UPLOAD_ERR_NO_FILE se ignora.
    if ($errCode !== UPLOAD_ERR_NO_FILE) {
        if ($errCode !== UPLOAD_ERR_OK) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=upload_error code={$errCode}");
            echo json_encode(['error' => 'Error al recibir el avatar (código ' . (int)$errCode . ').']); exit;
        }

        $tmpPath = $_FILES['avatar']['tmp_name'];
        $size    = (int) ($_FILES['avatar']['size'] ?? 0);
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if ($size <= 0 || $size > $maxSize) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=bad_size size={$size}");
            echo json_encode(['error' => 'Tamaño de archivo no permitido (máx 5MB).']); exit;
        }

        // Validar contenido real (NO confiar en $_FILES[...]['type'])
        $info = @getimagesize($tmpPath);
        if ($info === false || empty($info['mime'])) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=not_an_image");
            echo json_encode(['error' => 'El archivo no es una imagen válida.']); exit;
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($info['mime'], $allowedMimes, true)) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=bad_mime mime={$info['mime']}");
            echo json_encode(['error' => 'Tipo de imagen no permitido (solo JPEG/PNG/GIF/WEBP).']); exit;
        }

        $ext = match ($info['mime']) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        };

        $dir = __DIR__ . '/uploads/usuarios/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $base = realpath($dir);
        if ($base === false) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=base_dir_missing");
            echo json_encode(['error' => 'Directorio de uploads no disponible.']); exit;
        }

        $newName  = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $base . DIRECTORY_SEPARATOR . $newName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            error_log("UPLOAD reject ip={$ip} script={$self} reason=move_failed");
            echo json_encode(['error' => 'Error al mover archivo.']); exit;
        }

        // Defensa anti path-traversal: confirmar que el destino quedó dentro del directorio
        $resolved = realpath($destPath);
        if ($resolved === false || strpos($resolved, $base . DIRECTORY_SEPARATOR) !== 0) {
            @unlink($destPath);
            error_log("UPLOAD reject ip={$ip} script={$self} reason=path_escape");
            echo json_encode(['error' => 'Ruta de destino inválida.']); exit;
        }

        @chmod($destPath, 0644);
        error_log("UPLOAD ok ip={$ip} script={$self} file={$newName} size={$size} mime={$info['mime']}");

        $fotoRuta = 'uploads/usuarios/' . $newName;
    }

    // ── Crear usuario en Snipe-IT ────────────────────────────────────────────
    $userData = array_filter([
        'first_name'           => $f('first_name'),
        'last_name'            => trim($f('apellido_paterno') . ' ' . $f('apellido_materno')),
        'username'             => $f('username'),
        'email'                => $f('email'),
        'password'             => $f('password'),
        'password_confirmation'=> $f('password_confirmation'),
        'activated'            => isset($_POST['activated']) ? true : false,
        'employee_num'         => $f('employee_num'),
        'jobtitle'             => $f('jobtitle'),
        'phone'                => $f('phone'),
        'mobile'               => $f('mobile'),
        'address'              => trim($f('calle') . ' ' . $f('numero')),
        'city'                 => $f('municipio_estado'),
        'country'              => $f('country'),
        'zip'                  => $f('zip'),
        'notes'                => $f('notes'),
        'website'              => $f('website'),
        'start_date'           => $f('start_date'),
        'end_date'             => $f('end_date'),
        'vip'                  => isset($_POST['vip'])                 ? true : false,
        'autoassign_licenses'  => isset($_POST['autoassign_licenses']) ? true : false,
        'remote'               => isset($_POST['remote'])              ? true : false,
    ], fn($v) => $v !== '' && $v !== null);

    if ($f('department_id')) $userData['department_id'] = (int)$f('department_id');
    if ($f('company_id'))    $userData['company_id']    = (int)$f('company_id');
    if ($f('location_id'))   $userData['location_id']   = (int)$f('location_id');
    if ($f('manager_id'))    $userData['manager_id']    = (int)$f('manager_id');

    $snipeUser = snipeRequest('/users', 'POST', $userData);

    if (!empty($snipeUser['error']) || ($snipeUser['status'] ?? '') === 'error') {
        $msgs = is_array($snipeUser['messages'] ?? null)
            ? implode(', ', array_map(fn($m) => is_array($m) ? implode(', ', $m) : $m, $snipeUser['messages']))
            : ($snipeUser['messages'] ?? $snipeUser['error'] ?? 'Error desconocido al crear usuario.');
        echo json_encode(['error' => 'Snipe-IT (usuario): ' . $msgs]);
        exit;
    }

    $newUserId = $snipeUser['payload']['id'] ?? null;

    // ── Subir avatar a Snipe-IT ───────────────────────────────────────────────
    if ($newUserId && $fotoRuta) {
        snipeUploadAvatar($newUserId, __DIR__ . '/' . $fotoRuta);
    }

    // ── Checkout de múltiples assets ─────────────────────────────────────────
    $assetIds        = $_POST['asset_ids']        ?? [];
    $checkoutDates   = $_POST['checkout_at']      ?? [];
    $expectedDates   = $_POST['expected_checkin'] ?? [];
    $assetNotes      = $_POST['asset_note']       ?? [];
    $assetAssigned   = $_POST['asset_assigned']   ?? [];
    $checkoutResults = [];

    if ($newUserId && !empty($assetIds)) {
        foreach ($assetIds as $i => $assetId) {
            $assetId = intval($assetId);

            // Si el activo ya estaba asignado, hacer checkin primero
            if (($assetAssigned[$i] ?? '0') === '1') {
                snipeRequest('/hardware/' . $assetId . '/checkin', 'POST', [
                    'note' => 'Reasignado a nuevo usuario vía SistemaDeTickets',
                ]);
            }

            $coPayload = array_filter([
                'checkout_to_type' => 'user',
                'assigned_user'    => $newUserId,
                'checkout_at'      => $checkoutDates[$i] ?? date('Y-m-d'),
                'expected_checkin' => $expectedDates[$i] ?? '',
                'note'             => $assetNotes[$i]    ?? '',
            ], fn($v) => $v !== '');

            $coResp = snipeRequest('/hardware/' . $assetId . '/checkout', 'POST', $coPayload);
            $checkoutResults[] = [
                'asset_id' => $assetId,
                'ok'       => ($coResp['status'] ?? '') === 'success',
                'msg'      => $coResp['messages'] ?? ($coResp['error'] ?? ''),
            ];
        }
    }

    // ── Llamar al generador de PDF (puerto 3000) ─────────────────────────────
    $pdfUrl  = null;
    $pdfWarn = null;

    // Construir array equipos con los activos seleccionados
    $assetTipos   = $_POST['asset_tipo']    ?? [];
    $assetMarcas  = $_POST['asset_marca']   ?? [];
    $assetModelos = $_POST['asset_modelo']  ?? [];
    $assetSerials = $_POST['asset_serial']  ?? [];
    $assetCaracts = $_POST['asset_caracts'] ?? [];

    $equiposPdf = [];
    foreach ($assetIds as $i => $aid) {
        $eq = [
            'tipo'            => mb_strtoupper($assetTipos[$i]   ?? '', 'UTF-8'),
            'caracteristicas' => ($assetCaracts[$i] ?? '') !== '' ? $assetCaracts[$i] : null,
            'marca'           => mb_strtoupper($assetMarcas[$i]  ?? '', 'UTF-8'),
            'modelo'          => ($assetModelos[$i] ?? '') !== '' ? $assetModelos[$i] : null,
            'no_serie'        => ($assetSerials[$i] ?? '') !== '' ? $assetSerials[$i] : null,
        ];
        $equiposPdf[] = array_filter($eq, fn($v) => $v !== '' && $v !== null) + $eq;
    }

    $rawDate  = $f('checkout_date_global') ?: date('Y-m-d');
    $fechaRec = date('d/m/Y', strtotime($rawDate));

    $u8 = fn(string $s): string => mb_strtoupper($s, 'UTF-8');
    $pdfPayload = json_encode([
        'nombre'           => $u8($f('first_name')),
        'apellido_paterno' => $u8($f('apellido_paterno')),
        'apellido_materno' => $u8($f('apellido_materno')) ?: null,
        'calle'            => $u8($f('calle')),
        'numero'           => $f('numero') ?: null,
        'colonia'          => $u8($f('colonia')) ?: null,
        'cp'               => $f('zip') ?: null,
        'municipio_estado' => $u8($f('municipio_estado')),
        'celular'          => $f('mobile') ?: null,
        'tel_particular'   => $f('phone') ?: null,
        'cargo'            => $u8($f('jobtitle')),
        'area'             => $u8($f('department_name')),
        'jefe_inmediato'   => $u8($f('manager_name')),
        'num_empleado'     => $f('employee_num'),
        'correo'           => $f('email'),
        'tipo'             => $f('tipo_contratacion') ?: 'PERMANENTE',
        'folio'            => $f('folio') ?: null,
        'fecha_recepcion'  => $fechaRec,
        'equipos'          => $equiposPdf,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    // Leer PDF_API_URL del entorno (con fallback directo al .env igual que getSnipeVars)
    $pdfApiUrl = getenv('PDF_API_URL') ?: '';
    if (!$pdfApiUrl) {
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
                if (strpos(trim($_line), '#') === 0 || strpos($_line, '=') === false) continue;
                [$_k, $_v] = explode('=', $_line, 2);
                if (trim($_k) === 'PDF_API_URL') { $pdfApiUrl = trim($_v); break; }
            }
        }
    }
    $pdfApiUrl = rtrim($pdfApiUrl ?: 'http://localhost:3000', '/');

    $ch = curl_init($pdfApiUrl . '/api/TicketBacros/carta-resguardo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS     => $pdfPayload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $pdfResp = curl_exec($ch);
    $pdfErr  = curl_error($ch);
    curl_close($ch);

    if (!$pdfErr && $pdfResp) {
        $dec    = json_decode($pdfResp, true);
        $pdfUrl = $dec['url_pdf'] ?? $dec['pdf_url'] ?? null;
    } else {
        $pdfWarn = 'PDF no generado: ' . $pdfErr;
    }

    // ── Checkout de licencias ─────────────────────────────────────────────────
    $licenseIds     = $_POST['license_ids'] ?? [];
    $licenseResults = [];

    if ($newUserId && !empty($licenseIds)) {
        foreach ($licenseIds as $licenseId) {
            $lResp = snipeRequest('/licenses/' . intval($licenseId) . '/checkout', 'POST', [
                'checkout_to_type' => 'user',
                'assigned_to'      => $newUserId,
            ]);
            $licenseResults[] = [
                'license_id' => $licenseId,
                'ok'         => ($lResp['status'] ?? '') === 'success',
                'msg'        => $lResp['messages'] ?? ($lResp['error'] ?? ''),
            ];
        }
    }

    $failedCo = array_filter($checkoutResults, fn($r) => !$r['ok']);
    $failedLic = array_filter($licenseResults, fn($r) => !$r['ok']);

    echo json_encode(array_filter([
        'success'          => true,
        'snipe_user_id'    => $newUserId,
        'checkout_results' => $checkoutResults,
        'pdf_url'          => $pdfUrl,
        'message'          => 'Usuario creado' . ($newUserId ? " (ID: $newUserId)" : '') .
                              '. Activos: ' . (count($checkoutResults) - count($failedCo)) . '/' . count($checkoutResults) .
                              '. Licencias: ' . (count($licenseResults) - count($failedLic)) . '/' . count($licenseResults) . '.',
        'warning'          => $pdfWarn ?? (
            !empty($failedCo)  ? 'Activos sin asignar: '  . implode(', ', array_column($failedCo,  'asset_id'))   :
            (!empty($failedLic) ? 'Licencias sin asignar: ' . implode(', ', array_column($failedLic, 'license_id')) : null)
        ),
    ], fn($v) => $v !== null));
    exit;
}

// ── Helper: leer parámetro GET y escaparlo (pre-relleno por URL) ─────────────
function gp(string $key, string $default = '', string ...$aliases): string
{
    $val = $_GET[$key] ?? null;
    if ($val === null) {
        foreach ($aliases as $a) {
            if (isset($_GET[$a])) { $val = $_GET[$a]; break; }
        }
    }
    $str = $val ?? $default;
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Helper: igual que gp() pero aplica Title Case (primera letra mayúscula) ──
function tc(string $key, string $default = '', string ...$aliases): string
{
    $val = $_GET[$key] ?? null;
    if ($val === null) {
        foreach ($aliases as $a) {
            if (isset($_GET[$a])) { $val = $_GET[$a]; break; }
        }
    }
    $str = $val ?? $default;
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
    }
    $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Cargar datos desde Snipe-IT para el formulario ────────────────────────────
function snipeList(string $endpoint): array
{
    $resp = snipeRequest($endpoint . '?limit=200&sort=name&order=asc');
    return $resp['rows'] ?? [];
}

$companies   = snipeList('/companies');
$departments = snipeList('/departments');
$locations   = snipeList('/locations');
// Solo usuarios activos pueden ser jefes/supervisores (Snipe-IT no tiene flag "is_manager")
$managersResp = snipeRequest('/users?limit=500&sort=last_name&order=asc&activated=1');
$managers     = $managersResp['rows'] ?? [];

// Licencias
$licencias = [];
$licResp   = snipeRequest('/licenses?limit=500&sort=name&order=asc');
foreach ($licResp['rows'] ?? [] as $lic) {
    $libres = ($lic['seats'] ?? 0) - ($lic['seats_used'] ?? 0);
    $licencias[] = [
        'id'          => $lic['id'],
        'name'        => $lic['name'] ?? '',
        'product_key' => $lic['serial'] ?? '',
        'fabricante'  => $lic['manufacturer']['name'] ?? '',
        'categoria'   => $lic['category']['name'] ?? '',
        'total'       => $lic['seats'] ?? 0,
        'libres'      => $libres,
        'libre'       => $libres > 0,
    ];
}

$snipeError = null;
$bienes     = [];
$hwResp     = snipeRequest('/hardware?limit=500&sort=name&order=asc');

if (isset($hwResp['error'])) {
    $snipeError = $hwResp['error'];
} else {
    foreach ($hwResp['rows'] ?? [] as $item) {
        $meta    = $item['status_label']['status_meta'] ?? $item['status_label']['status_type'] ?? '';
        $esLibre = in_array($meta, ['deployable', 'ready to deploy'], true);
        $bienes[] = [
            'id'          => $item['id'],
            'name'        => $item['name'] ?? '',
            'asset_tag'   => $item['asset_tag'] ?? '',
            'serial'      => $item['serial'] ?? '',
            'model'       => ($item['model']['name'] ?? ''),
            'marca'       => ($item['manufacturer']['name'] ?? ''),
            'categoria'   => ($item['category']['name'] ?? ''),
            'status'      => $item['status_label']['name'] ?? '',
            'estado'      => $esLibre ? 'Libre' : 'Asignado',
            'asignado_a'  => $item['assigned_to']['name'] ?? '',
        ];
    }
    usort($bienes, fn($a, $b) => strcmp($a['estado'], $b['estado']));
}
?>
<?php
// ── Pre-relleno por URL: parámetros GET disponibles ──────────────────────────
// Ejemplo: FormNuevoUsuario.php?nombre=Juan&apellidos=Pérez&correo=j@empresa.com
// Campos: nombre, apellidos, usuario, correo, num_empleado, cargo, telefono,
//         celular, direccion, ciudad, estado, pais, cp, sitio_web, notas,
//         jefe_inmediato, fecha_inicio, fecha_fin, empresa_id, depto_id, ubicacion_id
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nuevo Usuario | BacroCorp</title>
    <link rel="icon" type="image/png" href="Logo2.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f0f7ff,#e6f0ff,#d9e8ff);min-height:100vh;padding:20px}
        #homeButton{position:fixed;top:20px;left:20px;width:52px;height:52px;background:#fff;border:1px solid #d0ddeb;border-radius:16px;font-size:22px;cursor:pointer;box-shadow:0 6px 16px rgba(0,41,87,.1);transition:all .2s;z-index:1000;color:#004787;display:flex;align-items:center;justify-content:center;text-decoration:none}
        #homeButton:hover{background:#004787;color:#fff;transform:translateY(-2px)}
        .main-container{max-width:1200px;margin:75px auto 40px}
        .content-card{background:#fff;border-radius:32px;box-shadow:0 25px 50px -12px rgba(0,41,87,.2);padding:40px;border:1px solid rgba(0,71,151,.15)}
        .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;padding-bottom:24px;border-bottom:2px solid #e9edf4}
        .header-title{font-size:1.9rem;font-weight:700;color:#002f5f;display:flex;align-items:center;gap:12px}
        .header-title i{color:#004787}
        .header-subtitle{font-size:.9rem;color:#5f6c80;margin-top:4px}
        .logo-box{width:80px;height:80px;background:#fff;border-radius:20px;box-shadow:0 10px 25px rgba(0,41,87,.1);display:flex;align-items:center;justify-content:center;border:1px solid rgba(0,71,151,.2);padding:10px;flex-shrink:0}
        .logo-box img{width:100%;height:100%;object-fit:contain}
        .form-section{background:#f8fafd;border:1px solid #e2e8f0;border-radius:20px;padding:28px;margin-bottom:24px}
        .section-title{font-size:.95rem;font-weight:700;color:#002f5f;display:flex;align-items:center;gap:8px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #dde5f0}
        .section-title i{color:#004787}
        .form-label{font-size:.83rem;font-weight:600;color:#002f5f;margin-bottom:5px}
        .form-control,.form-select{border:1px solid #d0ddeb;border-radius:12px;padding:9px 13px;font-size:.9rem;color:#1e2f44;transition:border-color .2s,box-shadow .2s}
        .form-control:focus,.form-select:focus{border-color:#004787;box-shadow:0 0 0 3px rgba(0,71,151,.1);outline:none}
        .req{color:#e53e3e;margin-left:3px}
        /* Foto */
        .foto-wrap{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .foto-preview{width:100px;height:100px;border-radius:50%;border:3px solid #d0ddeb;background:#f0f7ff;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#a0aec0;overflow:hidden;flex-shrink:0}
        .foto-preview img{width:100%;height:100%;object-fit:cover;border-radius:50%}
        .btn-upload{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px dashed #004787;color:#004787;border-radius:12px;padding:9px 18px;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
        .btn-upload:hover{background:#f0f7ff}
        /* Password */
        .pw-wrap{position:relative}
        .pw-wrap .form-control{padding-right:42px}
        .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5f6c80;cursor:pointer;font-size:.9rem;padding:0}
        .btn-gen{background:#004787;color:#fff;border:none;border-radius:12px;padding:9px 16px;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s}
        .btn-gen:hover{background:#002f5f}
        /* Checkboxes custom */
        .check-item{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:all .2s}
        .check-item:hover{border-color:#004787;background:#f0f7ff}
        .check-item input[type=checkbox]{width:17px;height:17px;accent-color:#004787;flex-shrink:0;margin-top:2px}
        .check-item-label{font-size:.88rem;color:#1e2f44;font-weight:600;line-height:1.3}
        .check-item-desc{font-size:.78rem;color:#5f6c80;font-weight:400;margin-top:2px}
        /* Tabla bienes */
        .bienes-wrap{overflow-x:auto;border-radius:14px;border:1px solid #e2e8f0}
        table.btbl{width:100%;border-collapse:collapse;min-width:900px}
        table.btbl thead th{background:#002f5f;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.3px;padding:12px 10px;font-weight:600;white-space:nowrap;border-right:1px solid rgba(255,255,255,.12)}
        table.btbl thead th:last-child{border-right:none}
        table.btbl tbody tr{border-bottom:1px solid #e9edf4;transition:background .12s}
        table.btbl tbody tr:hover{background:#f0f7ff}
        table.btbl tbody tr.row-checked{background:#e0eeff}
        table.btbl tbody td{padding:11px 10px;font-size:.85rem;color:#1e2f44;border-right:1px solid #e9edf4;vertical-align:middle}
        table.btbl tbody td:last-child{border-right:none}
        .cb-asset{width:17px;height:17px;accent-color:#004787;cursor:pointer}
        .badge-libre{background:#e6f7ec;color:#0b6b3f;border:1px solid #b8e0c7;padding:3px 10px;border-radius:40px;font-size:.75rem;font-weight:600;white-space:nowrap}
        .badge-asignado{background:#fff8e7;color:#b85e00;border:1px solid #ffdbb3;padding:3px 10px;border-radius:40px;font-size:.75rem;font-weight:600;white-space:nowrap}
        .inline-date{border:1px solid #d0ddeb;border-radius:8px;padding:5px 8px;font-size:.8rem;color:#1e2f44;width:130px}
        .inline-note{border:1px solid #d0ddeb;border-radius:8px;padding:5px 8px;font-size:.8rem;color:#1e2f44;width:160px}
        /* Contador */
        .selected-counter{display:inline-flex;align-items:center;gap:8px;background:#e0eeff;color:#004787;border:1px solid #b8d6ff;border-radius:40px;padding:4px 14px;font-size:.83rem;font-weight:600;margin-bottom:12px}
        .selected-counter span{font-size:1rem;font-weight:700}
        /* Submit */
        .btn-submit{background:linear-gradient(135deg,#002f5f,#004787);color:#fff;border:none;border-radius:16px;padding:13px 36px;font-size:1rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:10px;transition:all .25s;box-shadow:0 8px 20px rgba(0,41,87,.25)}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,41,87,.35)}
        .btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
        /* Warn reasignación */
        .warn-strip{background:#fff8e7;border:1px solid #ffdbb3;border-radius:12px;padding:11px 15px;font-size:.85rem;color:#b85e00;display:none;align-items:center;gap:10px;margin-bottom:12px}
        .warn-strip.show{display:flex}
        /* Snipe error */
        .snipe-error{background:#fee9e9;border:1px solid #ffcece;border-radius:14px;padding:14px 18px;color:#b02a37;font-size:.88rem;display:flex;align-items:center;gap:10px;margin-bottom:14px}
        /* Divider */
        .divider-label{font-size:.78rem;font-weight:700;color:#9aa6b5;text-transform:uppercase;letter-spacing:.5px;margin:8px 0 4px}
        @media(max-width:768px){.content-card{padding:18px}.page-header{flex-direction:column;gap:12px}.header-title{font-size:1.5rem}}
    </style>
</head>
<body>
<a href="IniSoport.php" id="homeButton" title="Inicio"><i class="fas fa-home"></i></a>

<div class="main-container">
<div class="content-card">

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="header-title"><i class="fas fa-user-plus"></i> Nuevo Usuario</h1>
            <div class="header-subtitle">Registro en Snipe-IT + asignación de activos + carta de resguardo</div>
        </div>
        <div class="logo-box"><img src="Logo2.png" alt="BacroCorp" /></div>
    </div>

    <form id="frmUsuario" enctype="multipart/form-data" novalidate>

    <!-- ══ 1. DATOS DE ACCESO ═════════════════════════════════════════════════ -->
    <div class="form-section">
        <div class="section-title"><i class="fas fa-key"></i> Datos de Acceso</div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Nombre(s)<span class="req">*</span></label>
                <input type="text" class="form-control" name="first_name"
                       placeholder="Nombre(s)" value="<?= tc('nombre') ?>" required />
            </div>
            <div class="col-md-3">
                <label class="form-label">Apellido Paterno<span class="req">*</span></label>
                <input type="text" class="form-control" name="apellido_paterno"
                       placeholder="Paterno" value="<?= tc('apellido_paterno') ?>" required />
            </div>
            <div class="col-md-3">
                <label class="form-label">Apellido Materno</label>
                <input type="text" class="form-control" name="apellido_materno"
                       placeholder="Materno" value="<?= tc('apellido_materno', '', 'apellido_mat') ?>" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Usuario<span class="req">*</span></label>
                <input type="text" class="form-control" name="username" id="username"
                       placeholder="nombre.apellido" value="<?= gp('usuario') ?>" required />
            </div>

            <div class="col-md-5">
                <label class="form-label">Contraseña<span class="req">*</span></label>
                <div class="d-flex gap-2 align-items-center">
                    <div class="pw-wrap flex-grow-1">
                        <input type="password" class="form-control" name="password" id="password"
                               placeholder="Mínimo 8 caracteres" required />
                        <button type="button" class="pw-toggle" id="togglePw" title="Ver / ocultar">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-gen" id="btnGenPw">
                        <i class="fas fa-dice"></i> Generar
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Confirmar Contraseña<span class="req">*</span></label>
                <div class="pw-wrap">
                    <input type="password" class="form-control" name="password_confirmation"
                           id="pwConfirm" placeholder="Repite la contraseña" required />
                    <button type="button" class="pw-toggle" id="togglePwC" title="Ver / ocultar">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <label class="check-item w-100">
                    <input type="checkbox" name="activated" value="1"
                           <?= gp('puede_login', '1') !== '0' ? 'checked' : '' ?> />
                    <div>
                        <div class="check-item-label">Este usuario puede iniciar sesión</div>
                        <div class="check-item-desc">Permite acceso al sistema con estas credenciales</div>
                    </div>
                </label>
            </div>

            <div class="col-md-6">
                <label class="form-label">Correo Electrónico<span class="req">*</span></label>
                <input type="email" class="form-control" name="email"
                       placeholder="correo@bacrocorp.com" value="<?= gp('correo') ?>" required />
            </div>
            <div class="col-md-6">
                <label class="form-label">Foto del Usuario</label>
                <div class="foto-wrap">
                    <div class="foto-preview" id="fotoPreview"><i class="fas fa-user"></i></div>
                    <div style="flex:1">
                        <label class="btn-upload" for="avatar">
                            <i class="fas fa-cloud-upload-alt"></i> Seleccionar imagen
                        </label>
                        <input type="file" id="avatar" name="avatar"
                               accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/avif"
                               style="display:none" />
                        <div id="fotoNom" style="font-size:.78rem;color:#5f6c80;margin-top:5px;">
                            No se eligió ningún archivo<br/>
                            <span style="color:#9aa6b5;">Tipos permitidos: jpg, webp, png, gif, svg, avif — máx. 25 MB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ 2. INFORMACIÓN ADICIONAL ══════════════════════════════════════════ -->
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-info-circle"></i> Información Adicional
            <button type="button" id="btnToggleOpcional"
                    style="margin-left:auto;background:none;border:1px solid #d0ddeb;border-radius:8px;padding:4px 12px;font-size:.8rem;color:#004787;cursor:pointer;">
                <i class="fas fa-chevron-down" id="iconOpcional"></i> Mostrar / Ocultar
            </button>
        </div>
        <div id="seccionOpcional">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Empresa</label>
                    <select class="form-select" name="company_id">
                        <option value="">— Sin empresa —</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= gp('empresa_id') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Departamento</label>
                    <select class="form-select" name="department_id" id="selDept">
                        <option value="">— Sin departamento —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                    data-name="<?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= gp('depto_id') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="department_name" id="deptName" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ubicación</label>
                    <select class="form-select" name="location_id">
                        <option value="">— Sin ubicación —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['id'] ?>"
                                <?= gp('ubicacion_id') == $l['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Número de Empleado</label>
                    <input type="text" class="form-control" name="employee_num"
                           placeholder="EMP-0000" value="<?= gp('num_empleado') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cargo / Puesto</label>
                    <input type="text" class="form-control" name="jobtitle"
                           placeholder="Ej. Analista de TI" value="<?= tc('cargo') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jefe Inmediato / Supervisor</label>
                    <select class="form-select" name="manager_id" id="selManager"
                            onchange="document.getElementById('managerNameHidden').value = this.options[this.selectedIndex].dataset.name || '';">
                        <option value="">— Sin supervisor —</option>
                        <?php foreach ($managers as $mgr): ?>
                            <option value="<?= $mgr['id'] ?>"
                                    data-name="<?= htmlspecialchars($mgr['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= gp('manager_id') == $mgr['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mgr['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="manager_name" id="managerNameHidden"
                           value="<?= tc('jefe_inmediato') ?>" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de Contratación</label>
                    <select class="form-select" name="tipo_contratacion">
                        <option value="PERMANENTE" <?= gp('tipo', 'PERMANENTE') === 'PERMANENTE' ? 'selected' : '' ?>>PERMANENTE</option>
                        <option value="TEMPORAL"   <?= gp('tipo') === 'TEMPORAL' ? 'selected' : '' ?>>TEMPORAL</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Folio</label>
                    <input type="text" class="form-control" name="folio"
                           placeholder="Ej. 2026-001" value="<?= gp('folio') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Idioma</label>
                    <select class="form-select" name="locale">
                        <?php $idiomaVal = gp('idioma', 'es-MX'); ?>
                        <option value="es-MX" <?= $idiomaVal === 'es-MX' ? 'selected' : '' ?>>Español (México)</option>
                        <option value="es"    <?= $idiomaVal === 'es'    ? 'selected' : '' ?>>Español</option>
                        <option value="en-US" <?= $idiomaVal === 'en-US' ? 'selected' : '' ?>>English (US)</option>
                        <option value="fr"    <?= $idiomaVal === 'fr'    ? 'selected' : '' ?>>Français</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="start_date"
                           value="<?= gp('fecha_inicio', date('Y-m-d')) ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" name="end_date"
                           value="<?= gp('fecha_fin') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teléfono Fijo</label>
                    <input type="tel" class="form-control" name="phone"
                           placeholder="(000) 000-0000" value="<?= gp('telefono') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Celular</label>
                    <input type="tel" class="form-control" name="mobile"
                           placeholder="(000) 000-0000" value="<?= gp('celular') ?>" />
                </div>

                <div class="col-md-5">
                    <label class="form-label">Calle</label>
                    <input type="text" class="form-control" name="calle"
                           placeholder="Ej. Av. Reforma" value="<?= tc('calle', '', 'direccion') ?>" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" class="form-control" name="numero"
                           placeholder="500" value="<?= gp('numero', '', 'num_ext') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Colonia</label>
                    <input type="text" class="form-control" name="colonia"
                           placeholder="Centro" value="<?= tc('colonia', '', 'col') ?>" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Código Postal</label>
                    <input type="text" class="form-control" name="zip"
                           placeholder="00000" value="<?= gp('cp') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Municipio / Estado</label>
                    <?php
                        $mun = $_GET['municipio_estado']
                            ?? (isset($_GET['ciudad']) ? trim(($_GET['ciudad'] ?? '') . ' ' . ($_GET['estado'] ?? '')) : null)
                            ?? '';
                        $mun = mb_convert_case($mun, MB_CASE_TITLE, 'UTF-8');
                        $mun = htmlspecialchars($mun, ENT_QUOTES, 'UTF-8');
                    ?>
                    <input type="text" class="form-control" name="municipio_estado"
                           placeholder="Ej. Morelia Michoacán de Ocampo" value="<?= $mun ?>" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">País</label>
                    <input type="text" class="form-control" name="country"
                           placeholder="MX" maxlength="2" value="<?= gp('pais', 'MX') ?>" />
                </div>

                <div class="col-md-6">
                    <label class="form-label">Sitio Web</label>
                    <input type="url" class="form-control" name="website"
                           placeholder="https://" value="<?= gp('sitio_web') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notas</label>
                    <input type="text" class="form-control" name="notes"
                           placeholder="Observaciones adicionales" value="<?= gp('notas') ?>" />
                </div>

                <div class="col-12">
                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <label class="check-item">
                                <input type="checkbox" name="vip" value="1"
                                       <?= gp('vip') === '1' ? 'checked' : '' ?> />
                                <div>
                                    <div class="check-item-label"><i class="fas fa-star text-warning me-1"></i>Usuario VIP</div>
                                    <div class="check-item-desc">Marca personas importantes dentro de la organización.</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="check-item">
                                <input type="checkbox" name="autoassign_licenses" value="1"
                                       <?= gp('auto_licencias', '1') !== '0' ? 'checked' : '' ?> />
                                <div>
                                    <div class="check-item-label"><i class="fas fa-key me-1 text-primary"></i>Auto-asignar Licencias</div>
                                    <div class="check-item-desc">Incluir en asignación masiva de licencias de software.</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="check-item">
                                <input type="checkbox" name="remote" value="1"
                                       <?= gp('remoto') === '1' ? 'checked' : '' ?> />
                                <div>
                                    <div class="check-item-label"><i class="fas fa-wifi me-1 text-info"></i>Usuario Remoto</div>
                                    <div class="check-item-desc">El usuario trabaja desde ubicaciones externas.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ 3. ACTIVOS A ASIGNAR (múltiple) ═══════════════════════════════════ -->
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-laptop"></i> Activos a Asignar
            <span style="margin-left:auto;font-size:.78rem;font-weight:400;color:#5f6c80;">
                <i class="fas fa-circle" style="color:#0b6b3f;font-size:.45rem"></i> Libre &nbsp;
                <i class="fas fa-circle" style="color:#b85e00;font-size:.45rem"></i> Asignado
            </span>
        </div>

        <?php if ($snipeError): ?>
            <div class="snipe-error"><i class="fas fa-exclamation-circle fa-lg"></i>
                No se pudo conectar a Snipe-IT: <strong><?= htmlspecialchars($snipeError) ?></strong></div>
        <?php endif; ?>

        <!-- Advertencia reasignación -->
        <div class="warn-strip" id="warnStrip">
            <i class="fas fa-exclamation-triangle fa-lg"></i>
            <span id="warnMsg"></span>
        </div>

        <!-- Contador + buscador -->
        <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
            <div class="selected-counter" id="counterBadge" style="display:none!important">
                <i class="fas fa-check-circle"></i>
                <span id="counterNum">0</span> activo(s) seleccionado(s)
            </div>
            <input type="text" id="buscador" class="form-control"
                   style="max-width:300px;" placeholder="Buscar por nombre, modelo, serie..." />
        </div>

        <input type="hidden" name="checkout_date_global" id="checkoutDateGlobal" value="<?= date('Y-m-d') ?>" />

        <?php if (!empty($bienes)): ?>
        <div class="bienes-wrap">
            <table class="btbl" id="tblBienes">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center;">
                            <input type="checkbox" id="checkAll" title="Seleccionar todos"
                                   style="width:16px;height:16px;accent-color:#fff;cursor:pointer" />
                        </th>
                        <th>Nombre del Activo</th>
                        <th>Modelo</th>
                        <th>No. Serie</th>
                        <th>Estado</th>
                        <th>Asignado a</th>
                        <th>Fecha de Asignación</th>
                        <th>Fecha Dev. Esperada</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bienes as $b): ?>
                    <?php $esAsig = $b['estado'] === 'Asignado'; ?>
                    <tr class="bien-row"
                        data-id="<?= $b['id'] ?>"
                        data-name="<?= htmlspecialchars($b['name']) ?>"
                        data-model="<?= htmlspecialchars($b['model']) ?>"
                        data-marca="<?= htmlspecialchars($b['marca']) ?>"
                        data-cat="<?= htmlspecialchars($b['categoria']) ?>"
                        data-serial="<?= htmlspecialchars($b['serial']) ?>"
                        data-asignado="<?= $esAsig ? '1' : '0' ?>"
                        data-asignado-a="<?= htmlspecialchars($b['asignado_a']) ?>">

                        <td style="text-align:center;">
                            <input type="checkbox" class="cb-asset"
                                   name="asset_ids[]" value="<?= $b['id'] ?>" />
                            <input type="hidden" class="asset-hidden" name="asset_tipo[]"     value="<?= htmlspecialchars($b['categoria']) ?>" disabled />
                            <input type="hidden" class="asset-hidden" name="asset_marca[]"    value="<?= htmlspecialchars($b['marca']) ?>"    disabled />
                            <input type="hidden" class="asset-hidden" name="asset_modelo[]"   value="<?= htmlspecialchars($b['model']) ?>"    disabled />
                            <input type="hidden" class="asset-hidden" name="asset_serial[]"   value="<?= htmlspecialchars($b['serial']) ?>"   disabled />
                            <input type="hidden" class="asset-hidden" name="asset_caracts[]"  value="" disabled />
                            <input type="hidden" class="asset-hidden" name="asset_assigned[]" value="<?= $esAsig ? '1' : '0' ?>" disabled />
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($b['name']) ?></strong>
                            <?php if ($b['asset_tag']): ?>
                                <br/><span style="font-size:.73rem;color:#9aa6b5;"><?= htmlspecialchars($b['asset_tag']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($b['model']) ?></td>
                        <td style="font-family:monospace;font-size:.78rem;"><?= htmlspecialchars($b['serial']) ?></td>
                        <td>
                            <?php if ($esAsig): ?>
                                <span class="badge-asignado"><i class="fas fa-circle" style="font-size:.35rem;margin-right:4px"></i>Asignado</span>
                            <?php else: ?>
                                <span class="badge-libre"><i class="fas fa-circle" style="font-size:.35rem;margin-right:4px"></i>Libre</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8rem;color:#5f6c80;"><?= htmlspecialchars($b['asignado_a']) ?: '—' ?></td>
                        <td>
                            <input type="date" class="inline-date co-date"
                                   name="checkout_at[]" value="<?= date('Y-m-d') ?>" disabled />
                        </td>
                        <td>
                            <input type="date" class="inline-date ec-date"
                                   name="expected_checkin[]" disabled />
                        </td>
                        <td>
                            <input type="text" class="inline-note"
                                   name="asset_note[]" placeholder="Nota..." disabled />
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:#5f6c80;">
                <i class="fas fa-box-open fa-2x" style="margin-bottom:8px;display:block"></i>
                No se encontraron activos en Snipe-IT.
            </div>
        <?php endif; ?>
    </div>

    <!-- ══ 4. LICENCIAS A ASIGNAR ════════════════════════════════════════════ -->
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-key"></i> Licencias a Asignar
            <span style="margin-left:auto;font-size:.78rem;font-weight:400;color:#5f6c80;">
                <i class="fas fa-circle" style="color:#0b6b3f;font-size:.45rem"></i> Disponible &nbsp;
                <i class="fas fa-circle" style="color:#b85e00;font-size:.45rem"></i> Sin asientos
            </span>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
            <div class="selected-counter" id="licCounterBadge" style="display:none!important">
                <i class="fas fa-check-circle"></i>
                <span id="licCounterNum">0</span> licencia(s) seleccionada(s)
            </div>
            <input type="text" id="buscadorLic" class="form-control"
                   style="max-width:300px;" placeholder="Buscar por nombre, fabricante..." />
        </div>

        <?php if (!empty($licencias)): ?>
        <div class="bienes-wrap">
            <table class="btbl" id="tblLicencias">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center;">
                            <input type="checkbox" id="checkAllLic" title="Seleccionar todas"
                                   style="width:16px;height:16px;accent-color:#fff;cursor:pointer" />
                        </th>
                        <th>Nombre de Licencia</th>
                        <th>Fabricante</th>
                        <th>Categoría</th>
                        <th style="text-align:center;">Asientos Libres</th>
                        <th style="text-align:center;">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($licencias as $lic): ?>
                    <tr class="bien-row lic-row" data-libre="<?= $lic['libre'] ? '1' : '0' ?>">
                        <td style="text-align:center;">
                            <input type="checkbox" class="cb-lic"
                                   name="license_ids[]" value="<?= $lic['id'] ?>"
                                   <?= !$lic['libre'] ? 'disabled title="Sin asientos disponibles"' : '' ?> />
                        </td>
                        <td><strong><?= htmlspecialchars($lic['name']) ?></strong></td>
                        <td><?= htmlspecialchars($lic['fabricante']) ?></td>
                        <td><?= htmlspecialchars($lic['categoria']) ?></td>
                        <td style="text-align:center;">
                            <?php if ($lic['libre']): ?>
                                <span class="badge-libre"><i class="fas fa-circle" style="font-size:.35rem;margin-right:4px"></i><?= $lic['libres'] ?></span>
                            <?php else: ?>
                                <span class="badge-asignado"><i class="fas fa-circle" style="font-size:.35rem;margin-right:4px"></i>0</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;color:#5f6c80;"><?= $lic['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:#5f6c80;">
                <i class="fas fa-key fa-2x" style="margin-bottom:8px;display:block"></i>
                No se encontraron licencias en Snipe-IT.
            </div>
        <?php endif; ?>
    </div>

    <!-- ══ Enviar ══════════════════════════════════════════════════════════════ -->
    <div style="display:flex;justify-content:flex-end;align-items:center;gap:14px;">
        <div id="spinner" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
        <button type="submit" class="btn-submit" id="btnSubmit">
            <i class="fas fa-paper-plane"></i>
            Crear Usuario y Asignar Activos
        </button>
    </div>

    </form>
</div><!-- .content-card -->
</div><!-- .main-container -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
'use strict';

// ── Preview foto ───────────────────────────────────────────────────────────────
const inputFoto = document.getElementById('avatar');
const preview   = document.getElementById('fotoPreview');
const fotoNom   = document.getElementById('fotoNom');
inputFoto.addEventListener('change', function(){
    const file = this.files[0];
    if (!file) return;
    if (file.size > 25*1024*1024){
        Swal.fire({icon:'warning',title:'Imagen muy grande',text:'Máximo 25 MB.',confirmButtonColor:'#004787'});
        this.value=''; return;
    }
    const r = new FileReader();
    r.onload = e => { preview.innerHTML=`<img src="${e.target.result}" />`; };
    r.readAsDataURL(file);
    fotoNom.innerHTML = `<strong>${file.name}</strong>`;
});

// ── Toggle password ────────────────────────────────────────────────────────────
document.getElementById('togglePw').addEventListener('click', function(){
    const inp = document.getElementById('password');
    const ic  = this.querySelector('i');
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    ic.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
});
document.getElementById('togglePwC').addEventListener('click', function(){
    const inp = document.getElementById('pwConfirm');
    const ic  = this.querySelector('i');
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    ic.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
});

// ── Generar contraseña aleatoria ───────────────────────────────────────────────
document.getElementById('btnGenPw').addEventListener('click', function(){
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%&';
    const pw = Array.from({length:12}, () => chars[Math.floor(Math.random()*chars.length)]).join('');
    document.getElementById('password').value = pw;
    document.getElementById('pwConfirm').value = pw;
    document.getElementById('password').type = 'text';
    document.getElementById('pwConfirm').type = 'text';
    Swal.fire({icon:'success',title:'Contraseña generada',html:`<code style="font-size:1.1rem">${pw}</code>`,
        timer:3000,timerProgressBar:true,showConfirmButton:false});
});

// ── Auto username desde nombre + apellido ─────────────────────────────────────
const fnInput = document.querySelector('[name="first_name"]');
const lnInput = document.querySelector('[name="apellido_paterno"]');
const unInput = document.getElementById('username');
function autoUser(){
    if (unInput.dataset.manual) return;
    const f = fnInput.value.trim().toLowerCase().replace(/\s+/g,'.');
    const l = lnInput.value.trim().toLowerCase().split(' ')[0];
    if (f && l) unInput.value = f + '.' + l;
}
fnInput.addEventListener('input', autoUser);
lnInput.addEventListener('input', autoUser);
unInput.addEventListener('input', () => { unInput.dataset.manual = unInput.value ? '1' : ''; });

// ── Toggle sección opcional ────────────────────────────────────────────────────
document.getElementById('btnToggleOpcional').addEventListener('click', function(){
    const sec = document.getElementById('seccionOpcional');
    const ic  = document.getElementById('iconOpcional');
    const visible = sec.style.display !== 'none';
    sec.style.display = visible ? 'none' : '';
    ic.className = visible ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
});

// ── Capturar nombre del departamento para el PDF ───────────────────────────────
document.getElementById('selDept').addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    document.getElementById('deptName').value = opt.dataset.name || '';
});


// ── Buscador activos ──────────────────────────────────────────────────────────
document.getElementById('buscador')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('.bien-row:not(.lic-row)').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── Buscador licencias ────────────────────────────────────────────────────────
document.getElementById('buscadorLic')?.addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('.lic-row').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── Seleccionar todas las licencias ──────────────────────────────────────────
document.getElementById('checkAllLic')?.addEventListener('change', function(){
    document.querySelectorAll('.cb-lic:not(:disabled)').forEach(cb => {
        cb.checked = this.checked;
    });
    updateLicCounter();
});

document.querySelectorAll('.cb-lic').forEach(cb => {
    cb.addEventListener('change', updateLicCounter);
});

function updateLicCounter(){
    const n = document.querySelectorAll('.cb-lic:checked').length;
    const badge = document.getElementById('licCounterBadge');
    document.getElementById('licCounterNum').textContent = n;
    badge.style.display = n > 0 ? 'inline-flex' : 'none';
}

// ── Seleccionar todos ─────────────────────────────────────────────────────────
document.getElementById('checkAll')?.addEventListener('change', async function(){
    const cbs = document.querySelectorAll('.cb-asset');
    if (this.checked) {
        // ¿hay assets asignados entre todos?
        const hayAsig = Array.from(cbs).some(cb => cb.closest('.bien-row').dataset.asignado === '1');
        if (hayAsig) {
            const r = await Swal.fire({
                icon:'warning', title:'Algunos activos ya están asignados',
                html:'Hay activos ya asignados en la lista. ¿Deseas seleccionarlos también?',
                showCancelButton:true, confirmButtonText:'Sí, seleccionar todos',
                cancelButtonText:'Solo libres', confirmButtonColor:'#004787', cancelButtonColor:'#b85e00',
                reverseButtons:true,
            });
            if (!r.isConfirmed) {
                // Solo libres
                cbs.forEach(cb => {
                    const esAsig = cb.closest('.bien-row').dataset.asignado === '1';
                    cb.checked = !esAsig;
                    toggleRowUI(cb);
                });
                this.checked = false;
                updateCounter(); return;
            }
        }
    }
    cbs.forEach(cb => { cb.checked = this.checked; toggleRowUI(cb); });
    updateCounter();
});

// ── Checkbox individual ───────────────────────────────────────────────────────
document.querySelectorAll('.cb-asset').forEach(cb => {
    cb.addEventListener('change', async function(){
        const row    = this.closest('.bien-row');
        const esAsig = row.dataset.asignado === '1';
        const asigA  = row.dataset.asignadoA;

        if (this.checked && esAsig) {
            const r = await Swal.fire({
                icon:'warning', title:'¡Activo ya asignado!',
                html:`Este activo está asignado a <strong>${asigA}</strong>.<br>¿Deseas <strong>reasignarlo</strong>?`,
                showCancelButton:true, confirmButtonText:'Sí, reasignar',
                cancelButtonText:'Cancelar', confirmButtonColor:'#004787',
                cancelButtonColor:'#e53e3e', reverseButtons:true,
            });
            if (!r.isConfirmed) { this.checked = false; return; }
        }
        toggleRowUI(this);
        updateCounter();
        updateWarnStrip();
    });
});

function toggleRowUI(cb){
    const row     = cb.closest('.bien-row');
    const enabled = cb.checked;
    row.classList.toggle('row-checked', enabled);
    row.querySelectorAll('.co-date,.ec-date,.inline-note,.asset-hidden').forEach(el => {
        el.disabled = !enabled;
    });
}

function updateCounter(){
    const n = document.querySelectorAll('.cb-asset:checked').length;
    const badge = document.getElementById('counterBadge');
    document.getElementById('counterNum').textContent = n;
    badge.style.display = n > 0 ? 'inline-flex' : 'none';
}

function updateWarnStrip(){
    const asigChecked = Array.from(document.querySelectorAll('.cb-asset:checked'))
        .filter(cb => cb.closest('.bien-row').dataset.asignado === '1');
    const ws = document.getElementById('warnStrip');
    if (asigChecked.length) {
        document.getElementById('warnMsg').textContent =
            `⚠️ ${asigChecked.length} activo(s) ya asignado(s) serán reasignados al nuevo usuario.`;
        ws.classList.add('show');
    } else { ws.classList.remove('show'); }
}


// ── Envío del formulario ──────────────────────────────────────────────────────
document.getElementById('frmUsuario').addEventListener('submit', async function(e){
    e.preventDefault();

    const firstName = document.querySelector('[name="first_name"]').value.trim();
    const lastName  = document.querySelector('[name="apellido_paterno"]').value.trim();
    const username  = document.querySelector('[name="username"]').value.trim();
    const email     = document.querySelector('[name="email"]').value.trim();
    const pw        = document.getElementById('password').value;
    const pwC       = document.getElementById('pwConfirm').value;
    const selected  = document.querySelectorAll('.cb-asset:checked').length;

    if (!firstName || !lastName || !username || !email) {
        Swal.fire({icon:'warning',title:'Campos requeridos',
            text:'Nombre, Apellidos, Usuario y Correo son obligatorios.',
            confirmButtonColor:'#004787'});
        return;
    }
    if (!pw || pw.length < 8) {
        Swal.fire({icon:'warning',title:'Contraseña inválida',
            text:'La contraseña debe tener al menos 8 caracteres.',
            confirmButtonColor:'#004787'});
        return;
    }
    if (pw !== pwC) {
        Swal.fire({icon:'error',title:'Contraseñas no coinciden',
            confirmButtonColor:'#004787'});
        return;
    }

    const btn     = document.getElementById('btnSubmit');
    const spinner = document.getElementById('spinner');
    btn.disabled  = true;
    spinner.style.display = 'inline-flex';

    try {
        const resp = await fetch('FormNuevoUsuario.php', {
            method:'POST', body: new FormData(this)
        });
        const data = await resp.json();

        if (data.error) {
            Swal.fire({icon:'error',title:'Error',
                html: `<pre style="text-align:left;font-size:.82rem;white-space:pre-wrap">${data.error}</pre>`,
                confirmButtonColor:'#004787'});
            return;
        }

        let html = `<p>${data.message}</p>`;
        if (data.pdf_url) {
            html += `<div style="margin-top:12px">
                <a href="${data.pdf_url}" target="_blank"
                   style="color:#004787;font-weight:600;text-decoration:none">
                   <i class="fas fa-file-pdf" style="color:#e53e3e"></i>
                   &nbsp;Descargar Carta de Resguardo
                </a></div>`;
        }
        if (data.warning) {
            html += `<p style="margin-top:10px;color:#b85e00;font-size:.83rem">
                <i class="fas fa-exclamation-triangle"></i> ${data.warning}</p>`;
        }

        await Swal.fire({icon:'success',title:'¡Usuario creado!',
            html, confirmButtonText:'Nuevo registro', confirmButtonColor:'#004787'});

        this.reset();
        preview.innerHTML = '<i class="fas fa-user"></i>';
        fotoNom.innerHTML  = 'No se eligió ningún archivo';
        document.querySelectorAll('.bien-row').forEach(r => {
            r.classList.remove('row-checked');
            r.querySelectorAll('.co-date,.ec-date,.inline-note').forEach(el => el.disabled=true);
        });
        updateCounter();
        document.getElementById('warnStrip').classList.remove('show');

    } catch(err){
        Swal.fire({icon:'error',title:'Error de conexión',
            text:'No se pudo enviar el formulario.',confirmButtonColor:'#004787'});
    } finally {
        btn.disabled = false;
        spinner.style.display = 'none';
    }
});

})();
</script>
</body>
</html>
