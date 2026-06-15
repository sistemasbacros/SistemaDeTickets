<?php
session_start();
date_default_timezone_set('America/Mexico_City');

// Conexión a base de datos para nombres (vwLBSContactList)
$serverName1 = "WIN-44O80L37Q7M\COMERCIAL";
$connectionInfo1 = array( 
    "Database"=>"BASENUEVA", 
    "UID"=>"SA", 
    "PWD"=>"Administrador1*",
    "CharacterSet" => "UTF-8"
);
$conn1 = sqlsrv_connect($serverName1, $connectionInfo1);

$array_tot1 = [];
if ($conn1) {
    $sql = "SELECT ContactName FROM [dbo].[vwLBSContactList] ORDER BY ContactName";
    $stmt = sqlsrv_query($conn1, $sql);
    if ($stmt) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            array_push($array_tot1, $row['ContactName']);
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn1);
}

// Datos de respaldo
if (empty($array_tot1)) {
    $array_tot1 = [
        "LUIS ANTONIO ROMERO LOPEZ", 
        "MARIA FERNANDA LOPEZ", 
        "JUAN CARLOS RODRIGUEZ", 
        "ANA SOFIA MARTINEZ",
        "CARLOS ALBERTO PEREZ"
    ];
}

// Conexión a base de datos de Tickets
$serverName = "DESAROLLO-BACRO\SQLEXPRESS";
$connectionInfo = array( 
    "Database"=>"Ticket", 
    "UID"=>"Larome03", 
    "PWD"=>"Larome03",
    "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Variables para la tabla
$array_tt1 = []; // Id_Ticket
$array_tt2 = []; // Nombre
$array_tt3 = []; // Prioridad
$array_tt4 = []; // Empresa (Ubicación)
$array_tt5 = []; // Area_Piso
$array_tt6 = []; // Asunto (Tipo de ticket)
$array_tt7 = []; // Mensaje
$array_tt8 = []; // Adjuntos (Problema)
$array_tt9 = []; // Fecha
$array_tt10 = []; // Hora
$array_tt11 = []; // Estatus

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filterNombre"])) {
    $name1 = test_input($_POST["filterNombre"]);
    $name2 = test_input($_POST["filterFechaInicio"]);
    $name3 = test_input($_POST["filterFechaFin"]);
    
    if (!empty($name1) && !empty($name2) && !empty($name3) && $conn) {
        $sql1 = "SELECT 
                    Id_Ticket,
                    Nombre,
                    Prioridad,
                    Empresa,
                    Area_Piso,
                    Asunto,
                    Mensaje,
                    Adjuntos as Problema,
                    CONVERT(varchar, Fecha, 23) as Fecha,
                    LEFT(CONVERT(varchar, Hora, 8), 8) as Hora,
                    Estatus
                FROM [dbo].[TicketsSG] 
                WHERE Nombre = ? 
                AND Fecha >= ? 
                AND Fecha <= ?
                ORDER BY Fecha DESC, Hora DESC";
        
        $params = array($name1, $name2, $name3);
        $stmt1 = sqlsrv_query($conn, $sql1, $params);
        
        if ($stmt1) {
            while($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
                array_push($array_tt1, $row['Id_Ticket'] ?? '');
                array_push($array_tt2, $row['Nombre'] ?? '');
                array_push($array_tt3, $row['Prioridad'] ?? '');
                array_push($array_tt4, $row['Empresa'] ?? '');
                array_push($array_tt5, $row['Area_Piso'] ?? '');
                array_push($array_tt6, $row['Asunto'] ?? '');
                array_push($array_tt7, $row['Mensaje'] ?? '');
                array_push($array_tt8, $row['Problema'] ?? '');
                array_push($array_tt9, $row['Fecha'] ?? '');
                array_push($array_tt10, $row['Hora'] ?? '');
                array_push($array_tt11, $row['Estatus'] ?? '');
            }
            sqlsrv_free_stmt($stmt1);
        }
    }
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CONSULTA DE TICKETS | BACROCORP GLASS</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e6f0ff 0%, #b3d9ff 50%, #80bfff 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        /* Efecto glass en el fondo */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(0, 51, 102, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 80% 70%, rgba(0, 102, 204, 0.05) 0%, transparent 30%);
            pointer-events: none;
            z-index: 0;
        }

        /* Contenedor principal */
        .glass-container {
            max-width: 1400px;
            width: 100%;
            margin: 80px auto 30px;
            position: relative;
            z-index: 10;
        }

        /* Tarjeta glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 102, 204, 0.15);
            border-radius: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 51, 102, 0.25), 0 0 0 1px rgba(0, 102, 204, 0.1) inset;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #003366, #0066cc, #0099ff);
            z-index: 1;
        }

        /* Header */
        .glass-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 102, 204, 0.1);
        }

        .glass-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #003366;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .glass-title i {
            color: #0066cc;
            font-size: 2rem;
        }

        .glass-subtitle {
            font-size: 0.95rem;
            color: #4a5b6e;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .glass-subtitle i {
            color: #0066cc;
        }

        /* Logo */
        .logo-wrapper {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 25px;
            box-shadow: 0 15px 30px rgba(0, 102, 204, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(0, 102, 204, 0.2);
            padding: 12px;
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Terminal line glass */
        .terminal-line {
            background: rgba(0, 102, 204, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 30px;
            font-size: 0.95rem;
            color: #003366;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .terminal-prompt {
            color: #0066cc;
            font-weight: 600;
        }

        .terminal-user {
            background: rgba(0, 102, 204, 0.1);
            padding: 5px 12px;
            border-radius: 20px;
        }

        /* Filtros glass */
        .filter-section {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 30px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #003366;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #0066cc;
            width: 18px;
        }

        /* Inputs glass */
        .glass-input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border: 2px solid rgba(0, 102, 204, 0.2);
            border-radius: 16px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            color: #003366;
            transition: all 0.3s ease;
        }

        .glass-input:hover {
            border-color: rgba(0, 102, 204, 0.4);
            background: rgba(255, 255, 255, 0.9);
        }

        .glass-input:focus {
            border-color: #0066cc;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
            outline: none;
        }

        /* Select2 glass */
        .select2-container--default .select2-selection--single {
            height: 52px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            border: 2px solid rgba(0, 102, 204, 0.2);
            border-radius: 16px;
            padding: 12px 16px;
            font-family: 'Inter', sans-serif;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
            color: #003366;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
            right: 12px;
        }

        .select2-container--default .select2-dropdown {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #0066cc;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 102, 204, 0.15);
        }

        .select2-container--default .select2-results__option--highlighted {
            background: #0066cc;
            color: white;
        }

        .select2-search__field {
            border: 2px solid rgba(0, 102, 204, 0.2) !important;
            border-radius: 12px !important;
            padding: 8px !important;
            background: white !important;
        }

        .select2-search__field:focus {
            border-color: #0066cc !important;
            outline: none !important;
        }

        /* Botón glass */
        .btn-glass {
            background: linear-gradient(135deg, #003366, #0066cc);
            color: white;
            font-size: 1rem;
            font-weight: 600;
            padding: 14px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(0, 102, 204, 0.3);
        }

        .btn-glass:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 102, 204, 0.4);
        }

        /* Tabla glass */
        .table-container {
            overflow-x: auto;
            margin-top: 30px;
        }

        .glass-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 51, 102, 0.2);
        }

        .glass-table thead tr {
            background: linear-gradient(135deg, #003366, #0066cc);
            color: white;
        }

        .glass-table th {
            padding: 15px 12px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            white-space: nowrap;
        }

        .glass-table td {
            padding: 12px 12px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
            font-size: 0.9rem;
            color: #003366;
            white-space: nowrap;
        }

        .glass-table td:first-child, .glass-table th:first-child { padding-left: 20px; }
        .glass-table td:last-child, .glass-table th:last-child { padding-right: 20px; }

        .glass-table tbody tr:hover {
            background: rgba(0, 102, 204, 0.05);
        }

        /* Badges de estatus */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }

        .status-proceso {
            background: rgba(255, 243, 205, 0.8);
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-resuelto {
            background: rgba(212, 237, 218, 0.8);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-cancelado {
            background: rgba(248, 215, 218, 0.8);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Prioridad */
        .priority-alto {
            color: #dc3545;
            font-weight: 600;
            white-space: nowrap;
        }

        .priority-medio {
            color: #ffc107;
            font-weight: 600;
            white-space: nowrap;
        }

        .priority-bajo {
            color: #28a745;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Botón home glass */
        #homeButton {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 20px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(0, 51, 102, 0.15);
            transition: all 0.3s ease;
            z-index: 1000;
            color: #0066cc;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        #homeButton:hover {
            background: #0066cc;
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0, 102, 204, 0.3);
            border-color: #0066cc;
        }

        /* DataTables ajustes */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 12px;
            border-radius: 12px;
            border: 2px solid rgba(0, 102, 204, 0.2);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #0066cc;
            outline: none;
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 20px;
            text-align: center;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 12px;
            margin: 0 3px;
            border-radius: 12px;
            border: 1px solid rgba(0, 102, 204, 0.2);
            cursor: pointer;
            display: inline-block;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0066cc;
            color: white !important;
            border-color: #0066cc;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: rgba(0, 102, 204, 0.2);
        }

        .dataTables_wrapper .dataTables_info {
            margin-top: 15px;
            color: #003366;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .glass-card {
                padding: 25px;
            }

            .glass-title {
                font-size: 1.8rem;
            }

            .logo-wrapper {
                width: 70px;
                height: 70px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            #homeButton {
                top: 15px;
                left: 15px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .glass-table th, .glass-table td {
                font-size: 0.8rem;
                padding: 10px 8px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 102, 204, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: #0066cc;
            border-radius: 10px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- Botón Home -->
    <a href="http://desarollo-bacros/TicketBacros/MenSG.php" id="homeButton" title="Inicio">
        <i class="fas fa-home"></i>
    </a>

    <div class="glass-container">
        <div class="glass-card">
            <!-- Header -->
            <div class="glass-header">
                <div>
                    <h1 class="glass-title">
                        <i class="fas fa-ticket-alt"></i>
                        CONSULTA DE TICKETS
                    </h1>
                    <div class="glass-subtitle">
                        <i class="fas fa-circle" style="font-size: 0.5rem; color: #28a745;"></i>
                        <span>Sistema Glass | BACROCORP</span>
                    </div>
                </div>
                <div class="logo-wrapper">
                    <img src="Logo2.png" alt="Logo" />
                </div>
            </div>

            <!-- Terminal Line -->
            <div class="terminal-line">
                <span class="terminal-prompt"><i class="fas fa-chevron-right"></i></span>
                <span class="terminal-user">user@bacrocorp:~$</span>
                <span><i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i:s'); ?></span>
                <span><i class="fas fa-circle" style="color: #28a745; font-size: 0.5rem;"></i> Glass Mode</span>
                <span><i class="fas fa-database"></i> <?php echo count($array_tt1); ?> tickets</span>
            </div>

            <!-- Formulario de Filtros -->
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="filterForm">
                <div class="filter-section">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-circle"></i> NOMBRE
                            </label>
                            <select id="filterNombre" name="filterNombre" style="width: 100%;" required>
                                <option value="" disabled <?php echo !isset($_POST['filterNombre']) ? 'selected' : ''; ?>>👤 Seleccione un nombre...</option>
                                <?php foreach ($array_tot1 as $nombre): ?>
                                    <option value="<?php echo htmlspecialchars($nombre); ?>" <?php echo (isset($_POST['filterNombre']) && $_POST['filterNombre'] == $nombre) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nombre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i> FECHA INICIO
                            </label>
                            <input type="text" class="glass-input datepicker" id="filterFechaInicio" name="filterFechaInicio" 
                                   value="<?php echo isset($_POST['filterFechaInicio']) ? htmlspecialchars($_POST['filterFechaInicio']) : date('Y-m-d'); ?>" 
                                   autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i> FECHA FIN
                            </label>
                            <input type="text" class="glass-input datepicker" id="filterFechaFin" name="filterFechaFin" 
                                   value="<?php echo isset($_POST['filterFechaFin']) ? htmlspecialchars($_POST['filterFechaFin']) : date('Y-m-d'); ?>" 
                                   autocomplete="off" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn-glass">
                                <i class="fas fa-search"></i> BUSCAR
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Tabla Glass -->
            <div class="table-container">
                <table class="glass-table" id="ticketsTable">
                    <thead>
                        <tr>
                            <th>ID TICKET</th>
                            <th>NOMBRE</th>
                            <th>PRIORIDAD</th>
                            <th>UBICACIÓN</th>
                            <th>ÁREA</th>
                            <th>TIPO TICKET</th>
                            <th>DESCRIPCIÓN</th>
                            <th>MENSAJE</th>
                            <th>FECHA</th>
                            <th>HORA</th>
                            <th>ESTATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($array_tt1)): ?>
                            <?php for ($i = 0; $i < count($array_tt1); $i++): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($array_tt1[$i]); ?></strong></td>
                                    <td><?php echo htmlspecialchars($array_tt2[$i]); ?></td>
                                    <td>
                                        <?php 
                                        $prioridad = $array_tt3[$i] ?? '';
                                        $prioridad_class = '';
                                        $prioridad_icon = '';
                                        if (strpos($prioridad, 'Alto') !== false) {
                                            $prioridad_class = 'priority-alto';
                                            $prioridad_icon = '🔴';
                                        } elseif (strpos($prioridad, 'Medio') !== false) {
                                            $prioridad_class = 'priority-medio';
                                            $prioridad_icon = '🟡';
                                        } else {
                                            $prioridad_class = 'priority-bajo';
                                            $prioridad_icon = '🟢';
                                        }
                                        ?>
                                        <span class="<?php echo $prioridad_class; ?>">
                                            <?php echo $prioridad_icon . ' ' . htmlspecialchars($prioridad); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($array_tt4[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt5[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt6[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt8[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt7[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt9[$i]); ?></td>
                                    <td><?php echo htmlspecialchars($array_tt10[$i]); ?></td>
                                    <td>
                                        <?php 
                                        $estatus = $array_tt11[$i] ?? '';
                                        $badge_class = 'status-proceso';
                                        if (strpos($estatus, 'Resuelto') !== false) {
                                            $badge_class = 'status-resuelto';
                                        } elseif (strpos($estatus, 'Cancelado') !== false) {
                                            $badge_class = 'status-cancelado';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($estatus); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 50px;">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: rgba(0,102,204,0.3); margin-bottom: 15px; display: block;"></i>
                                    <p style="color: #003366; font-size: 1.1rem;">No hay tickets para mostrar</p>
                                    <p style="color: #4a5b6e;">Seleccione un nombre y fechas para realizar la búsqueda</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar Select2
            $('#filterNombre').select2({
                placeholder: '👤 Busque o seleccione un nombre...',
                allowClear: false,
                width: '100%'
            });

            // Inicializar Datepicker
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });

            <?php if (!empty($array_tt1)): ?>
            // Inicializar DataTable
            $('#ticketsTable').DataTable({
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                order: [[8, 'desc']], // Ordenar por fecha descendente
                pageLength: 50,
                lengthMenu: [25, 50, 100, 250, 500]
            });
            <?php endif; ?>

            // Validar fechas
            $('#filterForm').on('submit', function(e) {
                var fechaInicio = $('#filterFechaInicio').val();
                var fechaFin = $('#filterFechaFin').val();
                var nombre = $('#filterNombre').val();

                if (!nombre) {
                    e.preventDefault();
                    alert('Debe seleccionar un nombre');
                    return false;
                }

                if (!fechaInicio || !fechaFin) {
                    e.preventDefault();
                    alert('Debe seleccionar ambas fechas');
                    return false;
                }

                if (fechaInicio > fechaFin) {
                    e.preventDefault();
                    alert('La fecha de inicio no puede ser mayor que la fecha fin');
                    return false;
                }
            });
        });
    </script>
</body>
</html>