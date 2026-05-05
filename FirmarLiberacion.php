<?php
// DEPRECATED 2026-05-05: sistema reemplazado por Solicitud de Baja v2
// (BCR-TH-SGI-FO-27 con conceptos por area y firmantes configurables).
// Los tokens del sistema viejo NO funcionan en el nuevo (tablas distintas).
// Mostramos pagina de aviso 410 con mensaje claro en vez de redirect ciego,
// para que el firmante entienda por que su link viejo no funciona.
http_response_code(410); // Gone
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Sistema descontinuado — Liberación de Responsabilidades</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;max-width:640px;margin:60px auto;padding:0 20px;color:#222}
h1{color:#c00}
.box{background:#fff8e1;border-left:4px solid #ffa000;padding:16px 20px;border-radius:4px;margin:24px 0}
a{color:#0066cc}
</style>
</head>
<body>
<h1>Este link ya no es válido</h1>
<div class="box">
  <p>El sistema de <strong>Liberación de Responsabilidades v1</strong> fue
     reemplazado el 5 de mayo de 2026 por <strong>Solicitud de Baja v2</strong>
     (BCR-TH-SGI-FO-27).</p>
  <p>Si tienes una solicitud pendiente de firmar, espera el nuevo correo con
     un link actualizado o contacta al área de Talento Humano.</p>
</div>
<p>Si crees que recibiste este mensaje por error, escribe a
   <a href="mailto:soporte@bacrocorp.com">soporte@bacrocorp.com</a>
   indicando el folio del email.</p>
<?php exit; ?>

<?php
/**
 * @file FirmarLiberacion.php (DEPRECATED — ver FirmarSolicitudBaja.php)
 * @brief Página de firma para jefes que reciben el link por correo.
 *
 * URL de entrada: FirmarLiberacion.php?folio=LIB-2026-XXXX&token=abc123...
 * Muestra datos del empleado, canvas para firma digital y botones Firmar/Rechazar.
 * Llama POST /api/TicketBacros/liberacion/firmar
 *
 * NOTA: Página pública por token (magic link). NO incluir auth_check.php
 * — la autenticación la hace el token UUID validado contra la API.
 */

// Token format guard — reject malformed tokens early so probes can't fingerprint the page.
$rawToken = $_GET['token'] ?? '';
if (!is_string($rawToken) || $rawToken === '' || strlen($rawToken) > 256
    || !preg_match('/^[A-Za-z0-9_\-\.]+$/', $rawToken)) {
    http_response_code(400);
    @error_log(sprintf(
        'TOKEN_REJECT script=%s ip=%s user=%s len=%d',
        basename($_SERVER['SCRIPT_NAME'] ?? '-'),
        $_SERVER['REMOTE_ADDR']     ?? '-',
        $_SESSION['user_username']  ?? '-',
        is_string($rawToken) ? strlen($rawToken) : 0
    ));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Token inválido</title>';
    echo '<h1>Token inválido</h1>';
    echo '<p>El enlace de firma que abriste no es válido. Si llegaste aquí desde un correo, vuelve al mensaje original y haz clic de nuevo. Si el problema persiste, contacta al administrador.</p>';
    echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
    exit;
}

// Folio format guard — folios siguen patron tipo LIB-2026-XXXX (alfanumerico + guion/underscore).
$rawFolio = $_GET['folio'] ?? '';
if (!is_string($rawFolio) || $rawFolio === '' || !preg_match('/^[A-Z0-9_\-]{1,64}$/', $rawFolio)) {
    http_response_code(400);
    @error_log(sprintf(
        'FOLIO_REJECT script=%s ip=%s user=%s len=%d',
        basename($_SERVER['SCRIPT_NAME'] ?? '-'),
        $_SERVER['REMOTE_ADDR']     ?? '-',
        $_SESSION['user_username']  ?? '-',
        is_string($rawFolio) ? strlen($rawFolio) : 0
    ));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Folio inválido</title>';
    echo '<h1>Folio inválido</h1>';
    echo '<p>El enlace de firma que abriste no es válido. Si llegaste aquí desde un correo, vuelve al mensaje original y haz clic de nuevo. Si el problema persiste, contacta al administrador.</p>';
    echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_client.php';

// ─── Validación server-side del token ANTES de renderizar la página ───────
// Defensa en profundidad: el regex de arriba ya descartó tokens malformados,
// ahora verificamos contra la API que el token EXISTE, está Pendiente y es turno.
$validation = api_call('GET', '/api/TicketBacros/liberacion/check-token/' . urlencode($rawToken));

if ($validation['http'] === 404) {
    http_response_code(404);
    @error_log("FIRMAR_LIB_REJECT reason=token_not_found token_len=" . strlen($rawToken)
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Enlace no encontrado</title>';
    echo '<h1>Enlace de firma no encontrado</h1>';
    echo '<p>El enlace de firma no existe o ya fue eliminado.</p>';
    echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
    exit;
}

if ($validation['http'] === 400 || $validation['http'] === 422) {
    $msg = $validation['json']['message'] ?? $validation['json']['error'] ?? 'Token inválido';
    http_response_code(410);
    @error_log("FIRMAR_LIB_REJECT reason=token_invalid msg=" . substr($msg, 0, 100)
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Enlace ya usado o no es turno</title>';
    echo '<h1>Este enlace ya fue usado o aún no es tu turno de firmar</h1>';
    echo '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
    exit;
}

if (!$validation['ok']) {
    http_response_code(503);
    @error_log("FIRMAR_LIB_REJECT reason=api_error http=" . $validation['http']
             . " err=" . substr($validation['error'] ?? '-', 0, 100)
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Servicio no disponible</title>';
    echo '<h1>Servicio temporalmente no disponible</h1>';
    echo '<p>Inténtalo de nuevo en unos minutos.</p>';
    exit;
}

// Validación server-side OK — extraemos metadata mínima.
// Esto NO incluye tokens de otros firmantes ni firmas almacenadas.
$tokenMeta = $validation['json']['data'] ?? [];

// Asegurar que el folio en la URL coincide con el folio que respondió el API.
if (!empty($_GET['folio']) && isset($tokenMeta['folio'])
    && !hash_equals((string)$tokenMeta['folio'], (string)$_GET['folio'])) {
    http_response_code(400);
    @error_log("FIRMAR_LIB_REJECT reason=folio_mismatch url_folio=" . $_GET['folio']
             . " token_folio=" . $tokenMeta['folio']
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Enlace inconsistente</title>';
    echo '<h1>Enlace inconsistente</h1>';
    echo '<p>El folio del enlace no coincide con el firmante. Revisa el correo original.</p>';
    exit;
}

function getApiUrl(): string {
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

$folio  = trim($_GET['folio']  ?? '');
$token  = trim($_GET['token']  ?? '');
$sol    = null;
$errMsg = null;

// Validación básica de parámetros
if (!$folio || !$token) {
    $errMsg = 'Enlace incompleto. Se requiere folio y token. Por favor usa el link de tu correo.';
} else {
    // Pre-cargar datos del empleado para mostrar contexto al firmante
    $apiUrl = getApiUrl();
    $ch = curl_init($apiUrl . '/api/TicketBacros/liberacion/' . urlencode($folio));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $resp    = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        $errMsg = 'No se pudo conectar al servidor: ' . htmlspecialchars($curlErr);
    } elseif ($httpCode === 404) {
        $errMsg = 'Folio <strong>' . htmlspecialchars($folio) . '</strong> no encontrado.';
    } elseif ($httpCode !== 200) {
        $errMsg = 'Error al cargar los datos de la solicitud (HTTP ' . $httpCode . ').';
    } else {
        $data = json_decode($resp, true);
        $sol  = $data['data'] ?? $data ?? null;
        if (!$sol || !isset($sol['folio'])) {
            $errMsg = 'Respuesta inesperada del servidor.';
            $sol = null;
        }
    }
}

// API URL para el JS
$apiUrlJs = htmlspecialchars(getApiUrl());
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Firma de Liberación<?= $folio ? ' — ' . htmlspecialchars($folio) : '' ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

<style>
* { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
    color: #111;
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

img.logo {
    position: absolute; top: 20px; right: 20px;
    width: 130px; height: 130px; object-fit: contain;
    filter: drop-shadow(0 0 3px #999);
    animation: float 4s ease-in-out infinite;
}
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

h2 {
    font-weight: 700; font-size: 1.9rem;
    margin: 10px 0 22px; color: #333; text-align: center;
}

.container {
    background: #fff; padding: 28px 36px;
    border-radius: 15px; box-shadow: 0 12px 25px rgba(0,0,0,.1);
    max-width: 680px; width: 100%;
}

/* Ficha del empleado */
.emp-card {
    background: #f0f4ff; border-left: 4px solid #0033cc;
    border-radius: 8px; padding: 14px 18px; margin-bottom: 22px;
}
.emp-card h3 { margin: 0 0 10px; font-size: 1.05rem; color: #003; }
.emp-row { display: flex; gap: 8px; margin-bottom: 5px; font-size: .9rem; }
.emp-label { color: #555; min-width: 130px; }
.emp-val   { font-weight: 600; }

/* Folio badge */
.folio-badge {
    background: #003366; color: #fff;
    display: inline-block; padding: 6px 18px;
    border-radius: 20px; font-size: 1rem; font-weight: 700;
    margin-bottom: 4px;
}

/* Sección firma */
.section-title {
    font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #0033cc; margin: 20px 0 10px;
    padding-bottom: 6px; border-bottom: 2px solid #e0e7ff;
}

#firma-canvas {
    display: block;
    border: 2px solid #333;
    border-radius: 6px;
    cursor: crosshair;
    background: #fff;
    width: 100%;
    max-width: 560px;
    height: 160px;
    touch-action: none;
}

.canvas-toolbar {
    display: flex; gap: 10px; margin-top: 8px; flex-wrap: wrap;
}
.btn-limpiar {
    background: #6c757d; color: #fff; border: none;
    border-radius: 6px; padding: 7px 14px; font-size: .9rem;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s;
}
.btn-limpiar:hover { background: #545b62; }

textarea {
    width: 100%; padding: 10px 12px;
    border-radius: 8px; border: 1px solid #bbb;
    font-size: .95rem; font-family: 'Inter', sans-serif;
    background: #f9f9f9; color: #111; resize: vertical;
    transition: border-color .2s;
}
textarea:focus { border-color: #0033cc; outline: none; background: #eaeaea; }

.btn-row {
    display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap;
}

.btn-firmar {
    flex: 1; min-width: 160px;
    background: #28a745; color: #fff;
    border: none; border-radius: 8px;
    padding: 13px 20px; font-size: 1rem; font-weight: 700;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s, transform .15s;
}
.btn-firmar:hover { background: #218838; transform: translateY(-2px); }

.btn-rechazar {
    flex: 1; min-width: 160px;
    background: #dc3545; color: #fff;
    border: none; border-radius: 8px;
    padding: 13px 20px; font-size: 1rem; font-weight: 700;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s, transform .15s;
}
.btn-rechazar:hover { background: #c82333; transform: translateY(-2px); }

button:disabled { opacity: .6; cursor: not-allowed; transform: none !important; }

.alert-danger {
    background: #f8d7da; border-left: 4px solid #dc3545;
    border-radius: 6px; padding: 14px 18px; color: #721c24;
    text-align: center; font-weight: 600;
}
.alert-warning {
    background: #fff3cd; border-left: 4px solid #ffc107;
    border-radius: 6px; padding: 12px 16px; color: #856404;
    font-size: .9rem; margin-bottom: 16px;
}

@media (max-width: 600px) {
    .container { padding: 18px; }
    h2 { font-size: 1.4rem; }
    .emp-row { flex-direction: column; gap: 2px; }
    #firma-canvas { height: 130px; }
}
</style>
</head>
<body>

<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-file-signature"></i> Liberación de Responsabilidades</h2>

<div class="container">

<?php if ($errMsg): ?>
    <div class="alert-danger">
        <i class="fas fa-exclamation-triangle"></i><br><br>
        <?= $errMsg ?>
    </div>

<?php elseif ($sol): ?>

    <?php
    $firmantes      = $sol['firmantes']      ?? [];
    $firmanteActual = (int)($sol['firmante_actual'] ?? 1);
    $estatusGral    = $sol['estatus_general'] ?? 'Pendiente';
    $total          = 14;
    $porcentaje     = $total > 0 ? round(($firmanteActual - 1) / $total * 100) : 0;

    // Verificar si el proceso ya terminó
    $yaTerminado = in_array($estatusGral, ['Completado', 'Rechazado']);
    ?>

    <?php if ($yaTerminado): ?>
        <div class="alert-warning">
            <i class="fas fa-info-circle"></i>
            Esta solicitud tiene estatus <strong><?= htmlspecialchars($estatusGral) ?></strong>.
            <?= $estatusGral === 'Completado' ? 'El proceso de firmas ha concluido.' : 'El proceso fue rechazado.' ?>
        </div>
    <?php endif; ?>

    <!-- Folio -->
    <div style="text-align:center;margin-bottom:16px">
        <div class="folio-badge"><?= htmlspecialchars($sol['folio']) ?></div>
        <div style="font-size:.85rem;color:#555;margin-top:4px">
            Firmante <?= $firmanteActual ?> de <?= $total ?>
        </div>
    </div>

    <!-- Datos del empleado -->
    <div class="emp-card">
        <h3><i class="fas fa-user"></i> Empleado que causa baja</h3>
        <div class="emp-row">
            <span class="emp-label">Nombre:</span>
            <span class="emp-val">
                <?= htmlspecialchars(($sol['emp_nombre'] ?? '') . ' ' . ($sol['emp_apellido_paterno'] ?? '') . ' ' . ($sol['emp_apellido_materno'] ?? '')) ?>
            </span>
        </div>
        <div class="emp-row">
            <span class="emp-label">ID Empleado:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_id_empleado'] ?? '—') ?></span>
        </div>
        <div class="emp-row">
            <span class="emp-label">Departamento:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_departamento'] ?? '—') ?></span>
        </div>
        <div class="emp-row">
            <span class="emp-label">Puesto:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_puesto'] ?? '—') ?></span>
        </div>
        <div class="emp-row">
            <span class="emp-label">Fecha de Baja:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_fecha_baja'] ?? '—') ?></span>
        </div>
        <div class="emp-row">
            <span class="emp-label">Motivo:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_motivo_baja'] ?? '—') ?></span>
        </div>
    </div>

    <?php if (!$yaTerminado): ?>

    <!-- Sección firma digital -->
    <div class="section-title"><i class="fas fa-pen-nib"></i> Firma Digital</div>

    <p style="font-size:.9rem;color:#555;margin-bottom:8px">
        Dibuje su firma en el recuadro usando el mouse o toque la pantalla:
    </p>

    <canvas id="firma-canvas" width="560" height="160"></canvas>

    <div class="canvas-toolbar">
        <button type="button" class="btn-limpiar" onclick="limpiarFirma()">
            <i class="fas fa-eraser"></i> Limpiar firma
        </button>
        <span style="font-size:.8rem;color:#999;align-self:center">
            <i class="fas fa-info-circle"></i> Se requiere firma para Aprobar
        </span>
    </div>

    <div class="section-title" style="margin-top:22px"><i class="fas fa-comment"></i> Comentarios</div>
    <textarea id="comentarios" rows="3" placeholder="Comentarios opcionales..."></textarea>

    <div class="btn-row">
        <button class="btn-firmar" id="btnFirmar" onclick="enviarAccion('Firmado')">
            <i class="fas fa-check-circle"></i> Firmar y Aprobar
        </button>
        <button class="btn-rechazar" id="btnRechazar" onclick="enviarAccion('Rechazado')">
            <i class="fas fa-times-circle"></i> Rechazar
        </button>
    </div>

    <?php else: ?>
        <p style="text-align:center;color:#888;margin-top:10px">
            No hay acciones disponibles para esta solicitud.
        </p>
    <?php endif; ?>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const API_URL = '<?= $apiUrlJs ?>';
const FOLIO   = '<?= htmlspecialchars($folio, ENT_QUOTES) ?>';
const TOKEN   = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';

// ── Canvas de firma ──────────────────────────────────────────────────────────
const canvas = document.getElementById('firma-canvas');
const ctx    = canvas ? canvas.getContext('2d') : null;
let dibujando = false;
let tieneTrazo = false;

if (canvas) {
    // Ajustar resolución real vs tamaño CSS para que no se vea pixelado
    function ajustarCanvas() {
        const rect = canvas.getBoundingClientRect();
        const dpr  = window.devicePixelRatio || 1;
        canvas.width  = rect.width  * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#000';
        ctx.lineWidth   = 2.2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
    }
    ajustarCanvas();

    function getPos(e) {
        const r = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        if (e.touches) {
            return { x: (e.touches[0].clientX - r.left), y: (e.touches[0].clientY - r.top) };
        }
        return { x: e.offsetX, y: e.offsetY };
    }

    canvas.addEventListener('mousedown',  e => { dibujando = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); });
    canvas.addEventListener('mousemove',  e => { if (!dibujando) return; ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); tieneTrazo = true; });
    canvas.addEventListener('mouseup',    () => dibujando = false);
    canvas.addEventListener('mouseleave', () => dibujando = false);

    canvas.addEventListener('touchstart', e => {
        e.preventDefault();
        const p = getPos(e);
        dibujando = true;
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }, { passive: false });
    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        if (!dibujando) return;
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        tieneTrazo = true;
    }, { passive: false });
    canvas.addEventListener('touchend', () => dibujando = false);
}

function limpiarFirma() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    tieneTrazo = false;
}

// ── Envío al API ─────────────────────────────────────────────────────────────
async function enviarAccion(accion) {
    if (accion === 'Firmado' && !tieneTrazo) {
        await Swal.fire({
            title: 'Firma requerida',
            text: 'Por favor dibuje su firma en el recuadro antes de aprobar.',
            icon: 'warning',
            confirmButtonColor: '#003366',
        });
        return;
    }

    if (accion === 'Rechazado') {
        const conf = await Swal.fire({
            title: '¿Rechazar la solicitud?',
            text: 'Esta acción detendrá el proceso de firmas. ¿Está seguro?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Sí, rechazar',
            cancelButtonText:   'Cancelar',
        });
        if (!conf.isConfirmed) return;
    }

    let firmaB64 = null;
    if (accion === 'Firmado' && canvas) {
        const exportCanvas = document.createElement('canvas');
        const rect = canvas.getBoundingClientRect();
        exportCanvas.width = Math.round(rect.width);
        exportCanvas.height = Math.round(rect.height);
        const exportCtx = exportCanvas.getContext('2d');
        exportCtx.drawImage(canvas, 0, 0, exportCanvas.width, exportCanvas.height);
        firmaB64 = exportCanvas.toDataURL('image/png');
    }

    const comentarios = document.getElementById('comentarios')?.value.trim() ?? '';

    // Deshabilitar botones mientras se procesa
    ['btnFirmar', 'btnRechazar'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) { btn.disabled = true; }
    });

    Swal.fire({
        title: 'Procesando...',
        text: 'Enviando firma al servidor',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const res = await fetch(API_URL + '/api/TicketBacros/liberacion/firmar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                folio:             FOLIO,
                token:             TOKEN,
                firma_img_base64:  firmaB64,
                comentarios:       comentarios || null,
                accion:            accion,
            }),
        });

        const data = await res.json();

        if (data.success) {
            await Swal.fire({
                title: accion === 'Firmado' ? '¡Firma registrada!' : 'Solicitud rechazada',
                html: `
                    <div style="text-align:center">
                        <div style="font-size:52px;margin-bottom:12px">${accion === 'Firmado' ? '✅' : '❌'}</div>
                        <p>${data.message || 'Acción procesada correctamente.'}</p>
                        ${data.estatus_general ? `<p style="font-size:.9rem;color:#555">Estatus: <strong>${data.estatus_general}</strong> | Firmante: ${data.firmante_actual} / 14</p>` : ''}
                    </div>`,
                icon: accion === 'Firmado' ? 'success' : 'info',
                confirmButtonColor: '#003366',
                confirmButtonText: 'Cerrar',
                allowOutsideClick: false,
            });
            // Recargar la página para mostrar estado actualizado
            window.location.href = 'EstadoLiberacion.php?folio=' + encodeURIComponent(FOLIO);
        } else {
            throw new Error(data.message || 'El servidor respondió con error.');
        }
    } catch (err) {
        ['btnFirmar', 'btnRechazar'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = false;
        });
        Swal.fire({
            title: 'Error al enviar',
            text: err.message,
            icon: 'error',
            confirmButtonColor: '#003366',
        });
    }
}
</script>
</body>
</html>
