<?php
 session_start(); // this NEEDS TO BE AT THE TOP of the page before any output etc
   // echo $_SESSION['superhero'];
 
$array1 = [];
// $array2 = [];
// $array3 = [];
// $array4 = [];
// $array5 = [];
// $array6 = [];
// $array7 = [];
 
 foreach($_SESSION['superhero'] as $valor)
{
	
 // echo "\n- ".$valor;
array_push($array1,$valor);

}



// echo $array1[0];
// echo $array1[1];
// echo $array1[2];
// echo $array1[3];
// echo $array1[4];
// echo $array1[5];
// echo $array1[6];


?>


<?php
// echo $array1[9];
// echo $array1[1];
// echo $array1[2];
// echo $array1[3];
// echo $array1[4];
// echo $array1[5];
// echo $array1[6];


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
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  

        <div class="form-group">
            <label for="startDate" class="col-sm-3 col-form-label is-required"  style="color:black;font-size:20px;font-weight: bold;">Fecha</label>
            <input id="fecha" name="fecha" class="form-control" type="text" required readonly>
            <span id="startDateSelected"></span>
        </div>
       <div class="form-group">
    <label for="exampleFormControlInput1" class="col-sm-3 col-form-label is-required"  style="color:black;font-size:20px;font-weight: bold;">Hora</label>
    <input type="text" class="form-control" id="Hora" name= 'Hora'  required readonly>
  </div>
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

// // $pedido = $name = $email = $gender = $comment = $website = "";


// echo $array1[9];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	
	 // // if (empty( $name1)) {
    // // echo '$var es o bien 0, vacía, o no se encuentra definida en absoluto';
// // }else {}

	
  $name8 = test_input($_POST["fecha"]); /// folio
  $name9 = test_input($_POST["Hora"]); /// folio
  
  

  
  
 // $name10 = test_input($_POST["Hora"]); /// folio


// echo $name7;
// echo "<br>";
// echo $name8;
// echo "<br>";
// echo $name6;
// echo "<br>";

// ////////////////// Update

// ////////////////// Insert
$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Ticket", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);


// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

// // echo $name7; 

$sql = "Update TMANVEHI  set Fecha_Ingreso=ltrim(rtrim(cast(convert(date,'$name8', 103)as nvarchar))),HORA_Ingreso='$name9' where idt='$array1[0]'";




// if ($name8 == 'En proceso') {
// $sql = "Update T3 set PA= '$name7',Estatus='$name8 ',Tiempo_Ejec='$name6',Tipo='$name9', HoraT='$name10' where Id_Ticket='$array1[9]'";
// }


// if ($name8 == 'Atendido') {
// $sql = "Update T3 set PA= '$name7',Estatus='$name8 ',Tiempo_Ejec='$name6',FinT='$name9', FeT='$name10' where Id_Ticket='$array1[9]'";
// }


$stmt = sqlsrv_query( $conn, $sql );

  
  echo'<script type="text/javascript">
alert("Se asigno el ticket");
    </script>';
	

}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }
// // https://www.dynamsoft.com/codepool/mobile-qr-code-scanner-in-html5.html
?>
