<?php
/**
 * @file Asignarticket.php
 * @brief Módulo de asignación de tickets de Servicios Generales a personal.
 *
 * @description
 * Interfaz para asignar tickets de Servicios Generales (tabla TicketsSG)
 * al personal disponible (tabla conped). Permite visualizar tickets pendientes
 * y asignarlos a técnicos o personal de mantenimiento.
 *
 * Este módulo trabaja con la tabla TicketsSG (diferente a T3 usada por TI)
 * y consulta la tabla conped para obtener la lista de personal disponible
 * para asignación.
 *
 * Características:
 * - Conexión directa a SQL Server
 * - Consulta de personal ordenado por nombre
 * - Vista de tickets con todos los campos relevantes
 * - Sin verificación de sesión (considerar agregar seguridad)
 *
 * Datos consultados de tickets:
 * - Id_Ticket, Nombre, Correo, Prioridad, Departamento (Empresa)
 * - Asunto, Problema (Adjuntos), Mensaje
 * - Fecha/Hora de captura, proceso y término
 * - Estatus, Enlace
 *
 * @module Módulo de Servicios Generales
 * @access Público (ADVERTENCIA: sin autenticación)
 *
 * @dependencies
 * - PHP: sqlsrv extension
 * - JS CDN: Bootstrap, DataTables
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tablas:
 *   - conped: Catálogo de personal (nombre)
 *   - TicketsSG: Tickets de Servicios Generales
 *
 * @security
 * - ADVERTENCIA: Sin verificación de sesión
 * - Credenciales hardcoded
 * - die() expone errores de SQL al cliente
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.2
 * @since 2024
 */

// Conexión SQL Server
require_once __DIR__ . '/config.php';
$connectionInfo = array("Database" => $DB_DATABASE, "UID" => $DB_USERNAME, "PWD" => $DB_PASSWORD, "CharacterSet" => "UTF-8", "TrustServerCertificate" => true, "Encrypt" => true);
$conn = sqlsrv_connect($DB_SERVER, $connectionInfo);
if (!$conn) die(print_r(sqlsrv_errors(), true));

// Obtener personal (conped)
$sql = "SELECT * FROM conped ORDER BY nombre";
$stmt = sqlsrv_query($conn, $sql);
$usuarios = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $usuarios[] = $row['Nombre'];
}

// Obtener tickets
$sql1 = "SELECT Id_Ticket AS Id, Nombre, Correo, Prioridad, Empresa AS Departamento, Asunto, Adjuntos AS Problema, Mensaje, 
    CAST(Fecha AS CHAR) AS Fecha_capturat, LEFT(CAST(Hora AS CHAR), 8) AS Hora_capturat, Estatus ,
    CAST(fecha_proceso AS CHAR) as fecha_proceso, LEFT(CAST(hora_proceso AS CHAR), 8) AS hora_proceso,
    CAST(Fecha_Termino AS CHAR) as Fecha_Termino, LEFT(CAST(Hora_Termino AS CHAR), 8) AS Hora_Termino, Enlace
    FROM TicketsSG";
$stmt1 = sqlsrv_query($conn, $sql1);
$tickets = [];
while ($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
    $tickets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes" />
    <title>PROCESA TUS TICKETS | BACROCORP</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
    
    <!-- DataTables Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />
    
    <!-- Google Fonts: Inter (mejor legibilidad) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 50%, #d9e8ff 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo sutil */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(0, 71, 151, 0.03) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(0, 41, 87, 0.03) 0%, transparent 40%);
            pointer-events: none;
            z-index: 0;
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1650px;
            width: 100%;
            margin: 75px auto 25px;
            position: relative;
            z-index: 10;
        }

        /* Tarjeta principal */
        .content-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 41, 87, 0.25);
            padding: 35px;
            position: relative;
            border: 1px solid rgba(0, 71, 151, 0.15);
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9edf4;
        }

        .header-title {
            font-size: 2rem;
            font-weight: 700;
            color: #002f5f;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.3px;
        }

        .header-title i {
            color: #004787;
            font-size: 2rem;
        }

        .header-subtitle {
            font-size: 0.9rem;
            color: #5f6c80;
            margin-top: 5px;
            font-weight: 400;
        }

        /* Logo */
        .logo-box {
            width: 85px;
            height: 85px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 41, 87, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 71, 151, 0.2);
            padding: 12px;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Barra de estado */
        .status-bar {
            background: #f8fafd;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 12px 24px;
            margin-bottom: 30px;
            font-size: 0.95rem;
            color: #1e2f44;
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
            box-shadow: 0 4px 12px rgba(0, 41, 87, 0.05);
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-item i {
            color: #004787;
            width: 18px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        /* Botón home */
        #homeButton {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 52px;
            height: 52px;
            background: white;
            border: 1px solid #d0ddeb;
            border-radius: 16px;
            font-size: 22px;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(0, 41, 87, 0.1);
            transition: all 0.2s ease;
            z-index: 1000;
            color: #004787;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        #homeButton:hover {
            background: #004787;
            color: white;
            border-color: #004787;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 71, 151, 0.2);
        }

        /* Botones */
        .btn-custom {
            background: white;
            border: 1px solid #d0ddeb;
            color: #002f5f;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-custom:hover {
            background: #f0f7ff;
            border-color: #004787;
        }

        /* Contenedor de tabla con scroll */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            border-radius: 24px;
            background: transparent;
            margin-top: 25px;
            border: 1px solid #e9edf4;
        }

        /* DataTables - Tabla con columnas bien marcadas */
        .dataTables_wrapper {
            font-family: 'Inter', sans-serif;
            width: 100%;
        }

        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            min-width: 1450px !important;
            margin: 0 !important;
            border: 1px solid #e0e7ef !important;
        }

        /* CABECERA - Bien marcada */
        table.dataTable thead tr {
            background-color: #f0f5fc !important;
        }

        table.dataTable thead th {
            background-color: #002f5f !important;
            color: white !important;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 16px 12px !important;
            white-space: nowrap;
            border-right: 1px solid rgba(255,255,255,0.15) !important;
            border-bottom: 2px solid #001a33 !important;
        }

        table.dataTable thead th:last-child {
            border-right: none !important;
        }

        /* CUERPO - Filas alternadas para mejor legibilidad */
        table.dataTable tbody tr {
            background-color: white !important;
            border-bottom: 1px solid #e9edf4 !important;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #fafcff !important;
        }

        table.dataTable tbody tr:hover {
            background-color: #e6f0ff !important;
        }

        table.dataTable tbody td {
            padding: 14px 12px !important;
            font-size: 0.9rem;
            color: #1e2f44;
            border-right: 1px solid #e9edf4 !important;
            border-bottom: 1px solid #e9edf4 !important;
            vertical-align: middle;
            line-height: 1.5;
            white-space: nowrap;
        }

        table.dataTable tbody td:last-child {
            border-right: none !important;
        }

        /* Bordes exteriores de la tabla */
        table.dataTable tbody tr:last-child td {
            border-bottom: 1px solid #d0ddeb !important;
        }

        /* Badges de estatus - Con bordes definidos */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            gap: 6px;
            border: 1px solid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .status-pendiente {
            background: #fff8e7;
            color: #b85e00;
            border-color: #ffdbb3;
        }

        .status-proceso {
            background: #e8f0fe;
            color: #004787;
            border-color: #b8d6ff;
        }

        .status-resuelto {
            background: #e6f7ec;
            color: #0b6b3f;
            border-color: #b8e0c7;
        }

        .status-cancelado {
            background: #fee9e9;
            color: #b02a37;
            border-color: #ffcece;
        }

        /* Prioridades - Bien marcadas */
        .priority-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .priority-alto { 
            color: #b02a37; 
        }
        .priority-medio { 
            color: #b85e00; 
        }
        .priority-bajo { 
            color: #0b6b3f; 
        }

        .priority-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .priority-dot.alto { 
            background: #b02a37; 
            box-shadow: 0 0 0 2px rgba(176,42,55,0.2);
        }
        .priority-dot.medio { 
            background: #b85e00; 
            box-shadow: 0 0 0 2px rgba(184,94,0,0.2);
        }
        .priority-dot.bajo { 
            background: #0b6b3f; 
            box-shadow: 0 0 0 2px rgba(11,107,63,0.2);
        }

        /* Enlaces y botones */
        .link-evidencia {
            color: #004787;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0f7ff;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #d0ddeb;
            transition: all 0.2s;
        }

        .link-evidencia:hover {
            background: #004787;
            color: white;
            border-color: #004787;
        }

        .btn-asignar {
            background: white;
            border: 1px solid #d0ddeb;
            color: #002f5f;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-asignar:hover {
            background: #002f5f;
            color: white;
            border-color: #002f5f;
        }

        /* Controles DataTables */
        .dataTables_length select,
        .dataTables_filter input {
            border: 1px solid #d0ddeb !important;
            border-radius: 40px !important;
            padding: 8px 20px !important;
            background: white !important;
            font-size: 0.9rem !important;
        }

        .dataTables_filter input {
            width: 240px;
        }

        .dataTables_filter input:focus {
            border-color: #004787 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0,71,151,0.1) !important;
        }

        .dataTables_info {
            padding-top: 20px;
            color: #5f6c80;
            font-size: 0.9rem;
        }

        .dataTables_paginate {
            padding-top: 20px;
        }

        .paginate_button {
            border: 1px solid #d0ddeb !important;
            border-radius: 40px !important;
            margin: 0 3px !important;
            padding: 6px 14px !important;
            background: white !important;
            color: #1e2f44 !important;
        }

        .paginate_button.current {
            background: #002f5f !important;
            color: white !important;
            border-color: #002f5f !important;
        }

        .dt-buttons {
            margin-bottom: 20px;
        }

        .dt-button {
            background: white !important;
            border: 1px solid #d0ddeb !important;
            border-radius: 40px !important;
            padding: 8px 20px !important;
            color: #002f5f !important;
            margin-right: 8px !important;
        }

        .dt-button:hover {
            background: #f0f7ff !important;
            border-color: #004787 !important;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 32px;
            box-shadow: 0 30px 60px -15px rgba(0, 41, 87, 0.3);
        }

        .modal-header {
            border-bottom: 1px solid #e9edf4;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 700;
            color: #002f5f;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #e9edf4;
            padding: 20px 25px;
        }

        .form-label {
            font-weight: 600;
            color: #002f5f;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #d0ddeb;
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #004787;
            box-shadow: 0 0 0 3px rgba(0,71,151,0.1);
            outline: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 12px; }
            
            .content-card { padding: 20px; }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-title { font-size: 1.7rem; }
            
            .logo-box {
                width: 70px;
                height: 70px;
                align-self: flex-end;
            }
            
            .status-bar {
                padding: 10px 18px;
                gap: 15px;
                font-size: 0.85rem;
            }
            
            #homeButton {
                top: 12px;
                left: 12px;
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
            
            .dataTables_filter input { width: 100%; }
            
            .main-container {
                margin-top: 60px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { 
            width: 10px; 
            height: 10px; 
        }
        ::-webkit-scrollbar-track { 
            background: #edf2f7; 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb { 
            background: #9bb0c7; 
            border-radius: 10px; 
            border: 2px solid #edf2f7;
        }
        ::-webkit-scrollbar-thumb:hover { 
            background: #004787; 
        }
    </style>
</head>
<body>
    <!-- Botón Home -->
    <a href="MenSG.php" id="homeButton" title="Inicio">
        <i class="fas fa-home"></i>
    </a>

    <div class="main-container">
        <div class="content-card">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1 class="header-title">
                        <i class="fas fa-ticket-alt"></i>
                        Procesa tus tickets
                    </h1>
                    <div class="header-subtitle">
                        <i class="fas fa-circle" style="color: #004787; font-size: 0.4rem;"></i>
                        Gestión y seguimiento de solicitudes
                    </div>
                </div>
                <div class="logo-box">
                    <img src="Logo2.png" alt="Logo" />
                </div>
            </div>

            <!-- Barra de estado -->
            <div class="status-bar">
                <div class="status-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('Y-m-d H:i'); ?></span>
                </div>
                <div class="status-item">
                    <i class="fas fa-database"></i>
                    <span><?php echo count($tickets); ?> tickets</span>
                </div>
                <div class="status-item ms-auto">
                    <span class="status-dot"></span>
                    <span>Sistema activo</span>
                </div>
            </div>

            <!-- Tabla de Tickets con scroll -->
            <div class="table-responsive-wrapper">
                <table id="ticketsTable" class="table w-100"></table>
            </div>
        </div>
    </div>

    <!-- MODAL Asignar Ticket -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="assignForm" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check" style="color: #004787;"></i>
                        Asignar Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ticketId" name="ticketId" />
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i> Asignar a:
                        </label>
                        <input type="text" class="form-control" id="asignadoA" name="asignadoA" 
                               placeholder="Nombre completo" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i> Nuevo estatus:
                        </label>
                        <select id="nuevoEstatus" name="nuevoEstatus" class="form-select" required>
                            <option value="Pendiente">⏳ Pendiente</option>
                            <option value="En proceso">🔄 En proceso</option>
                            <option value="Resuelto">✅ Resuelto</option>
                            <option value="Cancelado">❌ Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-camera me-1"></i> Evidencia:
                        </label>
                        <input type="file" class="form-control" id="evidenciaFoto" name="evidenciaFoto" accept="image/*" />
                        <small class="text-muted mt-1 d-block">Máx 5MB - JPG, PNG, GIF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-asignar">
                        <i class="fas fa-check"></i> Guardar
                    </button>
                    <button type="button" class="btn-asignar" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script>
        const tickets = <?php echo json_encode($tickets); ?>;
        
        // Formatear datos
        const formattedTickets = tickets.map(t => {
            // Prioridad con punto de color
            let prioridadHTML = t.Prioridad;
            if (t.Prioridad && t.Prioridad.includes('Alto')) {
                prioridadHTML = '<span class="priority-tag priority-alto"><span class="priority-dot alto"></span> ' + t.Prioridad + '</span>';
            } else if (t.Prioridad && t.Prioridad.includes('Medio')) {
                prioridadHTML = '<span class="priority-tag priority-medio"><span class="priority-dot medio"></span> ' + t.Prioridad + '</span>';
            } else {
                prioridadHTML = '<span class="priority-tag priority-bajo"><span class="priority-dot bajo"></span> ' + t.Prioridad + '</span>';
            }

            // Estatus
            let estatusClass = 'status-pendiente';
            if (t.Estatus && t.Estatus.includes('proceso')) estatusClass = 'status-proceso';
            else if (t.Estatus && t.Estatus.includes('Resuelto')) estatusClass = 'status-resuelto';
            else if (t.Estatus && t.Estatus.includes('Cancelado')) estatusClass = 'status-cancelado';
            
            let estatusHTML = `<span class="status-badge ${estatusClass}"><i class="fas fa-circle" style="font-size: 0.4rem;"></i> ${t.Estatus || '-'}</span>`;

            // Evidencia
            let evidenciaHTML = t.Enlace ? 
                `<a href="${t.Enlace}" target="_blank" class="link-evidencia">
                    <i class="fas fa-image"></i> Ver
                </a>` : 
                '<span style="color: #9aa6b5; font-size: 0.8rem;"><i class="fas fa-times-circle"></i> Sin evidencia</span>';

            return [
                t.Id || '-',
                t.Nombre || '-',
                t.Correo || '-',
                prioridadHTML,
                t.Departamento || '-',
                t.Asunto || '-',
                t.Problema ? (t.Problema.length > 35 ? t.Problema.substring(0, 35) + '…' : t.Problema) : '-',
                t.Mensaje ? (t.Mensaje.length > 30 ? t.Mensaje.substring(0, 30) + '…' : t.Mensaje) : '-',
                t.Fecha_capturat || '-',
                t.Hora_capturat || '-',
                estatusHTML,
                t.fecha_proceso || '-',
                t.hora_proceso || '-',
                t.Fecha_Termino || '-',
                t.Hora_Termino || '-',
                evidenciaHTML,
                `<button class="btn-asignar assign-btn" data-id="${t.Id}">
                    <i class="fas fa-user-tag"></i> Asignar
                </button>`
            ];
        });

        // Inicializar DataTable
        const table = $('#ticketsTable').DataTable({
            data: formattedTickets,
            columns: [
                { title: 'ID' },
                { title: 'Nombre' },
                { title: 'Correo' },
                { title: 'Prioridad' },
                { title: 'Ubicación' },
                { title: 'Tipo' },
                { title: 'Problema' },
                { title: 'Mensaje' },
                { title: 'Fecha' },
                { title: 'Hora' },
                { title: 'Estatus' },
                { title: 'F. Proceso' },
                { title: 'H. Proceso' },
                { title: 'F. Término' },
                { title: 'H. Término' },
                { title: 'Evidencia' },
                { title: 'Acciones' }
            ],
            pageLength: 20,
            lengthMenu: [10, 20, 50, 100, 250],
            order: [[8, 'desc']],
            responsive: false,
            scrollX: false,
            autoWidth: true,
            stripeClasses: ['', 'bg-light'], // Para filas alternadas
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel me-2"></i> Exportar a Excel',
                    className: 'btn-custom',
                    exportOptions: {
                        columns: [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14]
                    }
                }
            ]
        });

        // Modal de asignación
        $(document).on('click', '.assign-btn', function () {
            const ticketId = $(this).data('id');
            $('#ticketId').val(ticketId);
            $('#assignModal').modal('show');
        });

        // Enviar asignación
        $('#assignForm').on('submit', function (e) {
            e.preventDefault();
            
            let formData = new FormData(this);

            $.ajax({
                url: 'actualizar_ticket.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (resp) {
                    alert('✅ Ticket actualizado correctamente');
                    location.reload();
                },
                error: function () {
                    alert('❌ Error al actualizar el ticket');
                }
            });
        });
    </script>
</body>
</html>