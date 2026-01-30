<?php
////////////////// Backend (PHP) - Conexión y Consulta con Filtros
// INICIO DE SESIÓN Y VERIFICACIÓN
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirigir al login si no está autenticado
    header("Location: Loginti.php");
    exit();
}

// Obtener datos del usuario desde la sesión
$nombre_usuario = $_SESSION['user_name'] ?? 'Usuario';
$id_empleado = $_SESSION['user_id'] ?? 'N/A';
$area_usuario = $_SESSION['user_area'] ?? 'N/A';
$usuario = $_SESSION['user_username'] ?? 'N/A';

// Obtener los ULTIMOS DOS componentes como apellidos
$partes_nombre = explode(' ', trim($nombre_usuario));
$apellidos_usuario = '';
if (count($partes_nombre) >= 2) {
    // Tomar los ULTIMOS DOS elementos del array
    $apellidos_usuario = implode(' ', array_slice($partes_nombre, -2));
}

$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; // Server de la base de datos
$connectionInfo = array("Database" => "Ticket", "UID" => "Larome03", "PWD" => "Larome03", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Obtener los filtros de la solicitud GET
$nombreFiltro = isset($_GET['nombre']) ? "%" . $_GET['nombre'] . "%" : "%";
$fechaInicioFiltro = isset($_GET['fecha_inicial']) ? $_GET['fecha_inicial'] : "1900-01-01";
$fechaFinFiltro = isset($_GET['fecha_final']) ? $_GET['fecha_final'] : "9999-12-31";

// CONSULTA SQL ACTUALIZADA - Incluyendo campos de imagen
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
    [imagen_url],
    [imagen_nombre],
    [imagen_tipo],
    [imagen_size]
FROM [dbo].[T3] 
WHERE 
    (CONVERT(DATE, fecha, 103) BETWEEN ? AND ?) 
    AND (Nombre LIKE ? OR ? = '%')  -- Filtro por nombre si se especifica
    AND (Nombre LIKE ?)  -- FILTRO OBLIGATORIO por apellidos del usuario
ORDER BY CONVERT(DATE, fecha, 103)";

// Parámetros para la consulta
$params = array(
    $fechaInicioFiltro, 
    $fechaFinFiltro, 
    $nombreFiltro,
    $nombreFiltro,
    "%" . $apellidos_usuario . "%"  // FILTRO OBLIGATORIO por apellidos
);

// Ejecutar la consulta
$stmt = sqlsrv_query($conn, $sql, $params);

// Verificar si hay error en la consulta
if ($stmt === false) {
    die("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));
}

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
$array13 = []; // imagen_url
$array14 = []; // imagen_nombre
$array15 = []; // imagen_tipo
$array16 = []; // imagen_size

// URL base para las imágenes
$base_url = "http://desarollo-bacros/";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    array_push($array1, $row['Nombre']);
    array_push($array2, $row['Correo']);
    array_push($array3, $row['Prioridad']);
    array_push($array4, $row['Empresa']);
    array_push($array5, $row['Asunto']);
    array_push($array6, $row['Mensaje']);
    array_push($array7, $row['Adjuntos']);
    
    // Formatear fecha correctamente
    $fecha = $row['Fecha'];
    if ($fecha instanceof DateTime) {
        $fecha = $fecha->format('d/m/Y');
    } else {
        $fecha = (string)$fecha;
    }
    array_push($array8, $fecha);
    
    // Formatear hora
    $hora = $row['Hora'];
    if ($hora instanceof DateTime) {
        $hora = $hora->format('H:i:s');
    } else {
        $hora = (string)$hora;
    }
    array_push($array9, $hora);
    
    array_push($array10, $row['Id_Ticket']);
    array_push($array11, $row['Estatus']);
    array_push($array12, $row['PA']);
    
    // Campos de imagen - IMPORTANTE: Corregir la URL
    $imagen_url = $row['imagen_url'];
    
    // Si la URL contiene "../uploads/", reemplazarla con la URL correcta
    if (!empty($imagen_url) && strpos($imagen_url, '../uploads/') !== false) {
        // Extraer solo el nombre del archivo después de uploads/
        $nombre_archivo = basename($imagen_url);
        // Crear URL completa: http://desarollo-bacros/uploads/nombre_archivo
        $imagen_url = $base_url . 'uploads/' . $nombre_archivo;
    }
    
    array_push($array13, $imagen_url);
    array_push($array14, $row['imagen_nombre']);
    array_push($array15, $row['imagen_tipo']);
    array_push($array16, $row['imagen_size']);
}

// Contador de tickets encontrados
$total_tickets = count($array1);

// Liberar la consulta
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Función para formatear el tamaño del archivo
function formatFileSize($bytes) {
    if ($bytes == 0 || $bytes === NULL) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Filtrar Tickets - BacroCorp</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
        :root {
            --navy-primary: #0a192f;
            --navy-secondary: #112240;
            --navy-dark: #020c1b;
            --accent-blue: #64a8ff;
            --accent-teal: #64ffda;
            --accent-purple: #7c3aed;
            --snow-white: #f8fafc;
            --pearl-white: #f1f5f9;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.15);
            --glass-shadow: 0 8px 32px rgba(2, 12, 27, 0.4);
            --gradient-primary: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --gradient-accent: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            --gradient-blue: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            --font-primary: 'Outfit', sans-serif;
            --font-heading: 'Manrope', sans-serif;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-primary) 50%, var(--navy-secondary) 100%);
            min-height: 100vh;
            font-family: var(--font-primary);
            overflow-x: hidden;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .container {
            width: 100%;
            max-width: 1400px;
            margin-top: 10px;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo-glow {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: var(--gradient-blue);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 36px;
            position: relative;
            animation: logoPulse 3s ease-in-out infinite;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
        }
        
        @keyframes logoPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
            }
            50% {
                transform: scale(1.03);
                box-shadow: 0 0 40px rgba(59, 130, 246, 0.6);
            }
        }
        
        .system-title {
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 2.2rem;
            margin-bottom: 8px;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            text-align: center;
        }
        
        .system-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
            font-weight: 400;
            text-align: center;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #dbeafe;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 10px;
            backdrop-filter: blur(10px);
        }
        
        .btn-primary {
            background: var(--gradient-blue);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 14px;
            font-weight: 700;
            font-family: var(--font-heading);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1rem;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
            letter-spacing: 0.03em;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.07);
            border: 2px solid rgba(255, 255, 255, 0.12);
            color: white;
            border-radius: 14px;
            padding: 12px 18px;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-blue);
            color: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-label {
            font-weight: 600;
            font-family: var(--font-heading);
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: var(--accent-blue);
            font-size: 0.85rem;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            padding: 20px;
            box-shadow: var(--glass-shadow);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        table.dataTable {
            width: 100% !important;
            font-size: 13px;
            border-collapse: separate !important;
            border-spacing: 0;
        }
        
        /* ENCABEZADOS BLANCOS CON TEXTO AZUL - ESTILOS MÁS FUERTES */
        #ticketsTable thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background: white !important;
            background-color: white !important;
            color: #1e3a8a !important;
            font-weight: 700 !important;
            padding: 12px !important;
            white-space: nowrap !important;
            border-bottom: 2px solid #3b82f6 !important;
        }
        
        /* TEXTO BLANCO EN LA TABLA */
        #ticketsTable tbody td {
            color: white !important;
            background-color: transparent !important;
        }
        
        .dataTables_wrapper .dataTables_scrollBody {
            background: transparent !important;
        }
        
        table.dataTable tbody td {
            padding: 10px !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            font-weight: 500 !important;
            background-color: transparent !important;
        }
        
        table.dataTable tbody tr {
            background-color: transparent !important;
        }
        
        table.dataTable tbody tr td {
            color: white !important;
        }
        
        table.dataTable.table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: white !important;
        }
        
        table.dataTable.table-striped > tbody > tr:nth-of-type(even) > * {
            background-color: transparent !important;
            color: white !important;
        }
        
        table.dataTable tbody tr:hover td {
            background-color: rgba(59, 130, 246, 0.3) !important;
            color: white !important;
        }
        
        .priority-high {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .priority-medium {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .priority-low {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-open {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-closed {
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-in-progress {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white !important;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .ticket-counter {
            background: var(--gradient-accent);
            color: white;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
        }
        
        /* OVERRIDE COMPLETO DE DATATABLES BOOTSTRAP 5 */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background: rgba(255, 255, 255, 0.07) !important;
            border: 2px solid rgba(255, 255, 255, 0.12) !important;
            color: white !important;
        }
        
        /* ELIMINAR ESTILOS DE DATATABLES POR COMPLETO */
        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc,
        table.dataTable thead .sorting_asc_disabled,
        table.dataTable thead .sorting_desc_disabled {
            background-image: none !important;
            background-color: white !important;
            color: #1e3a8a !important;
        }
        
        /* Estilos para botones de imagen */
        .image-buttons {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
            margin-bottom: 4px;
        }
        
        .image-buttons .btn {
            padding: 4px 8px;
            font-size: 0.75rem;
            white-space: nowrap;
        }
        
        .image-info {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            word-break: break-word;
            max-width: 150px;
        }
        
        .btn-image-view {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border: none;
        }
        
        .btn-image-download {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
        }
        
        .image-preview-modal .modal-content {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .system-title {
                font-size: 1.8rem;
            }
            
            .system-subtitle {
                font-size: 0.9rem;
            }
            
            .logo-glow {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
            
            table.dataTable thead th, table.dataTable tbody td {
                white-space: normal;
                word-break: break-word;
            }
            
            .image-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    
    <!-- Header Section -->
    <div class="header-section">
        <div class="logo-glow">
            <i class="fas fa-filter"></i>
        </div>
        
        <h1 class="system-title">FILTRAR TICKETS</h1>
        <p class="system-subtitle">Sistema de Búsqueda y Filtrado Avanzado de Tickets</p>
        
        <div class="security-badge">
            <i class="fas fa-user-tag"></i>
            <strong>Filtro activo:</strong> Mostrando solo tickets que contienen "<strong><?php echo htmlspecialchars($apellidos_usuario); ?></strong>" juntos
            <?php if ($total_tickets > 0): ?>
                <span class="ticket-counter">
                    <i class="fas fa-ticket-alt"></i> <?php echo $total_tickets; ?> tickets
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenedor principal -->
    <div class="container">
        <!-- Información del Usuario Autenticado -->
        <div class="glass-card" style="padding: 20px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="security-badge" style="margin: 0; width: fit-content;">
                        <i class="fas fa-user-check"></i>
                        <strong>Usuario:</strong> <?php echo htmlspecialchars($nombre_usuario); ?> | 
                        <strong>Área:</strong> <?php echo htmlspecialchars($area_usuario); ?> | 
                        <strong>ID:</strong> <?php echo htmlspecialchars($id_empleado); ?>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="javascript:history.back()" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <!-- Formulario de Filtro -->
        <div class="glass-card" style="padding: 30px;">
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="section-title" style="font-family: var(--font-heading); font-weight: 700; font-size: 1.2rem; margin-bottom: 20px; color: var(--accent-blue); display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-filter"></i>
                        Filtros de Búsqueda Adicionales
                    </h3>
                </div>
            </div>
            
            <form id="filtroForm" class="row g-4">
                <div class="col-md-4">
                    <label for="nombre" class="form-label">
                        <i class="fas fa-search"></i>
                        Búsqueda específica
                    </label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Buscar dentro de sus tickets..." 
                           value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>" />
                </div>
                <div class="col-md-3">
                    <label for="fecha_inicial" class="form-label">
                        <i class="fas fa-calendar-day"></i>
                        Fecha Inicial
                    </label>
                    <input type="date" id="fecha_inicial" name="fecha_inicial" class="form-control" 
                           value="<?php echo isset($_GET['fecha_inicial']) ? htmlspecialchars($_GET['fecha_inicial']) : ''; ?>" />
                </div>
                <div class="col-md-3">
                    <label for="fecha_final" class="form-label">
                        <i class="fas fa-calendar-day"></i>
                        Fecha Final
                    </label>
                    <input type="date" id="fecha_final" name="fecha_final" class="form-control" 
                           value="<?php echo isset($_GET['fecha_final']) ? htmlspecialchars($_GET['fecha_final']) : ''; ?>" />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Resultados -->
        <div class="glass-card" style="padding: 30px;">
            <?php if ($total_tickets > 0): ?>
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Prioridad</th>
                            <th>Departamento</th>
                            <th>Asunto</th>
                            <th>Mensaje</th>
                            <th>Adjunto</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>No Ticket</th>
                            <th>Estatus</th>
                            <th>Responsable</th>
                            <!-- NUEVA COLUMNA -->
                            <th>Imagen Adjunta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($array1); $i++): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($array1[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array2[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array3[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array4[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array5[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array6[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array7[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array8[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array9[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array10[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array11[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array12[$i]); ?></td>
                            
                            <!-- NUEVA CELDA PARA IMAGEN -->
                            <td>
                                <?php 
                                $imagen_url = $array13[$i];
                                $imagen_nombre = $array14[$i];
                                $imagen_tipo = $array15[$i];
                                $imagen_size = $array16[$i];
                                
                                // Verificar si hay imagen
                                if (!empty($imagen_url) && $imagen_url !== 'NULL' && $imagen_url !== null && !empty($imagen_nombre)): 
                                    // La URL ya está formateada correctamente como: http://desarollo-bacros/uploads/ticket_1768416635_6967e57bbfcb2.jpg
                                ?>
                                    <div class="image-buttons">
                                        <!-- Botón para ver la imagen en modal -->
                                        <button type="button" 
                                                class="btn btn-sm btn-image-view view-image-btn"
                                                data-image-url="<?php echo htmlspecialchars($imagen_url); ?>"
                                                data-image-name="<?php echo htmlspecialchars($imagen_nombre); ?>"
                                                title="Ver imagen">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        
                                        <!-- Botón para descargar la imagen -->
                                        <a href="<?php echo htmlspecialchars($imagen_url); ?>" 
                                           download="<?php echo htmlspecialchars($imagen_nombre); ?>" 
                                           class="btn btn-sm btn-image-download"
                                           title="Descargar imagen">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="image-info">
                                        <div><strong><?php echo htmlspecialchars(substr($imagen_nombre, 0, 25)); ?><?php echo strlen($imagen_nombre) > 25 ? '...' : ''; ?></strong></div>
                                        <div><?php echo formatFileSize($imagen_size); ?></div>
                                        <div style="color: #64ffda; font-size: 0.7rem;">
                                            <?php echo strtoupper(pathinfo($imagen_nombre, PATHINFO_EXTENSION)); ?> | 
                                            Ticket: <?php echo htmlspecialchars($array10[$i]); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin imagen</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <div style="font-size: 80px; margin-bottom: 20px; color: rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 style="color: rgba(255, 255, 255, 0.7); margin-bottom: 15px;">No se encontraron tickets</h3>
                <p style="color: rgba(255, 255, 255, 0.5); max-width: 500px; margin: 0 auto;">
                    No hay tickets registrados que contengan los apellidos "<strong><?php echo htmlspecialchars($apellidos_usuario); ?></strong>" JUNTOS.
                </p>
                <a href="javascript:history.back()" class="btn btn-primary mt-4">
                    <i class="fas fa-arrow-left me-2"></i> Volver al sistema
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para vista previa de imagen -->
    <div class="modal fade image-preview-modal" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Vista Previa de Imagen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImagePreview" src="" alt="Vista previa" class="img-fluid rounded" style="max-height: 70vh;">
                    <div class="mt-3" id="imageInfo">
                        <!-- La información de la imagen se carga aquí dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalDownloadLink" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Descargar
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>

    <script>
        $(document).ready(function() {
            // Solo inicializar DataTable si hay datos
            <?php if ($total_tickets > 0): ?>
            
            // Registrar el plugin para ordenar fechas con Moment.js
            $.fn.dataTable.moment('DD/MM/YYYY');

            // Inicializar la tabla de DataTable
            var table = $('#ticketsTable').DataTable({
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
                },
                order: [],
                columnDefs: [{
                    orderable: false,
                    targets: '_all'
                }],
                drawCallback: function(settings) {
                    // Forzar texto blanco en todas las celdas
                    $('#ticketsTable tbody td').css('color', 'white');
                    // Forzar encabezados blancos con texto azul
                    $('#ticketsTable thead th').css({
                        'color': '#1e3a8a',
                        'background': 'white',
                        'background-color': 'white'
                    });
                    
                    // Reasignar eventos a los botones después de cada redibujado de DataTable
                    $('.view-image-btn').off('click').on('click', function() {
                        var imageUrl = $(this).data('image-url');
                        var imageName = $(this).data('image-name');
                        
                        // Mostrar la imagen en el modal
                        $('#modalImagePreview').attr('src', imageUrl);
                        $('#imageModalTitle').text('Vista Previa: ' + imageName);
                        
                        // Configurar enlace de descarga
                        $('#modalDownloadLink').attr('href', imageUrl);
                        $('#modalDownloadLink').attr('download', imageName);
                        
                        // Mostrar información adicional
                        var imageInfo = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre:</strong> ${imageName}</p>
                                    <p><strong>URL:</strong> <small>${imageUrl}</small></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p><strong>Ticket:</strong> ${$(this).closest('tr').find('td:eq(9)').text()}</p>
                                    <p><strong>Usuario:</strong> ${$(this).closest('tr').find('td:eq(0)').text()}</p>
                                </div>
                            </div>
                        `;
                        $('#imageInfo').html(imageInfo);
                        
                        // Mostrar el modal
                        var imageModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                        imageModal.show();
                    });
                },
                initComplete: function() {
                    // Aplicar estilos después de inicializar
                    setTimeout(function() {
                        $('#ticketsTable thead th').css({
                            'color': '#1e3a8a',
                            'background': 'white',
                            'background-color': 'white'
                        });
                    }, 100);
                },
                columnDefs: [
                    {
                        targets: [2], // Columna Prioridad
                        render: function(data, type, row) {
                            if (type === 'display') {
                                var priority = data.toLowerCase();
                                var className = 'priority-medium';
                                
                                if (priority.includes('alta') || priority.includes('high')) {
                                    className = 'priority-high';
                                } else if (priority.includes('baja') || priority.includes('low')) {
                                    className = 'priority-low';
                                }
                                
                                return '<span class="' + className + '">' + data + '</span>';
                            }
                            return data;
                        }
                    },
                    {
                        targets: [10], // Columna Estatus
                        render: function(data, type, row) {
                            if (type === 'display') {
                                var status = data.toLowerCase();
                                var className = 'status-open';
                                
                                if (status.includes('cerrado') || status.includes('closed')) {
                                    className = 'status-closed';
                                } else if (status.includes('proceso') || status.includes('progress')) {
                                    className = 'status-in-progress';
                                }
                                
                                return '<span class="' + className + '">' + data + '</span>';
                            }
                            return data;
                        }
                    },
                    {
                        targets: [9], // Columna No Ticket
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return '<span class="badge bg-dark">#' + data + '</span>';
                            }
                            return data;
                        }
                    },
                    {
                        targets: [6], // Columna Adjunto
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (data && data.trim() !== '' && data.trim() !== 'N/A' && data.trim() !== 'NULL') {
                                    return '<a href="' + data + '" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-paperclip"></i> Ver</a>';
                                }
                                return '<span style="color: rgba(255, 255, 255, 0.7);">N/A</span>';
                            }
                            return data;
                        }
                    },
                    {
                        targets: [12], // Columna Imagen Adjunta
                        width: '200px',
                        render: function(data, type, row) {
                            return data;
                        }
                    }
                ]
            });

            // Forzar estilos después de inicializar DataTable
            setTimeout(function() {
                // Forzar encabezados blancos
                $('#ticketsTable thead th').css({
                    'color': '#1e3a8a',
                    'background': 'white',
                    'background-color': 'white',
                    'font-weight': '700',
                    'border-bottom': '2px solid #3b82f6'
                });
                
                // Forzar texto blanco en celdas
                $('#ticketsTable tbody td').css('color', 'white');
                $('#ticketsTable tbody tr').css('background-color', 'transparent');
            }, 200);

            // Evento para botones de ver imagen
            $(document).on('click', '.view-image-btn', function() {
                var imageUrl = $(this).data('image-url');
                var imageName = $(this).data('image-name');
                
                // Mostrar la imagen en el modal
                $('#modalImagePreview').attr('src', imageUrl);
                $('#imageModalTitle').text('Vista Previa: ' + imageName);
                
                // Configurar enlace de descarga
                $('#modalDownloadLink').attr('href', imageUrl);
                $('#modalDownloadLink').attr('download', imageName);
                
                // Mostrar información adicional
                var imageInfo = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> ${imageName}</p>
                            <p><strong>URL:</strong> <small>${imageUrl}</small></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>Ticket:</strong> ${$(this).closest('tr').find('td:eq(9)').text()}</p>
                            <p><strong>Usuario:</strong> ${$(this).closest('tr').find('td:eq(0)').text()}</p>
                        </div>
                    </div>
                `;
                $('#imageInfo').html(imageInfo);
                
                // Mostrar el modal
                var imageModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                imageModal.show();
            });

            // Filtrar los resultados (recargar la página con nuevos parámetros)
            $('#filtroForm').on('submit', function (e) {
                e.preventDefault();
                
                var nombre = $('#nombre').val();
                var fechaInicio = $('#fecha_inicial').val();
                var fechaFinal = $('#fecha_final').val();
                
                // Construir URL con parámetros
                var url = window.location.pathname;
                var params = [];
                
                if (nombre) params.push('nombre=' + encodeURIComponent(nombre));
                if (fechaInicio) params.push('fecha_inicial=' + fechaInicio);
                if (fechaFinal) params.push('fecha_final=' + fechaFinal);
                
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                
                // Redirigir a la misma página con los nuevos filtros
                window.location.href = url;
            });

            // Configurar fechas por defecto (últimos 30 días)
            var today = new Date();
            var lastMonth = new Date();
            lastMonth.setDate(today.getDate() - 30);
            
            var todayFormatted = today.toISOString().split('T')[0];
            var lastMonthFormatted = lastMonth.toISOString().split('T')[0];
            
            // Solo establecer si no hay valores
            if (!$('#fecha_inicial').val()) {
                $('#fecha_inicial').val(lastMonthFormatted);
            }
            if (!$('#fecha_final').val()) {
                $('#fecha_final').val(todayFormatted);
            }
            
            <?php endif; ?>
        });
    </script>
</body>
</html>