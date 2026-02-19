<?php
/**
 * @file TableT1.php
 * @brief Vista avanzada de gestión de tickets con autenticación y diseño glassmorphism.
 *
 * @description
 * Módulo de visualización y gestión de tickets del Sistema de Tickets TI de BacroCorp.
 * Presenta una interfaz moderna con diseño glassmorphism, partículas animadas de fondo
 * y tabla interactiva DataTables para la administración de tickets de soporte técnico.
 * 
 * Requiere autenticación previa mediante Loginti.php. Valida la sesión del usuario
 * antes de mostrar cualquier contenido. Incluye soporte para visualización de imágenes
 * de evidencia mediante Lightbox2.
 *
 * Características principales:
 * - Verificación de sesión con redirección automática a login
 * - Header con información del usuario autenticado
 * - Tabla DataTables con ordenamiento, búsqueda y paginación
 * - Diseño dark mode con gradientes navy y efectos glass
 * - Partículas animadas CSS en el fondo
 * - Logo corporativo con efecto glow pulsante
 * - Modal para ver detalles y editar tickets
 * - Soporte para visualización de imágenes adjuntas (Lightbox)
 * - Responsive design para dispositivos móviles
 *
 * @module Módulo de Gestión de Tickets
 * @access Privado (requiere sesión activa desde Loginti.php)
 *
 * @dependencies
 * - PHP: session_start()
 * - JS CDN: Bootstrap 5.3.0-alpha1, Font Awesome 6.4.0, DataTables 1.13.4, 
 *           SweetAlert2 11, Lightbox2 2.11.4, jQuery 3.6.0
 * - CSS CDN: Google Fonts (Outfit, Manrope)
 * - Interno: Loginti.php (autenticación), fetch_tickets.php (API datos),
 *            update_ticket.php (API actualización)
 *
 * @database
 * - No realiza conexiones directas a base de datos
 * - Obtiene datos via API: fetch_tickets.php
 * - Actualiza datos via API: update_ticket.php
 *
 * @session
 * - Variables requeridas (establecidas por Loginti.php):
 *   - $_SESSION['logged_in']     — Bandera booleana de autenticación (debe ser true)
 *   - $_SESSION['user_name']     — Nombre completo del usuario (mostrado en header)
 *   - $_SESSION['user_id']       — ID del empleado autenticado
 *   - $_SESSION['user_area']     — Área/departamento del usuario
 *   - $_SESSION['user_username'] — Nombre de usuario
 * - Redirección: Si logged_in !== true, redirige a Loginti.php
 *
 * @inputs
 * - $_SESSION (datos del usuario autenticado)
 * - API fetch_tickets.php (datos de tickets via AJAX)
 *
 * @outputs
 * - HTML: Página completa con dashboard de tickets
 * - UI: Tabla interactiva, modales, alertas SweetAlert2
 *
 * @security
 * - Verificación de sesión obligatoria antes de renderizar
 * - Redirección inmediata si no hay sesión válida (header + exit)
 * - Uso de operador null coalescing (??) para valores por defecto
 * - Datos de usuario mostrados desde sesión (no desde BD directa)
 *
 * @ui_components
 * - Header con logo, título y datos del usuario
 * - Contenedor de partículas animadas (CSS puro)
 * - Card glassmorphism principal
 * - DataTable con columnas: ID, Solicitante, Prioridad, Asunto, Estado, Acciones
 * - Modal de detalle/edición de ticket
 * - Lightbox para imágenes de evidencia
 *
 * @css_variables
 * - --navy-primary: #0a192f (fondo principal)
 * - --navy-secondary: #112240 (fondo secundario)
 * - --accent-blue: #64a8ff (acentos)
 * - --accent-teal: #64ffda (highlights)
 * - --glass-bg: rgba(255,255,255,0.08) (efecto glass)
 * - --glass-border: rgba(255,255,255,0.15) (bordes glass)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.2
 * @since 2024
 * @updated 2026-02-18
 */

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Gestión de Tickets - BacroCorp</title>

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
  <!-- Lightbox CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
  
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
    
    .particles-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -2;
      overflow: hidden;
    }
    
    .particle {
      position: absolute;
      background: rgba(100, 168, 255, 0.1);
      border-radius: 50%;
      animation: floatParticle 20s infinite linear;
    }
    
    @keyframes floatParticle {
      0% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
      }
      10% {
        opacity: 0.3;
      }
      90% {
        opacity: 0.3;
      }
      100% {
        transform: translateY(-100px) rotate(360deg);
        opacity: 0;
      }
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
    }
    
    .glass-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--accent-blue), transparent);
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
    
    .logo-container {
      position: relative;
      display: inline-block;
      margin-bottom: 15px;
    }
    
    .logo-glow {
      width: 100px;
      height: 100px;
      margin: 0 auto;
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
    
    .logo-glow::after {
      content: '';
      position: absolute;
      top: -3px;
      left: -3px;
      right: -3px;
      bottom: -3px;
      background: var(--gradient-blue);
      border-radius: 50%;
      z-index: -1;
      filter: blur(12px);
      opacity: 0.6;
      animation: logoPulse 3s ease-in-out infinite 0.5s;
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
      position: relative;
    }
    
    .system-title::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 2px;
      background: var(--gradient-blue);
      border-radius: 2px;
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
    
    .top-bar {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
      margin-bottom: 25px;
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
    
    .btn-primary:active {
      transform: translateY(-1px);
    }
    
    .btn-icon {
      background: var(--gradient-blue);
      border: none;
      color: white;
      width: 55px;
      height: 55px;
      border-radius: 16px;
      font-size: 22px;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-icon:hover {
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
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
    
    table.dataTable thead th {
      position: sticky;
      top: 0;
      z-index: 10;
      background: var(--gradient-blue);
      color: white;
      font-weight: 600;
      padding: 12px;
      white-space: nowrap;
      border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    table.dataTable tbody td {
      padding: 10px;
      vertical-align: middle;
      white-space: nowrap;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: white !important;
      font-weight: 400;
    }
    
    table.dataTable tbody tr {
      background-color: rgba(255, 255, 255, 0.02);
    }
    
    table.dataTable tbody tr:nth-child(odd) {
      background-color: rgba(255, 255, 255, 0.05);
    }
    
    table.dataTable tbody tr:hover {
      background-color: rgba(100, 168, 255, 0.15);
      cursor: pointer;
    }
    
    table.dataTable tbody tr.selected {
      background-color: rgba(59, 130, 246, 0.25) !important;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 6px 14px;
      margin: 0 3px;
      border-radius: 6px;
      background-color: rgba(255, 255, 255, 0.1);
      color: white !important;
      font-weight: 600;
      transition: background-color 0.2s;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background-color: rgba(59, 130, 246, 0.3);
      color: white !important;
      border: 1px solid rgba(59, 130, 246, 0.5);
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--gradient-blue) !important;
      color: white !important;
      border: 1px solid rgba(59, 130, 246, 0.5);
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info {
      color: white !important;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
      background: rgba(255, 255, 255, 0.07);
      border: 2px solid rgba(255, 255, 255, 0.12);
      color: white;
      border-radius: 14px;
      padding: 8px 12px;
    }
    
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
      background: rgba(255, 255, 255, 0.1);
      border-color: var(--accent-blue);
      color: white;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    
    .modal-content {
      background: var(--navy-secondary);
      color: white;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: var(--glass-shadow);
    }
    
    .modal-header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .modal-footer {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
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
    
    select.form-control option, .form-select option {
      background-color: var(--navy-secondary);
      color: white;
    }
    
    .img-thumbnail {
      max-width: 80px;
      max-height: 60px;
      border-radius: 8px;
      border: 2px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
      cursor: pointer;
      background: rgba(255, 255, 255, 0.05);
    }
    
    .img-thumbnail:hover {
      transform: scale(1.05);
      border-color: var(--accent-blue);
      box-shadow: 0 0 15px rgba(100, 168, 255, 0.3);
    }
    
    .image-cell {
      text-align: center;
      vertical-align: middle;
    }
    
    .no-image {
      color: rgba(255, 255, 255, 0.5);
      font-size: 11px;
      font-style: italic;
    }
    
    .progress {
      height: 20px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.1);
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .progress-bar {
      background: var(--gradient-blue);
      transition: width 0.3s ease;
    }
    
    .progress-bar-striped {
      background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
      );
      background-size: 1rem 1rem;
    }
    
    .progress-bar-animated {
      animation: progress-bar-stripes 1s linear infinite;
    }
    
    @keyframes progress-bar-stripes {
      0% {
        background-position: 1rem 0;
      }
      100% {
        background-position: 0 0;
      }
    }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .animate-in {
      animation: slideUp 0.5s ease-out forwards;
    }
    
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }
    
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
      
      .top-bar {
        flex-direction: column;
        align-items: center;
      }
      
      .btn-primary {
        width: 100%;
        max-width: 320px;
      }
      
      table.dataTable thead th, table.dataTable tbody td {
        white-space: normal;
        word-break: break-word;
      }
      
      .img-thumbnail {
        max-width: 60px;
        max-height: 45px;
      }
    }
  </style>
</head>

<body>
  <!-- Partículas de fondo -->
  <div class="particles-container" id="particlesContainer"></div>
  
  <!-- Header Section -->
  <div class="header-section animate-in">
    <div class="logo-container">
      <div class="logo-glow">
        <i class="fas fa-ticket-alt"></i>
      </div>
    </div>
    
    <h1 class="system-title">GESTIÓN DE TICKETS</h1>
    <p class="system-subtitle">Sistema de Administración y Seguimiento de Tickets de Soporte</p>
    
    <div class="security-badge">
      <i class="fas fa-badge-check"></i>
      Panel Administrativo - Acceso Restringido
    </div>
  </div>
  
  <!-- Contenedor principal -->
  <div class="container">
    <!-- Información del Usuario Autenticado -->
    <div class="glass-card animate-in delay-1" style="padding: 20px; margin-bottom: 25px;">
      <div class="security-badge" style="margin: 0; width: 100%; text-align: center;">
        <i class="fas fa-user-check"></i>
        <strong>Usuario Autenticado:</strong> <?php echo htmlspecialchars($nombre_usuario); ?> | 
        <strong>Área:</strong> <?php echo htmlspecialchars($area_usuario); ?> | 
        <strong>ID:</strong> <?php echo htmlspecialchars($id_empleado); ?>
      </div>
    </div>

    <div class="glass-card animate-in delay-1" style="padding: 30px;">
      <div class="top-bar">
        <a href="dashboard.php" class="btn btn-primary">
          <i class="fas fa-home me-2"></i>INICIO
        </a>
        <button class="btn btn-primary" id="assignTicketBtn">
          <i class="fas fa-user-check me-2"></i>Asignar Ticket
        </button>
        <button class="btn-icon" id="exportExcelBtn" aria-label="Exportar a Excel">
          <i class="fas fa-file-excel"></i>
        </button>
      </div>

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
              <th>Hora Inicio</th>
              <th>No Ticket</th>
              <th>Estatus</th>
              <th>Responsable</th>
              <th>FechaEnProceso</th>
              <th>HoraEnProceso</th>
              <th>FechaPausa</th>
              <th>HoraPausa</th>
              <th>FechaTerminado</th>
              <th>HoraTerminado</th>
              <th>FechaCancelado</th>
              <th>HoraCancelado</th>
              <th>Imagen</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal Asignar -->
  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="assignForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-check me-2"></i>Asignar Ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticketId" name="ticketId" />
          <div class="mb-3">
            <label for="responsable" class="form-label">
              <i class="fas fa-user me-2"></i>Responsable
            </label>
            <select id="responsable" name="responsable" class="form-select" required>
              <option value="">Seleccione un responsable</option>
              <option value="Ariel Antonio Sanchez">Ariel Antonio Sanchez</option>
              <option value="Alfredo Rosales Ramirez">Alfredo Rosales Ramirez</option>
              <option value="Boris Kelvin Ramirez Neyra">Boris Kelvin Ramirez Neyra</option>
              <option value="Luis Antonio Romero López">Luis Antonio Romero López</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="estatus" class="form-label">
              <i class="fas fa-flag me-2"></i>Estatus
            </label>
            <select id="estatus" name="estatus" class="form-select" required>
              <option value="">Seleccione</option>
              <option value="En Proceso">En Proceso</option>
              <option value="Pausa">Pausa</option>
              <option value="Atendido">Atendido</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="asunto" class="form-label">
              <i class="fas fa-tags me-2"></i>Asunto
            </label>
            <select id="asunto" name="asunto" class="form-select" required>
              <option value="">Seleccione</option>
              <option value="COMEDOR">🍽️ COMEDOR</option>
              <option value="ERP">💼 ERP</option>
              <option value="LAPTOP / PC">💻 LAPTOP / PC</option>
              <option value="IMPRESORA">🖨️ IMPRESORA</option>
              <option value="CONTPAQi">📊 CONTPAQi</option>
              <option value="CORREO">📧 CORREO</option>
              <option value="NUEVO INGRESO">👤 NUEVO INGRESO</option>
              <option value="CARPETAS ACCESO">📁 CARPETAS ACCESO</option>
              <option value="SALIDA EQUIPO">🚪 SALIDA EQUIPO</option>
              <option value="DIGITALIZACIÓN">📄 DIGITALIZACIÓN</option>
              <option value="BLOQUEO USB">🔒 BLOQUEO USB</option>
              <option value="TELEFONÍA">📞 TELEFONÍA</option>
              <option value="INTERNET">🌐 INTERNET</option>
              <option value="SOFTWARE">🛠️ SOFTWARE</option>
              <option value="INFRAESTRUCTURA TI">🏗️ INFRAESTRUCTURA TI</option>
              <option value="OTROS">❓ OTROS</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Lightbox JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>

  <!-- Moment.js y plugin para ordenar fechas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>

<!-- SheetJS para exportar Excel -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/shim.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

<script>
  let table;
  let selectedRowData = null;

  // Configurar Lightbox
  lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'albumLabel': "Imagen %1 de %2",
    'fadeDuration': 300,
    'imageFadeDuration': 300
  });

  // Crear partículas de fondo
  function createParticles() {
    const container = document.getElementById('particlesContainer');
    const particleCount = 12;
    
    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div');
      particle.classList.add('particle');
      
      const size = Math.random() * 5 + 2;
      const left = Math.random() * 100;
      const delay = Math.random() * 20;
      const duration = Math.random() * 10 + 20;
      
      particle.style.width = `${size}px`;
      particle.style.height = `${size}px`;
      particle.style.left = `${left}%`;
      particle.style.animationDelay = `${delay}s`;
      particle.style.animationDuration = `${duration}s`;
      
      container.appendChild(particle);
    }
  }

  $(document).ready(function() {
    createParticles();

    // Registrar el plugin para ordenar fechas
    $.fn.dataTable.moment('DD/MM/YYYY');

    table = $('#ticketsTable').DataTable({
      ajax: 'fetch_tickets.php',
      columns: [
        { data: 'Nombre' },
        { data: 'Correo' },
        { data: 'Prioridad' },
        { data: 'Empresa' },
        { data: 'Asunto' },
       { 
        data: 'Mensaje',
        render: function(data, type, row) {
            return '<div style="max-width:200px; white-space:normal; word-wrap:break-word;">' + data + '</div>';
        }
    },
        { data: 'Adjuntos' },
        { data: 'Fecha' },
        { data: 'Hora' },
        { data: 'Id_Ticket' },
        { data: 'Estatus' },
        { data: 'PA' },
        { data: 'FechaEnProceso' },
        { data: 'HoraEnProceso' },
        { data: 'FechaPausa' },
        { data: 'HoraPausa' },
        { data: 'FechaTerminado' },
        { data: 'HoraTerminado' },
        { data: 'FechaCancelado' },
        { data: 'HoraCancelado' },
        { 
          data: 'Imagen_URL',
          render: function(data, type, row) {
            if (data && data.trim() !== '') {
              const imageName = row.Imagen_Nombre || 'Imagen';
              return `
                <div class="image-cell">
                  <a href="${data}" data-lightbox="image-${row.Id_Ticket}" data-title="${imageName} - Ticket ${row.Id_Ticket}">
                    <img src="${data}" alt="${imageName}" class="img-thumbnail" 
                      onerror="this.src='https://via.placeholder.com/80x60?text=No+Image';" />
                  </a>
                  <div style="font-size: 10px; margin-top: 3px; color: rgba(255,255,255,0.6);">
                    ${row.Imagen_Size ? '(' + Math.round(row.Imagen_Size / 1024) + ' KB)' : ''}
                  </div>
                </div>
              `;
            } else {
              return '<div class="no-image">Sin imagen</div>';
            }
          },
          className: 'image-cell'
        }
      ],
      order: [[7, 'desc']],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
      },
      lengthMenu: [10, 25, 50],
      scrollY: '400px',
      scrollX: true,
      scrollCollapse: true
    });

    // Selección de fila
    $('#ticketsTable tbody').on('click', 'tr', function(e) {
      // No seleccionar si se hace clic en la imagen
      if ($(e.target).closest('a, img').length === 0) {
        if ($(this).hasClass('selected')) {
          $(this).removeClass('selected');
          selectedRowData = null;
        } else {
          table.$('tr.selected').removeClass('selected');
          $(this).addClass('selected');
          selectedRowData = table.row(this).data();
        }
      }
    });

    // Botón para abrir modal asignar
    $('#assignTicketBtn').on('click', function() {
      if (!selectedRowData) {
        Swal.fire({
          title: '⚠️ Selección Requerida',
          html: `
            <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; margin-bottom: 20px;">📋</div>
              <h3 style="color: #ffc107; margin-bottom: 15px;">Selecciona un Ticket</h3>
              <p style="color: #666;">Por favor selecciona un ticket de la tabla para poder asignarlo.</p>
            </div>
          `,
          icon: 'warning',
          confirmButtonColor: '#1e4e79',
          confirmButtonText: 'Entendido'
        });
        return;
      }

      // Establecer valores en el modal
      $('#ticketId').val(selectedRowData.Id_Ticket);
      
      // Buscar el responsable en las opciones del select
      if (selectedRowData.PA) {
        $('#responsable').val(selectedRowData.PA);
      } else {
        $('#responsable').val('');
      }
      
      $('#estatus').val(selectedRowData.Estatus || '');
      $('#asunto').val(selectedRowData.Asunto || '');

      let assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
      assignModal.show();
    });

    // ENVÍO DEL FORMULARIO
    $('#assignForm').on('submit', function(e) {
      e.preventDefault();

      // Crear objeto de datos simple
      let formData = {
        id_ticket: $('#ticketId').val(),
        responsable: $('#responsable').val(),
        estatus: $('#estatus').val(),
        asunto: $('#asunto').val()
      };

      console.log('Enviando datos:', formData);

      // Mostrar loading
      Swal.fire({
        title: '🔄 Actualizando Ticket',
        html: `
          <div style="text-align: center;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; margin: 20px 0;" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p style="color: #666; margin-top: 10px;">Procesando solicitud...</p>
          </div>
        `,
        showConfirmButton: false,
        allowOutsideClick: false
      });

      // AJAX simple
      $.ajax({
        url: 'update_ticket.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          console.log('Respuesta recibida:', response);
          Swal.close();
          
          if (response.success) {
            let notificationStatus = '';
            let icon = 'success';
            
            if (response.notifications) {
              if (response.notifications.user && response.notifications.admin) {
                notificationStatus = '<p style="color: #28a745; font-weight: bold; margin: 10px 0;">✅ Notificaciones enviadas</p>';
              } else if (response.notifications.user || response.notifications.admin) {
                notificationStatus = '<p style="color: #ffc107; font-weight: bold; margin: 10px 0;">⚠️ Notificación parcialmente enviada</p>';
                icon = 'warning';
              } else {
                notificationStatus = '<p style="color: #dc3545; font-weight: bold; margin: 10px 0;">❌ No se enviaron notificaciones</p>';
                icon = 'warning';
              }
            }

            Swal.fire({
              title: '🎉 ¡Éxito!',
              html: `
                <div style="text-align: center; padding: 20px;">
                  <div style="font-size: 60px; margin-bottom: 20px;">✅</div>
                  <h3 style="color: #28a745; margin-bottom: 15px;">Ticket Actualizado</h3>
                  <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                    <p style="margin: 5px 0;"><strong>Ticket ID:</strong> ${response.ticket_id}</p>
                    <p style="margin: 5px 0;"><strong>Estado:</strong> ${response.estatus}</p>
                    <p style="margin: 5px 0;"><strong>Responsable:</strong> ${response.responsable}</p>
                  </div>
                  ${notificationStatus}
                </div>
              `,
              icon: icon,
              confirmButtonColor: '#1e4e79',
              confirmButtonText: 'Continuar'
            }).then((result) => {
              table.ajax.reload(null, false);
              $('#assignModal').modal('hide');
              selectedRowData = null;
            });
          } else {
            Swal.fire({
              title: '❌ Error',
              html: `
                <div style="text-align: center; padding: 20px;">
                  <div style="font-size: 60px; margin-bottom: 20px;">😞</div>
                  <h3 style="color: #dc3545; margin-bottom: 15px;">Error al Actualizar</h3>
                  <p style="color: #666;">${response.msg}</p>
                </div>
              `,
              icon: 'error',
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'Entendido'
            });
          }
        },
        error: function(xhr, status, error) {
          Swal.close();
          console.log('Error completo:', xhr.responseText);
          
          let errorMsg = 'Error de conexión';
          try {
            const errorResponse = JSON.parse(xhr.responseText);
            errorMsg = errorResponse.msg || errorMsg;
          } catch (e) {
            errorMsg = xhr.responseText || error;
          }

          Swal.fire({
            title: '❌ Error del Servidor',
            html: `
              <div style="text-align: center; padding: 20px;">
                <div style="font-size: 60px; margin-bottom: 20px;">🔌</div>
                <h3 style="color: #dc3545; margin-bottom: 15px;">Error de Comunicación</h3>
                <p style="color: #666;">${errorMsg}</p>
              </div>
            `,
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Reintentar'
          });
        }
      });
    });

    // Exportar a Excel
    $('#exportExcelBtn').on('click', function() {
      Swal.fire({
        title: '📊 Preparando Exportación',
        html: `
          <div style="text-align: center;">
            <div class="spinner-border text-primary" role="status" style="margin: 20px 0;">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p style="color: #666;">Preparando archivo Excel...</p>
          </div>
        `,
        showConfirmButton: false,
        allowOutsideClick: false
      });

      setTimeout(() => {
        const dataToExport = [];
        const headers = [];
        $('#ticketsTable thead th').each(function() {
          headers.push($(this).text().trim());
        });
        dataToExport.push(headers);

        table.rows({ search: 'applied' }).every(function() {
          const rowData = this.data();
          dataToExport.push([
            rowData.Nombre,
            rowData.Correo,
            rowData.Prioridad,
            rowData.Empresa,
            rowData.Asunto,
            rowData.Mensaje,
            rowData.Adjuntos,
            rowData.Fecha,
            rowData.Hora,
            rowData.Id_Ticket,
            rowData.Estatus,
            rowData.PA,
            rowData.FechaEnProceso,
            rowData.HoraEnProceso,
            rowData.FechaPausa,
            rowData.HoraPausa,
            rowData.FechaTerminado,
            rowData.HoraTerminado,
            rowData.FechaCancelado,
            rowData.HoraCancelado,
            rowData.Imagen_URL || 'Sin imagen'
          ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(dataToExport);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Tickets');

        const fechaActual = new Date().toLocaleDateString('es-ES').replace(/\//g, '-');
        const fileName = `Tickets_BacroCorp_${fechaActual}.xlsx`;
        
        XLSX.writeFile(wb, fileName);
        
        Swal.close();
        
        Swal.fire({
          title: '✅ Exportación Exitosa',
          html: `
            <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; margin-bottom: 20px;">📄</div>
              <h3 style="color: #28a745; margin-bottom: 15px;">Archivo Generado</h3>
              <p style="color: #666;">El archivo <strong>${fileName}</strong> se ha descargado correctamente.</p>
            </div>
          `,
          icon: 'success',
          confirmButtonColor: '#1e4e79',
          confirmButtonText: 'Excelente'
        });
      }, 500);
    });

  });
</script>

</body>
</html>