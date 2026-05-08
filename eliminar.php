<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $_SESSION = array();
    session_destroy();
    header("Location: Admiin.php");
    exit;
}

$isAuthenticated = (
    isset($_SESSION['authenticated_from_login']) &&
    $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_id']) &&
    $_SESSION['session_id'] === session_id() &&
    isset($_SESSION['browser_fingerprint']) &&
    $_SESSION['browser_fingerprint'] === md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])
);

if (!$isAuthenticated) {
    session_unset();
    session_destroy();
    header("Location: Admiin.php");
    exit;
}

// Rango de fechas: por defecto últimos 7 días
if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin    = $_GET['fecha_fin'];
} else {
    $fecha_fin    = date('Y-m-d');
    $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
}

if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
    $fecha_inicio = $fecha_fin;
}

$registros       = [];
$resumen_persona = [];
$total_desayunos = 0;
$total_comidas   = 0;
$error_msg       = '';

try {
    $serverName = '192.168.100.95,1433';
    $connInfo   = [
        'Database'               => 'Comedor',
        'UID'                    => 'Larome03',
        'PWD'                    => 'Larome03',
        'CharacterSet'           => 'UTF-8',
        'Encrypt'                => false,
        'TrustServerCertificate' => true,
    ];
    $conn = sqlsrv_connect($serverName, $connInfo);

    if ($conn === false) {
        throw new Exception("No se pudo conectar a la base de datos: " . print_r(sqlsrv_errors(), true));
    }

    // Detalle de cada entrada en el periodo
    $sql_detalle = "
        SELECT
            nombre,
            convert(varchar, Hora_Entrada, 103) AS Fecha,
            convert(varchar, Hora_Entrada, 108) AS Hora,
            CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno' ELSE 'Comida' END AS TipoComida
        FROM Entradas
        WHERE nombre <> '.' AND nombre <> '' AND nombre NOT LIKE '[0-9]%'
          AND convert(date, Hora_Entrada, 103) BETWEEN ? AND ?
        ORDER BY Hora_Entrada DESC
    ";
    $stmt = sqlsrv_query($conn, $sql_detalle, [$fecha_inicio, $fecha_fin]);
    if ($stmt === false) {
        throw new Exception("Error en consulta de detalle: " . print_r(sqlsrv_errors(), true));
    }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $registros[] = $row;
        if ($row['TipoComida'] === 'Desayuno') {
            $total_desayunos++;
        } else {
            $total_comidas++;
        }
    }
    sqlsrv_free_stmt($stmt);

    // Resumen por persona
    $sql_resumen = "
        SELECT
            nombre,
            COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS Desayunos,
            COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS Comidas,
            COUNT(*) AS Total
        FROM Entradas
        WHERE nombre <> '.' AND nombre <> '' AND nombre NOT LIKE '[0-9]%'
          AND convert(date, Hora_Entrada, 103) BETWEEN ? AND ?
        GROUP BY nombre
        ORDER BY Total DESC
    ";
    $stmt2 = sqlsrv_query($conn, $sql_resumen, [$fecha_inicio, $fecha_fin]);
    if ($stmt2 === false) {
        throw new Exception("Error en consulta de resumen: " . print_r(sqlsrv_errors(), true));
    }
    while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        $resumen_persona[] = $row;
    }
    sqlsrv_free_stmt($stmt2);

    sqlsrv_close($conn);

} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

$total_registros = $total_desayunos + $total_comidas;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Comidas y Desayunos</title>
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
        .badge-comida   { background: #27ae60; }
    </style>
</head>
<body>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Registro de Comidas y Desayunos</h4>
        <small class="opacity-75">Sistema Comedor &mdash; BacroCorp</small>
    </div>
    <a href="Admiin.php" class="btn btn-outline-light btn-sm">← Regresar</a>
</div>

<div class="container-fluid py-4">

    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Filtro de fechas -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-semibold">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control"
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control"
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Consultar</button>
                </div>
                <div class="col-auto">
                    <a href="RegistroComidas.php" class="btn btn-outline-secondary">Últimos 7 días</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">🍳</span>
                    <div>
                        <div class="text-muted small">Desayunos servidos</div>
                        <div class="fs-2 fw-bold text-warning"><?= number_format($total_desayunos) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">🍽️</span>
                    <div>
                        <div class="text-muted small">Comidas servidas</div>
                        <div class="fs-2 fw-bold text-success"><?= number_format($total_comidas) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon">📋</span>
                    <div>
                        <div class="text-muted small">Total registros</div>
                        <div class="fs-2 fw-bold text-primary"><?= number_format($total_registros) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Detalle / Resumen por persona -->
    <ul class="nav nav-tabs" id="mainTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-detalle">Detalle de entradas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-resumen">Resumen por persona</a>
        </li>
    </ul>

    <div class="tab-content" id="mainTabContent">

        <!-- Tab detalle -->
        <div class="tab-pane fade show active" id="tab-detalle">
            <table id="tablaDetalle" class="table table-sm table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['Fecha']) ?></td>
                        <td><?= htmlspecialchars($r['Hora']) ?></td>
                        <td>
                            <?php if ($r['TipoComida'] === 'Desayuno'): ?>
                                <span class="badge badge-desayuno">Desayuno</span>
                            <?php else: ?>
                                <span class="badge badge-comida">Comida</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tab resumen por persona -->
        <div class="tab-pane fade" id="tab-resumen">
            <table id="tablaResumen" class="table table-sm table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Desayunos</th>
                        <th class="text-center">Comidas</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumen_persona as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-desayuno"><?= $p['Desayunos'] ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-comida"><?= $p['Comidas'] ?></span>
                        </td>
                        <td class="text-center fw-bold"><?= $p['Total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary fw-bold">
                        <td>TOTAL</td>
                        <td class="text-center"><?= $total_desayunos ?></td>
                        <td class="text-center"><?= $total_comidas ?></td>
                        <td class="text-center"><?= $total_registros ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div><!-- /tab-content -->
</div><!-- /container -->

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
    const commonOpts = {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '📥 Excel', className: 'btn btn-success btn-sm' },
            { extend: 'print',      text: '🖨️ Imprimir', className: 'btn btn-secondary btn-sm' }
        ]
    };

    $('#tablaDetalle').DataTable({
        ...commonOpts,
        pageLength: 25,
        order: [[2, 'desc'], [3, 'desc']]
    });

    $('#tablaResumen').DataTable({
        ...commonOpts,
        pageLength: 25,
        order: [[3, 'desc']]
    });
});
</script>
</body>
</html>
