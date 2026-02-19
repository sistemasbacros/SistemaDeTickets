<?php
/**
 * @file t1.php
 * @brief Receptor de datos AJAX (duplicado de Asignartiemtick.php).
 *
 * @description
 * Endpoint que recibe array JSON vía POST y lo almacena en sesión.
 * Este archivo es un duplicado exacto de Asignartiemtick.php.
 *
 * Considerar eliminar este duplicado y unificar en un solo archivo
 * para mejorar mantenibilidad.
 *
 * @module Módulo de Asignación
 * @access API (POST request)
 *
 * @dependencies
 * - PHP: json_decode, session_start()
 *
 * @inputs
 * - POST['arrayDeValores']: JSON string con array de valores
 *
 * @session
 * - $_SESSION['superhero']: Array almacenado
 *
 * @see Asignartiemtick.php (archivo original/duplicado)
 *
 * @deprecated Considerar usar Asignartiemtick.php en su lugar
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */

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
