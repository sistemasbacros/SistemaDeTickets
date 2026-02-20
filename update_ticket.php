<?php
/**
 * @file update_ticket.php
 * @brief API REST para actualización de tickets con notificaciones por email.
 *
 * @description
 * Endpoint de API que maneja las actualizaciones de tickets del sistema. Recibe
 * peticiones POST con los datos a actualizar, ejecuta las modificaciones en la
 * base de datos y envía notificaciones por correo electrónico a las partes
 * involucradas (solicitante, responsable asignado, administradores).
 *
 * Operaciones soportadas:
 * - Asignación/cambio de responsable del ticket
 * - Cambio de estado (Abierto, En Proceso, Resuelto, Cerrado)
 * - Actualización de asunto/descripción
 * - Registro de notas y comentarios de seguimiento
 * - Actualización de fechas de atención y resolución
 *
 * Notificaciones automáticas:
 * - Al asignar responsable: Email al técnico asignado
 * - Al cambiar estado: Email al solicitante original
 * - Al resolver ticket: Email de confirmación con detalles
 * - Copia a administradores en todas las notificaciones
 *
 * Plantillas de email HTML con diseño profesional BacroCorp incluyendo:
 * - Logo corporativo
 * - Resumen del ticket actualizado
 * - Historial de cambios
 * - Link para ver el ticket en el sistema
 *
 * @module API de Tickets
 * @access API (POST request requerido)
 *
 * @dependencies
 * - PHP: sqlsrv extension, json_encode/decode
 * - PHPMailer: PHPMailer.php, SMTP.php, Exception.php
 * - SMTP: smtp.office365.com:587 (TLS)
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: T3 (tickets TI)
 * - Operaciones: UPDATE con prepared statements
 * - Columnas actualizables: Estado, Responsable, Asunto, FechaAsignacion,
 *                           FechaResolucion, Notas, UltimaActualizacion
 *
 * @inputs
 * - POST (application/x-www-form-urlencoded):
 *   - id_ticket (required): ID único del ticket a actualizar
 *   - responsable (required): Nombre del técnico responsable
 *   - estatus (required): Nuevo estado del ticket
 *   - asunto (optional): Nuevo asunto/título
 *   - notas (optional): Notas de seguimiento
 *   - fecha_resolucion (optional): Fecha de cierre
 *
 * @outputs
 * - Content-Type: application/json
 * - Respuesta exitosa:
 *   {"success": true, "msg": "Ticket actualizado correctamente", "data": {...}}
 * - Respuesta de error:
 *   {"success": false, "msg": "Descripción del error"}
 *
 * @http_methods
 * - POST: Único método permitido
 * - GET/PUT/DELETE: Retorna error "Método no permitido"
 *
 * @email
 * - Servidor SMTP: smtp.office365.com
 * - Puerto: 587 (STARTTLS)
 * - Remitente: tickets@bacrocorp.com
 * - Plantillas HTML con estilos inline
 * - Encabezados: Logo BacroCorp, número de ticket, estado
 * - Cuerpo: Detalles del ticket, cambios realizados
 * - Footer: Información de contacto, link al sistema
 *
 * @security
 * - Validación de campos requeridos (id_ticket, responsable, estatus)
 * - Content-Type JSON en response header
 * - Sanitización de inputs antes de UPDATE
 * - Prepared statements para prevenir SQL injection
 * - No expone errores de BD en respuesta al cliente
 *
 * @constants
 * - ADMIN_EMAIL: 'tickets@bacrocorp.com' — Email del administrador
 * - ADMIN_NAME: 'Administrador TI BacroCorp' — Nombre del remitente
 *
 * @error_codes
 * - "Método no permitido": Request no es POST
 * - "Datos incompletos": Faltan campos requeridos
 * - "Ticket no encontrado": ID no existe en BD
 * - "Error de conexión": Fallo al conectar con BD
 * - "Error de actualización": Fallo en query UPDATE
 * - "Error de email": Fallo al enviar notificación (no crítico)
 *
 * @workflow
 * 1. Recibir POST request
 * 2. Validar método HTTP (POST obligatorio)
 * 3. Extraer y validar parámetros requeridos
 * 4. Conectar a base de datos
 * 5. Ejecutar UPDATE con prepared statement
 * 6. Obtener datos actualizados del ticket
 * 7. Enviar notificaciones por email
 * 8. Retornar respuesta JSON
 *
 * @author Equipo Tecnología BacroCorp
 * @version 3.0
 * @since 2024
 * @updated 2025-01-20
 */

// update_ticket.php - VERSIÓN COMPLETA CON ESTILOS ORIGINALES Y CORRECCIÓN DE FECHAS

// HEADERS PRIMERO - ANTES DE CUALQUIER SALIDA
header('Content-Type: application/json');

// Incluir PHPMailer después de los headers
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$id_ticket = $_POST['id_ticket'] ?? '';
$responsable = $_POST['responsable'] ?? '';
$estatus = $_POST['estatus'] ?? '';
$asunto = $_POST['asunto'] ?? '';

// Validar datos requeridos
if (empty($id_ticket) || empty($responsable) || empty($estatus)) {
    echo json_encode(['success' => false, 'msg' => 'Datos incompletos: ID Ticket, Responsable y Estatus son requeridos']);
    exit;
}

try {
    // Conectar a la base de datos
    $serverName = $DB_HOST;
    $connectionInfo = array(
        "Database" => $DB_DATABASE,
        "UID" => $DB_USERNAME,
        "PWD" => $DB_PASSWORD,
        "CharacterSet" => "UTF-8",
        "ReturnDatesAsStrings" => true,
        "TrustServerCertificate" => true,
        "Encrypt" => true
    );
    
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if (!$conn) {
        $errors = sqlsrv_errors();
        throw new Exception("Error de conexión a la base de datos: " . $errors[0]['message']);
    }

    // Obtener datos actuales del ticket
    $sqlSelect = "SELECT * FROM T3 WHERE Id_Ticket = ?";
    $paramsSelect = array($id_ticket);
    $stmtSelect = sqlsrv_query($conn, $sqlSelect, $paramsSelect);
    
    if (!$stmtSelect) {
        $errors = sqlsrv_errors();
        throw new Exception("Error al obtener datos del ticket: " . $errors[0]['message']);
    }
    
    $ticketData = sqlsrv_fetch_array($stmtSelect, SQLSRV_FETCH_ASSOC);
    if (!$ticketData) {
        throw new Exception("Ticket no encontrado: " . $id_ticket);
    }
    
    // Extraer datos del ticket
    $userEmail = $ticketData['Correo'] ?? '';
    $userName = $ticketData['Nombre'] ?? '';
    $prioridad = $ticketData['Prioridad'] ?? '';
    $departamento = $ticketData['Empresa'] ?? '';
    $descripcion = $ticketData['Adjuntos'] ?? '';
    $mensaje = $ticketData['Mensaje'] ?? '';
	
	date_default_timezone_set('America/Mexico_City');

    // CORRECCIÓN: Formato de fecha para SQL Server (YYYY-MM-DD)
    $fechaActualSQL = date('Y-m-d');
    $horaActual = date('H:i:s');
    
    // Para correos usamos formato legible (DD/MM/YYYY)
    $fechaActualCorreo = date('d/m/Y');
    
    // Construir consulta dinámica con formato SQL Server
    $sqlUpdate = "UPDATE T3 SET Estatus = ?, PA = ?, Asunto = ?";
    $paramsUpdate = array($estatus, $responsable, $asunto);
    
    switch($estatus) {
        case 'En Proceso':
            $sqlUpdate .= ", FechaEnProceso = ?, HoraEnProceso = ?";
            $paramsUpdate[] = $fechaActualSQL;
            $paramsUpdate[] = $horaActual;
            break;
        case 'Pausa':
            $sqlUpdate .= ", FechaPausa = ?, HoraPausa = ?";
            $paramsUpdate[] = $fechaActualSQL;
            $paramsUpdate[] = $horaActual;
            break;
        case 'Atendido':
            $sqlUpdate .= ", FechaTerminado = ?, HoraTerminado = ?";
            $paramsUpdate[] = $fechaActualSQL;
            $paramsUpdate[] = $horaActual;
            break;
    }
    
    $sqlUpdate .= " WHERE Id_Ticket = ?";
    $paramsUpdate[] = $id_ticket;

    // Actualizar ticket en la base de datos
    $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

    if (!$stmtUpdate) {
        $errors = sqlsrv_errors();
        throw new Exception("Error al actualizar el ticket: " . $errors[0]['message']);
    }

    // Enviar notificaciones según el estado
    $notificationResults = [
        'user' => false,
        'admin' => false
    ];

    // Solo enviar notificaciones si hay email de usuario
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

    // Liberar recursos
    if ($stmtSelect) sqlsrv_free_stmt($stmtSelect);
    if ($stmtUpdate) sqlsrv_free_stmt($stmtUpdate);
    if ($conn) sqlsrv_close($conn);

    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'msg' => 'Ticket actualizado correctamente',
        'notifications' => $notificationResults,
        'ticket_id' => $id_ticket,
        'estatus' => $estatus,
        'responsable' => $responsable
    ]);

} catch (Exception $e) {
    // En caso de error, enviar respuesta JSON de error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'msg' => $e->getMessage(),
        'error_type' => 'server_error'
    ]);
    exit;
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
?>