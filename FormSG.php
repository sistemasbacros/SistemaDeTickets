<?php
/**
 * @file FormSG.php
 * @brief Formulario de creación de tickets para Servicios Generales.
 *
 * @description
 * Formulario de captura de tickets del módulo de Servicios Generales.
 * Permite a los usuarios reportar solicitudes de mantenimiento, limpieza,
 * reparaciones y otros servicios generales de la empresa.
 *
 * Migrado de conexión directa a SQL Server a consumo de API REST:
 *   - POST /api/TicketBacros/tickets-sg  →  crear ticket SG
 *   - GET  /api/TicketBacros/personal    →  lista de nombres para el selector
 *
 * NOTA: El origen de datos del selector de nombres se cambió de
 * [BASENUEVA].[dbo].[vwLBSContactList] (Comercial) a la tabla [conped]
 * (base de datos Ticket), expuesta por el endpoint /api/TicketBacros/personal.
 * Si se requiere volver a usar vwLBSContactList, será necesario agregar un
 * endpoint dedicado en el backend Rust.
 *
 * @module Módulo de Servicios Generales
 * @access Público (sin autenticación)
 *
 * @dependencies
 *   - CSS CDN: Google Fonts (Inter), SweetAlert2
 *   - Backend: API Rust (puerto 3000)
 *
 * @database
 *   - Tabla destino: TicketsSG (via API)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_client.php';

// Pre-cargar lista de personal vía api_call() (endpoint protegido con JWT).
// Se inyecta en el HTML para que el JS no tenga que llamar al endpoint
// protegido directamente desde el navegador (no tiene Authorization header).
$personalPreload = [];
$_resp = api_call('GET', '/api/TicketBacros/personal');
if ($_resp['ok']) {
    $_json = $_resp['json'] ?? [];
    if (isset($_json['data']) && is_array($_json['data'])) {
        $personalPreload = $_json['data'];
    } elseif (is_array($_json)) {
        $personalPreload = $_json;
    }
}

/**
 * Determina la URL base de la API Rust.
 *
 * Prioridad:
 *   1. Variable de entorno API_URL
 *   2. Clave API_URL en el archivo .env
 *   3. host.docker.internal:3000  (dentro de contenedores Docker)
 *   4. localhost:3000              (entorno local sin Docker)
 */
function getApiUrl(): string
{
    // 1. Variable de entorno ya cargada
    $fromEnv = getenv('API_URL');
    if ($fromEnv) {
        return rtrim($fromEnv, '/');
    }

    // 2. Fallback: detectar si estamos en Docker (host.docker.internal resolvible)
    //    En producción se espera que API_URL esté definida en el .env.
    $dockerHost = 'host.docker.internal';
    // phpcs:ignore
    $resolved = @file_get_contents("http://{$dockerHost}:3000/health");
    if ($resolved !== false) {
        return "http://{$dockerHost}:3000";
    }

    // 3. Localhost para entornos locales sin Docker
    return 'http://localhost:3000';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SERVICIOS GENERALES</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

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

    /* Logo a la parte superior derecha */
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

    button[type="submit"]:disabled {
      background: #999;
      cursor: not-allowed;
      transform: none;
    }

    @media (max-width: 640px) {
      .container {
        padding: 25px 20px;
      }

      h2 {
        font-size: 2rem;
      }
    }

    /* Botón flotante - esquina superior izquierda */
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

    /* Indicador de carga del selector de nombres */
    .select-loading {
      font-size: 0.85rem;
      color: #888;
      margin-top: 4px;
      display: none;
    }

    .select-loading.visible {
      display: block;
    }
  </style>
</head>
<body>
  <button id="homeButton" title="Inicio">&#x1F3E0;</button>
  <h2>SERVICIOS GENERALES</h2>
  <img src="Logo2.png" alt="Logo" class="logo" />

  <div class="container">
    <form id="sgForm">
      <div class="form-group">
        <label for="Nombre">Nombre</label>
        <select id="Nombre" name="Nombre" required>
          <option value="" disabled selected>Cargando lista...</option>
        </select>
        <span class="select-loading" id="loadingMsg">Cargando personal...</span>
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
        <label for="empre">Ubicación</label>
        <select id="empre" name="empre" required>
          <option value="PRIMERO">PRIMER PISO</option>
          <option value="SEGUNDO">SEGUNDO PISO</option>
          <option value="TERCER">TERCER PISO</option>
          <option value="CASA">CASA</option>
          <option value="PATIO">PATIO</option>
          <option value="VIGILANCIA">VIGILANCIA</option>
        </select>
      </div>

      <div class="form-group">
        <label for="Asunto">Tipo</label>
        <select id="Asunto" name="Asunto" required>
          <option value="MANTENIMIENTO_GENERAL">MANTENIMIENTO EDIFICIO</option>
          <option value="INTENDENDCIA">INTENDENDCIA</option>
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

      <button type="submit" id="submitBtn">Solicitar</button>
    </form>
  </div>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // ──────────────────────────────────────────────────────────────
    // URL base de la API — debe coincidir con la URL del backend Rust.
    // En producción se puede definir la variable de entorno API_URL.
    // ──────────────────────────────────────────────────────────────
    const API_URL = <?php echo json_encode(getApiUrl()); ?>;

    // ── Navegación ────────────────────────────────────────────────
    document.getElementById('homeButton').addEventListener('click', function () {
      window.location.href = 'MenSG.php';
    });

    // ── Fecha y hora automáticas ──────────────────────────────────
    function pad(n) { return n < 10 ? '0' + n : n; }

    function updateDateTime() {
      const now = new Date();
      // Formato YYYY-MM-DD (consistente con el backend)
      const formattedDate = now.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      }).split('/').reverse().join('-');

      document.getElementById('fecha').value = formattedDate;
      document.getElementById('Hora').value =
        pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ── Generar ID de ticket ──────────────────────────────────────
    function generateTicketID() {
      const ticket = document.getElementById('tik');
      if (!ticket.value) {
        ticket.value = 'TI-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
      }
    }

    generateTicketID();

    // ── Cargar lista de personal (pre-inyectada desde PHP) ────────
    // Fuente: GET /api/TicketBacros/personal  (tabla conped, BD Ticket)
    // El endpoint requiere Bearer JWT, por lo que la página PHP lo
    // consulta server-side con api_call() (que reenvía $_SESSION['api_jwt'])
    // y embebe el resultado en window.PERSONAL_DATA.
    const PERSONAL_DATA = <?php echo json_encode($personalPreload, JSON_UNESCAPED_UNICODE); ?>;

    function cargarPersonal() {
      const select = document.getElementById('Nombre');
      const loadingMsg = document.getElementById('loadingMsg');

      loadingMsg.classList.add('visible');

      try {
        // Limpiar opciones previas
        select.innerHTML = '';

        // Opción por defecto vacía
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- Selecciona tu nombre --';
        defaultOpt.disabled = true;
        defaultOpt.selected = true;
        select.appendChild(defaultOpt);

        if (!PERSONAL_DATA || PERSONAL_DATA.length === 0) {
          const emptyOpt = document.createElement('option');
          emptyOpt.value = '';
          emptyOpt.textContent = 'No hay personal registrado';
          emptyOpt.disabled = true;
          select.appendChild(emptyOpt);
          return;
        }

        PERSONAL_DATA.forEach(function (persona) {
          const nombre = persona.nombre || persona.Nombre || '';
          if (!nombre) return;
          const option = document.createElement('option');
          // El valor usa el nombre completo en minúsculas con guiones bajos
          // para mantener compatibilidad con el comportamiento original.
          option.value = nombre.toLowerCase().replace(/\s+/g, '_');
          option.textContent = nombre;
          select.appendChild(option);
        });

      } catch (err) {
        console.error('Error cargando personal:', err);
        select.innerHTML = '';
        const errorOpt = document.createElement('option');
        errorOpt.value = '';
        errorOpt.textContent = 'Error cargando lista';
        errorOpt.disabled = true;
        errorOpt.selected = true;
        select.appendChild(errorOpt);

        Swal.fire({
          icon: 'warning',
          title: 'Aviso',
          text: 'No se pudo cargar la lista de personal. Verifique la conexión con el servidor.',
          confirmButtonColor: '#003366',
          confirmButtonText: 'Entendido'
        });
      } finally {
        loadingMsg.classList.remove('visible');
      }
    }

    cargarPersonal();

    // ── Enviar formulario a la API ─────────────────────────────────
    document.getElementById('sgForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const submitBtn = document.getElementById('submitBtn');

      // Validar que se seleccionó un nombre real
      const nombreSelect = document.getElementById('Nombre');
      if (!nombreSelect.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Campo requerido',
          text: 'Por favor selecciona tu nombre de la lista.',
          confirmButtonColor: '#003366',
          confirmButtonText: 'Entendido'
        });
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando...';

      // Obtener el texto visible del selector (nombre completo)
      const nombreTexto = nombreSelect.options[nombreSelect.selectedIndex].textContent;

      const payload = {
        Nombre:    nombreTexto,
        Prioridad: document.getElementById('prio').value,
        Empresa:   document.getElementById('empre').value,
        Asunto:    document.getElementById('Asunto').value,
        Adjuntos:  document.getElementById('adj').value,
        Mensaje:   document.getElementById('men').value,
        Fecha:     document.getElementById('fecha').value,
        Hora:      document.getElementById('Hora').value,
        Id_Ticket: document.getElementById('tik').value
      };

      try {
        const response = await fetch(API_URL + '/api/TicketBacros/tickets-sg', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Ticket Creado',
            html:
              '<div style="text-align:center;padding:10px">' +
              '<p style="font-size:1rem;margin-bottom:12px">Se levant&oacute; correctamente tu ticket</p>' +
              '<span style="background:#003366;color:#fff;padding:8px 18px;border-radius:20px;font-size:1.1rem;font-weight:bold;">' +
              escapeHtml(payload.Id_Ticket) +
              '</span>' +
              '</div>',
            confirmButtonColor: '#003366',
            confirmButtonText: 'Volver al Inicio',
            allowOutsideClick: false
          }).then(function () {
            window.location.href = 'MenSG.php';
          });
        } else {
          const errorMsg = result.message || 'Error desconocido del servidor.';
          Swal.fire({
            icon: 'error',
            title: 'Error al crear el ticket',
            text: errorMsg,
            confirmButtonColor: '#003366',
            confirmButtonText: 'Entendido'
          });
          submitBtn.disabled = false;
          submitBtn.textContent = 'Solicitar';
        }

      } catch (err) {
        console.error('Error enviando ticket SG:', err);
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudo comunicar con el servidor. Verifique su conexión e intente nuevamente.',
          confirmButtonColor: '#003366',
          confirmButtonText: 'Entendido'
        });
        submitBtn.disabled = false;
        submitBtn.textContent = 'Solicitar';
      }
    });

    // ── Utilidad: escapar HTML para mostrar valores en SweetAlert ─
    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }
  </script>
</body>
</html>
