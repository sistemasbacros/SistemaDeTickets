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
 * - http://192.168.100.95/TicketBacros/M/website-menu-05/index.html (inicio)
 *
 * @ui_components
 * - Formulario con validación de campos requeridos
 * - Link de navegación a inicio
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */
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

  <!-- Name input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="text" id="form4Example1" class="form-control" />
    <label class="form-label" for="form4Example1"style="color:black;font-size:20px;font-weight: bold;" >Nombre de la persona que levanto el ticket</label>
  </div>

  <!-- Email input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="email" id="form4Example2" class="form-control" />
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Correo de la persona que levanto el ticket</label>
  </div>
  
    <!-- Email input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="email" id="form4Example2" class="form-control" />
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Tipo de ticket levantado</label>
  </div>
  
    <!-- Email input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="email" id="form4Example2" class="form-control" />
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Clasificación de ticket levantado</label>
  </div>

  <!-- Message input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <textarea class="form-control" id="form4Example3" rows="4"></textarea>
    <label class="form-label" for="form4Example3"style="color:black;font-size:20px;font-weight: bold;">Mensaje de la persona que levanto el ticket</label>
  </div>
  
      <!-- Email input -->
  <div data-mdb-input-init class="form-outline mb-4">
    <input type="email" id="form4Example2" class="form-control" />
    <label class="form-label" for="form4Example2"style="color:black;font-size:20px;font-weight: bold;">Tiempo estimado para atender el ticket</label>
  </div>
  
  
  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Responsable:</label>  
<select class="form-select" id="prio" name= 'prio'  aria-label="Default select example">
  <option value="Bajo">Selecciona</option>
  <option value="Bajo">Victor Juarez</option>
  <option value="Medio">Luis Becerril</option>
  <option value="Alto">Ariel Antonio</option>
    <option value="Alto">Alfredo Rosales</option>
</select>



  <label for="exampleFormControlInput1" style="color:black;font-size:20px;font-weight: bold;">Estatus:</label>  
<select class="form-select" id="prio" name= 'prio'  aria-label="Default select example">
  <option value="Bajo">Selecciona</option>
  <option value="Bajo">En proceso</option>
  <option value="Medio">Pausa</option>
  <option value="Alto">Atendido</option>
</select>

  <!-- Checkbox -->
  <div class="form-check d-flex justify-content-center mb-4">

  </div>

  <!-- Submit button -->
  <button type="submit" class="btn btn-primary">Asignar</button>
</form>

    </div>
</body>
</html>


	<script>
var hoy = new Date();
var fecha = hoy.getDate() + '/' + ( hoy.getMonth() + 1 ) + '/' + hoy.getFullYear();
var hora = hoy.getHours() + ':' + hoy.getMinutes() + ':' + hoy.getSeconds(); 
var fechaYHora =  "Fol"+ " " + fecha + ' ' + hora;

// alert(fecha)
// alert(hora)



///alert(fechaYHora)
   ///document.getElementById('email').value = fechaYHora  
   
   
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
    $('#fecha').datepicker({
      format: 'dd/mm/yyyy',
      value: todayDate
   });
});
   
 
  document.getElementById('fecha').value = fecha   
 
document.getElementById('Hora').value = hora   
   
document.getElementById("tik").value = fechaYHora+':'+hoy.getMilliseconds();
	</script>




<?php

// $pedido = $name = $email = $gender = $comment = $website = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	
	 // if (empty( $name1)) {
    // echo '$var es o bien 0, vacía, o no se encuentra definida en absoluto';
// }else {}

	
	
  $name1 = test_input($_POST["Nombre"]); /// folio
  $name2 = test_input($_POST["elect"]); /// folio
  $name3 = test_input($_POST["prio"]); /// folio
  $name4 = test_input($_POST["empre"]); /// folio
  $name5 = test_input($_POST["Asunto"]); /// folio
  $name6 = test_input($_POST["men"]); /// folio
  $name7 = test_input($_POST["adj"]); /// folio
  $name8 = test_input($_POST["fecha"]); /// folio
  $name9 = test_input($_POST["Hora"]); /// folio
  $name10 = test_input($_POST["tik"]); /// folio
  
  
  // $name11 = test_input($_POST["color"]); /// folio
  // $name12 = test_input($_POST["Unacara"]); /// folio
  // $name13 = test_input($_POST["Doscara"]); /// folio
  // $name14 = test_input($_POST["ruta"]); /// folio
  // $name15 = test_input($_POST["com"]); /// folio
  
  // $namep = test_input($_POST["NombreP"]); /// Nombre Personal
  // $nameelec = test_input($_POST["CE"]); /// Correo electronico
  
  // $email = test_input($_POST["departamento"]);  /// departamento
  // $website = test_input($_POST["birthday"]);  // Fecha
  // $comment = test_input($_POST["documentacion"]);
  // $gender = test_input($_POST["ND"]);
  // $pedido = test_input($_POST["Empresa"]);
  // $pedido1 = test_input($_POST["Prioridad"]);
  // $pedido2 = test_input($_POST["Ad"]); /// este no
  
 // ////////////////////// Agregar variables adjuste
// $pedido31 = test_input($_POST["B/N"]);
// $pedido32 = test_input($_POST["Color"]);
// $pedido33 = test_input($_POST["Unacara"]);
// $pedido34 = test_input($_POST["Doscaras"]); 

 // $pedido3 = test_input($_POST["Estatus"]);
  // $pedido5 = test_input($_POST["ralma"]);
  // $pedido4 = test_input($_POST["comment"]);
  
  
  
// echo "<h2>Datos enviados:</h2>";
// echo $name;
// echo "<br>";
// echo $name1;
// echo "<br>";
// echo $name2;
// echo "<br>";
// echo $name3;
// echo "<br>";
// echo $name4;
// echo "<br>";
// echo $name5;
// echo "<br>";
// echo $name6;
// echo "<br>";
// echo $name7;
// echo "<br>";
// echo $name8;
// echo "<br>";
// echo $name9;
// echo "<br>";
// echo $name10;
// echo "<br>";
// echo $name11;
// echo "<br>";
// echo $name12;
// echo "<br>";
// echo $name13;
// echo "<br>";
// echo $name14;
// echo "<br>";
// echo $name15;
// echo "<br>";


////////////////// Update

////////////////// Insert
require_once __DIR__ . '/config.php';
$serverName = $DB_HOST;
$connectionInfo = array( "Database"=>$DB_DATABASE, "UID"=>$DB_USERNAME, "PWD"=>$DB_PASSWORD,"CharacterSet" => "UTF-8", "TrustServerCertificate" => true, "Encrypt" => true);
$conn = sqlsrv_connect( $serverName, $connectionInfo);


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// echo $name7; 

$sql = "Insert  into T3 ([Nombre ],[Correo],[Prioridad],[Empresa],[Asunto],[Mensaje],[Adjuntos],[Fecha],[Hora],[Id_Ticket],[Estatus],[PA])values('$name1','$name2','$name3','$name4','$name5','$name6','$name7','$name8','$name9','$name10','','')";

$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// }

sqlsrv_free_stmt( $stmt);

  ///////////echo "Se levanto el ticket.<br />";
  
  echo'<script type="text/javascript">
alert("Se levanto de forma correcta tu ticket");
    </script>';
	
	
	/////////////////////////////////////////////////////    window.location.href="http://192.168.100.79/Comedor/FormularioSEDESA.php";

////////////////// Select


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// $sql = "Select *  from [dbo].[Usuarios]";
// $stmt = sqlsrv_query( $conn, $sql );
// if( $stmt === false) {
    // die( print_r( sqlsrv_errors(), true) );
// }

// while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
      // echo $row['Usuario'].", ".$row['Contrasena']."<br />";
// }

// sqlsrv_free_stmt( $stmt);


}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }
// https://www.dynamsoft.com/codepool/mobile-qr-code-scanner-in-html5.html
?>