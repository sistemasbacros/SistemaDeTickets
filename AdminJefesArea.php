<?php
/**
 * @file AdminJefesArea.php
 * @brief CRUD de Jefes de Area / Supervisores Administradores.
 *
 * Permite listar, crear, editar y desactivar jefes de area.
 * Usa los endpoints /api/TicketBacros/jefes-area con JWT.
 * Requiere sesion activa (login) para acceder.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/roles.php';
require_role('admin');

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

$apiUrlJs    = htmlspecialchars(getApiUrl());
$userName     = htmlspecialchars($_SESSION['user_name']     ?? '');
$userArea     = htmlspecialchars($_SESSION['user_area']     ?? '');
$userUsername = htmlspecialchars($_SESSION['user_username'] ?? '');
$apiJwt       = $_SESSION['api_jwt'] ?? '';

$areasDisponibles = [
    'SISTEMAS',
    'FINANZAS',
    'TALENTO HUMANO',
    'COMEDOR',
    'COMPRAS',
    'OPERACIONES',
    'ALMACEN',
    'SEGURIDAD',
    'MANTENIMIENTO',
    'CALIDAD',
    'PRODUCCION',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Administrar Jefes de Area</title>

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
    max-width: 1100px; width: 100%;
}

/* Token section */
.token-section {
    background: #fff3cd; border-left: 4px solid #ffc107;
    border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;
}
.token-section label { font-weight: 600; font-size: .9rem; color: #856404; display: block; margin-bottom: 6px; }
.token-row { display: flex; gap: 10px; }
.token-row input {
    flex: 1; padding: 9px 12px; border-radius: 8px;
    border: 1px solid #bbb; font-size: .9rem;
    font-family: 'Inter', monospace; background: #f9f9f9;
}
.token-row input:focus { border-color: #0033cc; outline: none; }
.token-row button {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 9px 16px;
    font-size: .9rem; font-weight: 600; cursor: pointer;
    white-space: nowrap; transition: background .2s;
}
.token-row button:hover { background: #0022aa; }

.section-title {
    font-size: .85rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #0033cc; margin: 20px 0 10px;
    padding-bottom: 6px; border-bottom: 2px solid #e0e7ff;
}

/* Tabla */
.table-wrap { overflow-x: auto; }
table { border-collapse: collapse; width: 100%; font-size: .88rem; }
thead tr { background: #003366; color: #fff; }
th { padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
td { padding: 9px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
tbody tr:hover { background: #f5f8ff; }
tr:last-child td { border-bottom: none; }

.badge-area {
    display: inline-block; padding: 2px 10px;
    border-radius: 12px; font-size: .78rem; font-weight: 700;
    background: #e0e7ff; color: #0033cc;
}

/* Botones accion */
.btn-edit {
    background: #ffc107; color: #333; border: none;
    border-radius: 6px; padding: 5px 10px; font-size: .8rem;
    cursor: pointer; font-weight: 600; transition: background .2s;
    margin-right: 4px;
}
.btn-edit:hover { background: #e0a800; }

.btn-delete {
    background: #dc3545; color: #fff; border: none;
    border-radius: 6px; padding: 5px 10px; font-size: .8rem;
    cursor: pointer; font-weight: 600; transition: background .2s;
}
.btn-delete:hover { background: #c82333; }

.btn-primary {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 10px 20px;
    font-size: .95rem; font-weight: 700; cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background .2s, transform .15s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-primary:hover { background: #0022aa; transform: translateY(-2px); }

.btn-secondary {
    background: #28a745; color: #fff; border: none;
    border-radius: 8px; padding: 10px 20px;
    font-size: .95rem; font-weight: 700; cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background .2s, transform .15s;
    display: inline-flex; align-items: center; gap: 8px;
}
.btn-secondary:hover { background: #218838; transform: translateY(-2px); }

/* Modal overlay */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 2000;
    align-items: center; justify-content: center;
}
.modal-overlay.active { display: flex; }

.modal-box {
    background: #fff; border-radius: 15px;
    padding: 28px 32px; max-width: 560px; width: 90%;
    box-shadow: 0 20px 40px rgba(0,0,0,.3);
    max-height: 90vh; overflow-y: auto;
}
.modal-box h3 { margin: 0 0 18px; color: #003; font-size: 1.2rem; }

.form-group { display: flex; flex-direction: column; margin-bottom: 14px; }
.form-group label { font-weight: 600; font-size: .9rem; margin-bottom: 5px; color: #444; }
.form-group label .req { color: #dc3545; }
.form-group input, .form-group select {
    padding: 10px 12px; border-radius: 8px;
    border: 1px solid #bbb; font-size: .95rem;
    font-family: 'Inter', sans-serif; background: #f9f9f9;
}
.form-group input:focus, .form-group select:focus {
    border-color: #0033cc; outline: none; background: #eaeaea;
}

.modal-buttons { display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end; }
.btn-cancel {
    background: #6c757d; color: #fff; border: none;
    border-radius: 8px; padding: 10px 18px;
    font-size: .93rem; font-weight: 600; cursor: pointer;
    transition: background .2s;
}
.btn-cancel:hover { background: #545b62; }
.btn-save {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 10px 18px;
    font-size: .93rem; font-weight: 600; cursor: pointer;
    transition: background .2s;
}
.btn-save:hover { background: #0022aa; }

.empty-state { text-align: center; padding: 40px 20px; color: #aaa; }
.empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }

.alert-danger {
    background: #f8d7da; border-left: 4px solid #dc3545;
    border-radius: 6px; padding: 12px 16px; color: #721c24; margin-bottom: 16px;
}

.info-box {
    background: #e8f4fd; border-left: 4px solid #0066cc;
    border-radius: 6px; padding: 12px 16px;
    font-size: .88rem; color: #1a4a7a; margin-bottom: 16px;
}

/* Stats */
.stats-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.stat-card {
    flex: 1; min-width: 100px;
    border-radius: 10px; padding: 10px 14px; text-align: center;
    background: #e0e7ff; color: #0033cc;
}
.stat-card .stat-num { font-size: 1.6rem; font-weight: 700; }
.stat-card .stat-label { font-size: .75rem; font-weight: 600; text-transform: uppercase; }

/* Filter */
.filter-row { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: flex-end; }
.filter-row select, .filter-row input {
    padding: 8px 12px; border-radius: 8px; border: 1px solid #bbb;
    font-size: .9rem; font-family: 'Inter', sans-serif; background: #f9f9f9;
}
.filter-row select:focus, .filter-row input:focus { border-color: #0033cc; outline: none; }

@media (max-width: 640px) {
    .container { padding: 18px; }
    h2 { font-size: 1.4rem; }
    .token-row { flex-direction: column; }
}
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-users-cog"></i> Administrar Jefes de Area</h2>

<div class="container">

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        Bienvenido, <strong><?= $userName ?></strong>.
        Aqui puede agregar, editar o desactivar jefes/supervisores por area.
        Un mismo usuario puede estar en multiples areas.
    </div>


    <!-- Toolbar -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div>
            <button class="btn-secondary" onclick="abrirModalCrear()">
                <i class="fas fa-user-plus"></i> Nuevo Jefe de Area
            </button>
        </div>
        <div>
            <button class="btn-primary" onclick="abrirModalMultiArea()">
                <i class="fas fa-layer-group"></i> Agregar a Multiples Areas
            </button>
        </div>
    </div>

    <!-- Filtro -->
    <div class="filter-row">
        <select id="filtroArea" onchange="filtrarTabla()">
            <option value="">Todas las areas</option>
            <?php foreach ($areasDisponibles as $a): ?>
                <option value="<?= $a ?>"><?= $a ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="filtroBuscar" placeholder="Buscar por nombre..." oninput="filtrarTabla()" />
    </div>

    <!-- Stats -->
    <div class="stats-row" id="statsRow" style="display:none"></div>

    <!-- Tabla -->
    <div id="tablaContainer">
        <div class="empty-state">
            <i class="fas fa-key"></i>
            Cargando jefes de area...
        </div>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="modal-overlay" id="modalJefe">
    <div class="modal-box">
        <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Nuevo Jefe de Area</h3>

        <input type="hidden" id="editId" value="" />

        <div class="form-group">
            <label>Nombre de Usuario <span class="req">*</span></label>
            <input type="text" id="fNombreUsuario" placeholder="nombre.usuario" />
        </div>
        <div class="form-group">
            <label>Nombre Completo <span class="req">*</span></label>
            <input type="text" id="fNombreCompleto" placeholder="NOMBRE COMPLETO DEL JEFE" style="text-transform:uppercase" />
        </div>
        <div class="form-group">
            <label>Area <span class="req">*</span></label>
            <select id="fArea">
                <?php foreach ($areasDisponibles as $a): ?>
                    <option value="<?= $a ?>"><?= $a ?></option>
                <?php endforeach; ?>
                <option value="_OTRA">-- Otra (escribir) --</option>
            </select>
        </div>
        <div class="form-group" id="fAreaOtraGroup" style="display:none">
            <label>Area personalizada</label>
            <input type="text" id="fAreaOtra" placeholder="Ej. LOGISTICA" style="text-transform:uppercase" />
        </div>
        <div class="form-group">
            <label>Correo <span class="req">*</span></label>
            <input type="email" id="fCorreo" placeholder="correo@bacrocorp.com" />
        </div>
        <div class="form-group">
            <label>ID Empleado <span class="req">*</span></label>
            <input type="text" id="fIdEmpleado" placeholder="nombre.usuario o EMP-XXX" />
        </div>
        <div class="form-group">
            <label>Puesto</label>
            <input type="text" id="fPuesto" placeholder="Ej. Supervisor Administrador" value="Jefe de Area" />
        </div>

        <div class="modal-buttons">
            <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-save" id="btnGuardar" onclick="guardarJefe()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal Multi-Area -->
<div class="modal-overlay" id="modalMultiArea">
    <div class="modal-box">
        <h3><i class="fas fa-layer-group"></i> Agregar Usuario a Multiples Areas</h3>
        <p style="font-size:.88rem;color:#555;margin-bottom:14px">
            Esto creara un registro por cada area seleccionada para el mismo usuario.
        </p>

        <div class="form-group">
            <label>Nombre de Usuario <span class="req">*</span></label>
            <input type="text" id="mNombreUsuario" placeholder="nombre.usuario" />
        </div>
        <div class="form-group">
            <label>Nombre Completo <span class="req">*</span></label>
            <input type="text" id="mNombreCompleto" placeholder="NOMBRE COMPLETO" style="text-transform:uppercase" />
        </div>
        <div class="form-group">
            <label>Correo <span class="req">*</span></label>
            <input type="email" id="mCorreo" placeholder="correo@bacrocorp.com" />
        </div>
        <div class="form-group">
            <label>ID Empleado <span class="req">*</span></label>
            <input type="text" id="mIdEmpleado" placeholder="nombre.usuario" />
        </div>
        <div class="form-group">
            <label>Puesto</label>
            <input type="text" id="mPuesto" value="Supervisor Administrador" />
        </div>
        <div class="form-group">
            <label>Seleccione las areas <span class="req">*</span></label>
            <div id="multiAreaChecks" style="max-height:200px;overflow-y:auto;padding:8px;background:#f9f9f9;border-radius:8px;border:1px solid #ddd">
                <?php foreach ($areasDisponibles as $a): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;font-size:.9rem">
                        <input type="checkbox" value="<?= $a ?>" style="accent-color:#0033cc;width:18px;height:18px" />
                        <?= $a ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal-buttons">
            <button class="btn-cancel" onclick="cerrarModalMulti()">Cancelar</button>
            <button class="btn-save" id="btnGuardarMulti" onclick="guardarMultiArea()">
                <i class="fas fa-save"></i> Crear en Areas Seleccionadas
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// HTTPS (producción) → same-origin (nginx hace proxy interno a localhost:3000)
// HTTP (dev local)   → puerto 3000 explícito (frontend y backend separados)
const API_URL = window.location.protocol === 'https:'
    ? window.location.origin
    : window.location.protocol + '//' + window.location.hostname + ':3000';
let jwtToken = <?= json_encode($apiJwt ?: null, JSON_UNESCAPED_SLASHES) ?>;
let jefesData = [];

function authHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + jwtToken,
    };
}

// Parsear respuesta de forma segura (el API a veces responde texto plano)
async function safeJson(res) {
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        throw new Error(text || 'Respuesta no valida del servidor (HTTP ' + res.status + ')');
    }
}

// ── Login al API desde el navegador ────────────────────────────
async function obtenerToken() {
    if (jwtToken) return true;

    // Pedir credenciales una sola vez
    const { value: creds } = await Swal.fire({
        title: 'Autenticacion API',
        html: `
            <input id="swal-user" class="swal2-input" placeholder="Usuario" value="<?= $userUsername ?>" style="font-size:.95rem">
            <input id="swal-pass" class="swal2-input" type="password" placeholder="Contrasena" style="font-size:.95rem" autofocus>
        `,
        focusConfirm: false,
        confirmButtonText: 'Conectar',
        confirmButtonColor: '#003366',
        showCancelButton: false,
        allowOutsideClick: false,
        preConfirm: () => {
            const u = document.getElementById('swal-user').value.trim();
            const p = document.getElementById('swal-pass').value.trim();
            if (!u || !p) { Swal.showValidationMessage('Complete ambos campos'); return false; }
            return { usuario: u, contrasena: p };
        }
    });

    if (!creds) return false;

    try {
        const res = await fetch(`${API_URL}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(creds),
        });
        const data = await safeJson(res);
        if (res.ok && data.token) {
            jwtToken = data.token;
            console.log('JWT obtenido, longitud:', jwtToken.length, 'inicio:', jwtToken.substring(0, 20));
            return true;
        } else {
            throw new Error(data.message || data.error || 'Credenciales invalidas');
        }
    } catch (err) {
        await Swal.fire({ title: 'Error de login', text: err.message, icon: 'error', confirmButtonColor: '#003366' });
        return false;
    }
}

// ── Cargar jefes ───────────────────────────────────────────────
async function cargarJefes() {
    if (!jwtToken && !(await obtenerToken())) return;

    const container = document.getElementById('tablaContainer');
    container.innerHTML = '<div style="text-align:center;padding:30px;color:#888"><i class="fas fa-spinner fa-spin" style="font-size:2rem"></i><br>Cargando...</div>';

    try {
        console.log('GET jefes-area con token:', jwtToken ? jwtToken.substring(0, 20) + '...' : 'SIN TOKEN');
        const res = await fetch(`${API_URL}/api/TicketBacros/jefes-area`, {
            headers: { 'Authorization': 'Bearer ' + jwtToken },
        });

        if (res.status === 401 || res.status === 403) {
            // Token de sesion invalido, intentar login via popup
            jwtToken = null;
            if (await obtenerToken()) {
                return cargarJefes(); // Reintentar con nuevo token
            }
            throw new Error('No se pudo autenticar con el API.');
        }

        const data = await safeJson(res);
        jefesData = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);

        renderTabla();
        renderStats();

    } catch (err) {
        container.innerHTML = `<div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> ${err.message}</div>`;
    }
}

function renderStats() {
    const areas = {};
    jefesData.forEach(j => {
        const a = j.area || 'SIN AREA';
        areas[a] = (areas[a] || 0) + 1;
    });

    const statsRow = document.getElementById('statsRow');
    statsRow.style.display = 'flex';
    statsRow.innerHTML = `
        <div class="stat-card">
            <div class="stat-num">${jefesData.length}</div>
            <div class="stat-label">Total Registros</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">${Object.keys(areas).length}</div>
            <div class="stat-label">Areas</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">${new Set(jefesData.map(j => j.nombre_usuario || j.id_empleado)).size}</div>
            <div class="stat-label">Usuarios Unicos</div>
        </div>
    `;
}

function filtrarTabla() {
    renderTabla();
}

function renderTabla() {
    const filtroArea   = document.getElementById('filtroArea').value;
    const filtroBuscar = document.getElementById('filtroBuscar').value.toLowerCase();

    let filtrados = jefesData;
    if (filtroArea) filtrados = filtrados.filter(j => (j.area || '') === filtroArea);
    if (filtroBuscar) filtrados = filtrados.filter(j =>
        (j.nombre_completo || '').toLowerCase().includes(filtroBuscar) ||
        (j.nombre_usuario || '').toLowerCase().includes(filtroBuscar) ||
        (j.correo || '').toLowerCase().includes(filtroBuscar)
    );

    const container = document.getElementById('tablaContainer');

    if (filtrados.length === 0) {
        container.innerHTML = `<div class="empty-state"><i class="fas fa-users-slash"></i>No se encontraron jefes${filtroArea || filtroBuscar ? ' con los filtros aplicados' : ''}.</div>`;
        return;
    }

    let rows = '';
    filtrados.forEach(j => {
        rows += `
            <tr>
                <td><strong>${esc(j.id)}</strong></td>
                <td>${esc(j.nombre_completo || '')}</td>
                <td style="font-size:.85rem">${esc(j.nombre_usuario || '')}</td>
                <td><span class="badge-area">${esc(j.area || '')}</span></td>
                <td style="font-size:.85rem">${esc(j.correo || '')}</td>
                <td style="font-size:.85rem">${esc(j.id_empleado || '')}</td>
                <td style="font-size:.85rem">${esc(j.puesto || '')}</td>
                <td style="white-space:nowrap">
                    <button class="btn-edit" onclick='editarJefe(${JSON.stringify(j)})'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" onclick="desactivarJefe(${j.id}, '${esc(j.nombre_completo)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
    });

    container.innerHTML = `
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Area</th>
                        <th>Correo</th>
                        <th>ID Empleado</th>
                        <th>Puesto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <p style="font-size:.8rem;color:#999;margin-top:8px">${filtrados.length} registro(s) mostrado(s)</p>`;
}

function esc(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

// ── Modal Crear/Editar ─────────────────────────────────────────
document.getElementById('fArea').addEventListener('change', function() {
    document.getElementById('fAreaOtraGroup').style.display = this.value === '_OTRA' ? 'block' : 'none';
});

async function abrirModalCrear() {
    if (!jwtToken && !(await obtenerToken())) {
        return;
    }
    document.getElementById('editId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Jefe de Area';
    document.getElementById('fNombreUsuario').value = '';
    document.getElementById('fNombreCompleto').value = '';
    document.getElementById('fArea').value = 'SISTEMAS';
    document.getElementById('fAreaOtraGroup').style.display = 'none';
    document.getElementById('fCorreo').value = '';
    document.getElementById('fIdEmpleado').value = '';
    document.getElementById('fPuesto').value = 'Jefe de Area';
    document.getElementById('modalJefe').classList.add('active');
}

function editarJefe(j) {
    document.getElementById('editId').value = j.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Editar Jefe de Area';
    document.getElementById('fNombreUsuario').value = j.nombre_usuario || '';
    document.getElementById('fNombreCompleto').value = j.nombre_completo || '';

    const selectArea = document.getElementById('fArea');
    const areaVal = j.area || '';
    const opts = Array.from(selectArea.options).map(o => o.value);
    if (opts.includes(areaVal)) {
        selectArea.value = areaVal;
        document.getElementById('fAreaOtraGroup').style.display = 'none';
    } else {
        selectArea.value = '_OTRA';
        document.getElementById('fAreaOtraGroup').style.display = 'block';
        document.getElementById('fAreaOtra').value = areaVal;
    }

    document.getElementById('fCorreo').value = j.correo || '';
    document.getElementById('fIdEmpleado').value = j.id_empleado || '';
    document.getElementById('fPuesto').value = j.puesto || '';
    document.getElementById('modalJefe').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalJefe').classList.remove('active');
}

async function guardarJefe() {
    const editId = document.getElementById('editId').value;
    let area = document.getElementById('fArea').value;
    if (area === '_OTRA') area = document.getElementById('fAreaOtra').value.trim().toUpperCase();

    const payload = {
        nombre_usuario:  document.getElementById('fNombreUsuario').value.trim(),
        nombre_completo: document.getElementById('fNombreCompleto').value.trim().toUpperCase(),
        area:            area,
        correo:          document.getElementById('fCorreo').value.trim(),
        id_empleado:     document.getElementById('fIdEmpleado').value.trim(),
        puesto:          document.getElementById('fPuesto').value.trim(),
    };

    if (!payload.nombre_usuario || !payload.nombre_completo || !payload.area || !payload.correo || !payload.id_empleado) {
        Swal.fire({ title: 'Campos requeridos', text: 'Complete todos los campos obligatorios.', icon: 'warning', confirmButtonColor: '#003366' });
        return;
    }

    const isEdit = !!editId;
    const url    = isEdit ? `${API_URL}/api/TicketBacros/jefes-area/${editId}` : `${API_URL}/api/TicketBacros/jefes-area`;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: authHeaders(),
            body: JSON.stringify(payload),
        });

        const data = await safeJson(res);

        if (res.ok && data.success !== false) {
            cerrarModal();
            await Swal.fire({
                title: isEdit ? 'Actualizado' : 'Creado',
                text: data.message || `Jefe de area ${isEdit ? 'actualizado' : 'creado'} correctamente.`,
                icon: 'success',
                confirmButtonColor: '#003366',
            });
            cargarJefes();
        } else {
            throw new Error(data.message || 'Error del servidor.');
        }
    } catch (err) {
        Swal.fire({ title: 'Error', text: err.message, icon: 'error', confirmButtonColor: '#003366' });
    }
}

// ── Desactivar ─────────────────────────────────────────────────
async function desactivarJefe(id, nombre) {
    const conf = await Swal.fire({
        title: 'Desactivar jefe?',
        html: `Se desactivara a <strong>${nombre}</strong> (ID: ${id}).<br>Esta accion no elimina el registro, solo lo desactiva.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Si, desactivar',
        cancelButtonText: 'Cancelar',
    });
    if (!conf.isConfirmed) return;

    try {
        const res = await fetch(`${API_URL}/api/TicketBacros/jefes-area/${id}`, {
            method: 'DELETE',
            headers: authHeaders(),
        });

        const data = await safeJson(res);

        if (res.ok && data.success !== false) {
            await Swal.fire({
                title: 'Desactivado',
                text: data.message || 'Jefe de area desactivado.',
                icon: 'success',
                confirmButtonColor: '#003366',
            });
            cargarJefes();
        } else {
            throw new Error(data.message || 'Error al desactivar.');
        }
    } catch (err) {
        Swal.fire({ title: 'Error', text: err.message, icon: 'error', confirmButtonColor: '#003366' });
    }
}

// ── Modal Multi-Area ───────────────────────────────────────────
async function abrirModalMultiArea() {
    if (!jwtToken && !(await obtenerToken())) {
        return;
    }
    document.getElementById('mNombreUsuario').value = '';
    document.getElementById('mNombreCompleto').value = '';
    document.getElementById('mCorreo').value = '';
    document.getElementById('mIdEmpleado').value = '';
    document.getElementById('mPuesto').value = 'Supervisor Administrador';
    document.querySelectorAll('#multiAreaChecks input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('modalMultiArea').classList.add('active');
}

function cerrarModalMulti() {
    document.getElementById('modalMultiArea').classList.remove('active');
}

async function guardarMultiArea() {
    const nombre_usuario  = document.getElementById('mNombreUsuario').value.trim();
    const nombre_completo = document.getElementById('mNombreCompleto').value.trim().toUpperCase();
    const correo          = document.getElementById('mCorreo').value.trim();
    const id_empleado     = document.getElementById('mIdEmpleado').value.trim();
    const puesto          = document.getElementById('mPuesto').value.trim();

    const areasSeleccionadas = [];
    document.querySelectorAll('#multiAreaChecks input[type="checkbox"]:checked').forEach(cb => {
        areasSeleccionadas.push(cb.value);
    });

    if (!nombre_usuario || !nombre_completo || !correo || !id_empleado) {
        Swal.fire({ title: 'Campos requeridos', text: 'Complete todos los campos obligatorios.', icon: 'warning', confirmButtonColor: '#003366' });
        return;
    }
    if (areasSeleccionadas.length === 0) {
        Swal.fire({ title: 'Sin areas', text: 'Seleccione al menos un area.', icon: 'warning', confirmButtonColor: '#003366' });
        return;
    }

    const btn = document.getElementById('btnGuardarMulti');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';

    let exitos = 0;
    let errores = [];

    for (const area of areasSeleccionadas) {
        try {
            const res = await fetch(`${API_URL}/api/TicketBacros/jefes-area`, {
                method: 'POST',
                headers: authHeaders(),
                body: JSON.stringify({ nombre_usuario, nombre_completo, area, correo, id_empleado, puesto }),
            });
            const data = await safeJson(res);
            if (res.ok && data.success !== false) {
                exitos++;
            } else {
                errores.push(`${area}: ${data.message || 'Error'}`);
            }
        } catch (err) {
            errores.push(`${area}: ${err.message}`);
        }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Crear en Areas Seleccionadas';
    cerrarModalMulti();

    if (errores.length === 0) {
        await Swal.fire({
            title: 'Creados correctamente',
            html: `Se creo <strong>${nombre_completo}</strong> en <strong>${exitos}</strong> area(s).`,
            icon: 'success',
            confirmButtonColor: '#003366',
        });
    } else {
        await Swal.fire({
            title: 'Resultado parcial',
            html: `Exitos: ${exitos}<br>Errores:<br><span style="font-size:.85rem;color:#dc3545">${errores.join('<br>')}</span>`,
            icon: exitos > 0 ? 'warning' : 'error',
            confirmButtonColor: '#003366',
        });
    }

    cargarJefes();
}

// ── Init ───────────────────────────────────────────────────────
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});

// Cargar automaticamente si hay JWT
if (jwtToken) cargarJefes();

// Cerrar modales con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalMulti();
    }
});

// Cerrar modal al clic fuera
document.getElementById('modalJefe').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
document.getElementById('modalMultiArea').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalMulti();
});
</script>
</body>
</html>
