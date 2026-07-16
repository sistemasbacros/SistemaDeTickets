<?php
/**
 * @file update_ticket.php
 * @brief API REST para actualización de tickets.
 *
 * @description
 * Endpoint de API que maneja las actualizaciones de tickets del sistema. Recibe
 * peticiones POST con los datos a actualizar y llama a la API REST de Rust para
 * ejecutar las modificaciones. Las notificaciones por correo electrónico las
 * envía el backend Rust.
 *
 * Operaciones soportadas:
 * - Asignación/cambio de responsable del ticket
 * - Cambio de estado (Pendiente, En proceso, Atendido)
 * - Actualización de asunto/descripción
 * - Registro de notas y comentarios de seguimiento
 *
 * @api PUT {apiUrl}/api/TicketBacros/tickets/:id
 *
 * @module API de Tickets
 * @access API (POST request requerido)
 *
 * @dependencies
 * - config.php: carga de variables de entorno (.env), PDF_API_URL
 *
 * @inputs
 * - POST (application/x-www-form-urlencoded):
 *   - id_ticket   (required): ID único del ticket a actualizar
 *   - responsable (required): Nombre del técnico responsable
 *   - estatus     (required): Nuevo estado del ticket
 *   - asunto      (optional): Nuevo asunto/título
 *
 * @outputs
 * - Content-Type: application/json
 * - Respuesta exitosa:
 *   {"success": true, "msg": "Ticket actualizado correctamente", "data": {...}}
 * - Respuesta de error:
 *   {"success": false, "msg": "Descripción del error"}
 *
 * @estatus_mapping
 * - PHP "En Proceso" → API "En proceso"  (Rust valida estatus exactos)
 * - PHP "Pausa"      → API "Pausa"       (no mapeado en Rust, se envía tal cual)
 * - PHP "Atendido"   → API "Atendido"
 *
 * @author Equipo Tecnología BacroCorp
 * @version 4.0 (migrado a API REST - sin sqlsrv)
 * @since 2024
 * @updated 2026-04-23
 */

require_once __DIR__ . '/auth_check_api.php';

// HEADERS PRIMERO - ANTES DE CUALQUIER SALIDA
header('Content-Type: application/json');

// config.php carga las variables de entorno (.env) usadas por getenv('PDF_API_URL')
require_once __DIR__ . '/config.php';

// URL base de la API Rust
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$id_ticket  = trim($_POST['id_ticket']  ?? '');
$responsable = trim($_POST['responsable'] ?? '');
$estatus    = trim($_POST['estatus']    ?? '');
$asunto     = trim($_POST['asunto']     ?? '');

// Validar datos requeridos
if (empty($id_ticket) || empty($responsable) || empty($estatus)) {
    echo json_encode(['success' => false, 'msg' => 'Datos incompletos: ID Ticket, Responsable y Estatus son requeridos']);
    exit;
}

try {
    // ------------------------------------------------------------------
    // PASO 1: Verificar que el ticket exista en la API
    // ------------------------------------------------------------------
    $ticketData = obtenerTicketDesdeApi($apiUrl, $id_ticket);
    if ($ticketData === null) {
        throw new Exception("Ticket no encontrado: $id_ticket");
    }

    // ------------------------------------------------------------------
    // PASO 2: Enviar actualización a la API Rust
    //
    // La API Rust acepta JSON con los campos:
    //   ticketId, asignadoA, nuevoEstatus
    //
    // Mapeo de estatus PHP → Rust (la API valida: Pendiente, En proceso, Atendido)
    // "En Proceso" (PHP) → "En proceso" (Rust)
    // "Pausa"      (PHP) → se envía como "Pausa" (el servicio lo acepta como caso _)
    // "Atendido"   (PHP) → "Atendido"
    // ------------------------------------------------------------------
    $estatusApi = mapearEstatusParaApi($estatus);

    $putUrl  = $apiUrl . '/api/TicketBacros/tickets/' . urlencode($id_ticket);
    $putBody = json_encode([
        'ticketId'     => $id_ticket,
        'asignadoA'    => $responsable,
        'nuevoEstatus' => $estatusApi,
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

    $putResponse = curl_exec($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Error de conexión con la API: $curlError");
    }

    $putData = json_decode($putResponse, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $apiMsg = $putData['error'] ?? $putData['message'] ?? $putResponse;
        throw new Exception("Error al actualizar el ticket (HTTP $httpCode): $apiMsg");
    }

    if (isset($putData['success']) && $putData['success'] === false) {
        $apiMsg = $putData['message'] ?? $putData['error'] ?? 'Error desconocido';
        throw new Exception("Error al actualizar el ticket: $apiMsg");
    }

    // ------------------------------------------------------------------
    // PASO 3: Respuesta exitosa
    //         (las notificaciones por correo las envía el backend Rust)
    // ------------------------------------------------------------------
    echo json_encode([
        'success'      => true,
        'msg'          => 'Ticket actualizado correctamente',
        'ticket_id'    => $id_ticket,
        'estatus'      => $estatus,
        'responsable'  => $responsable,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success'    => false,
        'msg'        => $e->getMessage(),
        'error_type' => 'server_error',
    ]);
    exit;
}

// ==============================================
// FUNCIÓN: Obtener datos del ticket desde la API
// ==============================================

/**
 * Llama a GET /api/TicketBacros/tickets y busca el ticket con el id dado.
 * Retorna el array del ticket o null si no se encuentra.
 *
 * @param string $apiUrl  URL base de la API (sin slash final)
 * @param string $idTicket ID del ticket a buscar
 * @return array|null
 */
function obtenerTicketDesdeApi(string $apiUrl, string $idTicket): ?array
{
    $getUrl = $apiUrl . '/api/TicketBacros/tickets';

    $ch = curl_init($getUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['data']) || !is_array($data['data'])) {
        return null;
    }

    // Buscar el ticket por id_ticket
    foreach ($data['data'] as $ticket) {
        if (isset($ticket['id_ticket']) && (string)$ticket['id_ticket'] === (string)$idTicket) {
            return $ticket;
        }
    }

    return null;
}

// ==============================================
// FUNCIÓN: Mapear estatus PHP → API Rust
// ==============================================

/**
 * Mapea los estatus del formulario PHP a los valores exactos que acepta la API Rust.
 * La API valida contra: ["Pendiente", "En proceso", "Atendido"]
 *
 * @param string $estatus Estatus recibido del formulario PHP
 * @return string Estatus válido para la API
 */
function mapearEstatusParaApi(string $estatus): string
{
    $mapa = [
        'En Proceso' => 'En proceso',  // PHP usa mayúscula, Rust usa minúscula
        'En proceso' => 'En proceso',
        'Atendido'   => 'Atendido',
        'Pendiente'  => 'Pendiente',
        'Pausa'      => 'Pausa',       // La API lo acepta (caso _ en el repositorio)
    ];

    return $mapa[$estatus] ?? $estatus;
}
