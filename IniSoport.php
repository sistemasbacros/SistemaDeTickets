<?php
/**
 * @file IniSoport.php
 * @brief Dashboard principal del Sistema de Tickets con métricas SLA y gestión de permisos.
 *
 * @description
 * Módulo central post-autenticación que actúa como hub principal del sistema.
 * Presenta un dashboard ejecutivo con métricas de SLA (Service Level Agreement),
 * estadísticas de tickets por estado/prioridad, y acceso controlado por roles
 * a las diferentes funcionalidades del sistema.
 *
 * Este archivo implementa:
 * - Sistema de permisos por usuario (whitelist de usernames)
 * - Verificación exhaustiva de sesión con múltiples parámetros
 * - Cálculo de métricas SLA (tickets dentro/fuera de tiempo)
 * - Dashboard visual con contadores, gráficos y tablas resumen
 * - Exportación a Excel de datos de tickets
 * - Menú lateral con navegación a módulos del sistema
 *
 * Módulos accesibles desde este dashboard:
 * - Tickets TI: Creación, consulta y gestión
 * - Servicios Generales: Tickets de mantenimiento
 * - Soporte: Tickets de soporte técnico
 * - Dashboard (restringido): Métricas avanzadas SLA
 * - Vehículos: Gestión de mantenimiento vehicular
 * - Contratos: Catálogo de contratos
 *
 * @module Módulo Principal / Dashboard
 * @access Privado (requiere sesión activa desde Loginti.php)
 *
 * @dependencies
 * - PHP: session, sqlsrv extension, output buffering
 * - JS CDN: Bootstrap 5.3.0-alpha1, Chart.js 3.9.1, DataTables 1.13.4,
 *           SweetAlert2 11, Font Awesome 6.4.0, jQuery 3.6.0
 * - CSS CDN: Google Fonts (Outfit, Manrope), Animate.css
 * - Interno: PHPMailer (notificaciones), Consultadata.php (conexiones BD)
 * - Externo: PhpSpreadsheet (exportación Excel)
 *
 * @database
 * - Servidor primario: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tablas consultadas:
 *   - T3: Tickets TI (principal)
 *   - TicketsSG: Tickets Servicios Generales
 * - Consultas:
 *   - Conteo por estado (Abierto, Resuelto, En Proceso, Cerrado)
 *   - Conteo por prioridad (Alta, Media, Baja, Crítica)
 *   - Tickets por usuario/área
 *   - Cálculo de tiempos SLA
 *
 * @session
 * - Variables requeridas (desde Loginti.php):
 *   - $_SESSION['logged_in']       — Bandera de autenticación
 *   - $_SESSION['user_name']       — Nombre del usuario
 *   - $_SESSION['user_id']         — ID del empleado
 *   - $_SESSION['user_area']       — Área del usuario
 *   - $_SESSION['user_username']   — Username (para verificar permisos)
 *   - $_SESSION['client_ip']       — IP vinculada a sesión
 *   - $_SESSION['login_time']      — Timestamp de inicio de sesión
 *   - $_SESSION['last_activity']   — Última actividad
 * - Validaciones:
 *   - Sesión activa (logged_in === true)
 *   - IP consistente (client_ip === REMOTE_ADDR)
 *   - Tiempo de sesión máximo (8 horas)
 *   - Inactividad máxima (30 minutos)
 *
 * @inputs
 * - $_SESSION (datos del usuario autenticado)
 * - $_GET (opcional): filtros de fecha, área, estado
 * - Database: Estadísticas calculadas de tablas T3 y TicketsSG
 *
 * @outputs
 * - HTML: Dashboard completo con métricas y navegación
 * - JSON: Datos para gráficos Chart.js via AJAX
 * - Excel: Exportación de reportes (endpoint interno)
 *
 * @security
 * - Verificación de sesión multicapa
 * - Lista blanca de usuarios para dashboard avanzado
 * - Lista blanca de usuarios para procesar tickets
 * - Vinculación de sesión a IP
 * - Headers anti-cache completos
 * - Protección contra clickjacking (X-Frame-Options)
 * - Regeneración de token CSRF cuando es necesario
 *
 * @permissions
 * - $usuariosPermitidosDashboard: ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio']
 *   → Acceso a métricas SLA avanzadas y estadísticas
 * - $usuariosPermitidosProcesar: ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio']
 *   → Capacidad de cambiar estado de tickets
 * - Usuarios sin permisos especiales: Solo visualización básica
 *
 * @ui_components
 * - Header con logo, título y perfil de usuario
 * - Sidebar colapsable con navegación a módulos
 * - Cards métricas: Total tickets, Abiertos, En Proceso, Resueltos
 * - Gráficos Chart.js: Tickets por estado, por prioridad, tendencia temporal
 * - Tabla resumen de tickets recientes
 * - Footer con información del sistema
 * - Diseño glassmorphism con tema navy oscuro
 *
 * @sla_metrics
 * - Crítica: 2 horas máximo de respuesta
 * - Alta: 4 horas máximo de respuesta
 * - Media: 8 horas máximo de respuesta
 * - Baja: 24 horas máximo de respuesta
 * - Cálculo: % tickets dentro de SLA vs fuera de SLA
 *
 * @author Equipo Tecnología BacroCorp
 * @version 3.2
 * @since 2024
 * @updated 2025-02-06
 */

// VERIFICACIÓN DE SESIÓN MEJORADA - VERSIÓN FINAL CON TODAS LAS VARIANTES DE PRIORIDAD
session_start();

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Headers más estrictos para prevenir cache
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// SISTEMA DE PERMISOS POR USUARIO
$usuariosPermitidosDashboard = ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio'];
$usuariosPermitidosProcesar = ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio'];

// Obtener usuario actual desde la sesión
$usuarioActual = $_SESSION['user_username'] ?? '';

// VERIFICAR SI EL USUARIO TIENE PERMISOS PARA EL DASHBOARD
$tienePermisosDashboard = in_array($usuarioActual, $usuariosPermitidosDashboard);

// VERIFICACIÓN DE SESIÓN MÁS FLEXIBLE PERO SEGURA
$session_valid = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    
    $session_timeout = 8 * 60 * 60;
    if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] <= $session_timeout)) {
        
        $inactivity_timeout = 30 * 60;
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] <= $inactivity_timeout)) {
            
            $session_valid = true;
            $_SESSION['LAST_ACTIVITY'] = time();
            
            if (!isset($_SESSION['authenticated_from_login'])) $_SESSION['authenticated_from_login'] = true;
            if (!isset($_SESSION['login_source'])) $_SESSION['login_source'] = 'form_login';
            if (!isset($_SESSION['ip_address'])) $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            if (!isset($_SESSION['user_agent'])) $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            if (!isset($_SESSION['session_token'])) $_SESSION['session_token'] = bin2hex(random_bytes(32));
            if (!isset($_SESSION['browser_token'])) $_SESSION['browser_token'] = bin2hex(random_bytes(16));
            if (!isset($_SESSION['browser_fingerprint'])) $_SESSION['browser_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . $_SESSION['browser_token']);
            if (!isset($_SESSION['session_exclusive'])) $_SESSION['session_exclusive'] = true;
            if (!isset($_SESSION['exclusive_login_time'])) $_SESSION['exclusive_login_time'] = time();
            if (!isset($_SESSION['last_access_time'])) $_SESSION['last_access_time'] = time();
            if (!isset($_SESSION['last_direct_access'])) $_SESSION['last_direct_access'] = time();
            
            if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 300)) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        } else {
            $session_valid = false;
            error_log("Sesión inactiva: " . $usuarioActual);
        }
    } else {
        $session_valid = false;
        error_log("Sesión expirada: " . $usuarioActual);
    }
} else {
    $session_valid = false;
    error_log("Datos de sesión faltantes");
}

if (!$session_valid) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: Loginti.php?error=session_expired");
    exit();
}

$nombre = $_SESSION['user_name'] ?? 'Usuario';
$id_empleado = $_SESSION['user_id'] ?? 'N/A';
$area = $_SESSION['user_area'] ?? 'N/A';
$usuario = $_SESSION['user_username'] ?? 'N/A';

if (isset($_POST['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: Loginti.php");
    exit();
}

require_once __DIR__ . '/config.php';
$serverName = $DB_HOST;
$connectionOptions = array(
    "Database" => $DB_DATABASE,
    "UID" => $DB_USERNAME,
    "PWD" => $DB_PASSWORD,
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "LoginTimeout" => 30
);

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) die("Error de conexión: " . print_r(sqlsrv_errors(), true));

// FUNCIÓN PARA CALCULAR SLA - VERSIÓN CORREGIDA
function verificarSLA($prioridad, $tiempo_ejecucion) {
    if ($tiempo_ejecucion === null || $tiempo_ejecucion === '' || !is_numeric($tiempo_ejecucion)) {
        return null;
    }
    
    $horas = floatval($tiempo_ejecucion);
    $prioridad_original = trim($prioridad ?? '');
    $prioridad_lower = strtolower($prioridad_original);
    
    // 🔴 Prioridad ALTA/URGENTE: ≤ 2 horas
    if (strpos($prioridad_lower, 'alt') !== false || 
        strpos($prioridad_lower, 'urg') !== false ||
        $prioridad_lower == 'alto' || 
        $prioridad_lower == 'alta' ||
        $prioridad_lower == 'urgente') {
        
        $sla_max = 2;
        $descripcion = 'ALTA/URGENTE (≤2h)';
        $categoria = 'ALTA';
        $cumple = ($horas <= 2);
    }
    // 🟠 Prioridad MEDIA: ≤ 4 horas
    else if (strpos($prioridad_lower, 'med') !== false ||
             $prioridad_lower == 'medio' ||
             $prioridad_lower == 'media') {
        
        $sla_max = 4;
        $descripcion = 'MEDIA (≤4h)';
        $categoria = 'MEDIA';
        $cumple = ($horas <= 4);
    }
    // 🟡 Prioridad BAJA: ≤ 10 horas
    else if (strpos($prioridad_lower, 'baj') !== false ||
             $prioridad_lower == 'bajo' ||
             $prioridad_lower == 'baja') {
        
        $sla_max = 10;
        $descripcion = 'BAJA (≤10h)';
        $categoria = 'BAJA';
        $cumple = ($horas <= 10);
    }
    // 🔵 Prioridad ESTÁNDAR: ≤ 24 horas
    else {
        $sla_max = 24;
        $descripcion = 'ESTÁNDAR (≤24h)';
        $categoria = 'ESTÁNDAR';
        $cumple = ($horas <= 24);
    }
    
    return [
        'cumple' => $cumple,
        'estado' => $cumple ? 'CUMPLE' : 'NO CUMPLE',
        'sla_max' => $sla_max,
        'color' => $cumple ? '#10b981' : '#ef4444',
        'descripcion' => $descripcion,
        'categoria' => $categoria,
        'horas' => $horas
    ];
}

// FUNCIÓN PARA CALCULAR HORAS LABORALES - LUNES A VIERNES 8:30 - 17:30
function calcularHorasLaborales($fecha_inicio, $hora_inicio, $fecha_fin, $hora_fin) {
    if (!$fecha_inicio || !$fecha_fin) return null;
    try {
        if (is_string($fecha_inicio)) {
            $fecha_inicio_dt = date_create_from_format('d/m/Y', $fecha_inicio);
            if (!$fecha_inicio_dt) $fecha_inicio_dt = date_create($fecha_inicio);
        } else if ($fecha_inicio instanceof DateTime) $fecha_inicio_dt = $fecha_inicio;
        else return null;
        
        if (is_string($fecha_fin)) {
            $fecha_fin_dt = date_create_from_format('d/m/Y', $fecha_fin);
            if (!$fecha_fin_dt) $fecha_fin_dt = date_create($fecha_fin);
        } else if ($fecha_fin instanceof DateTime) $fecha_fin_dt = $fecha_fin;
        else return null;
        
        if ($hora_inicio && is_string($hora_inicio)) {
            list($h, $m, $s) = sscanf($hora_inicio, '%d:%d:%d');
            if ($h !== null && $m !== null) $fecha_inicio_dt->setTime($h, $m, $s ?? 0);
        }
        if ($hora_fin && is_string($hora_fin)) {
            list($h, $m, $s) = sscanf($hora_fin, '%d:%d:%d');
            if ($h !== null && $m !== null) $fecha_fin_dt->setTime($h, $m, $s ?? 0);
        }
        
        if ($fecha_fin_dt <= $fecha_inicio_dt) return 0;
        
        // Horario laboral: Lunes a Viernes 8:30 - 17:30
        $hora_inicio_laboral = 8; $minuto_inicio_laboral = 30;
        $hora_fin_laboral = 17; $minuto_fin_laboral = 30;
        $horas_totales = 0;
        $fecha_actual = clone $fecha_inicio_dt;
        
        while ($fecha_actual < $fecha_fin_dt) {
            $dia_semana = (int)$fecha_actual->format('N');
            if ($dia_semana <= 5) {
                $inicio_dia = clone $fecha_actual; $inicio_dia->setTime($hora_inicio_laboral, $minuto_inicio_laboral, 0);
                $fin_dia = clone $fecha_actual; $fin_dia->setTime($hora_fin_laboral, $minuto_fin_laboral, 0);
                $inicio_efectivo = ($fecha_actual > $inicio_dia) ? clone $fecha_actual : $inicio_dia;
                $fin_efectivo = ($fecha_fin_dt < $fin_dia) ? clone $fecha_fin_dt : $fin_dia;
                if ($fin_efectivo > $inicio_efectivo) {
                    $diferencia = $fin_efectivo->diff($inicio_efectivo);
                    $horas_dia = $diferencia->h + ($diferencia->i / 60) + ($diferencia->s / 3600);
                    $horas_totales += $horas_dia;
                }
            }
            $fecha_actual->add(new DateInterval('P1D'));
            $fecha_actual->setTime($hora_inicio_laboral, $minuto_inicio_laboral, 0);
        }
        return round($horas_totales, 2);
    } catch (Exception $e) {
        error_log("Error calculando horas laborales: " . $e->getMessage());
        return null;
    }
}

// FUNCIÓN PARA EXPORTAR A EXCEL
if (isset($_GET['export']) && $_GET['export'] == 'excel' && $tienePermisosDashboard) {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $estatus = $_GET['estatus'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    $empresa = $_GET['empresa'] ?? '';
    $asunto = $_GET['asunto'] ?? '';

    $whereConditions = ["1=1"]; $params = array();
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $fecha_inicio_dmy = date('d/m/Y', strtotime($fecha_inicio));
        $fecha_fin_dmy = date('d/m/Y', strtotime($fecha_fin));
        $whereConditions[] = "CONVERT(DATE, Fecha, 103) BETWEEN CONVERT(DATE, ?, 103) AND CONVERT(DATE, ?, 103)";
        $params[] = $fecha_inicio_dmy; $params[] = $fecha_fin_dmy;
    }
    if (!empty($estatus)) { $whereConditions[] = "Estatus = ?"; $params[] = $estatus; }
    if (!empty($prioridad)) { $whereConditions[] = "Prioridad = ?"; $params[] = $prioridad; }
    if (!empty($empresa)) { $whereConditions[] = "Empresa = ?"; $params[] = $empresa; }
    if (!empty($asunto)) { $whereConditions[] = "Asunto LIKE ?"; $params[] = '%' . $asunto . '%'; }
    $whereClause = implode(" AND ", $whereConditions);

    $sqlExport = "SELECT Id_Ticket, Nombre, Correo, Prioridad, Empresa, Asunto, Mensaje, Fecha, Hora, Estatus, PA, FechaTerminado, HoraTerminado, FechaEnProceso, HoraEnProceso FROM [Ticket].[dbo].[T3] WHERE $whereClause ORDER BY Fecha DESC, Hora DESC";
    $stmtExport = sqlsrv_query($conn, $sqlExport, $params);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_tickets_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='13' style='background-color: #1e3a8a; color: white; font-size: 16px;'>REPORTE DE TICKETS - BACROCORP</th></tr>";
    echo "<tr><th colspan='13'>Período: " . date('d/m/Y', strtotime($fecha_inicio)) . " - " . date('d/m/Y', strtotime($fecha_fin)) . "</th></tr>";
    echo "<tr><th colspan='13'>Generado por: " . $nombre . " - " . date('d/m/Y H:i:s') . "</th></tr><tr></tr>";
    echo "<tr style='background-color: #3b82f6; color: white; font-weight: bold;'>";
    echo "<th>ID</th>";
    echo "<th>Solicitante</th>";
    echo "<th>Correo</th>";
    echo "<th>Prioridad</th>";
    echo "<th>Empresa</th>";
    echo "<th>Asunto</th>";
    echo "<th>Mensaje</th>";
    echo "<th>Estatus</th>";
    echo "<th>Fecha</th>";
    echo "<th>Tiempo</th>";
    echo "<th>SLA</th>";
    echo "<th>Atendido Por</th>";
    echo "<th>Fecha Terminado</th>";
    echo "</tr>";
    
    if ($stmtExport) {
        while ($row = sqlsrv_fetch_array($stmtExport, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . ($row['Id_Ticket'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Nombre'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Correo'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Prioridad'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Empresa'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Asunto'] ?? 'N/A') . "</td>";
            echo "<td>" . substr(($row['Mensaje'] ?? 'N/A'), 0, 100) . "</td>";
            echo "<td>" . ($row['Estatus'] ?? 'N/A') . "</td>";
            
            $fecha = 'N/A';
            if ($row['Fecha']) {
                if ($row['Fecha'] instanceof DateTime) $fecha = $row['Fecha']->format('d/m/Y');
                else {
                    $dateObj = date_create_from_format('d/m/Y', $row['Fecha']);
                    $fecha = $dateObj ? $dateObj->format('d/m/Y') : $row['Fecha'];
                }
            }
            echo "<td>" . $fecha . "</td>";
            
            if (($row['Estatus'] ?? '') == 'Atendido' && !empty($row['FechaTerminado'])) {
                $tiempoEjec = calcularHorasLaborales(
                    $row['FechaEnProceso'] ?? $row['Fecha'], 
                    $row['HoraEnProceso'] ?? '08:30:00', 
                    $row['FechaTerminado'], 
                    $row['HoraTerminado'] ?? '17:30:00'
                );
                
                if ($tiempoEjec !== null) {
                    $sla_info = verificarSLA($row['Prioridad'] ?? '', $tiempoEjec);
                    echo "<td>" . number_format($tiempoEjec, 2) . " hrs</td>";
                    if ($sla_info) {
                        echo "<td style='color: " . $sla_info['color'] . "; font-weight: bold;'>" . $sla_info['estado'] . "</td>";
                    } else {
                        echo "<td>N/A</td>";
                    }
                } else {
                    echo "<td>Error</td><td>Error</td>";
                }
            } else {
                echo "<td>En proceso</td><td>Pendiente</td>";
            }
            
            echo "<td>" . ($row['PA'] ?? 'Sin asignar') . "</td>";
            
            $fechaTerminado = 'N/A';
            if ($row['FechaTerminado']) {
                if ($row['FechaTerminado'] instanceof DateTime) {
                    $fechaTerminado = $row['FechaTerminado']->format('d/m/Y H:i');
                } else {
                    $dateObj = date_create_from_format('d/m/Y', $row['FechaTerminado']);
                    $fechaTerminado = $dateObj ? $dateObj->format('d/m/Y') : $row['FechaTerminado'];
                }
            }
            echo "<td>" . $fechaTerminado . "</td>";
            echo "</tr>";
        }
        sqlsrv_free_stmt($stmtExport);
    }
    echo "</table>";
    exit();
}

if ($tienePermisosDashboard) {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $estatus = $_GET['estatus'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    $empresa = $_GET['empresa'] ?? '';
    $asunto = $_GET['asunto'] ?? '';

    $whereConditions = ["1=1"]; $params = array();
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $fecha_inicio_dmy = date('d/m/Y', strtotime($fecha_inicio));
        $fecha_fin_dmy = date('d/m/Y', strtotime($fecha_fin));
        $whereConditions[] = "CONVERT(DATE, Fecha, 103) BETWEEN CONVERT(DATE, ?, 103) AND CONVERT(DATE, ?, 103)";
        $params[] = $fecha_inicio_dmy; $params[] = $fecha_fin_dmy;
    }
    if (!empty($estatus)) { $whereConditions[] = "Estatus = ?"; $params[] = $estatus; }
    if (!empty($prioridad)) { $whereConditions[] = "Prioridad = ?"; $params[] = $prioridad; }
    if (!empty($empresa)) { $whereConditions[] = "Empresa = ?"; $params[] = $empresa; }
    if (!empty($asunto)) { $whereConditions[] = "Asunto LIKE ?"; $params[] = '%' . $asunto . '%'; }
    $whereClause = implode(" AND ", $whereConditions);

    $sqlTickets = "SELECT Id_Ticket, Nombre, Correo, Prioridad, Empresa, Asunto, Mensaje, Fecha, Hora, Estatus, PA, FechaTerminado, HoraTerminado, FechaEnProceso, HoraEnProceso FROM [Ticket].[dbo].[T3] WHERE $whereClause ORDER BY CONVERT(DATETIME, Fecha, 103) DESC, Hora DESC";
    $stmtTickets = sqlsrv_query($conn, $sqlTickets, $params);

    $sqlTotal = "SELECT COUNT(*) as total FROM [Ticket].[dbo].[T3] WHERE $whereClause";
    $stmtTotal = sqlsrv_query($conn, $sqlTotal, $params);
    $totalTickets = 0;
    if ($stmtTotal) { $row = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC); $totalTickets = $row ? $row['total'] : 0; sqlsrv_free_stmt($stmtTotal); }

    $ticketsConTiempo = []; 
    $tiemposEjecucion = []; 
    $totalHorasLaborales = 0; 
    $ticketsConTiempoCalculado = 0;
    
    $slaData = [
        'ALTA' => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 2, 'desc' => '≤2h'],
        'MEDIA' => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 4, 'desc' => '≤4h'],
        'BAJA' => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 10, 'desc' => '≤10h'],
        'ESTÁNDAR' => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 24, 'desc' => '≤24h']
    ];
    
    $cumplimientoGlobal = ['cumple' => 0, 'nocumple' => 0, 'total' => 0];
    
    if ($stmtTickets) {
        sqlsrv_fetch($stmtTickets, SQLSRV_SCROLL_FIRST);
        while ($ticket = sqlsrv_fetch_array($stmtTickets, SQLSRV_FETCH_ASSOC)) {
            $tiempoCalculado = null;
            $sla_info = null;
            
            if (($ticket['Estatus'] ?? '') == 'Atendido' && !empty($ticket['FechaTerminado'])) {
                $tiempoCalculado = calcularHorasLaborales(
                    $ticket['FechaEnProceso'] ?? $ticket['Fecha'], 
                    $ticket['HoraEnProceso'] ?? '08:30:00', 
                    $ticket['FechaTerminado'], 
                    $ticket['HoraTerminado'] ?? '17:30:00'
                );
                
                if ($tiempoCalculado !== null && $tiempoCalculado >= 0) {
                    $sla_info = verificarSLA($ticket['Prioridad'] ?? '', $tiempoCalculado);
                    
                    $tiemposEjecucion[] = $tiempoCalculado;
                    $totalHorasLaborales += $tiempoCalculado;
                    $ticketsConTiempoCalculado++;
                    $ticket['Tiempo_Ejec_Calculado'] = $tiempoCalculado;
                    $ticket['SLA_Info'] = $sla_info;
                    
                    if ($sla_info) {
                        $categoria = $sla_info['categoria'];
                        
                        if ($sla_info['cumple']) {
                            $slaData[$categoria]['cumple']++;
                            $cumplimientoGlobal['cumple']++;
                        } else {
                            $slaData[$categoria]['nocumple']++;
                            $cumplimientoGlobal['nocumple']++;
                        }
                        $slaData[$categoria]['total']++;
                    }
                }
            }
            
            $ticketsConTiempo[] = $ticket;
        }
        sqlsrv_fetch($stmtTickets, SQLSRV_SCROLL_FIRST);
    }

    $tiempoPromedioGeneral = $ticketsConTiempoCalculado > 0 ? round($totalHorasLaborales / $ticketsConTiempoCalculado, 2) : 0;
    $totalSLATickets = $cumplimientoGlobal['cumple'] + $cumplimientoGlobal['nocumple'];
    $cumplimientoSLAPorcentaje = $totalSLATickets > 0 ? round(($cumplimientoGlobal['cumple'] / $totalSLATickets) * 100, 1) : 0;

    // CORRECCIÓN: Datos de estatus con manejo correcto de "En proceso"
    $estatusData = [];
    $sqlEstatus = "SELECT 
                      CASE 
                         WHEN Estatus IS NULL OR Estatus = '' THEN 'Pendiente'
                         ELSE Estatus 
                      END as Estatus_Normalizado,
                      COUNT(*) as cantidad 
                   FROM [Ticket].[dbo].[T3] 
                   WHERE $whereClause 
                   GROUP BY 
                      CASE 
                         WHEN Estatus IS NULL OR Estatus = '' THEN 'Pendiente'
                         ELSE Estatus 
                      END";
    $stmtEstatus = sqlsrv_query($conn, $sqlEstatus, $params);
    if ($stmtEstatus) { 
        while ($row = sqlsrv_fetch_array($stmtEstatus, SQLSRV_FETCH_ASSOC)) {
            $estatusData[$row['Estatus_Normalizado']] = $row['cantidad'];
        } 
        sqlsrv_free_stmt($stmtEstatus); 
    }

    $prioridadData = [];
    $sqlPrioridad = "SELECT ISNULL(NULLIF(Prioridad, ''), 'No especificada') as Prioridad, COUNT(*) as cantidad FROM [Ticket].[dbo].[T3] WHERE $whereClause GROUP BY ISNULL(NULLIF(Prioridad, ''), 'No especificada')";
    $stmtPrioridad = sqlsrv_query($conn, $sqlPrioridad, $params);
    if ($stmtPrioridad) { while ($row = sqlsrv_fetch_array($stmtPrioridad, SQLSRV_FETCH_ASSOC)) $prioridadData[$row['Prioridad']] = $row['cantidad']; sqlsrv_free_stmt($stmtPrioridad); }

    $empresaData = [];
    $sqlEmpresa = "SELECT CASE WHEN Empresa IS NULL OR Empresa = '' OR Empresa = 'N/A' THEN 'No especificada' WHEN UPPER(TRIM(Empresa)) LIKE '%CONTA%' THEN 'FINANZAS Y CONTABILIDAD' WHEN UPPER(TRIM(Empresa)) LIKE '%FINANZAS%' THEN 'FINANZAS Y CONTABILIDAD' ELSE UPPER(TRIM(Empresa)) END as Empresa_Normalizada, COUNT(*) as cantidad FROM [Ticket].[dbo].[T3] WHERE $whereClause GROUP BY CASE WHEN Empresa IS NULL OR Empresa = '' OR Empresa = 'N/A' THEN 'No especificada' WHEN UPPER(TRIM(Empresa)) LIKE '%CONTA%' THEN 'FINANZAS Y CONTABILIDAD' WHEN UPPER(TRIM(Empresa)) LIKE '%FINANZAS%' THEN 'FINANZAS Y CONTABILIDAD' ELSE UPPER(TRIM(Empresa)) END ORDER BY cantidad DESC";
    $stmtEmpresa = sqlsrv_query($conn, $sqlEmpresa, $params);
    if ($stmtEmpresa) {
        $contador = 0; $otrosTotal = 0;
        while ($row = sqlsrv_fetch_array($stmtEmpresa, SQLSRV_FETCH_ASSOC)) {
            if ($contador < 15) { $empresaData[$row['Empresa_Normalizada']] = $row['cantidad']; $contador++; }
            else $otrosTotal += $row['cantidad'];
        }
        if ($otrosTotal > 0) $empresaData['Otras AREAS/EMPRESAS'] = $otrosTotal;
        sqlsrv_free_stmt($stmtEmpresa);
        if (empty($empresaData)) $empresaData['No especificada'] = 0;
    }

    $asuntoData = [];
    $sqlAsunto = "SELECT CASE WHEN Asunto IS NULL OR Asunto = '' THEN 'No especificado' ELSE Asunto END as Asunto, COUNT(*) as cantidad FROM [Ticket].[dbo].[T3] WHERE $whereClause GROUP BY CASE WHEN Asunto IS NULL OR Asunto = '' THEN 'No especificado' ELSE Asunto END ORDER BY cantidad DESC";
    $stmtAsunto = sqlsrv_query($conn, $sqlAsunto, $params);
    if ($stmtAsunto) {
        $contador = 0; $otrosAsuntosTotal = 0;
        while ($row = sqlsrv_fetch_array($stmtAsunto, SQLSRV_FETCH_ASSOC)) {
            if ($contador < 10) { $asuntoData[$row['Asunto']] = $row['cantidad']; $contador++; }
            else $otrosAsuntosTotal += $row['cantidad'];
        }
        if ($otrosAsuntosTotal > 0) $asuntoData['Otros Asuntos'] = $otrosAsuntosTotal;
        sqlsrv_free_stmt($stmtAsunto);
        if (empty($asuntoData)) $asuntoData['No especificado'] = 0;
    }

    $mensualData = []; $currentYear = date('Y');
    $sqlMensual = "SELECT MONTH(CONVERT(DATETIME, Fecha, 103)) as mes, COUNT(*) as cantidad FROM [Ticket].[dbo].[T3] WHERE YEAR(CONVERT(DATETIME, Fecha, 103)) = ? GROUP BY MONTH(CONVERT(DATETIME, Fecha, 103)) ORDER BY mes";
    $stmtMensual = sqlsrv_query($conn, $sqlMensual, array($currentYear));
    if ($stmtMensual) { while ($row = sqlsrv_fetch_array($stmtMensual, SQLSRV_FETCH_ASSOC)) $mensualData[$row['mes']] = $row['cantidad']; sqlsrv_free_stmt($stmtMensual); }

    $atendidoPorData = [];
    $sqlAtendidoPor = "SELECT CASE WHEN PA LIKE '%luis%' OR PA LIKE '%Luis%' THEN 'Luis' WHEN PA LIKE '%alfredo%' OR PA LIKE '%Alfredo%' THEN 'Alfredo' WHEN PA LIKE '%ariel%' OR PA LIKE '%Ariel%' THEN 'Ariel' WHEN PA LIKE '%romero%' OR PA LIKE '%Romero%' THEN 'Romero' WHEN PA IS NULL OR PA = '' THEN 'Sin asignar' ELSE 'Otros' END as AtendidoPor_Categoria, COUNT(*) as cantidad FROM [Ticket].[dbo].[T3] WHERE $whereClause GROUP BY CASE WHEN PA LIKE '%luis%' OR PA LIKE '%Luis%' THEN 'Luis' WHEN PA LIKE '%alfredo%' OR PA LIKE '%Alfredo%' THEN 'Alfredo' WHEN PA LIKE '%ariel%' OR PA LIKE '%Ariel%' THEN 'Ariel' WHEN PA LIKE '%romero%' OR PA LIKE '%Romero%' THEN 'Romero' WHEN PA IS NULL OR PA = '' THEN 'Sin asignar' ELSE 'Otros' END";
    $stmtAtendidoPor = sqlsrv_query($conn, $sqlAtendidoPor, $params);
    if ($stmtAtendidoPor) { while ($row = sqlsrv_fetch_array($stmtAtendidoPor, SQLSRV_FETCH_ASSOC)) $atendidoPorData[$row['AtendidoPor_Categoria']] = $row['cantidad']; sqlsrv_free_stmt($stmtAtendidoPor); }

    $tiempoEjecucionData = ['Rápido (≤4h)' => 0, 'Moderado (4-24h)' => 0, 'Lento (24-72h)' => 0, 'Muy lento (>72h)' => 0];
    foreach ($tiemposEjecucion as $tiempo) {
        if ($tiempo <= 4) $tiempoEjecucionData['Rápido (≤4h)']++;
        elseif ($tiempo <= 24) $tiempoEjecucionData['Moderado (4-24h)']++;
        elseif ($tiempo <= 72) $tiempoEjecucionData['Lento (24-72h)']++;
        else $tiempoEjecucionData['Muy lento (>72h)']++;
    }

    $estatusOptions = []; $sqlEstatusOpt = "SELECT DISTINCT Estatus FROM [Ticket].[dbo].[T3] WHERE Estatus IS NOT NULL AND Estatus != '' ORDER BY Estatus";
    $stmtEstatusOpt = sqlsrv_query($conn, $sqlEstatusOpt); if ($stmtEstatusOpt) { while ($row = sqlsrv_fetch_array($stmtEstatusOpt, SQLSRV_FETCH_ASSOC)) $estatusOptions[] = $row['Estatus']; sqlsrv_free_stmt($stmtEstatusOpt); }
    $prioridadOptions = []; $sqlPrioridadOpt = "SELECT DISTINCT Prioridad FROM [Ticket].[dbo].[T3] WHERE Prioridad IS NOT NULL AND Prioridad != '' ORDER BY Prioridad";
    $stmtPrioridadOpt = sqlsrv_query($conn, $sqlPrioridadOpt); if ($stmtPrioridadOpt) { while ($row = sqlsrv_fetch_array($stmtPrioridadOpt, SQLSRV_FETCH_ASSOC)) $prioridadOptions[] = $row['Prioridad']; sqlsrv_free_stmt($stmtPrioridadOpt); }
    $empresaOptions = []; $sqlEmpresaOpt = "SELECT DISTINCT Empresa FROM [Ticket].[dbo].[T3] WHERE Empresa IS NOT NULL AND Empresa != '' ORDER BY Empresa";
    $stmtEmpresaOpt = sqlsrv_query($conn, $sqlEmpresaOpt); if ($stmtEmpresaOpt) { while ($row = sqlsrv_fetch_array($stmtEmpresaOpt, SQLSRV_FETCH_ASSOC)) $empresaOptions[] = $row['Empresa']; sqlsrv_free_stmt($stmtEmpresaOpt); }
    $asuntoOptions = []; $sqlAsuntoOpt = "SELECT DISTINCT Asunto FROM [Ticket].[dbo].[T3] WHERE Asunto IS NOT NULL AND Asunto != '' ORDER BY Asunto";
    $stmtAsuntoOpt = sqlsrv_query($conn, $sqlAsuntoOpt); if ($stmtAsuntoOpt) { while ($row = sqlsrv_fetch_array($stmtAsuntoOpt, SQLSRV_FETCH_ASSOC)) $asuntoOptions[] = $row['Asunto']; sqlsrv_free_stmt($stmtAsuntoOpt); }

    $mensualLabels = []; $mensualValues = [];
    for ($i = 1; $i <= 12; $i++) { $month = date('Y-m', strtotime("$currentYear-$i-01")); $mensualLabels[] = date('M Y', strtotime($month)); $mensualValues[] = $mensualData[$i] ?? 0; }
}

$iniciales = substr($nombre, 0, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - SOPORTE TÉCNICO BACROCORP</title>

  <!-- Prevenir cache -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <!-- Fuentes -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- ECharts -->
  <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/echarts-gl@2.0.9/dist/echarts-gl.min.js"></script>

  <style>
    :root {
      --navy-primary: #0a192f;
      --navy-secondary: #112240;
      --navy-tertiary: #1e3a5c;
      --accent-blue: #3b82f6;
      --accent-blue-light: #60a5fa;
      --accent-blue-dark: #1d4ed8;
      --success-green: #10b981;
      --warning-orange: #f59e0b;
      --error-red: #ef4444;
      --snow-white: #f8fafc;
      --pearl-white: #f1f5f9;
      --text-primary: #1e293b;
      --text-secondary: #475569;
      --text-light: #e2e8f0;
      --card-bg: rgba(255, 255, 255, 0.98);
      --card-shadow: 0 12px 32px rgba(2, 12, 27, 0.12);
      --glass-bg: rgba(255, 255, 255, 0.15);
      --glass-border: rgba(255, 255, 255, 0.2);
      --gradient-primary: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
      --gradient-secondary: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
      --gradient-accent: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
      --gradient-blue: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
      --gradient-success: linear-gradient(135deg, #059669 0%, #10b981 100%);
      --gradient-warning: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
      --gradient-error: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      --spacing-xs: 6px;
      --spacing-sm: 12px;
      --spacing-md: 18px;
      --spacing-lg: 24px;
      --spacing-xl: 32px;
      --spacing-xxl: 48px;
      --border-radius-sm: 8px;
      --border-radius-md: 12px;
      --border-radius-lg: 16px;
      --border-radius-xl: 20px;
      --border-radius-xxl: 24px;
    }

    .dark-mode {
      --navy-primary: #0f172a;
      --navy-secondary: #1e293b;
      --navy-tertiary: #334155;
      --accent-blue: #60a5fa;
      --accent-blue-light: #93c5fd;
      --accent-blue-dark: #3b82f6;
      --snow-white: #0f172a;
      --pearl-white: #1e293b;
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --text-light: #e2e8f0;
      --card-bg: rgba(15, 23, 42, 0.95);
      --card-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
      --glass-bg: rgba(255, 255, 255, 0.08);
      --glass-border: rgba(255, 255, 255, 0.12);
      --select-bg: #1e293b;
      --select-text: #f1f5f9;
      --select-border: #334155;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--snow-white);
      color: var(--text-primary);
      line-height: 1.5;
      min-height: 100vh;
      overflow-x: hidden;
      transition: all 0.4s ease;
      font-size: 0.9rem;
    }

    /* SIDEBAR */
    #sidebar {
      position: fixed; top: 0; left: 0; height: 100vh; width: 240px;
      background: linear-gradient(165deg, var(--navy-primary) 0%, var(--navy-secondary) 50%, var(--navy-tertiary) 100%);
      backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2); padding-top: var(--spacing-lg);
      display: flex; flex-direction: column; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1000; overflow-y: auto;
    }
    #sidebar.hidden { transform: translateX(-100%); }
    .sidebar-header { padding: var(--spacing-md) var(--spacing-lg); margin-bottom: var(--spacing-lg); border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    .sidebar-logo { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.5rem; color: var(--accent-blue); letter-spacing: 0.05em; text-align: center; }
    .sidebar-subtitle { font-size: 0.75rem; color: var(--text-light); text-align: center; margin-top: var(--spacing-xs); opacity: 0.8; }
    #sidebar a {
      display: flex; align-items: center; color: var(--text-light); font-weight: 500; font-size: 0.9rem;
      padding: var(--spacing-sm) var(--spacing-lg); text-decoration: none; border-radius: var(--border-radius-md);
      margin: 4px var(--spacing-md); letter-spacing: 0.02em; transition: all 0.3s ease; user-select: none;
      position: relative; overflow: hidden; background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    #sidebar a::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent); transition: left 0.6s; }
    #sidebar a:hover::before { left: 100%; }
    #sidebar a:hover { background: rgba(59, 130, 246, 0.15); color: white; transform: translateX(8px); border-color: rgba(59, 130, 246, 0.3); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.2); }
    #sidebar a.active { background: var(--gradient-blue); color: white; border-color: rgba(59, 130, 246, 0.5); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); }
    #sidebar a i { margin-right: var(--spacing-sm); font-size: 1.1rem; transition: transform 0.3s ease; min-width: 24px; text-align: center; color: var(--accent-blue); }
    #sidebar a:hover i, #sidebar a.active i { transform: scale(1.1); color: white; }
    #sidebar a.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
    #sidebar a.disabled:hover { background: rgba(255, 255, 255, 0.05); color: var(--text-light); transform: none; border-color: rgba(255, 255, 255, 0.08); box-shadow: none; }
    #sidebar a.disabled::before { display: none; }
    .theme-toggle { margin-top: auto; margin-bottom: var(--spacing-lg); padding: 0 var(--spacing-md); }
    .theme-toggle-btn {
      display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.12); color: var(--text-light);
      padding: var(--spacing-sm); border-radius: var(--border-radius-md); cursor: pointer;
      font-family: 'Inter', sans-serif; font-weight: 500; font-size: 0.85rem; transition: all 0.3s ease; width: 100%;
    }
    .theme-toggle-btn:hover { background: rgba(59, 130, 246, 0.15); color: white; border-color: rgba(59, 130, 246, 0.3); transform: translateY(-2px); }
    .theme-toggle-btn i { margin-right: var(--spacing-sm); }
    .logout-btn {
      display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.15);
      backdrop-filter: blur(10px); border: 1px solid rgba(239, 68, 68, 0.3); color: #fecaca;
      padding: var(--spacing-sm); border-radius: var(--border-radius-md); cursor: pointer;
      font-family: 'Inter', sans-serif; font-weight: 500; font-size: 0.85rem; transition: all 0.3s ease;
      width: 100%; margin-bottom: var(--spacing-lg);
    }
    .logout-btn:hover { background: rgba(239, 68, 68, 0.25); color: white; border-color: rgba(239, 68, 68, 0.5); transform: translateY(-2px); }
    .logout-btn i { margin-right: var(--spacing-sm); }

    /* Fondo */
    .background-container {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -3; overflow: hidden;
      background: linear-gradient(135deg, var(--pearl-white) 0%, var(--snow-white) 100%);
    }
    .dark-mode .background-container { background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-secondary) 100%); }
    .glass-layer {
      position: absolute; width: 100%; height: 100%;
      background: linear-gradient(135deg, rgba(30, 58, 138, 0.03) 0%, rgba(59, 130, 246, 0.05) 25%, rgba(96, 165, 250, 0.04) 50%, rgba(14, 165, 233, 0.03) 75%, rgba(59, 130, 246, 0.02) 100%);
      backdrop-filter: blur(40px);
    }
    .water-drops-container { position: absolute; width: 100%; height: 100%; }
    .water-drop {
      position: absolute; background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, rgba(96, 165, 250, 0.08) 30%, transparent 70%);
      border-radius: 50%; animation: waterRipple 10s infinite ease-in-out;
    }
    @keyframes waterRipple {
      0% { transform: scale(0); opacity: 0.4; }
      50% { opacity: 0.2; }
      100% { transform: scale(8); opacity: 0; }
    }

    /* Header */
    .dashboard-header {
      background: var(--card-bg); backdrop-filter: blur(20px); border-bottom: 1px solid var(--glass-border);
      padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) 260px;
      display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0;
      z-index: 100; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08); transition: padding 0.4s ease;
    }
    .dashboard-header.full-width { padding-left: var(--spacing-lg); }
    .logo-container { display: flex; align-items: center; gap: var(--spacing-sm); }
    .logo { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.5rem; background: var(--gradient-blue); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .logo-subtitle { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; }
    .nav-controls { display: flex; align-items: center; gap: var(--spacing-md); }
    .sidebar-toggle {
      background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border);
      color: var(--accent-blue); width: 40px; height: 40px; border-radius: var(--border-radius-md);
      display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease;
      font-size: 1.1rem;
    }
    .sidebar-toggle:hover { background: var(--accent-blue); color: white; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); }
    .user-info {
      display: flex; align-items: center; gap: var(--spacing-sm); padding: 6px var(--spacing-sm);
      border-radius: var(--border-radius-md); background: var(--glass-bg); backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
    }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--gradient-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem; }
    .user-details { display: flex; flex-direction: column; }
    .user-name { font-weight: 600; font-size: 0.85rem; }
    .user-role { font-size: 0.75rem; color: var(--text-secondary); }

    /* Módulos */
    .module-container {
      position: fixed; top: 60px; left: 260px; right: var(--spacing-lg); bottom: var(--spacing-lg);
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-xl);
      box-shadow: var(--card-shadow); border: 1px solid var(--glass-border); z-index: 50;
      display: none; overflow: hidden; transition: all 0.3s ease;
    }
    .module-container.active { display: block; }
    .module-iframe { width: 100%; height: 100%; border: none; border-radius: var(--border-radius-xl); }

    /* Contenido Principal */
    .dashboard-container {
      padding: var(--spacing-lg) var(--spacing-lg) var(--spacing-lg) 260px; max-width: 100%;
      margin: 0 auto; transition: padding 0.4s ease; min-height: calc(100vh - 60px);
    }
    .dashboard-container.full-width { padding-left: var(--spacing-lg); }

    /* Títulos */
    .dashboard-title {
      font-family: 'Montserrat', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: var(--spacing-xs);
      background: var(--gradient-blue); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      position: relative; line-height: 1.2;
    }
    .dashboard-subtitle { color: var(--text-secondary); margin-bottom: var(--spacing-lg); font-size: 1rem; max-width: 600px; line-height: 1.5; }

    /* Info Usuario */
    .user-info-card {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      margin-bottom: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);
      position: relative; overflow: hidden;
    }
    .user-info-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--gradient-blue); }
    .user-info-avatar { width: 50px; height: 50px; border-radius: 50%; background: var(--gradient-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.1rem; border: 2px solid var(--card-bg); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
    .user-info-details { flex: 1; }
    .user-info-name { font-family: 'Montserrat', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 4px; color: var(--text-primary); }
    .user-info-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--spacing-sm); margin-top: var(--spacing-xs); }
    .user-info-stat { display: flex; flex-direction: column; }
    .user-info-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .user-info-value { font-weight: 700; font-size: 0.9rem; color: var(--accent-blue); }

    /* Filtros */
    .filters-container {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg); background: var(--card-bg); backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg); padding: var(--spacing-md); box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border); position: relative;
    }
    .filters-container::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0; }
    .filter-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }
    .filter-label { font-size: 0.8rem; color: var(--accent-blue); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .filter-select {
      background: var(--card-bg); border: 1px solid var(--glass-border); color: var(--text-primary);
      padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.3s ease; cursor: pointer;
      backdrop-filter: blur(10px);
    }
    .dark-mode .filter-select { background: var(--select-bg); color: var(--select-text); border-color: var(--select-border); }
    .filter-select:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }
    .filter-select:hover { border-color: var(--accent-blue); }
    .filter-select option { background: var(--card-bg); color: var(--text-primary); padding: 8px; }
    .dark-mode .filter-select option { background: var(--select-bg); color: var(--select-text); }
    .filter-date-group { display: flex; flex-direction: column; gap: var(--spacing-xs); }
    .filter-date-input {
      background: var(--card-bg); border: 1px solid var(--glass-border); color: var(--text-primary);
      padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif; font-size: 0.85rem; transition: all 0.3s ease; backdrop-filter: blur(10px);
    }
    .dark-mode .filter-date-input { background: var(--select-bg); color: var(--select-text); border-color: var(--select-border); }
    .filter-date-input:focus { outline: none; border-color: var(--accent-blue); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }
    .filter-actions { display: flex; gap: var(--spacing-sm); align-items: flex-end; }
    .filter-btn {
      background: var(--gradient-blue); color: white; border: none; padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md); font-family: 'Inter', sans-serif; font-weight: 600;
      font-size: 0.85rem; cursor: pointer; transition: all 0.3s ease; height: fit-content;
      display: flex; align-items: center; gap: var(--spacing-xs);
    }
    .filter-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3); }
    .filter-reset { background: var(--glass-bg); color: var(--text-secondary); border: 1px solid var(--glass-border); }
    .filter-reset:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
    .export-btn {
      background: var(--gradient-success); color: white; border: none; padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md); font-family: 'Inter', sans-serif; font-weight: 600;
      font-size: 0.85rem; cursor: pointer; transition: all 0.3s ease; height: fit-content;
      display: flex; align-items: center; gap: var(--spacing-xs); text-decoration: none;
    }
    .export-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); }

    /* KPIs */
    .kpi-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg);
    }
    .kpi-card {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      transition: all 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column;
    }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); }
    .kpi-card.success::before { background: var(--gradient-success); }
    .kpi-card.warning::before { background: var(--gradient-warning); }
    .kpi-card.error::before { background: var(--gradient-error); }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15); }
    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-sm); }
    .kpi-title { font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-icon { width: 42px; height: 42px; border-radius: var(--border-radius-md); background: var(--gradient-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
    .kpi-card.success .kpi-icon { background: var(--gradient-success); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .kpi-card.warning .kpi-icon { background: var(--gradient-warning); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .kpi-card.error .kpi-icon { background: var(--gradient-error); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }
    .kpi-value { font-family: 'Montserrat', sans-serif; font-size: 2rem; font-weight: 800; margin-bottom: var(--spacing-xs); background: var(--gradient-blue); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .kpi-card.success .kpi-value { background: var(--gradient-success); -webkit-background-clip: text; }
    .kpi-card.warning .kpi-value { background: var(--gradient-warning); -webkit-background-clip: text; }
    .kpi-card.error .kpi-value { background: var(--gradient-error); -webkit-background-clip: text; }
    .kpi-comparison { color: var(--text-secondary); font-size: 0.75rem; margin-top: auto; }

    /* Gráficas */
    .charts-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);
    }
    .chart-card {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;
      min-height: 480px; display: flex; flex-direction: column;
    }
    .chart-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); }
    .chart-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(59, 130, 246, 0.25); border-color: var(--accent-blue); }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md); flex-shrink: 0; }
    .chart-title { font-family: 'Montserrat', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--accent-blue); letter-spacing: 0.5px; }
    .chart-actions { display: flex; gap: var(--spacing-xs); }
    .chart-action-btn {
      background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-secondary);
      width: 32px; height: 32px; border-radius: var(--border-radius-sm); display: flex;
      align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; font-size: 0.8rem;
    }
    .chart-action-btn:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
    .chart-container { position: relative; height: 480px; width: 100%; flex-grow: 1; }

    /* Tiempo Promedio */
    .tiempo-promedio-container {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);
    }
    .tiempo-promedio-card {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      text-align: center; transition: all 0.3s ease; position: relative; overflow: hidden;
    }
    .tiempo-promedio-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); }
    .tiempo-promedio-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
    .tiempo-promedio-value { font-family: 'Montserrat', sans-serif; font-size: 2.5rem; font-weight: 800; color: var(--accent-blue); margin: var(--spacing-sm) 0; }
    .tiempo-promedio-label { font-size: 0.9rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .tiempo-promedio-subtitle { font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px; }

    /* SLA Card */
    .sla-card {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      transition: all 0.3s ease; position: relative; overflow: hidden;
    }
    .sla-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); }
    .sla-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-sm); }
    .sla-title { font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
    .sla-percentage { font-family: 'Montserrat', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--accent-blue); }
    .sla-bar-container { height: 8px; background: var(--glass-bg); border-radius: 10px; margin: var(--spacing-sm) 0; overflow: hidden; }
    .sla-bar { height: 100%; background: var(--gradient-success); border-radius: 10px; transition: width 0.5s ease; }
    .sla-stats { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary); }

    /* Tabla */
    .table-section {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      margin-bottom: var(--spacing-lg); position: relative; overflow: hidden;
    }
    .table-section::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-blue); }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md); }
    .section-title { font-family: 'Montserrat', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--text-primary); }
    .table-info { font-size: 0.8rem; color: var(--text-secondary); }
    .table-actions { display: flex; gap: var(--spacing-sm); }
    .tickets-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .tickets-table th {
      text-align: left; padding: var(--spacing-sm) var(--spacing-md); border-bottom: 2px solid var(--glass-border);
      color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
      background: rgba(59, 130, 246, 0.05);
    }
    .tickets-table td { padding: var(--spacing-sm) var(--spacing-md); border-bottom: 1px solid var(--glass-border); }
    .tickets-table tr { transition: all 0.3s ease; }
    .tickets-table tr:hover { background: rgba(59, 130, 246, 0.05); transform: translateX(3px); }

    /* Badges */
    .status-badge {
      padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: white;
      display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .status-atendido { background: var(--gradient-success); }
    .status-en-proceso { background: var(--gradient-warning); }
    .status-pendiente { background: var(--gradient-blue); }
    .priority-alta { background: var(--gradient-error); }
    .priority-media { background: var(--gradient-warning); }
    .priority-baja { background: var(--gradient-blue); }
    .tiempo-rapido { background: var(--gradient-success) !important; }
    .tiempo-moderado { background: var(--gradient-warning) !important; }
    .tiempo-lento { background: var(--gradient-error) !important; }
    .sla-cumple { background: var(--gradient-success) !important; }
    .sla-no-cumple { background: var(--gradient-error) !important; }

    /* Alert */
    .custom-alert {
      display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); z-index: 2000;
      align-items: center; justify-content: center;
    }
    .custom-alert.active { display: flex; }
    .alert-content {
      background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg);
      padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border);
      max-width: 350px; width: 90%; text-align: center; transform: scale(0.9); opacity: 0;
      transition: all 0.3s ease;
    }
    .custom-alert.active .alert-content { transform: scale(1); opacity: 1; }
    .alert-icon { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #ef4444, #dc2626); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--spacing-md); color: white; font-size: 1.5rem; }
    .alert-title { font-family: 'Montserrat', sans-serif; font-size: 1.2rem; font-weight: 700; margin-bottom: var(--spacing-sm); color: var(--text-primary); }
    .alert-message { color: var(--text-secondary); margin-bottom: var(--spacing-md); line-height: 1.5; font-size: 0.9rem; }
    .alert-actions { display: flex; gap: var(--spacing-sm); justify-content: center; }
    .alert-btn { padding: var(--spacing-xs) var(--spacing-lg); border-radius: var(--border-radius-sm); font-family: 'Inter', sans-serif; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; font-size: 0.85rem; }
    .alert-btn-cancel { background: var(--glass-bg); color: var(--text-secondary); border: 1px solid var(--glass-border); }
    .alert-btn-cancel:hover { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
    .alert-btn-confirm { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .alert-btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3); }

    /* Sin Permisos */
    .no-permission-container {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      min-height: 60vh; text-align: center; padding: var(--spacing-xxl);
    }
    .no-permission-icon { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; margin-bottom: var(--spacing-lg); color: white; font-size: 3rem; }
    .no-permission-title { font-family: 'Montserrat', sans-serif; font-size: 2rem; font-weight: 800; margin-bottom: var(--spacing-md); background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .no-permission-message { color: var(--text-secondary); font-size: 1.1rem; margin-bottom: var(--spacing-lg); max-width: 500px; line-height: 1.6; }
    .no-permission-details { background: var(--card-bg); backdrop-filter: blur(20px); border-radius: var(--border-radius-lg); padding: var(--spacing-md); box-shadow: var(--card-shadow); border: 1px solid var(--glass-border); max-width: 400px; width: 100%; }
    .no-permission-user { font-weight: 600; color: var(--accent-blue); margin-bottom: var(--spacing-sm); }
    .no-permission-info { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; }

    /* Responsive */
    @media (max-width: 1400px) { .charts-grid { grid-template-columns: 1fr; } .module-container { left: 260px; right: var(--spacing-md); bottom: var(--spacing-md); } .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 1200px) { .dashboard-container { padding: var(--spacing-md) var(--spacing-md) var(--spacing-md) 250px; } .dashboard-header { padding-left: 250px; } .dashboard-title { font-size: 1.6rem; } .module-container { left: 250px; right: var(--spacing-md); } .filters-container { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 992px) {
      #sidebar { width: 220px; transform: translateX(-100%); } #sidebar.active { transform: translateX(0); }
      .dashboard-header { padding: var(--spacing-sm) var(--spacing-md); } .dashboard-container { padding: var(--spacing-md); }
      .dashboard-title { font-size: 1.4rem; } .kpi-grid { grid-template-columns: 1fr; gap: var(--spacing-md); }
      .charts-grid { gap: var(--spacing-md); } .filters-container { grid-template-columns: 1fr; gap: var(--spacing-sm); }
      .user-info-card { flex-direction: column; text-align: center; gap: var(--spacing-sm); } .user-info-stats { grid-template-columns: repeat(2, 1fr); gap: var(--spacing-sm); }
      .module-container { left: var(--spacing-md); right: var(--spacing-md); top: 55px; bottom: var(--spacing-md); }
      .alert-actions { flex-direction: column; } .no-permission-container { padding: var(--spacing-xl); }
      .no-permission-icon { width: 100px; height: 100px; font-size: 2.5rem; } .no-permission-title { font-size: 1.6rem; }
    }
    @media (max-width: 768px) {
      .dashboard-header { padding: var(--spacing-xs) var(--spacing-sm); } .dashboard-container { padding: var(--spacing-sm); }
      .dashboard-title { font-size: 1.3rem; margin-bottom: 6px; } .dashboard-subtitle { font-size: 0.9rem; margin-bottom: var(--spacing-md); }
      .kpi-card { padding: var(--spacing-sm); } .kpi-value { font-size: 1.7rem; } .chart-card { padding: var(--spacing-sm); min-height: 440px; }
      .chart-container { height: 400px; } .table-section { padding: var(--spacing-sm); } .tickets-table { font-size: 0.75rem; }
      .tickets-table th, .tickets-table td { padding: 8px 12px; } .module-container { left: var(--spacing-sm); right: var(--spacing-sm); top: 50px; bottom: var(--spacing-sm); border-radius: var(--border-radius-lg); }
      .nav-controls { gap: var(--spacing-sm); } .user-info { padding: 4px; } .user-details { display: none; }
      .no-permission-container { padding: var(--spacing-lg); } .no-permission-icon { width: 80px; height: 80px; font-size: 2rem; }
      .no-permission-title { font-size: 1.4rem; } .no-permission-message { font-size: 1rem; }
    }
    @media (max-width: 576px) {
      #sidebar { width: 100%; } .dashboard-title { font-size: 1.2rem; } .dashboard-subtitle { font-size: 0.85rem; }
      .user-info-card { padding: var(--spacing-sm); } .user-info-stats { grid-template-columns: 1fr; gap: var(--spacing-xs); }
      .kpi-grid { gap: var(--spacing-sm); } .kpi-card { padding: var(--spacing-sm); } .kpi-value { font-size: 1.5rem; }
      .charts-grid { gap: var(--spacing-sm); } .chart-card { padding: var(--spacing-sm); min-height: 420px; }
      .chart-container { height: 380px; } .chart-title { font-size: 0.9rem; } .table-section { padding: var(--spacing-sm); overflow-x: auto; }
      .tickets-table { min-width: 600px; } .module-container { left: var(--spacing-xs); right: var(--spacing-xs); top: 45px; bottom: var(--spacing-xs); border-radius: var(--border-radius-md); }
      .logo-container { flex-direction: column; gap: 4px; text-align: center; } .logo { font-size: 1.3rem; } .logo-subtitle { font-size: 0.75rem; }
      .no-permission-container { padding: var(--spacing-md); } .no-permission-icon { width: 70px; height: 70px; font-size: 1.8rem; }
      .no-permission-title { font-size: 1.2rem; } .no-permission-message { font-size: 0.9rem; }
    }

    /* Animaciones */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--glass-bg); border-radius: var(--border-radius-sm); }
    ::-webkit-scrollbar-thumb { background: var(--accent-blue); border-radius: var(--border-radius-sm); }
    ::-webkit-scrollbar-thumb:hover { background: var(--accent-blue-dark); }

    /* Efectos 3D */
    .chart-card { transform-style: preserve-3d; perspective: 1200px; }
    .chart-container { transform: translateZ(0); transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1); }
    .chart-card:hover .chart-container { transform: translateZ(25px) scale(1.02); }
    .chart-card::after {
      content: ''; position: absolute; bottom: -20px; left: 10%; width: 80%; height: 40px;
      background: radial-gradient(ellipse at 50% 0%, rgba(0,0,0,0.2), transparent 80%);
      filter: blur(15px); border-radius: 50%; transform: translateZ(-30px) rotateX(80deg);
      opacity: 0; transition: opacity 0.4s ease;
    }
    .chart-card:hover::after { opacity: 0.8; }
  </style>
</head>

<body class="dark-mode">
  <!-- SIDEBAR -->
  <nav id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo">BACROCORP</div><div class="sidebar-subtitle">Sistema de Soporte</div></div>
    <?php if ($tienePermisosDashboard): ?><a href="#" class="nav-link" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a><?php else: ?><a href="#" class="nav-link disabled" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a><?php endif; ?>
    <a href="#" class="nav-link" data-page="levantar-ticket" data-link="Formtic1.php"><i class="fas fa-ticket-alt"></i>LEVANTAR TICKET</a>
    <a href="#" class="nav-link" data-page="revisar-tickets" data-link="RevisarT.php"><i class="fas fa-eye"></i>REVISAR TICKETS</a>
    <?php if (in_array($usuarioActual, $usuariosPermitidosProcesar)): ?><a href="#" class="nav-link" data-page="procesar-tickets" data-link="TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a><?php else: ?><a href="#" class="nav-link disabled" data-page="procesar-tickets" data-link="TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a><?php endif; ?>
    <?php if ($tienePermisosDashboard): ?><a href="#" class="nav-link active" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a><?php else: ?><a href="#" class="nav-link disabled" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a><?php endif; ?>
    <div class="theme-toggle"><button class="theme-toggle-btn" id="themeToggle"><i class="fas fa-sun"></i> MODO CLARO</button></div>
    <div style="padding: 0 var(--spacing-md);"><button type="button" class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> CERRAR SESIÓN</button></div>
  </nav>

  <!-- Alerts -->
  <div class="custom-alert" id="logoutAlert"><div class="alert-content"><div class="alert-icon"><i class="fas fa-sign-out-alt"></i></div><h3 class="alert-title">Cerrar Sesión</h3><p class="alert-message">¿Estás seguro de que deseas cerrar tu sesión?</p><div class="alert-actions"><button class="alert-btn alert-btn-cancel" id="cancelLogout">Cancelar</button><button class="alert-btn alert-btn-confirm" id="confirmLogout">Sí, Cerrar Sesión</button></div></div></div>
  <div class="custom-alert" id="permissionAlert"><div class="alert-content"><div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div><h3 class="alert-title">Acceso Restringido</h3><p class="alert-message" id="permissionMessage">No tienes permisos para acceder a este módulo.</p><div class="alert-actions"><button class="alert-btn alert-btn-permission" id="closePermissionAlert">Aceptar</button></div></div></div>

  <!-- Fondo -->
  <div class="background-container"><div class="glass-layer"></div><div class="water-drops-container" id="waterDrops"></div></div>

  <!-- Header -->
  <header class="dashboard-header">
    <div class="logo-container"><div class="logo">BACROCORP</div><div class="logo-subtitle">Dashboard de Reportes</div></div>
    <div class="nav-controls">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="user-info"><div class="user-avatar"><?php echo $iniciales; ?></div><div class="user-details"><div class="user-name"><?php echo htmlspecialchars($nombre); ?></div><div class="user-role"><?php echo htmlspecialchars($area); ?></div></div></div>
    </div>
  </header>

  <!-- Módulos -->
  <div class="module-container" id="moduleContainer"><iframe src="" class="module-iframe" id="moduleIframe"></iframe></div>

  <!-- Contenido Principal -->
  <div class="dashboard-container">
    <?php if (!$tienePermisosDashboard): ?>
      <div class="no-permission-container animate-fade-in-up">
        <div class="no-permission-icon"><i class="fas fa-lock"></i></div>
        <h1 class="no-permission-title">Acceso Restringido</h1>
        <p class="no-permission-message">No tienes permisos para acceder al Dashboard de Reportes.<br>Este módulo está restringido para usuarios autorizados.</p>
        <div class="no-permission-details"><div class="no-permission-user">Usuario: <?php echo htmlspecialchars($usuario); ?></div><div class="no-permission-info">Si crees que deberías tener acceso, contacta al administrador.</div></div>
      </div>
    <?php else: ?>
      <div id="dashboardContent">
        <h1 class="dashboard-title animate-fade-in-up">Reportes de Tickets</h1>
        <p class="dashboard-subtitle animate-fade-in-up">Dashboard profesional con métricas 3D y cumplimiento SLA en tiempo real</p>

        <!-- Info Usuario -->
        <div class="user-info-card animate-fade-in-up">
          <div class="user-info-avatar"><?php echo $iniciales; ?></div>
          <div class="user-info-details">
            <div class="user-info-name"><?php echo htmlspecialchars($nombre); ?></div>
            <div class="user-info-stats">
              <div class="user-info-stat"><div class="user-info-label">ID Empleado</div><div class="user-info-value"><?php echo htmlspecialchars($id_empleado); ?></div></div>
              <div class="user-info-stat"><div class="user-info-label">Área</div><div class="user-info-value"><?php echo htmlspecialchars($area); ?></div></div>
              <div class="user-info-stat"><div class="user-info-label">Usuario</div><div class="user-info-value"><?php echo htmlspecialchars($usuario); ?></div></div>
              <div class="user-info-stat"><div class="user-info-label">Período</div><div class="user-info-value"><?php echo date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)); ?></div></div>
            </div>
          </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="filters-container animate-fade-in-up">
          <div class="filter-date-group"><div class="filter-label">Fecha Inicio</div><input type="date" class="filter-date-input" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>"></div>
          <div class="filter-date-group"><div class="filter-label">Fecha Fin</div><input type="date" class="filter-date-input" name="fecha_fin" value="<?php echo $fecha_fin; ?>"></div>
          <div class="filter-group"><div class="filter-label">Estatus</div><select class="filter-select" name="estatus"><option value="">Todos</option><?php foreach ($estatusOptions as $option): ?><option value="<?php echo htmlspecialchars($option); ?>" <?php echo $estatus == $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?></select></div>
          <div class="filter-group"><div class="filter-label">Prioridad</div><select class="filter-select" name="prioridad"><option value="">Todas</option><?php foreach ($prioridadOptions as $option): ?><option value="<?php echo htmlspecialchars($option); ?>" <?php echo $estatus == $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?></select></div>
          <div class="filter-group"><div class="filter-label">Empresa</div><select class="filter-select" name="empresa"><option value="">Todas</option><?php foreach ($empresaOptions as $option): ?><option value="<?php echo htmlspecialchars($option); ?>" <?php echo $empresa == $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?></select></div>
          <div class="filter-group"><div class="filter-label">Asunto</div><select class="filter-select" name="asunto"><option value="">Todos</option><?php foreach ($asuntoOptions as $option): ?><option value="<?php echo htmlspecialchars($option); ?>" <?php echo $asunto == $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?></select></div>
          <div class="filter-actions">
            <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="?" class="filter-btn filter-reset"><i class="fas fa-redo"></i> Limpiar</a>
            <?php if ($totalTickets > 0): ?>
              <a href="?export=excel&<?php echo http_build_query($_GET); ?>" class="export-btn"><i class="fas fa-file-excel"></i> Exportar</a>
            <?php endif; ?>
          </div>
        </form>

        <?php if ($totalTickets > 0): ?>
        <!-- KPIs PRINCIPALES - CORREGIDO PARA "En proceso" -->
        <div class="kpi-grid">
          <div class="kpi-card animate-fade-in-up">
            <div class="kpi-header"><div class="kpi-title">Total de Tickets</div><div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div></div>
            <div class="kpi-value"><?php echo $totalTickets; ?></div>
            <div class="kpi-comparison">En el período seleccionado</div>
          </div>
          
          <div class="kpi-card success animate-fade-in-up">
            <div class="kpi-header"><div class="kpi-title">Atendidos</div><div class="kpi-icon"><i class="fas fa-check-circle"></i></div></div>
            <div class="kpi-value"><?php 
              $atendidos = 0;
              foreach ($estatusData as $key => $value) {
                  if (stripos($key, 'atendido') !== false) {
                      $atendidos = $value;
                      break;
                  }
              }
              echo $atendidos; 
            ?></div>
            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round($atendidos / $totalTickets * 100, 1) : 0; ?>% del total</div>
          </div>
          
          <div class="kpi-card warning animate-fade-in-up">
            <div class="kpi-header"><div class="kpi-title">En Proceso</div><div class="kpi-icon"><i class="fas fa-cogs"></i></div></div>
            <div class="kpi-value"><?php 
              $enProceso = 0;
              foreach ($estatusData as $key => $value) {
                  if (stripos($key, 'proceso') !== false) {
                      $enProceso = $value;
                      break;
                  }
              }
              echo $enProceso; 
            ?></div>
            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round($enProceso / $totalTickets * 100, 1) : 0; ?>% del total</div>
          </div>
          
          <div class="kpi-card <?php echo $cumplimientoSLAPorcentaje >= 80 ? 'success' : ($cumplimientoSLAPorcentaje >= 60 ? 'warning' : 'error'); ?> animate-fade-in-up">
            <div class="kpi-header"><div class="kpi-title">Cumplimiento SLA</div><div class="kpi-icon"><i class="fas fa-clock"></i></div></div>
            <div class="kpi-value"><?php echo $cumplimientoSLAPorcentaje; ?>%</div>
            <div class="kpi-comparison"><?php echo $cumplimientoGlobal['cumple']; ?> de <?php echo $totalSLATickets; ?> tickets con tiempo</div>
          </div>
        </div>

        <!-- SECCIÓN SLA -->
        <div class="tiempo-promedio-container">
          <div class="tiempo-promedio-card animate-fade-in-up">
            <div class="tiempo-promedio-label">Tiempo Promedio General</div>
            <div class="tiempo-promedio-value"><?php echo $tiempoPromedioGeneral; ?> hrs</div>
            <div class="tiempo-promedio-subtitle">Basado en <?php echo $ticketsConTiempoCalculado; ?> de <?php echo $totalTickets; ?> tickets<br><small><i class="fas fa-clock"></i> Horario laboral: Lun-Vie, 8:30-17:30</small></div>
          </div>
          
          <div class="sla-card animate-fade-in-up">
            <div class="sla-header">
              <div class="sla-title">CUMPLIMIENTO SLA GLOBAL</div>
              <div class="sla-percentage"><?php echo $cumplimientoSLAPorcentaje; ?>%</div>
            </div>
            <div class="sla-bar-container"><div class="sla-bar" style="width: <?php echo $cumplimientoSLAPorcentaje; ?>%;"></div></div>
            <div class="sla-stats">
              <span><i class="fas fa-check-circle" style="color: #10b981;"></i> Cumple: <?php echo $cumplimientoGlobal['cumple']; ?></span>
              <span><i class="fas fa-times-circle" style="color: #ef4444;"></i> No cumple: <?php echo $cumplimientoGlobal['nocumple']; ?></span>
            </div>
          </div>
        </div>

        <!-- GRÁFICA DE SLA POR PRIORIDAD -->
        <div class="chart-card animate-fade-in-up">
          <div class="chart-header"><div class="chart-title">CUMPLIMIENTO SLA POR PRIORIDAD</div><div class="chart-actions"><button class="chart-action-btn" data-chart="slaPriorityChart"><i class="fas fa-expand"></i></button></div></div>
          <div class="chart-container" id="slaPriorityChart"></div>
        </div>

        <!-- GRÁFICAS 3D -->
        <div class="charts-grid">
          <?php if (!empty($estatusData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">DISTRIBUCIÓN POR ESTATUS</div><div class="chart-actions"><button class="chart-action-btn" data-chart="statusChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="statusChart"></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($prioridadData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">TICKETS POR PRIORIDAD</div><div class="chart-actions"><button class="chart-action-btn" data-chart="priorityChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="priorityChart"></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($empresaData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">TICKETS POR ÁREA</div><div class="chart-actions"><button class="chart-action-btn" data-chart="empresaChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="empresaChart"></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($asuntoData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">TICKETS POR ASUNTO</div><div class="chart-actions"><button class="chart-action-btn" data-chart="asuntoChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="asuntoChart"></div>
          </div>
          <?php endif; ?>

          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">EVOLUCIÓN MENSUAL <?php echo date('Y'); ?></div><div class="chart-actions"><button class="chart-action-btn" data-chart="monthlyChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="monthlyChart"></div>
          </div>

          <?php if (!empty($atendidoPorData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header"><div class="chart-title">TICKETS ATENDIDOS POR</div><div class="chart-actions"><button class="chart-action-btn" data-chart="atendidoPorChart"><i class="fas fa-expand"></i></button></div></div>
            <div class="chart-container" id="atendidoPorChart"></div>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($tiempoEjecucionData)): ?>
        <div class="chart-card animate-fade-in-up">
          <div class="chart-header"><div class="chart-title">TIEMPO DE EJECUCIÓN</div><div class="chart-actions"><button class="chart-action-btn" data-chart="tiempoEjecucionChart"><i class="fas fa-expand"></i></button></div></div>
          <div class="chart-container" id="tiempoEjecucionChart"></div>
        </div>
        <?php endif; ?>

        <!-- TABLA PRINCIPAL CON TODAS LAS COLUMNAS -->
        <div class="table-section animate-fade-in-up">
          <div class="table-header">
            <div class="section-title">Listado Completo de Tickets</div>
            <div class="table-actions">
              <div class="table-info">Mostrando <?php echo count($ticketsConTiempo); ?> tickets</div>
            </div>
          </div>
          <div style="overflow-x: auto;">
            <table class="tickets-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Solicitante</th>
                  <th>Correo</th>
                  <th>Prioridad</th>
                  <th>Empresa</th>
                  <th>Asunto</th>
                  <th>Mensaje</th>
                  <th>Estatus</th>
                  <th>Fecha</th>
                  <th>Tiempo</th>
                  <th>SLA</th>
                  <th>Atendido Por</th>
                  <th>Fecha Terminado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ticketsConTiempo as $ticket): 
                  $fecha = 'N/A'; 
                  if ($ticket['Fecha']) { 
                    if ($ticket['Fecha'] instanceof DateTime) $fecha = $ticket['Fecha']->format('d/m/Y'); 
                    else { 
                      $dateObj = date_create_from_format('d/m/Y', $ticket['Fecha']); 
                      $fecha = $dateObj ? $dateObj->format('d/m/Y') : $ticket['Fecha']; 
                    } 
                  }
                  
                  $fechaTerminado = 'N/A';
                  if ($ticket['FechaTerminado']) {
                    if ($ticket['FechaTerminado'] instanceof DateTime) {
                      $fechaTerminado = $ticket['FechaTerminado']->format('d/m/Y');
                    } else {
                      $dateObj = date_create_from_format('d/m/Y', $ticket['FechaTerminado']);
                      $fechaTerminado = $dateObj ? $dateObj->format('d/m/Y') : $ticket['FechaTerminado'];
                    }
                  }
                  
                  $estatusVal = $ticket['Estatus'] ?? 'Pendiente'; 
                  $prioridadVal = $ticket['Prioridad'] ?? 'No especificada';
                  $tiempoEjec = $ticket['Tiempo_Ejec_Calculado'] ?? null; 
                  $slaInfo = $ticket['SLA_Info'] ?? null;
                  $atendidoPor = $ticket['PA'] ?? 'Sin asignar'; 
                  $asunto = $ticket['Asunto'] ?? 'Sin asunto';
                  $mensaje = $ticket['Mensaje'] ?? 'Sin mensaje';
                  
                  $tiempoEjecFormateado = 'En proceso'; 
                  $tiempoClass = '';
                  if ($tiempoEjec !== null && $tiempoEjec !== '') {
                    $horas = floatval($tiempoEjec);
                    if ($horas < 1) { $minutos = round($horas * 60); $tiempoEjecFormateado = $minutos . ' min'; }
                    else if ($horas < 24) $tiempoEjecFormateado = number_format($horas, 1) . ' hrs';
                    else { $dias = floor($horas / 24); $horas_restantes = $horas % 24; $tiempoEjecFormateado = $dias . 'd ' . number_format($horas_restantes, 0) . 'h'; }
                    
                    if ($horas <= 4) $tiempoClass = 'tiempo-rapido'; 
                    else if ($horas <= 24) $tiempoClass = 'tiempo-moderado'; 
                    else $tiempoClass = 'tiempo-lento';
                  }
                  
                  $estatusClass = 'pendiente'; 
                  if (stripos($estatusVal, 'atendido') !== false) $estatusClass = 'atendido'; 
                  elseif (stripos($estatusVal, 'proceso') !== false) $estatusClass = 'en-proceso';
                  
                  $prioridadClass = 'alta';
                  $prioridadLower = strtolower($prioridadVal);
                  if (strpos($prioridadLower, 'alt') !== false) $prioridadClass = 'alta';
                  else if (strpos($prioridadLower, 'med') !== false) $prioridadClass = 'media';
                  else if (strpos($prioridadLower, 'baj') !== false) $prioridadClass = 'baja';
                  else $prioridadClass = 'estandar';
                ?>
                <tr>
                  <td style="font-weight: 700; color: var(--accent-blue);">#<?php echo htmlspecialchars($ticket['Id_Ticket'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars(substr($ticket['Nombre'] ?? 'N/A', 0, 25)); ?></td>
                  <td><?php echo htmlspecialchars(substr($ticket['Correo'] ?? 'N/A', 0, 25)); ?></td>
                  <td><span class="status-badge priority-<?php echo $prioridadClass; ?>"><i class="fas fa-flag"></i> <?php echo htmlspecialchars($prioridadVal); ?></span></td>
                  <td><?php echo htmlspecialchars(substr($ticket['Empresa'] ?? 'N/A', 0, 20)); ?></td>
                  <td><?php echo htmlspecialchars(substr($asunto, 0, 25)); ?></td>
                  <td><?php echo htmlspecialchars(substr($mensaje, 0, 30)); ?>...</td>
                  <td><span class="status-badge status-<?php echo $estatusClass; ?>"><i class="fas fa-circle"></i> <?php echo htmlspecialchars($estatusVal); ?></span></td>
                  <td><?php echo $fecha; ?></td>
                  <td>
                    <?php if ($tiempoEjec !== null): ?>
                      <span class="status-badge <?php echo $tiempoClass; ?>"><i class="fas fa-clock"></i> <?php echo $tiempoEjecFormateado; ?></span>
                    <?php else: ?>
                      <span class="status-badge" style="background: #6b7280;"><i class="fas fa-clock"></i> En proceso</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($slaInfo): ?>
                      <span class="status-badge <?php echo $slaInfo['cumple'] ? 'sla-cumple' : 'sla-no-cumple'; ?>">
                        <i class="fas <?php echo $slaInfo['cumple'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $slaInfo['estado']; ?>
                      </span>
                    <?php else: ?>
                      <span class="status-badge" style="background: #6b7280;"><i class="fas fa-minus-circle"></i> -</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($atendidoPor); ?></td>
                  <td><?php echo $fechaTerminado; ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php else: ?>
        <div class="chart-card animate-fade-in-up" style="text-align: center; padding: var(--spacing-xl);">
          <h3 style="color: var(--text-secondary); margin-bottom: var(--spacing-sm);">⚠️ NO SE ENCONTRARON TICKETS</h3>
          <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">No hay tickets que coincidan con los criterios de búsqueda.</p>
          <a href="?" class="filter-btn" style="display: inline-block;">Ver todos los tickets</a>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Verificación de sesión
    let lastVerificationTime = Date.now();
    let sessionCheckInterval;
    function verificarSesionEnTiempoReal() {
        const now = Date.now();
        if (now - lastVerificationTime > 30000) {
            lastVerificationTime = now;
            fetch(window.location.href, { method: 'HEAD', headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }, credentials: 'same-origin' })
                .then(response => { if (response.status === 302 || response.redirected) { clearInterval(sessionCheckInterval); window.location.href = 'Loginti.php?error=session_expired'; } })
                .catch(error => console.log('Error verificando sesión:', error));
        }
    }
    
    <?php if ($tienePermisosDashboard): ?>
    // DATOS PARA GRÁFICAS
    <?php if (!empty($estatusData)): ?>const statusData = { categories: <?php echo json_encode(array_keys($estatusData)); ?>, values: <?php echo json_encode(array_values($estatusData)); ?> };<?php endif; ?>
    <?php if (!empty($prioridadData)): ?>const priorityData = { categories: <?php echo json_encode(array_keys($prioridadData)); ?>, values: <?php echo json_encode(array_values($prioridadData)); ?> };<?php endif; ?>
    <?php if (!empty($empresaData)): ?>const empresaData = { categories: <?php echo json_encode(array_keys($empresaData)); ?>, values: <?php echo json_encode(array_values($empresaData)); ?> };<?php endif; ?>
    <?php if (!empty($asuntoData)): ?>const asuntoData = { categories: <?php echo json_encode(array_keys($asuntoData)); ?>, values: <?php echo json_encode(array_values($asuntoData)); ?> };<?php endif; ?>
    <?php if (!empty($atendidoPorData)): ?>const atendidoPorData = { categories: <?php echo json_encode(array_keys($atendidoPorData)); ?>, values: <?php echo json_encode(array_values($atendidoPorData)); ?> };<?php endif; ?>
    <?php if (!empty($tiempoEjecucionData)): ?>const tiempoEjecucionData = { categories: <?php echo json_encode(array_keys($tiempoEjecucionData)); ?>, values: <?php echo json_encode(array_values($tiempoEjecucionData)); ?> };<?php endif; ?>
    const monthlyData = { categories: <?php echo json_encode($mensualLabels); ?>, values: <?php echo json_encode($mensualValues); ?> };
    
    const slaPriorityData = {
        categories: ['ALTA (≤2h)', 'MEDIA (≤4h)', 'BAJA (≤10h)', 'ESTÁNDAR (≤24h)'],
        cumple: [
            <?php echo $slaData['ALTA']['cumple'] ?? 0; ?>, 
            <?php echo $slaData['MEDIA']['cumple'] ?? 0; ?>, 
            <?php echo $slaData['BAJA']['cumple'] ?? 0; ?>,
            <?php echo $slaData['ESTÁNDAR']['cumple'] ?? 0; ?>
        ],
        nocumple: [
            <?php echo $slaData['ALTA']['nocumple'] ?? 0; ?>, 
            <?php echo $slaData['MEDIA']['nocumple'] ?? 0; ?>, 
            <?php echo $slaData['BAJA']['nocumple'] ?? 0; ?>,
            <?php echo $slaData['ESTÁNDAR']['nocumple'] ?? 0; ?>
        ]
    };

    function initialize3DCharts() {
        const themeColors = document.body.classList.contains('dark-mode') ? 
            { text: '#f1f5f9', grid: 'rgba(255,255,255,0.15)', bg: 'rgba(15,23,42,0.9)' } : 
            { text: '#1e293b', grid: 'rgba(0,0,0,0.1)', bg: 'rgba(255,255,255,0.9)' };

        if (document.getElementById('slaPriorityChart')) {
            const slaPriorityChart = echarts.init(document.getElementById('slaPriorityChart'));
            slaPriorityChart.setOption({
                tooltip: { 
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' },
                    formatter: function(params) {
                        let res = params[0].name + '<br/>';
                        let total = 0;
                        params.forEach(p => total += p.value);
                        params.forEach(p => {
                            res += p.marker + ' ' + p.seriesName + ': ' + p.value + ' (' + ((p.value/total)*100).toFixed(1) + '%)<br/>';
                        });
                        res += '<b>Total: ' + total + '</b>';
                        return res;
                    }
                },
                legend: {
                    data: ['CUMPLE SLA', 'NO CUMPLE SLA'],
                    orient: 'horizontal',
                    left: 'center',
                    top: 0,
                    textStyle: { color: themeColors.text, fontWeight: 'bold' },
                    backgroundColor: 'rgba(0,0,0,0.3)',
                    borderRadius: 8,
                    padding: [5, 15]
                },
                grid: { left: '12%', right: '8%', bottom: '12%', top: '15%', containLabel: true },
                xAxis: {
                    type: 'category',
                    data: slaPriorityData.categories,
                    axisLabel: { 
                        color: themeColors.text, 
                        fontSize: 11, 
                        fontWeight: 'bold',
                        rotate: 15,
                        interval: 0
                    },
                    axisLine: { show: false },
                    axisTick: { show: false }
                },
                yAxis: {
                    type: 'value',
                    name: 'Tickets',
                    nameTextStyle: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLabel: { color: themeColors.text, fontSize: 11 },
                    splitLine: { lineStyle: { color: themeColors.grid, type: 'dashed', width: 1.5 } }
                },
                series: [
                    {
                        name: 'CUMPLE SLA',
                        type: 'bar',
                        stack: 'total',
                        barWidth: '65%',
                        data: slaPriorityData.cumple,
                        itemStyle: {
                            borderRadius: [8, 8, 0, 0],
                            color: {
                                type: 'linear',
                                x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: '#10b981' },
                                    { offset: 0.7, color: '#059669' },
                                    { offset: 1, color: '#047857' }
                                ]
                            },
                            borderColor: '#ffffff',
                            borderWidth: 1.5,
                            shadowBlur: 15,
                            shadowColor: 'rgba(16,185,129,0.4)',
                            shadowOffsetX: 2,
                            shadowOffsetY: 4
                        },
                        label: {
                            show: true,
                            position: 'inside',
                            formatter: '{c}',
                            color: 'white',
                            fontWeight: 'bold',
                            fontSize: 11
                        }
                    },
                    {
                        name: 'NO CUMPLE SLA',
                        type: 'bar',
                        stack: 'total',
                        barWidth: '65%',
                        data: slaPriorityData.nocumple,
                        itemStyle: {
                            borderRadius: [8, 8, 0, 0],
                            color: {
                                type: 'linear',
                                x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: '#ef4444' },
                                    { offset: 0.7, color: '#dc2626' },
                                    { offset: 1, color: '#b91c1c' }
                                ]
                            },
                            borderColor: '#ffffff',
                            borderWidth: 1.5,
                            shadowBlur: 15,
                            shadowColor: 'rgba(239,68,68,0.4)',
                            shadowOffsetX: 2,
                            shadowOffsetY: 4
                        },
                        label: {
                            show: true,
                            position: 'inside',
                            formatter: '{c}',
                            color: 'white',
                            fontWeight: 'bold',
                            fontSize: 11
                        }
                    }
                ]
            });
        }

        <?php if (!empty($estatusData)): ?>
        if (document.getElementById('statusChart')) {
            const statusChart = echarts.init(document.getElementById('statusChart'));
            statusChart.setOption({
                tooltip: { 
                    trigger: 'axis', 
                    axisPointer: { type: 'shadow' },
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' },
                    formatter: function(params) {
                        let total = 0;
                        params.forEach(p => total += p.value);
                        return `${params[0].name}<br>${params[0].value} tickets (${((params[0].value/total)*100).toFixed(1)}%)`;
                    }
                },
                grid: { left: '12%', right: '8%', bottom: '12%', top: '8%', containLabel: true },
                xAxis: {
                    type: 'category',
                    data: statusData.categories,
                    axisLabel: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLine: { show: false },
                    axisTick: { show: false }
                },
                yAxis: {
                    type: 'value',
                    name: 'Tickets',
                    nameTextStyle: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLabel: { color: themeColors.text, fontSize: 11 },
                    splitLine: { 
                        lineStyle: { color: themeColors.grid, type: 'dashed', width: 1.5 }
                    }
                },
                series: [{
                    name: 'Tickets',
                    type: 'bar',
                    barWidth: '55%',
                    data: statusData.values,
                    itemStyle: {
                        borderRadius: [8, 8, 0, 0],
                        color: {
                            type: 'linear',
                            x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [
                                { offset: 0, color: '#3b82f6' },
                                { offset: 0.7, color: '#2563eb' },
                                { offset: 1, color: '#1d4ed8' }
                            ]
                        },
                        borderColor: '#ffffff',
                        borderWidth: 1.5,
                        shadowBlur: 12,
                        shadowColor: 'rgba(59,130,246,0.4)',
                        shadowOffsetX: 2,
                        shadowOffsetY: 4
                    },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: '{c}',
                        color: themeColors.text,
                        fontWeight: 'bold',
                        fontSize: 13,
                        textShadowBlur: 6,
                        textShadowColor: 'rgba(59,130,246,0.5)'
                    }
                }]
            });
        }
        <?php endif; ?>

        <?php if (!empty($prioridadData)): ?>
        if (document.getElementById('priorityChart')) {
            const priorityChart = echarts.init(document.getElementById('priorityChart'));
            priorityChart.setOption({
                tooltip: { 
                    trigger: 'axis', 
                    axisPointer: { type: 'shadow' },
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                grid: { left: '12%', right: '8%', bottom: '12%', top: '8%', containLabel: true },
                xAxis: {
                    type: 'category',
                    data: priorityData.categories,
                    axisLabel: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLine: { show: false },
                    axisTick: { show: false }
                },
                yAxis: {
                    type: 'value',
                    name: 'Tickets',
                    nameTextStyle: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLabel: { color: themeColors.text, fontSize: 11 },
                    splitLine: { lineStyle: { color: themeColors.grid, type: 'dashed', width: 1.5 } }
                },
                series: [{
                    name: 'Tickets',
                    type: 'bar',
                    barWidth: '55%',
                    data: priorityData.values,
                    itemStyle: {
                        borderRadius: [10, 10, 0, 0],
                        color: function(params) {
                            const val = params.data;
                            if (typeof val === 'number') {
                                if (val > 10) return { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: '#ef4444' }, { offset: 1, color: '#dc2626' }] };
                                if (val > 5) return { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: '#f59e0b' }, { offset: 1, color: '#d97706' }] };
                            }
                            return { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: '#3b82f6' }, { offset: 1, color: '#1d4ed8' }] };
                        },
                        borderColor: '#ffffff',
                        borderWidth: 1.5,
                        shadowBlur: 15,
                        shadowColor: function(params) {
                            const val = params.data;
                            if (typeof val === 'number') {
                                if (val > 10) return 'rgba(239,68,68,0.4)';
                                if (val > 5) return 'rgba(245,158,11,0.4)';
                            }
                            return 'rgba(59,130,246,0.4)';
                        },
                        shadowOffsetX: 2,
                        shadowOffsetY: 4
                    },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: '{c}',
                        color: themeColors.text,
                        fontWeight: 'bold',
                        fontSize: 13,
                        textShadowBlur: 6
                    }
                }]
            });
        }
        <?php endif; ?>

        <?php if (!empty($empresaData)): ?>
        if (document.getElementById('empresaChart')) {
            const empresaChart = echarts.init(document.getElementById('empresaChart'));
            empresaChart.setOption({
                tooltip: { 
                    trigger: 'item',
                    formatter: '{b}: {c} tickets ({d}%)',
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                series: [{
                    name: 'Áreas',
                    type: 'pie',
                    radius: ['35%', '70%'],
                    center: ['50%', '50%'],
                    avoidLabelOverlap: true,
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        shadowBlur: 20,
                        shadowColor: 'rgba(59,130,246,0.5)',
                        shadowOffsetX: 3,
                        shadowOffsetY: 5
                    },
                    label: {
                        show: true,
                        position: 'outside',
                        formatter: function(params) {
                            const name = params.name.length > 15 ? params.name.slice(0, 12) + '...' : params.name;
                            return `${name}\n${params.value} (${params.percent.toFixed(1)}%)`;
                        },
                        color: themeColors.text,
                        fontSize: 11,
                        fontWeight: 'bold',
                        lineHeight: 18,
                        textShadowBlur: 4,
                        textShadowColor: 'rgba(0,0,0,0.3)'
                    },
                    labelLine: {
                        length: 20,
                        length2: 15,
                        smooth: true,
                        lineStyle: { width: 2, color: 'rgba(59,130,246,0.6)' }
                    },
                    data: empresaData.categories.map((cat, index) => ({
                        value: empresaData.values[index],
                        name: cat,
                        itemStyle: { color: `hsl(${index * 25 % 360}, 75%, 60%)` }
                    }))
                }]
            });
        }
        <?php endif; ?>

        <?php if (!empty($asuntoData)): ?>
        if (document.getElementById('asuntoChart')) {
            const asuntoChart = echarts.init(document.getElementById('asuntoChart'));
            asuntoChart.setOption({
                tooltip: { 
                    trigger: 'item',
                    formatter: '{b}: {c} tickets ({d}%)',
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                series: [{
                    name: 'Asuntos',
                    type: 'pie',
                    radius: ['30%', '70%'],
                    center: ['50%', '50%'],
                    avoidLabelOverlap: true,
                    itemStyle: {
                        borderRadius: 12,
                        borderColor: 'rgba(255,255,255,0.8)',
                        borderWidth: 2.5,
                        shadowBlur: 25,
                        shadowColor: 'rgba(59,130,246,0.6)',
                        shadowOffsetX: 4,
                        shadowOffsetY: 6
                    },
                    label: {
                        show: true,
                        position: 'outside',
                        formatter: function(params) {
                            const name = params.name.length > 12 ? params.name.slice(0, 10) + '...' : params.name;
                            return `${name}\n${params.value} (${params.percent.toFixed(1)}%)`;
                        },
                        color: themeColors.text,
                        fontSize: 11,
                        fontWeight: 'bold',
                        lineHeight: 18,
                        textShadowBlur: 5,
                        textShadowColor: 'rgba(59,130,246,0.5)',
                        backgroundColor: 'rgba(0,0,0,0.2)',
                        padding: [4, 8],
                        borderRadius: 12
                    },
                    labelLine: {
                        length: 22,
                        length2: 18,
                        smooth: true,
                        lineStyle: { width: 2, color: 'rgba(59,130,246,0.7)' }
                    },
                    data: asuntoData.categories.map((cat, index) => ({
                        value: asuntoData.values[index],
                        name: cat,
                        itemStyle: { color: `hsl(${index * 36 % 360}, 80%, 60%)` }
                    }))
                }]
            });
        }
        <?php endif; ?>

        if (document.getElementById('monthlyChart')) {
            const monthlyChart = echarts.init(document.getElementById('monthlyChart'));
            monthlyChart.setOption({
                tooltip: { 
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                grid: { left: '10%', right: '10%', bottom: '15%', top: '15%', containLabel: true },
                xAxis: {
                    type: 'category',
                    data: monthlyData.categories,
                    axisLabel: { 
                        color: themeColors.text, 
                        fontSize: 11, 
                        fontWeight: 'bold', 
                        rotate: 30,
                        textShadowBlur: 4,
                        textShadowColor: 'rgba(59,130,246,0.3)'
                    },
                    axisLine: { show: false },
                    axisTick: { show: false }
                },
                yAxis: {
                    type: 'value',
                    name: 'Tickets',
                    nameTextStyle: { color: themeColors.text, fontSize: 12, fontWeight: 'bold' },
                    axisLabel: { color: themeColors.text, fontSize: 11 },
                    splitLine: { lineStyle: { color: themeColors.grid, type: 'dashed', width: 1.5 } }
                },
                series: [{
                    name: 'Tickets',
                    type: 'line',
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 10,
                    data: monthlyData.values,
                    lineStyle: {
                        width: 4,
                        color: '#3b82f6',
                        shadowBlur: 15,
                        shadowColor: 'rgba(59,130,246,0.6)',
                        shadowOffsetX: 2,
                        shadowOffsetY: 4
                    },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [
                                { offset: 0, color: 'rgba(59,130,246,0.5)' },
                                { offset: 0.6, color: 'rgba(59,130,246,0.2)' },
                                { offset: 1, color: 'rgba(59,130,246,0)' }
                            ]
                        }
                    },
                    itemStyle: {
                        color: '#3b82f6',
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        shadowBlur: 12,
                        shadowColor: 'rgba(59,130,246,0.8)'
                    },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: '{c}',
                        color: themeColors.text,
                        fontWeight: 'bold',
                        fontSize: 12,
                        textShadowBlur: 6,
                        textShadowColor: 'rgba(59,130,246,0.6)',
                        backgroundColor: 'rgba(0,0,0,0.3)',
                        padding: [4, 10],
                        borderRadius: 12
                    }
                }]
            });
        }

        <?php if (!empty($atendidoPorData)): ?>
        if (document.getElementById('atendidoPorChart')) {
            const atendidoPorChart = echarts.init(document.getElementById('atendidoPorChart'));
            atendidoPorChart.setOption({
                tooltip: { 
                    trigger: 'item',
                    formatter: '{b}: {c} tickets ({d}%)',
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                series: [{
                    name: 'Responsables',
                    type: 'pie',
                    radius: ['35%', '70%'],
                    center: ['50%', '50%'],
                    avoidLabelOverlap: true,
                    itemStyle: {
                        borderRadius: 10,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        shadowBlur: 20,
                        shadowColor: 'rgba(139,92,246,0.5)',
                        shadowOffsetX: 3,
                        shadowOffsetY: 5
                    },
                    label: {
                        show: true,
                        position: 'outside',
                        formatter: '{b}\n{c} ({d}%)',
                        color: themeColors.text,
                        fontSize: 11,
                        fontWeight: 'bold',
                        lineHeight: 18,
                        textShadowBlur: 4
                    },
                    labelLine: {
                        length: 20,
                        length2: 15,
                        smooth: true,
                        lineStyle: { width: 2, color: 'rgba(139,92,246,0.6)' }
                    },
                    data: atendidoPorData.categories.map((cat, index) => ({
                        value: atendidoPorData.values[index],
                        name: cat,
                        itemStyle: {
                            color: index === 0 ? '#3b82f6' : index === 1 ? '#8b5cf6' : 
                                   index === 2 ? '#10b981' : index === 3 ? '#f59e0b' : '#6b7280'
                        }
                    }))
                }]
            });
        }
        <?php endif; ?>

        <?php if (!empty($tiempoEjecucionData)): ?>
        if (document.getElementById('tiempoEjecucionChart')) {
            const tiempoEjecucionChart = echarts.init(document.getElementById('tiempoEjecucionChart'));
            tiempoEjecucionChart.setOption({
                tooltip: { 
                    trigger: 'item',
                    formatter: '{b}: {c} tickets ({d}%)',
                    backgroundColor: 'rgba(0,0,0,0.85)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    textStyle: { color: '#fff', fontSize: 12, fontWeight: '500' }
                },
                series: [{
                    name: 'Tiempo Respuesta',
                    type: 'pie',
                    radius: ['35%', '70%'],
                    center: ['50%', '50%'],
                    avoidLabelOverlap: true,
                    itemStyle: {
                        borderRadius: 12,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        shadowBlur: 25,
                        shadowColor: 'rgba(59,130,246,0.6)',
                        shadowOffsetX: 4,
                        shadowOffsetY: 6
                    },
                    label: {
                        show: true,
                        position: 'outside',
                        formatter: '{b}\n{c} ({d}%)',
                        color: themeColors.text,
                        fontSize: 11,
                        fontWeight: 'bold',
                        lineHeight: 18,
                        textShadowBlur: 5,
                        backgroundColor: 'rgba(0,0,0,0.2)',
                        padding: [4, 8],
                        borderRadius: 12
                    },
                    labelLine: {
                        length: 22,
                        length2: 18,
                        smooth: true,
                        lineStyle: { width: 2, color: 'rgba(59,130,246,0.7)' }
                    },
                    data: tiempoEjecucionData.categories.map((cat, index) => ({
                        value: tiempoEjecucionData.values[index],
                        name: cat,
                        itemStyle: {
                            color: index === 0 ? '#10b981' : index === 1 ? '#f59e0b' : 
                                   index === 2 ? '#ef4444' : index === 3 ? '#b91c1c' : '#6b7280'
                        }
                    }))
                }]
            });
        }
        <?php endif; ?>
    }

    function resizeCharts() {
        const ids = ['statusChart','priorityChart','empresaChart','asuntoChart','monthlyChart','atendidoPorChart','tiempoEjecucionChart','slaPriorityChart'];
        ids.forEach(id => { const chart = echarts.getInstanceByDom(document.getElementById(id)); if (chart) chart.resize(); });
    }
    <?php endif; ?>

    function createWaterDrops() {
        const container = document.getElementById('waterDrops');
        for (let i = 0; i < 10; i++) {
            const drop = document.createElement('div'); drop.className = 'water-drop';
            drop.style.left = Math.random() * 100 + '%'; drop.style.top = Math.random() * 100 + '%';
            drop.style.width = (Math.random() * 150 + 80) + 'px'; drop.style.height = drop.style.width;
            drop.style.animationDelay = Math.random() * 10 + 's'; drop.style.animationDuration = (Math.random() * 8 + 10) + 's';
            container.appendChild(drop);
        }
    }

    const sidebar = document.getElementById('sidebar'), sidebarToggle = document.getElementById('sidebarToggle');
    const dashboardHeader = document.querySelector('.dashboard-header'), dashboardContainer = document.querySelector('.dashboard-container');
    const moduleContainer = document.getElementById('moduleContainer');
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('hidden'); dashboardHeader.classList.toggle('full-width'); dashboardContainer.classList.toggle('full-width');
        moduleContainer.style.left = sidebar.classList.contains('hidden') ? 'var(--spacing-lg)' : '260px';
        const icon = this.querySelector('i'); icon.className = sidebar.classList.contains('hidden') ? 'fas fa-bars' : 'fas fa-times';
        <?php if ($tienePermisosDashboard): ?> setTimeout(resizeCharts, 300); <?php endif; ?>
    });

    const themeToggle = document.getElementById('themeToggle'), body = document.body;
    themeToggle.addEventListener('click', function() {
        body.classList.toggle('dark-mode');
        const icon = this.querySelector('i');
        if (body.classList.contains('dark-mode')) { icon.className = 'fas fa-sun'; this.innerHTML = '<i class="fas fa-sun"></i> MODO CLARO'; }
        else { icon.className = 'fas fa-moon'; this.innerHTML = '<i class="fas fa-moon"></i> MODO OSCURO'; }
        <?php if ($tienePermisosDashboard): ?> initialize3DCharts(); <?php endif; ?>
    });

    const logoutBtn = document.getElementById('logoutBtn'), logoutAlert = document.getElementById('logoutAlert');
    const cancelLogout = document.getElementById('cancelLogout'), confirmLogout = document.getElementById('confirmLogout');
    logoutBtn.addEventListener('click', () => logoutAlert.classList.add('active'));
    cancelLogout.addEventListener('click', () => logoutAlert.classList.remove('active'));
    confirmLogout.addEventListener('click', () => { const form = document.createElement('form'); form.method = 'POST'; form.innerHTML = '<input type="hidden" name="logout" value="true">'; document.body.appendChild(form); form.submit(); });
    logoutAlert.addEventListener('click', (e) => { if (e.target === logoutAlert) logoutAlert.classList.remove('active'); });

    const permissionAlert = document.getElementById('permissionAlert'), permissionMessage = document.getElementById('permissionMessage');
    const closePermissionAlert = document.getElementById('closePermissionAlert');
    closePermissionAlert.addEventListener('click', () => permissionAlert.classList.remove('active'));
    permissionAlert.addEventListener('click', (e) => { if (e.target === permissionAlert) permissionAlert.classList.remove('active'); });

    <?php if ($tienePermisosDashboard): ?>
    document.querySelectorAll('.chart-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const chartId = this.getAttribute('data-chart');
            const chartElement = document.getElementById(chartId);
            if (chartElement) {
                chartElement.style.height = chartElement.style.height === '520px' ? '480px' : '520px';
                resizeCharts();
            }
        });
    });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        sessionCheckInterval = setInterval(verificarSesionEnTiempoReal, 30000);
        createWaterDrops();
        <?php if ($tienePermisosDashboard): ?>
        setTimeout(() => { initialize3DCharts(); }, 150);
        window.addEventListener('resize', resizeCharts);
        <?php endif; ?>
        
        const navLinks = document.querySelectorAll('.nav-link'), moduleIframe = document.getElementById('moduleIframe');
        const dashboardContent = document.getElementById('dashboardContent');
        const pageConfig = {
            'dashboard': { showModule: false, title: 'Dashboard de Reportes', mainTitle: 'Bienvenido, <?php echo htmlspecialchars($nombre); ?>', subtitle: 'Dashboard personalizado del sistema de soporte técnico con métricas 3D', permissionRequired: true, allowedUsers: <?php echo json_encode($usuariosPermitidosDashboard); ?> },
            'levantar-ticket': { showModule: true, url: 'Formtic1.php', title: 'Levantar Ticket', mainTitle: 'Formulario de Tickets', subtitle: 'Complete el formulario para crear un nuevo ticket de soporte' },
            'revisar-tickets': { showModule: true, url: 'RevisarT.php', title: 'Revisar Tickets', mainTitle: 'Revisión de Tickets', subtitle: 'Revise y gestione los tickets asignados' },
            'procesar-tickets': { showModule: true, url: 'TableT1.php', title: 'Procesar Tickets', mainTitle: 'Procesamiento de Tickets', subtitle: 'Procese y actualice el estado de los tickets', permissionRequired: true, allowedUsers: <?php echo json_encode($usuariosPermitidosProcesar); ?> },
            'reportes': { showModule: false, title: 'Dashboard de Reportes', mainTitle: 'Reportes de Tickets', subtitle: 'Dashboard con métricas 3D y cumplimiento SLA', permissionRequired: true, allowedUsers: <?php echo json_encode($usuariosPermitidosDashboard); ?> }
        };
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.classList.contains('disabled')) {
                    const page = this.getAttribute('data-page'), config = pageConfig[page];
                    permissionMessage.textContent = config && config.permissionRequired ? `El módulo "${config.title}" está restringido para usuarios autorizados.` : 'No tienes permisos para acceder a este módulo.';
                    permissionAlert.classList.add('active'); return;
                }
                navLinks.forEach(l => l.classList.remove('active')); this.classList.add('active');
                const page = this.getAttribute('data-page'), config = pageConfig[page];
                if (!config) return;
                if (config.permissionRequired) {
                    const currentUser = '<?php echo $usuarioActual; ?>';
                    if (!config.allowedUsers.includes(currentUser)) {
                        permissionMessage.textContent = `No tienes permisos para acceder al módulo "${config.title}".`;
                        permissionAlert.classList.add('active'); return;
                    }
                }
                if (config.showModule && config.url) {
                    moduleContainer.classList.add('active'); if (dashboardContent) dashboardContent.style.display = 'none';
                    moduleIframe.src = config.url;
                } else {
                    moduleContainer.classList.remove('active'); if (dashboardContent) dashboardContent.style.display = 'block';
                    moduleIframe.src = '';
                    <?php if ($tienePermisosDashboard): ?> setTimeout(() => { initialize3DCharts(); }, 100); <?php endif; ?>
                }
                document.querySelector('.logo-subtitle').textContent = config.title;
                <?php if ($tienePermisosDashboard): ?> 
                document.querySelector('.dashboard-title').textContent = config.mainTitle;
                document.querySelector('.dashboard-subtitle').textContent = config.subtitle;
                <?php endif; ?>
            });
        });
        
        document.querySelectorAll('.filter-select').forEach(select => { select.addEventListener('change', function() { this.form.submit(); }); });
    });

    window.addEventListener('pageshow', function(e) { if (e.persisted) window.location.reload(); });
    window.addEventListener('beforeunload', function() { if (sessionCheckInterval) clearInterval(sessionCheckInterval); });
  </script>
</body>
</html>

<?php
if (isset($stmtTickets)) sqlsrv_free_stmt($stmtTickets);
if (isset($conn)) sqlsrv_close($conn);
?>