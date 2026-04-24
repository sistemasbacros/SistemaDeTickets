<?php
/**
 * @file fetch_tickets.php
 * @brief API REST para obtener listado de tickets en formato JSON (DataTables compatible).
 *
 * @description
 * Endpoint que retorna el listado de tickets TI en formato JSON optimizado para DataTables.
 * La fuente de datos es el Rust API (GET /api/TicketBacros/tickets), que filtra
 * automáticamente los tickets del mes actual más los pendientes/en proceso del año.
 *
 * @note Migrado de sqlsrv directo al Rust API. Los campos de imagen (imagen_url,
 *       imagen_nombre, imagen_tipo, imagen_size) y los timestamps de Pausa, Terminado
 *       y Cancelado no son devueltos por el endpoint actual del API; se retornan como
 *       null para mantener compatibilidad con el frontend sin romper la estructura.
 *
 * @outputs
 * - Content-Type: application/json
 * - Estructura DataTables: { "data": [ { ...campos del ticket... }, ... ] }
 * - Error de conexión: {"data": []}
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 * @updated 2026-04-23
 */

header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// URL base del Rust API. Lee PDF_API_URL del entorno (igual que otros módulos)
// o cae en el default para Docker: http://host.docker.internal:3000
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');
$endpoint = $apiUrl . '/api/TicketBacros/tickets';

// ── Llamada al API con cURL ───────────────────────────────────────────────────
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$responseBody = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError    = curl_error($ch);
curl_close($ch);

// ── Manejo de errores de transporte o HTTP ────────────────────────────────────
if ($curlError || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode(['data' => []]);
    exit;
}

$apiResponse = json_decode($responseBody, true);

if (!isset($apiResponse['data']) || !is_array($apiResponse['data'])) {
    echo json_encode(['data' => []]);
    exit;
}

// ── Transformación: snake_case del API → PascalCase que espera el frontend ───
//
// El Rust API devuelve TicketTiData con estos campos (snake_case):
//   nombre, correo, prioridad, empresa, asunto, mensaje, adjuntos,
//   fecha, hora, id_ticket, estatus, responsable,
//   fecha_en_proceso, hora_en_proceso
//
// El frontend JS espera PascalCase (igual que el PHP original):
//   Nombre, Correo, Prioridad, Empresa, Asunto, Mensaje, Adjuntos,
//   Fecha, Hora, Id_Ticket, Estatus, PA,
//   FechaEnProceso, HoraEnProceso,
//   FechaPausa, HoraPausa, FechaTerminado, HoraTerminado,
//   FechaCancelado, HoraCancelado,
//   Imagen_URL, Imagen_Nombre, Imagen_Tipo, Imagen_Size
//
// Campos no disponibles en el endpoint actual → null
// (FechaPausa/Pausa, FechaTerminado/Terminado, FechaCancelado/Cancelado, imágenes)

$dataSet = [];
foreach ($apiResponse['data'] as $ticket) {
    $dataSet[] = [
        'Nombre'          => $ticket['nombre']           ?? '',
        'Correo'          => $ticket['correo']           ?? '',
        'Prioridad'       => $ticket['prioridad']        ?? '',
        'Empresa'         => $ticket['empresa']          ?? '',
        'Asunto'          => $ticket['asunto']           ?? '',
        'Mensaje'         => $ticket['mensaje']          ?? '',
        'Adjuntos'        => $ticket['adjuntos']         ?? '',
        'Fecha'           => $ticket['fecha']            ?? null,
        'Hora'            => $ticket['hora']             ?? null,
        'Id_Ticket'       => $ticket['id_ticket']        ?? '',
        'Estatus'         => $ticket['estatus']          ?? '',
        'PA'              => $ticket['responsable']      ?? null,
        'FechaEnProceso'  => $ticket['fecha_en_proceso'] ?? null,
        'HoraEnProceso'   => $ticket['hora_en_proceso']  ?? null,
        // Los siguientes campos no son retornados por el endpoint actual del API.
        // Se mantienen en la respuesta para no romper la estructura que espera el JS.
        'FechaPausa'      => null,
        'HoraPausa'       => null,
        'FechaTerminado'  => null,
        'HoraTerminado'   => null,
        'FechaCancelado'  => null,
        'HoraCancelado'   => null,
        'Imagen_URL'      => null,
        'Imagen_Nombre'   => null,
        'Imagen_Tipo'     => null,
        'Imagen_Size'     => null,
    ];
}

echo json_encode(['data' => $dataSet]);
