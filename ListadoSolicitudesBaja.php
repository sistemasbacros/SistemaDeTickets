<?php
/**
 * @file ListadoSolicitudesBaja.php
 * @brief Dashboard/listado de todas las Solicitudes de Baja.
 *
 * Consulta GET /api/TicketBacros/solicitud-baja con filtros opcionales.
 * Muestra tabla con estatus, filtros y stats rapidas.
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

$estatus     = trim($_GET['estatus']     ?? '');
$idEmpleado  = trim($_GET['id_empleado'] ?? '');

$apiUrl = getApiUrl();
$params = array_filter(['estatus' => $estatus, 'id_empleado' => $idEmpleado]);
$url    = $apiUrl . '/api/TicketBacros/solicitud-baja' . ($params ? '?' . http_build_query($params) : '');

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
$resp     = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$solicitudes = [];
$errMsg = null;

if ($curlErr) {
    $errMsg = 'No se pudo conectar al servidor: ' . htmlspecialchars($curlErr);
} elseif ($httpCode !== 200) {
    $errMsg = 'Error del servidor (HTTP ' . $httpCode . '). Verifique que el API este activo.';
} else {
    $data = json_decode($resp, true);
    $solicitudes = $data['data'] ?? (is_array($data) ? $data : []);
}

function colorEstatus(string $est): array {
    return match($est) {
        'Completado'  => ['#28a745', '#d4edda'],
        'En Proceso'  => ['#0066cc', '#cce5ff'],
        'Pendiente'   => ['#ffc107', '#fff3cd'],
        'Rechazado'   => ['#dc3545', '#f8d7da'],
        default       => ['#6c757d', '#e2e3e5'],
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Solicitudes de Baja</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

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

/* Filtros */
.filter-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 22px; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label { font-size: .85rem; font-weight: 600; color: #444; }
.filter-group select,
.filter-group input {
    padding: 9px 12px; border-radius: 8px;
    border: 1px solid #bbb; font-size: .93rem;
    background: #f9f9f9; font-family: 'Inter', sans-serif;
    min-width: 160px;
}
.filter-group select:focus,
.filter-group input:focus { border-color: #0033cc; outline: none; background: #eaeaea; }

.btn-filter {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 9px 18px;
    font-size: .93rem; font-weight: 600; cursor: pointer;
    transition: background .2s; align-self: flex-end;
}
.btn-filter:hover { background: #0022aa; }

.btn-limpiar-filtro {
    background: #6c757d; color: #fff; border: none;
    border-radius: 8px; padding: 9px 14px;
    font-size: .93rem; cursor: pointer;
    transition: background .2s; align-self: flex-end;
    text-decoration: none; display: inline-block;
}
.btn-limpiar-filtro:hover { background: #545b62; }

/* Stats */
.stats-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 22px; }
.stat-card {
    flex: 1; min-width: 120px;
    border-radius: 10px; padding: 12px 16px; text-align: center;
}
.stat-card .stat-num { font-size: 1.8rem; font-weight: 700; }
.stat-card .stat-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }

/* Tabla */
.table-wrap { overflow-x: auto; }
table { border-collapse: collapse; width: 100%; font-size: .88rem; }
thead tr { background: #003366; color: #fff; }
th { padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
td { padding: 9px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
tbody tr:hover { background: #f5f8ff; }
tr:last-child td { border-bottom: none; }

.badge {
    display: inline-block; padding: 3px 10px;
    border-radius: 20px; font-size: .78rem; font-weight: 700;
}

.btn-detalle {
    background: #0033cc; color: #fff; text-decoration: none;
    padding: 5px 12px; border-radius: 6px; font-size: .82rem;
    font-weight: 600; white-space: nowrap; transition: background .2s;
}
.btn-detalle:hover { background: #0022aa; }

.btn-pdf {
    background: #28a745; color: #fff; text-decoration: none;
    padding: 5px 12px; border-radius: 6px; font-size: .82rem;
    font-weight: 600; white-space: nowrap; transition: background .2s;
    margin-left: 4px;
}
.btn-pdf:hover { background: #218838; }

.empty-state { text-align: center; padding: 40px 20px; color: #aaa; }
.empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }

.alert-danger {
    background: #f8d7da; border-left: 4px solid #dc3545;
    border-radius: 6px; padding: 12px 16px; color: #721c24; margin-bottom: 16px;
}

@media (max-width: 640px) { .container { padding: 18px; } h2 { font-size: 1.4rem; } }
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-clipboard-list"></i> Solicitudes de Baja</h2>

<div class="container">

    <!-- Boton crear nueva -->
    <div style="margin-bottom:18px;display:flex;justify-content:flex-end">
        <a href="CrearSolicitudBaja.php" style="
            background:#0033cc;color:#fff;text-decoration:none;
            padding:9px 18px;border-radius:8px;font-weight:600;font-size:.93rem">
            <i class="fas fa-plus"></i> Nueva Solicitud de Baja
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="">
        <div class="filter-row">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Estatus</label>
                <select name="estatus">
                    <option value="">Todos</option>
                    <option value="Borrador"   <?= $estatus === 'Borrador'   ? 'selected' : '' ?>>Borrador</option>
                    <option value="Pendiente"  <?= $estatus === 'Pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                    <option value="En Proceso" <?= $estatus === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="Completado" <?= $estatus === 'Completado' ? 'selected' : '' ?>>Completado</option>
                    <option value="Rechazado"  <?= $estatus === 'Rechazado'  ? 'selected' : '' ?>>Rechazado</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-id-card"></i> No. Empleado</label>
                <input type="text" name="id_empleado"
                       placeholder="Ej. EMP-001"
                       value="<?= htmlspecialchars($idEmpleado) ?>" />
            </div>
            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <?php if ($estatus || $idEmpleado): ?>
                <a href="ListadoSolicitudesBaja.php" class="btn-limpiar-filtro">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= $errMsg ?></div>
    <?php else: ?>

        <?php
        $cBorrador = 0; $cPendiente = 0; $cEnProceso = 0; $cCompletado = 0; $cRechazado = 0;
        foreach ($solicitudes as $s) {
            match($s['estatus_general'] ?? $s['estatus'] ?? '') {
                'Borrador'   => $cBorrador++,
                'Pendiente'  => $cPendiente++,
                'En Proceso' => $cEnProceso++,
                'Completado' => $cCompletado++,
                'Rechazado'  => $cRechazado++,
                default      => null,
            };
        }
        $cTotal = count($solicitudes);
        ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card" style="background:#e2e3e5;color:#383d41">
                <div class="stat-num"><?= $cTotal ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card" style="background:#fff3cd;color:#856404">
                <div class="stat-num"><?= $cPendiente ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card" style="background:#cce5ff;color:#004085">
                <div class="stat-num"><?= $cEnProceso ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card" style="background:#d4edda;color:#155724">
                <div class="stat-num"><?= $cCompletado ?></div>
                <div class="stat-label">Completados</div>
            </div>
            <div class="stat-card" style="background:#f8d7da;color:#721c24">
                <div class="stat-num"><?= $cRechazado ?></div>
                <div class="stat-label">Rechazados</div>
            </div>
        </div>

        <!-- Tabla -->
        <?php if (empty($solicitudes)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                No se encontraron solicitudes<?= ($estatus || $idEmpleado) ? ' con los filtros aplicados' : '' ?>.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Empleado</th>
                            <th>No. Empleado</th>
                            <th>Fecha Salida</th>
                            <th>Estatus</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s):
                            $est = $s['estatus_general'] ?? $s['estatus'] ?? 'Pendiente';
                            [$colorHex] = colorEstatus($est);
                            $nombre = trim(
                                ($s['emp_nombre'] ?? '') . ' ' .
                                ($s['emp_apellido_paterno'] ?? '') . ' ' .
                                ($s['emp_apellido_materno'] ?? '')
                            ) ?: ($s['emp_nombre_completo'] ?? '-');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['folio'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($nombre) ?></td>
                            <td style="font-size:.82rem"><?= htmlspecialchars($s['emp_id_empleado'] ?? '') ?></td>
                            <td style="white-space:nowrap"><?= htmlspecialchars($s['fecha_salida'] ?? '') ?></td>
                            <td>
                                <span class="badge" style="background:<?= $colorHex ?>;color:#fff">
                                    <?= htmlspecialchars($est) ?>
                                </span>
                            </td>
                            <td style="font-size:.8rem;white-space:nowrap;color:#666">
                                <?= htmlspecialchars(substr($s['fecha_creacion'] ?? '', 0, 10)) ?>
                            </td>
                            <td style="white-space:nowrap">
                                <a href="DetalleSolicitudBaja.php?folio=<?= urlencode($s['folio'] ?? '') ?>"
                                   class="btn-detalle">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <?php if ($est === 'Completado'): ?>
                                    <a href="DetalleSolicitudBaja.php?folio=<?= urlencode($s['folio'] ?? '') ?>"
                                       class="btn-pdf" title="Ver detalle y enviar PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});
</script>
</body>
</html>
