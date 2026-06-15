<?php
// Incluir los archivos necesarios de PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de correos
define('ADMIN_EMAIL', 'tickets@bacrocorp.com');
define('ADMIN_NAME', 'Administrador TI BacroCorp');
define('HOME_URL', 'http://192.168.100.95/TicketBacros/M/website-menu-05/index.html');

// Variable para controlar si el formulario ya se procesó
$form_procesado = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    $form_procesado = true;
    
    // Validar que todos los campos requeridos existan
    $campos_requeridos = ['Nombre', 'elect', 'prio', 'empre', 'Asunto', 'adj', 'fecha', 'Hora', 'tik'];
    $campos_validos = true;
    $campo_faltante = '';
    
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
            $campos_validos = false;
            $campo_faltante = $campo;
            break;
        }
    }
    
    if (!$campos_validos) {
        showErrorAlert("El campo " . $campo_faltante . " es requerido.");
    } else {
        // Obtener y limpiar los datos del formulario
        $name1 = test_input($_POST["Nombre"]);
        $name2 = test_input($_POST["elect"]);
        $name3 = test_input($_POST["prio"]);
        $name4 = test_input($_POST["empre"]);
        $name5 = test_input($_POST["Asunto"]);
        $name6 = isset($_POST["men"]) ? test_input($_POST["men"]) : '';
        $name7 = test_input($_POST["adj"]);
        $name8 = test_input($_POST["fecha"]);
        $name9 = test_input($_POST["Hora"]);
        $name10 = test_input($_POST["tik"]);
        
        // Insertar en la base de datos
        $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
        $connectionInfo = array(
            "Database" => "Ticket",
            "UID" => "Larome03",
            "PWD" => "Larome03",
            "CharacterSet" => "UTF-8",
            "ReturnDatesAsStrings" => true
        );
        
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        
        if ($conn) {
            // Preparar la consulta SQL
            $sql = "INSERT INTO T3 (
                [Nombre], 
                [Correo], 
                [Prioridad], 
                [Empresa], 
                [Asunto], 
                [Mensaje], 
                [Adjuntos], 
                [Fecha], 
                [Hora], 
                [Id_Ticket], 
                [Estatus], 
                [PA]
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', ''
            )";
            
            $params = array(
                $name1, 
                $name2, 
                $name3, 
                $name4, 
                $name5, 
                $name6, 
                $name7, 
                $name8, 
                $name9, 
                $name10
            );
            
            // Ejecutar la consulta
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en la base de datos: " . print_r($errors, true));
                
                $error_message = "Error al guardar el ticket en la base de datos.";
                if (!empty($errors)) {
                    $error_message .= " Código: " . $errors[0]['code'];
                }
                
                showErrorAlert($error_message);
            } else {
                // Obtener el ID del ticket insertado
                sqlsrv_free_stmt($stmt);
                
                // Enviar correos de confirmación (opcional, no detiene el proceso)
                $emailResults = sendConfirmationEmails(
                    $name1, $name2, $name3, $name4, $name5, 
                    $name6, $name7, $name8, $name9, $name10
                );
                
                // Mostrar alerta de éxito y redirigir
                showSuccessAlert($name1, $name2, $name10);
            }
            
            sqlsrv_close($conn);
        } else {
            $errors = sqlsrv_errors();
            error_log("Error de conexión a la base de datos: " . print_r($errors, true));
            
            $error_message = "Error de conexión a la base de datos.";
            if (!empty($errors)) {
                $error_message .= " Código: " . $errors[0]['code'];
            }
            
            showErrorAlert($error_message);
        }
    }
}

function sendConfirmationEmails($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
    $results = [
        'user' => false,
        'admin' => false
    ];
    
    // Validar que el correo sea válido antes de enviar
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Enviar correo al usuario
        $results['user'] = sendUserConfirmationEmail(
            $name, $email, $priority, $department, $subject, 
            $message, $description, $date, $time, $ticketId
        );
    } else {
        error_log("Correo de usuario inválido: " . $email);
    }
    
    // Enviar correo al administrador
    $results['admin'] = sendAdminNotificationEmail(
        $name, $email, $priority, $department, $subject, 
        $message, $description, $date, $time, $ticketId
    );
    
    return $results;
}

function sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@bacrocorp.com';
        $mail->Password = 'XTqzA0GkA#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10; // Timeout de 10 segundos
        
        // Destinatarios
        $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
        $mail->addAddress($email, $name);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '✅ Confirmación de Ticket #' . $ticketId . ' - Departamento de TI BacroCorp';
        
        // Cuerpo del correo
        $mail->Body = createUserEmailTemplate(
            $name, $email, $priority, $department, $subject, 
            $message, $description, $date, $time, $ticketId
        );
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error enviando correo al usuario: " . $e->getMessage());
        return false;
    }
}

function sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
    try {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tickets@bacrocorp.com';
        $mail->Password = 'XTqzA0GkA#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10; // Timeout de 10 segundos
        
        // Destinatarios
        $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '🚨 NUEVO TICKET #' . $ticketId . ' - ' . $priority . ' - ' . $subject;
        
        // Cuerpo del correo para administrador
        $mail->Body = createAdminEmailTemplate(
            $name, $email, $priority, $department, $subject, 
            $message, $description, $date, $time, $ticketId
        );
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Error enviando correo al administrador: " . $e->getMessage());
        return false;
    }
}

function createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
    $priorityColor = getPriorityColor($priority);
    
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
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            .header {
                background: linear-gradient(135deg, #003366 0%, #0066cc 100%);
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
            .ticket-info {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                border-left: 4px solid #0066cc;
            }
            .ticket-detail {
                margin-bottom: 10px;
                display: flex;
            }
            .ticket-label {
                font-weight: 600;
                min-width: 120px;
            }
            .priority-badge {
                padding: 4px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: bold;
                color: white;
                background: ' . $priorityColor . ';
            }
            .message {
                background-color: #e8f4fd;
                border-radius: 8px;
                padding: 15px;
                margin: 20px 0;
                border-left: 4px solid #3498db;
            }
            .footer {
                background-color: #f1f1f1;
                padding: 20px;
                text-align: center;
                font-size: 14px;
                color: #666;
            }
            .thank-you {
                font-size: 18px;
                color: #2c3e50;
                margin-bottom: 20px;
                text-align: center;
            }
            .ticket-id {
                background: #003366;
                color: white;
                padding: 10px;
                border-radius: 5px;
                text-align: center;
                font-size: 18px;
                font-weight: bold;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎯 Departamento de TI - BacroCorp</h1>
                <p>Sistema de Gestión de Tickets</p>
            </div>
            <div class="content">
                <div class="thank-you">
                    <strong>Estimado/a ' . htmlspecialchars($name) . ',</strong>
                </div>
                <p>Hemos recibido correctamente tu solicitud de soporte técnico y hemos creado un ticket con la siguiente información:</p>
                
                <div class="ticket-id">Ticket #: ' . htmlspecialchars($ticketId) . '</div>
                
                <div class="ticket-info">
                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . htmlspecialchars($date) . ' a las ' . htmlspecialchars($time) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Departamento:</span>
                        <span>' . htmlspecialchars($department) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Tipo de Solicitud:</span>
                        <span>' . htmlspecialchars($subject) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Prioridad:</span>
                        <span class="priority-badge">' . htmlspecialchars($priority) . '</span>
                    </div>
                </div>
                
                <div class="message">
                    <p><strong>Descripción del problema:</strong></p>
                    <p>' . htmlspecialchars($description) . '</p>
                    ' . (!empty($message) ? '<p><strong>Detalles adicionales:</strong></p><p>' . htmlspecialchars($message) . '</p>' : '') . '
                </div>
                
                <p>Nuestro equipo de especialistas revisará tu solicitud a la mayor brevedad posible y te mantendremos informado sobre el progreso.</p>
                
                <p>Si necesitas agregar información adicional a tu ticket o tienes alguna pregunta, por favor responde a este correo haciendo referencia al número de ticket proporcionado.</p>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #555;">
                    <p>Atentamente,<br>
                    <strong>Equipo de Soporte Técnico</strong><br>
                    Departamento de TI - BacroCorp<br>
                    <em>"Innovación y eficiencia a tu servicio"</em></p>
                </div>
            </div>
            <div class="footer">
                <p>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
                <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>';
}

function createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
    $priorityColor = getPriorityColor($priority);
    $urgencyIcon = getUrgencyIcon($priority);
    
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
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
                background: #fff3cd;
                border: 1px solid #ffeaa7;
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
                border-left: 5px solid ' . $priorityColor . ';
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
                background: ' . $priorityColor . ';
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
                <h1>' . $urgencyIcon . ' NUEVO TICKET DE SOPORTE - REQUIERE ATENCIÓN</h1>
            </div>
            <div class="content">
                <div class="alert-banner">
                    ⚠️ Se ha generado un nuevo ticket con prioridad ' . htmlspecialchars($priority) . ' que requiere tu atención inmediata.
                </div>
                
                <div class="ticket-card">
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . htmlspecialchars($ticketId) . '</span>
                    </div>
                    
                    <div class="ticket-detail">
                        <span class="ticket-label">Fecha y Hora:</span>
                        <span>' . htmlspecialchars($date) . ' - ' . htmlspecialchars($time) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Prioridad:</span>
                        <span class="priority-badge">' . htmlspecialchars($priority) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Tipo de Solicitud:</span>
                        <span><strong>' . htmlspecialchars($subject) . '</strong></span>
                    </div>
                </div>
                
                <div class="user-info">
                    <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                    <div class="ticket-detail">
                        <span class="ticket-label">Nombre:</span>
                        <span>' . htmlspecialchars($name) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Correo:</span>
                        <span>' . htmlspecialchars($email) . '</span>
                    </div>
                    <div class="ticket-detail">
                        <span class="ticket-label">Departamento:</span>
                        <span>' . htmlspecialchars($department) . '</span>
                    </div>
                </div>
                
                <div class="problem-description">
                    <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                    <p><strong>' . htmlspecialchars($description) . '</strong></p>
                    ' . (!empty($message) ? '<p><strong>Detalles adicionales:</strong></p><p>' . htmlspecialchars($message) . '</p>' : '') . '
                </div>
                
                <div class="action-buttons">
                    <p style="font-weight: bold; margin-bottom: 15px;">🚀 Acciones Recomendadas:</p>
                    <p>• Revisar el ticket en el sistema de gestión<br>
                    • Asignar técnico según la prioridad<br>
                    • Contactar al usuario si se requiere información adicional</p>
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

function showSuccessAlert($name, $email, $ticketId) {
    $home_url = HOME_URL;
    
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="refresh" content="3;url=' . $home_url . '">
        <title>Ticket Creado - Redirigiendo...</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body {
                font-family: "Inter", sans-serif;
                background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <script>
        // Ocultar modal de procesamiento si existe
        if (window.opener && window.opener.document.getElementById("processingModal")) {
            window.opener.document.getElementById("processingModal").classList.remove("active");
        }
        
        Swal.fire({
            title: "✅ ¡Ticket Creado Exitosamente!",
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">🎉</div>
                    <h2 style="color: #003366; margin-bottom: 15px;">Solicitud Registrada</h2>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                        <p style="margin: 5px 0;"><strong>Nombre:</strong> ' . addslashes(htmlspecialchars($name)) . '</p>
                        <p style="margin: 5px 0;"><strong>Correo:</strong> ' . addslashes(htmlspecialchars($email)) . '</p>
                        <p style="margin: 10px 0;"><strong>Ticket ID:</strong> 
                            <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">' . addslashes(htmlspecialchars($ticketId)) . '</span>
                        </p>
                    </div>
                    
                    <p style="color: #28a745; font-weight: bold;">✓ Ticket guardado correctamente</p>
                    <p style="color: #666; font-size: 14px; margin-top: 15px;">Serás redirigido al inicio en 3 segundos...</p>
                </div>
            `,
            icon: "success",
            confirmButtonColor: "#003366",
            confirmButtonText: "Volver al Inicio Ahora",
            allowOutsideClick: false,
            timer: 3000,
            timerProgressBar: true
        }).then((result) => {
            window.location.href = "' . $home_url . '";
        });
        </script>
    </body>
    </html>';
    exit();
}

function showErrorAlert($message) {
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body {
                font-family: "Inter", sans-serif;
                background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <script>
        // Ocultar modal de procesamiento
        if (window.opener && window.opener.document.getElementById("processingModal")) {
            window.opener.document.getElementById("processingModal").classList.remove("active");
        }
        
        Swal.fire({
            title: "❌ Error",
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px; color: #dc3545; margin-bottom: 20px;">⚠️</div>
                    <p style="color: #2c3e50;">' . addslashes($message) . '</p>
                    <p style="color: #666; font-size: 14px; margin-top: 15px;">Por favor, intenta nuevamente.</p>
                </div>
            `,
            icon: "error",
            confirmButtonColor: "#003366",
            confirmButtonText: "Volver al formulario"
        }).then(() => {
            window.history.back();
        });
        </script>
    </body>
    </html>';
    exit();
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Ticket Soporte TI</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
  * {
    box-sizing: border-box;
  }

  body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
    color: #111;
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    position: relative;
  }

  .container {
    background: #ffffff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
    max-width: 650px;
    width: 100%;
  }

  h2 {
    font-weight: 700;
    font-size: 2.5rem;
    margin-bottom: 20px;
    color: #333;
    text-align: center;
    animation: pulse 4s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% {
      text-shadow: 0 0 8px #aaa;
      color: #222;
    }
    50% {
      text-shadow: 0 0 20px #888;
      color: #444;
    }
  }

  img.logo {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 150px;
    height: 150px;
    object-fit: contain;
    filter: drop-shadow(0 0 3px #999);
    animation: float 4s ease-in-out infinite;
  }

  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
  }

  form {
    display: grid;
    gap: 20px;
  }

  label {
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 6px;
    color: #444;
  }

  input[type="text"],
  select,
  textarea {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #bbb;
    font-size: 1rem;
    background: #f9f9f9;
    color: #111;
    transition: all 0.2s ease-in-out;
  }

  input[type="text"]:focus,
  select:focus,
  textarea:focus {
    background: #eaeaea;
    border-color: #888;
    outline: none;
  }

  textarea {
    resize: vertical;
    min-height: 100px;
  }

  .form-group {
    display: flex;
    flex-direction: column;
  }

  button[type="submit"] {
    background: #444;
    color: white;
    font-size: 1.1rem;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
    position: relative;
    overflow: hidden;
  }

  button[type="submit"]:hover:not(:disabled) {
    background: #222;
    transform: translateY(-2px);
  }

  button[type="submit"]:disabled {
    background: #999;
    cursor: not-allowed;
    transform: none;
  }

  .loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 10px;
    vertical-align: middle;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  @media (max-width: 640px) {
    .container {
      padding: 25px 20px;
    }

    h2 {
      font-size: 2rem;
    }
  }

  #homeButton {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 56px;
    height: 56px;
    background-color: #0033cc;
    color: #ffffff;
    border: none;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    z-index: 1000;
  }

  #homeButton:hover {
    background-color: #0022aa;
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
  }

  /* Modal de procesamiento personalizado */
  .processing-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }

  .processing-modal.active {
    display: flex;
  }

  .processing-content {
    background: white;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: slideIn 0.3s ease;
  }

  @keyframes slideIn {
    from {
      transform: translateY(-50px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .processing-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #003366;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
  }

  .processing-content h3 {
    color: #003366;
    margin-bottom: 10px;
    font-size: 24px;
  }

  .processing-content p {
    color: #666;
    margin: 5px 0;
  }

  .progress-bar {
    width: 100%;
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    margin-top: 20px;
    overflow: hidden;
  }

  .progress-fill {
    width: 0%;
    height: 100%;
    background: linear-gradient(90deg, #003366, #0066cc);
    animation: progress 2s ease-in-out infinite;
  }

  @keyframes progress {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
  }
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<h2>Ticket Soporte TI</h2>
<img src="Logo2.png" alt="Logo" class="logo" />

<!-- Modal de procesamiento -->
<div class="processing-modal" id="processingModal">
  <div class="processing-content">
    <div class="processing-spinner"></div>
    <h3>Procesando solicitud</h3>
    <p>Por favor espera, estamos registrando tu ticket...</p>
    <p style="font-size: 14px; color: #999;">No cierres esta ventana</p>
    <div class="progress-bar">
      <div class="progress-fill"></div>
    </div>
  </div>
</div>

<div class="container">
  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="ticketForm">
    <input type="hidden" name="submit_ticket" value="1">
    
    <div class="form-group">
      <label for="Nombre">Nombre</label>
      <input type="text" id="Nombre" name="Nombre" placeholder="Escribe tu nombre completo" required />
    </div>

    <div class="form-group">
      <label for="elect">Correo electrónico</label>
      <input type="text" id="elect" name="elect" placeholder="correo@empresa.com" required />
    </div>

    <div class="form-group">
      <label for="prio">Prioridad</label>
      <select id="prio" name="prio" required>
        <option value="Bajo">Bajo</option>
        <option value="Medio">Medio</option>
        <option value="Alto">Alto</option>
      </select>
    </div>

    <div class="form-group">
      <label for="empre">Departamento</label>
      <input type="text" id="empre" name="empre" placeholder="Ej: Finanzas, RH, IT..." required />
    </div>

    <div class="form-group">
      <label for="Asunto">Tipo</label>
      <select id="Asunto" name="Asunto" required>
        <option value="COMEDOR">COMEDOR</option>
        <option value="ERP">ERP</option>
        <option value="LAPTOP / PC">LAPTOP / PC</option>
        <option value="IMPRESORA">IMPRESORA</option>
        <option value="CONTPAQi">CONTPAQi</option>
        <option value="CORREO">CORREO</option>
        <option value="NUEVO INGRESO">NUEVO INGRESO</option>
        <option value="CARPETAS ACCESO">CARPETAS ACCESO</option>
        <option value="SALIDA EQUIPO">SALIDA EQUIPO</option>
        <option value="DIGITALIZACIÓN">DIGITALIZACIÓN</option>
        <option value="BLOQUEO USB">BLOQUEO USB</option>
        <option value="TELEFONÍA">TELEFONÍA</option>
        <option value="INTERNET">INTERNET</option>
        <option value="SOFTWARE">SOFTWARE</option>
        <option value="INFRAESTRUCTURA TI">INFRAESTRUCTURA TI</option>
        <option value="OTROS">OTROS</option>
      </select>
    </div>

    <div class="form-group">
      <label for="adj">Descripción</label>
      <input type="text" id="adj" name="adj" placeholder="Resumen breve del problema" required />
    </div>

    <div class="form-group">
      <label for="men">Mensaje</label>
      <textarea id="men" name="men" placeholder="Detalles completos del problema..."></textarea>
    </div>

    <div class="form-group">
      <label for="fecha">Fecha</label>
      <input id="fecha" name="fecha" type="text" required readonly />
    </div>

    <div class="form-group">
      <label for="Hora">Hora</label>
      <input type="text" id="Hora" name="Hora" required readonly />
    </div>

    <div class="form-group">
      <label for="tik">ID Ticket</label>
      <input type="text" id="tik" name="tik" required readonly />
    </div>

    <button type="submit" id="submitBtn">
      <span class="btn-text">Solicitar</span>
    </button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.getElementById("homeButton").addEventListener("click", function() {
    window.location.href = "<?php echo HOME_URL; ?>";
  });

  function pad(n) { return n < 10 ? '0' + n : n; }
  
  function updateDateTime() {
    const now = new Date();
    document.getElementById('fecha').value = now.toLocaleDateString('es-ES');
    document.getElementById('Hora').value = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
  }
  updateDateTime();
  setInterval(updateDateTime, 1000);

  function generateTicketID() {
    const ticket = document.getElementById('tik');
    if (!ticket.value) {
      ticket.value = 'TI-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
    }
  }
  generateTicketID();

  // Control del formulario para evitar doble clic
  const form = document.getElementById('ticketForm');
  const submitBtn = document.getElementById('submitBtn');
  const processingModal = document.getElementById('processingModal');
  let formSubmitted = false;

  form.addEventListener('submit', function(e) {
    if (formSubmitted) {
      e.preventDefault();
      return false;
    }

    // Validar campos requeridos
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        isValid = false;
        field.style.borderColor = '#dc3545';
      } else {
        field.style.borderColor = '#bbb';
      }
    });

    // Validación adicional para correo electrónico
    const emailField = document.getElementById('elect');
    const emailValue = emailField.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (emailValue && !emailPattern.test(emailValue)) {
      isValid = false;
      emailField.style.borderColor = '#dc3545';
      Swal.fire({
        title: 'Correo inválido',
        text: 'Por favor ingresa un correo electrónico válido',
        icon: 'warning',
        confirmButtonColor: '#003366'
      });
      return false;
    }

    if (!isValid) {
      e.preventDefault();
      Swal.fire({
        title: 'Campos incompletos',
        text: 'Por favor completa todos los campos requeridos',
        icon: 'warning',
        confirmButtonColor: '#003366'
      });
      return false;
    }

    // Marcar como enviado y mostrar modal
    formSubmitted = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Procesando...';
    processingModal.classList.add('active');

    // El formulario se enviará normalmente
    return true;
  });

  // Validación en tiempo real para campos requeridos
  const requiredInputs = form.querySelectorAll('[required]');
  requiredInputs.forEach(input => {
    input.addEventListener('input', function() {
      if (this.value.trim()) {
        this.style.borderColor = '#bbb';
      }
    });
  });

  // Validación en tiempo real para el correo
  const emailInput = document.getElementById('elect');
  emailInput.addEventListener('input', function() {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (this.value.trim() && !emailPattern.test(this.value.trim())) {
      this.style.borderColor = '#dc3545';
    } else if (this.value.trim() && emailPattern.test(this.value.trim())) {
      this.style.borderColor = '#28a745';
    } else {
      this.style.borderColor = '#bbb';
    }
  });

  // Prevenir envío con Enter accidental
  form.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
      e.preventDefault();
    }
  });

  // Regenerar ticket ID si está vacío al cargar la página
  window.addEventListener('load', function() {
    generateTicketID();
  });
</script>

</body>
</html>