<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$serverName = '192.168.100.95,1433';
$connInfo   = [
    'Database'               => 'Comedor',
    'UID'                    => 'Larome03',
    'PWD'                    => 'Larome03',
    'CharacterSet'           => 'UTF-8',
    'Encrypt'                => false,
    'TrustServerCertificate' => true,
];

$pedidos            = [];
$resumen_por_dia    = [];
$fechas_disponibles = [];
$fecha_seleccionada = $_GET['fecha'] ?? '';
$error_msg          = '';
$total_desayunos    = 0;
$total_comidas      = 0;

try {
    $conn = sqlsrv_connect($serverName, $connInfo);
    if ($conn === false) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    // Fechas distintas registradas en PedidosComida
    $stmt_fechas = sqlsrv_query($conn, "SELECT DISTINCT Fecha FROM PedidosComida ORDER BY Fecha DESC");
    if ($stmt_fechas) {
        while ($row = sqlsrv_fetch_array($stmt_fechas, SQLSRV_FETCH_ASSOC)) {
            $f = $row['Fecha'];
            if ($f instanceof DateTime) $f = $f->format('Y-m-d');
            $fechas_disponibles[] = $f;
        }
        sqlsrv_free_stmt($stmt_fechas);
    }

    if (empty($fecha_seleccionada) && !empty($fechas_disponibles)) {
        $fecha_seleccionada = $fechas_disponibles[0];
    }

    if (!empty($fecha_seleccionada)) {
        $sql = "
            SELECT Id_Empleado, Usuario, Fecha,
                   Lunes, Martes, Miercoles, Jueves, Viernes, Costo
            FROM PedidosComida
            WHERE Fecha = ?
            ORDER BY Usuario, Id_Empleado
        ";
        $stmt = sqlsrv_query($conn, $sql, [$fecha_seleccionada]);
        if ($stmt === false) throw new Exception("Error al consultar PedidosComida.");
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['Fecha'] instanceof DateTime) $row['Fecha'] = $row['Fecha']->format('Y-m-d');
            $pedidos[] = $row;
            foreach (['Lunes','Martes','Miercoles','Jueves','Viernes'] as $dia) {
                if (!empty($row[$dia])) {
                    if (!isset($resumen_por_dia[$dia])) $resumen_por_dia[$dia] = ['Desayuno'=>0,'Comida'=>0];
                    $tipo = $row[$dia];
                    if (isset($resumen_por_dia[$dia][$tipo])) $resumen_por_dia[$dia][$tipo]++;
                    if ($tipo === 'Desayuno') $total_desayunos++;
                    elseif ($tipo === 'Comida') $total_comidas++;
                }
            }
        }
        sqlsrv_free_stmt($stmt);
    }

    sqlsrv_close($conn);
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

$total_pedidos = count($pedidos);

function fmt($fecha) {
    if (empty($fecha)) return '';
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d ? $d->format('d/m/Y') : $fecha;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Comedor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .page-header { background: linear-gradient(135deg, #2c3e50, #3498db); color: #fff; padding: 20px 30px; }
        .card-stat { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-icon { font-size: 2rem; }
        .tab-content { background: #fff; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; padding: 20px; }
        .nav-tabs .nav-link.active { font-weight: 600; }
        .badge-desayuno { background: #f39c12; }
        .badge-comida { background: #27ae60; }
        select.fecha-select { font-size: 1rem; border-radius: 8px; border: 1px solid #ced4da; padding: 8px 14px; }
    </style>
</head>
<body>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Pedidos del Comedor</h4>
        <small class="opacity-75">Sistema Comedor &mdash; BacroCorp</small>
    </div>
    <a href="Admiin.php" class="btn btn-outline-light btn-sm">← Regresar</a>
</div>

<div class="container-fluid py-4">

    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Selector de semana/fecha registrada -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-semibold">Seleccionar semana registrada</label>
                    <select name="fecha" class="fecha-select" onchange="this.form.submit()">
                        <option value="">-- Elige una semana --</option>
                        <?php foreach ($fechas_disponibles as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $f === $fecha_seleccionada ? 'selected' : '' ?>>
                                <?= fmt($f) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($fecha_seleccionada)): ?>
                <div class="col-auto align-self-end">
                    <span class="text-muted">Semana: <strong><?= fmt($fecha_seleccionada) ?></strong></span>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (!empty($fecha_seleccionada)): ?>

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">👤</span>
                    <div>
                        <div class="text-muted small">Pedidos registrados</div>
                        <div class="fs-2 fw-bold text-primary"><?= $total_pedidos ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">🍳</span>
                    <div>
                        <div class="text-muted small">Desayunos totales</div>
                        <div class="fs-2 fw-bold text-warning"><?= $total_desayunos ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">🍽️</span>
                    <div>
                        <div class="text-muted small">Comidas totales</div>
                        <div class="fs-2 fw-bold text-success"><?= $total_comidas ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="mainTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-detalle">Detalle de pedidos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-resumen">Resumen por día</a>
        </li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-detalle">
            <table id="tablaDetalle" class="table table-sm table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>ID Empleado</th>
                        <th>Usuario</th>
                        <th>Lunes</th>
                        <th>Martes</th>
                        <th>Miércoles</th>
                        <th>Jueves</th>
                        <th>Viernes</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($p['Id_Empleado']) ?></td>
                        <td><?= htmlspecialchars($p['Usuario']) ?></td>
                        <?php foreach (['Lunes','Martes','Miercoles','Jueves','Viernes'] as $dia): ?>
                        <td>
                            <?php if (!empty($p[$dia])): ?>
                                <?php if ($p[$dia] === 'Desayuno'): ?>
                                    <span class="badge badge-desayuno">Desayuno</span>
                                <?php else: ?>
                                    <span class="badge badge-comida">Comida</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td>$<?= number_format($p['Costo'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tab-pane fade" id="tab-resumen">
            <table id="tablaResumen" class="table table-sm table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>Día</th>
                        <th class="text-center">Desayunos</th>
                        <th class="text-center">Comidas</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['Lunes','Martes','Miercoles','Jueves','Viernes'] as $dia):
                        $d = $resumen_por_dia[$dia] ?? ['Desayuno'=>0,'Comida'=>0];
                        $total_dia = ($d['Desayuno'] ?? 0) + ($d['Comida'] ?? 0);
                    ?>
                    <tr>
                        <td><?= $dia === 'Miercoles' ? 'Miércoles' : $dia ?></td>
                        <td class="text-center"><span class="badge badge-desayuno"><?= $d['Desayuno'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge badge-comida"><?= $d['Comida'] ?? 0 ?></span></td>
                        <td class="text-center fw-bold"><?= $total_dia ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td>TOTAL</td>
                        <td class="text-center"><?= $total_desayunos ?></td>
                        <td class="text-center"><?= $total_comidas ?></td>
                        <td class="text-center"><?= $total_desayunos + $total_comidas ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <?php else: ?>
        <div class="alert alert-info">Selecciona una semana para ver los pedidos registrados.</div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
$(function () {
    const opts = {
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '📥 Excel', className: 'btn btn-success btn-sm' },
            { extend: 'print', text: '🖨️ Imprimir', className: 'btn btn-secondary btn-sm' }
        ]
    };
    $('#tablaDetalle').DataTable({ ...opts, pageLength: 25 });
    $('#tablaResumen').DataTable({ ...opts, paging: false, searching: false });
});
</script>
</body>
</html>
