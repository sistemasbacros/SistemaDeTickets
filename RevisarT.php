<?php
/**
 * @file RevisarT.php
 * @brief Vista segura de revisión de tickets con filtrado por usuario.
 *
 * @description
 * Módulo de visualización de tickets con autenticación requerida.
 * Implementa filtrado inteligente basado en el usuario autenticado,
 * mostrando solo los tickets relevantes según permisos y área.
 *
 * Incluye función de extracción inteligente de apellidos para
 * correlacionar tickets con usuarios del sistema.
 *
 * Características:
 * - Verificación de sesión obligatoria
 * - Extracción inteligente de apellidos del nombre completo
 * - Filtrado de tickets por usuario/área
 * - Interfaz con DataTables y diseño glassmorphism
 *
 * Algoritmo de extracción de apellidos:
 * - 4+ partes: Primeras 2 como apellidos (ROMERO LOPEZ LUIS ANTONIO → ROMERO LOPEZ)
 * - 3 partes: Primera como apellido
 * - 2 partes: Primera como apellido
 *
 * @module Módulo de Consulta de Tickets
 * @access Privado (requiere sesión activa desde Loginti.php)
 *
 * @dependencies
 * - PHP: session, sqlsrv extension
 * - JS CDN: DataTables, Bootstrap, SweetAlert2
 * - Interno: Loginti.php (autenticación)
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: T3 (consulta filtrada)
 *
 * @session
 * - $_SESSION['logged_in']: Verificación (debe ser true)
 * - $_SESSION['user_name']: Nombre para filtrado
 * - $_SESSION['user_id']: ID del empleado
 * - $_SESSION['user_area']: Área del usuario
 * - $_SESSION['user_username']: Username
 *
 * @functions
 * - extraerApellidos($nombre_completo): Extrae apellidos de nombre completo
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */

////////////////// Backend (PHP) - Conexión y Consulta con Filtros
// INICIO DE SESIÓN Y VERIFICACIÓN
<?php
////////////////// Backend (PHP) - Conexión y Consulta con Filtros
// INICIO DE SESIÓN Y VERIFICACIÓN
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Loginti.php");
    exit();
}

// Obtener datos del usuario desde la sesión
$nombre_usuario = $_SESSION['user_name'] ?? 'Usuario';
$id_empleado = $_SESSION['user_id'] ?? 'N/A';
$area_usuario = $_SESSION['user_area'] ?? 'N/A';
$usuario = $_SESSION['user_username'] ?? 'N/A';

// ===== FUNCIÓN PARA EXTRAER APELLIDOS DE FORMA INTELIGENTE =====
function extraerApellidos($nombre_completo) {
    $nombre_completo = trim($nombre_completo);
    $partes = explode(' ', $nombre_completo);
    $num_partes = count($partes);
    
    if ($num_partes >= 4) return implode(' ', array_slice($partes, 0, 2));
    if ($num_partes == 3) return implode(' ', array_slice($partes, 0, 2));
    if ($num_partes == 2) return $partes[0];
    return $nombre_completo;
}

$apellidos_usuario = extraerApellidos($nombre_usuario);
$comodines_busqueda = array_unique([$apellidos_usuario, $nombre_usuario]);

require_once __DIR__ . '/config.php';
$connectionInfo = array("Database" => $DB_DATABASE, "UID" => $DB_USERNAME, "PWD" => $DB_PASSWORD, "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($DB_SERVER, $connectionInfo);

if (!$conn) die("Error de conexión: " . print_r(sqlsrv_errors(), true));

$nombreFiltro = isset($_GET['nombre']) ? "%" . $_GET['nombre'] . "%" : "%";
$fechaInicioFiltro = isset($_GET['fecha_inicial']) ? $_GET['fecha_inicial'] : "1900-01-01";
$fechaFinFiltro = isset($_GET['fecha_final']) ? $_GET['fecha_final'] : "9999-12-31";

$sql = "SELECT 
    [Nombre], [Correo], [Prioridad], [Empresa], [Asunto], [Mensaje], [Adjuntos],
    [Fecha] = LTRIM(RTRIM(CAST(CONVERT(DATE, fecha, 103) AS NVARCHAR))),
    [Hora], [Id_Ticket], [Estatus], [PA], [imagen_url], [imagen_nombre], [imagen_tipo], [imagen_size]
FROM [dbo].[T3] 
WHERE 
    (CONVERT(DATE, fecha, 103) BETWEEN ? AND ?) 
    AND (Nombre LIKE ? OR ? = '%')
    AND ((Nombre LIKE ?) OR (Nombre LIKE ?))
ORDER BY CONVERT(DATE, fecha, 103) DESC";

$params = array($fechaInicioFiltro, $fechaFinFiltro, $nombreFiltro, $nombreFiltro);
foreach ($comodines_busqueda as $comodin) $params[] = "%" . $comodin . "%";

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) die("Error en la consulta SQL: " . print_r(sqlsrv_errors(), true));

$array1 = []; $array2 = []; $array3 = []; $array4 = []; $array5 = []; $array6 = []; $array7 = [];
$array8 = []; $array9 = []; $array10 = []; $array11 = []; $array12 = [];
$array13 = []; $array14 = []; $array15 = []; $array16 = [];

$base_url = BASE_URL . '/';

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    array_push($array1, $row['Nombre']);
    array_push($array2, $row['Correo']);
    array_push($array3, $row['Prioridad']);
    array_push($array4, $row['Empresa']);
    array_push($array5, $row['Asunto']);
    array_push($array6, $row['Mensaje']);
    array_push($array7, $row['Adjuntos']);
    
    $fecha = $row['Fecha'];
    if ($fecha instanceof DateTime) $fecha = $fecha->format('d/m/Y');
    else $fecha = (string)$fecha;
    array_push($array8, $fecha);
    
    $hora = $row['Hora'];
    if ($hora instanceof DateTime) $hora = $hora->format('H:i:s');
    else $hora = (string)$hora;
    array_push($array9, $hora);
    
    array_push($array10, $row['Id_Ticket']);
    array_push($array11, $row['Estatus']);
    array_push($array12, $row['PA']);
    
    $imagen_url = $row['imagen_url'];
    if (!empty($imagen_url) && strpos($imagen_url, '../uploads/') !== false) {
        $nombre_archivo = basename($imagen_url);
        $imagen_url = $base_url . 'uploads/' . $nombre_archivo;
    }
    
    array_push($array13, $imagen_url);
    array_push($array14, $row['imagen_nombre']);
    array_push($array15, $row['imagen_tipo']);
    array_push($array16, $row['imagen_size']);
}

$total_tickets = count($array1);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes" />
    <title>Mis Tickets - BacroCorp</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Moderna pero Profesional -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />

    <style>
        :root {
            --navy-primary: #0a192f;
            --navy-secondary: #112240;
            --navy-dark: #020c1b;
            --accent-blue: #64a8ff;
            --accent-blue-deep: #3b82f6;
            --accent-blue-light: #93c5fd;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.15);
            --glass-shadow: 0 8px 32px rgba(2, 12, 27, 0.4);
            --gradient-blue: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --gradient-blue-soft: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            --gradient-success: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --gradient-warning: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            --gradient-danger: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            --font-primary: 'Outfit', sans-serif;
            --font-heading: 'Inter', sans-serif;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-primary) 50%, var(--navy-secondary) 100%);
            font-family: var(--font-primary);
            color: white;
            min-height: 100vh;
            padding: 15px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Tipografía Premium - Moderna y Profesional */
        h1, h2, h3, h4, h5, h6, .table thead th, .badge-counter, .btn-filter, .ticket-id {
            font-family: var(--font-heading);
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        body, p, span, div, input, select, textarea, .user-detail, .pattern-tag, .stat-badge {
            font-family: var(--font-primary);
            font-weight: 400;
            letter-spacing: -0.01em;
        }
        
        /* ===== HEADER SUPER COMPACTO ===== */
        .header-compact {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: var(--glass-shadow);
            border-left: 4px solid var(--accent-blue);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-mini {
            width: 48px;
            height: 48px;
            background: var(--gradient-blue);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 6px 15px rgba(59,130,246,0.3);
        }
        
        .title-mini h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, white, var(--accent-blue-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }
        
        .title-mini p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
            margin: 2px 0 0;
        }
        
        .header-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .badge-light {
            background: rgba(59,130,246,0.15);
            border: 1px solid rgba(59,130,246,0.3);
            color: var(--accent-blue);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .badge-counter {
            background: var(--gradient-blue);
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(59,130,246,0.3);
        }
        
        /* ===== USER INFO ===== */
        .user-info-compact {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 15px 25px;
            margin-bottom: 15px;
            box-shadow: var(--glass-shadow);
            border-left: 4px solid var(--accent-blue-light);
        }
        
        .user-details {
            display: flex;
            align-items: center;
            gap: 35px;
            flex-wrap: wrap;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: var(--gradient-blue);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.4rem;
            flex-shrink: 0;
            box-shadow: 0 6px 15px rgba(59,130,246,0.3);
        }
        
        .user-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .user-detail i {
            color: var(--accent-blue);
            width: 20px;
            font-size: 1.1rem;
        }
        
        .user-detail .label {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
        }
        
        .user-detail .value {
            font-family: var(--font-heading);
            font-weight: 600;
            color: white;
            white-space: normal;
            word-break: break-word;
            font-size: 1rem;
        }
        
        /* ===== SEARCH INFO ===== */
        .search-compact {
            background: rgba(59,130,246,0.08);
            border-left: 4px solid var(--accent-blue);
            border-radius: 16px;
            padding: 12px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: var(--glass-shadow);
        }
        
        .search-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent-blue);
            white-space: nowrap;
        }
        
        .pattern-tag {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }
        
        .pattern-tag:hover {
            background: rgba(59,130,246,0.2);
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }
        
        .pattern-main {
            background: var(--gradient-blue-soft);
            border: none;
            color: white;
            font-weight: 600;
        }
        
        /* ===== FILTROS COMPACTOS ===== */
        .filters-compact {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            box-shadow: var(--glass-shadow);
            border-left: 4px solid var(--accent-blue);
        }
        
        .filters-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--accent-blue);
        }
        
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.5px;
        }
        
        .form-label i {
            color: var(--accent-blue);
        }
        
        .form-control {
            background: rgba(0,0,0,0.3);
            border: 2px solid rgba(255,255,255,0.1);
            color: white;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 0.9rem;
            width: 100%;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--accent-blue);
            outline: none;
            background: rgba(0,0,0,0.4);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .btn-filter {
            background: var(--gradient-blue);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59,130,246,0.4);
        }
        
        /* ===== TABLA PREMIUM ===== */
        .table-wrapper {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 20px 25px;
            box-shadow: var(--glass-shadow);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-title {
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-title i {
            color: var(--accent-blue);
        }
        
        .table-stats {
            display: flex;
            gap: 15px;
        }
        
        .stat-badge {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
        }
        
        /* ENCABEZADOS AZULES */
        #ticketsTable thead th {
            background: var(--gradient-blue) !important;
            color: white !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 10px !important;
            border: none !important;
            white-space: nowrap;
            font-family: var(--font-heading) !important;
        }
        
        #ticketsTable thead th:first-child {
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }
        
        #ticketsTable thead th:last-child {
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }
        
        #ticketsTable thead th:not(:last-child) {
            border-right: 2px solid rgba(255,255,255,0.2) !important;
        }
        
        /* CUERPO */
        #ticketsTable tbody tr {
            background: rgba(10,25,47,0.7);
            transition: var(--transition);
        }
        
        #ticketsTable tbody tr:nth-child(even) {
            background: rgba(17,34,64,0.7);
        }
        
        #ticketsTable tbody tr:hover {
            background: rgba(59,130,246,0.2);
            cursor: pointer;
        }
        
        #ticketsTable tbody td {
            padding: 12px 10px !important;
            color: white !important;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            border-right: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            font-weight: 400;
        }
        
        #ticketsTable tbody td:last-child {
            border-right: none;
        }
        
        /* BADGES PREMIUM */
        .priority-high {
            background: var(--gradient-danger);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            font-weight: 600;
        }
        
        .priority-medium {
            background: var(--gradient-warning);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            font-weight: 600;
        }
        
        .priority-low {
            background: var(--gradient-success);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            font-weight: 600;
        }
        
        .status-open {
            background: var(--gradient-blue);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 75px;
            text-align: center;
            font-weight: 600;
        }
        
        .status-closed {
            background: linear-gradient(135deg, #475569, #6b7280);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 75px;
            text-align: center;
            font-weight: 600;
        }
        
        .status-progress {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 75px;
            text-align: center;
            font-weight: 600;
        }
        
        .status-atendido {
            background: var(--gradient-blue-soft);
            color: white;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            display: inline-block;
            min-width: 75px;
            text-align: center;
            font-weight: 600;
        }
        
        .ticket-id {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--accent-blue);
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        /* IMAGEN */
        .image-buttons {
            display: flex;
            gap: 4px;
        }
        
        .btn-image {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
            border: none;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(0,0,0,0.3);
            transition: var(--transition);
        }
        
        .btn-image:hover {
            background: rgba(0,0,0,0.5);
            transform: translateY(-1px);
        }
        
        .btn-view { background: var(--gradient-blue); }
        .btn-download { background: var(--gradient-blue-soft); }
        
        .image-info {
            font-size: 0.65rem;
            color: rgba(255,255,255,0.6);
            margin-top: 2px;
        }
        
        /* DATATABLES */
        .dataTables_wrapper {
            padding: 0;
        }
        
        .dataTables_length,
        .dataTables_filter {
            margin-bottom: 15px !important;
        }
        
        .dataTables_length label,
        .dataTables_filter label {
            color: white !important;
            font-size: 0.85rem !important;
        }
        
        .dataTables_length select,
        .dataTables_filter input {
            background: rgba(0,0,0,0.3) !important;
            border: 2px solid rgba(255,255,255,0.1) !important;
            color: white !important;
            border-radius: 8px !important;
            padding: 5px 10px !important;
            font-size: 0.85rem !important;
            margin-left: 5px;
        }
        
        .dataTables_filter input {
            width: 200px !important;
        }
        
        .dataTables_paginate {
            margin-top: 15px !important;
            text-align: center;
        }
        
        .dataTables_paginate .paginate_button {
            padding: 6px 14px !important;
            margin: 0 3px !important;
            border-radius: 8px !important;
            background: rgba(255,255,255,0.05) !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            font-size: 0.8rem !important;
            transition: var(--transition) !important;
            cursor: pointer;
            display: inline-block;
        }
        
        .dataTables_paginate .paginate_button:hover {
            background: rgba(59,130,246,0.2) !important;
            border-color: var(--accent-blue) !important;
            transform: translateY(-2px);
        }
        
        .dataTables_paginate .paginate_button.current {
            background: var(--gradient-blue) !important;
            border: none !important;
        }
        
        .dataTables_info {
            color: rgba(255,255,255,0.6) !important;
            font-size: 0.85rem !important;
            margin-top: 10px !important;
        }
        
        /* MODAL */
        .modal-content {
            background: var(--navy-secondary);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            color: white;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-close-white {
            filter: invert(1);
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            .user-detail { font-size: 0.95rem; }
        }
        
        @media (max-width: 992px) {
            .header-compact { flex-direction: column; align-items: flex-start; }
            .header-badges { width: 100%; }
            .badge-light, .badge-counter { flex: 1; justify-content: center; }
            
            .user-details { gap: 20px; }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            
            .header-left { width: 100%; }
            .title-mini h1 { font-size: 1.3rem; }
            
            .user-info-compact { padding: 15px; }
            .user-details { 
                flex-direction: column; 
                align-items: flex-start;
                gap: 12px;
                width: 100%;
            }
            
            .user-detail { 
                width: 100%;
                padding: 6px 0;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            .user-detail:last-child { border-bottom: none; }
            
            #ticketsTable td:nth-child(12) {
                min-width: 80px;
                white-space: normal;
                word-break: break-word;
            }
            
            #ticketsTable td:nth-child(1) {
                min-width: 120px;
                white-space: normal;
                word-break: break-word;
            }
            
            .search-compact { 
                flex-direction: column; 
                align-items: flex-start;
            }
            .search-label { margin-bottom: 5px; }
            
            .pattern-tag { 
                margin: 2px;
                font-size: 0.75rem;
            }
            
            .filters-compact { padding: 15px; }
            .filters-compact .row { flex-direction: column; }
            .filters-compact [class*="col-"] { width: 100%; margin-bottom: 10px; }
            
            .table-wrapper { padding: 15px; overflow-x: auto; }
            #ticketsTable { min-width: 1000px; }
            
            .table-header { flex-direction: column; align-items: flex-start; }
            .table-stats { width: 100%; justify-content: space-between; }
            
            .dataTables_length,
            .dataTables_filter {
                text-align: left !important;
                width: 100% !important;
            }
            
            .dataTables_length select {
                width: 80px !important;
                margin-left: 5px;
            }
            
            .dataTables_filter input {
                width: 100% !important;
                margin-top: 5px !important;
                margin-left: 0 !important;
            }
            
            .dataTables_paginate {
                white-space: nowrap;
                overflow-x: auto;
                padding-bottom: 5px;
            }
            
            .dataTables_paginate .paginate_button {
                padding: 4px 10px !important;
                margin: 0 2px !important;
            }
            
            .image-buttons { 
                flex-direction: row;
                justify-content: center;
            }
            .btn-image { 
                padding: 4px 10px;
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 576px) {
            .badge-light, .badge-counter { 
                width: 100%; 
                justify-content: center;
            }
            
            .user-detail {
                font-size: 0.9rem;
            }
            
            .pattern-tag { 
                width: 100%; 
                text-align: center;
                justify-content: center;
            }
            
            .modal-dialog { margin: 10px; }
            
            .dataTables_paginate .paginate_button {
                padding: 3px 8px !important;
                font-size: 0.7rem !important;
            }
        }
        
        /* Utilidades */
        .text-accent { color: var(--accent-blue); }
    </style>
</head>

<body>
    <div class="container">
        <!-- HEADER SUPER COMPACTO -->
        <div class="header-compact">
            <div class="header-left">
                <div class="logo-mini">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="title-mini">
                    <h1>MIS TICKETS</h1>
                    <p>Seguimiento de solicitudes</p>
                </div>
            </div>
            <div class="header-badges">
                <span class="badge-light"><i class="fas fa-shield-alt"></i> Panel</span>
                <span class="badge-light"><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i'); ?></span>
                <?php if ($total_tickets > 0): ?>
                <span class="badge-counter"><i class="fas fa-ticket-alt"></i> <?php echo $total_tickets; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- USER INFO -->
        <div class="user-info-compact">
            <div class="user-details">
                <div class="user-avatar"><?php echo substr($nombre_usuario, 0, 2); ?></div>
                <div class="user-detail">
                    <i class="fas fa-user-check"></i>
                    <span class="label">Usuario:</span>
                    <span class="value"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                </div>
                <div class="user-detail">
                    <i class="fas fa-building"></i>
                    <span class="label">Área:</span>
                    <span class="value"><?php echo htmlspecialchars($area_usuario); ?></span>
                </div>
                <div class="user-detail">
                    <i class="fas fa-id-badge"></i>
                    <span class="label">ID:</span>
                    <span class="value"><?php echo htmlspecialchars($id_empleado); ?></span>
                </div>
            </div>
        </div>

        <!-- SEARCH INFO -->
        <div class="search-compact">
            <div class="search-label"><i class="fas fa-search"></i> Búsqueda activa:</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                <?php foreach ($comodines_busqueda as $index => $patron): ?>
                <span class="pattern-tag <?php echo $index == 0 ? 'pattern-main' : ''; ?>">
                    <i class="fas fa-<?php echo $index == 0 ? 'users' : 'user'; ?>"></i> <?php echo htmlspecialchars($patron); ?>
                </span>
                <?php endforeach; ?>
                <span class="pattern-tag"><i class="fas fa-check-circle" style="color:var(--accent-blue);"></i> Apellidos: <?php echo htmlspecialchars($apellidos_usuario); ?></span>
            </div>
        </div>

        <!-- FILTROS COMPACTOS -->
        <div class="filters-compact">
            <div class="filters-title"><i class="fas fa-sliders-h"></i> Filtros adicionales</div>
            <form id="filtroForm" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-search"></i> BUSCAR</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Asunto, mensaje..." value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar-day"></i> DESDE</label>
                    <input type="date" id="fecha_inicial" name="fecha_inicial" class="form-control" value="<?php echo isset($_GET['fecha_inicial']) ? htmlspecialchars($_GET['fecha_inicial']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar-day"></i> HASTA</label>
                    <input type="date" id="fecha_final" name="fecha_final" class="form-control" value="<?php echo isset($_GET['fecha_final']) ? htmlspecialchars($_GET['fecha_final']) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrar</button>
                </div>
            </form>
        </div>

        <!-- TABLA PREMIUM -->
        <div class="table-wrapper">
            <div class="table-header">
                <div class="table-title"><i class="fas fa-crown"></i> Mis Tickets</div>
                <div class="table-stats">
                    <span class="stat-badge"><i class="fas fa-database"></i> <?php echo $total_tickets; ?> registros</span>
                    <span class="stat-badge"><i class="fas fa-sync-alt"></i> Tiempo real</span>
                </div>
            </div>

            <?php if ($total_tickets > 0): ?>
            <div style="overflow-x: auto;">
                <table id="ticketsTable" class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>NOMBRE</th>
                            <th>CORREO</th>
                            <th>PRIORIDAD</th>
                            <th>DEPTO</th>
                            <th>ASUNTO</th>
                            <th>MENSAJE</th>
                            <th>ADJUNTO</th>
                            <th>FECHA</th>
                            <th>HORA</th>
                            <th>No.</th>
                            <th>ESTATUS</th>
                            <th>RESPONSABLE</th>
                            <th>IMAGEN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($array1); $i++): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($array1[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array2[$i]); ?></td>
                            <td>
                                <?php 
                                $p = strtolower($array3[$i] ?? '');
                                $clase = 'priority-medium';
                                if (strpos($p, 'alt') !== false || strpos($p, 'urg') !== false) $clase = 'priority-high';
                                elseif (strpos($p, 'baj') !== false) $clase = 'priority-low';
                                ?>
                                <span class="<?php echo $clase; ?>"><?php echo htmlspecialchars($array3[$i]); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($array4[$i]); ?></td>
                            <td><?php echo htmlspecialchars($array5[$i]); ?></td>
                            <td><?php echo htmlspecialchars(substr($array6[$i] ?? '', 0, 30)) . (strlen($array6[$i] ?? '') > 30 ? '…' : ''); ?></td>
                            <td><?php echo $array7[$i] ? '<i class="fas fa-paperclip" style="color:var(--accent-blue);"></i>' : '-'; ?></td>
                            <td><?php echo htmlspecialchars($array8[$i]); ?></td>
                            <td><?php echo htmlspecialchars(substr($array9[$i], 0, 5)); ?></td>
                            <td><span class="ticket-id">#<?php echo htmlspecialchars($array10[$i]); ?></span></td>
                            <td>
                                <?php 
                                $e = strtolower($array11[$i] ?? '');
                                $clase2 = 'status-open';
                                if (strpos($e, 'cerrado') !== false) $clase2 = 'status-closed';
                                elseif (strpos($e, 'proceso') !== false) $clase2 = 'status-progress';
                                elseif (strpos($e, 'atendido') !== false) $clase2 = 'status-atendido';
                                ?>
                                <span class="<?php echo $clase2; ?>"><?php echo htmlspecialchars($array11[$i]); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($array12[$i]) ?: '-'; ?></td>
                            <td>
                                <?php if (!empty($array13[$i]) && $array13[$i] !== 'NULL'): ?>
                                <div class="image-buttons">
                                    <button class="btn-image btn-view view-image-btn" 
                                            data-url="<?php echo htmlspecialchars($array13[$i]); ?>"
                                            data-name="<?php echo htmlspecialchars($array14[$i]); ?>"
                                            data-id="<?php echo htmlspecialchars($array10[$i]); ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="<?php echo htmlspecialchars($array13[$i]); ?>" download="<?php echo htmlspecialchars($array14[$i]); ?>" class="btn-image btn-download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                <div class="image-info"><?php echo formatFileSize($array16[$i]); ?></div>
                                <?php else: ?>
                                <span style="color:rgba(255,255,255,0.3); font-size:0.7rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 3rem; color:rgba(255,255,255,0.1);"></i>
                <h4 style="margin: 10px 0; color:rgba(255,255,255,0.7);">No se encontraron tickets</h4>
                <div style="margin-top: 15px;">
                    <?php foreach ($comodines_busqueda as $p): ?>
                    <span class="pattern-tag"><?php echo htmlspecialchars($p); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-image me-2" style="color:var(--accent-blue);"></i> Vista previa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" style="max-height:70vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            <?php if ($total_tickets > 0): ?>
            $.fn.dataTable.moment('DD/MM/YYYY');
            
            $('#ticketsTable').DataTable({
                scrollY: 500,
                scrollX: true,
                paging: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                pageLength: 25,
                language: {
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ tickets",
                    infoEmpty: "Mostrando 0 a 0 de 0 tickets",
                    infoFiltered: "(filtrado de _MAX_ tickets totales)",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron registros",
                    emptyTable: "No hay datos disponibles",
                    paginate: { 
                        first: '<i class="fas fa-angle-double-left"></i>', 
                        last: '<i class="fas fa-angle-double-right"></i>', 
                        next: '<i class="fas fa-angle-right"></i>', 
                        previous: '<i class="fas fa-angle-left"></i>' 
                    }
                },
                drawCallback: function() {
                    $('.view-image-btn').off('click').on('click', function() {
                        $('#modalImage').attr('src', $(this).data('url'));
                        new bootstrap.Modal(document.getElementById('imageModal')).show();
                    });
                }
            });
            <?php endif; ?>

            $('.view-image-btn').click(function() {
                $('#modalImage').attr('src', $(this).data('url'));
                new bootstrap.Modal(document.getElementById('imageModal')).show();
            });

            $('#filtroForm').submit(function(e) {
                e.preventDefault();
                let params = [];
                if ($('#nombre').val()) params.push('nombre=' + encodeURIComponent($('#nombre').val()));
                if ($('#fecha_inicial').val()) params.push('fecha_inicial=' + $('#fecha_inicial').val());
                if ($('#fecha_final').val()) params.push('fecha_final=' + $('#fecha_final').val());
                window.location.href = window.location.pathname + (params.length ? '?' + params.join('&') : '');
            });

            if (!$('#fecha_inicial').val()) {
                let d = new Date(); d.setDate(d.getDate() - 30);
                $('#fecha_inicial').val(d.toISOString().split('T')[0]);
            }
            if (!$('#fecha_final').val()) {
                $('#fecha_final').val(new Date().toISOString().split('T')[0]);
            }
        });
    </script>
</body>
</html>