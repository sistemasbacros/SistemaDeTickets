<?php
/**
 * @file FirmarSolicitudBaja.php
 * @brief Vista del firmante (jefe de area) para verificar conceptos y firmar.
 *
 * URL: FirmarSolicitudBaja.php?token=abc123...
 * Carga datos del firmante via GET /api/TicketBacros/solicitud-baja/firmar/{token}
 * Muestra conceptos del departamento, canvas de firma, y envia POST para firmar/rechazar.
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

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_client.php';

// ─── Validación server-side del token ANTES de renderizar la página ───────
// Aprovecha el endpoint EXISTENTE GET /solicitud-baja/firmar/:token, que
// devuelve la vista del firmante (sin tokens de otros firmantes).
$validation = api_call(
    'GET',
    '/api/TicketBacros/solicitud-baja/firmar/' . urlencode($rawToken)
);

if ($validation['http'] === 404) {
    http_response_code(404);
    @error_log("FIRMAR_BAJA_REJECT reason=token_not_found token_len=" . strlen($rawToken)
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Enlace no encontrado</title>';
    echo '<h1>Enlace de firma no encontrado</h1>';
    echo '<p>La solicitud de baja no existe o ya fue eliminada.</p>';
    echo '<p><a href="IniSoport.php">Volver al inicio</a></p>';
    exit;
}

if ($validation['http'] === 400 || $validation['http'] === 422) {
    $msg = $validation['json']['message'] ?? $validation['json']['error'] ?? 'Token inválido';
    http_response_code(410);
    @error_log("FIRMAR_BAJA_REJECT reason=token_invalid msg=" . substr($msg, 0, 100)
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
    @error_log("FIRMAR_BAJA_REJECT reason=api_error http=" . $validation['http']
             . " err=" . substr($validation['error'] ?? '-', 0, 100)
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Servicio no disponible</title>';
    echo '<h1>Servicio temporalmente no disponible</h1>';
    echo '<p>Inténtalo de nuevo en unos minutos.</p>';
    exit;
}

// Validación OK — exponemos metadata para que el JS la pueda usar
// (aunque hoy el JS recarga la misma URL, dejarla aquí ahorra una llamada).
$tokenMeta = $validation['json']['data'] ?? [];

// Si el firmante ya firmó/rechazó, mostrar mensaje informativo en lugar de
// la página de firma. (Defensa en profundidad: el front también lo maneja.)
$firmanteEstatus = $tokenMeta['firmante_estatus'] ?? 'Pendiente';
if ($firmanteEstatus === 'Firmado' || $firmanteEstatus === 'Rechazado') {
    @error_log("FIRMAR_BAJA_REJECT reason=already_signed estatus=$firmanteEstatus"
             . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    // Dejamos que el HTML normal se renderice — el JS detectará el estatus
    // y mostrará el mensaje apropiado. Solo loggeamos.
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

$token    = trim($_GET['token'] ?? '');
$apiUrlJs = htmlspecialchars(getApiUrl());
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Firma de Liberacion de Responsabilidades</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

<style>
* { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
    color: #111; margin: 0; padding: 20px;
    display: flex; flex-direction: column; align-items: center; min-height: 100vh;
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
    max-width: 780px; width: 100%;
}

/* Ficha empleado */
.emp-card {
    background: #f0f4ff; border-left: 4px solid #0033cc;
    border-radius: 8px; padding: 14px 18px; margin-bottom: 22px;
}
.emp-card h3 { margin: 0 0 10px; font-size: 1.05rem; color: #003; }
.emp-row { display: flex; gap: 8px; margin-bottom: 5px; font-size: .9rem; }
.emp-label { color: #555; min-width: 130px; }
.emp-val   { font-weight: 600; }

/* Firmante info */
.firmante-header {
    background: #003366; color: #fff;
    border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
}
.firmante-header .area-name { font-size: 1.1rem; font-weight: 700; }
.firmante-header .firmante-name { font-size: .9rem; opacity: .9; }

.section-title {
    font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #0033cc; margin: 20px 0 10px;
    padding-bottom: 6px; border-bottom: 2px solid #e0e7ff;
}

/* Tabla conceptos */
.conceptos-table {
    width: 100%; border-collapse: collapse; font-size: .9rem; margin-bottom: 18px;
}
.conceptos-table thead tr { background: #003366; color: #fff; }
.conceptos-table th { padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
.conceptos-table td { padding: 9px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
.conceptos-table tbody tr:hover { background: #f5f8ff; }

.radio-group { display: flex; gap: 12px; }
.radio-group label { display: flex; align-items: center; gap: 4px; cursor: pointer; font-weight: 500; }
.radio-group input[type="radio"] { accent-color: #0033cc; }

.monto-input {
    width: 120px; padding: 6px 8px; border-radius: 6px;
    border: 1px solid #bbb; font-size: .9rem;
    font-family: 'Inter', sans-serif;
}
.monto-input:focus { border-color: #0033cc; outline: none; }

/* Canvas firma */
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

textarea {
    width: 100%; padding: 10px 12px;
    border-radius: 8px; border: 1px solid #bbb;
    font-size: .95rem; font-family: 'Inter', sans-serif;
    background: #f9f9f9; color: #111; resize: vertical;
    transition: border-color .2s;
}
textarea:focus { border-color: #0033cc; outline: none; background: #eaeaea; }

.btn-limpiar {
    background: #6c757d; color: #fff; border: none;
    border-radius: 6px; padding: 7px 14px; font-size: .9rem;
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: background .2s;
}
.btn-limpiar:hover { background: #545b62; }

.btn-row { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }

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

#loading { text-align: center; padding: 40px; color: #888; }
#loading i { font-size: 2rem; display: block; margin-bottom: 10px; }
#contenido { display: none; }

@media (max-width: 600px) {
    .container { padding: 18px; }
    h2 { font-size: 1.4rem; }
    .emp-row { flex-direction: column; gap: 2px; }
    #firma-canvas { height: 130px; }
    .monto-input { width: 90px; }
}
</style>
</head>
<body>

<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-file-signature"></i> Liberacion de Responsabilidades — Firma</h2>

<div class="container">

<?php if (!$token): ?>
    <div style="text-align:center;padding:20px 0">
        <div style="font-size:3rem;color:#0033cc;margin-bottom:14px"><i class="fas fa-key"></i></div>
        <p style="color:#555;margin-bottom:18px;font-size:.95rem">
            Ingrese el token de firma que recibio por correo electronico<br>
            o utilice directamente el enlace del correo.
        </p>
        <form method="GET" action="" style="display:flex;gap:10px;max-width:500px;margin:0 auto">
            <input type="text" name="token" placeholder="Pegue aqui su token de firma..."
                   required style="
                       flex:1;padding:12px 14px;border-radius:8px;
                       border:1px solid #bbb;font-size:.95rem;
                       font-family:'Inter',monospace;background:#f9f9f9;
                   " />
            <button type="submit" style="
                background:#0033cc;color:#fff;border:none;
                border-radius:8px;padding:12px 20px;
                font-size:.95rem;font-weight:600;cursor:pointer;
                font-family:'Inter',sans-serif;white-space:nowrap;
            "><i class="fas fa-arrow-right"></i> Ir</button>
        </form>
    </div>
<?php else: ?>

    <div id="loading">
        <i class="fas fa-spinner fa-spin"></i>
        Cargando informacion del firmante...
    </div>

    <div id="contenido">
        <!-- Info empleado -->
        <div class="emp-card" id="empCard"></div>

        <!-- Header firmante -->
        <div class="firmante-header" id="firmanteHeader"></div>

        <!-- Alerta si ya firmo -->
        <div id="alertaYaFirmo" style="display:none"></div>

        <!-- Conceptos -->
        <div id="conceptosSection" style="display:none">
            <div class="section-title"><i class="fas fa-clipboard-check"></i> Verificar Conceptos</div>
            <div style="overflow-x:auto">
                <table class="conceptos-table">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Se Entrega</th>
                            <th>Monto a Descontar</th>
                        </tr>
                    </thead>
                    <tbody id="conceptosBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Firma -->
        <div id="firmaSection" style="display:none">
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
        </div>
    </div>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// HTTPS (producción) → same-origin (nginx hace proxy interno a localhost:3000)
// HTTP (dev local)   → puerto 3000 explícito (frontend y backend separados)
const API_URL = window.location.protocol === 'https:'
    ? window.location.origin
    : window.location.protocol + '//' + window.location.hostname + ':3000';
const TOKEN   = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';

let firmanteData = null;
let conceptos = [];

// ── Canvas de firma ─────────────────────────────────────────────
const canvas = document.getElementById('firma-canvas');
const ctx    = canvas ? canvas.getContext('2d') : null;
let dibujando = false;
let tieneTrazo = false;

if (canvas) {
    function ajustarCanvas() {
        const rect = canvas.getBoundingClientRect();
        // Si el canvas no es visible aun, reintentar despues
        if (rect.width === 0) {
            setTimeout(ajustarCanvas, 100);
            return;
        }
        const dpr  = window.devicePixelRatio || 1;
        canvas.width  = rect.width  * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#000';
        ctx.lineWidth   = 2.2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
    }

    function getPos(e) {
        const r = canvas.getBoundingClientRect();
        if (e.touches) {
            return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
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
        dibujando = true; ctx.beginPath(); ctx.moveTo(p.x, p.y);
    }, { passive: false });
    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        if (!dibujando) return;
        const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); tieneTrazo = true;
    }, { passive: false });
    canvas.addEventListener('touchend', () => dibujando = false);
}

function limpiarFirma() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    tieneTrazo = false;
}

// ── Cargar datos ────────────────────────────────────────────────
async function cargarDatos() {
    if (!TOKEN) return;

    try {
        const res = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/firmar/${TOKEN}`);
        const data = await res.json();

        if (!res.ok || !data.data) {
            throw new Error(data.message || 'No se pudo cargar la informacion del firmante.');
        }

        firmanteData = data.data;
        conceptos = Array.isArray(firmanteData.conceptos_departamento) ? firmanteData.conceptos_departamento : [];

        // Renderizar info del empleado
        const empCard = document.getElementById('empCard');
        empCard.innerHTML = `
            <h3><i class="fas fa-user"></i> Empleado que causa baja</h3>
            <div class="emp-row">
                <span class="emp-label">Nombre:</span>
                <span class="emp-val">${esc(firmanteData.emp_nombre_completo || '-')}</span>
            </div>
            <div class="emp-row">
                <span class="emp-label">No. Empleado:</span>
                <span class="emp-val">${esc(firmanteData.emp_id_empleado || '-')}</span>
            </div>
            <div class="emp-row">
                <span class="emp-label">Puesto:</span>
                <span class="emp-val">${esc(firmanteData.emp_puesto || '-')}</span>
            </div>
            <div class="emp-row">
                <span class="emp-label">Departamento:</span>
                <span class="emp-val">${esc(firmanteData.emp_departamento || '-')}</span>
            </div>
            <div class="emp-row">
                <span class="emp-label">Fecha Salida:</span>
                <span class="emp-val">${esc(firmanteData.fecha_salida || '-')}</span>
            </div>
        `;

        // Header del firmante
        document.getElementById('firmanteHeader').innerHTML = `
            <div>
                <div class="area-name"><i class="fas fa-building"></i> Area: ${esc(firmanteData.firmante_area || '-')}</div>
                <div class="firmante-name"><i class="fas fa-user-tie"></i> ${esc(firmanteData.firmante_nombre || 'Firmante')}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.85rem;opacity:.8">Estatus</div>
                <div style="font-weight:700">${esc(firmanteData.firmante_estatus || 'Pendiente')}</div>
            </div>
        `;

        // Si ya firmo o esta rechazado
        const estatus = firmanteData.firmante_estatus || 'Pendiente';
        if (estatus === 'Firmado' || estatus === 'Rechazado') {
            const alertEl = document.getElementById('alertaYaFirmo');
            alertEl.style.display = 'block';
            alertEl.innerHTML = `
                <div class="alert-warning">
                    <i class="fas fa-info-circle"></i>
                    Usted ya ${estatus === 'Firmado' ? 'firmo' : 'rechazo'} esta solicitud.
                    No es posible realizar cambios.
                </div>`;
        } else {
            // Mostrar conceptos si los hay
            if (conceptos.length > 0) {
                document.getElementById('conceptosSection').style.display = 'block';
                const tbody = document.getElementById('conceptosBody');
                tbody.innerHTML = '';
                conceptos.forEach((c, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${esc(c)}</strong></td>
                            <td>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="entrega_${i}" value="true" checked /> SI
                                    </label>
                                    <label>
                                        <input type="radio" name="entrega_${i}" value="false" /> NO
                                    </label>
                                </div>
                            </td>
                            <td>
                                <input type="number" class="monto-input" id="monto_${i}"
                                       placeholder="0.00" step="0.01" min="0" />
                            </td>
                        </tr>`;
                });
            }

            // Mostrar seccion de firma
            document.getElementById('firmaSection').style.display = 'block';
        }

        document.getElementById('loading').style.display = 'none';
        document.getElementById('contenido').style.display = 'block';

        // Ajustar canvas ahora que es visible
        if (canvas) ajustarCanvas();

    } catch (err) {
        document.getElementById('loading').innerHTML = `
            <div class="alert-danger">
                <i class="fas fa-exclamation-triangle"></i><br><br>
                ${err.message}
            </div>`;
    }
}

function esc(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ── Enviar firma ────────────────────────────────────────────────
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
            title: 'Rechazar la solicitud?',
            text: 'Esta accion podria detener el proceso de firmas. Esta seguro?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Si, rechazar',
            cancelButtonText:   'Cancelar',
        });
        if (!conf.isConfirmed) return;
    }

    let firmaB64 = null;
    if (accion === 'Firmado' && canvas && tieneTrazo) {
        // Exportar firma sin escala DPR para que la imagen sea visible
        const exportCanvas = document.createElement('canvas');
        const rect = canvas.getBoundingClientRect();
        exportCanvas.width = Math.round(rect.width);
        exportCanvas.height = Math.round(rect.height);
        const exportCtx = exportCanvas.getContext('2d');
        exportCtx.drawImage(canvas, 0, 0, exportCanvas.width, exportCanvas.height);
        firmaB64 = exportCanvas.toDataURL('image/png');
    }

    const comentarios = document.getElementById('comentarios')?.value.trim() || null;

    // Construir respuestas de conceptos
    const respuestas = conceptos.map((concepto, i) => {
        const radios = document.querySelectorAll(`input[name="entrega_${i}"]`);
        let seEntrega = null;
        radios.forEach(r => { if (r.checked) seEntrega = r.value === 'true'; });
        if (seEntrega === null) seEntrega = true;
        const montoEl = document.getElementById(`monto_${i}`);
        const monto = montoEl && montoEl.value ? montoEl.value : null;
        return { concepto, se_entrega: seEntrega, monto };
    });

    // Deshabilitar botones
    ['btnFirmar', 'btnRechazar'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) btn.disabled = true;
    });

    Swal.fire({
        title: 'Procesando...',
        text: 'Enviando firma al servidor',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const res = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/firmar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token: TOKEN,
                accion: accion,
                firma_img_base64: firmaB64,
                comentarios: comentarios,
                respuestas_conceptos: respuestas.length > 0 ? respuestas : undefined,
            }),
        });

        const data = await res.json();

        if (data.success !== false && res.ok) {
            await Swal.fire({
                title: accion === 'Firmado' ? 'Firma registrada!' : 'Solicitud rechazada',
                html: `
                    <div style="text-align:center">
                        <div style="font-size:52px;margin-bottom:12px">${accion === 'Firmado' ? '&#9989;' : '&#10060;'}</div>
                        <p>${data.message || 'Accion procesada correctamente.'}</p>
                        ${data.estatus_general ? `<p style="font-size:.9rem;color:#555">Estatus general: <strong>${data.estatus_general}</strong></p>` : ''}
                    </div>`,
                icon: accion === 'Firmado' ? 'success' : 'info',
                confirmButtonColor: '#003366',
                confirmButtonText: 'Cerrar',
                allowOutsideClick: false,
            });
            // Recargar para mostrar estado actualizado
            window.location.reload();
        } else {
            throw new Error(data.message || 'El servidor respondio con error.');
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

// Inicio
cargarDatos();
</script>
</body>
</html>
