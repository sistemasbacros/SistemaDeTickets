<?php
header('Content-Type: application/json');

$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionInfo = array(
  "Database" => "Ticket",
  "UID" => "Larome03",
  "PWD" => "Larome03",
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
WHERE YEAR(TRY_CONVERT(date, fecha, 103)) = YEAR(GETDATE()) 
  AND MONTH(TRY_CONVERT(date, fecha, 103)) = MONTH(GETDATE())
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
            // Construir URL completa
            $imagen_completa = 'http://desarollo-bacro' . ltrim($imagen_url, '.');
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