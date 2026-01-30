    <button class="btn btn-default1" id="Ini" name='Ini' onclick="window.location.href='http://192.168.100.79/Comedor/PlatBacrocorp.php'"> <img src="Inicio.jpg" width="65" /> </button> &nbsp;&nbsp;
    <button class="btn btn-default115" id="Car" name='Carg' onclick="window.location.href='http://192.168.100.79/Comedor/CargarNuevoContrato.php'"> <img src="Cargcon.png" width="50" /> </button>
      <button class="btn btn-default115" id="Edit" name='Edit' onclick="theFunction1();"> <img src="Contrato.jpg" width="70" /> </button>
  <button class="btn btn-default" id="Ec" name='Ec' onclick="theFunction();"> <img src="Basurero.jpg" width="50" /> </button> 
  <button class="btn btn-default1" id="EX" name='EX' onclick="ExportToExcel('xlsx')"> <img src="EXCEL.PNG" width="50" /> </button> 
  

  

 
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">



<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">



 <meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script src="https://nightly.datatables.net/fixedcolumns/js/dataTables.fixedColumns.js"></script>	




  
  
  <style>


    .table td{
  font-size: 12px;
	    font-weight: bold;	
    }
	
	    .table th{
  font-size: 15px;
		  color: white;
  background: #1E4E79;
    font-weight: bold;
    }




</style>
  
      <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	


<div class="inner1"> <table id="example" class="table table-striped table-bordered" width="100%" style="font-size:140%;background-color:#2D6DA6;color:white;font-weight: bold;"></table> </div></div>




<?php


$serverName = "LUISROMERO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Operaciones", "UID"=>"larome02", "PWD"=>"larome02","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }

$sql = "Select * from CatologodeContratos";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}


$array1 = [];
$array2 = [];
$array3 = [];
$array3 = [];
$array4 = [];
$array5 = [];
$array6 = [];
$array7 = [];



while( $row = sqlsrv_fetch_array( $stmt,SQLSRV_FETCH_NUMERIC) ) {
      //////echo $row[0].", ".$row[1]."<br />";
	  
	  array_push($array1,$row[0]);
	  array_push($array2,$row[1]);
	  array_push($array3,$row[2]);
	  array_push($array4,$row[3]);
	  array_push($array5,$row[4]);
	  array_push($array6,$row[5]);
	  array_push($array7,$row[6]);
}

sqlsrv_free_stmt( $stmt);



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


// Create our number formatter.
const formatter = new Intl.NumberFormat('es-MX', {
  style: 'currency',
  currency: 'MXN',

  // These options are needed to round to whole numbers if that's what you want.
  //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
  //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
});

// console.log(formatter.format(2500)); /* $2,500.00 */


var dataSet = [];

// alert(darray1)
// alert(darray2)
// alert(darray3)
// alert(darray4)
// alert(darray5)
// alert(darray6)
// alert(darray7)
// alert(darray8)
// alert(darray9)
// alert(darray10)
// alert(darray11)
// alert(darray12)
// alert(darray13)


for (var i = 0; i < darray1.length; i++) {
	dataSet.push([darray1[i],darray2[i],darray3[i],darray4[i],darray5[i],darray6[i],formatter.format(darray7[i]),'<a href="http://192.168.100.79/Comedor/uploads/'+darray3[i]+'/OPERACIONES/">'+darray3[i]+'</a>'])
}


new DataTable('#example', {
    columns: [
        { title: 'Id' },
		{ title: 'Empresa'},
		{ title: 'Cliente' },
        { title: 'Numero' },
        { title: 'VIGENCIA'},
        { title: 'SERVICIO'},
        { title: 'Monto_Contrato'},
		{ title: 'Ver documentación'},
    ],
    data: dataSet,lengthMenu: [
        [10, 25, 50,75,100, -1],
        [10, 25, 50, 75,100,'All']
    ]  , scrollY: 450,scrollX: 1,
  scrollCollapse: true
});

  
	  
       // counter++;


// var prueba=[];

// $('#example tbody').on( 'click', 'tr', function () {
    // prueba= table.row( this ).data()

		// // convertimos el array en un json para enviarlo al PHP
		// var arrayJson=JSON.stringify(prueba);
 
		// // mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		// $.post("prueba1.php",{arrayDeValores:arrayJson},function(data) {
 
			// // Mostramos el texto devuelto por el archivo php
			// ////alert(data);
		// });
// location.reload();
// } );

// var arrayJS=["casa","coche","moto","perro"];
 
		// // convertimos el array en un json para enviarlo al PHP
		// var arrayJson=JSON.stringify(arrayJS);
 
		// // mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		// $.post("proceso.php",{arrayDeValores:arrayJson},function(data) {
 
			// // Mostramos el texto devuelto por el archivo php
			// alert(data);
		// });
		
		function ExportToExcel(type, fn, dl) {
			
			

			
       var elt = document.getElementById('example');
       var wb = XLSX.utils.table_to_book(elt, { sheet: "Controldecontratos" });
       return dl ?
         XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }):
         XLSX.writeFile(wb, fn || ('ControlVeh.' + (type || 'xlsx')));
    }


</script>



<script>



var table = $('#example').DataTable();

var prueba=[];
  
$('#example').on( 'click', 'tr', function () {

prueba= table.row( this ).data()
////alert("Seleccionaste los siguentes datos" +" "+ prueba)
} );


table.on('click', 'tbody tr', (e) => {
    let classList = e.currentTarget.classList;
 
    if (classList.contains('selected')) {
        classList.remove('selected');
	}
    else {
        table.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        classList.add('selected');
		
		 // alert('Hola')
    }
});

 function theFunction () {
    // alert(prueba)
	
var arrayJson=JSON.stringify(prueba);
 
 			alert("Se elimino tu contrato");
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("prueba1114.php",{arrayDeValores:arrayJson},function(data) {
 
			// Mostramos el texto devuelto por el archivo php
			location.reload();
		});
		

    }

	
	//////////////////////////////  Function enviar datos  formulario editar texto
	 function theFunction1 () {
		// convertimos el array en un json para enviarlo al PHP
		var arrayJson=JSON.stringify(prueba);
 
		// mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		$.post("EditContrato.php",{arrayDeValores:arrayJson},function(data) {
 
			// Mostramos el texto devuelto por el archivo php
			// alert(data);
						window.location.href = 'http://192.168.100.79/Comedor/EditContrato1.php'; //Will take you to Google.
	
			
		});
				
		 }
		 
	
	
	
	
		//////////////////////////////  Function enviar datos  formulario editar texto
	 // function theFunction14 () {
		// // convertimos el array en un json para enviarlo al PHP
		// var arrayJson=JSON.stringify(prueba);
 
		// // mediante ajax, enviamos por POST el json en la variable: arrayDeValores
		// $.post("EditUpload1.php",{arrayDeValores:arrayJson},function(data) {
 
			// // Mostramos el texto devuelto por el archivo php
			// // alert(data);
						// window.location.href = 'http://192.168.100.79/Comedor/demoupload1.php'; //Will take you to Google.
	
			
		// });
		
		 // }

	
	
	
	///////////////////////////Función  sincronizar  archivos  
	 
</script>