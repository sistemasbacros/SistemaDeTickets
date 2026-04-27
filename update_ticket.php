<?php
/**
 * @file update_ticket.php
 * @brief API REST para actualización de tickets con notificaciones por email.
 *
 * @description
 * Endpoint de API que maneja las actualizaciones de tickets del sistema. Recibe
 * peticiones POST con los datos a actualizar, llama a la API REST de Rust para
 * ejecutar las modificaciones y envía notificaciones por correo electrónico a las
 * partes involucradas (solicitante, responsable asignado, administradores).
 *
 * Operaciones soportadas:
 * - Asignación/cambio de responsable del ticket
 * - Cambio de estado (Pendiente, En proceso, Atendido)
 * - Actualización de asunto/descripción
 * - Registro de notas y comentarios de seguimiento
 *
 * Notificaciones automáticas:
 * - Al cambiar estado "En proceso": Email al técnico asignado y al administrador
 * - Al poner en "Pausa": Email al usuario y al administrador
 * - Al marcar como "Atendido": Email de confirmación con detalles
 *
 * @api PUT {apiUrl}/api/TicketBacros/tickets/:id
 *
 * @module API de Tickets
 * @access API (POST request requerido)
 *
 * @dependencies
 * - PHPMailer: PHPMailer.php, SMTP.php, Exception.php
 * - SMTP: smtp.office365.com:587 (TLS)
 * - config.php: SMTP credentials, ADMIN_EMAIL, ADMIN_NAME, PDF_API_URL
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

// Incluir PHPMailer después de los headers
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    // PASO 1: Obtener datos actuales del ticket desde la API
    //         (necesarios para el correo: Correo, Nombre, Prioridad, etc.)
    // ------------------------------------------------------------------
    $ticketData = obtenerTicketDesdeApi($apiUrl, $id_ticket);
    if ($ticketData === null) {
        throw new Exception("Ticket no encontrado: $id_ticket");
    }

    $userEmail   = $ticketData['correo']    ?? '';
    $userName    = $ticketData['nombre']    ?? '';
    $prioridad   = $ticketData['prioridad'] ?? '';
    $departamento = $ticketData['empresa']  ?? '';
    $descripcion = $ticketData['adjuntos']  ?? '';
    $mensaje     = $ticketData['mensaje']   ?? '';

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
    // PASO 3: Enviar notificaciones por correo según el estado
    // ------------------------------------------------------------------
    date_default_timezone_set('America/Mexico_City');
    $fechaActualCorreo = date('d/m/Y');
    $horaActual        = date('H:i:s');

    $notificationResults = ['user' => false, 'admin' => false];

    if (!empty($userEmail)) {
        $notificationResults = sendStatusNotifications(
            $estatus,
            $userName,
            $userEmail,
            $responsable,
            $id_ticket,
            $prioridad,
            $departamento,
            $asunto,
            $descripcion,
            $mensaje,
            $fechaActualCorreo,
            $horaActual
        );
    }

    // ------------------------------------------------------------------
    // PASO 4: Respuesta exitosa
    // ------------------------------------------------------------------
    echo json_encode([
        'success'      => true,
        'msg'          => 'Ticket actualizado correctamente',
        'notifications' => $notificationResults,
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

// ==============================================
// FUNCIONES DE NOTIFICACIÓN
// ==============================================

function sendStatusNotifications($estatus, $userName, $userEmail, $responsable, $ticketId, $prioridad, $department, $subject, $description, $message, $fechaCorreo, $horaCorreo) {
    $results = [
        'user' => false,
        'admin' => false
    ];

    try {
        switch($estatus) {
            case 'En Proceso':
            case 'En proceso':
                $results['user'] = sendUserInProcessEmail($userName, $userEmail, $responsable, $ticketId, $subject, $description, $message, $fechaCorreo, $horaCorreo);
                $results['admin'] = sendAdminInProcessEmail($userName, $userEmail, $responsable, $ticketId, $subject, $prioridad, $department, $description, $message, $fechaCorreo, $horaCorreo);
                break;

            case 'Pausa':
                $results['user'] = sendUserPauseEmail($userName, $userEmail, $responsable, $ticketId, $subject, $description, $message, $fechaCorreo, $horaCorreo);
                $results['admin'] = sendAdminPauseEmail($userName, $userEmail, $responsable, $ticketId, $subject, $prioridad, $department, $description, $message, $fechaCorreo, $horaCorreo);
                break;

            case 'Atendido':
                $results['user'] = sendUserCompletedEmail($userName, $userEmail, $responsable, $ticketId, $subject, $description, $message, $fechaCorreo, $horaCorreo);
                $results['admin'] = sendAdminCompletedEmail($userName, $userEmail, $responsable, $ticketId, $subject, $prioridad, $department, $description, $message, $fechaCorreo, $horaCorreo);
                break;
        }
    } catch (Exception $e) {
        error_log("Error en notificaciones: " . $e->getMessage());
    }

    return $results;
}

// ==============================================
// FUNCIONES DE ENVÍO DE CORREO
// ==============================================

function sendUserInProcessEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "🔄 Ticket #$ticketId en Proceso - $asunto";

        $mail->Body = createUserInProcessTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo EN PROCESO al usuario: " . $e->getMessage());
        return false;
    }
}

function sendAdminInProcessEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);

        $mail->isHTML(true);
        $mail->Subject = "✅ TICKET ASIGNADO #$ticketId - $responsable";

        $mail->Body = createAdminInProcessTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo EN PROCESO al admin: " . $e->getMessage());
        return false;
    }
}

function sendUserPauseEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "⏸️ Ticket #$ticketId en Pausa - $asunto";

        $mail->Body = createUserPauseTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo PAUSA al usuario: " . $e->getMessage());
        return false;
    }
}

function sendAdminPauseEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);

        $mail->isHTML(true);
        $mail->Subject = "⏸️ TICKET EN PAUSA #$ticketId - $responsable";

        $mail->Body = createAdminPauseTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo PAUSA al admin: " . $e->getMessage());
        return false;
    }
}

function sendUserCompletedEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "✅ Ticket #$ticketId Atendido - $asunto";

        $mail->Body = createUserCompletedTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo ATENDIDO al usuario: " . $e->getMessage());
        return false;
    }
}

function sendAdminCompletedEmail($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo) {
    try {
        $mail = new PHPMailer(true);

        configurarSMTP($mail);

        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);

        $mail->isHTML(true);
        $mail->Subject = "🎉 TICKET COMPLETADO #$ticketId - $responsable";

        $mail->Body = createAdminCompletedTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaCorreo, $horaCorreo);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Error enviando correo ATENDIDO al admin: " . $e->getMessage());
        return false;
    }
}

// ==============================================
// PLANTILLAS DE CORREO - USUARIO (TUS ESTILOS ORIGINALES)
// ==============================================

function createUserInProcessTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaActual, $horaActual) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .content {
                padding: 30px;
            }
            .alert-banner {
                background: #e7f3ff;
                border: 2px solid #007bff;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
                font-weight: bold;
                color: #004085;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 25px;
                margin: 25px 0;
                border-left: 5px solid #007bff;
            }
            .ticket-detail {
                margin-bottom: 12px;
                display: flex;
                align-items: center;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 160px;
                color: #2c3e50;
            }
            .progress-container {
                background: #e9ecef;
                border-radius: 10px;
                height: 12px;
                margin: 30px 0;
                overflow: hidden;
                position: relative;
            }
            .progress-bar {
                background: linear-gradient(135deg, #007bff, #0056b3);
                height: 100%;
                width: 50%;
                border-radius: 10px;
                animation: progressAnimation 2s ease-in-out infinite;
                position: relative;
            }
            @keyframes progressAnimation {
                0% { width: 50%; }
                50% { width: 60%; }
                100% { width: 50%; }
            }
            .progress-text {
                text-align: center;
                color: #007bff;
                font-weight: 600;
                margin-top: 10px;
                font-size: 14px;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #17a2b8;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 30px 0;
                padding: 25px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 25px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🔄 TICKET EN PROCESO</h1>
                <p>Departamento de TI - BacroCorp</p>
            </div>
            <div class="content">
                <div class="alert-banner">
                    🔧 ¡Estamos trabajando en tu solicitud! Tu ticket está siendo atendido activamente.
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <span style="background: #003366; color: white; padding: 10px 25px; border-radius: 25px; font-size: 18px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Asunto:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Responsable:</span>
                        <span style="color: #007bff; font-weight: bold;">' . $responsable . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Estado:</span>
                        <span style="background: #007bff; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: bold;">En Proceso</span>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="progress-text">
                    ⏳ Procesando tu solicitud - Trabajando en la solución...
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Ticket</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Solicitante:</span>
                        <span>' . $userName . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<div style="margin-top: 15px;"><strong>Detalles adicionales:</strong><p>' . $mensaje . '</p></div>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px; color: #2c3e50;">💬 Comunicación</p>
                    <p>Nuestro especialista <strong>' . $responsable . '</strong> se encuentra trabajando en la solución.<br>
                    Te mantendremos informado sobre el progreso. Agradecemos tu paciencia.</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

function createUserPauseTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaActual, $horaActual) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
                color: #856404;
                padding: 25px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .content {
                padding: 25px;
            }
            .alert-banner {
                background: #fff3cd;
                border: 2px solid #ffeaa7;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-weight: bold;
                color: #856404;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 5px solid #ffc107;
            }
            .ticket-detail {
                margin-bottom: 8px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 140px;
                color: #2c3e50;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⏸️ TICKET EN PAUSA TEMPORAL</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    ⏳ Ticket en pausa temporal - Estamos esperando información adicional
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Asunto:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Responsable:</span>
                        <span style="color: #ffc107; font-weight: bold;">' . $responsable . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Estado:</span>
                        <span style="background: #ffc107; color: #856404; padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: bold;">En Pausa</span>
                    </div>
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Ticket</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Solicitante:</span>
                        <span>' . $userName . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<p><strong>Detalles adicionales:</strong></p><p>' . $mensaje . '</p>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px; color: #856404;">🔍 Razón de la Pausa</p>
                    <p>Actualmente estamos esperando información adicional, recursos necesarios
                    o coordinación con otros departamentos para continuar con la atención de tu solicitud.</p>
                    <p style="margin-top: 15px; color: #666;">Nuestro especialista reanudará el trabajo tan pronto como se resuelvan los impedimentos.</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

function createUserCompletedTemplate($userName, $responsable, $ticketId, $asunto, $descripcion, $mensaje, $fechaActual, $horaActual) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 25px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .content {
                padding: 25px;
            }
            .alert-banner {
                background: #d4edda;
                border: 2px solid #c3e6cb;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-weight: bold;
                color: #155724;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 5px solid #28a745;
            }
            .ticket-detail {
                margin-bottom: 8px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 140px;
                color: #2c3e50;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ TICKET COMPLETADO</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    🎉 ¡Ticket atendido exitosamente! Gracias por su confianza.
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Asunto:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Atendido por:</span>
                        <span style="color: #28a745; font-weight: bold;">' . $responsable . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Estado:</span>
                        <span style="background: #28a745; color: white; padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: bold;">Completado</span>
                    </div>
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Ticket</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Solicitante:</span>
                        <span>' . $userName . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<p><strong>Detalles adicionales:</strong></p><p>' . $mensaje . '</p>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px; color: #155724;">🎉 Agradecimiento</p>
                    <p>Le extendemos nuestro más sincero agradecimiento por su paciencia y confianza
                    en nuestro servicio. Su satisfacción es nuestra prioridad.</p>
                    <p style="margin-top: 15px; color: #666;">Por favor, verifique que todo funcione correctamente.
                    Si necesita más asistencia, no dude en contactarnos nuevamente.</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

// ==============================================
// PLANTILLAS DE CORREO - ADMINISTRADOR (TUS ESTILOS ORIGINALES)
// ==============================================

function createAdminInProcessTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaActual, $horaActual) {
    $prioridadColor = getPriorityColor($prioridad);
    $urgencyIcon = getUrgencyIcon($prioridad);

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 25px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .content {
                padding: 25px;
            }
            .alert-banner {
                background: #d4edda;
                border: 2px solid #c3e6cb;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-weight: bold;
                color: #155724;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 5px solid ' . $prioridadColor . ';
            }
            .ticket-detail {
                margin-bottom: 8px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 140px;
                color: #2c3e50;
            }
            .priority-badge {
                background: ' . $prioridadColor . ';
                color: white;
                padding: 6px 15px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: bold;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . $urgencyIcon . ' TICKET ASIGNADO - EN PROCESO</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    ✅ Ticket asignado correctamente a ' . $responsable . '
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Prioridad:</span>
                        <span class="priority-badge">' . $prioridad . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Tipo de Solicitud:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Responsable:</span>
                        <span style="color: #28a745; font-weight: bold;">' . $responsable . '</span>
                    </div>
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Nombre:</span>
                        <span>' . $userName . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Correo:</span>
                        <span>' . $userEmail . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Departamento:</span>
                        <span>' . $departamento . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<p><strong>Detalles adicionales:</strong></p><p>' . $mensaje . '</p>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px;">🚀 Estado Actual: En Proceso</p>
                    <p>• Ticket asignado a: <strong>' . $responsable . '</strong><br>
                    • Fecha de asignación: ' . $fechaActual . '<br>
                    • Hora: ' . $horaActual . '</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

function createAdminPauseTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaActual, $horaActual) {
    $prioridadColor = getPriorityColor($prioridad);

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
                color: #856404;
                padding: 25px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .content {
                padding: 25px;
            }
            .alert-banner {
                background: #fff3cd;
                border: 2px solid #ffeaa7;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-weight: bold;
                color: #856404;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 5px solid ' . $prioridadColor . ';
            }
            .ticket-detail {
                margin-bottom: 8px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 140px;
                color: #2c3e50;
            }
            .priority-badge {
                background: ' . $prioridadColor . ';
                color: white;
                padding: 6px 15px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: bold;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⏸️ TICKET EN PAUSA</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    ⏳ Ticket puesto en pausa por ' . $responsable . '
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Prioridad:</span>
                        <span class="priority-badge">' . $prioridad . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Tipo de Solicitud:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Responsable:</span>
                        <span style="color: #ffc107; font-weight: bold;">' . $responsable . '</span>
                    </div>
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Nombre:</span>
                        <span>' . $userName . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Correo:</span>
                        <span>' . $userEmail . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Departamento:</span>
                        <span>' . $departamento . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<p><strong>Detalles adicionales:</strong></p><p>' . $mensaje . '</p>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px;">📝 Para evidencia</p>
                    <p>Este correo documenta el cambio de estado a "Pausa" del ticket.<br>
                    Se requiere seguimiento para reactivar la atención.</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

function createAdminCompletedTemplate($userName, $userEmail, $responsable, $ticketId, $asunto, $prioridad, $departamento, $descripcion, $mensaje, $fechaActual, $horaActual) {
    $prioridadColor = getPriorityColor($prioridad);

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .container {
                max-width: 700px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            .header {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: white;
                padding: 25px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .content {
                padding: 25px;
            }
            .alert-banner {
                background: #d4edda;
                border: 2px solid #c3e6cb;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                text-align: center;
                font-weight: bold;
                color: #155724;
            }
            .ticket-card {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 5px solid ' . $prioridadColor . ';
            }
            .ticket-detail {
                margin-bottom: 8px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 140px;
                color: #2c3e50;
            }
            .priority-badge {
                background: ' . $prioridadColor . ';
                color: white;
                padding: 6px 15px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: bold;
            }
            .user-info {
                background: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
            }
            .problem-description {
                background: #fff3cd;
                border-radius: 8px;
                padding: 15px;
                margin: 15px 0;
                border-left: 4px solid #ffc107;
            }
            .action-buttons {
                text-align: center;
                margin: 25px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                background-color: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 TICKET COMPLETADO</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    ✅ Ticket completado exitosamente por ' . $responsable . '
                </div>

                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                    </div>

                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . $fechaActual . ' - ' . $horaActual . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Prioridad:</span>
                        <span class="priority-badge">' . $prioridad . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Tipo de Solicitud:</span>
                        <span><strong>' . $asunto . '</strong></span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Responsable:</span>
                        <span style="color: #28a745; font-weight: bold;">' . $responsable . '</span>
                    </div>
                </div>

                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Nombre:</span>
                        <span>' . $userName . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Correo:</span>
                        <span>' . $userEmail . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Departamento:</span>
                        <span>' . $departamento . '</span>
                    </div>
                </div>

                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . $descripcion . '</strong></p>
                    ' . ($mensaje ? '<p><strong>Detalles adicionales:</strong></p><p>' . $mensaje . '</p>' : '') . '
                </div>

                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px;">📝 Para evidencia</p>
                    <p>Este correo sirve como constancia de la finalización exitosa del ticket.<br>
                    Se ha notificado al usuario sobre la conclusión del servicio.</p>
                </div>

                <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                    <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
                </div>
            </div>
            <div class="footer">
                <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

// ==============================================
// FUNCIONES AUXILIARES
// ==============================================

function getPriorityColor($priority) {
    switch ($priority) {
        case 'Alto': return '#dc3545';
        case 'Medio': return '#ffc107';
        case 'Bajo': return '#28a745';
        default: return '#6c757d';
    }
}

function getUrgencyIcon($priority) {
    switch ($priority) {
        case 'Alto': return '🚨🔥';
        case 'Medio': return '⚠️📋';
        case 'Bajo': return 'ℹ️📥';
        default: return '📝';
    }
}
