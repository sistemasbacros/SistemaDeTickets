<?php
/**
 * @file DetalleSolicitudBaja.php
 * @brief Detalle de una Solicitud de Baja con progreso de firmas por area.
 *
 * Recibe folio por GET, consulta GET /api/TicketBacros/solicitud-baja/{folio}.
 * Muestra datos del empleado, progreso por area (AND/OR), detalle de cada firmante.
 */

require_once __DIR__ . '/auth_check.php';
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
    $ch = curl_init($apiUrl . '/api/TicketBacros/solicitud-baja/' . urlencode($folio));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $resp     = curl_exec($ch);
    $curlErr  = curl_error($ch);
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

function colorEstatus(string $est): string {
    return match($est) {
        'Completado'  => '#28a745',
        'En Proceso'  => '#0066cc',
        'Rechazado'   => '#dc3545',
        'Pendiente'   => '#ffc107',
        default       => '#6c757d',
    };
}

/** Conceptos fijos por departamento — mismos que BCR-TH-SGI-FO-27 */
function conceptosPorArea(string $area): array {
    $a = mb_strtoupper(trim($area));
    return match(true) {
        $a === 'SISTEMAS'                                              => ['LAPTOP','PC DE ESCRITORIO','MOUSE','CARGADOR','CELULAR'],
        str_contains($a,'ALMAC')                                       => ['HERRAMIENTA'],
        str_contains($a,'JURID')                                       => ['REPRESENTANTE LEGAL'],
        $a === 'LICITACIONES'                                          => ['DOCUMENTACION LEGAL'],
        $a === 'FINANZAS' || $a === 'COMPRAS'                          => ['COMPROBACION DE GASTOS','TARJETA SI VALE','ADEUDO DE FACTURACION'],
        str_contains($a,'GENERALES') || $a === 'MANTENIMIENTO'         => ['ADEUDOS VEHICULOS','MULTAS','TAG','TARJETA DE GASOLINA'],
        str_contains($a,'TITULAR') || $a === 'DIRECCION'               => ['INFORMACION GENERAL','CONTRASENAS'],
        $a === 'ARCHIVO'                                               => ['PRESTAMO DE DOCUMENTOS'],
        $a === 'CALIDAD'                                               => ['EQUIPOS DE MEDICION'],
        str_contains($a,'HIGIENE') || str_contains($a,'SEGURIDAD')     => ['EQUIPO DE PROTECCION PERSONAL','UNIFORME (< 3 MESES)'],
        $a === 'COMEDOR'                                               => ['CONSUMOS','EXTRA'],
        str_contains($a,'TALENTO')                                     => ['CREDENCIALES','CREDITO FONACOT','CREDITO INFONAVIT'],
        str_contains($a,'AUDITORIA')                                   => ['VISTO BUENO PROCESO'],
        str_contains($a,'OPERACIONES')                                 => [],
        default                                                        => [],
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Detalle Solicitud de Baja<?= $folio ? ' - ' . htmlspecialchars($folio) : '' ?></title>

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
}

.container {
    background: #fff; padding: 28px 36px;
    border-radius: 15px; box-shadow: 0 12px 25px rgba(0,0,0,.1);
    max-width: 960px; width: 100%;
}

/* Busqueda */
.search-row { display: flex; gap: 10px; margin-bottom: 22px; }
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

/* Header con folio y estatus */
.detail-header {
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px; margin-bottom: 20px;
}
.folio-badge {
    background: #003366; color: #fff;
    display: inline-block; padding: 6px 18px;
    border-radius: 20px; font-size: 1.1rem; font-weight: 700;
}
.badge {
    display: inline-block; padding: 4px 14px;
    border-radius: 20px; color: #fff; font-size: .85rem; font-weight: 700;
}

/* Info empleado */
.emp-card {
    background: #f0f4ff; border-left: 4px solid #0033cc;
    border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; font-size: .93rem;
}
.emp-card .emp-full { grid-column: 1 / -1; font-size: 1.1rem; font-weight: 700; color: #003; margin-bottom: 4px; }
.emp-label { color: #555; }
.emp-val   { font-weight: 600; }

/* Barra de progreso general */
.progress-wrap { margin: 18px 0 6px; }
.progress-label { display: flex; justify-content: space-between; font-size: .85rem; color: #555; margin-bottom: 4px; }
.progress-bg { background: #e0e0e0; border-radius: 10px; height: 18px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 10px; transition: width .6s ease; }

/* Area sections */
.area-section {
    background: #f8f9ff; border: 1px solid #d0d7ff;
    border-radius: 10px; padding: 16px 18px; margin-bottom: 14px;
}
.area-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 10px; flex-wrap: wrap; gap: 6px;
}
.area-header h4 { margin: 0; font-size: 1rem; color: #003; }
.area-logic {
    font-size: .8rem; font-weight: 600; padding: 3px 10px;
    border-radius: 12px; background: #e0e7ff; color: #0033cc;
}
.area-status {
    font-size: .82rem; font-weight: 700; padding: 3px 10px;
    border-radius: 12px;
}

/* Firmante row */
.firmante-row {
    padding: 8px 12px; border-radius: 6px; margin-bottom: 6px;
    display: flex; justify-content: space-between; align-items: flex-start;
    flex-wrap: wrap; gap: 6px;
}
.firmante-row.firmado   { background: #d4edda; }
.firmante-row.rechazado { background: #f8d7da; }
.firmante-row.pendiente { background: #fff; border: 1px solid #eee; }

.firmante-info { flex: 1; min-width: 200px; }
.firmante-nombre { font-weight: 700; font-size: .93rem; }
.firmante-estatus { font-size: .82rem; margin-top: 2px; }

.conceptos-mini {
    font-size: .82rem; color: #555; margin-top: 4px;
}
.concepto-item { display: inline-block; margin-right: 8px; }
.concepto-si { color: #28a745; }
.concepto-no { color: #dc3545; }

/* Tabla de conceptos por departamento */
.conceptos-table {
    width: 100%; border-collapse: collapse; font-size: .85rem;
    margin-top: 10px; margin-bottom: 6px;
}
.conceptos-table thead tr { background: #003366; color: #fff; }
.conceptos-table th {
    padding: 7px 10px; text-align: left; font-weight: 600;
    font-size: .8rem; white-space: nowrap;
}
.conceptos-table td {
    padding: 6px 10px; border-bottom: 1px solid #e8e8e8;
    vertical-align: middle;
}
.conceptos-table tbody tr:hover { background: #f0f4ff; }
.badge-si {
    background: #d4edda; color: #155724;
    padding: 2px 10px; border-radius: 10px; font-weight: 600; font-size: .8rem;
}
.badge-no {
    background: #f8d7da; color: #721c24;
    padding: 2px 10px; border-radius: 10px; font-weight: 600; font-size: .8rem;
}
.badge-pending {
    background: #e9ecef; color: #6c757d;
    padding: 2px 10px; border-radius: 10px; font-weight: 600; font-size: .8rem;
}

.firma-thumb {
    width: 80px; height: 36px; object-fit: contain;
    border: 1px solid #ccc; border-radius: 4px; background: #fff;
}

/* Alertas */
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

<h2><i class="fas fa-search"></i> Detalle Solicitud de Baja</h2>

<div class="container">

    <!-- Busqueda -->
    <form method="GET" action="">
        <div class="search-row">
            <input type="text" name="folio" placeholder="Ingrese folio: BAJA-2026-XXXX"
                   value="<?= htmlspecialchars($folio) ?>" required />
            <button type="submit"><i class="fas fa-search"></i> Buscar</button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= $errMsg ?></div>
    <?php endif; ?>

    <?php if ($sol): ?>
        <?php
        $firmantes    = $sol['firmantes']    ?? [];
        $firmasConfig = $sol['firmas_config'] ?? [];
        $estatusGral  = $sol['estatus_general'] ?? $sol['estatus'] ?? 'Pendiente';
        $colorEst     = colorEstatus($estatusGral);

        // Calcular progreso por area
        $areasProgreso = [];
        foreach ($firmasConfig as $fc) {
            $area   = $fc['area'] ?? '';
            $logica = $fc['logica'] ?? 'AND';
            $deptFirmantes = array_filter($firmantes, fn($f) => ($f['area'] ?? '') === $area);
            $firmados   = array_filter($deptFirmantes, fn($f) => ($f['estatus'] ?? '') === 'Firmado');
            $rechazados = array_filter($deptFirmantes, fn($f) => ($f['estatus'] ?? '') === 'Rechazado');

            $completa = false;
            if (count($rechazados) > 0) {
                $completa = false;
            } elseif ($logica === 'AND') {
                $completa = count($firmados) === count($deptFirmantes) && count($deptFirmantes) > 0;
            } else {
                $completa = count($firmados) > 0;
            }

            $areasProgreso[] = [
                'area'       => $area,
                'logica'     => $logica,
                'total'      => count($deptFirmantes),
                'firmados'   => count($firmados),
                'rechazados' => count($rechazados),
                'completa'   => $completa,
                'firmantes'  => array_values($deptFirmantes),
            ];
        }

        $areasCompletas = count(array_filter($areasProgreso, fn($a) => $a['completa']));
        $totalAreas     = count($areasProgreso);
        $porcentaje     = $totalAreas > 0 ? round($areasCompletas / $totalAreas * 100) : 0;

        $nombre = trim(
            ($sol['emp_nombre'] ?? '') . ' ' .
            ($sol['emp_apellido_paterno'] ?? '') . ' ' .
            ($sol['emp_apellido_materno'] ?? '')
        ) ?: ($sol['emp_nombre_completo'] ?? '-');
        ?>

        <!-- Header -->
        <div class="detail-header">
            <span class="folio-badge"><?= htmlspecialchars($sol['folio']) ?></span>
            <span class="badge" style="background:<?= $colorEst ?>">
                <?= htmlspecialchars($estatusGral) ?>
            </span>
        </div>

        <!-- Info empleado -->
        <div class="emp-card">
            <div class="emp-full"><i class="fas fa-user"></i> <?= htmlspecialchars($nombre) ?></div>

            <span class="emp-label">No. Empleado:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_id_empleado'] ?? '-') ?></span>

            <span class="emp-label">Puesto:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_puesto'] ?? '-') ?></span>

            <span class="emp-label">Departamento:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_departamento'] ?? '-') ?></span>

            <span class="emp-label">Fecha Salida:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['fecha_salida'] ?? '-') ?></span>

            <span class="emp-label">Motivo:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['emp_motivo_baja'] ?? '-') ?></span>

            <span class="emp-label">Jefe Directo:</span>
            <span class="emp-val"><?= htmlspecialchars($sol['jefe_directo'] ?? '-') ?></span>

            <span class="emp-label">Fecha Creacion:</span>
            <span class="emp-val"><?= htmlspecialchars(substr($sol['fecha_creacion'] ?? '', 0, 16)) ?></span>
        </div>

        <!-- Barra de progreso general -->
        <?php if ($totalAreas > 0): ?>
        <div class="progress-wrap">
            <div class="progress-label">
                <span><i class="fas fa-tasks"></i> Progreso de firmas por area</span>
                <span><?= $areasCompletas ?> / <?= $totalAreas ?> areas completas (<?= $porcentaje ?>%)</span>
            </div>
            <div class="progress-bg">
                <div class="progress-fill" style="width:<?= $porcentaje ?>%;background:<?= $colorEst ?>"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detalle por area -->
        <?php foreach ($areasProgreso as $ap): ?>
            <div class="area-section">
                <div class="area-header">
                    <h4><i class="fas fa-building"></i> <?= htmlspecialchars($ap['area']) ?></h4>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span class="area-logic">
                            <?= $ap['logica'] ?> — requiere <?= $ap['logica'] === 'AND' ? $ap['total'] . '/' . $ap['total'] : '1/' . $ap['total'] ?>
                        </span>
                        <?php if ($ap['rechazados'] > 0): ?>
                            <span class="area-status" style="background:#f8d7da;color:#721c24">Rechazado</span>
                        <?php elseif ($ap['completa']): ?>
                            <span class="area-status" style="background:#d4edda;color:#155724">Completa</span>
                        <?php else: ?>
                            <span class="area-status" style="background:#fff3cd;color:#856404">Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                // Tabla de conceptos del departamento
                $conceptosArea = conceptosPorArea($ap['area']);
                // Recopilar respuestas de todos los firmantes del area
                $respuestasArea = [];
                foreach ($ap['firmantes'] as $f) {
                    if (!empty($f['respuestas_conceptos']) && is_array($f['respuestas_conceptos'])) {
                        foreach ($f['respuestas_conceptos'] as $rc) {
                            $key = mb_strtoupper(trim($rc['concepto'] ?? ''));
                            $respuestasArea[$key] = $rc;
                        }
                    }
                }
                ?>

                <?php if (!empty($conceptosArea)): ?>
                <div style="overflow-x:auto">
                    <table class="conceptos-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th style="text-align:center">Se Entrega</th>
                                <th style="text-align:center">Monto a Descontar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($conceptosArea as $concepto):
                            $key = mb_strtoupper(trim($concepto));
                            $resp = $respuestasArea[$key] ?? null;
                            $entrega = $resp['se_entrega'] ?? null;
                            $monto = $resp['monto'] ?? null;
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($concepto) ?></strong></td>
                                <td style="text-align:center">
                                    <?php if ($entrega === true): ?>
                                        <span class="badge-si">SI</span>
                                    <?php elseif ($entrega === false): ?>
                                        <span class="badge-no">NO</span>
                                    <?php else: ?>
                                        <span class="badge-pending">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center">
                                    <?php if ($monto !== null && $monto !== ''): ?>
                                        $<?= htmlspecialchars(number_format((float)$monto, 2)) ?>
                                    <?php else: ?>
                                        <span style="color:#ccc">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php foreach ($ap['firmantes'] as $f):
                    $fEst = $f['estatus'] ?? 'Pendiente';
                    $cssClass = $fEst === 'Firmado' ? 'firmado' : ($fEst === 'Rechazado' ? 'rechazado' : 'pendiente');
                    $icon = match($fEst) {
                        'Firmado'   => '<i class="fas fa-check-circle" style="color:#28a745"></i>',
                        'Rechazado' => '<i class="fas fa-times-circle" style="color:#dc3545"></i>',
                        default     => '<i class="fas fa-clock" style="color:#6c757d"></i>',
                    };
                ?>
                    <div class="firmante-row <?= $cssClass ?>">
                        <div class="firmante-info">
                            <div class="firmante-nombre">
                                <?= $icon ?> <?= htmlspecialchars($f['nombre'] ?? $f['nombre_completo'] ?? 'Firmante') ?>
                            </div>
                            <div class="firmante-estatus">
                                <?= htmlspecialchars($fEst) ?>
                                <?php if (!empty($f['fecha_firma'])): ?>
                                    — <?= htmlspecialchars(substr($f['fecha_firma'], 0, 16)) ?>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($f['comentarios'])): ?>
                                <div style="font-size:.82rem;color:#666;margin-top:4px;font-style:italic">
                                    "<?= htmlspecialchars($f['comentarios']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($f['firma_img'])): ?>
                            <img src="<?= htmlspecialchars(getApiUrl() . '/api/TicketBacros/firmas/' . $f['firma_img']) ?>"
                                 alt="Firma" class="firma-thumb" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($ap['firmantes'])): ?>
                    <p style="color:#999;font-size:.88rem;margin:4px 0">Sin firmantes asignados.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($firmasConfig) && !empty($firmantes)): ?>
            <!-- Fallback: mostrar firmantes sin config de areas -->
            <div class="area-section">
                <div class="area-header">
                    <h4><i class="fas fa-users"></i> Firmantes</h4>
                </div>
                <?php foreach ($firmantes as $f):
                    $fEst = $f['estatus'] ?? 'Pendiente';
                    $cssClass = $fEst === 'Firmado' ? 'firmado' : ($fEst === 'Rechazado' ? 'rechazado' : 'pendiente');
                    $icon = match($fEst) {
                        'Firmado'   => '<i class="fas fa-check-circle" style="color:#28a745"></i>',
                        'Rechazado' => '<i class="fas fa-times-circle" style="color:#dc3545"></i>',
                        default     => '<i class="fas fa-clock" style="color:#6c757d"></i>',
                    };
                ?>
                    <div class="firmante-row <?= $cssClass ?>">
                        <div class="firmante-info">
                            <div class="firmante-nombre">
                                <?= $icon ?> <?= htmlspecialchars($f['nombre'] ?? $f['nombre_completo'] ?? '') ?>
                                <?php if (!empty($f['area'])): ?>
                                    <span style="font-size:.82rem;color:#666">(<?= htmlspecialchars($f['area']) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="firmante-estatus">
                                <?= htmlspecialchars($fEst) ?>
                                <?php if (!empty($f['fecha_firma'])): ?>
                                    — <?= htmlspecialchars(substr($f['fecha_firma'], 0, 16)) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($f['firma_img'])): ?>
                            <img src="<?= htmlspecialchars(getApiUrl() . '/api/TicketBacros/firmas/' . $f['firma_img']) ?>" class="firma-thumb" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Acciones PDF -->
        <?php if ($sol['estatus_general'] === 'Completado'): ?>
            <div style="margin-top:16px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                <button id="btnPreviewPdf" onclick="previsualizarPdf()" style="
                    background:#28a745;color:#fff;border:none;cursor:pointer;
                    padding:10px 22px;border-radius:8px;font-weight:600;font-size:.95rem;
                    font-family:'Inter',sans-serif;transition:background .2s">
                    <i class="fas fa-eye"></i> Previsualizar PDF
                </button>
                <button id="btnEnviarPdf" onclick="enviarPdfPorCorreo()" style="
                    background:#0033cc;color:#fff;border:none;cursor:pointer;
                    padding:10px 22px;border-radius:8px;font-weight:600;font-size:.95rem;
                    font-family:'Inter',sans-serif;transition:background .2s">
                    <i class="fas fa-envelope"></i> Enviar PDF por correo
                </button>
            </div>

            <!-- Preview iframe -->
            <div id="pdfPreviewContainer" style="display:none;margin-top:16px;border:2px solid #0033cc;border-radius:8px;overflow:hidden">
                <div style="background:#003366;color:#fff;padding:8px 14px;display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:600;font-size:.9rem"><i class="fas fa-file-pdf"></i> Vista previa del PDF</span>
                    <button onclick="cerrarPreview()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1.2rem" title="Cerrar">&times;</button>
                </div>
                <iframe id="pdfPreviewFrame" style="width:100%;height:600px;border:none"></iframe>
            </div>
        <?php endif; ?>

        <!-- Botones -->
        <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap">
            <a href="ListadoSolicitudesBaja.php" style="
                background:#6c757d;color:#fff;text-decoration:none;
                padding:9px 18px;border-radius:8px;font-weight:600;font-size:.93rem">
                <i class="fas fa-list"></i> Ver todas las solicitudes
            </a>
            <a href="CrearSolicitudBaja.php" style="
                background:#0033cc;color:#fff;text-decoration:none;
                padding:9px 18px;border-radius:8px;font-weight:600;font-size:.93rem">
                <i class="fas fa-plus"></i> Nueva Solicitud
            </a>
        </div>

    <?php elseif (!$folio): ?>
        <p style="text-align:center;color:#888;margin-top:10px">
            <i class="fas fa-search"></i> Ingrese un folio para consultar el detalle.
        </p>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});

function previsualizarPdf() {
    const folio = '<?= htmlspecialchars($folio, ENT_QUOTES) ?>';
    const API_URL = window.location.protocol + '//' + window.location.hostname + ':3000';
    const pdfUrl = `${API_URL}/api/TicketBacros/pdfs/BCR-TH-SGI-FO-27_BAJA_${folio.replace(/-/g, '_')}.pdf?t=${Date.now()}`;

    const container = document.getElementById('pdfPreviewContainer');
    const iframe = document.getElementById('pdfPreviewFrame');
    iframe.src = pdfUrl;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth' });
}

function cerrarPreview() {
    document.getElementById('pdfPreviewContainer').style.display = 'none';
    document.getElementById('pdfPreviewFrame').src = '';
}

async function enviarPdfPorCorreo() {
    const btn = document.getElementById('btnEnviarPdf');
    if (!btn) return;

    const folio = '<?= htmlspecialchars($folio, ENT_QUOTES) ?>';
    if (!folio) return;

    const API_URL = window.location.protocol + '//' + window.location.hostname + ':3000';

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const res = await fetch(`${API_URL}/api/TicketBacros/solicitud-baja/enviar-pdf/${encodeURIComponent(folio)}`, {
            method: 'POST'
        });
        const data = await res.json();

        if (res.ok && data.success) {
            Swal.fire({
                icon: 'success',
                title: 'PDF enviado',
                text: data.message || 'El PDF fue enviado exitosamente al Administrador de Bajas.',
                confirmButtonColor: '#0033cc'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo enviar el PDF.',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexion',
            text: 'No se pudo conectar al servidor.',
            confirmButtonColor: '#dc3545'
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-envelope"></i> Enviar PDF por correo';
    }
}
</script>
</body>
</html>
