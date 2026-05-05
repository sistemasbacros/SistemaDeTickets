<?php
// DEPRECATED 2026-05-05: sistema reemplazado por Solicitud de Baja v2
// (BCR-TH-SGI-FO-27 con conceptos por area y firmantes configurables).
// Los folios LIB-* del sistema viejo no tienen equivalente directo en el
// nuevo, por eso redirigimos al listado del sistema nuevo en vez de a un
// detalle especifico.
http_response_code(301);
header('Location: ListadoSolicitudesBaja.php');
exit;

/**
 * @file EstadoLiberacion.php (DEPRECATED — ver DetalleSolicitudBaja.php)
 * @brief Muestra el estado actual de una solicitud de Liberación de Responsabilidades.
 *
 * Recibe el folio por GET y consulta GET /api/TicketBacros/liberacion/{folio}.
 * Muestra barra de progreso, tabla de firmantes con colores por estatus.
 *
 * NOTA: Página pública (consulta de estado por folio). NO incluir auth_check
 * — el correo de notificación enlaza directo aquí para usuarios sin sesión.
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

$folio   = trim($_GET['folio'] ?? '');
$sol     = null;
$errMsg  = null;

if ($folio) {
    $apiUrl = getApiUrl();
    $ch = curl_init($apiUrl . '/api/TicketBacros/liberacion/' . urlencode($folio));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp    = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        $errMsg = 'No se pudo conectar al servidor: ' . htmlspecialchars($curlErr);
    } elseif ($httpCode === 404) {
        $errMsg = 'Folio <strong>' . htmlspecialchars($folio) . '</strong> no encontrado.';
    } elseif ($httpCode !== 200) {
        $errMsg = 'Error del servidor (HTTP ' . $httpCode . ').';
    } else {
        $data = json_decode($resp, true);
        $sol  = $data['data'] ?? $data ?? null;
        if (!$sol || !isset($sol['folio'])) {
            $errMsg = 'Respuesta inesperada del servidor.';
            $sol = null;
        }
    }
}

// Helpers de color
function colorEstatus(string $est): string {
    return match($est) {
        'Completado'  => '#28a745',
        'En Proceso'  => '#0066cc',
        'Rechazado'   => '#dc3545',
        default       => '#6c757d',   // Pendiente
    };
}

function bgFirmante(string $est): string {
    return match($est) {
        'Firmado'   => '#d4edda',
        'Rechazado' => '#f8d7da',
        default     => '#ffffff',
    };
}

$total = 14;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Estado de Liberación<?= $folio ? ' — ' . htmlspecialchars($folio) : '' ?></title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

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
}

.container {
    background: #fff; padding: 28px 36px;
    border-radius: 15px; box-shadow: 0 12px 25px rgba(0,0,0,.1);
    max-width: 860px; width: 100%;
}

/* Busqueda */
.search-row {
    display: flex; gap: 10px; margin-bottom: 22px;
}
.search-row input {
    flex: 1; padding: 10px 14px; border-radius: 8px;
    border: 1px solid #bbb; font-size: 1rem; font-family: 'Inter', sans-serif;
    background: #f9f9f9;
}
.search-row input:focus { border-color: #0033cc; outline: none; background: #eaeaea; }
.search-row button {
    background: #0033cc; color: #fff; border: none;
    border-radius: 8px; padding: 10px 18px;
    font-size: 1rem; font-weight: 600; cursor: pointer;
    transition: background .2s;
}
.search-row button:hover { background: #0022aa; }

/* Info empleado */
.emp-card {
    background: #f0f4ff; border-left: 4px solid #0033cc;
    border-radius: 8px; padding: 14px 18px;
    margin-bottom: 20px; display: grid;
    grid-template-columns: 1fr 1fr; gap: 6px 20px;
    font-size: .93rem;
}
.emp-card .emp-full { grid-column: 1 / -1; font-size: 1.1rem; font-weight: 700; color: #003; margin-bottom: 4px; }
.emp-label { color: #555; }
.emp-val   { font-weight: 600; }

/* Badge estatus */
.badge {
    display: inline-block; padding: 4px 14px;
    border-radius: 20px; color: #fff; font-size: .85rem; font-weight: 700;
}

/* Barra de progreso */
.progress-wrap { margin: 18px 0 6px; }
.progress-label { display: flex; justify-content: space-between; font-size: .85rem; color: #555; margin-bottom: 4px; }
.progress-bg { background: #e0e0e0; border-radius: 10px; height: 18px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 10px; transition: width .6s ease; }

/* Tabla firmantes */
.table-wrap { overflow-x: auto; margin-top: 20px; }
table { border-collapse: collapse; width: 100%; font-size: .9rem; }
thead tr { background: #003366; color: #fff; }
th { padding: 10px 12px; text-align: left; font-weight: 600; white-space: nowrap; }
td { padding: 9px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
tr:last-child td { border-bottom: none; }

.firma-thumb {
    width: 80px; height: 36px; object-fit: contain;
    border: 1px solid #ccc; border-radius: 4px; background: #fff;
}

.alert-danger {
    background: #f8d7da; border-left: 4px solid #dc3545;
    border-radius: 6px; padding: 12px 16px; color: #721c24; margin-bottom: 16px;
}

@media (max-width: 600px) {
    .container { padding: 18px; }
    .emp-card { grid-template-columns: 1fr; }
    h2 { font-size: 1.4rem; }
}
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-search"></i> Estado de Liberación</h2>

<div class="container">

    <!-- Formulario de búsqueda -->
    <form method="GET" action="">
        <div class="search-row">
            <input type="text" name="folio" placeholder="Ingrese folio: LIB-2026-XXXX"
                   value="<?= htmlspecialchars($folio) ?>" required />
            <button type="submit"><i class="fas fa-search"></i> Buscar</button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= $errMsg ?></div>
    <?php endif; ?>

    <?php if ($sol): ?>
        <?php
        $firmantes      = $sol['firmantes']      ?? [];
        $firmanteActual = (int)($sol['firmante_actual'] ?? 1);
        $estatusGral    = $sol['estatus_general'] ?? 'Pendiente';
        $porcentaje     = $total > 0 ? round(($firmanteActual - 1) / $total * 100) : 0;
        $colorEstatus   = colorEstatus($estatusGral);
        ?>

        <!-- Info empleado -->
        <div class="emp-card">
            <div class="emp-full">
                <i class="fas fa-user"></i>
                <?= htmlspecialchars(($sol['emp_nombre'] ?? '') . ' ' . ($sol['emp_apellido_paterno'] ?? '') . ' ' . ($sol['emp_apellido_materno'] ?? '')) ?>
            </div>
            <span class="emp-label">Folio:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['folio']) ?></span>

            <span class="emp-label">ID Empleado:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_id_empleado'] ?? '—') ?></span>

            <span class="emp-label">Departamento:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_departamento'] ?? '—') ?></span>

            <span class="emp-label">Puesto:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_puesto'] ?? '—') ?></span>

            <span class="emp-label">Fecha de Baja:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_fecha_baja'] ?? '—') ?></span>

            <span class="emp-label">Motivo:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_motivo_baja'] ?? '—') ?></span>

            <span class="emp-label">Estatus General:</span>
            <span class="emp-val">
                <span class="badge" style="background:<?= $colorEstatus ?>">
                    <?= htmlspecialchars($estatusGral) ?>
                </span>
            </span>

            <span class="emp-label">Fecha Creación:</span>
            <span class="emp-val"><?= htmlspecialchars(substr($sol['fecha_creacion'] ?? '', 0, 16)) ?></span>
        </div>

        <!-- Barra de progreso -->
        <div class="progress-wrap">
            <div class="progress-label">
                <span><i class="fas fa-tasks"></i> Progreso de firmas</span>
                <span><?= $firmanteActual - 1 ?> / <?= $total ?> completadas (<?= $porcentaje ?>%)</span>
            </div>
            <div class="progress-bg">
                <div class="progress-fill" style="width:<?= $porcentaje ?>%;background:<?= $colorEstatus ?>"></div>
            </div>
        </div>

        <!-- Tabla de firmantes -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Área</th>
                        <th>Nombre</th>
                        <th>Estatus</th>
                        <th>Fecha Firma</th>
                        <th>Firma</th>
                        <th>Comentarios</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firmantes as $f): ?>
                        <?php $bg = bgFirmante($f['estatus'] ?? ''); ?>
                        <tr style="background:<?= $bg ?>">
                            <td><strong><?= (int)$f['orden'] ?></strong></td>
                            <td><?= htmlspecialchars($f['area'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['nombre'] ?? '') ?></td>
                            <td>
                                <?php
                                $est = $f['estatus'] ?? 'Pendiente';
                                $ic  = match($est) {
                                    'Firmado'   => '<i class="fas fa-check-circle" style="color:#28a745"></i>',
                                    'Rechazado' => '<i class="fas fa-times-circle" style="color:#dc3545"></i>',
                                    default     => '<i class="fas fa-clock" style="color:#6c757d"></i>',
                                };
                                echo $ic . ' ' . htmlspecialchars($est);
                                ?>
                            </td>
                            <td><?= htmlspecialchars($f['fecha_firma'] ? substr($f['fecha_firma'], 0, 16) : '—') ?></td>
                            <td>
                                <?php if (!empty($f['firma_img'])): ?>
                                    <img src="<?= htmlspecialchars(getApiUrl() . '/api/TicketBacros/firmas/' . $f['firma_img']) ?>"
                                         alt="Firma <?= (int)$f['orden'] ?>"
                                         class="firma-thumb" />
                                <?php else: ?>
                                    <span style="color:#aaa">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.85rem;color:#555">
                                <?= htmlspecialchars($f['comentarios'] ?? '') ?: '<span style="color:#ccc">—</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Botones de acción -->
        <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap">
            <a href="AdminLiberaciones.php" style="
                background:#6c757d;color:#fff;text-decoration:none;
                padding:9px 18px;border-radius:8px;font-weight:600;font-size:.93rem">
                <i class="fas fa-list"></i> Ver todas las solicitudes
            </a>
            <a href="CrearLiberacion.php" style="
                background:#0033cc;color:#fff;text-decoration:none;
                padding:9px 18px;border-radius:8px;font-weight:600;font-size:.93rem">
                <i class="fas fa-plus"></i> Nueva Liberación
            </a>
        </div>

    <?php elseif (!$folio): ?>
        <p style="text-align:center;color:#888;margin-top:10px">
            <i class="fas fa-search"></i> Ingrese un folio para consultar el estado.
        </p>
    <?php endif; ?>

</div>

<script>
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});
</script>
</body>
</html>
