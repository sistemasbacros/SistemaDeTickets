<?php
/**
 * @file RevisarTickets.php
 * @brief Vista de consulta y filtrado de tickets con reportes.
 *
 * @description
 * Módulo de visualización y búsqueda de tickets con capacidad de filtrado
 * avanzado por nombre, rango de fechas y otros criterios. Presenta una
 * tabla con todos los tickets que cumplen los filtros especificados.
 *
 * Este archivo no requiere autenticación (considerar agregar seguridad)
 * y permite consultar el historial completo de tickets con múltiples
 * columnas de información incluyendo timestamps de cada estado.
 *
 * Filtros disponibles:
 * - Nombre del solicitante (búsqueda parcial con LIKE)
 * - Fecha inicial (rango de búsqueda)
 * - Fecha final (rango de búsqueda)
 *
 * @module Módulo de Reportes y Consultas
 * @access Público (sin autenticación - ADVERTENCIA de seguridad)
 *
 * @dependencies
 * - PHP: sqlsrv extension
 * - JS CDN: DataTables, Bootstrap, jQuery
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: T3 (consulta con filtros)
 * - Columnas: Nombre, Correo, Prioridad, Empresa, Asunto, Mensaje,
 *             Adjuntos, Fecha, Hora, Id_Ticket, Estatus, PA,
 *             HoraFin, Tipo, Clasificacion, Tiempo_Ejec, FinT...
 *
 * @inputs
 * - GET['nombre']: Filtro por nombre (opcional)
 * - GET['fecha_inicial']: Fecha inicio del rango (opcional)
 * - GET['fecha_final']: Fecha fin del rango (opcional)
 *
 * @security
 * - ADVERTENCIA: Sin verificación de sesión
 * - Credenciales hardcoded (migrar a .env)
 * - Parámetros GET sanitizados con LIKE
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.5
 * @since 2024
 */

////////////////// Backend (PHP) - Conexión y Consulta con Filtros
require_once __DIR__ . '/config.php';
$serverName = $DB_HOST;
$connectionInfo = array("Database" => $DB_DATABASE, "UID" => $DB_USERNAME, "PWD" => $DB_PASSWORD, "CharacterSet" => "UTF-8", "TrustServerCertificate" => true, "Encrypt" => true);
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Obtener los filtros de la solicitud GET
$nombreFiltro = isset($_GET['nombre']) ? "%" . $_GET['nombre'] . "%" : "%";
$fechaInicioFiltro = isset($_GET['fecha_inicial']) ? $_GET['fecha_inicial'] : "1900-01-01";
$fechaFinFiltro = isset($_GET['fecha_final']) ? $_GET['fecha_final'] : "9999-12-31";

// Consulta SQL con filtros
$sql = "SELECT 
    [Nombre],
    [Correo],
    [Prioridad],
    [Empresa],
    [Asunto],
    [Mensaje],
    [Adjuntos],
    [Fecha] = LTRIM(RTRIM(CAST(CONVERT(DATE, fecha, 103) AS NVARCHAR))),
    [Hora],
    [Id_Ticket],
    [Estatus],
    [PA],
    [HoraFin],
    [Tipo],
    [Clasificacion],
    [Tiempo_Ejec],
    [FinT],
    [FeT],
    [HoraT] 
FROM [dbo].[T3] 
WHERE 
    (CONVERT(DATE, fecha, 103) BETWEEN ? AND ?) 
    AND (Nombre LIKE ?) 
ORDER BY CONVERT(DATE, fecha, 103)";

// Parámetros para la consulta
$params = array($fechaInicioFiltro, $fechaFinFiltro, $nombreFiltro);

// Ejecutar la consulta
$stmt = sqlsrv_query($conn, $sql, $params);

// Arreglos para almacenar los resultados
$array1 = [];
$array2 = [];
$array3 = [];
$array4 = [];
$array5 = [];
$array6 = [];
$array7 = [];
$array8 = [];
$array9 = [];
$array10 = [];
$array11 = [];
$array12 = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    array_push($array1, $row['Nombre']);
    array_push($array2, $row['Correo']);
    array_push($array3, $row['Prioridad']);
    array_push($array4, $row['Empresa']);
    array_push($array5, $row['Asunto']);
    array_push($array6, $row['Mensaje']);
    array_push($array7, $row['Adjuntos']);
    array_push($array8, $row['Fecha']);
    array_push($array9, $row['Hora']);
    array_push($array10, $row['Id_Ticket']);
    array_push($array11, $row['Estatus']);
    array_push($array12, $row['PA']);
}

// Liberar la consulta
sqlsrv_free_stmt($stmt);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Filtrar Tickets</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- DataTable CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css" rel="stylesheet" />

    <!-- FontAwesome (para iconos) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>

    <!-- DataTable JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- XLSX JS -->
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
            padding: 40px;
            color: #333;
        }

        .filter-container {
            margin-bottom: 30px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .card-header {
            background-color: #1E4E79;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .card-body {
            background-color: #fff;
            border-radius: 10px;
        }

        .form-control {
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            border: 1.5px solid #1E4E79;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #1E4E79;
            box-shadow: 0 0 6px rgba(30, 78, 121, 0.6);
        }

        .btn-primary {
            background-color: #1E4E79;
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #155a74;
        }

        /* Tabla */
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
        }

        table.dataTable thead th {
            background-color: #1E4E79 !important; /* Azul marino igual que controles */
            color: white !important; /* Letras blancas */
            font-weight: 600 !important;
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 12px 15px;
            border-bottom: 2px solid #155a74;
            text-align: center;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: 13px;
            font-weight: 500;
            padding: 10px 8px;
            text-align: center;
            background-color: #fdfdfd;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f0f0f0;
        }

        table.dataTable tbody tr:hover {
            background-color: #e2e2e2;
        }

        /* Scroll */
        div.dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
            overflow-x: auto;
        }

        /* Responsive ajustes */
        @media screen and (max-width: 768px) {
            .filter-container {
                padding-left: 0;
                padding-right: 0;
            }

            .card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <a href="M/website-menu-05/index.html" class="inicio-link">
        <button class="btn btn-light btn-sm"><i class="fas fa-home"></i> INICIO</button>
    </a>

    <!-- Formulario de Filtro con Card -->
    <div class="container">
        <div class="filter-container">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filtrar Tickets
                </div>
                <div class="card-body">
                    <form id="filtroForm" class="row g-3">
                        <div class="col">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Filtrar por nombre" />
                        </div>
                        <div class="col">
                            <label for="fecha_inicial" class="form-label">Fecha Inicial:</label>
                            <input type="date" id="fecha_inicial" name="fecha_inicial" class="form-control" />
                        </div>
                        <div class="col">
                            <label for="fecha_final" class="form-label">Fecha Final:</label>
                            <input type="date" id="fecha_final" name="fecha_final" class="form-control" />
                        </div>
                        <div class="col d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="container">
        <table id="example" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Prioridad</th>
                    <th>Empresa</th>
                    <th>Asunto</th>
                    <th>Mensaje</th>
                    <th>Adjunto</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>No Ticket</th>
                    <th>Estatus</th>
                    <th>Responsable</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script>
        var darray1 = <?php echo json_encode($array1); ?>;
        var darray2 = <?php echo json_encode($array2); ?>;
        var darray3 = <?php echo json_encode($array3); ?>;
        var darray4 = <?php echo json_encode($array4); ?>;
        var darray5 = <?php echo json_encode($array5); ?>;
        var darray6 = <?php echo json_encode($array6); ?>;
        var darray7 = <?php echo json_encode($array7); ?>;
        var darray8 = <?php echo json_encode($array8); ?>;
        var darray9 = <?php echo json_encode($array9); ?>;
        var darray10 = <?php echo json_encode($array10); ?>;
        var darray11 = <?php echo json_encode($array11); ?>;
        var darray12 = <?php echo json_encode($array12); ?>;

        // Inicializar la tabla de DataTable
        var table = $('#example').DataTable({
            data: [],
            columns: [
                { title: 'Nombre' },
                { title: 'Correo' },
                { title: 'Prioridad' },
                { title: 'Empresa' },
                { title: 'Asunto' },
                { title: 'Mensaje' },
                { title: 'Adjunto' },
                { title: 'Fecha' },
                { title: 'Hora' },
                { title: 'No Ticket' },
                { title: 'Estatus' },
                { title: 'Responsable' }
            ],
            scrollY: 750,
            scrollX: true,
            scrollCollapse: true,
            lengthMenu: [
                [100, -1],
                [100, 'Todos']
            ],
            language: {
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                search: "Buscar:",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                },
                loadingRecords: "Cargando...",
                processing: "Procesando..."
            }
        });

        // Filtrar los resultados
        $('#filtroForm').on('submit', function (e) {
            e.preventDefault();

            var nombre = $('#nombre').val();
            var fechaInicio = $('#fecha_inicial').val();
            var fechaFinal = $('#fecha_final').val();

            var dataSet = [];

            for (var i = 0; i < darray1.length; i++) {
                // Filtrar por nombre y fechas
                if (
                    (darray1[i].toLowerCase().includes(nombre.toLowerCase())) &&
                    (fechaInicio === "" || darray8[i] >= fechaInicio) &&
                    (fechaFinal === "" || darray8[i] <= fechaFinal)
                ) {
                    dataSet.push([
                        darray1[i],
                        darray2[i],
                        darray3[i],
                        darray4[i],
                        darray5[i],
                        darray6[i],
                        darray7[i],
                        darray8[i],
                        darray9[i],
                        darray10[i],
                        darray11[i],
                        darray12[i]
                    ]);
                }
            }

            table.clear().rows.add(dataSet).draw();
        });
    </script>
</body>

</html>
