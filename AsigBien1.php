<?php
/**
 * @file AsigBien1.php
 * @brief Módulo de asignación de bienes - Variante 1.
 *
 * @description
 * Variante 1 del módulo de asignación de bienes. Procesa datos
 * almacenados en $_SESSION['superhero'] para la gestión de
 * asignaciones de equipos o recursos.
 *
 * Funcionalidad similar a AsigBien.php pero posiblemente con
 * diferencias en el renderizado o procesamiento posterior.
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
 * @see AsigBien.php, AsigBien2.php
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0 - Migrado a API REST (sin SQL injection)
 * @since 2024
 */

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
<h2>Asigna tickets de mantenimiento de vehícular</h2>  <img src="Logo2.png" width="50" height="50" align="right"> </div>
    <div class="container bcontent">
<form id="formAsignar1" method="post" action="#">

    <label class="form-label" for="form4Example1"style="color:black;font-size:20px;font-weight: bold;" >PLACAS</label>
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="np" name="np" class="form-control"  value="<?php echo htmlspecialchars($array1[1]) ?>"/>
  </div>

    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">TIPO DE MANTENIMIENTO</label>
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="correo" name="correo"  class="form-control"  value="<?php echo htmlspecialchars($array1[2]) ?>"/>
  </div>

    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">NOMBRE DE LA PERSONA QUE LEVANTO SU TICKET</label>
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="tipdeticket" name ="tipdeticket" class="form-control" value="<?php echo htmlspecialchars($array1[5]) ?>"/>
  </div>


    <label class="form-label" for="form4Example3"style="color:black;font-size:20px;font-weight: bold;">Mensaje de la persona que levanto el ticket</label>
  <div data-mdb-input-init class="form-outline mb-4" >
    <textarea class="form-control" id="mensaje" name="mensaje" rows="4" ></textarea>
  </div>

      <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Tiempo estimado para atender el ticket</label>
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="tiempo" name="tiempo" class="form-control" />
  </div>


  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Responsable:</label>
<select class="form-select" id="resp" name='resp'  aria-label="Default select example">
  <option value="Selecciona">Selecciona</option>
    <option value="LEONARDO PEREZ MORALES">LEONARDO PEREZ MORALES</option>
</select>

<br>

  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Estatus:</label>
<select class="form-select" id="Est" name='Est'  aria-label="Default select example">
  <option value="Selecciona">Selecciona</option>
  <option value="En proceso">En proceso</option>
  <option value="Pausa">Pausa</option>
  <option value="Atendido">Atendido</option>
</select>

<br>

    <label class="form-label" for="form4Example3"style="color:black;font-size:20px;font-weight: bold;">Diagnóstico</label>
  <div data-mdb-input-init class="form-outline mb-4" >
    <textarea class="form-control" id="diagnostico" name="diagnostico" rows="4" ></textarea>
  </div>


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
document.getElementById('formAsignar1').addEventListener('submit', function(e) {
    e.preventDefault();

    var responsable  = document.getElementById('resp').value;
    var estatus      = document.getElementById('Est').value;
    var comentarios  = document.getElementById('mensaje').value;
    var tiempo       = document.getElementById('tiempo').value;
    var diagnostico  = document.getElementById('diagnostico').value;
    var fechaDisplay = document.getElementById('fecha').value;
    var horaDisplay  = document.getElementById('Hora').value;

    if (responsable === 'Selecciona' || responsable === '') {
        alert('Debe seleccionar un responsable.');
        return;
    }
    if (estatus === 'Selecciona' || estatus === '') {
        alert('Debe seleccionar un estatus.');
        return;
    }
    if (!vehiculoIdt || vehiculoIdt.trim() === '') {
        alert('No se encontró el IDT del vehículo en la sesión.');
        return;
    }

    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd para la API
    var fechaISO = convertirFecha(fechaDisplay);

    var body = {
        idt:          vehiculoIdt.trim(),
        asignado:     responsable,
        estado:       estatus,
        diagnostico:  diagnostico,
        comentarios:  comentarios,
        fecha_res:    fechaISO,
        hora_res:     horaDisplay
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
            alert('No se pudo asignar el ticket vehicular: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(function(err) {
        console.error('Error al actualizar ticket vehicular:', err);
        alert('Error al conectar con el servidor: ' + err.message);
    });
});
	</script>
