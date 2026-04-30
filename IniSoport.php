<?php
/**
 * @file IniSoport.php
 * @brief Dashboard principal del Sistema de Tickets con métricas SLA y gestión de permisos.
 *
 * Migrado para consumir la API REST (Rust/Axum) en lugar de sqlsrv directo.
 * Todos los datos de tickets se obtienen vía:
 *   GET /api/TicketBacros/tickets  (lista con filtros)
 *   GET /api/TicketBacros/tickets?fecha_inicio=YYYY-01-01&fecha_fin=YYYY-12-31 (mensual año)
 *
 * @version 4.0 (API-only)
 */

session_start();

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
// Lax para mantener consistencia con Loginti.php / auth_check.php — Strict
// rompe la sesión cuando el usuario llega desde un link externo (email).
ini_set('session.cookie_samesite', 'Lax');

header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// SISTEMA DE PERMISOS POR USUARIO
$usuariosPermitidosDashboard = ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio'];
$usuariosPermitidosProcesar  = ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio'];

$usuarioActual = $_SESSION['user_username'] ?? '';
$tienePermisosDashboard = in_array($usuarioActual, $usuariosPermitidosDashboard);

// VERIFICACIÓN DE SESIÓN
$session_valid = false;
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
    isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {

    $session_timeout    = 8 * 60 * 60;
    $inactivity_timeout = 30 * 60;

    if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] <= $session_timeout)) {
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] <= $inactivity_timeout)) {
            $session_valid = true;
            $_SESSION['LAST_ACTIVITY'] = time();

            if (!isset($_SESSION['session_token']))  $_SESSION['session_token']  = bin2hex(random_bytes(32));
            if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 300)) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
}

if (!$session_valid) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: Loginti.php?error=session_expired");
    exit();
}

$nombre      = $_SESSION['user_name']     ?? 'Usuario';
$id_empleado = $_SESSION['user_id']       ?? 'N/A';
$area        = $_SESSION['user_area']     ?? 'N/A';
$usuario     = $_SESSION['user_username'] ?? 'N/A';

if (isset($_POST['logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: Loginti.php");
    exit();
}

// API URL
require_once __DIR__ . '/config.php';
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

// ─── Helper: llamada HTTP GET a la API ───────────────────────────────────────
function api_get(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status !== 200 || $body === false) return null;
    return json_decode($body, true);
}

// ─── Funciones de negocio (sin cambios de lógica) ────────────────────────────
function verificarSLA($prioridad, $tiempo_ejecucion) {
    if ($tiempo_ejecucion === null || $tiempo_ejecucion === '' || !is_numeric($tiempo_ejecucion)) return null;
    $horas           = floatval($tiempo_ejecucion);
    $prioridad_lower = strtolower(trim($prioridad ?? ''));

    if (strpos($prioridad_lower, 'alt') !== false || strpos($prioridad_lower, 'urg') !== false) {
        $sla_max = 2; $descripcion = 'ALTA/URGENTE (≤2h)'; $categoria = 'ALTA'; $cumple = ($horas <= 2);
    } elseif (strpos($prioridad_lower, 'med') !== false) {
        $sla_max = 4; $descripcion = 'MEDIA (≤4h)'; $categoria = 'MEDIA'; $cumple = ($horas <= 4);
    } elseif (strpos($prioridad_lower, 'baj') !== false) {
        $sla_max = 10; $descripcion = 'BAJA (≤10h)'; $categoria = 'BAJA'; $cumple = ($horas <= 10);
    } else {
        $sla_max = 24; $descripcion = 'ESTÁNDAR (≤24h)'; $categoria = 'ESTÁNDAR'; $cumple = ($horas <= 24);
    }
    return [
        'cumple'      => $cumple,
        'estado'      => $cumple ? 'CUMPLE' : 'NO CUMPLE',
        'sla_max'     => $sla_max,
        'color'       => $cumple ? '#10b981' : '#ef4444',
        'descripcion' => $descripcion,
        'categoria'   => $categoria,
        'horas'       => $horas,
    ];
}

function calcularHorasLaborales($fecha_inicio, $hora_inicio, $fecha_fin, $hora_fin, $hora_original_ticket = null) {
    if (!$fecha_inicio || !$fecha_fin) return null;
    try {
        if ($hora_inicio instanceof DateTime) $hora_inicio = $hora_inicio->format('H:i:s');
        if ($hora_fin    instanceof DateTime) $hora_fin    = $hora_fin->format('H:i:s');
        if ($fecha_inicio instanceof DateTime) $fecha_inicio = $fecha_inicio->format('Y-m-d');
        if ($fecha_fin   instanceof DateTime) $fecha_fin    = $fecha_fin->format('Y-m-d');

        if (is_string($fecha_inicio) && strpos($fecha_inicio, '/') !== false && !empty($hora_original_ticket)) {
            $hora_inicio = $hora_original_ticket;
        }

        $hora_inicio = preg_replace('/\.\d+$/', '', trim($hora_inicio ?? '08:30:00'));
        $hora_fin    = preg_replace('/\.\d+$/', '', trim($hora_fin    ?? '17:30:00'));
        if (empty($hora_inicio)) $hora_inicio = '08:30:00';
        if (empty($hora_fin))    $hora_fin    = '17:30:00';
        if (strlen($hora_inicio) == 5) $hora_inicio .= ':00';
        if (strlen($hora_fin)    == 5) $hora_fin    .= ':00';

        $fi = is_string($fecha_inicio) ? $fecha_inicio : '';
        if (strpos($fi, '/') !== false) {
            $p = explode('/', $fi);
            if (count($p) == 3) $fi = $p[2] . '-' . $p[1] . '-' . $p[0];
        }
        $ff = is_string($fecha_fin) ? $fecha_fin : '';
        if (strpos($ff, '/') !== false) {
            $p = explode('/', $ff);
            if (count($p) == 3) $ff = $p[2] . '-' . $p[1] . '-' . $p[0];
        }

        $inicio = new DateTime($fi . ' ' . $hora_inicio);
        $fin    = new DateTime($ff . ' ' . $hora_fin);
        if ($fin <= $inicio) return 0;

        $horas_totales = 0;
        $fecha_actual  = clone $inicio;
        while ($fecha_actual < $fin) {
            $dia = (int)$fecha_actual->format('N');
            if ($dia <= 5) {
                $ini_dia = clone $fecha_actual; $ini_dia->setTime(8, 30, 0);
                $fin_dia = clone $fecha_actual; $fin_dia->setTime(17, 30, 0);
                $ie = ($fecha_actual > $ini_dia) ? clone $fecha_actual : $ini_dia;
                $fe = ($fin < $fin_dia)          ? clone $fin           : $fin_dia;
                if ($fe > $ie) {
                    $d = $fe->diff($ie);
                    $horas_totales += $d->h + ($d->i / 60) + ($d->s / 3600);
                }
            }
            $fecha_actual->modify('+1 day');
            $fecha_actual->setTime(8, 30, 0);
        }
        return round($horas_totales, 2);
    } catch (Exception $e) {
        return 0;
    }
}

// ─── Normalizar un ticket recibido de la API (campos idénticos a sqlsrv) ─────
function normalizar_ticket(array $t): array {
    // La API devuelve snake_case; el código HTML espera PascalCase como sqlsrv.
    // Mapeamos los campos conocidos y dejamos pasar los demás.
    $map = [
        'id_ticket'        => 'Id_Ticket',
        'nombre'           => 'Nombre',
        'correo'           => 'Correo',
        'prioridad'        => 'Prioridad',
        'empresa'          => 'Empresa',
        'asunto'           => 'Asunto',
        'mensaje'          => 'Mensaje',
        'fecha'            => 'Fecha',
        'hora'             => 'Hora',
        'estatus'          => 'Estatus',
        'pa'               => 'PA',
        'fecha_terminado'  => 'FechaTerminado',
        'hora_terminado'   => 'HoraTerminado',
        'fecha_en_proceso' => 'FechaEnProceso',
        'hora_en_proceso'  => 'HoraEnProceso',
    ];
    $out = [];
    foreach ($map as $api_key => $php_key) {
        // Accept both snake_case and PascalCase from API
        $out[$php_key] = $t[$api_key] ?? $t[$php_key] ?? null;
    }
    return $out;
}

// ─── EXPORTAR A EXCEL (lógica preservada, datos desde API) ───────────────────
if (isset($_GET['export']) && $_GET['export'] == 'excel' && $tienePermisosDashboard) {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-m-d');
    $estatus      = $_GET['estatus']      ?? '';
    $prioridad    = $_GET['prioridad']    ?? '';
    $empresa      = $_GET['empresa']      ?? '';
    $asunto_f     = $_GET['asunto']       ?? '';

    // Construir query params para la API
    $qs = http_build_query(array_filter([
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin'    => $fecha_fin,
        'nombre'       => '',   // la API filtra por nombre; dejamos vacío para todos
    ]));
    $resp = api_get("$apiUrl/api/TicketBacros/tickets?$qs");
    $tickets_export = [];
    if ($resp && !empty($resp['data'])) {
        foreach ($resp['data'] as $t) {
            $t = normalizar_ticket($t);
            // Filtros locales que la API no soporta (estatus, prioridad, empresa, asunto)
            if (!empty($estatus)  && strcasecmp($t['Estatus']  ?? '', $estatus)  !== 0) continue;
            if (!empty($prioridad) && strcasecmp($t['Prioridad'] ?? '', $prioridad) !== 0) continue;
            if (!empty($empresa)  && stripos($t['Empresa']  ?? '', $empresa)  === false) continue;
            if (!empty($asunto_f) && stripos($t['Asunto']   ?? '', $asunto_f) === false) continue;
            $tickets_export[] = $t;
        }
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reporte_tickets_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";

    echo "<table border='1'>";
    echo "<tr><th colspan='14' style='background-color:#1e3a8a;color:white;font-size:16px;'>REPORTE DE TICKETS - BACROCORP</th></tr>";
    echo "<tr><th colspan='14'>Período: " . date('d/m/Y', strtotime($fecha_inicio)) . " - " . date('d/m/Y', strtotime($fecha_fin)) . "</th></tr>";
    echo "<tr><th colspan='14'>Generado por: " . htmlspecialchars($nombre) . " - " . date('d/m/Y H:i:s') . "</th></tr><tr></tr>";
    echo "<tr style='background-color:#3b82f6;color:white;font-weight:bold;'>";
    foreach (['ID','Solicitante','Correo','Prioridad','Empresa','Asunto','Mensaje','Estatus','Fecha','Tiempo','SLA','Atendido Por','Fecha Terminado','Hora Terminado'] as $h) {
        echo "<th>$h</th>";
    }
    echo "</tr>";

    foreach ($tickets_export as $row) {
        $fecha = 'N/A';
        if ($row['Fecha']) {
            $dateObj = date_create_from_format('d/m/Y', $row['Fecha']);
            if (!$dateObj) $dateObj = date_create($row['Fecha']);
            $fecha = $dateObj ? $dateObj->format('d/m/Y') : $row['Fecha'];
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Id_Ticket'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Nombre']    ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Correo']    ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Prioridad'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Empresa']   ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['Asunto']    ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['Mensaje'] ?? 'N/A', 0, 100)) . "</td>";
        echo "<td>" . htmlspecialchars($row['Estatus']   ?? 'N/A') . "</td>";
        echo "<td>" . $fecha . "</td>";

        if (($row['Estatus'] ?? '') == 'Atendido' && !empty($row['FechaTerminado'])) {
            $hora_inicio_calc = empty($row['HoraEnProceso']) ? $row['Hora'] : $row['HoraEnProceso'];
            $tiempoEjec = calcularHorasLaborales(
                $row['FechaEnProceso'] ?? $row['Fecha'],
                $hora_inicio_calc,
                $row['FechaTerminado'],
                $row['HoraTerminado'] ?? '17:30:00',
                $row['Hora'] ?? null
            );
            if ($tiempoEjec !== null) {
                $sla_info = verificarSLA($row['Prioridad'] ?? '', $tiempoEjec);
                echo "<td>" . number_format($tiempoEjec, 2) . " hrs</td>";
                echo $sla_info
                    ? "<td style='color:{$sla_info['color']};font-weight:bold;'>{$sla_info['estado']}</td>"
                    : "<td>N/A</td>";
            } else {
                echo "<td>Error</td><td>Error</td>";
            }
        } else {
            echo "<td>En proceso</td><td>Pendiente</td>";
        }

        echo "<td>" . htmlspecialchars($row['PA'] ?? 'Sin asignar') . "</td>";

        $fechaTerminado = 'N/A';
        if ($row['FechaTerminado']) {
            $dateObj = date_create_from_format('d/m/Y', $row['FechaTerminado']);
            if (!$dateObj) $dateObj = date_create($row['FechaTerminado']);
            $fechaTerminado = $dateObj ? $dateObj->format('d/m/Y') : $row['FechaTerminado'];
        }
        $horaTerminado = $row['HoraTerminado'] ?? 'N/A';

        echo "<td>$fechaTerminado</td>";
        echo "<td>$horaTerminado</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}

// ─── CARGA DE DATOS PARA DASHBOARD ───────────────────────────────────────────
if ($tienePermisosDashboard) {
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-m-d');
    $estatus      = $_GET['estatus']      ?? '';
    $prioridad    = $_GET['prioridad']    ?? '';
    $empresa      = $_GET['empresa']      ?? '';
    $asunto       = $_GET['asunto']       ?? '';
    $currentYear  = date('Y');

    // 1) Tickets del período seleccionado (para tabla + charts de filtro)
    $qs_period = http_build_query(array_filter([
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin'    => $fecha_fin,
    ]));
    $resp_period = api_get("$apiUrl/api/TicketBacros/tickets?$qs_period");

    $tickets_raw = [];
    if ($resp_period && !empty($resp_period['data'])) {
        foreach ($resp_period['data'] as $t) {
            $t = normalizar_ticket($t);
            // Filtros locales adicionales
            if (!empty($estatus)   && strcasecmp($t['Estatus']  ?? '', $estatus)  !== 0) continue;
            if (!empty($prioridad) && strcasecmp($t['Prioridad'] ?? '', $prioridad) !== 0) continue;
            if (!empty($empresa)   && stripos($t['Empresa']  ?? '', $empresa)   === false) continue;
            if (!empty($asunto)    && stripos($t['Asunto']    ?? '', $asunto)    === false) continue;
            $tickets_raw[] = $t;
        }
    }

    // 2) Tickets del año completo para gráfica mensual
    $qs_year = http_build_query([
        'fecha_inicio' => "$currentYear-01-01",
        'fecha_fin'    => "$currentYear-12-31",
    ]);
    $resp_year = api_get("$apiUrl/api/TicketBacros/tickets?$qs_year");
    $tickets_year = [];
    if ($resp_year && !empty($resp_year['data'])) {
        $tickets_year = $resp_year['data'];
    }

    // ─── Calcular SLA y enriquecer tickets ───────────────────────────────────
    $ticketsConTiempo        = [];
    $tiemposEjecucion        = [];
    $totalHorasLaborales     = 0;
    $ticketsConTiempoCalculado = 0;
    $slaData = [
        'ALTA'     => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 2,  'desc' => '≤2h'],
        'MEDIA'    => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 4,  'desc' => '≤4h'],
        'BAJA'     => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 10, 'desc' => '≤10h'],
        'ESTÁNDAR' => ['cumple' => 0, 'nocumple' => 0, 'total' => 0, 'sla_max' => 24, 'desc' => '≤24h'],
    ];
    $cumplimientoGlobal = ['cumple' => 0, 'nocumple' => 0, 'total' => 0];

    foreach ($tickets_raw as $ticket) {
        $tiempoCalculado = null;
        $sla_info        = null;

        if (($ticket['Estatus'] ?? '') == 'Atendido' && !empty($ticket['FechaTerminado'])) {
            $hora_inicio_calc = empty($ticket['HoraEnProceso']) ? $ticket['Hora'] : $ticket['HoraEnProceso'];
            $tiempoCalculado  = calcularHorasLaborales(
                $ticket['FechaEnProceso'] ?? $ticket['Fecha'],
                $hora_inicio_calc,
                $ticket['FechaTerminado'],
                $ticket['HoraTerminado'] ?? '17:30:00',
                $ticket['Hora'] ?? null
            );

            if ($tiempoCalculado !== null && $tiempoCalculado >= 0) {
                $sla_info = verificarSLA($ticket['Prioridad'] ?? '', $tiempoCalculado);
                $tiemposEjecucion[]    = $tiempoCalculado;
                $totalHorasLaborales  += $tiempoCalculado;
                $ticketsConTiempoCalculado++;
                $ticket['Tiempo_Ejec_Calculado'] = $tiempoCalculado;
                $ticket['SLA_Info']              = $sla_info;

                if ($sla_info) {
                    $cat = $sla_info['categoria'];
                    if ($sla_info['cumple']) {
                        $slaData[$cat]['cumple']++;
                        $cumplimientoGlobal['cumple']++;
                    } else {
                        $slaData[$cat]['nocumple']++;
                        $cumplimientoGlobal['nocumple']++;
                    }
                    $slaData[$cat]['total']++;
                }
            }
        }
        $ticketsConTiempo[] = $ticket;
    }

    $totalTickets             = count($ticketsConTiempo);
    $tiempoPromedioGeneral    = $ticketsConTiempoCalculado > 0
        ? round($totalHorasLaborales / $ticketsConTiempoCalculado, 2) : 0;
    $totalSLATickets          = $cumplimientoGlobal['cumple'] + $cumplimientoGlobal['nocumple'];
    $cumplimientoSLAPorcentaje = $totalSLATickets > 0
        ? round(($cumplimientoGlobal['cumple'] / $totalSLATickets) * 100, 1) : 0;

    // ─── Agregaciones desde los tickets filtrados ─────────────────────────────

    // Estatus
    $estatusData = [];
    foreach ($ticketsConTiempo as $t) {
        $e = (!empty($t['Estatus'])) ? $t['Estatus'] : 'Pendiente';
        $estatusData[$e] = ($estatusData[$e] ?? 0) + 1;
    }

    // Prioridad
    $prioridadData = [];
    foreach ($ticketsConTiempo as $t) {
        $p = (!empty($t['Prioridad'])) ? $t['Prioridad'] : 'No especificada';
        $prioridadData[$p] = ($prioridadData[$p] ?? 0) + 1;
    }

    // Empresa / Área (normalizada)
    $empresaDataRaw = [];
    foreach ($ticketsConTiempo as $t) {
        $emp = trim($t['Empresa'] ?? '');
        if ($emp === '' || $emp === 'N/A') {
            $emp = 'No especificada';
        } else {
            $emp = strtoupper($emp);
            if (strpos($emp, 'CONTA') !== false || strpos($emp, 'FINANZAS') !== false) {
                $emp = 'FINANZAS Y CONTABILIDAD';
            }
        }
        $empresaDataRaw[$emp] = ($empresaDataRaw[$emp] ?? 0) + 1;
    }
    arsort($empresaDataRaw);
    $empresaData   = [];
    $otrosTotal    = 0;
    $contador      = 0;
    foreach ($empresaDataRaw as $k => $v) {
        if ($contador < 15) { $empresaData[$k] = $v; $contador++; }
        else $otrosTotal += $v;
    }
    if ($otrosTotal > 0) $empresaData['Otras AREAS/EMPRESAS'] = $otrosTotal;
    if (empty($empresaData)) $empresaData['No especificada'] = 0;

    // Asunto
    $asuntoDataRaw = [];
    foreach ($ticketsConTiempo as $t) {
        $a = (!empty($t['Asunto'])) ? $t['Asunto'] : 'No especificado';
        $asuntoDataRaw[$a] = ($asuntoDataRaw[$a] ?? 0) + 1;
    }
    arsort($asuntoDataRaw);
    $asuntoData         = [];
    $otrosAsuntosTotal  = 0;
    $contador           = 0;
    foreach ($asuntoDataRaw as $k => $v) {
        if ($contador < 10) { $asuntoData[$k] = $v; $contador++; }
        else $otrosAsuntosTotal += $v;
    }
    if ($otrosAsuntosTotal > 0) $asuntoData['Otros Asuntos'] = $otrosAsuntosTotal;
    if (empty($asuntoData)) $asuntoData['No especificado'] = 0;

    // Mensual (año completo)
    $mensualData = [];
    foreach ($tickets_year as $t) {
        $t = normalizar_ticket($t);
        $f = $t['Fecha'] ?? '';
        $mes = null;
        if ($f) {
            // Intentar d/m/Y primero, luego Y-m-d
            $dateObj = date_create_from_format('d/m/Y', $f);
            if (!$dateObj) $dateObj = date_create($f);
            if ($dateObj && $dateObj->format('Y') == $currentYear) {
                $mes = (int)$dateObj->format('n');
            }
        }
        if ($mes) $mensualData[$mes] = ($mensualData[$mes] ?? 0) + 1;
    }

    // Atendido por (PA)
    $atendidoPorData = [];
    foreach ($ticketsConTiempo as $t) {
        $pa = $t['PA'] ?? '';
        if (stripos($pa, 'luis') !== false)    $cat = 'Luis';
        elseif (stripos($pa, 'alfredo') !== false) $cat = 'Alfredo';
        elseif (stripos($pa, 'ariel') !== false)   $cat = 'Ariel';
        elseif (stripos($pa, 'romero') !== false)  $cat = 'Romero';
        elseif ($pa === '')                        $cat = 'Sin asignar';
        else                                       $cat = 'Otros';
        $atendidoPorData[$cat] = ($atendidoPorData[$cat] ?? 0) + 1;
    }

    // Tiempo de ejecución
    $tiempoEjecucionData = ['Rápido (≤4h)' => 0, 'Moderado (4-24h)' => 0, 'Lento (24-72h)' => 0, 'Muy lento (>72h)' => 0];
    foreach ($tiemposEjecucion as $tiempo) {
        if ($tiempo <= 4)       $tiempoEjecucionData['Rápido (≤4h)']++;
        elseif ($tiempo <= 24)  $tiempoEjecucionData['Moderado (4-24h)']++;
        elseif ($tiempo <= 72)  $tiempoEjecucionData['Lento (24-72h)']++;
        else                    $tiempoEjecucionData['Muy lento (>72h)']++;
    }

    // Opciones de filtros (valores únicos derivados de los tickets del año)
    $estatusOptions   = [];
    $prioridadOptions = [];
    $empresaOptions   = [];
    $asuntoOptions    = [];
    foreach ($tickets_year as $t) {
        $t = normalizar_ticket($t);
        if (!empty($t['Estatus'])   && !in_array($t['Estatus'],   $estatusOptions))   $estatusOptions[]   = $t['Estatus'];
        if (!empty($t['Prioridad']) && !in_array($t['Prioridad'], $prioridadOptions)) $prioridadOptions[] = $t['Prioridad'];
        if (!empty($t['Empresa'])   && $t['Empresa'] !== 'N/A' && !in_array($t['Empresa'], $empresaOptions)) $empresaOptions[] = $t['Empresa'];
        if (!empty($t['Asunto'])    && !in_array($t['Asunto'],    $asuntoOptions))    $asuntoOptions[]    = $t['Asunto'];
    }
    sort($estatusOptions); sort($prioridadOptions); sort($empresaOptions); sort($asuntoOptions);

    // Labels mensuales
    $mensualLabels = [];
    $mensualValues = [];
    for ($i = 1; $i <= 12; $i++) {
        $mensualLabels[] = date('M Y', strtotime("$currentYear-$i-01"));
        $mensualValues[] = $mensualData[$i] ?? 0;
    }
}

$iniciales = strtoupper(substr($nombre, 0, 2));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes" />
    <title>Dashboard - SOPORTE TÉCNICO BACROCORP</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts-gl@2.0.9/dist/echarts-gl.min.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --navy-primary:#0a192f; --navy-secondary:#112240; --navy-tertiary:#1e3a5c;
            --accent-blue:#3b82f6; --accent-blue-light:#60a5fa; --accent-blue-dark:#1d4ed8;
            --success-green:#10b981; --warning-orange:#f59e0b; --error-red:#ef4444;
            --snow-white:#f8fafc; --pearl-white:#f1f5f9;
            --text-primary:#1e293b; --text-secondary:#475569; --text-light:#e2e8f0;
            --card-bg:rgba(255,255,255,0.98); --card-shadow:0 12px 32px rgba(2,12,27,0.12);
            --glass-bg:rgba(255,255,255,0.15); --glass-border:rgba(255,255,255,0.2);
            --gradient-primary:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);
            --gradient-blue:linear-gradient(135deg,#2563eb 0%,#3b82f6 100%);
            --gradient-success:linear-gradient(135deg,#059669 0%,#10b981 100%);
            --gradient-warning:linear-gradient(135deg,#d97706 0%,#f59e0b 100%);
            --gradient-error:linear-gradient(135deg,#dc2626 0%,#ef4444 100%);
            --spacing-xs:6px; --spacing-sm:12px; --spacing-md:18px; --spacing-lg:24px; --spacing-xl:32px; --spacing-xxl:48px;
            --border-radius-sm:8px; --border-radius-md:12px; --border-radius-lg:16px; --border-radius-xl:20px; --border-radius-xxl:24px;
        }
        .dark-mode {
            --navy-primary:#0f172a; --navy-secondary:#1e293b; --navy-tertiary:#334155;
            --accent-blue:#60a5fa; --accent-blue-light:#93c5fd; --accent-blue-dark:#3b82f6;
            --snow-white:#0f172a; --pearl-white:#1e293b;
            --text-primary:#f1f5f9; --text-secondary:#cbd5e1; --text-light:#e2e8f0;
            --card-bg:rgba(15,23,42,0.95); --card-shadow:0 12px 32px rgba(0,0,0,0.3);
            --glass-bg:rgba(255,255,255,0.08); --glass-border:rgba(255,255,255,0.12);
        }
        body { font-family:'Inter',sans-serif; background:var(--snow-white); color:var(--text-primary); line-height:1.5; min-height:100vh; overflow-x:hidden; transition:all .4s ease; font-size:.9rem; }
        #sidebar { position:fixed; top:0; left:0; height:100vh; width:260px; background:linear-gradient(165deg,var(--navy-primary) 0%,var(--navy-secondary) 50%,var(--navy-tertiary) 100%); border-right:1px solid rgba(255,255,255,.1); box-shadow:5px 0 30px rgba(0,0,0,.3); padding-top:var(--spacing-lg); display:flex; flex-direction:column; transition:transform .4s cubic-bezier(.4,0,.2,1); z-index:1000; overflow-y:auto; }
        #sidebar.hidden { transform:translateX(-100%); }
        .sidebar-header { padding:var(--spacing-md) var(--spacing-lg); margin-bottom:var(--spacing-lg); border-bottom:1px solid rgba(255,255,255,.1); }
        .sidebar-logo { font-family:'Montserrat',sans-serif; font-weight:800; font-size:1.6rem; background:linear-gradient(135deg,var(--accent-blue),var(--accent-blue-light)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; letter-spacing:.05em; text-align:center; }
        .sidebar-subtitle { font-size:.75rem; color:var(--text-light); text-align:center; margin-top:var(--spacing-xs); opacity:.8; }
        #sidebar a { display:flex; align-items:center; color:var(--text-light); font-weight:500; font-size:.9rem; padding:var(--spacing-sm) var(--spacing-lg); text-decoration:none; border-radius:var(--border-radius-md); margin:4px var(--spacing-md); transition:all .3s ease; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); }
        #sidebar a:hover { background:rgba(59,130,246,.15); color:white; transform:translateX(8px); border-color:rgba(59,130,246,.3); }
        #sidebar a.active { background:var(--gradient-blue); color:white; border-color:rgba(59,130,246,.5); }
        #sidebar a i { margin-right:var(--spacing-sm); font-size:1.1rem; min-width:24px; text-align:center; color:var(--accent-blue); }
        #sidebar a:hover i, #sidebar a.active i { color:white; }
        #sidebar a.disabled { opacity:.5; cursor:not-allowed; pointer-events:none; }
        .theme-toggle { margin-top:auto; margin-bottom:var(--spacing-lg); padding:0 var(--spacing-md); }
        .theme-toggle-btn { display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:var(--text-light); padding:var(--spacing-sm); border-radius:var(--border-radius-md); cursor:pointer; font-family:'Inter',sans-serif; font-weight:500; font-size:.85rem; transition:all .3s ease; width:100%; }
        .logout-btn { display:flex; align-items:center; justify-content:center; background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#fecaca; padding:var(--spacing-sm); border-radius:var(--border-radius-md); cursor:pointer; font-family:'Inter',sans-serif; font-weight:500; font-size:.85rem; transition:all .3s ease; width:100%; margin-bottom:var(--spacing-lg); }
        .logout-btn:hover { background:rgba(239,68,68,.25); color:white; }
        .background-container { position:fixed; top:0; left:0; width:100%; height:100%; z-index:-3; overflow:hidden; background:linear-gradient(135deg,var(--pearl-white) 0%,var(--snow-white) 100%); }
        .dark-mode .background-container { background:linear-gradient(135deg,var(--navy-primary) 0%,var(--navy-secondary) 100%); }
        .glass-layer { position:absolute; width:100%; height:100%; background:linear-gradient(135deg,rgba(30,58,138,.03) 0%,rgba(59,130,246,.05) 25%,rgba(96,165,250,.04) 50%,rgba(14,165,233,.03) 75%,rgba(59,130,246,.02) 100%); }
        .water-drops-container { position:absolute; width:100%; height:100%; }
        .water-drop { position:absolute; background:radial-gradient(circle,rgba(59,130,246,.12) 0%,rgba(96,165,250,.08) 30%,transparent 70%); border-radius:50%; animation:waterRipple 10s infinite ease-in-out; }
        @keyframes waterRipple { 0%{transform:scale(0);opacity:.4} 50%{opacity:.2} 100%{transform:scale(8);opacity:0} }
        .dashboard-header { background:var(--card-bg); border-bottom:1px solid var(--glass-border); padding:var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) 280px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100; box-shadow:0 2px 20px rgba(0,0,0,.1); transition:padding .4s ease; }
        .dashboard-header.full-width { padding-left:var(--spacing-lg); }
        .logo { font-family:'Montserrat',sans-serif; font-weight:800; font-size:1.6rem; background:linear-gradient(135deg,var(--accent-blue),var(--accent-blue-light)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .logo-subtitle { font-size:.8rem; color:var(--text-secondary); font-weight:500; }
        .nav-controls { display:flex; align-items:center; gap:var(--spacing-md); }
        .sidebar-toggle { background:var(--glass-bg); border:1px solid var(--glass-border); color:var(--accent-blue); width:40px; height:40px; border-radius:var(--border-radius-md); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .3s ease; font-size:1.1rem; }
        .user-info { display:flex; align-items:center; gap:var(--spacing-sm); padding:6px var(--spacing-sm); border-radius:var(--border-radius-md); background:var(--glass-bg); border:1px solid var(--glass-border); }
        .user-avatar { width:40px; height:40px; border-radius:50%; background:var(--gradient-blue); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1rem; }
        .user-details { display:flex; flex-direction:column; }
        .user-name { font-weight:600; font-size:.9rem; }
        .user-role { font-size:.75rem; color:var(--text-secondary); }
        .module-container { position:fixed; top:70px; left:280px; right:var(--spacing-lg); bottom:var(--spacing-lg); background:var(--card-bg); border-radius:var(--border-radius-xl); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); z-index:50; display:none; overflow:hidden; }
        .module-container.active { display:block; }
        .module-iframe { width:100%; height:100%; border:none; border-radius:var(--border-radius-xl); }
        .dashboard-container { padding:var(--spacing-lg) var(--spacing-lg) var(--spacing-lg) 280px; max-width:100%; margin:0 auto; transition:padding .4s ease; min-height:calc(100vh - 70px); }
        .dashboard-container.full-width { padding-left:var(--spacing-lg); }
        .dashboard-title { font-family:'Montserrat',sans-serif; font-size:clamp(1.6rem,3vw,2rem); font-weight:800; margin-bottom:var(--spacing-xs); background:var(--gradient-blue); -webkit-background-clip:text; -webkit-text-fill-color:transparent; line-height:1.2; }
        .dashboard-subtitle { color:var(--text-secondary); margin-bottom:var(--spacing-lg); font-size:clamp(.9rem,2vw,1rem); max-width:700px; line-height:1.6; }
        .user-info-card { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-md); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); margin-bottom:var(--spacing-lg); display:flex; align-items:center; gap:var(--spacing-md); position:relative; overflow:hidden; }
        .user-info-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:var(--gradient-blue); }
        .user-info-avatar { width:60px; height:60px; border-radius:50%; background:var(--gradient-blue); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1.2rem; flex-shrink:0; }
        .user-info-details { flex:1; }
        .user-info-name { font-family:'Montserrat',sans-serif; font-size:1.1rem; font-weight:700; margin-bottom:4px; }
        .user-info-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:var(--spacing-sm); margin-top:var(--spacing-xs); }
        .user-info-label { font-size:.7rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
        .user-info-value { font-weight:700; font-size:.9rem; color:var(--accent-blue); }
        .filters-container { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:var(--spacing-md); margin-bottom:var(--spacing-lg); background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-lg); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); position:relative; }
        .filters-container::before { content:''; position:absolute; top:0; left:0; width:100%; height:4px; background:var(--gradient-blue); border-radius:var(--border-radius-lg) var(--border-radius-lg) 0 0; }
        .filter-item { display:flex; flex-direction:column; gap:var(--spacing-xs); }
        .filter-label { font-size:.8rem; color:var(--accent-blue); font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
        .filter-input { background:var(--card-bg); border:2px solid var(--glass-border); border-radius:var(--border-radius-sm); padding:10px 14px; color:var(--text-primary); font-size:.85rem; font-family:'Inter',sans-serif; transition:all .3s ease; width:100%; }
        .filter-input:focus { outline:none; border-color:var(--accent-blue); box-shadow:0 0 0 3px rgba(59,130,246,.15); }
        .filter-actions { display:flex; gap:var(--spacing-sm); align-items:flex-end; flex-wrap:wrap; }
        .filter-btn { background:var(--gradient-blue); color:white; border:none; padding:10px 20px; border-radius:var(--border-radius-sm); cursor:pointer; font-weight:600; font-size:.85rem; transition:all .3s ease; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .filter-btn:hover { transform:translateY(-2px); box-shadow:0 6px 15px rgba(59,130,246,.4); }
        .filter-reset { background:rgba(107,114,128,.2); color:var(--text-secondary); border:1px solid rgba(107,114,128,.3); }
        .export-btn { background:linear-gradient(135deg,#059669,#10b981); color:white; border:none; padding:10px 20px; border-radius:var(--border-radius-sm); cursor:pointer; font-weight:600; font-size:.85rem; transition:all .3s ease; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:var(--spacing-lg); margin-bottom:var(--spacing-lg); }
        .kpi-card { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-lg); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); position:relative; overflow:hidden; transition:transform .3s ease; }
        .kpi-card:hover { transform:translateY(-4px); }
        .kpi-card::before { content:''; position:absolute; top:0; left:0; width:100%; height:4px; background:var(--gradient-blue); }
        .kpi-card.success::before { background:var(--gradient-success); }
        .kpi-card.warning::before { background:var(--gradient-warning); }
        .kpi-card.error::before { background:var(--gradient-error); }
        .kpi-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--spacing-sm); }
        .kpi-title { font-size:.85rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .kpi-icon { width:40px; height:40px; border-radius:var(--border-radius-sm); background:rgba(59,130,246,.1); display:flex; align-items:center; justify-content:center; color:var(--accent-blue); font-size:1.1rem; }
        .kpi-value { font-family:'Montserrat',sans-serif; font-size:2.5rem; font-weight:800; background:var(--gradient-blue); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin-bottom:4px; }
        .kpi-card.success .kpi-value { background:var(--gradient-success); -webkit-background-clip:text; }
        .kpi-card.warning .kpi-value { background:var(--gradient-warning); -webkit-background-clip:text; }
        .kpi-card.error .kpi-value { background:var(--gradient-error); -webkit-background-clip:text; }
        .kpi-comparison { font-size:.8rem; color:var(--text-secondary); }
        .tiempo-promedio-container { display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-lg); margin-bottom:var(--spacing-lg); }
        .tiempo-promedio-card, .sla-card { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-lg); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); }
        .tiempo-promedio-label { font-size:.85rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:var(--spacing-sm); }
        .tiempo-promedio-value { font-family:'Montserrat',sans-serif; font-size:2.5rem; font-weight:800; background:var(--gradient-blue); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin-bottom:var(--spacing-xs); }
        .tiempo-promedio-subtitle { font-size:.8rem; color:var(--text-secondary); line-height:1.5; }
        .sla-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--spacing-sm); }
        .sla-title { font-size:.85rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; }
        .sla-percentage { font-family:'Montserrat',sans-serif; font-size:2.2rem; font-weight:800; background:var(--gradient-success); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .sla-bar-container { height:12px; background:rgba(107,114,128,.2); border-radius:999px; overflow:hidden; margin-bottom:var(--spacing-sm); }
        .sla-bar { height:100%; background:linear-gradient(90deg,#059669,#10b981); border-radius:999px; transition:width .8s ease; }
        .sla-stats { display:flex; gap:var(--spacing-lg); font-size:.85rem; color:var(--text-secondary); }
        .charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-lg); margin-bottom:var(--spacing-lg); }
        .chart-card { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-lg); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); min-height:500px; margin-bottom:var(--spacing-lg); }
        .chart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--spacing-md); }
        .chart-title { font-family:'Montserrat',sans-serif; font-size:1rem; font-weight:700; color:var(--text-primary); text-transform:uppercase; letter-spacing:.5px; }
        .chart-actions { display:flex; gap:var(--spacing-xs); }
        .chart-action-btn { background:var(--glass-bg); border:1px solid var(--glass-border); color:var(--accent-blue); width:32px; height:32px; border-radius:var(--border-radius-sm); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .3s ease; }
        .chart-container { height:480px; width:100%; }
        .table-section { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-lg); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); margin-bottom:var(--spacing-lg); }
        .table-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--spacing-md); }
        .section-title { font-family:'Montserrat',sans-serif; font-size:1rem; font-weight:700; color:var(--text-primary); }
        .table-info { font-size:.85rem; color:var(--text-secondary); margin-top:4px; }
        .fecha-badge { background:rgba(16,185,129,.1); color:#059669; padding:6px 12px; border-radius:999px; font-size:.8rem; font-weight:600; border:1px solid rgba(16,185,129,.2); display:flex; align-items:center; gap:6px; }
        .tickets-table { width:100%; border-collapse:collapse; font-size:.82rem; }
        .tickets-table th { background:linear-gradient(135deg,#1e3a8a,#3b82f6); color:white; padding:12px 14px; text-align:left; font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
        .tickets-table td { padding:10px 14px; border-bottom:1px solid var(--glass-border); vertical-align:middle; white-space:nowrap; }
        .tickets-table tr:hover td { background:rgba(59,130,246,.04); }
        .status-badge { display:inline-block; padding:4px 10px; border-radius:999px; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
        .status-atendido  { background:rgba(16,185,129,.15); color:#059669; border:1px solid rgba(16,185,129,.3); }
        .status-en-proceso { background:rgba(245,158,11,.15); color:#d97706; border:1px solid rgba(245,158,11,.3); }
        .status-pendiente  { background:rgba(107,114,128,.15); color:#6b7280; border:1px solid rgba(107,114,128,.3); }
        .priority-alta  { background:rgba(239,68,68,.15);  color:#dc2626; border:1px solid rgba(239,68,68,.3); }
        .priority-media { background:rgba(245,158,11,.15); color:#d97706; border:1px solid rgba(245,158,11,.3); }
        .priority-baja  { background:rgba(59,130,246,.15); color:#2563eb; border:1px solid rgba(59,130,246,.3); }
        .tiempo-rapido   { background:rgba(16,185,129,.15);  color:#059669; border:1px solid rgba(16,185,129,.3); }
        .tiempo-moderado { background:rgba(245,158,11,.15);  color:#d97706; border:1px solid rgba(245,158,11,.3); }
        .tiempo-lento    { background:rgba(239,68,68,.15);   color:#dc2626; border:1px solid rgba(239,68,68,.3); }
        .sla-cumple    { background:rgba(16,185,129,.15); color:#059669; border:1px solid rgba(16,185,129,.3); }
        .sla-no-cumple { background:rgba(239,68,68,.15);  color:#dc2626; border:1px solid rgba(239,68,68,.3); }
        .custom-alert { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:9999; align-items:center; justify-content:center; }
        .custom-alert.active { display:flex; }
        .alert-content { background:var(--card-bg); border-radius:var(--border-radius-xl); padding:var(--spacing-xl); box-shadow:0 25px 50px rgba(0,0,0,.4); border:1px solid var(--glass-border); max-width:400px; width:90%; text-align:center; }
        .alert-icon { width:70px; height:70px; border-radius:50%; background:var(--gradient-blue); display:flex; align-items:center; justify-content:center; margin:0 auto var(--spacing-md); color:white; font-size:1.8rem; }
        .alert-title { font-family:'Montserrat',sans-serif; font-size:1.3rem; font-weight:700; margin-bottom:var(--spacing-sm); }
        .alert-message { color:var(--text-secondary); margin-bottom:var(--spacing-lg); line-height:1.6; }
        .alert-actions { display:flex; gap:var(--spacing-sm); justify-content:center; }
        .alert-btn { padding:10px 24px; border-radius:var(--border-radius-sm); font-weight:600; cursor:pointer; border:none; font-size:.9rem; transition:all .3s ease; }
        .alert-btn-cancel { background:rgba(107,114,128,.15); color:var(--text-secondary); border:1px solid rgba(107,114,128,.3); }
        .alert-btn-confirm, .alert-btn-permission { background:var(--gradient-blue); color:white; }
        .no-permission-container { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; text-align:center; padding:var(--spacing-xxl); }
        .no-permission-icon { width:140px; height:140px; border-radius:50%; background:linear-gradient(135deg,#f59e0b,#d97706); display:flex; align-items:center; justify-content:center; margin-bottom:var(--spacing-lg); color:white; font-size:3.5rem; }
        .no-permission-title { font-family:'Montserrat',sans-serif; font-size:clamp(1.8rem,4vw,2.2rem); font-weight:800; margin-bottom:var(--spacing-md); background:linear-gradient(135deg,#f59e0b,#d97706); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .no-permission-message { color:var(--text-secondary); font-size:clamp(1rem,2vw,1.1rem); margin-bottom:var(--spacing-lg); max-width:600px; line-height:1.7; }
        .no-permission-details { background:var(--card-bg); border-radius:var(--border-radius-lg); padding:var(--spacing-md); box-shadow:var(--card-shadow); border:1px solid var(--glass-border); max-width:450px; width:100%; }
        .no-permission-user { font-weight:600; color:var(--accent-blue); margin-bottom:var(--spacing-sm); }
        .no-permission-info { font-size:.9rem; color:var(--text-secondary); line-height:1.6; }
        .animate-fade-in-up { animation:fadeInUp .5s ease-out; }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        ::-webkit-scrollbar { width:8px; height:8px; }
        ::-webkit-scrollbar-thumb { background:var(--accent-blue); border-radius:var(--border-radius-sm); }
        @media (max-width:1400px) { .charts-grid{grid-template-columns:1fr} .kpi-grid{grid-template-columns:repeat(2,1fr)} }
        @media (max-width:992px) { #sidebar{width:240px;transform:translateX(-100%)} #sidebar.active{transform:translateX(0)} .dashboard-header{padding:var(--spacing-sm) var(--spacing-md)} .dashboard-container{padding:var(--spacing-md)} .kpi-grid{grid-template-columns:1fr} }
        @media (max-width:768px) { .tickets-table{min-width:1200px;font-size:.7rem} .tiempo-promedio-container{grid-template-columns:1fr} .user-details{display:none} }
        @media (max-width:576px) { #sidebar{width:100%} }
    </style>
</head>
<body class="dark-mode">
    <nav id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">BACROCORP</div>
            <div class="sidebar-subtitle">Sistema de Soporte</div>
        </div>
        <?php if ($tienePermisosDashboard): ?>
            <a href="#" class="nav-link" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a>
        <?php else: ?>
            <a href="#" class="nav-link disabled" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a>
        <?php endif; ?>
        <a href="#" class="nav-link" data-page="levantar-ticket" data-link="Formtic1.php"><i class="fas fa-ticket-alt"></i>LEVANTAR TICKET</a>
        <a href="#" class="nav-link" data-page="revisar-tickets" data-link="RevisarT.php"><i class="fas fa-eye"></i>REVISAR TICKETS</a>
        <?php if (in_array($usuarioActual, $usuariosPermitidosProcesar)): ?>
            <a href="#" class="nav-link" data-page="procesar-tickets" data-link="TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a>
        <?php else: ?>
            <a href="#" class="nav-link disabled" data-page="procesar-tickets" data-link="TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a>
        <?php endif; ?>
        <?php if ($tienePermisosDashboard): ?>
            <a href="#" class="nav-link active" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a>
        <?php else: ?>
            <a href="#" class="nav-link disabled" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a>
        <?php endif; ?>
        <div class="theme-toggle">
            <button class="theme-toggle-btn" id="themeToggle"><i class="fas fa-sun"></i> MODO CLARO</button>
        </div>
        <div style="padding:0 var(--spacing-md);">
            <button type="button" class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> CERRAR SESIÓN</button>
        </div>
    </nav>

    <div class="custom-alert" id="logoutAlert">
        <div class="alert-content">
            <div class="alert-icon"><i class="fas fa-sign-out-alt"></i></div>
            <h3 class="alert-title">Cerrar Sesión</h3>
            <p class="alert-message">¿Estás seguro de que deseas cerrar tu sesión?</p>
            <div class="alert-actions">
                <button class="alert-btn alert-btn-cancel" id="cancelLogout">Cancelar</button>
                <button class="alert-btn alert-btn-confirm" id="confirmLogout">Sí, Cerrar Sesión</button>
            </div>
        </div>
    </div>
    <div class="custom-alert" id="permissionAlert">
        <div class="alert-content">
            <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="alert-title">Acceso Restringido</h3>
            <p class="alert-message" id="permissionMessage">No tienes permisos para acceder a este módulo.</p>
            <div class="alert-actions">
                <button class="alert-btn alert-btn-permission" id="closePermissionAlert">Aceptar</button>
            </div>
        </div>
    </div>

    <div class="background-container">
        <div class="glass-layer"></div>
        <div class="water-drops-container" id="waterDrops"></div>
    </div>

    <header class="dashboard-header">
        <div class="logo-container">
            <div class="logo">BACROCORP</div>
            <div class="logo-subtitle">Sistema de Soporte</div>
        </div>
        <div class="nav-controls">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars($iniciales); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($nombre); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($area); ?></div>
                </div>
            </div>
        </div>
    </header>

    <div class="module-container" id="moduleContainer">
        <iframe src="" class="module-iframe" id="moduleIframe"></iframe>
    </div>

    <div class="dashboard-container">
        <?php if (!$tienePermisosDashboard): ?>
            <div class="no-permission-container animate-fade-in-up">
                <div class="no-permission-icon"><i class="fas fa-lock"></i></div>
                <h1 class="no-permission-title">Acceso Restringido</h1>
                <p class="no-permission-message">No tienes permisos para acceder al Dashboard de Reportes.<br>Este módulo está restringido para usuarios autorizados.</p>
                <div class="no-permission-details">
                    <div class="no-permission-user">Usuario: <?php echo htmlspecialchars($usuario); ?></div>
                    <div class="no-permission-info">Si crees que deberías tener acceso, contacta al administrador.</div>
                </div>
            </div>
        <?php else: ?>
            <div id="dashboardContent">
                <h1 class="dashboard-title animate-fade-in-up">Reportes de Tickets</h1>
                <p class="dashboard-subtitle animate-fade-in-up">Dashboard profesional con métricas 3D, cumplimiento SLA y seguimiento de fechas de terminado en tiempo real</p>

                <div class="user-info-card animate-fade-in-up">
                    <div class="user-info-avatar"><?php echo htmlspecialchars($iniciales); ?></div>
                    <div class="user-info-details">
                        <div class="user-info-name"><?php echo htmlspecialchars($nombre); ?></div>
                        <div class="user-info-stats">
                            <div class="user-info-stat">
                                <div class="user-info-label">ID Empleado</div>
                                <div class="user-info-value"><?php echo htmlspecialchars($id_empleado); ?></div>
                            </div>
                            <div class="user-info-stat">
                                <div class="user-info-label">Área</div>
                                <div class="user-info-value"><?php echo htmlspecialchars($area); ?></div>
                            </div>
                            <div class="user-info-stat">
                                <div class="user-info-label">Usuario</div>
                                <div class="user-info-value"><?php echo htmlspecialchars($usuario); ?></div>
                            </div>
                            <div class="user-info-stat">
                                <div class="user-info-label">Período</div>
                                <div class="user-info-value"><?php echo date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" class="filters-container animate-fade-in-up">
                    <div class="filter-item">
                        <label class="filter-label">FECHA INICIO</label>
                        <input type="date" class="filter-input" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">FECHA FIN</label>
                        <input type="date" class="filter-input" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">ESTATUS</label>
                        <select class="filter-input filter-select" name="estatus">
                            <option value="">Todos</option>
                            <?php foreach ($estatusOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $estatus == $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">PRIORIDAD</label>
                        <select class="filter-input filter-select" name="prioridad">
                            <option value="">Todas</option>
                            <?php foreach ($prioridadOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $prioridad == $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">EMPRESA</label>
                        <select class="filter-input filter-select" name="empresa">
                            <option value="">Todas</option>
                            <?php foreach ($empresaOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $empresa == $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">ASUNTO</label>
                        <select class="filter-input filter-select" name="asunto">
                            <option value="">Todos</option>
                            <?php foreach ($asuntoOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $asunto == $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="?" class="filter-btn filter-reset"><i class="fas fa-redo"></i> Limpiar</a>
                        <?php if ($totalTickets > 0): ?>
                            <a href="?export=excel&<?php echo http_build_query($_GET); ?>" class="export-btn"><i class="fas fa-file-excel"></i> Exportar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($totalTickets > 0): ?>
                    <div class="kpi-grid">
                        <div class="kpi-card animate-fade-in-up">
                            <div class="kpi-header">
                                <div class="kpi-title">Total de Tickets</div>
                                <div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo $totalTickets; ?></div>
                            <div class="kpi-comparison">En el período seleccionado</div>
                        </div>
                        <div class="kpi-card success animate-fade-in-up">
                            <div class="kpi-header">
                                <div class="kpi-title">Atendidos</div>
                                <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <div class="kpi-value"><?php
                                $atendidos = 0;
                                foreach ($estatusData as $k => $v) {
                                    if (stripos($k, 'atendido') !== false) { $atendidos = $v; break; }
                                }
                                echo $atendidos;
                            ?></div>
                            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round($atendidos / $totalTickets * 100, 1) : 0; ?>% del total</div>
                        </div>
                        <div class="kpi-card warning animate-fade-in-up">
                            <div class="kpi-header">
                                <div class="kpi-title">En Proceso</div>
                                <div class="kpi-icon"><i class="fas fa-cogs"></i></div>
                            </div>
                            <div class="kpi-value"><?php
                                $enProceso = 0;
                                foreach ($estatusData as $k => $v) {
                                    if (stripos($k, 'proceso') !== false) { $enProceso = $v; break; }
                                }
                                echo $enProceso;
                            ?></div>
                            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round($enProceso / $totalTickets * 100, 1) : 0; ?>% del total</div>
                        </div>
                        <div class="kpi-card <?php echo $cumplimientoSLAPorcentaje >= 80 ? 'success' : ($cumplimientoSLAPorcentaje >= 60 ? 'warning' : 'error'); ?> animate-fade-in-up">
                            <div class="kpi-header">
                                <div class="kpi-title">Cumplimiento SLA</div>
                                <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                            </div>
                            <div class="kpi-value"><?php echo $cumplimientoSLAPorcentaje; ?>%</div>
                            <div class="kpi-comparison"><?php echo $cumplimientoGlobal['cumple']; ?> de <?php echo $totalSLATickets; ?> tickets con tiempo</div>
                        </div>
                    </div>

                    <div class="tiempo-promedio-container">
                        <div class="tiempo-promedio-card animate-fade-in-up">
                            <div class="tiempo-promedio-label">Tiempo Promedio General</div>
                            <div class="tiempo-promedio-value"><?php echo $tiempoPromedioGeneral; ?> hrs</div>
                            <div class="tiempo-promedio-subtitle">
                                Basado en <?php echo $ticketsConTiempoCalculado; ?> de <?php echo $totalTickets; ?> tickets<br>
                                <small><i class="fas fa-clock"></i> Horario laboral: Lun-Vie, 8:30-17:30</small>
                            </div>
                        </div>
                        <div class="sla-card animate-fade-in-up">
                            <div class="sla-header">
                                <div class="sla-title">CUMPLIMIENTO SLA GLOBAL</div>
                                <div class="sla-percentage"><?php echo $cumplimientoSLAPorcentaje; ?>%</div>
                            </div>
                            <div class="sla-bar-container">
                                <div class="sla-bar" style="width:<?php echo $cumplimientoSLAPorcentaje; ?>%;"></div>
                            </div>
                            <div class="sla-stats">
                                <span><i class="fas fa-check-circle" style="color:#10b981;"></i> Cumple: <?php echo $cumplimientoGlobal['cumple']; ?></span>
                                <span><i class="fas fa-times-circle" style="color:#ef4444;"></i> No cumple: <?php echo $cumplimientoGlobal['nocumple']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="chart-card animate-fade-in-up">
                        <div class="chart-header">
                            <div class="chart-title">CUMPLIMIENTO SLA POR PRIORIDAD</div>
                            <div class="chart-actions"><button class="chart-action-btn" data-chart="slaPriorityChart"><i class="fas fa-expand"></i></button></div>
                        </div>
                        <div class="chart-container" id="slaPriorityChart"></div>
                    </div>

                    <div class="charts-grid">
                        <?php if (!empty($estatusData)): ?>
                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">DISTRIBUCIÓN POR ESTATUS</div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="statusChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="statusChart"></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($prioridadData)): ?>
                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">TICKETS POR PRIORIDAD</div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="priorityChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="priorityChart"></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($empresaData)): ?>
                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">TICKETS POR ÁREA</div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="empresaChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="empresaChart"></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($asuntoData)): ?>
                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">TICKETS POR ASUNTO</div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="asuntoChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="asuntoChart"></div>
                        </div>
                        <?php endif; ?>

                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">EVOLUCIÓN MENSUAL <?php echo $currentYear; ?></div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="monthlyChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="monthlyChart"></div>
                        </div>

                        <?php if (!empty($atendidoPorData)): ?>
                        <div class="chart-card animate-fade-in-up">
                            <div class="chart-header">
                                <div class="chart-title">TICKETS ATENDIDOS POR</div>
                                <div class="chart-actions"><button class="chart-action-btn" data-chart="atendidoPorChart"><i class="fas fa-expand"></i></button></div>
                            </div>
                            <div class="chart-container" id="atendidoPorChart"></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($tiempoEjecucionData)): ?>
                    <div class="chart-card animate-fade-in-up">
                        <div class="chart-header">
                            <div class="chart-title">TIEMPO DE EJECUCIÓN</div>
                            <div class="chart-actions"><button class="chart-action-btn" data-chart="tiempoEjecucionChart"><i class="fas fa-expand"></i></button></div>
                        </div>
                        <div class="chart-container" id="tiempoEjecucionChart"></div>
                    </div>
                    <?php endif; ?>

                    <div class="table-section animate-fade-in-up">
                        <div class="table-header">
                            <div>
                                <div class="section-title">Listado Completo de Tickets</div>
                                <div class="table-info">Mostrando <?php echo count($ticketsConTiempo); ?> tickets</div>
                            </div>
                            <div class="table-actions">
                                <span class="fecha-badge"><i class="fas fa-calendar-check"></i> Incluye fecha de terminado</span>
                            </div>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="tickets-table">
                                <thead>
                                    <tr>
                                        <th>ID</th><th>SOLICITANTE</th><th>CORREO</th><th>PRIORIDAD</th>
                                        <th>EMPRESA</th><th>ASUNTO</th><th>ESTATUS</th><th>FECHA</th>
                                        <th>TIEMPO</th><th>SLA</th><th>ATENDIDO POR</th>
                                        <th>FECHA TERMINADO</th><th>HORA TERMINADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ticketsConTiempo as $ticket):
                                        // Fecha del ticket
                                        $fecha = 'N/A';
                                        if ($ticket['Fecha']) {
                                            $dateObj = date_create_from_format('d/m/Y', $ticket['Fecha']);
                                            if (!$dateObj) $dateObj = date_create($ticket['Fecha']);
                                            $fecha = $dateObj ? $dateObj->format('d/m/Y') : $ticket['Fecha'];
                                        }

                                        // Fecha/Hora terminado
                                        $fechaTerminado = 'N/A'; $horaTerminado = 'N/A';
                                        if ($ticket['FechaTerminado']) {
                                            $dateObj = date_create_from_format('d/m/Y', $ticket['FechaTerminado']);
                                            if (!$dateObj) $dateObj = date_create($ticket['FechaTerminado']);
                                            $fechaTerminado = $dateObj ? $dateObj->format('d/m/Y') : $ticket['FechaTerminado'];
                                        }
                                        if ($ticket['HoraTerminado']) {
                                            $horaTerminado = $ticket['HoraTerminado'];
                                        }

                                        $estatusVal   = $ticket['Estatus']  ?? 'Pendiente';
                                        $prioridadVal = $ticket['Prioridad'] ?? 'No especificada';
                                        $tiempoEjec   = $ticket['Tiempo_Ejec_Calculado'] ?? null;
                                        $slaInfo      = $ticket['SLA_Info']              ?? null;
                                        $atendidoPor  = $ticket['PA']                    ?? 'Sin asignar';
                                        $asuntoVal    = $ticket['Asunto']               ?? 'Sin asunto';

                                        // Tiempo formateado
                                        $tiempoEjecFormateado = 'En proceso'; $tiempoClass = '';
                                        if ($tiempoEjec !== null && $tiempoEjec !== '') {
                                            $horas_t = floatval($tiempoEjec);
                                            if ($horas_t < 1)       { $tiempoEjecFormateado = round($horas_t * 60) . ' min'; }
                                            elseif ($horas_t < 24)  { $tiempoEjecFormateado = number_format($horas_t, 1) . ' hrs'; }
                                            else {
                                                $dias = floor($horas_t / 24);
                                                $tiempoEjecFormateado = $dias . 'd ' . round($horas_t % 24) . 'h';
                                            }
                                            $tiempoClass = $horas_t <= 4 ? 'tiempo-rapido' : ($horas_t <= 24 ? 'tiempo-moderado' : 'tiempo-lento');
                                        }

                                        // Clases de estatus y prioridad
                                        $estatusClass = 'pendiente';
                                        if (stripos($estatusVal, 'atendido') !== false) $estatusClass = 'atendido';
                                        elseif (stripos($estatusVal, 'proceso') !== false) $estatusClass = 'en-proceso';

                                        $prioridadLower = strtolower($prioridadVal);
                                        if (strpos($prioridadLower, 'alt') !== false || strpos($prioridadLower, 'urg') !== false) $prioridadClass = 'alta';
                                        elseif (strpos($prioridadLower, 'med') !== false) $prioridadClass = 'media';
                                        elseif (strpos($prioridadLower, 'baj') !== false) $prioridadClass = 'baja';
                                        else $prioridadClass = 'media';
                                    ?>
                                    <tr>
                                        <td style="font-weight:700;color:var(--accent-blue);">#<?php echo htmlspecialchars($ticket['Id_Ticket'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($ticket['Nombre'] ?? 'N/A', 0, 25)); ?></td>
                                        <td><?php echo htmlspecialchars(substr($ticket['Correo'] ?? 'N/A', 0, 25)); ?></td>
                                        <td><span class="status-badge priority-<?php echo $prioridadClass; ?>"><?php echo htmlspecialchars($prioridadVal); ?></span></td>
                                        <td><?php echo htmlspecialchars(substr($ticket['Empresa'] ?? 'N/A', 0, 20)); ?></td>
                                        <td><?php echo htmlspecialchars(substr($asuntoVal, 0, 25)); ?></td>
                                        <td><span class="status-badge status-<?php echo $estatusClass; ?>"><?php echo htmlspecialchars($estatusVal); ?></span></td>
                                        <td><?php echo $fecha; ?></td>
                                        <td>
                                            <?php if ($tiempoEjec !== null): ?>
                                                <span class="status-badge <?php echo $tiempoClass; ?>"><?php echo $tiempoEjecFormateado; ?></span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background:#6b7280;">En proceso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($slaInfo): ?>
                                                <span class="status-badge <?php echo $slaInfo['cumple'] ? 'sla-cumple' : 'sla-no-cumple'; ?>"><?php echo $slaInfo['estado']; ?></span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background:#6b7280;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($atendidoPor); ?></td>
                                        <td><?php echo $fechaTerminado; ?></td>
                                        <td><?php echo htmlspecialchars($horaTerminado); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="chart-card animate-fade-in-up" style="text-align:center;padding:var(--spacing-xl);">
                        <i class="fas fa-search" style="font-size:4rem;color:var(--text-secondary);margin-bottom:var(--spacing-md);"></i>
                        <h3 style="color:var(--text-secondary);margin-bottom:var(--spacing-sm);">NO SE ENCONTRARON TICKETS</h3>
                        <p style="color:var(--text-secondary);margin-bottom:var(--spacing-md);max-width:500px;margin-left:auto;margin-right:auto;">
                            No hay tickets que coincidan con los criterios de búsqueda seleccionados.
                        </p>
                        <a href="?" class="filter-btn" style="display:inline-block;">Ver todos los tickets</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ── Verificación de sesión periódica ─────────────────────────────────
        let lastVerificationTime = Date.now();
        let sessionCheckInterval;
        function verificarSesionEnTiempoReal() {
            if (Date.now() - lastVerificationTime > 30000) {
                lastVerificationTime = Date.now();
                fetch(window.location.href, { method:'HEAD', headers:{'Cache-Control':'no-cache'}, credentials:'same-origin' })
                    .then(r => { if (r.status === 302 || r.redirected) { clearInterval(sessionCheckInterval); window.location.href = 'Loginti.php?error=session_expired'; } })
                    .catch(() => {});
            }
        }

        <?php if ($tienePermisosDashboard): ?>
        // ── Datos para gráficas ───────────────────────────────────────────────
        <?php if (!empty($estatusData)): ?>const statusData = { categories: <?php echo json_encode(array_keys($estatusData)); ?>, values: <?php echo json_encode(array_values($estatusData)); ?> };<?php endif; ?>
        <?php if (!empty($prioridadData)): ?>const priorityData = { categories: <?php echo json_encode(array_keys($prioridadData)); ?>, values: <?php echo json_encode(array_values($prioridadData)); ?> };<?php endif; ?>
        <?php if (!empty($empresaData)): ?>const empresaData = { categories: <?php echo json_encode(array_keys($empresaData)); ?>, values: <?php echo json_encode(array_values($empresaData)); ?> };<?php endif; ?>
        <?php if (!empty($asuntoData)): ?>const asuntoData = { categories: <?php echo json_encode(array_keys($asuntoData)); ?>, values: <?php echo json_encode(array_values($asuntoData)); ?> };<?php endif; ?>
        <?php if (!empty($atendidoPorData)): ?>const atendidoPorData = { categories: <?php echo json_encode(array_keys($atendidoPorData)); ?>, values: <?php echo json_encode(array_values($atendidoPorData)); ?> };<?php endif; ?>
        <?php if (!empty($tiempoEjecucionData)): ?>const tiempoEjecucionData = { categories: <?php echo json_encode(array_keys($tiempoEjecucionData)); ?>, values: <?php echo json_encode(array_values($tiempoEjecucionData)); ?> };<?php endif; ?>

        const monthlyData = { categories: <?php echo json_encode($mensualLabels); ?>, values: <?php echo json_encode($mensualValues); ?> };
        const slaPriorityData = {
            categories: ['ALTA (≤2h)', 'MEDIA (≤4h)', 'BAJA (≤10h)', 'ESTÁNDAR (≤24h)'],
            cumple:   [<?php echo $slaData['ALTA']['cumple'] ?? 0; ?>, <?php echo $slaData['MEDIA']['cumple'] ?? 0; ?>, <?php echo $slaData['BAJA']['cumple'] ?? 0; ?>, <?php echo $slaData['ESTÁNDAR']['cumple'] ?? 0; ?>],
            nocumple: [<?php echo $slaData['ALTA']['nocumple'] ?? 0; ?>, <?php echo $slaData['MEDIA']['nocumple'] ?? 0; ?>, <?php echo $slaData['BAJA']['nocumple'] ?? 0; ?>, <?php echo $slaData['ESTÁNDAR']['nocumple'] ?? 0; ?>]
        };

        function initialize3DCharts() {
            const tc = document.body.classList.contains('dark-mode')
                ? { text:'#f1f5f9', grid:'rgba(255,255,255,0.15)' }
                : { text:'#1e293b', grid:'rgba(0,0,0,0.1)' };

            const barBase = {
                tooltip:{ trigger:'axis', axisPointer:{type:'shadow'}, backgroundColor:'rgba(0,0,0,0.85)', borderColor:'#3b82f6', borderWidth:2, textStyle:{color:'#fff',fontSize:12} },
                grid:{ left:'12%', right:'8%', bottom:'12%', top:'8%', containLabel:true },
                xAxis:{ type:'category', axisLabel:{color:tc.text,fontSize:12,fontWeight:'bold'}, axisLine:{show:false}, axisTick:{show:false} },
                yAxis:{ type:'value', name:'Tickets', nameTextStyle:{color:tc.text,fontSize:12,fontWeight:'bold'}, axisLabel:{color:tc.text,fontSize:11}, splitLine:{lineStyle:{color:tc.grid,type:'dashed',width:1.5}} }
            };

            // SLA por prioridad
            const slaPEl = document.getElementById('slaPriorityChart');
            if (slaPEl) {
                const ch = echarts.init(slaPEl);
                ch.setOption({ ...barBase,
                    tooltip:{ ...barBase.tooltip, formatter:function(params){ let r=params[0].name+'<br/>';let tot=0;params.forEach(p=>tot+=p.value);params.forEach(p=>{r+=p.marker+' '+p.seriesName+': '+p.value+' ('+(tot?((p.value/tot)*100).toFixed(1):0)+'%)<br/>';});return r+'<b>Total: '+tot+'</b>'; } },
                    legend:{ data:['CUMPLE SLA','NO CUMPLE SLA'], orient:'horizontal', left:'center', top:0, textStyle:{color:tc.text,fontWeight:'bold'} },
                    grid:{ ...barBase.grid, top:'15%' },
                    xAxis:{ ...barBase.xAxis, data:slaPriorityData.categories, axisLabel:{...barBase.xAxis.axisLabel,rotate:15,interval:0} },
                    series:[
                        { name:'CUMPLE SLA', type:'bar', stack:'total', barWidth:'65%', data:slaPriorityData.cumple, itemStyle:{ borderRadius:[8,8,0,0], color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#10b981'},{offset:1,color:'#047857'}]}, shadowBlur:15,shadowColor:'rgba(16,185,129,0.4)' }, label:{show:true,position:'inside',formatter:'{c}',color:'white',fontWeight:'bold',fontSize:11} },
                        { name:'NO CUMPLE SLA', type:'bar', stack:'total', barWidth:'65%', data:slaPriorityData.nocumple, itemStyle:{ borderRadius:[8,8,0,0], color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#ef4444'},{offset:1,color:'#b91c1c'}]}, shadowBlur:15,shadowColor:'rgba(239,68,68,0.4)' }, label:{show:true,position:'inside',formatter:'{c}',color:'white',fontWeight:'bold',fontSize:11} }
                    ]
                });
            }

            <?php if (!empty($estatusData)): ?>
            const statusEl = document.getElementById('statusChart');
            if (statusEl) {
                const ch = echarts.init(statusEl);
                ch.setOption({ ...barBase, xAxis:{...barBase.xAxis,data:statusData.categories}, series:[{ name:'Tickets', type:'bar', barWidth:'55%', data:statusData.values, itemStyle:{ borderRadius:[8,8,0,0], color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#3b82f6'},{offset:1,color:'#1d4ed8'}]}, shadowBlur:12,shadowColor:'rgba(59,130,246,0.4)' }, label:{show:true,position:'top',formatter:'{c}',color:tc.text,fontWeight:'bold',fontSize:13} }] });
            }
            <?php endif; ?>

            <?php if (!empty($prioridadData)): ?>
            const priorityEl = document.getElementById('priorityChart');
            if (priorityEl) {
                const ch = echarts.init(priorityEl);
                ch.setOption({ ...barBase, xAxis:{...barBase.xAxis,data:priorityData.categories}, series:[{ name:'Tickets', type:'bar', barWidth:'55%', data:priorityData.values, itemStyle:{ borderRadius:[10,10,0,0], color:function(p){ const v=p.data; if(v>10)return{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#ef4444'},{offset:1,color:'#dc2626'}]}; if(v>5)return{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#f59e0b'},{offset:1,color:'#d97706'}]}; return{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'#3b82f6'},{offset:1,color:'#1d4ed8'}]}; }, shadowBlur:15 }, label:{show:true,position:'top',formatter:'{c}',color:tc.text,fontWeight:'bold',fontSize:13} }] });
            }
            <?php endif; ?>

            <?php if (!empty($empresaData)): ?>
            const empresaEl = document.getElementById('empresaChart');
            if (empresaEl) {
                const ch = echarts.init(empresaEl);
                ch.setOption({ tooltip:{trigger:'item',formatter:'{b}: {c} tickets ({d}%)',backgroundColor:'rgba(0,0,0,0.85)',borderColor:'#3b82f6',borderWidth:2,textStyle:{color:'#fff',fontSize:12}}, series:[{ name:'Áreas', type:'pie', radius:['35%','70%'], avoidLabelOverlap:true, itemStyle:{borderRadius:10,borderColor:'#ffffff',borderWidth:2,shadowBlur:20,shadowColor:'rgba(59,130,246,0.5)'}, label:{show:true,position:'outside',formatter:function(p){const n=p.name.length>15?p.name.slice(0,12)+'...':p.name;return n+'\n'+p.value+' ('+p.percent.toFixed(1)+'%)';},color:tc.text,fontSize:11,fontWeight:'bold',lineHeight:18}, labelLine:{length:20,length2:15,smooth:true,lineStyle:{width:2}}, data:empresaData.categories.map((c,i)=>({value:empresaData.values[i],name:c,itemStyle:{color:'hsl('+(i*25%360)+',75%,60%)'}})) }] });
            }
            <?php endif; ?>

            <?php if (!empty($asuntoData)): ?>
            const asuntoEl = document.getElementById('asuntoChart');
            if (asuntoEl) {
                const ch = echarts.init(asuntoEl);
                ch.setOption({ tooltip:{trigger:'item',formatter:'{b}: {c} tickets ({d}%)',backgroundColor:'rgba(0,0,0,0.85)',borderColor:'#3b82f6',borderWidth:2,textStyle:{color:'#fff',fontSize:12}}, series:[{ name:'Asuntos', type:'pie', radius:['30%','70%'], avoidLabelOverlap:true, itemStyle:{borderRadius:12,borderColor:'rgba(255,255,255,0.8)',borderWidth:2.5,shadowBlur:25,shadowColor:'rgba(59,130,246,0.6)'}, label:{show:true,position:'outside',formatter:function(p){const n=p.name.length>12?p.name.slice(0,10)+'...':p.name;return n+'\n'+p.value+' ('+p.percent.toFixed(1)+'%)';},color:tc.text,fontSize:11,fontWeight:'bold',lineHeight:18}, labelLine:{length:22,length2:18,smooth:true,lineStyle:{width:2}}, data:asuntoData.categories.map((c,i)=>({value:asuntoData.values[i],name:c,itemStyle:{color:'hsl('+(i*36%360)+',80%,60%)'}})) }] });
            }
            <?php endif; ?>

            const monthlyEl = document.getElementById('monthlyChart');
            if (monthlyEl) {
                const ch = echarts.init(monthlyEl);
                ch.setOption({ tooltip:{trigger:'axis',axisPointer:{type:'shadow'},backgroundColor:'rgba(0,0,0,0.85)',borderColor:'#3b82f6',borderWidth:2,textStyle:{color:'#fff',fontSize:12}}, grid:{left:'10%',right:'10%',bottom:'15%',top:'15%',containLabel:true}, xAxis:{type:'category',data:monthlyData.categories,axisLabel:{color:tc.text,fontSize:11,fontWeight:'bold',rotate:30},axisLine:{show:false},axisTick:{show:false}}, yAxis:{type:'value',name:'Tickets',nameTextStyle:{color:tc.text,fontSize:12,fontWeight:'bold'},axisLabel:{color:tc.text,fontSize:11},splitLine:{lineStyle:{color:tc.grid,type:'dashed',width:1.5}}}, series:[{name:'Tickets',type:'line',smooth:true,symbol:'circle',symbolSize:10,data:monthlyData.values, lineStyle:{width:4,color:'#3b82f6',shadowBlur:15,shadowColor:'rgba(59,130,246,0.6)'}, areaStyle:{color:{type:'linear',x:0,y:0,x2:0,y2:1,colorStops:[{offset:0,color:'rgba(59,130,246,0.5)'},{offset:1,color:'rgba(59,130,246,0)'}]}}, itemStyle:{color:'#3b82f6',borderColor:'#ffffff',borderWidth:2,shadowBlur:12}, label:{show:true,position:'top',formatter:'{c}',color:tc.text,fontWeight:'bold',fontSize:12}}] });
            }

            <?php if (!empty($atendidoPorData)): ?>
            const atendidoPorEl = document.getElementById('atendidoPorChart');
            if (atendidoPorEl) {
                const ch = echarts.init(atendidoPorEl);
                const pal = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#6b7280'];
                ch.setOption({ tooltip:{trigger:'item',formatter:'{b}: {c} tickets ({d}%)',backgroundColor:'rgba(0,0,0,0.85)',borderColor:'#3b82f6',borderWidth:2,textStyle:{color:'#fff',fontSize:12}}, series:[{ name:'Responsables', type:'pie', radius:['35%','70%'], avoidLabelOverlap:true, itemStyle:{borderRadius:10,borderColor:'#ffffff',borderWidth:2,shadowBlur:20,shadowColor:'rgba(139,92,246,0.5)'}, label:{show:true,position:'outside',formatter:'{b}\n{c} ({d}%)',color:tc.text,fontSize:11,fontWeight:'bold',lineHeight:18}, labelLine:{length:20,length2:15,smooth:true,lineStyle:{width:2}}, data:atendidoPorData.categories.map((c,i)=>({value:atendidoPorData.values[i],name:c,itemStyle:{color:pal[i%pal.length]}})) }] });
            }
            <?php endif; ?>

            <?php if (!empty($tiempoEjecucionData)): ?>
            const tiempoEl = document.getElementById('tiempoEjecucionChart');
            if (tiempoEl) {
                const ch = echarts.init(tiempoEl);
                const pal2 = ['#10b981','#f59e0b','#ef4444','#b91c1c','#6b7280'];
                ch.setOption({ tooltip:{trigger:'item',formatter:'{b}: {c} tickets ({d}%)',backgroundColor:'rgba(0,0,0,0.85)',borderColor:'#3b82f6',borderWidth:2,textStyle:{color:'#fff',fontSize:12}}, series:[{ name:'Tiempo Respuesta', type:'pie', radius:['35%','70%'], avoidLabelOverlap:true, itemStyle:{borderRadius:12,borderColor:'#ffffff',borderWidth:2,shadowBlur:25}, label:{show:true,position:'outside',formatter:'{b}\n{c} ({d}%)',color:tc.text,fontSize:11,fontWeight:'bold',lineHeight:18}, labelLine:{length:22,length2:18,smooth:true,lineStyle:{width:2}}, data:tiempoEjecucionData.categories.map((c,i)=>({value:tiempoEjecucionData.values[i],name:c,itemStyle:{color:pal2[i%pal2.length]}})) }] });
            }
            <?php endif; ?>
        }

        function resizeCharts() {
            ['statusChart','priorityChart','empresaChart','asuntoChart','monthlyChart','atendidoPorChart','tiempoEjecucionChart','slaPriorityChart'].forEach(id => {
                const c = echarts.getInstanceByDom(document.getElementById(id));
                if (c) c.resize();
            });
        }
        <?php endif; ?>

        function createWaterDrops() {
            const container = document.getElementById('waterDrops');
            for (let i = 0; i < 10; i++) {
                const d = document.createElement('div');
                d.className = 'water-drop';
                d.style.left = Math.random()*100+'%'; d.style.top = Math.random()*100+'%';
                const sz = (Math.random()*150+80)+'px'; d.style.width = sz; d.style.height = sz;
                d.style.animationDelay = Math.random()*10+'s'; d.style.animationDuration = (Math.random()*8+10)+'s';
                container.appendChild(d);
            }
        }

        // ── DOM ───────────────────────────────────────────────────────────────
        const sidebar           = document.getElementById('sidebar');
        const sidebarToggle     = document.getElementById('sidebarToggle');
        const dashboardHeader   = document.querySelector('.dashboard-header');
        const dashboardContainer = document.querySelector('.dashboard-container');
        const moduleContainer   = document.getElementById('moduleContainer');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
            dashboardHeader.classList.toggle('full-width');
            dashboardContainer.classList.toggle('full-width');
            moduleContainer.style.left = sidebar.classList.contains('hidden') ? 'var(--spacing-lg)' : '280px';
            this.querySelector('i').className = sidebar.classList.contains('hidden') ? 'fas fa-bars' : 'fas fa-times';
            <?php if ($tienePermisosDashboard): ?> setTimeout(resizeCharts, 300); <?php endif; ?>
        });

        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            this.innerHTML = isDark ? '<i class="fas fa-sun"></i> MODO CLARO' : '<i class="fas fa-moon"></i> MODO OSCURO';
            <?php if ($tienePermisosDashboard): ?> initialize3DCharts(); <?php endif; ?>
        });

        const logoutAlert       = document.getElementById('logoutAlert');
        document.getElementById('logoutBtn').addEventListener('click', () => logoutAlert.classList.add('active'));
        document.getElementById('cancelLogout').addEventListener('click', () => logoutAlert.classList.remove('active'));
        document.getElementById('confirmLogout').addEventListener('click', () => {
            const f = document.createElement('form'); f.method='POST';
            f.innerHTML = '<input type="hidden" name="logout" value="true">';
            document.body.appendChild(f); f.submit();
        });
        logoutAlert.addEventListener('click', e => { if (e.target===logoutAlert) logoutAlert.classList.remove('active'); });

        const permissionAlert   = document.getElementById('permissionAlert');
        const permissionMessage = document.getElementById('permissionMessage');
        document.getElementById('closePermissionAlert').addEventListener('click', () => permissionAlert.classList.remove('active'));
        permissionAlert.addEventListener('click', e => { if (e.target===permissionAlert) permissionAlert.classList.remove('active'); });

        <?php if ($tienePermisosDashboard): ?>
        document.querySelectorAll('.chart-action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const el = document.getElementById(this.getAttribute('data-chart'));
                if (el) { el.style.height = el.style.height === '520px' ? '480px' : '520px'; resizeCharts(); }
            });
        });
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
            sessionCheckInterval = setInterval(verificarSesionEnTiempoReal, 30000);
            createWaterDrops();
            <?php if ($tienePermisosDashboard): ?>
            setTimeout(initialize3DCharts, 150);
            window.addEventListener('resize', resizeCharts);
            <?php endif; ?>

            const pageConfig = {
                'dashboard':         { showModule:false, title:'Dashboard de Reportes', mainTitle:'Bienvenido, <?php echo addslashes(htmlspecialchars($nombre)); ?>', subtitle:'Dashboard personalizado del sistema de soporte técnico con métricas 3D', permissionRequired:true, allowedUsers:<?php echo json_encode($usuariosPermitidosDashboard); ?> },
                'levantar-ticket':   { showModule:true,  url:'Formtic1.php', title:'Levantar Ticket', mainTitle:'Formulario de Tickets', subtitle:'Complete el formulario para crear un nuevo ticket de soporte' },
                'revisar-tickets':   { showModule:true,  url:'RevisarT.php', title:'Revisar Tickets', mainTitle:'Revisión de Tickets', subtitle:'Revise y gestione los tickets asignados' },
                'procesar-tickets':  { showModule:true,  url:'TableT1.php', title:'Procesar Tickets', mainTitle:'Procesamiento de Tickets', subtitle:'Procese y actualice el estado de los tickets', permissionRequired:true, allowedUsers:<?php echo json_encode($usuariosPermitidosProcesar); ?> },
                'reportes':          { showModule:false, title:'Dashboard de Reportes', mainTitle:'Reportes de Tickets', subtitle:'Dashboard con métricas 3D y cumplimiento SLA', permissionRequired:true, allowedUsers:<?php echo json_encode($usuariosPermitidosDashboard); ?> }
            };

            const dashboardContent = document.getElementById('dashboardContent');
            const moduleIframe     = document.getElementById('moduleIframe');
            const currentUser      = '<?php echo addslashes($usuarioActual); ?>';

            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.classList.contains('disabled')) {
                        const cfg = pageConfig[this.getAttribute('data-page')];
                        permissionMessage.textContent = cfg && cfg.permissionRequired
                            ? 'El módulo "' + cfg.title + '" está restringido para usuarios autorizados.'
                            : 'No tienes permisos para acceder a este módulo.';
                        permissionAlert.classList.add('active'); return;
                    }
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    const page = this.getAttribute('data-page');
                    const cfg  = pageConfig[page];
                    if (!cfg) return;
                    if (cfg.permissionRequired && !cfg.allowedUsers.includes(currentUser)) {
                        permissionMessage.textContent = 'No tienes permisos para acceder al módulo "' + cfg.title + '".';
                        permissionAlert.classList.add('active'); return;
                    }
                    if (cfg.showModule && cfg.url) {
                        moduleContainer.classList.add('active');
                        if (dashboardContent) dashboardContent.style.display = 'none';
                        moduleIframe.src = cfg.url;
                    } else {
                        moduleContainer.classList.remove('active');
                        if (dashboardContent) dashboardContent.style.display = 'block';
                        moduleIframe.src = '';
                        <?php if ($tienePermisosDashboard): ?> setTimeout(initialize3DCharts, 100); <?php endif; ?>
                    }
                    document.querySelector('.logo-subtitle').textContent = cfg.title;
                    <?php if ($tienePermisosDashboard): ?>
                    document.querySelector('.dashboard-title').textContent = cfg.mainTitle;
                    document.querySelector('.dashboard-subtitle').textContent = cfg.subtitle;
                    <?php endif; ?>
                });
            });

            document.querySelectorAll('.filter-select').forEach(s => {
                s.addEventListener('change', function() { this.form.submit(); });
            });
        });

        window.addEventListener('pageshow', e => { if (e.persisted) window.location.reload(); });
        window.addEventListener('beforeunload', () => { if (sessionCheckInterval) clearInterval(sessionCheckInterval); });
    </script>
</body>
</html>
