<?php
/**
 * @file actualizar_ticket.php
 * @brief API para actualización de tickets de Servicios Generales (TicketsSG).
 *
 * @description
 * Endpoint de API que maneja actualizaciones de tickets del módulo de
 * Servicios Generales. Recibe datos vía POST, llama a la API REST de Rust
 * (PUT /api/TicketBacros/tickets-sg/:id) y retorna el resultado.
 *
 * Este archivo es diferente a update_ticket.php que trabaja con la tabla T3.
 * actualizar_ticket.php trabaja específicamente con tickets de Servicios Generales.
 *
 * Operaciones:
 * - Asignación de ticket a personal (asignadoA)
 * - Cambio de estatus del ticket
 * - Soporte para subida de archivos (si existe)
 *
 * @api PUT {apiUrl}/api/TicketBacros/tickets-sg/:id
 *
 * @module API de Servicios Generales
 * @access API (POST request)
 *
 * @dependencies
 * - config.php: PDF_API_URL env var
 * - PHP curl extension
 *
 * @inputs
 * - POST['ticketId']:    ID del ticket a actualizar (requerido)
 * - POST['asignadoA']:   Persona asignada (requerido)
 * - POST['nuevoEstatus']: Nuevo estado del ticket (requerido)
 *                         Valores válidos en la API: Pendiente, En proceso, Resuelto, Cancelado
 * - FILES: Archivos adjuntos (opcional, se procesa localmente antes de llamar la API)
 *
 * @outputs
 * - JSON: {"success": true, "message": "..."}
 * - O {"error": "descripción del error"}
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0 (migrado a API REST - sin sqlsrv)
 * @since 2024
 * @updated 2026-04-23
 */

require_once __DIR__ . '/config.php';

// URL base de la API Rust
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

// Recibe datos POST con validación
$id      = trim($_POST['ticketId']     ?? '');
$asignado = trim($_POST['asignadoA']   ?? '');
$estatus  = trim($_POST['nuevoEstatus'] ?? '');

if (!$id || !$asignado || !$estatus) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Faltan datos requeridos']);
    exit;
}

// ------------------------------------------------------------------
// Procesar archivo de evidencia si existe (lógica local, sin BD)
// El archivo se guarda localmente; la ruta se podría enviar a la API
// en una llamada separada si el endpoint lo soportara.
// ------------------------------------------------------------------
if (isset($_FILES['evidenciaFoto']) && $_FILES['evidenciaFoto']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['evidenciaFoto']['tmp_name'];
    $fileName    = $_FILES['evidenciaFoto']['name'];
    $fileSize    = $_FILES['evidenciaFoto']['size'];
    $fileType    = $_FILES['evidenciaFoto']['type'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize      = 5 * 1024 * 1024; // 5 MB

    if (!in_array($fileType, $allowedTypes) || $fileSize > $maxSize) {
        http_response_code(400);
        echo "Tipo o tamaño de archivo no permitido.";
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = 'ticket_' . $id . '_' . time() . '.' . $fileExt;
    $destPath    = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmpPath, $destPath)) {
        http_response_code(500);
        echo "Error al mover archivo.";
        exit;
    }

    // Archivo subido correctamente; continúa con la actualización del ticket
    // (el enlace de evidencia se almacenaría via un endpoint dedicado si existiera)
}

// ------------------------------------------------------------------
// Llamar a la API Rust: PUT /api/TicketBacros/tickets-sg/:id
//
// La API acepta JSON con: ticketId, asignadoA, nuevoEstatus
// Estatus válidos en Rust: ["Pendiente", "En proceso", "Resuelto", "Cancelado"]
//
// Nota: el original PHP usaba "En proceso" (minúscula), que coincide con la API.
// ------------------------------------------------------------------
$putUrl  = $apiUrl . '/api/TicketBacros/tickets-sg/' . urlencode($id);
$putBody = json_encode([
    'ticketId'     => $id,
    'asignadoA'    => $asignado,
    'nuevoEstatus' => $estatus,
]);

$ch = curl_init($putUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_POSTFIELDS     => $putBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => "Error de conexión con la API: $curlError"]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    $apiMsg = $data['error'] ?? $data['message'] ?? $response;
    http_response_code($httpCode ?: 500);
    echo json_encode(['error' => "Error al actualizar el ticket (HTTP $httpCode): $apiMsg"]);
    exit;
}

// Propagar la respuesta de la API tal cual (mantiene compatibilidad con el frontend)
echo json_encode(['success' => true, 'message' => $data['message'] ?? 'Ticket actualizado']);
