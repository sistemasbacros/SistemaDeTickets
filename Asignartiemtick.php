<?php
/**
 * @file Asignartiemtick.php
 * @brief Receptor de datos de asignación de tickets vía AJAX.
 *
 * @description
 * Endpoint que recibe un array de valores vía POST (JSON) y lo almacena
 * en la sesión para uso posterior en el flujo de asignación de tickets.
 * Utilizado por los archivos AsigBien*.php para acceder a los datos.
 *
 * Flujo de operación:
 * 1. Recibe POST['arrayDeValores'] como JSON string
 * 2. Decodifica con json_decode a array PHP
 * 3. Almacena en $_SESSION['superhero']
 * 4. Imprime confirmación de recepción
 *
 * Nota: El nombre 'superhero' parece ser un placeholder de desarrollo.
 * El echo incluye "luis123232" que parece ser identificador de debug.
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
 * @outputs
 * - String: Mensaje de confirmación con conteo de elementos
 * - Listado de valores recibidos (debug)
 *
 * @session
 * - $_SESSION['superhero']: Array recibido almacenado aquí
 *
 * @todo
 * - Renombrar 'superhero' a nombre descriptivo
 * - Remover echo de debug "luis123232"
 * - Retornar JSON en lugar de texto plano
 * - Agregar validación de datos
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/auth_check_api.php';

// Entrada validada: debe ser un arreglo JSON. Antes se guardaba en sesión sin
// comprobar nada y se imprimía su contenido (más una cadena de depuración) en
// la respuesta.
$arrayRecibido = json_decode($_POST['arrayDeValores'] ?? '', true);
if (!is_array($arrayRecibido)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'arrayDeValores inválido']);
    exit;
}

// auth_check_api.php ya abrió la sesión; no volver a llamar session_start()
// (emitía "session already active").
   $_SESSION['superhero'] = $arrayRecibido;
   ///$_SESSION['hola'] = $arrayRecibido[1];
   // $_SESSION['superhero2'] = $arrayRecibido[2];
   // $_SESSION['superhero3'] = $arrayRecibido[3];
   // $_SESSION['superhero4'] = $arrayRecibido[4];
   // $_SESSION['superhero5'] = $arrayRecibido[5];
   // $_SESSION['superhero6'] = $arrayRecibido[6];
   // $_SESSION['superhero7'] = $arrayRecibido[7];
   


?>
