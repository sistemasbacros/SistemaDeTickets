<?php
/**
 * @file actualizar_ticket.php
 * @brief API para actualización de tickets de Servicios Generales (TicketsSG).
 *
 * @description
 * Endpoint de API que maneja actualizaciones de tickets del módulo de
 * Servicios Generales. Recibe datos vía POST y actualiza el registro
 * correspondiente en la tabla TicketsSG.
 *
 * Este archivo es diferente a update_ticket.php que trabaja con la tabla T3.
 * actualizar_ticket.php trabaja específicamente con tickets de Servicios Generales.
 *
 * Operaciones:
 * - Asignación de ticket a personal (asignadoA)
 * - Cambio de estatus del ticket
 * - Soporte para subida de archivos (si existe)
 *
 * @module API de Servicios Generales
 * @access API (POST request)
 *
 * @dependencies
 * - PHP: sqlsrv extension, json_encode
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: TicketsSG (UPDATE)
 *
 * @inputs
 * - POST['ticketId']: ID del ticket a actualizar (requerido)
 * - POST['asignadoA']: Persona asignada (requerido)
 * - POST['nuevoEstatus']: Nuevo estado del ticket (requerido)
 * - FILES: Archivos adjuntos (opcional)
 *
 * @outputs
 * - JSON: {"success": true/false, "message": "..."}
 * - O {"error": "descripción del error"}
 *
 * @security
 * - Validación de campos requeridos
 * - Sin autenticación de sesión (añadir recomendado)
 * - Errores de SQL pueden exponerse vía JSON
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.5
 * @since 2024
 */

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
    die(json_encode(['error' => sqlsrv_errors()], JSON_PRETTY_PRINT));
}

// Recibe datos POST con validación
$id = $_POST['ticketId'] ?? '';
$asignado = $_POST['asignadoA'] ?? '';
$estatus = $_POST['nuevoEstatus'] ?? '';

if (!$id || !$asignado || !$estatus) {
    echo json_encode(['error' => 'Faltan datos requeridos']);
    exit;
}



/////////////////////////


// Procesar archivo si existe
    if (isset($_FILES['evidenciaFoto']) && $_FILES['evidenciaFoto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['evidenciaFoto']['tmp_name'];
        $fileName = $_FILES['evidenciaFoto']['name'];
        $fileSize = $_FILES['evidenciaFoto']['size'];
        $fileType = $_FILES['evidenciaFoto']['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            $uploadDir = 'uploads/'; // carpeta para guardar evidencias (asegúrate que existe y tiene permisos)
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $newFileName = 'ticket_' . $ticketId . '_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Guardar $destPath en base de datos asociado al ticket
                // Ejemplo (ajusta según tu tabla y conexión):
                $sqlUpdate = "UPDATE TicketsSG SET ENLACE = ? WHERE Id_Ticket = ?";
                $params = [$destPath, $id];			
              // Ejecutar consulta
          $stmt = sqlsrv_query($conn, $sqlUpdate, $params);
                // Ejecutar actualización con sqlsrv_prepare / sqlsrv_execute
            } else {
                http_response_code(500);
                echo "Error al mover archivo.";
                exit;
            }
        } else {
            http_response_code(400);
            echo "Tipo o tamaño de archivo no permitido.";
            exit;
        }
    }

    echo "OK";
////////////////////////


// Fecha y hora actuales
date_default_timezone_set('America/Mexico_City');
$fechaActual = date('Y-m-d');
$horaActual = date('H:i:s');

// Construcción dinámica de la consulta según el estatus
if ($estatus === 'Resuelto' || $estatus === 'Cancelado') {
    $sql = "UPDATE TicketsSG SET PA = ?, Estatus = ?, Fecha_Termino = ?, Hora_Termino = ? WHERE Id_Ticket = ?";
    $params = [$asignado, $estatus, $fechaActual, $horaActual, $id];
} elseif ($estatus === 'En proceso') {
    $sql = "UPDATE TicketsSG SET PA = ?, Estatus = ?, Fecha_Proceso = ?, Hora_Proceso = ? WHERE Id_Ticket = ?";
    $params = [$asignado, $estatus, $fechaActual, $horaActual, $id];
} else {
    // Limpia campos de cierre si no está cerrado ni en proceso
    $sql = "UPDATE TicketsSG SET PA = ?, Estatus = ?, Fecha_Termino = NULL, Hora_Termino = NULL WHERE Id_Ticket = ?";
    $params = [$asignado, $estatus, $id];
}

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => sqlsrv_errors()], JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(['success' => true]);
?>


