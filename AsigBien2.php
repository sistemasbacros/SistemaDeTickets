<?php
/**
 * @file AsigBien2.php
 * @brief Módulo de asignación de bienes - Variante 2.
 *
 * @description
 * Variante 2 del módulo de asignación de bienes. Registra la fecha
 * y hora de ingreso del vehículo al taller mediante la API REST.
 *
 * Archivo más enfocado que las otras variantes: únicamente actualiza
 * Fecha_Ingreso y HORA_Ingreso en el registro vehicular.
 *
 * @module Módulo de Asignación de Bienes
 * @access Requiere sesión activa con datos
 *
 * @dependencies
 * - PHP: session_start()
 *
 * @session
 * - $_SESSION['superhero']: Array con datos de asignación
 *
 * @see AsigBien.php, AsigBien1.php
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0 - Migrado a API REST (sin SQL injection)
 * @since 2024
 */

require_once __DIR__ . '/auth_check.php';
session_start(); // this NEEDS TO BE AT THE TOP of the page before any output etc
  // echo $_SESSION['superhero'];

$array1 = [];

foreach($_SESSION['superhero'] as $valor)
{
  // echo "\n- ".$valor;
  array_push($array1, $valor);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>

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
  margin-left:5px;
  color: red;
  font-weight: bold;
}


    </style>
</head>
<body>
<h2>Asigna tu fecha de ingreso</h2>  <img src="Logo2.png" width="50" height="50" align="right"> </div>
    <div class="container bcontent">
<form id="formAsignar2" method="post" action="#">

        <div class="form-group">
            <label for="startDate" class="col-sm-3 col-form-label is-required"  style="color:black;font-size:20px;font-weight: bold;">Fecha</label>
            <input id="fecha" name="fecha" class="form-control" type="text" required readonly>
            <span id="startDateSelected"></span>
        </div>
       <div class="form-group">
    <label for="exampleFormControlInput1" class="col-sm-3 col-form-label is-required"  style="color:black;font-size:20px;font-weight: bold;">Hora</label>
    <input type="text" class="form-control" id="Hora" name='Hora'  required readonly>
  </div>
  <!-- Checkbox -->
  <div class="form-check d-flex justify-content-center mb-4">

  </div>

  <!-- Submit button -->
  <button type="submit" class="btn btn-primary">Asignar</button>
</form>

<!-- Mensaje de resultado -->
<div id="mensajeResultado" style="margin-top:15px;"></div>

    </div>
</body>
</html>


	<script>
var hoy = new Date();
var fecha = hoy.getDate() + '/' + ( hoy.getMonth() + 1 ) + '/' + hoy.getFullYear();
var hora = hoy.getHours() + ':' + hoy.getMinutes() + ':' + hoy.getSeconds();
var fechaYHora =  "Fol"+ " " + fecha + ' ' + hora;

// IDT del vehículo tomado de la sesión PHP ($array1[0])
var vehiculoIdt = "<?php echo addslashes($array1[0]) ?>";

  document.getElementById('fecha').value = fecha

document.getElementById('Hora').value = hora


$(document).ready(function() {
    var todayDate = (function(){
      var d = new Date();
      var day = d.getDate();
      day =  day > 9 ? day : '0' + day ;
      var month = (d.getMonth() + 1);
      month = month > 9 ? month : '0' + month;
      var _value =  day + '/' + month  + '/' + d.getFullYear();
      return _value;
    })();
    // Solo inicializar datepicker si está disponible
    if ($.fn.datepicker) {
      $('#fecha').datepicker({
        format: 'dd/mm/yyyy',
        value: todayDate
      });
    }
});


/**
 * Convierte una fecha en formato dd/mm/yyyy a yyyy-mm-dd
 * para enviarse a la API REST.
 */
function convertirFecha(fechaDDMMYYYY) {
    var partes = fechaDDMMYYYY.split('/');
    if (partes.length === 3) {
        return partes[2] + '-' + partes[1] + '-' + partes[0];
    }
    return fechaDDMMYYYY;
}


// Interceptar el envío del formulario y llamar a la API REST
document.getElementById('formAsignar2').addEventListener('submit', function(e) {
    e.preventDefault();

    var fechaDisplay = document.getElementById('fecha').value;
    var horaDisplay  = document.getElementById('Hora').value;

    if (!vehiculoIdt || vehiculoIdt.trim() === '') {
        alert('No se encontró el IDT del vehículo en la sesión.');
        return;
    }

    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd para la API
    var fechaISO = convertirFecha(fechaDisplay);

    // Solo actualizamos fecha_ingreso y hora_ingreso; los demás campos
    // se envían vacíos y el servicio Rust los interpretará como sin cambio
    // (el UPDATE_VEHICULO en el repositorio actualiza todos los campos,
    //  por lo que enviamos strings vacíos para los campos que no aplican aquí)
    var body = {
        idt:          vehiculoIdt.trim(),
        fecha_ingreso: fechaISO,
        hora_ingreso:  horaDisplay
    };

    fetch('http://host.docker.internal:3000/api/TicketBacros/vehiculos/' + encodeURIComponent(vehiculoIdt.trim()), {
        method:  'PUT',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body)
    })
    .then(function(response) {
        if (!response.ok) {
            return response.text().then(function(text) {
                throw new Error('Error ' + response.status + ': ' + text);
            });
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            alert('Se asigno el ticket');
        } else {
            alert('No se pudo registrar la fecha de ingreso: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(function(err) {
        console.error('Error al actualizar fecha de ingreso vehicular:', err);
        alert('Error al conectar con el servidor: ' + err.message);
    });
});
	</script>
