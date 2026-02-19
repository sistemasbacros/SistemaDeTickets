<?php
/**
 * @file fetch_tickets.php
 * @brief API REST para obtener listado de tickets en formato JSON (DataTables compatible).
 *
 * @description
 * Endpoint de API que retorna el listado de tickets de TI en formato JSON,
 * optimizado para su consumo por DataTables. Filtra automáticamente los tickets
 * del mes actual más los pendientes/en proceso de meses anteriores, permitiendo
 * una vista enfocada en tickets activos sin saturar la interfaz.
 *
 * Este archivo provee datos para:
 * - TableT1.php (tabla principal de tickets)
 * - IniSoport.php (dashboard con métricas)
 * - Cualquier componente que requiera listado de tickets vía AJAX
 *
 * Características de la consulta:
 * - Tickets del mes y año actual
 * - Tickets pendientes/en proceso de cualquier fecha
 * - Ordenamiento descendente por fecha
 * - Conversión de fechas a formato dd/mm/yyyy
 * - Conversión de horas a formato HH:MM:SS
 * - Incluye metadatos de imágenes adjuntas
 *
 * @module API de Tickets
 * @access API Pública (sin autenticación - considerar agregar)
 *
 * @dependencies
 * - PHP: sqlsrv extension, json_encode
 * - SQL Server: Funciones CONVERT, TRY_CONVERT, GETDATE
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Usuario: Larome03
 * - Tabla: dbo.T3 (tickets TI)
 * - Columnas retornadas:
 *   - Nombre: Solicitante
 *   - Correo: Email del solicitante
 *   - Prioridad: Alta/Media/Baja/Crítica
 *   - Empresa: Empresa relacionada
 *   - Asunto: Título del ticket
 *   - Mensaje: Descripción detallada
 *   - Adjuntos: Archivos adjuntos
 *   - Fecha/Hora: Fecha y hora de creación
 *   - Id_Ticket: Identificador único
 *   - Estatus: Estado actual del ticket
 *   - PA: Persona asignada/responsable
 *   - FechaEnProceso/HoraEnProceso: Timestamp de cambio a "En proceso"
 *   - FechaPausa/HoraPausa: Timestamp de pausa
 *   - FechaTerminado/HoraTerminado: Timestamp de resolución
 *   - FechaCancelado/HoraCancelado: Timestamp de cancelación
 *   - imagen_url, imagen_nombre, imagen_tipo, imagen_size: Metadatos de imagen
 *
 * @query_filters
 * - Filtro temporal:
 *   (Año actual AND Mes actual) OR (Año actual AND Estatus IN ('Pendiente', 'En proceso'))
 * - Propósito: Mostrar tickets actuales + tickets activos de meses anteriores
 *
 * @inputs
 * - Ninguno (no requiere parámetros)
 * - Futuras mejoras: Parámetros de filtrado (fecha, estado, usuario)
 *
 * @outputs
 * - Content-Type: application/json
 * - Estructura DataTables:
 *   {
 *     "data": [
 *       {
 *         "Nombre ": "Juan Pérez",
 *         "Correo": "juan@empresa.com",
 *         "Prioridad": "Alta",
 *         "Empresa": "BacroCorp",
 *         "Asunto": "Problema con impresora",
 *         "Mensaje": "Descripción...",
 *         "Adjuntos": "archivo.pdf",
 *         "Fecha": "15/01/2025",
 *         "Hora": "14:30:00",
 *         "Id_Ticket": "TIC-2025-001",
 *         "Estatus": "Pendiente",
 *         "PA": "Luis Vargas",
 *         ...timestamps de estados...
 *         "imagen_url": "uploads/img.jpg",
 *         "imagen_nombre": "evidencia.jpg",
 *         "imagen_tipo": "image/jpeg",
 *         "imagen_size": "1024"
 *       },
 *       ...más tickets...
 *     ]
 *   }
 * - Error de conexión: {"data": []}
 *
 * @security
 * - ADVERTENCIA: No tiene autenticación (agregar sesión recomendado)
 * - Credenciales de BD hardcoded (migrar a .env)
 * - No expone errores de SQL al cliente
 * - Content-Type establecido como JSON
 *
 * @date_formats
 * - Fechas: dd/mm/yyyy (formato 103 de SQL Server)
 * - Horas: HH:MM:SS (formato 108 de SQL Server)
 * - TRY_CONVERT: Manejo seguro de fechas inválidas (retorna NULL)
 *
 * @performance
 * - Sin paginación (todos los registros en una respuesta)
 * - Para optimizar: Implementar paginación server-side de DataTables
 * - Considerar caché para consultas frecuentes
 *
 * @todo
 * - Agregar autenticación de sesión
 * - Implementar paginación server-side
 * - Agregar parámetros de filtrado
 * - Migrar credenciales a variables de entorno
 * - Añadir rate limiting
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.8
 * @since 2024
 * @updated 2025-01-15
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

$serverName = $DB_HOST;
$connectionInfo = array(
  "Database" => $DB_DATABASE,
  "UID" => $DB_USERNAME,
  "PWD" => $DB_PASSWORD,
  "CharacterSet" => "UTF-8"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    echo json_encode(['data' => []]);
    exit;
}

$sql = "SELECT 
    [Nombre ], [Correo], [Prioridad], [Empresa], [Asunto], [Mensaje], [Adjuntos], 
    CONVERT(varchar, TRY_CONVERT(date, fecha, 103), 103) AS Fecha,  
    CONVERT(varchar(8), Hora, 108) AS Hora,
    [Id_Ticket], [Estatus], [PA], 
    CONVERT(varchar, TRY_CONVERT(date, FechaEnProceso, 103), 103) AS FechaEnProceso,
    CONVERT(varchar(8), HoraEnProceso, 108) AS HoraEnProceso,
    CONVERT(varchar, TRY_CONVERT(date, FechaPausa, 103), 103) AS FechaPausa,
    CONVERT(varchar(8), HoraPausa, 108) AS HoraPausa,
    CONVERT(varchar, TRY_CONVERT(date, FechaTerminado, 103), 103) AS FechaTerminado,
    CONVERT(varchar(8), HoraTerminado, 108) AS HoraTerminado,
    CONVERT(varchar, TRY_CONVERT(date, FechaCancelado, 103), 103) AS FechaCancelado,
    CONVERT(varchar(8), HoraCancelado, 108) AS HoraCancelado,
    [imagen_url],
    [imagen_nombre],
    [imagen_tipo],
    [imagen_size]
FROM [dbo].[T3] 
WHERE (YEAR(TRY_CONVERT(date, fecha, 103)) = YEAR(GETDATE()) 
  AND MONTH(TRY_CONVERT(date, fecha, 103)) = MONTH(GETDATE()) ) or  (YEAR(TRY_CONVERT(date, fecha, 103)) = YEAR(GETDATE())  and estatus in ('Pendiente','En proceso '))
ORDER BY TRY_CONVERT(date, fecha, 103) DESC";

$stmt = sqlsrv_query($conn, $sql);

$dataSet = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Construir la URL completa de la imagen
    $imagen_url = $row['imagen_url'] ?? '';
    $imagen_nombre = $row['imagen_nombre'] ?? '';
    $imagen_completa = '';
    
    if (!empty($imagen_url)) {
        // Si la URL ya es completa, usarla; si no, construirla
        if (strpos($imagen_url, 'http://') === 0 || strpos($imagen_url, 'https://') === 0) {
            $imagen_completa = $imagen_url;
        } else {
            // Construir URL relativa a la raíz del servidor
            $imagen_completa = ltrim($imagen_url, '.');
        }
    }
    
    $dataSet[] = [
        'Nombre' => trim($row['Nombre ']),
        'Correo' => $row['Correo'],
        'Prioridad' => $row['Prioridad'],
        'Empresa' => $row['Empresa'],
        'Asunto' => $row['Asunto'],
        'Mensaje' => $row['Mensaje'],
        'Adjuntos' => $row['Adjuntos'],
        'Fecha' => $row['Fecha'],
        'Hora' => $row['Hora'],
        'Id_Ticket' => $row['Id_Ticket'],
        'Estatus' => $row['Estatus'],
        'PA' => $row['PA'],
        'FechaEnProceso' => $row['FechaEnProceso'],
        'HoraEnProceso' => $row['HoraEnProceso'],
        'FechaPausa' => $row['FechaPausa'],
        'HoraPausa' => $row['HoraPausa'],
        'FechaTerminado' => $row['FechaTerminado'],
        'HoraTerminado' => $row['HoraTerminado'],
        'FechaCancelado' => $row['FechaCancelado'],
        'HoraCancelado' => $row['HoraCancelado'],
        'Imagen_URL' => $imagen_completa,
        'Imagen_Nombre' => $imagen_nombre,
        'Imagen_Tipo' => $row['imagen_tipo'] ?? '',
        'Imagen_Size' => $row['imagen_size'] ?? ''
    ];
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode(['data' => $dataSet]);
?>