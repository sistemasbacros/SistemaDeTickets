<?php
// VERIFICACIÓN DE SESIÓN MEJORADA - VERSIÓN CORREGIDA
session_start();

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
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
$usuariosPermitidosDashboard = ['luis.vargas', 'alfredo.rosales', 'luis.romero'];
$usuariosPermitidosProcesar = ['luis.vargas', 'alfredo.rosales', 'luis.romero', 'ariel.antonio'];

// Obtener usuario actual desde la sesión
$usuarioActual = $_SESSION['user_username'] ?? '';

// VERIFICAR SI EL USUARIO TIENE PERMISOS PARA EL DASHBOARD
$tienePermisosDashboard = in_array($usuarioActual, $usuariosPermitidosDashboard);

// VERIFICACIÓN DE SESIÓN MÁS FLEXIBLE PERO SEGURA
$session_valid = false;

// Verificar condiciones básicas de sesión
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
    isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    
    // Verificar tiempo de sesión (8 horas máximo)
    $session_timeout = 8 * 60 * 60; // 8 horas en segundos
    if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] <= $session_timeout)) {
        
        // Verificar inactividad (30 minutos máximo)
        $inactivity_timeout = 30 * 60; // 30 minutos en segundos
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] <= $inactivity_timeout)) {
            
            $session_valid = true;
            
            // Actualizar tiempo de última actividad
            $_SESSION['LAST_ACTIVITY'] = time();
            
            // Inicializar variables de seguridad si no existen (para migración suave)
            if (!isset($_SESSION['authenticated_from_login'])) {
                $_SESSION['authenticated_from_login'] = true;
            }
            if (!isset($_SESSION['login_source'])) {
                $_SESSION['login_source'] = 'form_login';
            }
            if (!isset($_SESSION['ip_address'])) {
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            }
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
            if (!isset($_SESSION['session_token'])) {
                $_SESSION['session_token'] = bin2hex(random_bytes(32));
            }
            if (!isset($_SESSION['browser_token'])) {
                $_SESSION['browser_token'] = bin2hex(random_bytes(16));
            }
            if (!isset($_SESSION['browser_fingerprint'])) {
                $_SESSION['browser_fingerprint'] = md5(
                    $_SERVER['HTTP_USER_AGENT'] . 
                    $_SERVER['REMOTE_ADDR'] . 
                    $_SESSION['browser_token']
                );
            }
            if (!isset($_SESSION['session_exclusive'])) {
                $_SESSION['session_exclusive'] = true;
            }
            if (!isset($_SESSION['exclusive_login_time'])) {
                $_SESSION['exclusive_login_time'] = time();
            }
            if (!isset($_SESSION['last_access_time'])) {
                $_SESSION['last_access_time'] = time();
            }
            if (!isset($_SESSION['last_direct_access'])) {
                $_SESSION['last_direct_access'] = time();
            }
            
            // Regenerar ID de sesión periódicamente para mayor seguridad (cada 5 minutos)
            if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 300)) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        } else {
            // Sesión inactiva por demasiado tiempo
            $session_valid = false;
            error_log("Sesión inactiva: " . $usuarioActual);
        }
    } else {
        // Sesión expirada por tiempo total
        $session_valid = false;
        error_log("Sesión expirada: " . $usuarioActual);
    }
} else {
    // Faltan datos básicos de sesión
    $session_valid = false;
    error_log("Datos de sesión faltantes");
}

// Si la sesión no es válida, DESTRUIR COMPLETAMENTE y redirigir al login
if (!$session_valid) {
    // Limpiar sesión completamente
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Redirigir al login con parámetro de error
    header("Location: Loginti.php?error=session_expired");
    exit();
}

// Obtener datos del usuario desde la sesión
$nombre = $_SESSION['user_name'] ?? 'Usuario';
$id_empleado = $_SESSION['user_id'] ?? 'N/A';
$area = $_SESSION['user_area'] ?? 'N/A';
$usuario = $_SESSION['user_username'] ?? 'N/A';

// Verificar si se solicitó cerrar sesión
if (isset($_POST['logout'])) {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header("Location: Loginti.php");
    exit();
}

// Conexión a la base de datos
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Ticket",
    "UID" => "Larome03", 
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "LoginTimeout" => 30
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Función para exportar a Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel' && $tienePermisosDashboard) {
    // Procesar filtros para exportación
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $estatus = $_GET['estatus'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    $empresa = $_GET['empresa'] ?? '';
    $asunto = $_GET['asunto'] ?? ''; // Cambiado de 'tipo' a 'asunto'

    // Construir condiciones WHERE dinámicamente
    $whereConditions = ["1=1"];
    $params = array();

    // Filtro por fechas
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $fecha_inicio_dmy = date('d/m/Y', strtotime($fecha_inicio));
        $fecha_fin_dmy = date('d/m/Y', strtotime($fecha_fin));
        
        $whereConditions[] = "CONVERT(DATE, Fecha, 103) BETWEEN CONVERT(DATE, ?, 103) AND CONVERT(DATE, ?, 103)";
        $params[] = $fecha_inicio_dmy;
        $params[] = $fecha_fin_dmy;
    }

    // Filtro por estatus
    if (!empty($estatus)) {
        $whereConditions[] = "Estatus = ?";
        $params[] = $estatus;
    }

    // Filtro por prioridad
    if (!empty($prioridad)) {
        $whereConditions[] = "Prioridad = ?";
        $params[] = $prioridad;
    }

    // Filtro por empresa
    if (!empty($empresa)) {
        $whereConditions[] = "Empresa = ?";
        $params[] = $empresa;
    }

    // Filtro por asunto (antes 'tipo')
    if (!empty($asunto)) {
        $whereConditions[] = "Asunto LIKE ?";
        $params[] = '%' . $asunto . '%';
    }

    $whereClause = implode(" AND ", $whereConditions);

    // Consulta para exportación
    $sqlExport = "SELECT 
        Id_Ticket,
        Nombre,
        Correo,
        Prioridad,
        Empresa,
        Asunto,
        Mensaje,
        Fecha,
        Hora,
        Estatus,
        PA,
        Tipo,
        Clasificacion,
        Tiempo_Ejec,
        FechaEnProceso,
        HoraEnProceso,
        FechaTerminado,
        HoraTerminado,
        CASE 
            WHEN FechaTerminado IS NOT NULL
            THEN 
                DATEDIFF(HOUR, 
                    COALESCE(FechaEnProceso, CONVERT(DATE, Fecha, 103)), 
                    FechaTerminado
                )
            WHEN Tiempo_Ejec IS NOT NULL AND Tiempo_Ejec != '' AND ISNUMERIC(Tiempo_Ejec) = 1 
            THEN CAST(Tiempo_Ejec as FLOAT)
            ELSE NULL
        END as Tiempo_Ejec_Real
        
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    ORDER BY 
        Fecha DESC, 
        Hora DESC";

    $stmtExport = sqlsrv_query($conn, $sqlExport, $params);
    
    // Configurar headers para descarga Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_tickets_' . date('Y-m-d') . '.xls"');
    
    // Iniciar salida Excel
    echo "<table border='1'>";
    echo "<tr><th colspan='10' style='background-color: #1e3a8a; color: white; font-size: 16px;'>REPORTE DE TICKETS - BACROCORP</th></tr>";
    echo "<tr><th colspan='10'>Período: " . date('d/m/Y', strtotime($fecha_inicio)) . " - " . date('d/m/Y', strtotime($fecha_fin)) . "</th></tr>";
    echo "<tr><th colspan='10'>Generado por: " . $nombre . " - " . date('d/m/Y H:i:s') . "</th></tr>";
    echo "<tr></tr>";
    
    // Encabezados de columnas
    echo "<tr style='background-color: #3b82f6; color: white; font-weight: bold;'>";
    echo "<th>ID Ticket</th>";
    echo "<th>Solicitante</th>";
    echo "<th>Correo</th>";
    echo "<th>Prioridad</th>";
    echo "<th>Empresa</th>";
    echo "<th>Asunto</th>";
    echo "<th>Estatus</th>";
    echo "<th>Fecha</th>";
    echo "<th>Tiempo Ejecución (hrs)</th>";
    echo "<th>Atendido Por</th>";
    echo "</tr>";
    
    // Datos
    if ($stmtExport) {
        while ($row = sqlsrv_fetch_array($stmtExport, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . ($row['Id_Ticket'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Nombre'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Correo'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Prioridad'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Empresa'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Asunto'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['Estatus'] ?? 'N/A') . "</td>";
            
            // Formatear fecha
            $fecha = 'N/A';
            if ($row['Fecha']) {
                if ($row['Fecha'] instanceof DateTime) {
                    $fecha = $row['Fecha']->format('d/m/Y');
                } else {
                    $dateObj = date_create_from_format('d/m/Y', $row['Fecha']);
                    if ($dateObj) {
                        $fecha = $dateObj->format('d/m/Y');
                    } else {
                        $fecha = $row['Fecha'];
                    }
                }
            }
            echo "<td>" . $fecha . "</td>";
            
            // Tiempo de ejecución
            $tiempoEjec = $row['Tiempo_Ejec_Real'] ?? $row['Tiempo_Ejec'] ?? 'N/A';
            if (is_numeric($tiempoEjec)) {
                echo "<td>" . number_format($tiempoEjec, 2) . "</td>";
            } else {
                echo "<td>" . $tiempoEjec . "</td>";
            }
            
            echo "<td>" . ($row['PA'] ?? 'Sin asignar') . "</td>";
            echo "</tr>";
        }
        sqlsrv_free_stmt($stmtExport);
    }
    
    echo "</table>";
    exit();
}

// Solo ejecutar consultas si el usuario tiene permisos para el dashboard
if ($tienePermisosDashboard) {
    // Procesar filtros
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $estatus = $_GET['estatus'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    $empresa = $_GET['empresa'] ?? '';
    $asunto = $_GET['asunto'] ?? ''; // Cambiado de 'tipo' a 'asunto'

    // Construir condiciones WHERE dinámicamente
    $whereConditions = ["1=1"];
    $params = array();

    // Filtro por fechas - VERSIÓN PARA FECHAS EN TEXTO
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        // Convertir fechas a formato dd/mm/yyyy para comparar con el texto en la BD
        $fecha_inicio_dmy = date('d/m/Y', strtotime($fecha_inicio));
        $fecha_fin_dmy = date('d/m/Y', strtotime($fecha_fin));
        
        $whereConditions[] = "CONVERT(DATE, Fecha, 103) BETWEEN CONVERT(DATE, ?, 103) AND CONVERT(DATE, ?, 103)";
        $params[] = $fecha_inicio_dmy;
        $params[] = $fecha_fin_dmy;
    }

    // Filtro por estatus
    if (!empty($estatus)) {
        $whereConditions[] = "Estatus = ?";
        $params[] = $estatus;
    }

    // Filtro por prioridad
    if (!empty($prioridad)) {
        $whereConditions[] = "Prioridad = ?";
        $params[] = $prioridad;
    }

    // Filtro por empresa
    if (!empty($empresa)) {
        $whereConditions[] = "Empresa = ?";
        $params[] = $empresa;
    }

    // Filtro por asunto (antes 'tipo')
    if (!empty($asunto)) {
        $whereConditions[] = "Asunto LIKE ?";
        $params[] = '%' . $asunto . '%';
    }

    $whereClause = implode(" AND ", $whereConditions);

    // CONSULTA PRINCIPAL CON FILTROS - MEJORADA PARA MANEJAR NULLS
    $sqlTickets = "SELECT 
        Id_Ticket,
        Nombre,
        Correo,
        Prioridad,
        Empresa,
        Asunto,
        Mensaje,
        Fecha,
        Hora,
        Estatus,
        PA,
        Tipo,
        Clasificacion,
        Tiempo_Ejec,
        FechaEnProceso,
        HoraEnProceso,
        FechaTerminado,
        HoraTerminado,
        -- Calcular tiempo de ejecución MEJORADO: usar Fecha si FechaEnProceso es NULL
        CASE 
            WHEN FechaTerminado IS NOT NULL
            THEN 
                DATEDIFF(HOUR, 
                    COALESCE(FechaEnProceso, CONVERT(DATE, Fecha, 103)), 
                    FechaTerminado
                )
            WHEN Tiempo_Ejec IS NOT NULL AND Tiempo_Ejec != '' AND ISNUMERIC(Tiempo_Ejec) = 1 
            THEN CAST(Tiempo_Ejec as FLOAT)
            ELSE NULL
        END as Tiempo_Ejec_Real
        
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    ORDER BY 
        Fecha DESC, 
        Hora DESC";

    // Ejecutar consulta con parámetros
    $stmtTickets = sqlsrv_query($conn, $sqlTickets, $params);

    // Consulta para total de tickets con filtros
    $sqlTotal = "SELECT COUNT(*) as total FROM [Ticket].[dbo].[T3] WHERE $whereClause";
    $stmtTotal = sqlsrv_query($conn, $sqlTotal, $params);
    $totalTickets = 0;
    if ($stmtTotal) {
        $row = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
        $totalTickets = $row ? $row['total'] : 0;
        sqlsrv_free_stmt($stmtTotal);
    }

    // CONSULTA PARA TIEMPO PROMEDIO DE EJECUCIÓN GENERAL - MEJORADA PARA MANEJAR NULLS
    $sqlTiempoPromedio = "SELECT 
        AVG(
            CASE 
                WHEN FechaTerminado IS NOT NULL
                THEN 
                    DATEDIFF(HOUR, 
                        COALESCE(FechaEnProceso, CONVERT(DATE, Fecha, 103)), 
                        FechaTerminado
                    )
                WHEN Tiempo_Ejec IS NOT NULL AND Tiempo_Ejec != '' AND ISNUMERIC(Tiempo_Ejec) = 1 
                THEN CAST(Tiempo_Ejec as FLOAT)
                ELSE NULL
            END
        ) as tiempo_promedio_general,
        
        COUNT(*) as total_tickets,
        
        -- Contar tickets con tiempo calculado correctamente
        SUM(
            CASE 
                WHEN FechaTerminado IS NOT NULL
                THEN 1
                WHEN Tiempo_Ejec IS NOT NULL AND Tiempo_Ejec != '' AND ISNUMERIC(Tiempo_Ejec) = 1 
                THEN 1
                ELSE 0
            END
        ) as total_tickets_con_tiempo

    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause";

    $stmtTiempoPromedio = sqlsrv_query($conn, $sqlTiempoPromedio, $params);
    $tiempoPromedioGeneral = 0;
    $totalTicketsConTiempo = 0;
    if ($stmtTiempoPromedio) {
        $row = sqlsrv_fetch_array($stmtTiempoPromedio, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $tiempoPromedioGeneral = round($row['tiempo_promedio_general'], 2);
            $totalTicketsConTiempo = $row['total_tickets_con_tiempo'];
        }
        sqlsrv_free_stmt($stmtTiempoPromedio);
    }

    // Consulta para distribución por estatus con filtros
    $estatusData = [];
    $sqlEstatus = "SELECT 
        ISNULL(NULLIF(Estatus, ''), 'Pendiente') as Estatus,
        COUNT(*) as cantidad 
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    GROUP BY ISNULL(NULLIF(Estatus, ''), 'Pendiente')";

    $stmtEstatus = sqlsrv_query($conn, $sqlEstatus, $params);
    if ($stmtEstatus) {
        while ($row = sqlsrv_fetch_array($stmtEstatus, SQLSRV_FETCH_ASSOC)) {
            $estatusData[$row['Estatus']] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtEstatus);
    }

    // Consulta para distribución por prioridad con filtros
    $prioridadData = [];
    $sqlPrioridad = "SELECT 
        ISNULL(NULLIF(Prioridad, ''), 'No especificada') as Prioridad,
        COUNT(*) as cantidad 
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    GROUP BY ISNULL(NULLIF(Prioridad, ''), 'No especificada')";

    $stmtPrioridad = sqlsrv_query($conn, $sqlPrioridad, $params);
    if ($stmtPrioridad) {
        while ($row = sqlsrv_fetch_array($stmtPrioridad, SQLSRV_FETCH_ASSOC)) {
            $prioridadData[$row['Prioridad']] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtPrioridad);
    }

    // CONSULTA CORREGIDA: Distribución por empresa - NORMALIZAR NOMBRES
    $empresaData = [];
    $sqlEmpresa = "SELECT 
        CASE 
            WHEN Empresa IS NULL OR Empresa = '' OR Empresa = 'N/A' 
            THEN 'No especificada'
            WHEN UPPER(TRIM(Empresa)) LIKE '%CONTA%'
            THEN 'FINANZAS Y CONTABILIDAD'
            WHEN UPPER(TRIM(Empresa)) LIKE '%FINANZAS%'
            THEN 'FINANZAS Y CONTABILIDAD'
            ELSE UPPER(TRIM(Empresa))
        END as Empresa_Normalizada,
        COUNT(*) as cantidad 
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    GROUP BY 
        CASE 
            WHEN Empresa IS NULL OR Empresa = '' OR Empresa = 'N/A' 
            THEN 'No especificada'
            WHEN UPPER(TRIM(Empresa)) LIKE '%CONTA%'
            THEN 'FINANZAS Y CONTABILIDAD'
            WHEN UPPER(TRIM(Empresa)) LIKE '%FINANZAS%'
            THEN 'FINANZAS Y CONTABILIDAD'
            ELSE UPPER(TRIM(Empresa))
        END
    ORDER BY cantidad DESC";

    $stmtEmpresa = sqlsrv_query($conn, $sqlEmpresa, $params);
    if ($stmtEmpresa) {
        $contador = 0;
        $otrosTotal = 0;
        $totalEmpresas = 0;
        
        while ($row = sqlsrv_fetch_array($stmtEmpresa, SQLSRV_FETCH_ASSOC)) {
            $empresaNombre = $row['Empresa_Normalizada'];
            $cantidad = $row['cantidad'];
            $totalEmpresas += $cantidad;
            
            // Tomar las 15 empresas principales (aumentado de 12 a 15)
            if ($contador < 15) {
                $empresaData[$empresaNombre] = $cantidad;
                $contador++;
            } else {
                // Agrupar el resto como "Otras Empresas"
                $otrosTotal += $cantidad;
            }
        }
        
        // Si hay más de 15 empresas, agregar "Otras Empresas" como categoría
        if ($otrosTotal > 0) {
            $empresaData['Otras AREAS/EMPRESAS'] = $otrosTotal;
        }
        
        sqlsrv_free_stmt($stmtEmpresa);
        
        // Si no hay datos de empresas, agregar "No especificada"
        if (empty($empresaData)) {
            $empresaData['No especificada'] = 0;
        }
    }

    // Consulta para distribución por asunto (antes 'tipo') con filtros
    $asuntoData = [];
    $sqlAsunto = "SELECT 
        ISNULL(NULLIF(Asunto, ''), 'No especificado') as Asunto,
        COUNT(*) as cantidad 
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    GROUP BY ISNULL(NULLIF(Asunto, ''), 'No especificado')";

    $stmtAsunto = sqlsrv_query($conn, $sqlAsunto, $params);
    if ($stmtAsunto) {
        while ($row = sqlsrv_fetch_array($stmtAsunto, SQLSRV_FETCH_ASSOC)) {
            $asuntoData[$row['Asunto']] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtAsunto);
    }

    // CONSULTA PARA EVOLUCIÓN MENSUAL INDEPENDIENTE DE FILTROS - SOLO AÑO ACTUAL
    $mensualData = [];
    $currentYear = date('Y');
    $sqlMensual = "SELECT 
        MONTH(CONVERT(DATETIME, Fecha, 103)) as mes,
        COUNT(*) as cantidad
    FROM [Ticket].[dbo].[T3] 
    WHERE YEAR(CONVERT(DATETIME, Fecha, 103)) = ?
    GROUP BY MONTH(CONVERT(DATETIME, Fecha, 103))
    ORDER BY mes";

    $stmtMensual = sqlsrv_query($conn, $sqlMensual, array($currentYear));
    if ($stmtMensual) {
        while ($row = sqlsrv_fetch_array($stmtMensual, SQLSRV_FETCH_ASSOC)) {
            $mesKey = $row['mes'];
            $mensualData[$mesKey] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtMensual);
    }

    // Consulta para quién atendió los tickets - AGRUPADA POR CATEGORÍAS
    $atendidoPorData = [];
    $sqlAtendidoPor = "SELECT 
        CASE 
            WHEN PA LIKE '%luis%' OR PA LIKE '%Luis%' THEN 'Luis'
            WHEN PA LIKE '%alfredo%' OR PA LIKE '%Alfredo%' THEN 'Alfredo'
            WHEN PA LIKE '%ariel%' OR PA LIKE '%Ariel%' THEN 'Ariel'
            WHEN PA LIKE '%romero%' OR PA LIKE '%Romero%' THEN 'Romero'
            WHEN PA IS NULL OR PA = '' THEN 'Sin asignar'
            ELSE 'Otros'
        END as AtendidoPor_Categoria,
        COUNT(*) as cantidad 
    FROM [Ticket].[dbo].[T3] 
    WHERE $whereClause
    GROUP BY 
        CASE 
            WHEN PA LIKE '%luis%' OR PA LIKE '%Luis%' THEN 'Luis'
            WHEN PA LIKE '%alfredo%' OR PA LIKE '%Alfredo%' THEN 'Alfredo'
            WHEN PA LIKE '%ariel%' OR PA LIKE '%Ariel%' THEN 'Ariel'
            WHEN PA LIKE '%romero%' OR PA LIKE '%Romero%' THEN 'Romero'
            WHEN PA IS NULL OR PA = '' THEN 'Sin asignar'
            ELSE 'Otros'
        END";

    $stmtAtendidoPor = sqlsrv_query($conn, $sqlAtendidoPor, $params);
    if ($stmtAtendidoPor) {
        while ($row = sqlsrv_fetch_array($stmtAtendidoPor, SQLSRV_FETCH_ASSOC)) {
            $atendidoPorData[$row['AtendidoPor_Categoria']] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtAtendidoPor);
    }

    // CONSULTA PARA GRÁFICA DE TIEMPO DE EJECUCIÓN POR RANGO DE CONSULTA
    $tiempoEjecucionData = [];
    $sqlTiempoEjecucion = "SELECT 
        CASE 
            WHEN Tiempo_Ejec_Real IS NULL THEN 'Sin tiempo'
            WHEN Tiempo_Ejec_Real <= 4 THEN 'Rápido (≤4h)'
            WHEN Tiempo_Ejec_Real <= 24 THEN 'Moderado (4-24h)'
            WHEN Tiempo_Ejec_Real <= 72 THEN 'Lento (24-72h)'
            ELSE 'Muy lento (>72h)'
        END as rango_tiempo,
        COUNT(*) as cantidad
    FROM (
        SELECT 
            CASE 
                WHEN FechaTerminado IS NOT NULL
                THEN 
                    DATEDIFF(HOUR, 
                        COALESCE(FechaEnProceso, CONVERT(DATE, Fecha, 103)), 
                        FechaTerminado
                    )
                WHEN Tiempo_Ejec IS NOT NULL AND Tiempo_Ejec != '' AND ISNUMERIC(Tiempo_Ejec) = 1 
                THEN CAST(Tiempo_Ejec as FLOAT)
                ELSE NULL
            END as Tiempo_Ejec_Real
        FROM [Ticket].[dbo].[T3] 
        WHERE $whereClause
    ) as subquery
    GROUP BY 
        CASE 
            WHEN Tiempo_Ejec_Real IS NULL THEN 'Sin tiempo'
            WHEN Tiempo_Ejec_Real <= 4 THEN 'Rápido (≤4h)'
            WHEN Tiempo_Ejec_Real <= 24 THEN 'Moderado (4-24h)'
            WHEN Tiempo_Ejec_Real <= 72 THEN 'Lento (24-72h)'
            ELSE 'Muy lento (>72h)'
        END";

    $stmtTiempoEjecucion = sqlsrv_query($conn, $sqlTiempoEjecucion, $params);
    if ($stmtTiempoEjecucion) {
        while ($row = sqlsrv_fetch_array($stmtTiempoEjecucion, SQLSRV_FETCH_ASSOC)) {
            $tiempoEjecucionData[$row['rango_tiempo']] = $row['cantidad'];
        }
        sqlsrv_free_stmt($stmtTiempoEjecucion);
    }

    // Obtener opciones para filtros
    $estatusOptions = [];
    $sqlEstatusOpt = "SELECT DISTINCT Estatus FROM [Ticket].[dbo].[T3] WHERE Estatus IS NOT NULL AND Estatus != '' ORDER BY Estatus";
    $stmtEstatusOpt = sqlsrv_query($conn, $sqlEstatusOpt);
    if ($stmtEstatusOpt) {
        while ($row = sqlsrv_fetch_array($stmtEstatusOpt, SQLSRV_FETCH_ASSOC)) {
            $estatusOptions[] = $row['Estatus'];
        }
        sqlsrv_free_stmt($stmtEstatusOpt);
    }

    $prioridadOptions = [];
    $sqlPrioridadOpt = "SELECT DISTINCT Prioridad FROM [Ticket].[dbo].[T3] WHERE Prioridad IS NOT NULL AND Prioridad != '' ORDER BY Prioridad";
    $stmtPrioridadOpt = sqlsrv_query($conn, $sqlPrioridadOpt);
    if ($stmtPrioridadOpt) {
        while ($row = sqlsrv_fetch_array($stmtPrioridadOpt, SQLSRV_FETCH_ASSOC)) {
            $prioridadOptions[] = $row['Prioridad'];
        }
        sqlsrv_free_stmt($stmtPrioridadOpt);
    }

    $empresaOptions = [];
    $sqlEmpresaOpt = "SELECT DISTINCT Empresa FROM [Ticket].[dbo].[T3] WHERE Empresa IS NOT NULL AND Empresa != '' ORDER BY Empresa";
    $stmtEmpresaOpt = sqlsrv_query($conn, $sqlEmpresaOpt);
    if ($stmtEmpresaOpt) {
        while ($row = sqlsrv_fetch_array($stmtEmpresaOpt, SQLSRV_FETCH_ASSOC)) {
            $empresaOptions[] = $row['Empresa'];
        }
        sqlsrv_free_stmt($stmtEmpresaOpt);
    }

    // Obtener opciones de asunto (categorías principales de los asuntos)
    $asuntoOptions = [];
    $sqlAsuntoOpt = "SELECT DISTINCT Asunto FROM [Ticket].[dbo].[T3] WHERE Asunto IS NOT NULL AND Asunto != '' ORDER BY Asunto";
    $stmtAsuntoOpt = sqlsrv_query($conn, $sqlAsuntoOpt);
    if ($stmtAsuntoOpt) {
        while ($row = sqlsrv_fetch_array($stmtAsuntoOpt, SQLSRV_FETCH_ASSOC)) {
            $asuntoOptions[] = $row['Asunto'];
        }
        sqlsrv_free_stmt($stmtAsuntoOpt);
    }

    // Preparar datos para gráficas ECharts
    $mensualLabels = [];
    $mensualValues = [];

    // Generar todos los meses del año actual
    for ($i = 1; $i <= 12; $i++) {
        $month = date('Y-m', strtotime("$currentYear-$i-01"));
        $mensualLabels[] = date('M Y', strtotime($month));
        $mensualValues[] = $mensualData[$i] ?? 0;
    }
}

// Crear iniciales para el avatar
$iniciales = substr($nombre, 0, 2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - SOPORTE TÉCNICO BACROCORP</title>

  <!-- Prevenir cache en el navegador -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <!-- Fuentes -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- ECharts -->
  <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

  <style>
    /* ESTILOS CSS COMPLETOS CON DISEÑO PROFESIONAL MEJORADO */
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

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

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

    /* SIDEBAR MEJORADO */
    #sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 240px;
      background: linear-gradient(165deg, var(--navy-primary) 0%, var(--navy-secondary) 50%, var(--navy-tertiary) 100%);
      backdrop-filter: blur(20px);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 5px 0 25px rgba(0, 0, 0, 0.2);
      padding-top: var(--spacing-lg);
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1000;
      overflow-y: auto;
    }

    #sidebar.hidden {
      transform: translateX(-100%);
    }

    .sidebar-header {
      padding: var(--spacing-md) var(--spacing-lg);
      margin-bottom: var(--spacing-lg);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-logo {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 1.5rem;
      color: var(--accent-blue);
      letter-spacing: 0.05em;
      text-align: center;
    }

    .sidebar-subtitle {
      font-size: 0.75rem;
      color: var(--text-light);
      text-align: center;
      margin-top: var(--spacing-xs);
      opacity: 0.8;
    }

    #sidebar a {
      display: flex;
      align-items: center;
      color: var(--text-light);
      font-weight: 500;
      font-size: 0.9rem;
      padding: var(--spacing-sm) var(--spacing-lg);
      text-decoration: none;
      border-radius: var(--border-radius-md);
      margin: 4px var(--spacing-md);
      letter-spacing: 0.02em;
      transition: all 0.3s ease;
      user-select: none;
      position: relative;
      overflow: hidden;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    #sidebar a::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent);
      transition: left 0.6s;
    }

    #sidebar a:hover::before {
      left: 100%;
    }

    #sidebar a:hover {
      background: rgba(59, 130, 246, 0.15);
      color: white;
      transform: translateX(8px);
      border-color: rgba(59, 130, 246, 0.3);
      box-shadow: 0 6px 15px rgba(59, 130, 246, 0.2);
    }

    #sidebar a.active {
      background: var(--gradient-blue);
      color: white;
      border-color: rgba(59, 130, 246, 0.5);
      box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    }

    #sidebar a i {
      margin-right: var(--spacing-sm);
      font-size: 1.1rem;
      transition: transform 0.3s ease;
      min-width: 24px;
      text-align: center;
      color: var(--accent-blue);
    }

    #sidebar a:hover i,
    #sidebar a.active i {
      transform: scale(1.1);
      color: white;
    }

    /* Estilo para enlaces deshabilitados */
    #sidebar a.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    #sidebar a.disabled:hover {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-light);
      transform: none;
      border-color: rgba(255, 255, 255, 0.08);
      box-shadow: none;
    }

    #sidebar a.disabled::before {
      display: none;
    }

    .theme-toggle {
      margin-top: auto;
      margin-bottom: var(--spacing-lg);
      padding: 0 var(--spacing-md);
    }

    .theme-toggle-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: var(--text-light);
      padding: var(--spacing-sm);
      border-radius: var(--border-radius-md);
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      width: 100%;
    }

    .theme-toggle-btn:hover {
      background: rgba(59, 130, 246, 0.15);
      color: white;
      border-color: rgba(59, 130, 246, 0.3);
      transform: translateY(-2px);
    }

    .theme-toggle-btn i {
      margin-right: var(--spacing-sm);
    }

    /* Botón de Cerrar Sesión */
    .logout-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(239, 68, 68, 0.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fecaca;
      padding: var(--spacing-sm);
      border-radius: var(--border-radius-md);
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      width: 100%;
      margin-bottom: var(--spacing-lg);
    }

    .logout-btn:hover {
      background: rgba(239, 68, 68, 0.25);
      color: white;
      border-color: rgba(239, 68, 68, 0.5);
      transform: translateY(-2px);
    }

    .logout-btn i {
      margin-right: var(--spacing-sm);
    }

    /* Fondo con efecto glassmorphism y gotas de agua */
    .background-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -3;
      overflow: hidden;
      background: linear-gradient(135deg, var(--pearl-white) 0%, var(--snow-white) 100%);
    }

    .dark-mode .background-container {
      background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-secondary) 100%);
    }

    .glass-layer {
      position: absolute;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, 
        rgba(30, 58, 138, 0.03) 0%, 
        rgba(59, 130, 246, 0.05) 25%, 
        rgba(96, 165, 250, 0.04) 50%, 
        rgba(14, 165, 233, 0.03) 75%, 
        rgba(59, 130, 246, 0.02) 100%);
      backdrop-filter: blur(40px);
    }

    .water-drops-container {
      position: absolute;
      width: 100%;
      height: 100%;
    }

    .water-drop {
      position: absolute;
      background: radial-gradient(circle, 
        rgba(59, 130, 246, 0.12) 0%, 
        rgba(96, 165, 250, 0.08) 30%, 
        transparent 70%);
      border-radius: 50%;
      animation: waterRipple 10s infinite ease-in-out;
    }

    @keyframes waterRipple {
      0% {
        transform: scale(0);
        opacity: 0.4;
      }
      50% {
        opacity: 0.2;
      }
      100% {
        transform: scale(8);
        opacity: 0;
      }
    }

    /* Header y Navegación - MEJORADO */
    .dashboard-header {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) 260px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
      transition: padding 0.4s ease;
    }

    .dashboard-header.full-width {
      padding-left: var(--spacing-lg);
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }

    .logo {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 1.5rem;
      background: var(--gradient-blue);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .logo-subtitle {
      font-size: 0.8rem;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .nav-controls {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
    }

    /* Botón Sidebar Superior Derecho */
    .sidebar-toggle {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      color: var(--accent-blue);
      width: 40px;
      height: 40px;
      border-radius: var(--border-radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1.1rem;
    }

    .sidebar-toggle:hover {
      background: var(--accent-blue);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
      padding: 6px var(--spacing-sm);
      border-radius: var(--border-radius-md);
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--gradient-blue);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 0.9rem;
    }

    .user-details {
      display: flex;
      flex-direction: column;
    }

    .user-name {
      font-weight: 600;
      font-size: 0.85rem;
    }

    .user-role {
      font-size: 0.75rem;
      color: var(--text-secondary);
    }

    /* CONTENEDOR DE MÓDULOS */
    .module-container {
      position: fixed;
      top: 60px;
      left: 260px;
      right: var(--spacing-lg);
      bottom: var(--spacing-lg);
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-xl);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      z-index: 50;
      display: none;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .module-container.active {
      display: block;
    }

    .module-iframe {
      width: 100%;
      height: 100%;
      border: none;
      border-radius: var(--border-radius-xl);
    }

    /* Contenido Principal - MEJORADO */
    .dashboard-container {
      padding: var(--spacing-lg) var(--spacing-lg) var(--spacing-lg) 260px;
      max-width: 100%;
      margin: 0 auto;
      transition: padding 0.4s ease;
      min-height: calc(100vh - 60px);
    }

    .dashboard-container.full-width {
      padding-left: var(--spacing-lg);
    }

    /* TÍTULOS MEJORADOS */
    .dashboard-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      margin-bottom: var(--spacing-xs);
      background: var(--gradient-blue);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      position: relative;
      line-height: 1.2;
    }

    .dashboard-subtitle {
      color: var(--text-secondary);
      margin-bottom: var(--spacing-lg);
      font-size: 1rem;
      max-width: 600px;
      line-height: 1.5;
    }

    /* Información del Usuario - MEJORADA */
    .user-info-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      margin-bottom: var(--spacing-lg);
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
      position: relative;
      overflow: hidden;
    }

    .user-info-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: var(--gradient-blue);
    }

    .user-info-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--gradient-blue);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.1rem;
      border: 2px solid var(--card-bg);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .user-info-details {
      flex: 1;
    }

    .user-info-name {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--text-primary);
    }

    .user-info-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: var(--spacing-sm);
      margin-top: var(--spacing-xs);
    }

    .user-info-stat {
      display: flex;
      flex-direction: column;
    }

    .user-info-label {
      font-size: 0.75rem;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }

    .user-info-value {
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--accent-blue);
    }

    /* Filtros Mejorados */
    .filters-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg);
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      position: relative;
    }

    .filters-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--gradient-blue);
      border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-xs);
    }

    .filter-label {
      font-size: 0.8rem;
      color: var(--accent-blue);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .filter-select {
      background: var(--card-bg);
      border: 1px solid var(--glass-border);
      color: var(--text-primary);
      padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      cursor: pointer;
      backdrop-filter: blur(10px);
    }

    .dark-mode .filter-select {
      background: var(--select-bg);
      color: var(--select-text);
      border-color: var(--select-border);
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .filter-select:hover {
      border-color: var(--accent-blue);
    }

    /* Estilos para las opciones del select */
    .filter-select option {
      background: var(--card-bg);
      color: var(--text-primary);
      padding: 8px;
    }

    .dark-mode .filter-select option {
      background: var(--select-bg);
      color: var(--select-text);
    }

    /* Estilos adicionales para filtros de fecha */
    .filter-date-group {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-xs);
    }

    .filter-date-input {
      background: var(--card-bg);
      border: 1px solid var(--glass-border);
      color: var(--text-primary);
      padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }

    .dark-mode .filter-date-input {
      background: var(--select-bg);
      color: var(--select-text);
      border-color: var(--select-border);
    }

    .filter-date-input:focus {
      outline: none;
      border-color: var(--accent-blue);
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    .filter-actions {
      display: flex;
      gap: var(--spacing-sm);
      align-items: flex-end;
    }

    .filter-btn {
      background: var(--gradient-blue);
      color: white;
      border: none;
      padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s ease;
      height: fit-content;
      display: flex;
      align-items: center;
      gap: var(--spacing-xs);
    }

    .filter-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    }

    .filter-reset {
      background: var(--glass-bg);
      color: var(--text-secondary);
      border: 1px solid var(--glass-border);
    }

    .filter-reset:hover {
      background: var(--accent-blue);
      color: white;
      border-color: var(--accent-blue);
    }

    /* Botón de Exportar a Excel */
    .export-btn {
      background: var(--gradient-success);
      color: white;
      border: none;
      padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--border-radius-md);
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s ease;
      height: fit-content;
      display: flex;
      align-items: center;
      gap: var(--spacing-xs);
      text-decoration: none;
    }

    .export-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
    }

    /* Grid de KPIs PROFESIONALES */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg);
    }

    .kpi-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--gradient-blue);
    }

    .kpi-card.success::before {
      background: var(--gradient-success);
    }

    .kpi-card.warning::before {
      background: var(--gradient-warning);
    }

    .kpi-card.error::before {
      background: var(--gradient-error);
    }

    .kpi-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .kpi-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: var(--spacing-sm);
    }

    .kpi-title {
      font-size: 0.85rem;
      color: var(--text-secondary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .kpi-icon {
      width: 42px;
      height: 42px;
      border-radius: var(--border-radius-md);
      background: var(--gradient-blue);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.1rem;
      box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
    }

    .kpi-card.success .kpi-icon {
      background: var(--gradient-success);
      box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
    }

    .kpi-card.warning .kpi-icon {
      background: var(--gradient-warning);
      box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
    }

    .kpi-card.error .kpi-icon {
      background: var(--gradient-error);
      box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
    }

    .kpi-value {
      font-family: 'Montserrat', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: var(--spacing-xs);
      background: var(--gradient-blue);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .kpi-card.success .kpi-value {
      background: var(--gradient-success);
      -webkit-background-clip: text;
    }

    .kpi-card.warning .kpi-value {
      background: var(--gradient-warning);
      -webkit-background-clip: text;
    }

    .kpi-card.error .kpi-value {
      background: var(--gradient-error);
      -webkit-background-clip: text;
    }

    .kpi-comparison {
      color: var(--text-secondary);
      font-size: 0.75rem;
      margin-top: auto;
    }

    /* Grid de Gráficas PROFESIONAL con ECharts - MEJORADO */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg);
    }

    .chart-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      transition: transform 0.3s ease;
      position: relative;
      overflow: hidden;
      min-height: 380px; /* AUMENTADO EL TAMAÑO MÍNIMO */
      display: flex;
      flex-direction: column;
    }

    .chart-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--gradient-blue);
    }

    .chart-card:hover {
      transform: translateY(-3px);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--spacing-md);
      flex-shrink: 0;
    }

    .chart-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .chart-actions {
      display: flex;
      gap: var(--spacing-xs);
    }

    .chart-action-btn {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      color: var(--text-secondary);
      width: 32px;
      height: 32px;
      border-radius: var(--border-radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.8rem;
    }

    .chart-action-btn:hover {
      background: var(--accent-blue);
      color: white;
      border-color: var(--accent-blue);
    }

    .chart-container {
      position: relative;
      height: 380px; /* AUMENTADO SIGNIFICATIVAMENTE DE 320px A 380px */
      width: 100%;
      flex-grow: 1;
    }

    /* TIEMPO PROMEDIO GENERAL - UNIFICADO */
    .tiempo-promedio-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--spacing-md);
      margin-bottom: var(--spacing-lg);
    }

    .tiempo-promedio-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .tiempo-promedio-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--gradient-blue);
    }

    .tiempo-promedio-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .tiempo-promedio-value {
      font-family: 'Montserrat', sans-serif;
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--accent-blue);
      margin: var(--spacing-sm) 0;
    }

    .tiempo-promedio-label {
      font-size: 0.9rem;
      color: var(--text-secondary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .tiempo-promedio-subtitle {
      font-size: 0.8rem;
      color: var(--text-secondary);
      margin-top: 4px;
    }

    /* Tabla de Tickets PROFESIONAL */
    .table-section {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      margin-bottom: var(--spacing-lg);
      position: relative;
      overflow: hidden;
    }

    .table-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--gradient-blue);
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--spacing-md);
    }

    .section-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .table-info {
      font-size: 0.8rem;
      color: var(--text-secondary);
    }

    .table-actions {
      display: flex;
      gap: var(--spacing-sm);
    }

    .tickets-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8rem;
    }

    .tickets-table th {
      text-align: left;
      padding: var(--spacing-sm) var(--spacing-md);
      border-bottom: 2px solid var(--glass-border);
      color: var(--text-secondary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background: rgba(59, 130, 246, 0.05);
    }

    .tickets-table td {
      padding: var(--spacing-sm) var(--spacing-md);
      border-bottom: 1px solid var(--glass-border);
    }

    .tickets-table tr {
      transition: all 0.3s ease;
    }

    .tickets-table tr:hover {
      background: rgba(59, 130, 246, 0.05);
      transform: translateX(3px);
    }

    .status-badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      color: white;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .status-atendido {
      background: var(--gradient-success);
      color: white;
    }

    .status-en-proceso {
      background: var(--gradient-warning);
      color: white;
    }

    .status-pendiente {
      background: var(--gradient-blue);
      color: white;
    }

    .priority-alta {
      background: var(--gradient-error);
      color: white;
    }

    .priority-media {
      background: var(--gradient-warning);
      color: white;
    }

    .priority-baja {
      background: var(--gradient-blue);
      color: white;
    }

    /* Estilos para badges de tiempo */
    .tiempo-rapido {
      background: var(--gradient-success) !important;
      color: white !important;
    }

    .tiempo-moderado {
      background: var(--gradient-warning) !important;
      color: white !important;
    }

    .tiempo-lento {
      background: var(--gradient-error) !important;
      color: white !important;
    }

    /* Custom Alert Compacto */
    .custom-alert {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
      z-index: 2000;
      align-items: center;
      justify-content: center;
    }

    .custom-alert.active {
      display: flex;
    }

    .alert-content {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      max-width: 350px;
      width: 90%;
      text-align: center;
      transform: scale(0.9);
      opacity: 0;
      transition: all 0.3s ease;
    }

    .custom-alert.active .alert-content {
      transform: scale(1);
      opacity: 1;
    }

    .alert-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto var(--spacing-md);
      color: white;
      font-size: 1.5rem;
    }

    .alert-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: var(--spacing-sm);
      color: var(--text-primary);
    }

    .alert-message {
      color: var(--text-secondary);
      margin-bottom: var(--spacing-md);
      line-height: 1.5;
      font-size: 0.9rem;
    }

    .alert-actions {
      display: flex;
      gap: var(--spacing-sm);
      justify-content: center;
    }

    .alert-btn {
      padding: var(--spacing-xs) var(--spacing-lg);
      border-radius: var(--border-radius-sm);
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
      font-size: 0.85rem;
    }

    .alert-btn-cancel {
      background: var(--glass-bg);
      color: var(--text-secondary);
      border: 1px solid var(--glass-border);
    }

    .alert-btn-cancel:hover {
      background: var(--accent-blue);
      color: white;
      border-color: var(--accent-blue);
    }

    .alert-btn-confirm {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }

    .alert-btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
    }

    /* Alert de Permisos */
    .permission-alert {
      background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    }

    .permission-alert .alert-icon {
      background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    }

    .alert-btn-permission {
      background: linear-gradient(135deg, #f59e0b, #d97706) !important;
      color: white !important;
    }

    .alert-btn-permission:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(245, 158, 11, 0.3) !important;
    }

    /* Mensaje de Sin Permisos */
    .no-permission-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      text-align: center;
      padding: var(--spacing-xxl);
    }

    .no-permission-icon {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: linear-gradient(135deg, #f59e0b, #d97706);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: var(--spacing-lg);
      color: white;
      font-size: 3rem;
    }

    .no-permission-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: var(--spacing-md);
      background: linear-gradient(135deg, #f59e0b, #d97706);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .no-permission-message {
      color: var(--text-secondary);
      font-size: 1.1rem;
      margin-bottom: var(--spacing-lg);
      max-width: 500px;
      line-height: 1.6;
    }

    .no-permission-details {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border-radius: var(--border-radius-lg);
      padding: var(--spacing-md);
      box-shadow: var(--card-shadow);
      border: 1px solid var(--glass-border);
      max-width: 400px;
      width: 100%;
    }

    .no-permission-user {
      font-weight: 600;
      color: var(--accent-blue);
      margin-bottom: var(--spacing-sm);
    }

    .no-permission-info {
      font-size: 0.9rem;
      color: var(--text-secondary);
      line-height: 1.5;
    }

    /* RESPONSIVE DESIGN MEJORADO */
    @media (max-width: 1400px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
      
      .module-container {
        left: 260px;
        right: var(--spacing-md);
        bottom: var(--spacing-md);
      }
      
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 1200px) {
      .dashboard-container {
        padding: var(--spacing-md) var(--spacing-md) var(--spacing-md) 250px;
      }
      
      .dashboard-header {
        padding-left: 250px;
      }
      
      .dashboard-title {
        font-size: 1.6rem;
      }
      
      .module-container {
        left: 250px;
        right: var(--spacing-md);
      }
      
      .filters-container {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 992px) {
      #sidebar {
        width: 220px;
        transform: translateX(-100%);
      }
      
      #sidebar.active {
        transform: translateX(0);
      }
      
      .dashboard-header {
        padding: var(--spacing-sm) var(--spacing-md);
      }
      
      .dashboard-container {
        padding: var(--spacing-md);
      }
      
      .dashboard-title {
        font-size: 1.4rem;
      }
      
      .kpi-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
      }
      
      .charts-grid {
        gap: var(--spacing-md);
      }
      
      .filters-container {
        grid-template-columns: 1fr;
        gap: var(--spacing-sm);
      }
      
      .user-info-card {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-sm);
      }
      
      .user-info-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--spacing-sm);
      }
      
      .module-container {
        left: var(--spacing-md);
        right: var(--spacing-md);
        top: 55px;
        bottom: var(--spacing-md);
      }
      
      .alert-actions {
        flex-direction: column;
      }
      
      .no-permission-container {
        padding: var(--spacing-xl);
      }
      
      .no-permission-icon {
        width: 100px;
        height: 100px;
        font-size: 2.5rem;
      }
      
      .no-permission-title {
        font-size: 1.6rem;
      }
    }

    @media (max-width: 768px) {
      .dashboard-header {
        padding: var(--spacing-xs) var(--spacing-sm);
      }
      
      .dashboard-container {
        padding: var(--spacing-sm);
      }
      
      .dashboard-title {
        font-size: 1.3rem;
        margin-bottom: 6px;
      }
      
      .dashboard-subtitle {
        font-size: 0.9rem;
        margin-bottom: var(--spacing-md);
      }
      
      .kpi-card {
        padding: var(--spacing-sm);
      }
      
      .kpi-value {
        font-size: 1.7rem;
      }
      
      .chart-card {
        padding: var(--spacing-sm);
        min-height: 340px; /* Ajustado para móviles */
      }
      
      .chart-container {
        height: 280px; /* Ajustado para móviles */
      }
      
      .table-section {
        padding: var(--spacing-sm);
      }
      
      .tickets-table {
        font-size: 0.75rem;
      }
      
      .tickets-table th,
      .tickets-table td {
        padding: 8px 12px;
      }
      
      .module-container {
        left: var(--spacing-sm);
        right: var(--spacing-sm);
        top: 50px;
        bottom: var(--spacing-sm);
        border-radius: var(--border-radius-lg);
      }
      
      .nav-controls {
        gap: var(--spacing-sm);
      }
      
      .user-info {
        padding: 4px;
      }
      
      .user-details {
        display: none;
      }
      
      .no-permission-container {
        padding: var(--spacing-lg);
      }
      
      .no-permission-icon {
        width: 80px;
        height: 80px;
        font-size: 2rem;
      }
      
      .no-permission-title {
        font-size: 1.4rem;
      }
      
      .no-permission-message {
        font-size: 1rem;
      }
    }

    @media (max-width: 576px) {
      #sidebar {
        width: 100%;
      }
      
      .dashboard-title {
        font-size: 1.2rem;
      }
      
      .dashboard-subtitle {
        font-size: 0.85rem;
      }
      
      .user-info-card {
        padding: var(--spacing-sm);
      }
      
      .user-info-stats {
        grid-template-columns: 1fr;
        gap: var(--spacing-xs);
      }
      
      .kpi-grid {
        gap: var(--spacing-sm);
      }
      
      .kpi-card {
        padding: var(--spacing-sm);
      }
      
      .kpi-value {
        font-size: 1.5rem;
      }
      
      .charts-grid {
        gap: var(--spacing-sm);
      }
      
      .chart-card {
        padding: var(--spacing-sm);
        min-height: 320px; /* Ajustado para móviles pequeños */
      }
      
      .chart-container {
        height: 260px; /* Ajustado para móviles pequeños */
      }
      
      .chart-title {
        font-size: 0.9rem;
      }
      
      .table-section {
        padding: var(--spacing-sm);
        overflow-x: auto;
      }
      
      .tickets-table {
        min-width: 600px;
      }
      
      .module-container {
        left: var(--spacing-xs);
        right: var(--spacing-xs);
        top: 45px;
        bottom: var(--spacing-xs);
        border-radius: var(--border-radius-md);
      }
      
      .logo-container {
        flex-direction: column;
        gap: 4px;
        text-align: center;
      }
      
      .logo {
        font-size: 1.3rem;
      }
      
      .logo-subtitle {
        font-size: 0.75rem;
      }
      
      .no-permission-container {
        padding: var(--spacing-md);
      }
      
      .no-permission-icon {
        width: 70px;
        height: 70px;
        font-size: 1.8rem;
      }
      
      .no-permission-title {
        font-size: 1.2rem;
      }
      
      .no-permission-message {
        font-size: 0.9rem;
      }
    }

    /* Animaciones de entrada mejoradas */
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

    .animate-fade-in-up {
      animation: fadeInUp 0.5s ease-out;
    }

    /* Scrollbar personalizado */
    ::-webkit-scrollbar {
      width: 6px;
    }

    ::-webkit-scrollbar-track {
      background: var(--glass-bg);
      border-radius: var(--border-radius-sm);
    }

    ::-webkit-scrollbar-thumb {
      background: var(--accent-blue);
      border-radius: var(--border-radius-sm);
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--accent-blue-dark);
    }
  </style>
</head>

<body class="dark-mode">
  <!-- SIDEBAR MEJORADO -->
  <nav id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">BACROCORP</div>
      <div class="sidebar-subtitle">Sistema de Soporte</div>
    </div>
    
    <!-- Enlace de Inicio con control de permisos -->
    <?php if ($tienePermisosDashboard): ?>
      <a href="#" class="nav-link" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a>
    <?php else: ?>
      <a href="#" class="nav-link disabled" data-page="dashboard"><i class="fas fa-home"></i>INICIO</a>
    <?php endif; ?>
    
    <a href="#" class="nav-link" data-page="levantar-ticket" data-link="http://desarollo-bacros/TicketBacros/Formtic1.php"><i class="fas fa-ticket-alt"></i>LEVANTAR TICKET</a>
    <a href="#" class="nav-link" data-page="revisar-tickets" data-link="http://desarollo-bacros/TicketBacros/RevisarT.php"><i class="fas fa-eye"></i>REVISAR TICKETS</a>
    
    <!-- Enlace de Procesar Tickets con control de permisos -->
    <?php if (in_array($usuarioActual, $usuariosPermitidosProcesar)): ?>
      <a href="#" class="nav-link" data-page="procesar-tickets" data-link="http://desarollo-bacros/TicketBacros/TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a>
    <?php else: ?>
      <a href="#" class="nav-link disabled" data-page="procesar-tickets" data-link="http://desarollo-bacros/TicketBacros/TableT1.php"><i class="fas fa-cogs"></i>PROCESAR TICKETS</a>
    <?php endif; ?>
    
    <!-- Enlace de Reportes con control de permisos -->
    <?php if ($tienePermisosDashboard): ?>
      <a href="#" class="nav-link active" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a>
    <?php else: ?>
      <a href="#" class="nav-link disabled" data-page="reportes"><i class="fas fa-chart-line"></i>REPORTES</a>
    <?php endif; ?>
    
    <div class="theme-toggle">
      <button class="theme-toggle-btn" id="themeToggle">
        <i class="fas fa-sun"></i> MODO CLARO
      </button>
    </div>

    <!-- Botón de Cerrar Sesión -->
    <div style="padding: 0 var(--spacing-md);">
      <button type="button" class="logout-btn" id="logoutBtn">
        <i class="fas fa-sign-out-alt"></i> CERRAR SESIÓN
      </button>
    </div>
  </nav>

  <!-- Custom Alert Compacto -->
  <div class="custom-alert" id="logoutAlert">
    <div class="alert-content">
      <div class="alert-icon">
        <i class="fas fa-sign-out-alt"></i>
      </div>
      <h3 class="alert-title">Cerrar Sesión</h3>
      <p class="alert-message">¿Estás seguro de que deseas cerrar tu sesión? Serás redirigido a la página de inicio de sesión.</p>
      <div class="alert-actions">
        <button class="alert-btn alert-btn-cancel" id="cancelLogout">Cancelar</button>
        <button class="alert-btn alert-btn-confirm" id="confirmLogout">Sí, Cerrar Sesión</button>
      </div>
    </div>
  </div>

  <!-- Alert de Permisos -->
  <div class="custom-alert" id="permissionAlert">
    <div class="alert-content">
      <div class="alert-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3 class="alert-title">Acceso Restringido</h3>
      <p class="alert-message" id="permissionMessage">No tienes permisos para acceder a este módulo.</p>
      <div class="alert-actions">
        <button class="alert-btn alert-btn-permission" id="closePermissionAlert">Aceptar</button>
      </div>
    </div>
  </div>

  <!-- Fondo con efectos glassmorphism y gotas de agua -->
  <div class="background-container">
    <div class="glass-layer"></div>
    <div class="water-drops-container" id="waterDrops"></div>
  </div>

  <!-- Header Mejorado -->
  <header class="dashboard-header">
    <div class="logo-container">
      <div class="logo">BACROCORP</div>
      <div class="logo-subtitle">Dashboard de Reportes</div>
    </div>
    
    <div class="nav-controls">
      <!-- Botón Sidebar Superior Derecho -->
      <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
      </button>
      
      <div class="user-info">
        <div class="user-avatar"><?php echo $iniciales; ?></div>
        <div class="user-details">
          <div class="user-name"><?php echo htmlspecialchars($nombre); ?></div>
          <div class="user-role"><?php echo htmlspecialchars($area); ?></div>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENEDOR DE MÓDULOS -->
  <div class="module-container" id="moduleContainer">
    <iframe src="" class="module-iframe" id="moduleIframe"></iframe>
  </div>

  <!-- Contenido Principal Mejorado -->
  <div class="dashboard-container">
    <?php if (!$tienePermisosDashboard): ?>
      <!-- MOSTRAR MENSAJE DE SIN PERMISOS -->
      <div class="no-permission-container animate-fade-in-up">
        <div class="no-permission-icon">
          <i class="fas fa-lock"></i>
        </div>
        <h1 class="no-permission-title">Acceso Restringido</h1>
        <p class="no-permission-message">
          No tienes permisos para acceder al Dashboard de Reportes.<br>
          Este módulo está restringido para usuarios autorizados.
        </p>
        <div class="no-permission-details">
          <div class="no-permission-user">
            Usuario: <?php echo htmlspecialchars($usuario); ?>
          </div>
          <div class="no-permission-info">
            Si crees que deberías tener acceso a este módulo,<br>
            contacta al administrador del sistema.
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Contenido del Dashboard (se oculta cuando se muestra el módulo) -->
      <div id="dashboardContent">
        <h1 class="dashboard-title animate-fade-in-up">Reportes de Tickets</h1>
        <p class="dashboard-subtitle animate-fade-in-up">Dashboard profesional con métricas en tiempo real del sistema de tickets</p>

        <!-- Información del Usuario - MEJORADA -->
        <div class="user-info-card animate-fade-in-up">
          <div class="user-info-avatar"><?php echo $iniciales; ?></div>
          <div class="user-info-details">
            <div class="user-info-name"><?php echo htmlspecialchars($nombre); ?></div>
            <div class="user-info-stats">
              <div class="user-info-stat">
                <div class="user-info-label">ID Empleado</div>
                <div class="user-info-value"><?php echo htmlspecialchars($id_empleado); ?></div>
              </div>
              <div class="user-info-stat">
                <div class="user-info-label">Área/Departamento</div>
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

        <!-- Filtros Mejorados - SIN BOTÓN DE EXPORTAR EXCEL -->
        <form method="GET" class="filters-container animate-fade-in-up">
          <div class="filter-date-group">
            <div class="filter-label">Fecha Inicio</div>
            <input type="date" class="filter-date-input" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
          </div>
          
          <div class="filter-date-group">
            <div class="filter-label">Fecha Fin</div>
            <input type="date" class="filter-date-input" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
          </div>
          
          <div class="filter-group">
            <div class="filter-label">Estatus</div>
            <select class="filter-select" name="estatus">
              <option value="">Todos los estatus</option>
              <?php foreach ($estatusOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $estatus == $option ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <div class="filter-label">Prioridad</div>
            <select class="filter-select" name="prioridad">
              <option value="">Todas las prioridades</option>
              <?php foreach ($prioridadOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $prioridad == $option ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <div class="filter-label">Empresa</div>
            <select class="filter-select" name="empresa">
              <option value="">Todas las empresas</option>
              <?php foreach ($empresaOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $empresa == $option ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-group">
            <div class="filter-label">Asunto</div>
            <select class="filter-select" name="asunto">
              <option value="">Todos los asuntos</option>
              <?php foreach ($asuntoOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $asunto == $option ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-actions">
            <button type="submit" class="filter-btn">
              <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="?" class="filter-btn filter-reset">
              <i class="fas fa-redo"></i> Limpiar
            </a>
          </div>
        </form>

        <?php if ($totalTickets > 0): ?>
        <!-- KPIs PROFESIONALES - MEJORADOS -->
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
            <div class="kpi-value"><?php echo $estatusData['Atendido'] ?? 0; ?></div>
            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round(($estatusData['Atendido'] ?? 0) / $totalTickets * 100, 1) : 0; ?>% del total</div>
          </div>

          <div class="kpi-card warning animate-fade-in-up">
            <div class="kpi-header">
              <div class="kpi-title">En Proceso</div>
              <div class="kpi-icon"><i class="fas fa-cogs"></i></div>
            </div>
            <div class="kpi-value"><?php echo $estatusData['En Proceso'] ?? 0; ?></div>
            <div class="kpi-comparison"><?php echo $totalTickets > 0 ? round(($estatusData['En Proceso'] ?? 0) / $totalTickets * 100, 1) : 0; ?>% del total</div>
          </div>

          <div class="kpi-card animate-fade-in-up">
            <div class="kpi-header">
              <div class="kpi-title">Tiempo Respuesta Prom.</div>
              <div class="kpi-icon"><i class="fas fa-stopwatch"></i></div>
            </div>
            <div class="kpi-value"><?php echo $tiempoPromedioGeneral; ?>h</div>
            <div class="kpi-comparison">
              <?php 
              $porcentajeConTiempo = $totalTickets > 0 ? round(($totalTicketsConTiempo / $totalTickets) * 100, 1) : 0;
              echo $porcentajeConTiempo; ?>% con tiempo registrado
            </div>
          </div>
        </div>

        <!-- Gráficas PROFESIONALES con ECharts - MEJORADAS -->
        <div class="charts-grid">
          <?php if (!empty($estatusData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header">
              <div class="chart-title">Distribución por Estatus</div>
              <div class="chart-actions">
                <button class="chart-action-btn" data-chart="statusChart"><i class="fas fa-expand"></i></button>
              </div>
            </div>
            <div class="chart-container" id="statusChart"></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($prioridadData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header">
              <div class="chart-title">Tickets por Prioridad</div>
              <div class="chart-actions">
                <button class="chart-action-btn" data-chart="priorityChart"><i class="fas fa-expand"></i></button>
              </div>
            </div>
            <div class="chart-container" id="priorityChart"></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($empresaData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header">
              <div class="chart-title">Tickets por ÁREA</div>
              <div class="chart-actions">
                <button class="chart-action-btn" data-chart="empresaChart"><i class="fas fa-expand"></i></button>
              </div>
            </div>
            <div class="chart-container" id="empresaChart"></div>
          </div>
          <?php endif; ?>

          <div class="chart-card animate-fade-in-up">
            <div class="chart-header">
              <div class="chart-title">Evolución Mensual <?php echo date('Y'); ?></div>
              <div class="chart-actions">
                <button class="chart-action-btn" data-chart="monthlyChart"><i class="fas fa-expand"></i></button>
              </div>
            </div>
            <div class="chart-container" id="monthlyChart"></div>
          </div>
        </div>

        <!-- TIEMPO PROMEDIO GENERAL - UNIFICADO -->
        <div class="tiempo-promedio-container">
          <div class="tiempo-promedio-card animate-fade-in-up">
            <div class="tiempo-promedio-label">Tiempo Promedio General</div>
            <div class="tiempo-promedio-value"><?php echo $tiempoPromedioGeneral; ?> hrs</div>
            <div class="tiempo-promedio-subtitle">
              Basado en <?php echo $totalTicketsConTiempo; ?> de <?php echo $totalTickets; ?> tickets con tiempo registrado
            </div>
          </div>

          <!-- Gráfica de Tiempo de Ejecución por Rango -->
          <?php if (!empty($tiempoEjecucionData)): ?>
          <div class="chart-card animate-fade-in-up">
            <div class="chart-header">
              <div class="chart-title">Tiempo de Ejecución por Rango</div>
              <div class="chart-actions">
                <button class="chart-action-btn" data-chart="tiempoEjecucionChart"><i class="fas fa-expand"></i></button>
              </div>
            </div>
            <div class="chart-container" id="tiempoEjecucionChart"></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- QUIÉN ATENDIÓ LOS TICKETS -->
        <?php if (!empty($atendidoPorData)): ?>
        <div class="chart-card animate-fade-in-up">
          <div class="chart-header">
            <div class="chart-title">Tickets Atendidos Por</div>
            <div class="chart-actions">
              <button class="chart-action-btn" data-chart="atendidoPorChart"><i class="fas fa-expand"></i></button>
            </div>
          </div>
          <div class="chart-container" id="atendidoPorChart"></div>
        </div>
        <?php endif; ?>

        <!-- Tabla de Tickets PROFESIONAL - MEJORADA -->
        <div class="table-section animate-fade-in-up">
          <div class="table-header">
            <div class="section-title">Tickets</div>
            <div class="table-actions">
              <div class="table-info">Mostrando <?php echo $totalTickets; ?> tickets</div>
              <a href="?export=excel&<?php echo http_build_query($_GET); ?>" class="export-btn">
                <i class="fas fa-file-excel"></i> Exportar Excel
              </a>
            </div>
          </div>
          <div style="overflow-x: auto;">
            <table class="tickets-table">
              <thead>
                <tr>
                  <th>ID Ticket</th>
                  <th>Solicitante</th>
                  <th>Asunto</th>
                  <th>Prioridad</th>
                  <th>Estatus</th>
                  <th>Fecha</th>
                  <th>Empresa</th>
                  <th>Tiempo Ejecución</th>
                  <th>Atendido Por</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($stmtTickets): ?>
                  <?php while ($ticket = sqlsrv_fetch_array($stmtTickets, SQLSRV_FETCH_ASSOC)): ?>
                    <?php
                    // Formatear fecha correctamente
                    $fecha = 'N/A';
                    if ($ticket['Fecha']) {
                        if ($ticket['Fecha'] instanceof DateTime) {
                            $fecha = $ticket['Fecha']->format('d/m/Y');
                        } else {
                            // Intentar parsear si es string
                            $dateObj = date_create_from_format('d/m/Y', $ticket['Fecha']);
                            if ($dateObj) {
                                $fecha = $dateObj->format('d/m/Y');
                            } else {
                                $fecha = $ticket['Fecha'];
                            }
                        }
                    }
                    
                    $estatusVal = $ticket['Estatus'] ?? 'Pendiente';
                    $prioridadVal = $ticket['Prioridad'] ?? 'No especificada';
                    
                    // USAR EL TIEMPO CALCULADO REAL
                    $tiempoEjec = $ticket['Tiempo_Ejec_Real'] ?? $ticket['Tiempo_Ejec'] ?? 'N/A';
                    $atendidoPor = $ticket['PA'] ?? 'Sin asignar';
                    $asunto = $ticket['Asunto'] ?? 'Sin asunto';
                    
                    // Formatear tiempo de ejecución de manera inteligente
                    $tiempoEjecFormateado = 'N/A';
                    $tiempoClass = '';
                    
                    if ($tiempoEjec !== 'N/A' && $tiempoEjec !== null && $tiempoEjec !== '') {
                        if (is_numeric($tiempoEjec)) {
                            $horas = floatval($tiempoEjec);
                            
                            // Formatear según la duración
                            if ($horas < 1) {
                                $minutos = round($horas * 60);
                                $tiempoEjecFormateado = $minutos . ' min';
                            } else if ($horas < 24) {
                                $tiempoEjecFormateado = number_format($horas, 1) . ' hrs';
                            } else {
                                $dias = floor($horas / 24);
                                $horas_restantes = $horas % 24;
                                $tiempoEjecFormateado = $dias . 'd ' . number_format($horas_restantes, 0) . 'h';
                            }
                            
                            // Asignar clase según el tiempo de respuesta
                            if ($horas <= 4) {
                                $tiempoClass = 'tiempo-rapido';
                            } else if ($horas <= 24) {
                                $tiempoClass = 'tiempo-moderado';
                            } else {
                                $tiempoClass = 'tiempo-lento';
                            }
                        } else {
                            $tiempoEjecFormateado = $tiempoEjec;
                        }
                    }
                    
                    $estatusClass = 'pendiente';
                    $prioridadClass = 'priority-media';
                    
                    if ($estatusVal === 'Atendido') {
                        $estatusClass = 'atendido';
                    } elseif ($estatusVal === 'En Proceso') {
                        $estatusClass = 'en-proceso';
                    }

                    if ($prioridadVal === 'Alta') {
                        $prioridadClass = 'priority-alta';
                    } elseif ($prioridadVal === 'Media') {
                        $prioridadClass = 'priority-media';
                    } elseif ($prioridadVal === 'Baja') {
                        $prioridadClass = 'priority-baja';
                    }
                    ?>
                    <tr>
                      <td>#<?php echo htmlspecialchars($ticket['Id_Ticket'] ?? 'N/A'); ?></td>
                      <td><?php echo htmlspecialchars($ticket['Nombre'] ?? 'N/A'); ?></td>
                      <td><?php echo htmlspecialchars($asunto); ?></td>
                      <td>
                        <span class="status-badge <?php echo $prioridadClass; ?>">
                          <i class="fas fa-flag"></i> <?php echo htmlspecialchars($prioridadVal); ?>
                        </span>
                      </td>
                      <td>
                        <span class="status-badge status-<?php echo $estatusClass; ?>">
                          <i class="fas fa-circle"></i> <?php echo htmlspecialchars($estatusVal); ?>
                        </span>
                      </td>
                      <td><?php echo $fecha; ?></td>
                      <td><?php echo htmlspecialchars($ticket['Empresa'] ?? 'N/A'); ?></td>
                      <td>
                        <?php if ($tiempoEjecFormateado !== 'N/A'): ?>
                          <span class="status-badge <?php echo $tiempoClass; ?>">
                            <i class="fas fa-clock"></i> <?php echo $tiempoEjecFormateado; ?>
                          </span>
                        <?php else: ?>
                          <span class="status-badge" style="background: #6b7280; color: white;">
                            <i class="fas fa-clock"></i> Sin dato
                          </span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($atendidoPor); ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
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
    // Sistema de verificación de sesión en tiempo real - VERSIÓN SIMPLIFICADA
    let lastVerificationTime = Date.now();
    let sessionCheckInterval;
    
    function verificarSesionEnTiempoReal() {
        const now = Date.now();
        const timeDiff = now - lastVerificationTime;
        
        // Verificar cada 30 segundos (menos intrusivo)
        if (timeDiff > 30000) {
            lastVerificationTime = now;
            
            // Hacer una petición al servidor para verificar la sesión
            fetch(window.location.href, {
                method: 'HEAD',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                credentials: 'same-origin'
            }).then(response => {
                if (response.status === 302 || response.redirected) {
                    // Sesión expirada, redirigir al login
                    clearInterval(sessionCheckInterval);
                    window.location.href = 'Loginti.php?error=session_expired';
                }
            }).catch(error => {
                console.log('Error verificando sesión:', error);
            });
        }
    }
    
    <?php if ($tienePermisosDashboard): ?>
    // Datos para gráficas ECharts desde PHP
    <?php if (!empty($estatusData)): ?>
    const statusData = {
        categories: <?php echo json_encode(array_keys($estatusData)); ?>,
        values: <?php echo json_encode(array_values($estatusData)); ?>,
        colors: ['#10b981', '#f59e0b', '#3b82f6', '#6b7280']
    };
    <?php endif; ?>

    <?php if (!empty($prioridadData)): ?>
    const priorityData = {
        categories: <?php echo json_encode(array_keys($prioridadData)); ?>,
        values: <?php echo json_encode(array_values($prioridadData)); ?>,
        colors: ['#ef4444', '#f59e0b', '#3b82f6']
    };
    <?php endif; ?>

    <?php if (!empty($empresaData)): ?>
    const empresaData = {
        categories: <?php echo json_encode(array_keys($empresaData)); ?>,
        values: <?php echo json_encode(array_values($empresaData)); ?>,
        colors: ['#3b82f6', '#8b5cf6', '#06b6d4', '#14b8a6', '#f59e0b', '#ef4444', '#10b981', '#f97316', '#8b5cf6', '#06b6d4', '#14b8a6', '#f59e0b', '#ef4444', '#10b981', '#3b82f6']
    };
    <?php endif; ?>

    <?php if (!empty($atendidoPorData)): ?>
    const atendidoPorData = {
        categories: <?php echo json_encode(array_keys($atendidoPorData)); ?>,
        values: <?php echo json_encode(array_values($atendidoPorData)); ?>,
        colors: ['#3b82f6', '#8b5cf6', '#06b6d4', '#14b8a6', '#f59e0b']
    };
    <?php endif; ?>

    <?php if (!empty($tiempoEjecucionData)): ?>
    const tiempoEjecucionData = {
        categories: <?php echo json_encode(array_keys($tiempoEjecucionData)); ?>,
        values: <?php echo json_encode(array_values($tiempoEjecucionData)); ?>,
        colors: ['#10b981', '#f59e0b', '#ef4444', '#6b7280', '#8b5cf6']
    };
    <?php endif; ?>

    const monthlyData = {
        categories: <?php echo json_encode($mensualLabels); ?>,
        values: <?php echo json_encode($mensualValues); ?>
    };
    
    // Calcular totales para porcentajes
    const statusTotal = statusData.values.reduce((a, b) => a + b, 0);
    const priorityTotal = priorityData.values.reduce((a, b) => a + b, 0);
    const empresaTotal = empresaData.values.reduce((a, b) => a + b, 0);
    const atendidoPorTotal = atendidoPorData.values.reduce((a, b) => a + b, 0);
    const tiempoEjecucionTotal = tiempoEjecucionData.values.reduce((a, b) => a + b, 0);
    <?php endif; ?>

    // Crear efecto de gotas de agua en el fondo
    function createWaterDrops() {
      const waterDropsContainer = document.getElementById('waterDrops');
      const dropsCount = 6;
      
      for (let i = 0; i < dropsCount; i++) {
        const drop = document.createElement('div');
        drop.classList.add('water-drop');
        
        // Posición aleatoria
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        
        // Tamaño aleatorio
        const size = Math.random() * 100 + 60;
        
        // Retraso aleatorio
        const delay = Math.random() * 8;
        
        drop.style.left = `${posX}%`;
        drop.style.top = `${posY}%`;
        drop.style.width = `${size}px`;
        drop.style.height = `${size}px`;
        drop.style.animationDelay = `${delay}s`;
        
        waterDropsContainer.appendChild(drop);
      }
    }

    // Toggle del sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const dashboardHeader = document.querySelector('.dashboard-header');
    const dashboardContainer = document.querySelector('.dashboard-container');
    const moduleContainer = document.getElementById('moduleContainer');

    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('hidden');
      dashboardHeader.classList.toggle('full-width');
      dashboardContainer.classList.toggle('full-width');
      
      // Ajustar el módulo cuando se toggle el sidebar
      if (sidebar.classList.contains('hidden')) {
        moduleContainer.style.left = 'var(--spacing-lg)';
      } else {
        moduleContainer.style.left = '260px';
      }
      
      // Cambiar icono del botón
      const icon = this.querySelector('i');
      if (sidebar.classList.contains('hidden')) {
        icon.className = 'fas fa-bars';
      } else {
        icon.className = 'fas fa-times';
      }
    });

    // Toggle del tema oscuro/claro
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    themeToggle.addEventListener('click', function() {
      body.classList.toggle('dark-mode');
      
      const icon = this.querySelector('i');
      if (body.classList.contains('dark-mode')) {
        icon.className = 'fas fa-sun';
        this.innerHTML = '<i class="fas fa-sun"></i> MODO CLARO';
      } else {
        icon.className = 'fas fa-moon';
        this.innerHTML = '<i class="fas fa-moon"></i> MODO OSCURO';
      }
      
      // Actualizar gráficas cuando cambie el tema
      <?php if ($tienePermisosDashboard): ?>
      updateCharts();
      <?php endif; ?>
    });

    // Custom Alert para cerrar sesión
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutAlert = document.getElementById('logoutAlert');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');

    logoutBtn.addEventListener('click', function() {
      logoutAlert.classList.add('active');
    });

    cancelLogout.addEventListener('click', function() {
      logoutAlert.classList.remove('active');
    });

    confirmLogout.addEventListener('click', function() {
      // Crear formulario temporal para cerrar sesión
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';
      
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'logout';
      input.value = 'true';
      form.appendChild(input);
      
      document.body.appendChild(form);
      form.submit();
    });

    // Alert de permisos
    const permissionAlert = document.getElementById('permissionAlert');
    const permissionMessage = document.getElementById('permissionMessage');
    const closePermissionAlert = document.getElementById('closePermissionAlert');

    closePermissionAlert.addEventListener('click', function() {
      permissionAlert.classList.remove('active');
    });

    // Cerrar alerts al hacer clic fuera del contenido
    logoutAlert.addEventListener('click', function(e) {
      if (e.target === logoutAlert) {
        logoutAlert.classList.remove('active');
      }
    });

    permissionAlert.addEventListener('click', function(e) {
      if (e.target === permissionAlert) {
        permissionAlert.classList.remove('active');
      }
    });

    <?php if ($tienePermisosDashboard): ?>
    // Variables para almacenar las instancias de gráficas ECharts
    let statusChart, priorityChart, empresaChart, monthlyChart, atendidoPorChart, tiempoEjecucionChart;

    // Función para obtener colores según el tema
    function getThemeColors() {
      const isDarkMode = body.classList.contains('dark-mode');
      return {
        textColor: isDarkMode ? '#f1f5f9' : '#1e293b',
        gridColor: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
        backgroundColor: isDarkMode ? 'rgba(15, 23, 42, 0.5)' : 'rgba(255, 255, 255, 0.5)'
      };
    }

    // Función para formatear etiquetas con porcentajes
    function formatLabelWithPercentage(name, value, total) {
      const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
      return `${name}\n${value} (${percentage}%)`;
    }

    // Función para inicializar gráficas ECharts con ESPACIO MEJORADO
    function initializeECharts() {
      const themeColors = getThemeColors();

      // Gráfica de Estatus - Donut con porcentajes y etiquetas en el centro - MEJORADO
      <?php if (!empty($estatusData)): ?>
      statusChart = echarts.init(document.getElementById('statusChart'));
      statusChart.setOption({
        tooltip: {
          trigger: 'item',
          formatter: function(params) {
            const percentage = statusTotal > 0 ? ((params.value / statusTotal) * 100).toFixed(1) : 0;
            return `${params.name}<br>${params.value} tickets (${percentage}%)`;
          }
        },
        legend: {
          orient: 'horizontal',
          bottom: 10,
          textStyle: {
            color: themeColors.textColor,
            fontSize: 11
          },
          itemWidth: 10,
          itemHeight: 10,
          itemGap: 6,
          type: 'scroll'
        },
        series: [{
          name: 'Estatus',
          type: 'pie',
          radius: ['40%', '70%'],
          center: ['50%', '45%'],
          avoidLabelOverlap: true,
          itemStyle: {
            borderRadius: 8,
            borderColor: themeColors.backgroundColor,
            borderWidth: 2
          },
          label: {
            show: true,
            position: 'outside',
            formatter: function(params) {
              const percentage = statusTotal > 0 ? ((params.value / statusTotal) * 100).toFixed(1) : 0;
              return `${params.name}\n${params.value} (${percentage}%)`;
            },
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 10,
            lineHeight: 14
          },
          emphasis: {
            label: {
              show: true,
              fontSize: 12,
              fontWeight: 'bold',
              color: themeColors.textColor
            }
          },
          labelLine: {
            show: true,
            length: 15,
            length2: 10,
            smooth: true,
            lineStyle: {
              width: 1.5
            }
          },
          data: statusData.categories.map((category, index) => ({
            value: statusData.values[index],
            name: category,
            itemStyle: {
              color: statusData.colors[index] || '#3b82f6'
            }
          }))
        }]
      });
      <?php endif; ?>

      // Gráfica de Prioridad - Barra con porcentajes en etiquetas - MEJORADO
      <?php if (!empty($prioridadData)): ?>
      priorityChart = echarts.init(document.getElementById('priorityChart'));
      priorityChart.setOption({
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          },
          formatter: function(params) {
            const percentage = priorityTotal > 0 ? ((params[0].value / priorityTotal) * 100).toFixed(1) : 0;
            return `${params[0].name}<br>${params[0].value} tickets (${percentage}%)`;
          }
        },
        grid: {
          left: '8%',
          right: '8%',
          bottom: '15%',
          top: '10%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          data: priorityData.categories,
          axisLabel: {
            color: themeColors.textColor,
            fontSize: 11,
            rotate: 0
          },
          axisLine: {
            lineStyle: {
              color: themeColors.gridColor
            }
          }
        },
        yAxis: {
          type: 'value',
          axisLabel: {
            color: themeColors.textColor,
            fontSize: 11
          },
          axisLine: {
            show: false
          },
          splitLine: {
            lineStyle: {
              color: themeColors.gridColor,
              opacity: 0.3
            }
          }
        },
        series: [{
          name: 'Tickets',
          type: 'bar',
          barWidth: '45%',
          data: priorityData.categories.map((category, index) => ({
            value: priorityData.values[index],
            name: category,
            itemStyle: {
              color: priorityData.colors[index] || '#3b82f6'
            }
          })),
          itemStyle: {
            borderRadius: [4, 4, 0, 0],
            shadowColor: 'rgba(0, 0, 0, 0.2)',
            shadowBlur: 4,
            shadowOffsetY: 2
          },
          label: {
            show: true,
            position: 'top',
            formatter: function(params) {
              const percentage = priorityTotal > 0 ? ((params.value / priorityTotal) * 100).toFixed(1) : 0;
              return `${params.value}\n(${percentage}%)`;
            },
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 10,
            lineHeight: 14
          }
        }]
      });
      <?php endif; ?>

      // Gráfica de Empresas (Top 15) - Pie con porcentajes - MEJORADO
      <?php if (!empty($empresaData)): ?>
      empresaChart = echarts.init(document.getElementById('empresaChart'));
      empresaChart.setOption({
        tooltip: {
          trigger: 'item',
          formatter: function(params) {
            const percentage = empresaTotal > 0 ? ((params.value / empresaTotal) * 100).toFixed(1) : 0;
            return `${params.name}<br>${params.value} tickets (${percentage}%)`;
          }
        },
        legend: {
          orient: 'vertical',
          right: 5,
          top: 'middle',
          textStyle: {
            color: themeColors.textColor,
            fontSize: 10
          },
          type: 'scroll',
          pageTextStyle: {
            color: themeColors.textColor
          },
          pageIconColor: themeColors.textColor,
          pageIconInactiveColor: themeColors.gridColor,
          formatter: function(name) {
            const index = empresaData.categories.indexOf(name);
            const value = empresaData.values[index] || 0;
            const percentage = empresaTotal > 0 ? ((value / empresaTotal) * 100).toFixed(1) : 0;
            return `${name.slice(0, 15)}${name.length > 15 ? '...' : ''} (${percentage}%)`;
          },
          itemWidth: 8,
          itemHeight: 8,
          itemGap: 5
        },
        series: [{
          name: 'Empresa',
          type: 'pie',
          radius: ['35%', '60%'],
          center: ['40%', '50%'],
          avoidLabelOverlap: true,
          itemStyle: {
            borderRadius: 6,
            borderColor: themeColors.backgroundColor,
            borderWidth: 1.5
          },
          label: {
            show: true,
            position: 'outside',
            formatter: function(params) {
              const percentage = empresaTotal > 0 ? ((params.value / empresaTotal) * 100).toFixed(1) : 0;
              // Abreviar nombres largos
              const name = params.name.length > 12 ? params.name.slice(0, 12) + '...' : params.name;
              return `${name}\n${params.value} (${percentage}%)`;
            },
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 9,
            lineHeight: 12,
            minMargin: 5
          },
          emphasis: {
            label: {
              show: true,
              fontSize: 11,
              fontWeight: 'bold',
              color: themeColors.textColor
            }
          },
          labelLine: {
            show: true,
            length: 12,
            length2: 8,
            smooth: true,
            lineStyle: {
              width: 1.2
            }
          },
          data: empresaData.categories.map((category, index) => ({
            value: empresaData.values[index],
            name: category,
            itemStyle: {
              color: empresaData.colors[index] || '#3b82f6'
            }
          }))
        }]
      });
      <?php endif; ?>

      // Gráfica de Tiempo de Ejecución por Rango con porcentajes - MEJORADO
      <?php if (!empty($tiempoEjecucionData)): ?>
      tiempoEjecucionChart = echarts.init(document.getElementById('tiempoEjecucionChart'));
      tiempoEjecucionChart.setOption({
        tooltip: {
          trigger: 'item',
          formatter: function(params) {
            const percentage = tiempoEjecucionTotal > 0 ? ((params.value / tiempoEjecucionTotal) * 100).toFixed(1) : 0;
            return `${params.name}<br>${params.value} tickets (${percentage}%)`;
          }
        },
        legend: {
          orient: 'horizontal',
          bottom: 10,
          textStyle: {
            color: themeColors.textColor,
            fontSize: 10
          },
          itemWidth: 9,
          itemHeight: 9,
          itemGap: 6,
          type: 'scroll'
        },
        series: [{
          name: 'Tiempo de Ejecución',
          type: 'pie',
          radius: ['40%', '70%'],
          center: ['50%', '45%'],
          avoidLabelOverlap: true,
          itemStyle: {
            borderRadius: 6,
            borderColor: themeColors.backgroundColor,
            borderWidth: 1.5
          },
          label: {
            show: true,
            position: 'outside',
            formatter: function(params) {
              const percentage = tiempoEjecucionTotal > 0 ? ((params.value / tiempoEjecucionTotal) * 100).toFixed(1) : 0;
              return `${params.name}\n${params.value} (${percentage}%)`;
            },
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 9,
            lineHeight: 12
          },
          emphasis: {
            label: {
              show: true,
              fontSize: 11,
              fontWeight: 'bold',
              color: themeColors.textColor
            }
          },
          labelLine: {
            show: true,
            length: 15,
            length2: 8,
            smooth: true,
            lineStyle: {
              width: 1.2
            }
          },
          data: tiempoEjecucionData.categories.map((category, index) => ({
            value: tiempoEjecucionData.values[index],
            name: category,
            itemStyle: {
              color: tiempoEjecucionData.colors[index] || '#3b82f6'
            }
          }))
        }]
      });
      <?php endif; ?>

      // Gráfica de Quién Atendió con porcentajes - MEJORADO
      <?php if (!empty($atendidoPorData)): ?>
      atendidoPorChart = echarts.init(document.getElementById('atendidoPorChart'));
      atendidoPorChart.setOption({
        tooltip: {
          trigger: 'item',
          formatter: function(params) {
            const percentage = atendidoPorTotal > 0 ? ((params.value / atendidoPorTotal) * 100).toFixed(1) : 0;
            return `${params.name}<br>${params.value} tickets (${percentage}%)`;
          }
        },
        legend: {
          orient: 'vertical',
          right: 5,
          top: 'middle',
          textStyle: {
            color: themeColors.textColor,
            fontSize: 10
          },
          type: 'scroll',
          pageTextStyle: {
            color: themeColors.textColor
          },
          pageIconColor: themeColors.textColor,
          pageIconInactiveColor: themeColors.gridColor,
          formatter: function(name) {
            const index = atendidoPorData.categories.indexOf(name);
            const value = atendidoPorData.values[index] || 0;
            const percentage = atendidoPorTotal > 0 ? ((value / atendidoPorTotal) * 100).toFixed(1) : 0;
            return `${name} (${percentage}%)`;
          },
          itemWidth: 8,
          itemHeight: 8,
          itemGap: 5
        },
        series: [{
          name: 'Atendido Por',
          type: 'pie',
          radius: ['35%', '65%'],
          center: ['40%', '50%'],
          roseType: 'radius',
          avoidLabelOverlap: true,
          itemStyle: {
            borderRadius: 6,
            borderColor: themeColors.backgroundColor,
            borderWidth: 1.5
          },
          label: {
            show: true,
            position: 'outside',
            formatter: function(params) {
              const percentage = atendidoPorTotal > 0 ? ((params.value / atendidoPorTotal) * 100).toFixed(1) : 0;
              return `${params.name}\n${params.value} (${percentage}%)`;
            },
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 9,
            lineHeight: 12
          },
          emphasis: {
            label: {
              show: true,
              fontSize: 11,
              fontWeight: 'bold',
              color: themeColors.textColor
            }
          },
          labelLine: {
            show: true,
            length: 12,
            length2: 8,
            smooth: true,
            lineStyle: {
              width: 1.2
            }
          },
          data: atendidoPorData.categories.map((category, index) => ({
            value: atendidoPorData.values[index],
            name: category,
            itemStyle: {
              color: atendidoPorData.colors[index] || '#3b82f6'
            }
          }))
        }]
      });
      <?php endif; ?>

      // Gráfica Mensual - Línea con etiquetas - MEJORADO
      monthlyChart = echarts.init(document.getElementById('monthlyChart'));
      monthlyChart.setOption({
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          },
          formatter: function(params) {
            return `${params[0].name}<br>${params[0].value} tickets`;
          }
        },
        grid: {
          left: '8%',
          right: '8%',
          bottom: '12%',
          top: '10%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          data: monthlyData.categories,
          axisLabel: {
            color: themeColors.textColor,
            rotate: 0,
            fontSize: 10,
            interval: 0
          },
          axisLine: {
            lineStyle: {
              color: themeColors.gridColor
            }
          }
        },
        yAxis: {
          type: 'value',
          axisLabel: {
            color: themeColors.textColor,
            fontSize: 10
          },
          axisLine: {
            show: false
          },
          splitLine: {
            lineStyle: {
              color: themeColors.gridColor,
              opacity: 0.3
            }
          }
        },
        series: [{
          name: 'Tickets',
          type: 'line',
          smooth: true,
          lineStyle: {
            width: 2.5,
            color: '#3b82f6'
          },
          areaStyle: {
            color: {
              type: 'linear',
              x: 0,
              y: 0,
              x2: 0,
              y2: 1,
              colorStops: [{
                offset: 0,
                color: 'rgba(59, 130, 246, 0.3)'
              }, {
                offset: 1,
                color: 'rgba(59, 130, 246, 0.1)'
              }]
            }
          },
          symbol: 'circle',
          symbolSize: 7,
          itemStyle: {
            color: '#3b82f6',
            borderColor: '#ffffff',
            borderWidth: 1.5
          },
          data: monthlyData.values,
          label: {
            show: true,
            position: 'top',
            formatter: '{c}',
            color: themeColors.textColor,
            fontWeight: 'bold',
            fontSize: 10,
            backgroundColor: 'rgba(255, 255, 255, 0.8)',
            padding: [2, 4],
            borderRadius: 3
          }
        }]
      });
    }

    // Función para actualizar gráficas cuando cambie el tema
    function updateCharts() {
      if (statusChart) statusChart.dispose();
      if (priorityChart) priorityChart.dispose();
      if (empresaChart) empresaChart.dispose();
      if (monthlyChart) monthlyChart.dispose();
      if (atendidoPorChart) atendidoPorChart.dispose();
      if (tiempoEjecucionChart) tiempoEjecucionChart.dispose();
      
      initializeECharts();
    }

    // Función para redimensionar gráficas cuando cambie el tamaño de la ventana
    function resizeCharts() {
      if (statusChart) statusChart.resize();
      if (priorityChart) priorityChart.resize();
      if (empresaChart) empresaChart.resize();
      if (monthlyChart) monthlyChart.resize();
      if (atendidoPorChart) atendidoPorChart.resize();
      if (tiempoEjecucionChart) tiempoEjecucionChart.resize();
    }
    <?php endif; ?>

    // Navegación entre páginas con control de permisos
    document.addEventListener('DOMContentLoaded', function() {
      // Iniciar verificación de sesión (menos intrusiva)
      sessionCheckInterval = setInterval(verificarSesionEnTiempoReal, 30000);
      
      // Crear efecto de gotas de agua
      createWaterDrops();
      
      <?php if ($tienePermisosDashboard): ?>
      // Inicializar gráficas ECharts solo si tiene permisos
      setTimeout(() => {
        initializeECharts();
      }, 100);
      
      // Redimensionar gráficas cuando cambie el tamaño de la ventana
      window.addEventListener('resize', resizeCharts);
      
      // Botones de expansión para gráficas
      document.querySelectorAll('.chart-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const chartId = this.getAttribute('data-chart');
          const chartElement = document.getElementById(chartId);
          
          if (chartElement) {
            chartElement.style.height = chartElement.style.height === '450px' ? '380px' : '450px';
            resizeCharts();
          }
        });
      });
      <?php endif; ?>
      
      const navLinks = document.querySelectorAll('.nav-link');
      const moduleContainer = document.getElementById('moduleContainer');
      const moduleIframe = document.getElementById('moduleIframe');
      const dashboardContent = document.getElementById('dashboardContent');
      
      // Configuración de enlaces específicos para cada página
      const pageConfig = {
        'dashboard': {
          showModule: false,
          title: 'Dashboard de Reportes',
          mainTitle: 'Bienvenido, <?php echo htmlspecialchars($nombre); ?>',
          subtitle: 'Dashboard personalizado del sistema de soporte técnico con métricas en tiempo real',
          permissionRequired: true,
          allowedUsers: <?php echo json_encode($usuariosPermitidosDashboard); ?>
        },
        'levantar-ticket': {
          showModule: true,
          url: 'http://desarollo-bacros/TicketBacros/Formtic1.php',
          title: 'Levantar Ticket',
          mainTitle: 'Formulario de Tickets',
          subtitle: 'Complete el formulario para crear un nuevo ticket de soporte'
        },
        'revisar-tickets': {
          showModule: true,
          url: 'http://desarollo-bacros/TicketBacros/RevisarT.php',
          title: 'Revisar Tickets',
          mainTitle: 'Revisión de Tickets',
          subtitle: 'Revise y gestione los tickets asignados'
        },
        'procesar-tickets': {
          showModule: true,
          url: 'http://desarollo-bacros/TicketBacros/TableT1.php',
          title: 'Procesar Tickets',
          mainTitle: 'Procesamiento de Tickets',
          subtitle: 'Procese y actualice el estado de los tickets',
          permissionRequired: true,
          allowedUsers: <?php echo json_encode($usuariosPermitidosProcesar); ?>
        },
        'reportes': {
          showModule: false,
          title: 'Dashboard de Reportes',
          mainTitle: 'Reportes de Tickets',
          subtitle: 'Dashboard con métricas en tiempo real del sistema de tickets',
          permissionRequired: true,
          allowedUsers: <?php echo json_encode($usuariosPermitidosDashboard); ?>
        }
      };
      
      navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Si el enlace está deshabilitado, mostrar alerta de permisos
          if (this.classList.contains('disabled')) {
            const page = this.getAttribute('data-page');
            const config = pageConfig[page];
            
            let message = 'No tienes permisos para acceder a este módulo.';
            if (config && config.permissionRequired) {
              message = `El módulo "${config.title}" está restringido para usuarios autorizados.`;
            }
            
            permissionMessage.textContent = message;
            permissionAlert.classList.add('active');
            return;
          }
          
          // Remover clase active de todos los enlaces
          navLinks.forEach(l => l.classList.remove('active'));
          
          // Agregar clase active al enlace clickeado
          this.classList.add('active');
          
          const page = this.getAttribute('data-page');
          const config = pageConfig[page];
          
          if (!config) {
            console.error('Configuración no encontrada para la página:', page);
            return;
          }
          
          // Verificar permisos para páginas restringidas
          if (config.permissionRequired) {
            const currentUser = '<?php echo $usuarioActual; ?>';
            if (!config.allowedUsers.includes(currentUser)) {
              permissionMessage.textContent = `No tienes permisos para acceder al módulo "${config.title}".`;
              permissionAlert.classList.add('active');
              return;
            }
          }
          
          // Mostrar/ocultar módulo según la configuración
          if (config.showModule && config.url) {
            // Mostrar módulo grande con la URL específica
            moduleContainer.classList.add('active');
            if (dashboardContent) dashboardContent.style.display = 'none';
            moduleIframe.src = config.url;
          } else {
            // Mostrar dashboard normal
            moduleContainer.classList.remove('active');
            if (dashboardContent) dashboardContent.style.display = 'block';
            moduleIframe.src = '';
          }
          
          // Actualizar títulos
          document.querySelector('.logo-subtitle').textContent = config.title;
          <?php if ($tienePermisosDashboard): ?>
          document.querySelector('.dashboard-title').textContent = config.mainTitle;
          document.querySelector('.dashboard-subtitle').textContent = config.subtitle;
          <?php endif; ?>
        });
      });
      
      // Auto-submit form cuando cambien algunos filtros
      document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', function() {
          this.form.submit();
        });
      });
    });

    // Prevenir que la página se cargue desde cache (posible multi-ventana)
    window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
        // La página se cargó desde cache, podría ser una nueva ventana
        // Forzar recarga para verificar sesión
        window.location.reload();
      }
    });

    // Prevenir el uso del botón de retroceso
    window.addEventListener('beforeunload', function() {
      // Limpiar el intervalo de verificación
      if (sessionCheckInterval) {
        clearInterval(sessionCheckInterval);
      }
    });
  </script>
</body>
</html>

<?php
// Cerrar conexiones
if (isset($stmtTickets)) sqlsrv_free_stmt($stmtTickets);
if (isset($conn)) sqlsrv_close($conn);
?>