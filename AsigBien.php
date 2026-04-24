<?php
/**
 * @file AsigBien.php
 * @brief Módulo de asignación de bienes con datos de sesión.
 *
 * @description
 * Componente que procesa datos almacenados en la variable de sesión
 * $_SESSION['superhero'] para gestión de asignación de bienes.
 * Extrae los valores de la sesión a un array local para su procesamiento.
 *
 * Este archivo es parte de un flujo multi-paso donde los datos se
 * almacenan temporalmente en la sesión entre páginas.
 *
 * Nota: El nombre 'superhero' de la variable de sesión parece ser un
 * placeholder de desarrollo; considerar renombrar a algo más descriptivo.
 *
 * Variantes relacionadas:
 * - AsigBien1.php: Variante 1 del proceso
 * - AsigBien2.php: Variante 2 del proceso
 *
 * @module Módulo de Asignación de Bienes
 * @access Requiere sesión activa con datos
 *
 * @dependencies
 * - PHP: session_start()
 *
 * @session
 * - $_SESSION['superhero']: Array con datos para asignación
 *   Los datos se extraen a $array1 para procesamiento local
 *
 * @variables
 * - $array1: Array local con valores de la sesión
 *
 * @todo
 * - Renombrar $_SESSION['superhero'] a nombre descriptivo
 * - Agregar validación de datos de sesión
 * - Documentar estructura esperada del array
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
  margin-left:5px;
  color: red;
  font-weight: bold;
}


    </style>
</head>
<body>
<h2>Asignar tickets y tiempos de atención</h2>  <img src="Logo2.png" width="50" height="50" align="right"> </div>
    <div class="container bcontent">
<form id="formAsignar" method="post" action="#">

  <!-- Name input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="np" name="np" class="form-control"  value="<?php echo htmlspecialchars($array1[0]) ?>"/>
    <label class="form-label" for="form4Example1"style="color:black;font-size:20px;font-weight: bold;" >Nombre de la persona que levanto el ticket</label>
  </div>

  <!-- Email input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="email" id="correo" name="correo"  class="form-control"  value="<?php echo htmlspecialchars($array1[1]) ?>"/>
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Correo de la persona que levanto el ticket</label>
  </div>

    <!-- Tipo de ticket -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="tipdeticket" name ="tipdeticket" class="form-control" value="<?php echo htmlspecialchars($array1[4]) ?>"/>
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Tipo de ticket levantado</label>
  </div>

    <!-- Clasificación -->
  <div data-mdb-input-init class="form-outline mb-4" >
    <input type="text" id="clasif" name="clasif" class="form-control" value="<?php echo htmlspecialchars($array1[4]) ?>"/>
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Clasificación de ticket levantado</label>
  </div>

  <!-- Message input -->
  <div data-mdb-input-init class="form-outline mb-4" >
    <textarea class="form-control" id="mensaje" name="mensaje" rows="4" ><?php echo htmlspecialchars($array1[5]) ?></textarea>
    <label class="form-label" for="form4Example3"style="color:black;font-size:20px;font-weight: bold;">Mensaje de la persona que levanto el ticket</label>
  </div>

      <!-- Tiempo estimado -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="tiempo" name="tiempo" class="form-control" value="<?php echo htmlspecialchars($array1[16]) ?>"/>
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Tiempo estimado para atender el ticket</label>
  </div>


  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Responsable:</label>
<select class="form-select" id="resp" name='resp'  aria-label="Default select example">
  <option value="Selecciona">Selecciona</option>
    <option value="Luis Antonio Romero López">Luis Antonio Romero López</option>
  <option value="Frank">Frank</option>
  <option value="Luis Becerril">Luis Becerril</option>
  <option value="Ariel Antonio">Ariel Antonio</option>
    <option value="Alfredo Rosales">Alfredo Rosales</option>
</select>



  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Estatus:</label>
<select class="form-select" id="Est" name='Est'  aria-label="Default select example">
  <option value="Selecciona">Selecciona</option>
  <option value="En proceso">En proceso</option>
  <option value="Atendido">Atendido</option>
</select>


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

// ID del ticket tomado de la sesión PHP
var ticketId = "<?php echo addslashes($array1[9]) ?>";

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


// Interceptar el envío del formulario y llamar a la API REST
document.getElementById('formAsignar').addEventListener('submit', function(e) {
    e.preventDefault();

    var responsable = document.getElementById('resp').value;
    var estatus     = document.getElementById('Est').value;

    if (responsable === 'Selecciona' || responsable === '') {
        alert('Debe seleccionar un responsable.');
        return;
    }
    if (estatus === 'Selecciona' || estatus === '') {
        alert('Debe seleccionar un estatus.');
        return;
    }
    if (!ticketId || ticketId.trim() === '') {
        alert('No se encontró el ID del ticket en la sesión.');
        return;
    }

    var body = {
        ticketId:      ticketId.trim(),
        asignadoA:     responsable,
        nuevoEstatus:  estatus
    };

    fetch('http://host.docker.internal:3000/api/TicketBacros/tickets/' + encodeURIComponent(ticketId.trim()), {
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
            alert('No se pudo asignar el ticket: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(function(err) {
        console.error('Error al actualizar ticket:', err);
        alert('Error al conectar con el servidor: ' + err.message);
    });
});
	</script>
