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
// config.php carga las variables de entorno (.env) usadas por getenv('PDF_API_URL')
require_once __DIR__ . '/config.php';

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
    showErrorAlert("Error al guardar el ticket: " . htmlspecialchars($api_msg));
  } else {
    // Ticket creado exitosamente en la API.
    // Los correos de confirmación (solicitante y admin) los envía el backend Rust.
    showSuccessAlert($name1, $name2, $name10);
  }
}

function showSuccessAlert($name, $email, $ticketId) {
  echo '
  <script>
  Swal.fire({
      title: "✅ ¡Ticket Creado Exitosamente!",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">🎉</div>
              <h2 style="color: #003366; margin-bottom: 15px;">Solicitud Registrada</h2>

              <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                  <p style="margin: 5px 0;"><strong>Nombre:</strong> ' . $name . '</p>
                  <p style="margin: 5px 0;"><strong>Correo:</strong> ' . $email . '</p>
                  <p style="margin: 10px 0;"><strong>Ticket ID:</strong>
                      <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">' . $ticketId . '</span>
                  </p>
              </div>

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

function showErrorAlert($message) {
  echo '
  <script>
  Swal.fire({
      title: "❌ Error",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #dc3545; margin-bottom: 20px;">⚠️</div>
              <p style="color: #2c3e50;">' . $message . '</p>
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
