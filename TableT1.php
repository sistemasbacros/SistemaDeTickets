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
require_once __DIR__ . '/auth_check.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes" />
    
    <title>Gestión de Tickets - BacroCorp</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Moderna pero Profesional (IGUAL QUE EL OTRO) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --navy-primary: #0a192f;
            --navy-secondary: #112240;
            --navy-dark: #020c1b;
            --accent-blue: #64a8ff;
            --accent-teal: #64ffda;
            --accent-purple: #7c3aed;
            --snow-white: #f8fafc;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-hover: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 8px 32px rgba(2, 12, 27, 0.3);
            --gradient-blue: linear-gradient(135deg, #1e3a8a, #3b82f6);
            --gradient-gold: linear-gradient(135deg, #f59e0b, #fbbf24);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --text-light: rgba(255, 255, 255, 0.95);
            --text-muted: rgba(255, 255, 255, 0.6);
            --border-radius-sm: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 20px;
            --border-radius-xl: 28px;
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(145deg, var(--navy-dark), var(--navy-primary), var(--navy-secondary));
            font-family: 'Outfit', sans-serif;
            color: white;
            min-height: 100vh;
            padding: var(--spacing-md);
            position: relative;
        }

        /* Tipografía Premium - IGUAL QUE EL OTRO */
        h1, h2, h3, h4, h5, h6, .btn-premium, .badge-premium, .table thead th {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        /* Layout Principal */
        .app-wrapper {
            max-width: 1800px;
            margin: 0 auto;
        }

        /* Header Premium */
        .header-premium {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-lg) var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-xl);
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
            box-shadow: var(--glass-shadow);
            border-left: 4px solid var(--accent-blue);
        }

        .header-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-blue), var(--accent-teal), transparent);
        }

        .logo-premium {
            width: 60px;
            height: 60px;
            background: var(--gradient-blue);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
            position: relative;
            overflow: hidden;
        }

        .logo-premium::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            20% { transform: translateX(100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .title-premium {
            flex: 1;
        }

        .title-premium h1 {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, white, var(--accent-teal));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .title-premium p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 4px 0 0 0;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .badge-group {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .badge-premium {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: var(--accent-blue);
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
            letter-spacing: 0.3px;
        }

        .badge-premium i {
            color: var(--accent-teal);
        }

        /* Tarjeta Usuario Premium */
        .user-card-premium {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg) var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-xl);
            flex-wrap: wrap;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--accent-blue-light);
        }

        .user-avatar-premium {
            width: 50px;
            height: 50px;
            background: var(--gradient-blue);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 8px 20px -5px rgba(59, 130, 246, 0.4);
        }

        .user-info-grid {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-xl);
            flex: 1;
        }

        .user-info-block {
            display: flex;
            flex-direction: column;
        }

        .user-info-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-info-value {
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-light);
        }

        .user-info-value i {
            color: var(--accent-blue);
            font-size: 0.9rem;
        }

        /* Acciones Premium */
        .actions-bar-premium {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            align-items: center;
            justify-content: space-between;
        }

        .btn-group-premium {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .btn-premium {
            background: var(--gradient-blue);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px -5px rgba(59, 130, 246, 0.4);
            letter-spacing: 0.5px;
        }

        .btn-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.6);
        }

        .btn-premium:hover::before {
            left: 100%;
        }

        .btn-excel-premium {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .btn-excel-premium:hover {
            background: rgba(16, 185, 129, 0.25);
            border-color: rgba(16, 185, 129, 0.5);
        }

        .btn-icon-premium {
            width: 48px;
            height: 48px;
            padding: 0;
            justify-content: center;
            border-radius: 14px;
        }

        .stats-info-premium {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.03);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid var(--glass-border);
        }

        /* ===== TABLA PREMIUM CON COLUMNAS PERFECTAMENTE MARCADAS ===== */
        .table-wrapper-premium {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .table-wrapper-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-blue), var(--accent-teal), transparent);
        }

        .table-header-premium {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .table-title-premium {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }

        .table-title-premium i {
            color: var(--accent-blue);
            font-size: 1.4rem;
        }

        .table-counter-premium {
            background: rgba(255,255,255,0.05);
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 0.85rem;
            border: 1px solid var(--glass-border);
            font-weight: 500;
        }

        /* DataTables Premium */
        .dataTables_wrapper {
            padding: 0;
        }

        .dataTables_length,
        .dataTables_filter {
            margin-bottom: var(--spacing-lg) !important;
        }

        .dataTables_length label,
        .dataTables_filter label {
            color: var(--text-muted) !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
        }

        .dataTables_length select,
        .dataTables_filter input {
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid var(--glass-border) !important;
            color: white !important;
            border-radius: 12px !important;
            padding: 8px 16px !important;
            font-size: 0.9rem !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .dataTables_length select:focus,
        .dataTables_filter input:focus {
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
            outline: none !important;
        }

        /* TABLA - COLUMNAS PERFECTAMENTE MARCADAS */
        table.dataTable {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 !important;
            font-family: 'Outfit', sans-serif;
        }

        /* ENCABEZADOS - Gradiente Premium */
        table.dataTable thead tr {
            background: var(--gradient-blue);
        }

        table.dataTable thead th {
            color: white !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 18px 12px !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            border-top: none !important;
            white-space: nowrap;
            font-family: 'Inter', sans-serif !important;
            position: relative;
        }

        /* BORDES VERTICALES ENTRE COLUMNAS */
        table.dataTable thead th:not(:last-child) {
            border-right: 2px solid rgba(255, 255, 255, 0.3) !important;
        }

        /* ESQUINAS REDONDEADAS */
        table.dataTable thead th:first-child {
            border-top-left-radius: 16px;
        }

        table.dataTable thead th:last-child {
            border-top-right-radius: 16px;
        }

        /* CUERPO DE TABLA */
        table.dataTable tbody tr {
            background-color: rgba(255, 255, 255, 0.02) !important;
            transition: var(--transition);
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        table.dataTable tbody tr:hover {
            background-color: rgba(100, 168, 255, 0.12) !important;
        }

        table.dataTable tbody tr.selected {
            background-color: rgba(59, 130, 246, 0.2) !important;
            border-left: 4px solid var(--accent-blue);
        }

        /* CELDAS CON BORDES COMPLETOS */
        table.dataTable tbody td {
            padding: 14px 12px !important;
            color: var(--text-light) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            vertical-align: middle;
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* BORDES VERTICALES EN CELDAS */
        table.dataTable tbody td:not(:last-child) {
            border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
        }

        /* PRIMERA CELDA CON BORDE IZQUIERDO */
        table.dataTable tbody td:first-child {
            border-left: 2px solid transparent;
            font-weight: 600;
        }

        /* Badges Premium */
        .badge-status-premium {
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            font-family: 'Inter', sans-serif;
        }

        .badge-proceso {
            background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(59,130,246,0.1));
            border-color: rgba(59,130,246,0.4);
            color: #93c5fd;
        }

        .badge-pausa {
            background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0.1));
            border-color: rgba(245,158,11,0.4);
            color: #fcd34d;
        }

        .badge-atendido {
            background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(16,185,129,0.1));
            border-color: rgba(16,185,129,0.4);
            color: #6ee7b7;
        }

        .badge-pendiente {
            background: linear-gradient(135deg, rgba(156,163,175,0.2), rgba(156,163,175,0.1));
            border-color: rgba(156,163,175,0.4);
            color: #d1d5db;
        }

        .badge-priority-premium {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 700;
            text-align: center;
            display: inline-block;
            min-width: 65px;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
            font-family: 'Inter', sans-serif;
        }

        .priority-alta {
            background: linear-gradient(135deg, rgba(239,68,68,0.2), rgba(239,68,68,0.1));
            border-color: rgba(239,68,68,0.4);
            color: #fca5a5;
        }

        .priority-media {
            background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(245,158,11,0.1));
            border-color: rgba(245,158,11,0.4);
            color: #fcd34d;
        }

        .priority-baja {
            background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(16,185,129,0.1));
            border-color: rgba(16,185,129,0.4);
            color: #6ee7b7;
        }

        /* Imagen Premium */
        .image-preview-premium {
            width: 55px;
            height: 40px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.2);
            cursor: pointer;
            transition: var(--transition);
            margin: 0 auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .image-preview-premium:hover {
            transform: scale(1.15);
            border-color: var(--accent-blue);
            box-shadow: 0 8px 25px rgba(59,130,246,0.3);
        }

        .image-preview-premium img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image-text-premium {
            color: var(--text-muted);
            font-size: 0.7rem;
            font-style: italic;
            font-weight: 400;
        }

        /* Paginación Premium */
        .dataTables_paginate {
            margin-top: var(--spacing-xl) !important;
        }

        .dataTables_paginate .paginate_button {
            padding: 8px 18px !important;
            margin: 0 5px !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.05) !important;
            color: white !important;
            border: 1px solid var(--glass-border) !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            transition: var(--transition) !important;
            font-family: 'Inter', sans-serif !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: var(--accent-blue) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59,130,246,0.3);
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--gradient-blue) !important;
            border-color: transparent !important;
            box-shadow: 0 5px 20px rgba(59,130,246,0.4);
        }

        /* Modal Premium */
        .modal-premium .modal-content {
            background: var(--navy-secondary);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-xl);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .modal-premium .modal-header {
            border-bottom: 1px solid var(--glass-border);
            padding: var(--spacing-lg) var(--spacing-xl);
            background: rgba(0,0,0,0.2);
        }

        .modal-premium .modal-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.3rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-premium .modal-body {
            padding: var(--spacing-xl);
        }

        .modal-premium .modal-footer {
            border-top: 1px solid var(--glass-border);
            padding: var(--spacing-lg) var(--spacing-xl);
            background: rgba(0,0,0,0.2);
        }

        .form-label-premium {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label-premium i {
            color: var(--accent-blue);
        }

        .form-select-premium {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            transition: var(--transition);
        }

        .form-select-premium:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
            outline: none;
        }

        .form-select-premium option {
            background: var(--navy-secondary);
            color: white;
            padding: 10px;
        }

        /* Scrollbar Premium */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--accent-blue), #3b82f6);
            border-radius: 10px;
            border: 2px solid transparent;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #3b82f6, var(--accent-blue));
        }

        /* Responsive Premium */
        @media (max-width: 1400px) {
            .app-wrapper { max-width: 1400px; }
        }

        @media (max-width: 1200px) {
            .user-info-grid { gap: var(--spacing-lg); }
        }

        @media (max-width: 992px) {
            .header-premium { flex-direction: column; align-items: flex-start; }
            .badge-group { width: 100%; }
            .user-info-grid { gap: var(--spacing-md); }
            .actions-bar-premium { flex-direction: column; align-items: flex-start; }
            .btn-group-premium { width: 100%; }
            .btn-premium { flex: 1; justify-content: center; }
        }

        @media (max-width: 768px) {
            .app-wrapper { padding: 0; }
            .header-premium { padding: var(--spacing-md); }
            .user-card-premium { padding: var(--spacing-md); }
            .user-info-grid { flex-direction: column; gap: var(--spacing-sm); }
            .user-info-block { width: 100%; }
            .table-wrapper-premium { padding: var(--spacing-md); overflow-x: auto; }
            
            table.dataTable { min-width: 1300px; }
            table.dataTable thead th { font-size: 0.7rem !important; padding: 12px 6px !important; }
            table.dataTable tbody td { font-size: 0.7rem !important; padding: 10px 6px !important; }
            
            .dataTables_length,
            .dataTables_filter {
                text-align: left !important;
                width: 100% !important;
            }
            
            .dataTables_filter input {
                width: 100% !important;
                margin-top: 8px !important;
            }

            .btn-group-premium { flex-direction: column; }
            .btn-premium { width: 100%; }
            .btn-icon-premium { width: 100%; }
        }

        @media (max-width: 576px) {
            .header-premium { padding: var(--spacing-sm); }
            .title-premium h1 { font-size: 1.3rem; }
            .badge-premium { width: 100%; justify-content: center; }
            .user-card-premium { padding: var(--spacing-sm); }
            .user-avatar-premium { width: 40px; height: 40px; font-size: 1rem; }
            .table-wrapper-premium { padding: var(--spacing-sm); }
            .modal-dialog { margin: 10px; }
        }

        /* Utilidades Premium */
        .text-accent { color: var(--accent-blue); }
        .text-gradient { background: linear-gradient(135deg, white, var(--accent-teal)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .font-inter { font-family: 'Inter', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>
</head>

<body>
    <div class="app-wrapper">
        <!-- Header Premium -->
        <div class="header-premium">
            <div class="logo-premium">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="title-premium">
                <h1>GESTIÓN DE TICKETS</h1>
                <p>Sistema de Administración y Seguimiento de Tickets de Soporte</p>
            </div>
            <div class="badge-group">
                <span class="badge-premium">
                    <i class="fas fa-shield-alt"></i> Panel Administrativo
                </span>
                <span class="badge-premium">
                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i'); ?>
                </span>
            </div>
        </div>

        <!-- Tarjeta Usuario Premium -->
        <div class="user-card-premium">
            <div class="user-avatar-premium">
                <?php echo substr($nombre_usuario, 0, 2); ?>
            </div>
            <div class="user-info-grid">
                <div class="user-info-block">
                    <span class="user-info-label">Usuario</span>
                    <span class="user-info-value">
                        <i class="fas fa-user-check" style="color: var(--accent-blue);"></i> <?php echo htmlspecialchars($nombre_usuario); ?>
                    </span>
                </div>
                <div class="user-info-block">
                    <span class="user-info-label">Área</span>
                    <span class="user-info-value">
                        <i class="fas fa-building" style="color: var(--accent-blue);"></i> <?php echo htmlspecialchars($area_usuario); ?>
                    </span>
                </div>
                <div class="user-info-block">
                    <span class="user-info-label">ID</span>
                    <span class="user-info-value">
                        <i class="fas fa-id-badge" style="color: var(--accent-blue);"></i> <?php echo htmlspecialchars($id_empleado); ?>
                    </span>
                </div>
                <div class="user-info-block">
                    <span class="user-info-label">Username</span>
                    <span class="user-info-value">
                        <i class="fas fa-user" style="color: var(--accent-blue);"></i> <?php echo htmlspecialchars($usuario); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Acciones Premium -->
        <div class="actions-bar-premium">
            <div class="btn-group-premium">
                <a href="dashboard.php" class="btn-premium">
                    <i class="fas fa-home"></i> INICIO
                </a>
                <button class="btn-premium" id="assignTicketBtn">
                    <i class="fas fa-user-check"></i> ASIGNAR
                </button>
                <button class="btn-premium btn-excel-premium btn-icon-premium" id="exportExcelBtn" title="Exportar a Excel">
                    <i class="fas fa-file-excel"></i>
                </button>
            </div>
            <div class="stats-info-premium">
                <i class="fas fa-database"></i>
                <span id="ticketCount">Cargando tickets...</span>
            </div>
        </div>

        <!-- Tabla Premium con Columnas Marcadas -->
        <div class="table-wrapper-premium">
            <div class="table-header-premium">
                <div class="table-title-premium">
                    <i class="fas fa-table"></i>
                    Listado de Tickets
                </div>
                <div class="table-counter-premium" id="totalCounter">0 tickets</div>
            </div>

            <div class="table-responsive">
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
                            <th>F.PROCESO</th>
                            <th>H.PROCESO</th>
                            <th>F.PAUSA</th>
                            <th>H.PAUSA</th>
                            <th>F.TERMINADO</th>
                            <th>H.TERMINADO</th>
                            <th>F.CANCELADO</th>
                            <th>H.CANCELADO</th>
                            <th>IMAGEN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="21" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Cargando tickets...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Asignar Premium -->
    <div class="modal fade modal-premium" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check" style="color: var(--accent-blue);"></i>
                        Asignar Ticket
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="assignForm">
                    <div class="modal-body">
                        <input type="hidden" id="ticketId" name="ticketId">
                        
                        <div class="mb-4">
                            <label class="form-label-premium">
                                <i class="fas fa-user-tie"></i>
                                Responsable
                            </label>
                            <select id="responsable" name="responsable" class="form-select-premium" required>
                                <option value="">Seleccione responsable</option>
                                <option value="Ariel Antonio Sanchez">Ariel Antonio Sanchez</option>
                                <option value="Alfredo Rosales Ramirez">Alfredo Rosales Ramirez</option>
                                <option value="Boris Kelvin Ramirez Neyra">Boris Kelvin Ramirez Neyra</option>
                                <option value="Luis Antonio Romero López">Luis Antonio Romero López</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-premium">
                                <i class="fas fa-flag"></i>
                                Estatus
                            </label>
                            <select id="estatus" name="estatus" class="form-select-premium" required>
                                <option value="">Seleccione estatus</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="Pausa">Pausa</option>
                                <option value="Atendido">Atendido</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-premium">
                                <i class="fas fa-tags"></i>
                                Asunto
                            </label>
                            <select id="asunto" name="asunto" class="form-select-premium" required>
                                <option value="">Seleccione asunto</option>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.1); border:none; color:white; padding:12px 24px; border-radius:12px;">Cancelar</button>
                        <button type="submit" class="btn-premium" style="padding:12px 32px;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <script>
        let table;
        let selectedRowData = null;

        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "Imagen %1 de %2",
            'fadeDuration': 300
        });

        $(document).ready(function() {
            $.fn.dataTable.moment('DD/MM/YYYY');

            table = $('#ticketsTable').DataTable({
                ajax: {
                    url: 'fetch_tickets.php',
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    dataSrc: function(json) {
                        console.log('Datos recibidos:', json);
                        
                        if (Array.isArray(json)) {
                            let count = json.length;
                            $('#ticketCount').text(count + ' tickets');
                            $('#totalCounter').text(count + ' tickets');
                            return json;
                        }
                        
                        if (json.data && Array.isArray(json.data)) {
                            let count = json.data.length;
                            $('#ticketCount').text(count + ' tickets');
                            $('#totalCounter').text(count + ' tickets');
                            return json.data;
                        }
                        
                        $('#ticketCount').text('0 tickets');
                        $('#totalCounter').text('0 tickets');
                        return [];
                    },
                    error: function() {
                        $('#ticketCount').text('Error de conexión');
                        $('#totalCounter').text('0 tickets');
                        return [];
                    }
                },
                columns: [
                    { data: 'Nombre' },
                    { data: 'Correo' },
                    { 
                        data: 'Prioridad',
                        render: function(data) {
                            if (!data) return '<span class="badge-priority-premium priority-media">-</span>';
                            let pClass = 'priority-media';
                            let dLower = data.toLowerCase();
                            if (dLower.includes('alt') || dLower.includes('urg')) pClass = 'priority-alta';
                            else if (dLower.includes('baj')) pClass = 'priority-baja';
                            return `<span class="badge-priority-premium ${pClass}">${data}</span>`;
                        }
                    },
                    { data: 'Empresa' },
                    { data: 'Asunto' },
                    { 
                        data: 'Mensaje',
                        render: function(data) {
                            if (!data) return '<span class="text-muted">-</span>';
                            let short = data.length > 40 ? data.substring(0, 40) + '…' : data;
                            return `<span title="${data.replace(/"/g, '&quot;')}" style="cursor:help; max-width:150px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:400;">${short}</span>`;
                        }
                    },
                    { data: 'Adjuntos' },
                    { data: 'Fecha' },
                    { data: 'Hora' },
                    { data: 'Id_Ticket' },
                    { 
                        data: 'Estatus',
                        render: function(data) {
                            if (!data) return '<span class="badge-status-premium badge-pendiente">Pendiente</span>';
                            let badgeClass = 'badge-pendiente';
                            if (data.includes('Proceso')) badgeClass = 'badge-proceso';
                            else if (data.includes('Pausa')) badgeClass = 'badge-pausa';
                            else if (data.includes('Atendido')) badgeClass = 'badge-atendido';
                            return `<span class="badge-status-premium ${badgeClass}"><i class="fas fa-circle" style="font-size:6px;"></i> ${data}</span>`;
                        }
                    },
                    { 
                        data: 'PA',
                        render: function(data) {
                            return data || '<span class="text-muted">-</span>';
                        }
                    },
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
                            if (data && data.trim()) {
                                return `
                                    <div style="text-align:center;">
                                        <a href="${data}" data-lightbox="image-${row.Id_Ticket}" data-title="Ticket ${row.Id_Ticket}">
                                            <div class="image-preview-premium">
                                                <img src="${data}" onerror="this.src='https://via.placeholder.com/55x40?text=No'">
                                            </div>
                                        </a>
                                    </div>
                                `;
                            }
                            return '<span class="no-image-text-premium">Sin imagen</span>';
                        }
                    }
                ],
                order: [[7, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
                    emptyTable: "No hay tickets disponibles",
                    zeroRecords: "No se encontraron tickets",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ tickets",
                    infoEmpty: "Mostrando 0 a 0 de 0 tickets",
                    infoFiltered: "(filtrado de _MAX_ tickets totales)"
                },
                lengthMenu: [10, 25, 50, 100],
                pageLength: 25,
                scrollY: '550px',
                scrollX: true,
                scrollCollapse: true,
                fixedHeader: true,
                autoWidth: true,
                drawCallback: function() {
                    let info = this.api().page.info();
                    $('#totalCounter').text(info.recordsTotal + ' tickets');
                }
            });

            // Selección de fila
            $('#ticketsTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a, img').length) return;
                
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                    selectedRowData = null;
                } else {
                    table.$('tr.selected').removeClass('selected');
                    $(this).addClass('selected');
                    selectedRowData = table.row(this).data();
                }
            });

            // Botón asignar
            $('#assignTicketBtn').on('click', function() {
                if (!selectedRowData) {
                    Swal.fire({
                        title: 'Selecciona un ticket',
                        text: 'Debes seleccionar un ticket de la tabla',
                        icon: 'warning',
                        confirmButtonColor: '#64a8ff',
                        background: '#112240',
                        color: 'white'
                    });
                    return;
                }

                $('#ticketId').val(selectedRowData.Id_Ticket);
                $('#responsable').val(selectedRowData.PA || '');
                $('#estatus').val(selectedRowData.Estatus || '');
                $('#asunto').val(selectedRowData.Asunto || '');

                new bootstrap.Modal(document.getElementById('assignModal')).show();
            });

            // Envío del formulario
            $('#assignForm').on('submit', function(e) {
                e.preventDefault();

                let formData = {
                    id_ticket: $('#ticketId').val(),
                    responsable: $('#responsable').val(),
                    estatus: $('#estatus').val(),
                    asunto: $('#asunto').val()
                };

                if (!formData.responsable || !formData.estatus || !formData.asunto) {
                    Swal.fire({
                        title: 'Campos incompletos',
                        icon: 'warning',
                        confirmButtonColor: '#64a8ff',
                        background: '#112240',
                        color: 'white'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Actualizando...',
                    html: '<div class="spinner-border text-primary"></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    background: '#112240'
                });

                $.ajax({
                    url: 'update_ticket.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                title: '¡Ticket actualizado!',
                                icon: 'success',
                                confirmButtonColor: '#64a8ff',
                                background: '#112240',
                                color: 'white'
                            }).then(() => {
                                table.ajax.reload(null, false);
                                $('#assignModal').modal('hide');
                                selectedRowData = null;
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.msg || 'Error al actualizar',
                                icon: 'error',
                                confirmButtonColor: '#dc3545',
                                background: '#112240'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            title: 'Error de conexión',
                            icon: 'error',
                            confirmButtonColor: '#dc3545',
                            background: '#112240'
                        });
                    }
                });
            });

            // Exportar Excel
            $('#exportExcelBtn').on('click', function() {
                Swal.fire({
                    title: 'Exportando...',
                    html: '<div class="spinner-border text-primary"></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    background: '#112240'
                });

                setTimeout(() => {
                    try {
                        const data = [];
                        const headers = [];
                        $('#ticketsTable thead th').each(function() {
                            headers.push($(this).text().trim());
                        });
                        data.push(headers);

                        table.rows({ search: 'applied' }).every(function() {
                            const r = this.data();
                            data.push([
                                r.Nombre || '', r.Correo || '', r.Prioridad || '', r.Empresa || '',
                                r.Asunto || '', r.Mensaje || '', r.Adjuntos || '', r.Fecha || '',
                                r.Hora || '', r.Id_Ticket || '', r.Estatus || '', r.PA || '',
                                r.FechaEnProceso || '', r.HoraEnProceso || '', r.FechaPausa || '',
                                r.HoraPausa || '', r.FechaTerminado || '', r.HoraTerminado || '',
                                r.FechaCancelado || '', r.HoraCancelado || '', r.Imagen_URL || 'Sin imagen'
                            ]);
                        });

                        const ws = XLSX.utils.aoa_to_sheet(data);
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
                        
                        const fecha = new Date().toLocaleDateString('es-ES').replace(/\//g, '-');
                        XLSX.writeFile(wb, `Tickets_BacroCorp_${fecha}.xlsx`);

                        Swal.close();
                        Swal.fire({
                            title: 'Exportación completada',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#112240'
                        });
                    } catch (e) {
                        console.error('Error al exportar:', e);
                        Swal.close();
                        Swal.fire({
                            title: 'Error al exportar',
                            icon: 'error',
                            background: '#112240'
                        });
                    }
                }, 500);
            });

            $(window).on('resize', function() {
                if (table) table.columns.adjust();
            });
        });
    </script>
</body>
</html>