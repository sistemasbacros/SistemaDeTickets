<?php
/**
 * @file Formasignar.php
 * @brief Formulario de asignación de tickets de soporte TI.
 *
 * @description
 * Formulario para la asignación de tickets a técnicos de soporte.
 * Incluye navegación de regreso a la página principal del sistema.
 *
 * Características:
 * - Bootstrap 4.3.1 para estilos
 * - Campos requeridos marcados con asterisco rojo
 * - Link de inicio a la página principal
 *
 * @module Módulo de Asignación
 * @access Público
 *
 * @dependencies
 * - JS CDN: jQuery 3.4.1, Popper.js, Bootstrap 4.3.1
 * - CSS CDN: Bootstrap 4.3.1
 *
 * @external_links
 * - M/website-menu-05/index.html (inicio)
 *
 * @ui_components
 * - Formulario con validación de campos requeridos
 * - Link de navegación a inicio
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */
require_once __DIR__ . '/config.php';
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Ticket soporte TI</title>  <a href="M/website-menu-05/index.html" style="color:black;font-size:20px;font-weight: bold;" >INICIO</a>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <style>
        .bcontent {
            margin-top: 10px;
        }

        .is-required:after {
            content: '*';
            margin-left: 5px;
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<h2>Asignar tickets y tiempos de atención</h2>  <img src="Logo2.png" width="50" height="50" align="right"> </div>
    <div class="container bcontent">

    <form id="formAsignar">
      <!-- Nombre -->
      <div data-mdb-input-init class="form-outline mb-4">
        <input type="text" id="Nombre" name="Nombre" class="form-control" required />
        <label class="form-label" for="Nombre" style="color:black;font-size:20px;font-weight: bold;">Nombre de la persona que levanto el ticket</label>
      </div>

      <!-- Correo -->
      <div data-mdb-input-init class="form-outline mb-4">
        <input type="email" id="elect" name="elect" class="form-control" required />
        <label class="form-label" for="elect" style="color:black;font-size:20px;font-weight: bold;">Correo de la persona que levanto el ticket</label>
      </div>

      <!-- Tipo de ticket (Asunto) -->
      <div data-mdb-input-init class="form-outline mb-4">
        <input type="text" id="Asunto" name="Asunto" class="form-control" />
        <label class="form-label" for="Asunto" style="color:black;font-size:20px;font-weight: bold;">Tipo de ticket levantado</label>
      </div>

      <!-- Empresa / Departamento -->
      <div data-mdb-input-init class="form-outline mb-4">
        <input type="text" id="empre" name="empre" class="form-control" />
        <label class="form-label" for="empre" style="color:black;font-size:20px;font-weight: bold;">Clasificación de ticket levantado</label>
      </div>

      <!-- Mensaje -->
      <div data-mdb-input-init class="form-outline mb-4">
        <textarea class="form-control" id="men" name="men" rows="4"></textarea>
        <label class="form-label" for="men" style="color:black;font-size:20px;font-weight: bold;">Mensaje de la persona que levanto el ticket</label>
      </div>

      <!-- Adjuntos / Tiempo estimado -->
      <div data-mdb-input-init class="form-outline mb-4">
        <input type="text" id="adj" name="adj" class="form-control" />
        <label class="form-label" for="adj" style="color:black;font-size:20px;font-weight: bold;">Tiempo estimado para atender el ticket</label>
      </div>

      <!-- Responsable (Prioridad en la API) -->
      <label for="prio" style="color:black;font-size:20px;font-weight: bold;">Responsable:</label>
      <select class="form-select mb-3" id="prio" name="prio" aria-label="Default select example">
        <option value="">Selecciona</option>
        <option value="Bajo">Victor Juarez</option>
        <option value="Medio">Luis Becerril</option>
        <option value="Alto">Ariel Antonio</option>
        <option value="Alto">Alfredo Rosales</option>
      </select>

      <!-- Estatus (guardado en Adjuntos extras; no mapeado al INSERT original) -->
      <label for="estatus" style="color:black;font-size:20px;font-weight: bold;">Estatus:</label>
      <select class="form-select mb-3" id="estatus" name="estatus" aria-label="Default select example">
        <option value="">Selecciona</option>
        <option value="En proceso">En proceso</option>
        <option value="Pausa">Pausa</option>
        <option value="Atendido">Atendido</option>
      </select>

      <!-- Campos ocultos generados por JS -->
      <input type="hidden" id="fecha" name="fecha" />
      <input type="hidden" id="Hora" name="Hora" />
      <input type="hidden" id="tik" name="tik" />

      <div class="form-check d-flex justify-content-center mb-4"></div>

      <!-- Submit button -->
      <button type="button" id="btnAsignar" class="btn btn-primary">Asignar</button>
    </form>

    </div>
</body>
</html>


<script>
// ── Fecha y hora actuales ──────────────────────────────────────────────────
var hoy = new Date();
var fecha = hoy.getDate() + '/' + (hoy.getMonth() + 1) + '/' + hoy.getFullYear();
var hora  = hoy.getHours() + ':' + hoy.getMinutes() + ':' + hoy.getSeconds();
var fechaYHora = "Fol" + " " + fecha + ' ' + hora;

$(document).ready(function () {
    // Rellenar campos ocultos al cargar
    document.getElementById('fecha').value = fecha;
    document.getElementById('Hora').value  = hora;
    document.getElementById('tik').value   = fechaYHora + ':' + hoy.getMilliseconds();

    // ── Envío via fetch → API Rust ─────────────────────────────────────────
    document.getElementById('btnAsignar').addEventListener('click', function () {
        var nombre   = document.getElementById('Nombre').value.trim();
        var correo   = document.getElementById('elect').value.trim();

        if (!nombre || !correo) {
            alert('Por favor completa los campos de Nombre y Correo.');
            return;
        }

        var payload = {
            Nombre:    nombre,
            Correo:    correo,
            Prioridad: document.getElementById('prio').value   || 'Bajo',
            Empresa:   document.getElementById('empre').value  || '',
            Asunto:    document.getElementById('Asunto').value || '',
            Mensaje:   document.getElementById('men').value    || null,
            Adjuntos:  document.getElementById('adj').value    || null,
            Fecha:     document.getElementById('fecha').value,
            Hora:      document.getElementById('Hora').value,
            Id_Ticket: document.getElementById('tik').value
        };

        var apiUrl = <?php echo json_encode($apiUrl); ?>;

        fetch(apiUrl + '/api/TicketBacros/tickets', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, status: response.status, data: data };
            });
        })
        .then(function (result) {
            if (result.ok && result.data.success) {
                alert('Se levanto de forma correcta tu ticket');
                document.getElementById('formAsignar').reset();
                // Regenerar folio tras reseteo
                var n = new Date();
                var f2 = n.getDate() + '/' + (n.getMonth() + 1) + '/' + n.getFullYear();
                var h2 = n.getHours() + ':' + n.getMinutes() + ':' + n.getSeconds();
                var fh2 = "Fol " + f2 + ' ' + h2;
                document.getElementById('fecha').value = f2;
                document.getElementById('Hora').value  = h2;
                document.getElementById('tik').value   = fh2 + ':' + n.getMilliseconds();
            } else {
                var msg = (result.data && result.data.message)
                    ? result.data.message
                    : 'Error al registrar el ticket (HTTP ' + result.status + ')';
                alert('Error: ' + msg);
            }
        })
        .catch(function (err) {
            console.error('Error de red al enviar el ticket:', err);
            alert('No se pudo conectar con el servidor. Intenta de nuevo.');
        });
    });
});
</script>
