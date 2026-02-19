<?php
/**
 * @file PruebaTabla.php
 * @brief Tabla de prueba con autenticación por parámetro GET.
 *
 * @description
 * Módulo de visualización de datos con control de acceso basado en
 * parámetro GET 'newpwd'. Dependiendo de la contraseña proporcionada,
 * muestra datos de LICITACIONES u OPERACIONES.
 *
 * Credenciales de acceso:
 * - "Lic01" → Acceso a LICITACIONES
 * - "Ope01?" → Acceso a OPERACIONES
 *
 * ADVERTENCIA DE SEGURIDAD: Este método de autenticación NO es seguro.
 * Las contraseñas viajan en la URL y son visibles en logs del servidor.
 * Se recomienda implementar autenticación real con sesión.
 *
 * @module Módulo de Pruebas / Reportes
 * @access Por contraseña GET (INSEGURO)
 *
 * @dependencies
 * - JS CDN: jQuery 3.5.1 + 1.11.3, DataTables 1.10.22 + 1.13.4
 * - CSS CDN: DataTables, Bootstrap 3.3.7
 *
 * @inputs
 * - GET['newpwd']: Contraseña de acceso
 *   - "Lic01" → $value = "LICITACIONES"
 *   - "Ope01?" → $value = "OPERACIONES"
 *
 * @security
 * - ADVERTENCIA CRÍTICA: Contraseñas en URL (visible en logs)
 * - ADVERTENCIA: Sin sanitización de parámetro GET
 * - RECOMENDACIÓN: Migrar a autenticación por sesión
 *
 * @todo
 * - Implementar autenticación segura con sesión
 * - Remover contraseñas de URLs
 * - Agregar CSRF protection
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0 (desarrollo/pruebas)
 * @since 2024
 */

require_once __DIR__ . '/config.php';

$value;

if ($_GET['newpwd'] == "Lic01") {
$value="LICITACIONES";
} 


if ($_GET['newpwd'] == "Ope01?") {
	// echo "entro0";
$value="OPERACIONES";
} 


?>


<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.22/css/jquery.dataTables.css">
  <link rel="stylesheet" type="text/css" href="https://datatables.net/media/css/site-examples.css">
    
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">



 <meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
	

<script src="https://nightly.datatables.net/fixedcolumns/js/dataTables.fixedColumns.js"></script>	
<link href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css" rel="stylesheet" type="text/css" />	



	
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
  
  
  <style>


    .table td{
  font-size: 12x;
	    font-weight: bold;	
		  background: #1E4E79;
		    color: white;
    }
	
	    .table th{
  font-size: 12px;
		  color: white;
  background: #1E4E79;
    font-weight: bold;
    }
	



.red {
  background-color: red
}
.blue {
  background-color: #1E4E79;
  color: white;
}


div.scroll {
    overflow:auto;
}

</style>



     <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	
	
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
	
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">

	

  <button class="btn btn-default1" id="Ini" name='Ini' onclick="window.location.href='http://192.168.100.79/Comedor/PlatBacrocorp.php'"> <img src="Inicio.jpg" width="24" /> </button> &nbsp;&nbsp;
  <button class="btn btn-default115" id="Car" name='Carg' onclick="window.location.href='http://192.168.100.79/Comedor/uploadexcseg.php'"> <img src="Cargcon.png" width="24" /> </button>
  <button class="btn btn-default115" id="Edit" name='Edit' onclick="theFunction1();"> <img src="Contrato.jpg" width="24" /> </button>
  <button class="btn btn-default" id="Ec" name='Ec' onclick="theFunction();"> <img src="Basurero.jpg" width="24" /> </button> 
  <button class="btn btn-default1" id="EX" name='EX' onclick="ExportToExcel('xlsx')"> <img src="EXCEL.PNG" width="24" />   <button class="btn btn-default1" id="EX" name='EX' onclick="toCelsius();"> <img src="Aprobacion.PNG" width="40" /> </button><a href="http://192.168.100.79/Comedor/PruebaTabla.php?newpwd=Ope01?">RECARGAR</a> <div  id="demo" style=" font-size: 25px;;background-color:#2D6DA6;color:white;"><?php echo $value ?></div> </button>  <form method="post" action="<?php echo htmlspecialchars("http://192.168.100.79/Comedor/PruebaTabla.php?newpwd=Ope01?");?>">   <label for="exampleFormControlInput1">Centro de costos:</label>
      <select class="selectpicker" id="Ncliente" name='Ncliente' required>
    </select>
<button type="submit" class="btn btn-dark">Buscar</button>  


<table id="example" class="cell-border" width="100%" style="font-size:90%;"></table>
</div>


</form>




<?php

$serverName = $DB_HOST_OPERACIONES;
$connectionInfo = array( "Database"=>$DB_DATABASE_OPERACIONES, "UID"=>$DB_USERNAME_OPERACIONES, "PWD"=>$DB_PASSWORD_OPERACIONES, "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

$sql = "Select * from SeguimientodeContrato18102023 order by [NO#],UNIDAD";


$sql1 = "Select Id,Cliente from  CatologodeContratos where numero is not null Order by Id";


$stmt = sqlsrv_query( $conn, $sql );

$stmt1 = sqlsrv_query( $conn, $sql1);


if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


$array1= [];
$array2= [];
$array3= [];
$array4= [];
$array5= [];
$array6= [];
$array7= [];
$array8= [];
$array9= [];
$array10= [];
$array11= [];
$array12= [];
$array13= [];
$array14= [];
$array15= [];
$array16= [];
$array17= [];
$array18= [];
$array19= [];
$array20= [];
$array21= [];
$array22= [];
$array23= [];
$array24= [];
$array25= [];
$array26= [];
$array27= [];
$array28= [];
$array29= [];
$array30= [];
$array31= [];
$array32= [];
$array33= [];
$array34= [];
$array35= [];
$array36= [];
$array37= [];
$array38= [];
$array39= [];
$array40= [];
$array41= [];
$array42= [];
$array43= [];
$array44= [];
$array45= [];
$array46= [];
$array47= [];
$array48= [];
$array49= [];
$array50= [];
$array51= [];
$array52= [];
$array53= [];
$array54= [];
$array55= [];
$array56= [];
$array57= [];
$array58= [];
$array59= [];
$array60= [];
$array61= [];
$array62= [];
$array63= [];
$array64= [];
$array65= [];
$array66= [];
$array67= [];
$array68= [];
$array69= [];
$array70= [];
$array71= [];
$array72= [];
$array73= [];
$array74= [];
$array75= [];
$array76= [];
$array77= [];


$array1Contra= [];
$array2Contra= [];



while( $row = sqlsrv_fetch_array( $stmt1,SQLSRV_FETCH_NUMERIC) ) {
//      echo $row[0].", ".$row[1]."<br />";	  
array_push($array1Contra,$row[0]);
array_push($array2Contra,$row[1]);

}




while( $row = sqlsrv_fetch_array( $stmt,SQLSRV_FETCH_NUMERIC) ) {
//      echo $row[0].", ".$row[1]."<br />";
	  
array_push($array1,$row[0]);
array_push($array2,$row[1]);
array_push($array3,$row[2]);
array_push($array4,$row[3]);
array_push($array5,$row[4]);
array_push($array6,$row[5]);
array_push($array7,$row[6]);
array_push($array8,$row[7]);
array_push($array9,$row[8]);
array_push($array10,$row[9]);
array_push($array11,$row[10]);
array_push($array12,$row[11]);
array_push($array13,$row[12]);
array_push($array14,$row[13]);
array_push($array15,$row[14]);
array_push($array16,$row[15]);
array_push($array17,$row[16]);
array_push($array18,$row[17]);
array_push($array19,$row[18]);
array_push($array20,$row[19]);
array_push($array21,$row[20]);
array_push($array22,$row[21]);
array_push($array23,$row[22]);
array_push($array24,$row[23]);
array_push($array25,$row[24]);
array_push($array26,$row[25]);
array_push($array27,$row[26]);
array_push($array28,$row[27]);
array_push($array29,$row[28]);
array_push($array30,$row[29]);
array_push($array31,$row[30]);
array_push($array32,$row[31]);
array_push($array33,$row[32]);
array_push($array34,$row[33]);
array_push($array35,$row[34]);
array_push($array36,$row[35]);
array_push($array37,$row[36]);
array_push($array38,$row[37]);
array_push($array39,$row[38]);
array_push($array40,$row[39]);
array_push($array41,$row[40]);
array_push($array42,$row[41]);
array_push($array43,$row[42]);
array_push($array44,$row[43]);
array_push($array45,$row[44]);
array_push($array46,$row[45]);
array_push($array47,$row[46]);
array_push($array48,$row[47]);
array_push($array49,$row[48]);
array_push($array50,$row[49]);
array_push($array51,$row[50]);
array_push($array52,$row[51]);
array_push($array53,$row[52]);
array_push($array54,$row[53]);
array_push($array55,$row[54]);
array_push($array56,$row[55]);
array_push($array57,$row[56]);
array_push($array58,$row[57]);
array_push($array59,$row[58]);
array_push($array60,$row[59]);
array_push($array61,$row[60]);
array_push($array62,$row[61]);
array_push($array63,$row[62]);
array_push($array64,$row[63]);
array_push($array65,$row[64]);
array_push($array66,$row[65]);
array_push($array67,$row[66]);
array_push($array68,$row[67]);
array_push($array69,$row[68]);
array_push($array70,$row[69]);
array_push($array71,$row[70]);
array_push($array72,$row[71]);
array_push($array73,$row[72]);
array_push($array74,$row[73]);
array_push($array75,$row[74]);
array_push($array76,$row[75]);
array_push($array77,$row[76]);

}

sqlsrv_free_stmt( $stmt);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
$pruebaselect = test_input($_POST['Ncliente']);
////////echo  $pruebaselect;




$serverName = $DB_HOST_OPERACIONES;
$connectionInfo = array( "Database"=>$DB_DATABASE_OPERACIONES, "UID"=>$DB_USERNAME_OPERACIONES, "PWD"=>$DB_PASSWORD_OPERACIONES, "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

$sql = "Select * from SeguimientodeContrato18102023 where [CC] = '$pruebaselect' order by [NO#],UNIDAD";

$stmt = sqlsrv_query( $conn, $sql );

if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}



$array1= [];
$array2= [];
$array3= [];
$array4= [];
$array5= [];
$array6= [];
$array7= [];
$array8= [];
$array9= [];
$array10= [];
$array11= [];
$array12= [];
$array13= [];
$array14= [];
$array15= [];
$array16= [];
$array17= [];
$array18= [];
$array19= [];
$array20= [];
$array21= [];
$array22= [];
$array23= [];
$array24= [];
$array25= [];
$array26= [];
$array27= [];
$array28= [];
$array29= [];
$array30= [];
$array31= [];
$array32= [];
$array33= [];
$array34= [];
$array35= [];
$array36= [];
$array37= [];
$array38= [];
$array39= [];
$array40= [];
$array41= [];
$array42= [];
$array43= [];
$array44= [];
$array45= [];
$array46= [];
$array47= [];
$array48= [];
$array49= [];
$array50= [];
$array51= [];
$array52= [];
$array53= [];
$array54= [];
$array55= [];
$array56= [];
$array57= [];
$array58= [];
$array59= [];
$array60= [];
$array61= [];
$array62= [];
$array63= [];
$array64= [];
$array65= [];
$array66= [];
$array67= [];
$array68= [];
$array69= [];
$array70= [];
$array71= [];
$array72= [];
$array73= [];
$array74= [];
$array75= [];
$array76= [];
$array77= [];



while( $row = sqlsrv_fetch_array( $stmt,SQLSRV_FETCH_NUMERIC) ) {
//      echo $row[0].", ".$row[1]."<br />";
	  
array_push($array1,$row[0]);
array_push($array2,$row[1]);
array_push($array3,$row[2]);
array_push($array4,$row[3]);
array_push($array5,$row[4]);
array_push($array6,$row[5]);
array_push($array7,$row[6]);
array_push($array8,$row[7]);
array_push($array9,$row[8]);
array_push($array10,$row[9]);
array_push($array11,$row[10]);
array_push($array12,$row[11]);
array_push($array13,$row[12]);
array_push($array14,$row[13]);
array_push($array15,$row[14]);
array_push($array16,$row[15]);
array_push($array17,$row[16]);
array_push($array18,$row[17]);
array_push($array19,$row[18]);
array_push($array20,$row[19]);
array_push($array21,$row[20]);
array_push($array22,$row[21]);
array_push($array23,$row[22]);
array_push($array24,$row[23]);
array_push($array25,$row[24]);
array_push($array26,$row[25]);
array_push($array27,$row[26]);
array_push($array28,$row[27]);
array_push($array29,$row[28]);
array_push($array30,$row[29]);
array_push($array31,$row[30]);
array_push($array32,$row[31]);
array_push($array33,$row[32]);
array_push($array34,$row[33]);
array_push($array35,$row[34]);
array_push($array36,$row[35]);
array_push($array37,$row[36]);
array_push($array38,$row[37]);
array_push($array39,$row[38]);
array_push($array40,$row[39]);
array_push($array41,$row[40]);
array_push($array42,$row[41]);
array_push($array43,$row[42]);
array_push($array44,$row[43]);
array_push($array45,$row[44]);
array_push($array46,$row[45]);
array_push($array47,$row[46]);
array_push($array48,$row[47]);
array_push($array49,$row[48]);
array_push($array50,$row[49]);
array_push($array51,$row[50]);
array_push($array52,$row[51]);
array_push($array53,$row[52]);
array_push($array54,$row[53]);
array_push($array55,$row[54]);
array_push($array56,$row[55]);
array_push($array57,$row[56]);
array_push($array58,$row[57]);
array_push($array59,$row[58]);
array_push($array60,$row[59]);
array_push($array61,$row[60]);
array_push($array62,$row[61]);
array_push($array63,$row[62]);
array_push($array64,$row[63]);
array_push($array65,$row[64]);
array_push($array66,$row[65]);
array_push($array67,$row[66]);
array_push($array68,$row[67]);
array_push($array69,$row[68]);
array_push($array70,$row[69]);
array_push($array71,$row[70]);
array_push($array72,$row[71]);
array_push($array73,$row[72]);
array_push($array74,$row[73]);
array_push($array75,$row[74]);
array_push($array76,$row[75]);
array_push($array77,$row[76]);

}




}





function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
 }
// https://www.dynamsoft.com/codepool/mobile-qr-code-scanner-in-html5.html


?>

<script>


var darray1 = <?php echo json_encode($array1);?>;
var darray2 = <?php echo json_encode($array2);?>;
var darray3 = <?php echo json_encode($array3);?>;
var darray4 = <?php echo json_encode($array4);?>;
var darray5 = <?php echo json_encode($array5);?>;
var darray6 = <?php echo json_encode($array6);?>;
var darray7 = <?php echo json_encode($array7);?>;
var darray8 = <?php echo json_encode($array8);?>;
var darray9 = <?php echo json_encode($array9);?>;
var darray10 = <?php echo json_encode($array10);?>;
var darray11 = <?php echo json_encode($array11);?>;
var darray12 = <?php echo json_encode($array12);?>;
var darray13 = <?php echo json_encode($array13);?>;
var darray14 = <?php echo json_encode($array14);?>;
var darray15 = <?php echo json_encode($array15);?>;
var darray16 = <?php echo json_encode($array16);?>;
var darray17 = <?php echo json_encode($array17);?>;
var darray18 = <?php echo json_encode($array18);?>;
var darray19 = <?php echo json_encode($array19);?>;
var darray20 = <?php echo json_encode($array20);?>;
var darray21 = <?php echo json_encode($array21);?>;
var darray22 = <?php echo json_encode($array22);?>;
var darray23 = <?php echo json_encode($array23);?>;
var darray24 = <?php echo json_encode($array24);?>;
var darray25 = <?php echo json_encode($array25);?>;
var darray26 = <?php echo json_encode($array26);?>;
var darray27 = <?php echo json_encode($array27);?>;
var darray28 = <?php echo json_encode($array28);?>;
var darray29 = <?php echo json_encode($array29);?>;
var darray30 = <?php echo json_encode($array30);?>;
var darray31 = <?php echo json_encode($array31);?>;
var darray32 = <?php echo json_encode($array32);?>;
var darray33 = <?php echo json_encode($array33);?>;
var darray34 = <?php echo json_encode($array34);?>;
var darray35 = <?php echo json_encode($array35);?>;
var darray36 = <?php echo json_encode($array36);?>;
var darray37 = <?php echo json_encode($array37);?>;
var darray38 = <?php echo json_encode($array38);?>;
var darray39 = <?php echo json_encode($array39);?>;
var darray40 = <?php echo json_encode($array40);?>;
var darray41 = <?php echo json_encode($array41);?>;
var darray42 = <?php echo json_encode($array42);?>;
var darray43 = <?php echo json_encode($array43);?>;
var darray44 = <?php echo json_encode($array44);?>;
var darray45 = <?php echo json_encode($array45);?>;
var darray46 = <?php echo json_encode($array46);?>;
var darray47 = <?php echo json_encode($array47);?>;
var darray48 = <?php echo json_encode($array48);?>;
var darray49 = <?php echo json_encode($array49);?>;
var darray50 = <?php echo json_encode($array50);?>;
var darray51 = <?php echo json_encode($array51);?>;
var darray52 = <?php echo json_encode($array52);?>;
var darray53 = <?php echo json_encode($array53);?>;
var darray54 = <?php echo json_encode($array54);?>;
var darray55 = <?php echo json_encode($array55);?>;
var darray56 = <?php echo json_encode($array56);?>;
var darray57 = <?php echo json_encode($array57);?>;
var darray58 = <?php echo json_encode($array58);?>;
var darray59 = <?php echo json_encode($array59);?>;
var darray60 = <?php echo json_encode($array60);?>;
var darray61 = <?php echo json_encode($array61);?>;
var darray62 = <?php echo json_encode($array62);?>;
var darray63 = <?php echo json_encode($array63);?>;
var darray64 = <?php echo json_encode($array64);?>;
var darray65 = <?php echo json_encode($array65);?>;
var darray66 = <?php echo json_encode($array66);?>;
var darray67 = <?php echo json_encode($array67);?>;
var darray68 = <?php echo json_encode($array68);?>;
var darray69 = <?php echo json_encode($array69);?>;
var darray70 = <?php echo json_encode($array70);?>;
var darray71 = <?php echo json_encode($array71);?>;
var darray72 = <?php echo json_encode($array72);?>;
var darray73 = <?php echo json_encode($array73);?>;
var darray74 = <?php echo json_encode($array74);?>;
var darray75 = <?php echo json_encode($array75);?>;
var darray76 = <?php echo json_encode($array76);?>;
var darray77 = <?php echo json_encode($array77);?>;


var dCont1 = <?php echo json_encode($array1Contra);?>;
var dCont2 = <?php echo json_encode($array2Contra);?>;


        function cargar_provincias()
        {
           //////////////////////////////////////// var array = ["Cantabria", "Asturias", "Galicia", "Andalucia", "Extremadura"];
            for(var i in dCont2)
            { 
                document.getElementById("Ncliente").innerHTML += "<option value='"+dCont1[i]+"'>"+dCont1[i]+"</option>"; 

            }
    }

    cargar_provincias();


var newArr = [];

for (var i = 0; i < darray1.length; i++) {
  newArr.push({"CC":darray1[i],
"NO#":darray2[i],
"UNIDAD":darray3[i],
"EQUIPO":darray4[i],
"FECHA_ENT_OS_01":darray5[i],
"Num_FACT_01":darray6[i],
"FECHA_ENT/GTN_01":darray7[i],
"FECHA_INGRESO_01":darray8[i],
"FECHA_VMTO_CR_01":darray9[i],
"CBZ_01":darray10[i],
"FECHA_ENT_OS_02": darray11[i],
"Num_FACT_02": darray12[i],
"FECHA_ENT/GTN_02": darray13[i],
"FECHA_INGRESO_02": darray14[i],
"FECHA_VMTO_CR_02": darray15[i],
"CBZ_02": darray16[i],
"FECHA_ENT_OS_03": darray17[i],
"Num_FACT_03": darray18[i],
"FECHA_ENT/GTN_03": darray19[i],
"FECHA_INGRESO_03": darray20[i],
"FECHA_VMTO_CR_03": darray21[i],
"CBZ_03": darray22[i],
"FECHA_ENT_OS_04": darray23[i],
"Num_FACT_04": darray24[i],
"FECHA_ENT/GTN_04": darray25[i],
"FECHA_INGRESO_04": darray26[i],
"FECHA_VMTO_CR_04": darray27[i],
"CBZ_04": darray28[i],
"FECHA_ENT_OS_05": darray29[i],
"Num_FACT_05": darray30[i],
"FECHA_ENT/GTN_05": darray31[i],
"FECHA_INGRESO_05": darray32[i],
"FECHA_VMTO_CR_05": darray33[i],
"CBZ_05": "$"+darray34[i],
"FECHA_ENT_OS_06": darray35[i],
"Num_FACT_06": darray36[i],
"FECHA_ENT/GTN_06": darray37[i],
"FECHA_INGRESO_06": darray38[i],
"FECHA_VMTO_CR_06": darray39[i],
"CBZ_06": "$"+darray40[i],
"FECHA_ENT_OS_07": darray41[i],
"Num_FACT_07": darray42[i],
"FECHA_ENT/GTN_07": darray43[i],
"FECHA_INGRESO_07": darray44[i],
"FECHA_VMTO_CR_07": darray45[i],
"CBZ_07": "$"+darray46[i],
"FECHA_ENT_OS_08": darray47[i],
"Num_FACT_08": darray48[i],
"FECHA_ENT/GTN_08": darray49[i],
"FECHA_INGRESO_08": darray50[i],
"FECHA_VMTO_CR_08": darray51[i],
"CBZ_08": "$"+darray52[i],
"FECHA_ENT_OS_09": darray53[i],
"Num_FACT_09": darray54[i],
"FECHA_ENT/GTN_09": darray55[i],
"FECHA_INGRESO_09": darray56[i],
"FECHA_VMTO_CR_09": darray57[i],
"CBZ_09": "$"+darray58[i],
"FECHA_ENT_OS_10": darray59[i],
"Num_FACT_10": darray60[i],
"FECHA_ENT/GTN_10": darray61[i],
"FECHA_INGRESO_10": darray62[i],
"FECHA_VMTO_CR_10": darray63[i],
"CBZ_10": "$"+darray64[i],
"FECHA_ENT_OS_11": darray65[i],
"Num_FACT_11": darray66[i],
"FECHA_ENT/GTN_11": darray67[i],
"FECHA_INGRESO_11": darray68[i],
"FECHA_VMTO_CR_11": darray69[i],
"CBZ_11": "$"+darray70[i],
"FECHA_ENT_OS_12": darray71[i],
"Num_FACT_12": darray72[i],
"FECHA_ENT/GTN_12": darray73[i],
"FECHA_INGRESO_12": darray74[i],
"FECHA_VMTO_CR_12": darray75[i],
"CBZ_12": "$"+darray76[i],
"Porcentaje_Avance":  Math.round((darray77[i]*100))+"%"
});
}

// "CC":darray1[i],
// "NO#":darray2[i],
// "UNIDAD":darray3[i],
// "EQUIPO":darray4[i],
// "FECHA_ENT_OS_01":darray5[i],
// "Num_FACT_01":darray6[i],
// "FECHA_ENT/GTN_01":darray7[i],
// "FECHA_INGRESO_01":darray8[i],
// "FECHA_VMTO_CR_01":darray9[i],
// "CBZ_01":darray10[i],
// "FECHA_ENT_OS_02": darray11[i],
// "Num_FACT_02": darray12[i],
// "FECHA_ENT/GTN_02": darray13[i],
// "FECHA_INGRESO_02": darray14[i],
// "FECHA_VMTO_CR_02": darray15[i],
// "CBZ_02": darray16[i],
// "FECHA_ENT_OS_03": darray17[i],
// "Num_FACT_03": darray18[i],
// "FECHA_ENT/GTN_03": darray19[i],
// "FECHA_INGRESO_03": darray20[i],
// "FECHA_VMTO_CR_03": darray21[i],
// "CBZ_03": darray22[i],
// "FECHA_ENT_OS_04": darray23[i],
// "Num_FACT_04": darray24[i],
// "FECHA_ENT/GTN_04": darray25[i],
// "FECHA_INGRESO_04": darray26[i],
// "FECHA_VMTO_CR_04": darray27[i],
// "CBZ_04": darray28[i],
// "FECHA_ENT_OS_05": darray29[i],
// "Num_FACT_05": darray30[i],
// "FECHA_ENT/GTN_05": darray31[i],
// "FECHA_INGRESO_05": darray32[i],
// "FECHA_VMTO_CR_05": darray33[i],
// "CBZ_05": darray34[i],
// "FECHA_ENT_OS_06": darray35[i],
// "Num_FACT_06": darray36[i],
// "FECHA_ENT/GTN_06": darray37[i],
// "FECHA_INGRESO_06": darray38[i],
// "FECHA_VMTO_CR_06": darray39[i],
// "CBZ_06": darray40[i],
// "FECHA_ENT_OS_07": darray41[i],
// "Num_FACT_07": darray42[i],
// "FECHA_ENT/GTN_07": darray43[i],
// "FECHA_INGRESO_07": darray44[i],
// "FECHA_VMTO_CR_07": darray45[i],
// "CBZ_07": darray46[i],
// "FECHA_ENT_OS_08": darray47[i],
// "Num_FACT_08": darray48[i],
// "FECHA_ENT/GTN_08": darray49[i],
// "FECHA_INGRESO_08": darray50[i],
// "FECHA_VMTO_CR_08": darray51[i],
// "CBZ_08": darray52[i],
// "FECHA_ENT_OS_09": darray53[i],
// "Num_FACT_09": darray54[i],
// "FECHA_ENT/GTN_09": darray55[i],
// "FECHA_INGRESO_09": darray56[i],
// "FECHA_VMTO_CR_09": darray57[i],
// "CBZ_09": darray58[i],
// "FECHA_ENT_OS_10": darray59[i],
// "Num_FACT_10": darray60[i],
// "FECHA_ENT/GTN_10": darray61[i],
// "FECHA_INGRESO_10": darray62[i],
// "FECHA_VMTO_CR_10": darray63[i],
// "CBZ_10": darray64[i],
// "FECHA_ENT_OS_11": darray65[i],
// "Num_FACT_11": darray66[i],
// "FECHA_ENT/GTN_11": darray67[i],
// "FECHA_INGRESO_11": darray68[i],
// "FECHA_VMTO_CR_11": darray69[i],
// "CBZ_11": darray70[i],
// "FECHA_ENT_OS_12": darray71[i],
// "Num_FACT_12": darray72[i],
// "FECHA_ENT/GTN_12": darray73[i],
// "FECHA_INGRESO_12": darray74[i],
// "FECHA_VMTO_CR_12": darray75[i],
// "CBZ_12": darray76[i],
// "Porcentaje_Avance": darray77[i]






// for(let i of darray2){
  // newArr.push({"CC" : darray1[i],"No" : darray2[i],"Unidad" : darray3[i],"Equipo" : darray4[i]});
// }
  console.log(newArr.length);


// var myJsonString = JSON.stringify(darray2);

// alert(myJsonString)
// alert(darray2)
// alert(darray3)


var dataSet = {
  "data": newArr
};



  console.log(dataSet);



var previous = '';
 
// $(document).ready(function() {


// } );


var table = $('#example').DataTable( {
  data: dataSet.data,
  "order": [[ 0, 'asc' ]],
  columns: [
   { title: '<span class="blue"">No</span>' ,data: "NO#" },
{ title: '<span class="blue">Unidad</span>' , data: "UNIDAD" },
{ title: '<span class="blue">Equipo</span>' , data: "EQUIPO" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "FECHA_ENT_OS_01" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "Num_FACT_01" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "FECHA_ENT/GTN_01" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "FECHA_INGRESO_01" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "FECHA_VMTO_CR_01" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Enero</th></tr></thead>' , data: "CBZ_01" },

{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "FECHA_ENT_OS_02" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "Num_FACT_02" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "FECHA_ENT/GTN_02" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "FECHA_INGRESO_02" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "FECHA_VMTO_CR_02" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Febrero</th></tr></thead>' , data: "CBZ_02" },

{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "FECHA_ENT_OS_03" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "Num_FACT_03" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "FECHA_ENT/GTN_03" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "FECHA_INGRESO_03" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "FECHA_VMTO_CR_03" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Marzo</th></tr></thead>' , data: "CBZ_03" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "FECHA_ENT_OS_04" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "Num_FACT_04" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "FECHA_ENT/GTN_04" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "FECHA_INGRESO_04" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "FECHA_VMTO_CR_04" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Abril</th></tr></thead>' , data: "CBZ_04" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "FECHA_ENT_OS_05" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "Num_FACT_05" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "FECHA_ENT/GTN_05" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "FECHA_INGRESO_05" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "FECHA_VMTO_CR_05" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Mayo</th></tr></thead>' , data: "CBZ_05" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "FECHA_ENT_OS_06" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "Num_FACT_06" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "FECHA_ENT/GTN_06" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "FECHA_INGRESO_06" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "FECHA_VMTO_CR_06" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Junio</th></tr></thead>' , data: "CBZ_06" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "FECHA_ENT_OS_07" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "Num_FACT_07" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "FECHA_ENT/GTN_07" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "FECHA_INGRESO_07" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "FECHA_VMTO_CR_07" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Julio</th></tr></thead>' , data: "CBZ_07" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "FECHA_ENT_OS_08" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "Num_FACT_08" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "FECHA_ENT/GTN_08" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "FECHA_INGRESO_08" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "FECHA_VMTO_CR_08" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Agosto</th></tr></thead>' , data: "CBZ_08" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "FECHA_ENT_OS_09" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "Num_FACT_09" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "FECHA_ENT/GTN_09" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "FECHA_INGRESO_09" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "FECHA_VMTO_CR_09" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Septiembre</th></tr></thead>' , data: "CBZ_09" },


{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "FECHA_ENT_OS_10" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "Num_FACT_10" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "FECHA_ENT/GTN_10" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "FECHA_INGRESO_10" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "FECHA_VMTO_CR_10" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Octubre</th></tr></thead>' , data: "CBZ_10" },



{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "FECHA_ENT_OS_11" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "Num_FACT_11" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "FECHA_ENT/GTN_11" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "FECHA_INGRESO_11" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "FECHA_VMTO_CR_11" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Noviembre</th></tr></thead>' , data: "CBZ_11" },



{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT_OS</th><th colspan="1"class="blue">Operaciones</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "FECHA_ENT_OS_12" },
{ title: '<thead><tr><th rowspan="2"class="blue">Num_FACT</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "Num_FACT_12" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_ENT/GTN</th><th colspan="1"class="blue">Finanzas</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "FECHA_ENT/GTN_12" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_INGRESO</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "FECHA_INGRESO_12" },
{ title: '<thead><tr><th rowspan="2"class="blue">FECHA_VMTO_CR</th><th colspan="1"class="blue">Gestión</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "FECHA_VMTO_CR_12" },
{ title: '<thead><tr><th rowspan="2"class="blue">CBZ</th><th colspan="1"class="blue">Cobranza</th></tr><tr><th class="blue">Diciembre</th></tr></thead>' , data: "CBZ_12" },

{ title: '<thead> <th colspan="2"class="blue"></th></tr> <tr> <th class="blue" >Porcentaje_Avance</th></tr></thead>' , data: "Porcentaje_Avance" },

  ],responsive:true,     select: {
        style: 'single',
		items: 'cell'
    }, 
   lengthMenu: [
        [10, 25, 50,75,100, -1],
        [10, 25, 50, 75,100,'All']
    ] , scrollY:280,scrollX: 1,fixedColumns:   true,fixedColumns: {
        left: 3

    },
  "initComplete": function(settings, json) {
    // in case the initial sort order leads to 
    // cells needing to be altered:
    processColumnNodes( $('#example').DataTable() );
  }
} ); 
    



table.on( 'draw', function () {

  processColumnNodes( $('#example').DataTable() );
} );

function processColumnNodes(tbl) {
  // see https://datatables.net/reference/type/selector-modifier
  var selector_modifier = { order: 'current', page: 'current', search: 'applied' }

  var previous = '';
  var officeNodes = tbl.column(0, selector_modifier).nodes();
  var officeData = tbl.column(0, selector_modifier).data();
    for (var i = 0; i < officeData.length; i++) {
    var current = officeData[i];
    if (current === previous) {
		officeNodes[i].textContent = '';
    // officeNodes[i].setAttribute("style", "border-top:none;");
		officeNodes[i].style.backgroundColor = "#E8E8E9"
	    officeNodes[i].style.border="hidden";
		  officeNodes[i].style.borderWidth= '0.9';
	
     
    } else {
    }
    previous = current;
	
  }
  
  
  // see https://datatables.net/reference/type/selector-modifier
  var selector_modifier = { order: 'current', page: 'current', search: 'applied' }

  var previous = '';
  var officeNodes = tbl.column(1, selector_modifier).nodes();
  var officeData = tbl.column(1, selector_modifier).data();
    for (var i = 0; i < officeData.length; i++) {
    var current = officeData[i];
    if (current === previous) {
      officeNodes[i].textContent = '';
      // officeNodes[i].setAttribute("style", "border-top:none;");
	  		// officeNodes[i].style.backgroundColor = "#1E4E79"
			//////
		officeNodes[i].style.backgroundColor = "#E8E8E9"
	    officeNodes[i].style.border="hidden";
		  officeNodes[i].style.borderWidth= '0.9';
		
		///// none
    } else {
      officeNodes[i].textContent = current;
    }
    previous = current;
  } 
  
  
}


var prueba=[];

var prueba=[];
  
// $('#example').on( 'click', 'tbody th', function () {

// prueba= table.row( this ).data()
// alert("Seleccionaste los siguentes datos" +" "+ prueba)
// } );


var index;
var cellValue;

var index1;

var index2;
	
var cellValue2;
var cellValue1;


/// Nombre encabezado de lla table example




$("tr").on('click', function (index, htmlElement) {        
    index2 = $(this).index();           
    cellValue2 = $(this).text();
	
	// alert(index2)

 // alert('index = ' + index2 + ", " + "cellValue = " + cellValue2); 

});


var table = $('#example').DataTable();
 
$('#example tbody').on( 'click', 'td', function () {
    // alert( 'Clicked on cell in visible column: '+table.cell( this ).index().columnVisible );

} );




/// Nombre encabezado de lla table example

$("td").on('click', function (index, htmlElement) {        
    index1 = $(this).index();           
    cellValue1 = $(this).text();
	
 // alert(index1)
// alert('index = ' + index1 + ", " + "cellValue = " + cellValue1); 

});


var pruebatt1=[];


function toCelsius() {
	pruebatt1=[];
// alert(index2)
// alert(cellValue1)
// alert(index1)
// alert(darray2[index2]);
// alert(darray3[index2]);
// alert(darray4[index2]);

pruebatt1.push(index1,darray2[index2],darray3[index2],darray4[index2]);

// alert(pruebatt1)

		// convertimos el array en un json para enviarlo al PHP
		var arrayJson=JSON.stringify(pruebatt1);
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("editpruebatabla.php",{arrayDeValores:arrayJson},function(data) {
 
			// Mostramos el texto devuelto por el archivo php
			// alert(data);
			
			var myParam = location.search.split('newpwd=')[1]
			
			// alert(myParam)
			
if (myParam=='Ope01?' && index1=='3'|| myParam=='Ope01?' && index1=='9' || myParam=='Ope01?' && index1=='15' ||
myParam=='Ope01?' && index1=='21'|| myParam=='Ope01?' && index1=='27' || myParam=='Ope01?' && index1=='33' ||
myParam=='Ope01?' && index1=='39'|| myParam=='Ope01?' && index1=='45' || myParam=='Ope01?' && index1=='51' ||
myParam=='Ope01?' && index1=='57'|| myParam=='Ope01?' && index1=='63' || myParam=='Ope01?' && index1=='69'
) {
window.open('http://192.168.100.79/Comedor/editcamposegcont.php?newpwd=Ope01?','Actualiza tus campos','toolbars=0,width=500,height=500,right=200, top=200,scrollbars=1,resizable=5');
} else  { alert('No se puede edítar el registro.Con esta cuenta')} 
			
			// window.location.href = 'http://192.168.100.79/Comedor/FormularioCargaeqSC.php'; //Will take you to Google.
	
			
		});
				

}






/// Nombre encabezado de lla table example

$("tr").on('click', function (index, htmlElement) {        
    index = $(this).index();           
    cellValue = $(this).text();

 prueba=[];
prueba.push(darray2[index]);
prueba.push(darray3[index]);
prueba.push(darray4[index]);
prueba.push(darray5[index]);
prueba.push(darray6[index]);
prueba.push(darray7[index]);
prueba.push(darray8[index]);
prueba.push(darray9[index]);
prueba.push(darray10[index]);
prueba.push(darray11[index]);
prueba.push(darray12[index]);
prueba.push(darray13[index]);
prueba.push(darray14[index]);
prueba.push(darray15[index]);
prueba.push(darray16[index]);
prueba.push(darray17[index]);
prueba.push(darray18[index]);
prueba.push(darray19[index]);
prueba.push(darray20[index]);
prueba.push(darray21[index]);
prueba.push(darray22[index]);
prueba.push(darray23[index]);
prueba.push(darray24[index]);
prueba.push(darray25[index]);
prueba.push(darray26[index]);
prueba.push(darray27[index]);
prueba.push(darray28[index]);
prueba.push(darray29[index]);
prueba.push(darray30[index]);
prueba.push(darray31[index]);
prueba.push(darray32[index]);
prueba.push(darray33[index]);
prueba.push(darray34[index]);
prueba.push(darray35[index]);
prueba.push(darray36[index]);
prueba.push(darray37[index]);
prueba.push(darray38[index]);
prueba.push(darray39[index]);
prueba.push(darray40[index]);
prueba.push(darray41[index]);
prueba.push(darray42[index]);
prueba.push(darray43[index]);
prueba.push(darray44[index]);
prueba.push(darray45[index]);
prueba.push(darray46[index]);
prueba.push(darray47[index]);
prueba.push(darray48[index]);
prueba.push(darray49[index]);
prueba.push(darray50[index]);
prueba.push(darray51[index]);
prueba.push(darray52[index]);
prueba.push(darray53[index]);
prueba.push(darray54[index]);
prueba.push(darray55[index]);
prueba.push(darray56[index]);
prueba.push(darray57[index]);
prueba.push(darray58[index]);
prueba.push(darray59[index]);
prueba.push(darray60[index]);
prueba.push(darray61[index]);
prueba.push(darray62[index]);
prueba.push(darray63[index]);
prueba.push(darray64[index]);
prueba.push(darray65[index]);
prueba.push(darray66[index]);
prueba.push(darray67[index]);
prueba.push(darray68[index]);
prueba.push(darray69[index]);
prueba.push(darray70[index]);
prueba.push(darray71[index]);
prueba.push(darray72[index]);
prueba.push(darray73[index]);
prueba.push(darray74[index]);
prueba.push(darray75[index]);
prueba.push(darray76[index]);
prueba.push(darray77[index]);



// // if ( cellValue == "Nombre_Unidad") {	
// // } else {alert("Seleccionaste la siguiente fecha:" + cellValue)}		

// //alert(prueba.length)	

// alert('index = ' + index + ", " + "cellValue = " + cellValue); 

});

// alert(prueba)

// table.on('click', 'tbody tr', (e) => {
    // let classList = e.currentTarget.classList;
 
    // if (classList.contains('selected')) {
        // classList.remove('selected');
	// }
    // else {
        // table.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        // classList.add('selected');
		
		 // // alert('Hola')
    // }
// });




		function ExportToExcel(type, fn, dl) {
			
			

			
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "Seguimiento_Contratos" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('ControlVeh.' + (type || 'xlsx')));
    }



  function theFunction () {
    // alert(prueba)
	
var arrayJson=JSON.stringify(prueba);
 
 			alert("Se elimino tu contrato");
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("po.php",{arrayDeValores:arrayJson},function(data) {
 //////alert(data)
			// Mostramos alertel texto devuelto por el archivo php
			location.reload();
		});
		

    }




	 function theFunction1 () {
		// convertimos el array en un json para enviarlo al PHP
		var arrayJson=JSON.stringify(prueba);
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("editseg25102023.php",{arrayDeValores:arrayJson},function(data) {
 
			// Mostramos el texto devuelto por el archivo php
			// alert(data);
						window.location.href = 'http://192.168.100.79/Comedor/FormularioCargaeqSC.php'; //Will take you to Google.
	
			
		});
				
		 }
		 



</script>



