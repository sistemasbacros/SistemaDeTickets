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

require_once __DIR__ . '/auth_check_api.php';
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
if (isset($_FILES['evidenciaFoto'])) {
    if ($_FILES['evidenciaFoto']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Error al recibir archivo (código ' . $_FILES['evidenciaFoto']['error'] . ').']);
        exit;
    }

    $fileTmpPath = $_FILES['evidenciaFoto']['tmp_name'];
    $fileSize    = (int) $_FILES['evidenciaFoto']['size'];
    $maxSize     = 5 * 1024 * 1024; // 5 MB

    if ($fileSize <= 0 || $fileSize > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Tamaño de archivo no permitido (máx 5MB).']);
        exit;
    }

    // Validar contenido real de la imagen (NO confiar en $_FILES[...]['type'])
    $info = @getimagesize($fileTmpPath);
    if ($info === false || empty($info['mime'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El archivo no es una imagen válida.']);
        exit;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($info['mime'], $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de imagen no permitido (solo JPEG/PNG/GIF/WEBP).']);
        exit;
    }

    $ext = match ($info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    };

    // Generar nombre nuevo: nunca reusar el original (evita XSS / path traversal)
    $safeName = 'ticket_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $id)
              . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    $base = realpath(__DIR__ . '/uploads');
    if ($base === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Directorio de uploads no disponible.']);
        exit;
    }
    $destPath = $base . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($fileTmpPath, $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al mover archivo.']);
        exit;
    }

    // Verificar que el destino quedó dentro del directorio uploads (defensa anti path-traversal)
    $resolved = realpath($destPath);
    if ($resolved === false || strpos($resolved, $base . DIRECTORY_SEPARATOR) !== 0) {
        @unlink($destPath);
        http_response_code(500);
        echo json_encode(['error' => 'Ruta de destino inválida.']);
        exit;
    }

    @chmod($destPath, 0644);

    $self = basename(__FILE__);
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
    error_log("UPLOAD ok ip={$ip} script={$self} file={$safeName} size={$fileSize} mime={$info['mime']}");

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
