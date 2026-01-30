<?php
$arrayRecibido=json_decode($_POST["arrayDeValores"], true );
 
echo "Hemos recibido en el PHP un array de ".count($arrayRecibido)." elementos de luis123232";

$array1530p1 = [];

foreach($arrayRecibido as $valor)
{
	
	echo "\n- ".$valor;

} 
   session_start();
   $_SESSION['superhero'] = $arrayRecibido;
   ///$_SESSION['hola'] = $arrayRecibido[1];
   // $_SESSION['superhero2'] = $arrayRecibido[2];
   // $_SESSION['superhero3'] = $arrayRecibido[3];
   // $_SESSION['superhero4'] = $arrayRecibido[4];
   // $_SESSION['superhero5'] = $arrayRecibido[5];
   // $_SESSION['superhero6'] = $arrayRecibido[6];
   // $_SESSION['superhero7'] = $arrayRecibido[7];
   


?>
