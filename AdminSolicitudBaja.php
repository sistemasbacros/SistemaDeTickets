<?php
/**
 * @file AdminSolicitudBaja.php
 * @brief Vista Admin para configurar firmantes por area en una Solicitud de Baja.
 *
 * Recibe admin_token por GET, carga datos de la solicitud y catalogo de jefes,
 * permite completar datos del empleado y asignar firmantes con logica AND/OR por area.
 *
 * NOTA: Página pública por admin_token (magic link). NO incluir auth_check
 * ni require_role — la autenticación la hace el admin_token UUID validado
 * contra la API en /api/TicketBacros/solicitud-baja/admin/{admin_token}.
 */

require_once __DIR__ . '/config.php';

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

$adminToken = trim($_GET['token'] ?? '');
$apiUrlJs   = htmlspecialchars(getApiUrl());

$motivos = [
    'Renuncia voluntaria',
    'Terminacion',
    'Fin de contrato',
    'Jubilacion',
    'Abandono',
    'Fallecimiento',
    'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Configurar Solicitud de Baja</title>

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

#homeButton {
    position: fixed; top: 20px; left: 20px;
    width: 56px; height: 56px;
    background-color: #0033cc; color: #fff;
    border: none; border-radius: 50%; font-size: 24px;
    cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,.2);
    transition: all .3s; z-index: 1000;
}
#homeButton:hover { background-color: #0022aa; transform: translateY(-2px); }

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
    animation: pulse 4s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{text-shadow:0 0 8px #aaa;color:#222} 50%{text-shadow:0 0 20px #888;color:#444} }

.container {
    background: #fff; padding: 28px 36px;
    border-radius: 15px; box-shadow: 0 12px 25px rgba(0,0,0,.1);
    max-width: 900px; width: 100%;
}

.section-title {
    font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #0033cc; margin: 20px 0 10px;
    padding-bottom: 6px; border-bottom: 2px solid #e0e7ff;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .container { padding: 18px; } h2 { font-size: 1.4rem; } }

.form-group { display: flex; flex-direction: column; margin-bottom: 10px; }

label { font-weight: 600; font-size: .95rem; margin-bottom: 5px; color: #444; }
label .req { color: #dc3545; margin-left: 2px; }

input, select {
    width: 100%; padding: 10px 12px; border-radius: 8px;
    border: 1px solid #bbb; font-size: .97rem;
    background: #f9f9f9; color: #111;
    font-family: 'Inter', sans-serif;
    transition: border-color .2s, background .2s;
}
input:focus, select:focus { background: #eaeaea; border-color: #0033cc; outline: none; }

/* Header info */
.header-info {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px; margin-bottom: 18px;
}
.folio-badge {
    background: #003366; color: #fff;
    display: inline-block; padding: 6px 18px;
    border-radius: 20px; font-size: 1rem; font-weight: 700;
}
.estatus-badge {
    display: inline-block; padding: 4px 14px;
    border-radius: 20px; color: #fff; font-size: .85rem; font-weight: 700;
}

/* Area cards */
.area-card {
    background: #f8f9ff; border: 1px solid #d0d7ff;
    border-radius: 10px; padding: 16px 18px; margin-bottom: 14px;
}
.area-card h4 {
    margin: 0 0 10px; font-size: 1rem; color: #003;
    display: flex; align-items: center; gap: 8px;
}
.area-card h4 i { color: #0033cc; }

.logic-toggle {
    display: flex; gap: 14px; margin-bottom: 10px; font-size: .9rem;
}
.logic-toggle label { font-weight: 500; color: #555; cursor: pointer; display: flex; align-items: center; gap: 4px; }
.logic-toggle input[type="radio"] { accent-color: #0033cc; }

.jefe-check {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 10px; border-radius: 6px; margin-bottom: 4px;
    transition: background .2s;
}
.jefe-check:hover { background: #e8ecff; }
.jefe-check input[type="checkbox"] { accent-color: #0033cc; width: 18px; height: 18px; }
.jefe-check .jefe-name { font-weight: 600; font-size: .93rem; }
.jefe-check .jefe-id { font-size: .8rem; color: #888; margin-left: 4px; }

.conceptos-preview {
    margin-top: 10px; padding: 8px 12px;
    background: #e8f4fd; border-radius: 6px; border-left: 3px solid #0288d1;
}
.conceptos-preview summary {
    cursor: pointer; font-size: .82rem; font-weight: 600; color: #0288d1;
    user-select: none;
}
.conceptos-preview summary:hover { color: #01579b; }
.conceptos-preview ul {
    margin: 6px 0 0; padding-left: 18px; font-size: .82rem; color: #444;
}
.conceptos-preview li { margin-bottom: 2px; }

/* Botones */
.btn-primary {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 13px 24px;
    font-size: 1.05rem; font-weight: 700; cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background .2s, transform .15s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-primary:hover { background: #0022aa; transform: translateY(-2px); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none !important; }

.btn-secondary {
    background: #6c757d; color: #fff; border: none;
    border-radius: 8px; padding: 9px 16px;
    font-size: .9rem; font-weight: 600; cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-secondary:hover { background: #545b62; }

.alert-danger {
    background: #f8d7da; border-left: 4px solid #dc3545;
    border-radius: 6px; padding: 14px 18px; color: #721c24;
    text-align: center; font-weight: 600;
}

.info-box {
    background: #e8f4fd; border-left: 4px solid #0066cc;
    border-radius: 6px; padding: 12px 16px;
    font-size: .88rem; color: #1a4a7a; margin-bottom: 16px;
}

#loading {
    text-align: center; padding: 40px; color: #888;
}
#loading i { font-size: 2rem; display: block; margin-bottom: 10px; }

#contenido { display: none; }

.add-area-row {
    display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 14px;
}
.add-area-row input { max-width: 250px; }
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-cogs"></i> Configurar Solicitud de Baja</h2>

<div class="container">

<?php if (!$adminToken): ?>
    <div style="max-width:520px;margin:0 auto">
        <div style="background:#f0f4ff;border-left:4px solid #0033cc;border-radius:8px;padding:18px 22px;margin-bottom:20px">
            <h3 style="margin:0 0 8px;color:#003;font-size:1.1rem"><i class="fas fa-key"></i> Acceso por Token</h3>
            <p style="color:#555;font-size:.9rem;margin:0 0 14px">
                Pegue el token de administracion que recibio por correo para acceder a la configuracion de la solicitud de baja.
            </p>
            <div style="display:flex;gap:10px">
                <input type="text" id="tokenInput" placeholder="Ej. c5d1049aee4b47ff894e9d9de9d3e1ae"
                       style="flex:1;padding:11px 14px;border-radius:8px;border:2px solid #bbb;font-size:.95rem;font-family:'Inter',monospace;background:#fff;transition:border-color .2s"
                       onfocus="this.style.borderColor='#0033cc'" onblur="this.style.borderColor='#bbb'" />
                <button onclick="irConToken()" style="background:#0033cc;color:#fff;border:none;border-radius:8px;padding:11px 22px;font-size:.95rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .2s;font-family:'Inter',sans-serif"
                        onmouseover="this.style.background='#0022aa'" onmouseout="this.style.background='#0033cc'">
                    <i class="fas fa-sign-in-alt"></i> Ingresar
                </button>
            </div>
        </div>
        <div style="text-align:center;color:#999;font-size:.85rem">
            <i class="fas fa-info-circle"></i> El token se encuentra en el correo con asunto "Solicitud de Baja"
        </div>
    </div>
    <script>
    function irConToken() {
        const t = document.getElementById('tokenInput').value.trim();
        if (!t) { document.getElementById('tokenInput').style.borderColor = '#dc3545'; return; }
        window.location.href = 'AdminSolicitudBaja.php?token=' + encodeURIComponent(t);
    }
    document.getElementById('tokenInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') irConToken();
    });
    document.getElementById('tokenInput').focus();
    </script>
<?php else: ?>

    <div id="loading">
        <i class="fas fa-spinner fa-spin"></i>
        Cargando datos de la solicitud...
    </div>

    <div id="contenido">
        <!-- Header con folio y estatus -->
        <div class="header-info">
            <div>
                <span class="folio-badge" id="folioDisplay">---</span>
            </div>
            <div>
                <span class="estatus-badge" id="estatusDisplay" style="background:#6c757d">---</span>
            </div>
        </div>

        <!-- Datos del empleado que se da de baja -->
        <div id="empCard" style="display:none;background:#f0f4ff;border-left:4px solid #0033cc;border-radius:8px;padding:16px 20px;margin-bottom:18px">
            <div style="font-weight:700;color:#003;font-size:1.05rem;margin-bottom:10px">
                <i class="fas fa-user"></i> <span id="empNombreCompleto">---</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 20px;font-size:.93rem">
                <span style="color:#555">No. Empleado:</span>
                <span style="font-weight:600" id="empIdEmpleado">---</span>
                <span style="color:#555">Fecha de Salida:</span>
                <span style="font-weight:600" id="empFechaSalida">---</span>
                <span style="color:#555">Jefe Directo:</span>
                <span style="font-weight:600" id="empJefeDirecto">---</span>
                <span style="color:#555">Car&aacute;cter:</span>
                <span style="font-weight:600" id="empCaracter">---</span>
                <span style="color:#555">Tipo de Solicitud:</span>
                <span style="font-weight:600" id="empTipoSolicitud">---</span>
                <span style="color:#555">Fecha Recepci&oacute;n Sistemas:</span>
                <span style="font-weight:600" id="empFechaRecepcion">---</span>
            </div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Complete los datos faltantes del empleado, seleccione los jefes firmantes por area
            y defina la logica de aprobacion (AND = todos firman, OR = al menos uno firma).
        </div>

        <!-- Datos del empleado -->
        <div class="section-title"><i class="fas fa-user-edit"></i> Completar Datos del Empleado</div>

        <div class="form-row">
            <div class="form-group">
                <label>Puesto</label>
                <input type="text" id="emp_puesto" placeholder="Ej. Analista TI" />
            </div>
            <div class="form-group">
                <label>Departamento</label>
                <input type="text" id="emp_departamento" placeholder="Ej. Tecnologias de Informacion" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Correo del Empleado</label>
                <input type="email" id="emp_correo" placeholder="juan@bacrocorp.com" />
            </div>
            <div class="form-group">
                <label>Fecha de Ingreso</label>
                <input type="date" id="emp_fecha_ingreso" />
            </div>
        </div>

        <div class="form-group">
            <label>Motivo de Baja</label>
            <select id="emp_motivo_baja">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($motivos as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Firmantes por area -->
        <div class="section-title"><i class="fas fa-users-cog"></i> Asignar Firmantes por Area</div>

        <div id="areasContainer"></div>

        <!-- Agregar area personalizada -->
        <div class="add-area-row">
            <div class="form-group" style="margin-bottom:0">
                <label>Agregar otra area</label>
                <input type="text" id="nuevaArea" placeholder="Ej. ALMACEN" style="text-transform:uppercase" />
            </div>
            <button type="button" class="btn-secondary" onclick="agregarAreaManual()">
                <i class="fas fa-plus"></i> Agregar Area
            </button>
        </div>

        <!-- Boton enviar -->
        <div style="text-align:center;margin-top:24px">
            <button class="btn-primary" id="btnConfigurar" onclick="enviarConfiguracion()">
                <i class="fas fa-paper-plane"></i> Configurar y Notificar Jefes
            </button>
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
const ADMIN_TOKEN = '<?= htmlspecialchars($adminToken, ENT_QUOTES) ?>';

let solicitud = null;
let jefesDisponibles = [];
let jefesPorArea = {};
let areasConfig = {}; // { AREA: { logica: 'AND'|'OR', jefes_ids: Set } }

// Conceptos fijos por departamento — BCR-TH-SGI-FO-27
const CONCEPTOS_POR_AREA = {
    'SISTEMAS':                 ['LAPTOP','PC DE ESCRITORIO','MOUSE','CARGADOR','CELULAR'],
    'ALMACEN':                  ['HERRAMIENTA'],
    'ALMACÉN':                  ['HERRAMIENTA'],
    'JURIDICO':                 ['REPRESENTANTE LEGAL'],
    'JURÍDICO':                 ['REPRESENTANTE LEGAL'],
    'LICITACIONES':             ['DOCUMENTACION LEGAL'],
    'FINANZAS':                 ['COMPROBACION DE GASTOS','TARJETA SI VALE','ADEUDO DE FACTURACION'],
    'COMPRAS':                  ['COMPROBACION DE GASTOS','TARJETA SI VALE','ADEUDO DE FACTURACION'],
    'SERVICIOS GENERALES':      ['ADEUDOS VEHICULOS','MULTAS','TAG','TARJETA DE GASOLINA'],
    'MANTENIMIENTO':            ['ADEUDOS VEHICULOS','MULTAS','TAG','TARJETA DE GASOLINA'],
    'DEPARTAMENTO TITULAR':     ['INFORMACION GENERAL','CONTRASENAS'],
    'DEPTO. TITULAR':           ['INFORMACION GENERAL','CONTRASENAS'],
    'DIRECCION':                ['INFORMACION GENERAL','CONTRASENAS'],
    'ARCHIVO':                  ['PRESTAMO DE DOCUMENTOS'],
    'CALIDAD':                  ['EQUIPOS DE MEDICION'],
    'SEGURIDAD E HIGIENE':      ['EQUIPO DE PROTECCION PERSONAL','UNIFORME (< 3 MESES)'],
    'SEGURIDAD':                ['EQUIPO DE PROTECCION PERSONAL','UNIFORME (< 3 MESES)'],
    'COMEDOR':                  ['CONSUMOS','EXTRA'],
    'TALENTO HUMANO':           ['CREDENCIALES','CREDITO FONACOT','CREDITO INFONAVIT'],
    'AUDITORIA':                ['VISTO BUENO PROCESO'],
    'AUDITORÍA':                ['VISTO BUENO PROCESO'],
    'OPERACIONES':              [],
    'OPERACIONES ADMINISTRATIVA': [],
};

function getConceptosArea(area) {
    const a = (area || '').toUpperCase().trim();
    if (CONCEPTOS_POR_AREA[a]) return CONCEPTOS_POR_AREA[a];
    // Fuzzy match
    for (const [key, val] of Object.entries(CONCEPTOS_POR_AREA)) {
        if (a.includes(key) || key.includes(a)) return val;
    }
    return [];
}

async function cargarDatos() {
    if (!ADMIN_TOKEN) return;

    try {
        // Cargar solicitud
        const resSol = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/admin/${ADMIN_TOKEN}`);
        const dataSol = await resSol.json();

        if (!resSol.ok || !dataSol.data) {
            throw new Error(dataSol.message || 'No se pudo cargar la solicitud.');
        }
        solicitud = dataSol.data;

        // Mostrar folio y estatus
        document.getElementById('folioDisplay').textContent = solicitud.folio || '---';
        const est = solicitud.estatus_general || solicitud.estatus || 'Borrador';
        const estatusEl = document.getElementById('estatusDisplay');
        estatusEl.textContent = est;
        estatusEl.style.background = est === 'Completado' ? '#28a745' :
                                     est === 'Rechazado' ? '#dc3545' :
                                     est === 'En Proceso' ? '#0066cc' : '#6c757d';

        // Mostrar tarjeta con datos del empleado que se da de baja
        const nombreCompleto = [solicitud.emp_nombre, solicitud.emp_apellido_paterno, solicitud.emp_apellido_materno].filter(Boolean).join(' ');
        document.getElementById('empNombreCompleto').textContent = nombreCompleto || '---';
        document.getElementById('empIdEmpleado').textContent = solicitud.emp_id_empleado || '---';
        document.getElementById('empFechaSalida').textContent = solicitud.fecha_salida || '---';
        document.getElementById('empJefeDirecto').textContent = solicitud.jefe_directo || '---';
        document.getElementById('empCaracter').textContent = solicitud.caracter || '---';
        document.getElementById('empTipoSolicitud').textContent = solicitud.tipo_solicitud || '---';
        document.getElementById('empFechaRecepcion').textContent = solicitud.fecha_recepcion_sistemas || '---';
        document.getElementById('empCard').style.display = 'block';

        // Pre-llenar datos del empleado si existen
        if (solicitud.emp_puesto) document.getElementById('emp_puesto').value = solicitud.emp_puesto;
        if (solicitud.emp_departamento) document.getElementById('emp_departamento').value = solicitud.emp_departamento;
        if (solicitud.emp_correo) document.getElementById('emp_correo').value = solicitud.emp_correo;
        if (solicitud.emp_fecha_ingreso) document.getElementById('emp_fecha_ingreso').value = solicitud.emp_fecha_ingreso.substring(0,10);
        if (solicitud.emp_motivo_baja) document.getElementById('emp_motivo_baja').value = solicitud.emp_motivo_baja;

        // Cargar catalogo de jefes
        try {
            const resJefes = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/jefes-disponibles`);
            const dataJefes = await resJefes.json();
            jefesDisponibles = Array.isArray(dataJefes.data) ? dataJefes.data : (Array.isArray(dataJefes) ? dataJefes : []);
        } catch (e) {
            console.warn('No se pudo cargar catalogo de jefes:', e);
            jefesDisponibles = [];
        }

        // Agrupar por area
        jefesPorArea = {};
        jefesDisponibles.forEach(j => {
            const area = (j.area || 'SIN AREA').toUpperCase();
            if (!jefesPorArea[area]) jefesPorArea[area] = [];
            jefesPorArea[area].push(j);
        });

        // Renderizar areas
        renderizarAreas();

        document.getElementById('loading').style.display = 'none';
        document.getElementById('contenido').style.display = 'block';

    } catch (err) {
        document.getElementById('loading').innerHTML = `
            <div class="alert-danger">
                <i class="fas fa-exclamation-triangle"></i><br><br>
                ${err.message}
            </div>`;
    }
}

function renderizarAreas() {
    const container = document.getElementById('areasContainer');
    container.innerHTML = '';

    Object.keys(jefesPorArea).sort().forEach(area => {
        if (!areasConfig[area]) {
            areasConfig[area] = { logica: 'AND', jefes_ids: new Set() };
        }
        agregarAreaCard(area, jefesPorArea[area]);
    });
}

function agregarAreaCard(area, jefes) {
    const container = document.getElementById('areasContainer');
    if (!areasConfig[area]) {
        areasConfig[area] = { logica: 'AND', jefes_ids: new Set() };
    }
    const config = areasConfig[area];

    const card = document.createElement('div');
    card.className = 'area-card';
    card.id = `area-${area}`;

    let jefesHtml = '';
    if (jefes && jefes.length > 0) {
        jefes.forEach(j => {
            const checked = config.jefes_ids.has(j.id) ? 'checked' : '';
            jefesHtml += `
                <label class="jefe-check">
                    <input type="checkbox" value="${j.id}" ${checked}
                           onchange="toggleJefe('${area}', ${j.id}, this.checked)" />
                    <span class="jefe-name">${j.nombre_completo || j.nombre || 'Sin nombre'}</span>
                    <span class="jefe-id">(id:${j.id})</span>
                </label>`;
        });
    } else {
        jefesHtml = '<p style="color:#999;font-size:.88rem;margin:4px 0">No hay jefes registrados en esta area.</p>';
    }

    // Conceptos que le corresponden a este departamento
    const conceptos = getConceptosArea(area);
    let conceptosHtml = '';
    if (conceptos.length > 0) {
        const items = conceptos.map(c => `<li>${c}</li>`).join('');
        conceptosHtml = `
            <div class="conceptos-preview">
                <details>
                    <summary><i class="fas fa-clipboard-list"></i> Preguntas que se mostraran al firmante (${conceptos.length})</summary>
                    <ul>${items}</ul>
                </details>
            </div>`;
    } else {
        conceptosHtml = `
            <div class="conceptos-preview" style="background:#fff8e1;border-left-color:#ffa000">
                <span style="font-size:.82rem;color:#e65100"><i class="fas fa-info-circle"></i> Sin conceptos — solo se pedira firma</span>
            </div>`;
    }

    card.innerHTML = `
        <h4><i class="fas fa-building"></i> ${area}</h4>
        <div class="logic-toggle">
            <label>
                <input type="radio" name="logica_${area}" value="AND"
                       ${config.logica === 'AND' ? 'checked' : ''}
                       onchange="areasConfig['${area}'].logica='AND'" />
                <strong>AND</strong> — todos firman
            </label>
            <label>
                <input type="radio" name="logica_${area}" value="OR"
                       ${config.logica === 'OR' ? 'checked' : ''}
                       onchange="areasConfig['${area}'].logica='OR'" />
                <strong>OR</strong> — al menos uno
            </label>
        </div>
        ${jefesHtml}
        ${conceptosHtml}
    `;
    container.appendChild(card);
}

function toggleJefe(area, jefeId, checked) {
    if (!areasConfig[area]) areasConfig[area] = { logica: 'AND', jefes_ids: new Set() };
    if (checked) {
        areasConfig[area].jefes_ids.add(jefeId);
    } else {
        areasConfig[area].jefes_ids.delete(jefeId);
    }
}

function agregarAreaManual() {
    const input = document.getElementById('nuevaArea');
    const area = input.value.trim().toUpperCase();
    if (!area) return;

    if (document.getElementById(`area-${area}`)) {
        Swal.fire({ title: 'Area ya existe', text: `El area "${area}" ya esta en la lista.`, icon: 'info', confirmButtonColor: '#003366' });
        return;
    }

    areasConfig[area] = { logica: 'OR', jefes_ids: new Set() };
    agregarAreaCard(area, []);
    input.value = '';
}

async function enviarConfiguracion() {
    // Construir firmas_config solo con areas que tienen jefes seleccionados
    const firmasConfig = [];
    for (const [area, cfg] of Object.entries(areasConfig)) {
        if (cfg.jefes_ids.size > 0) {
            firmasConfig.push({
                area: area,
                logica: cfg.logica,
                jefes_ids: Array.from(cfg.jefes_ids),
            });
        }
    }

    if (firmasConfig.length === 0) {
        await Swal.fire({
            title: 'Sin firmantes',
            text: 'Seleccione al menos un jefe firmante en al menos un area.',
            icon: 'warning',
            confirmButtonColor: '#003366',
        });
        return;
    }

    const payload = {
        emp_puesto:       document.getElementById('emp_puesto').value.trim() || undefined,
        emp_departamento: document.getElementById('emp_departamento').value.trim() || undefined,
        emp_correo:       document.getElementById('emp_correo').value.trim() || undefined,
        emp_fecha_ingreso: document.getElementById('emp_fecha_ingreso').value || undefined,
        emp_motivo_baja:  document.getElementById('emp_motivo_baja').value || undefined,
        firmas_config:    firmasConfig,
    };

    // Limpiar undefined
    Object.keys(payload).forEach(k => { if (payload[k] === undefined) delete payload[k]; });

    const btn = document.getElementById('btnConfigurar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Configurando...';

    Swal.fire({
        title: 'Procesando...',
        text: 'Configurando firmantes y enviando notificaciones',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const res = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/admin/${ADMIN_TOKEN}/configurar`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (res.ok && (data.success !== false)) {
            await Swal.fire({
                title: 'Configuracion Exitosa',
                html: `
                    <div style="text-align:center">
                        <div style="font-size:52px;margin-bottom:12px">&#9989;</div>
                        <p>${data.message || 'Se han asignado los firmantes y se enviaron las notificaciones por correo.'}</p>
                        <p style="font-size:.9rem;color:#555">${firmasConfig.length} area(s) configurada(s)</p>
                    </div>`,
                icon: 'success',
                confirmButtonColor: '#003366',
                confirmButtonText: 'Ver Listado',
            });
            window.location.href = 'ListadoSolicitudesBaja.php';
        } else {
            throw new Error(data.message || 'Error al configurar la solicitud.');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Configurar y Notificar Jefes';
        Swal.fire({
            title: 'Error',
            text: err.message,
            icon: 'error',
            confirmButtonColor: '#003366',
        });
    }
}

// Inicio
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});

cargarDatos();
</script>
</body>
</html>
