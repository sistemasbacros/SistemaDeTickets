<?php
/**
 * @file FormTic.php
 * @brief Formulario de creación de tickets de soporte TI (versión ligera).
 *
 * @description
 * Formulario simplificado para la creación de tickets de soporte de TI.
 * Presenta una interfaz moderna con diseño degradado claro, optimizada
 * para captura rápida de incidencias sin requerir autenticación previa.
 *
 * Características:
 * - Diseño limpio con gradiente light (#f0f0f0 → #dcdcdc)
 * - Tipografía Inter de Google Fonts
 * - Iconos Font Awesome 5.15.4
 * - Alertas con SweetAlert2
 * - Sin verificación de sesión (formulario público)
 * - Campos: Nombre, Email, Prioridad, Asunto, Descripción
 *
 * Nota: Este es un formulario más simple que FormTic1.php, sin las
 * funcionalidades avanzadas de subida de imágenes y verificación.
 *
 * @module Módulo de Tickets TI
 * @access Público (sin autenticación)
 *
 * @dependencies
 * - JS CDN: Font Awesome 5.15.4, SweetAlert2 11
 * - CSS CDN: Google Fonts (Inter)
 * - Backend: API Rust /api/TicketBacros/tickets (POST)
 *
 * @ui_components
 * - Formulario centrado con campos de entrada
 * - Selectores de prioridad
 * - Área de texto para descripción
 * - Botón de envío
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/js_escape.php';
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
  }

  button[type="submit"]:hover {
    background: #222;
    transform: translateY(-2px);
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
</style>

</head>
<body>
<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<h2>Ticket Soporte TI</h2>
<img src="Logo2.png" alt="Logo" class="logo" />

<div class="container">
  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
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

    <button type="submit">Solicitar</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.getElementById("homeButton").addEventListener("click", function() {
    window.location.href = "M/website-menu-05/index.html";
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
</script>

</body>
</html>

<?php
// Incluir los archivos necesarios de PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name1  = test_input($_POST["Nombre"]);
  $name2  = test_input($_POST["elect"]);
  $name3  = test_input($_POST["prio"]);
  $name4  = test_input($_POST["empre"]);
  $name5  = test_input($_POST["Asunto"]);
  $name6  = test_input($_POST["men"]);
  $name7  = test_input($_POST["adj"]);
  $name8  = test_input($_POST["fecha"]);
  $name9  = test_input($_POST["Hora"]);
  $name10 = test_input($_POST["tik"]);

  // ── Llamada a la API Rust en lugar de sqlsrv directo ──────────────────────
  $apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

  $payload = json_encode([
    'Nombre'    => $name1,
    'Correo'    => $name2,
    'Prioridad' => $name3,
    'Empresa'   => $name4,
    'Asunto'    => $name5,
    'Mensaje'   => $name6,
    'Adjuntos'  => $name7,
    'Fecha'     => $name8,
    'Hora'      => $name9,
    'Id_Ticket' => $name10,
  ]);

  $ch = curl_init($apiUrl . '/api/TicketBacros/tickets');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 15);
  $response    = curl_exec($ch);
  $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curl_error  = curl_error($ch);
  curl_close($ch);

  if ($curl_error) {
    error_log("Error cURL al crear ticket: " . $curl_error);
    showErrorAlert("Error de comunicación con el sistema de tickets. Intente nuevamente.");
  } elseif ($http_status < 200 || $http_status >= 300) {
    $decoded = json_decode($response, true);
    $api_msg = isset($decoded['message']) ? $decoded['message'] : "Error HTTP $http_status";
    error_log("API error al crear ticket TI: HTTP $http_status — $response");
    showErrorAlert("Error al guardar el ticket: " . $api_msg);
  } else {
    // Ticket creado exitosamente en la API
    $emailResults = sendConfirmationEmails(
      $name1, $name2, $name3, $name4, $name5,
      $name6, $name7, $name8, $name9, $name10
    );

    if ($emailResults['user'] && $emailResults['admin']) {
      showSuccessAlert($name1, $name2, $name10, true, true);
    } elseif ($emailResults['user'] && !$emailResults['admin']) {
      showSuccessAlert($name1, $name2, $name10, true, false);
    } elseif (!$emailResults['user'] && $emailResults['admin']) {
      showSuccessAlert($name1, $name2, $name10, false, true);
    } else {
      showWarningAlert($name10, "No se pudieron enviar los correos de confirmación.");
    }
  }
}

function sendConfirmationEmails($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
  $results = [
    'user'  => false,
    'admin' => false
  ];

  $results['user']  = sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId);
  $results['admin'] = sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId);

  return $results;
}

function sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
  try {
    $mail = new PHPMailer(true);

    configurarSMTP($mail);

    $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = '✅ Confirmación de Ticket #' . $ticketId . ' - Departamento de TI BacroCorp';
    $mail->Body    = createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId);

    return $mail->send();

  } catch (Exception $e) {
    error_log("Error enviando correo al usuario: " . $e->getMessage());
    return false;
  }
}

function sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId) {
  try {
    $mail = new PHPMailer(true);

    configurarSMTP($mail);

    $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
    $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);

    $mail->isHTML(true);
    $mail->Subject = '🚨 NUEVO TICKET #' . $ticketId . ' - ' . $priority . ' - ' . $subject;
    $mail->Body    = createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId);

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
                  <strong>Estimado/a ' . $name . ',</strong>
              </div>
              <p>Hemos recibido correctamente tu solicitud de soporte técnico y hemos creado un ticket con la siguiente información:</p>

              <div class="ticket-id">Ticket #: ' . $ticketId . '</div>

              <div class="ticket-info">
                  <div class="ticket-detail">
                      <span class="ticket-label">Fecha y Hora:</span>
                      <span>' . $date . ' a las ' . $time . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Departamento:</span>
                      <span>' . $department . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Tipo de Solicitud:</span>
                      <span>' . $subject . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Prioridad:</span>
                      <span class="priority-badge" style="background: ' . $priorityColor . ';">' . $priority . '</span>
                  </div>
              </div>

              <div class="message">
                  <p><strong>Descripción del problema:</strong></p>
                  <p>' . $description . '</p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
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
  $urgencyIcon   = getUrgencyIcon($priority);

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
          .btn {
              display: inline-block;
              padding: 10px 20px;
              margin: 0 10px;
              background: #007bff;
              color: white;
              text-decoration: none;
              border-radius: 5px;
              font-weight: bold;
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
                  ⚠️ Se ha generado un nuevo ticket con prioridad ' . $priority . ' que requiere tu atención inmediata.
              </div>

              <div class="ticket-card">
                  <div style="text-align: center; margin-bottom: 15px;">
                      <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                  </div>

                  <div class="ticket-detail">
                      <span class="ticket-label">Fecha y Hora:</span>
                      <span>' . $date . ' - ' . $time . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Prioridad:</span>
                      <span class="priority-badge">' . $priority . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Tipo de Solicitud:</span>
                      <span><strong>' . $subject . '</strong></span>
                  </div>
              </div>

              <div class="user-info">
                  <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                  <div class="ticket-detail">
                      <span class="ticket-label">Nombre:</span>
                      <span>' . $name . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Correo:</span>
                      <span>' . $email . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Departamento:</span>
                      <span>' . $department . '</span>
                  </div>
              </div>

              <div class="problem-description">
                  <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                  <p><strong>' . $description . '</strong></p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
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
    case 'Alto':  return '#dc3545';
    case 'Medio': return '#ffc107';
    case 'Bajo':  return '#28a745';
    default:      return '#6c757d';
  }
}

function getUrgencyIcon($priority) {
  switch ($priority) {
    case 'Alto':  return '🚨🔥';
    case 'Medio': return '⚠️📋';
    case 'Bajo':  return 'ℹ️📥';
    default:      return '📝';
  }
}

function showSuccessAlert($name, $email, $ticketId, $userEmailSent, $adminEmailSent) {
  $emailStatus = '';

  if ($userEmailSent && $adminEmailSent) {
    $emailStatus = '<p style="color: #28a745; font-weight: bold;">✓ Correos enviados al usuario y al administrador</p>';
  } elseif ($userEmailSent && !$adminEmailSent) {
    $emailStatus = '<p style="color: #ffc107; font-weight: bold;">✓ Correo enviado al usuario<br>⚠ No se pudo enviar al administrador</p>';
  } elseif (!$userEmailSent && $adminEmailSent) {
    $emailStatus = '<p style="color: #ffc107; font-weight: bold;">⚠ No se pudo enviar al usuario<br>✓ Correo enviado al administrador</p>';
  } else {
    $emailStatus = '<p style="color: #dc3545; font-weight: bold;">✗ No se pudieron enviar los correos</p>';
  }

  echo '
  <script>
  const _swalName        = ' . js_value($name) . ';
  const _swalEmail       = ' . js_value($email) . ';
  const _swalTicketId    = ' . js_value($ticketId) . ';
  const _swalEmailStatus = ' . js_value($emailStatus) . ';
  Swal.fire({
      title: "✅ ¡Ticket Creado Exitosamente!",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">🎉</div>
              <h2 style="color: #003366; margin-bottom: 15px;">Solicitud Registrada</h2>

              <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                  <p style="margin: 5px 0;"><strong>Nombre:</strong> ${_swalName}</p>
                  <p style="margin: 5px 0;"><strong>Correo:</strong> ${_swalEmail}</p>
                  <p style="margin: 10px 0;"><strong>Ticket ID:</strong>
                      <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">${_swalTicketId}</span>
                  </p>
              </div>

              ${_swalEmailStatus}

              <p style="color: #666; font-size: 14px; margin-top: 15px;">El ticket ha sido registrado en el sistema correctamente.</p>
          </div>
      `,
      icon: "success",
      confirmButtonColor: "#003366",
      confirmButtonText: "Volver al Inicio",
      allowOutsideClick: false
  }).then((result) => {
      window.location.href = "M/website-menu-05/index.html";
  });
  </script>';
}

function showWarningAlert($ticketId, $error) {
  echo '
  <script>
  const _swalWarnTicketId = ' . js_value($ticketId) . ';
  const _swalWarnError    = ' . js_value($error) . ';
  Swal.fire({
      title: "⚠️ Ticket Creado",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #ffc107; margin-bottom: 20px;">📝</div>
              <h2 style="color: #003366; margin-bottom: 15px;">¡Solicitud Registrada!</h2>

              <p style="margin-bottom: 20px;"><strong>Ticket ID:</strong>
                  <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">${_swalWarnTicketId}</span>
              </p>

              <p style="color: #666; font-size: 14px; margin-bottom: 10px;">El ticket se ha guardado en el sistema correctamente.</p>
              <p style="color: #dc3545; font-size: 14px; font-weight: bold;">Error: ${_swalWarnError}</p>
          </div>
      `,
      icon: "warning",
      confirmButtonColor: "#003366",
      confirmButtonText: "Volver al Inicio",
      allowOutsideClick: false
  }).then((result) => {
      window.location.href = "M/website-menu-05/index.html";
  });
  </script>';
}

function showErrorAlert($message) {
  echo '
  <script>
  const _swalErrMessage = ' . js_value($message) . ';
  Swal.fire({
      title: "❌ Error",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #dc3545; margin-bottom: 20px;">⚠️</div>
              <p style="color: #2c3e50;">${_swalErrMessage}</p>
              <p style="color: #666; font-size: 14px; margin-top: 15px;">Por favor, intente nuevamente.</p>
          </div>
      `,
      icon: "error",
      confirmButtonColor: "#003366",
      confirmButtonText: "Entendido"
  });
  </script>';
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>
