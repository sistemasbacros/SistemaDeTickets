

<?php





$serverName1 = "WIN-44O80L37Q7M\COMERCIAL"; //serverName\instanceName
$connectionInfo1 = array( "Database"=>"BASENUEVA", "UID"=>"SA", "PWD"=>"Administrador1*","CharacterSet" => "UTF-8");
$conn1 = sqlsrv_connect( $serverName1, $connectionInfo1);

/////Query ordenes de cancelación de alimentos.
$sql = "Select ContactName,NickName, MainAddress from [dbo].[vwLBSContactList]";
/////Query ordenes de cancelación de alimentos.

$stmt = sqlsrv_query( $conn1, $sql );

$array_tot1 = [];

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
/////////////////////////////////////////////////// Array nuevas variables
// echo $row['COLABORADOR'];
array_push($array_tot1,$row['ContactName']);
}




$serverName = "DESAROLLO-BACRO\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>"Ticket", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);

// if( $conn ) {
     // echo "Conexión establecida.<br />";
// }else{
     // echo "Conexión no se pudo establecer.<br />";
     // die( print_r( sqlsrv_errors(), true));
// }


/////Query ordenes de cancelación de alimentos.
$sql = "Select * from conped order by nombre";
/////Query ordenes de cancelación de alimentos.

/// Ejecutar Query
// $stmt = sqlsrv_query( $conn, $sql );


$array_t1 = [];
$array_t2 = [];
$array_t3 = [];
$array_t4 = [];
$array_t5 = [];

/// Ejecutar Query
$stmt = sqlsrv_query( $conn, $sql );



if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {

array_push($array_t1,$row['Id_Empleado']);
array_push($array_t2,$row['Nombre']);
array_push($array_t3,$row['Area']);
array_push($array_t4,$row['Usuario']);
array_push($array_t5,$row['Contrasena']);


}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
 $name1 = test_input($_POST["filterNombre"]); /// Nombre
 $name2 = test_input($_POST["filterFechaInicio"]); /// DEPARTAMENTO
 $name3 = test_input($_POST["filterFechaFin"]); /// JEFE INMEDIATO QUE AUTORIZÓ
 
 // echo $name1;
 // echo $name2;
 // echo $name3;
 

$sql1 = "Select * from (
Select Id_Ticket as Id,Nombre,Correo,Prioridad,Empresa as Departamento,Asunto, Adjuntos as Problema,Mensaje,cast(Fecha as char) as Fecha ,left(cast(Hora as char) ,8) as Hora,Estatus
from [dbo].[TicketsSG]
where (convert(date,Fecha, 103)  >= '$name2' and convert(date,Fecha, 103)  <= '$name3') and nombre='$name1'
) as a";

$array_tt1 = [];
$array_tt2 = [];
$array_tt3 = [];
$array_tt4 = [];
$array_tt5 = [];
$array_tt6 = [];
$array_tt7 = [];
$array_tt8 = [];
$array_tt9 = [];
$array_tt10 = [];
$array_tt11 = [];


$stmt1 = sqlsrv_query( $conn, $sql1 );



if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row = sqlsrv_fetch_array( $stmt1, SQLSRV_FETCH_ASSOC) ) {

array_push($array_tt1,$row['Id']);
array_push($array_tt2,$row['Nombre']);
array_push($array_tt3,$row['Correo']);
array_push($array_tt4,$row['Prioridad']);
array_push($array_tt5,$row['Departamento']);
array_push($array_tt6,$row['Asunto']);
array_push($array_tt7,$row['Problema']);
array_push($array_tt8,$row['Mensaje']);
array_push($array_tt9,$row['Fecha']);
array_push($array_tt10,$row['Hora']);
array_push($array_tt11,$row['Estatus']);



}

 
 
 


}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CONSULTA TU TICKET</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />

  <!-- jQuery UI CSS para Datepicker -->
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <!-- Bootstrap Datepicker CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      padding: 20px;
      background: #f8f9fa;
    }

    .filter-group {
      margin-bottom: 1rem;
    }

    .ui-datepicker {
      font-size: 0.9rem;
    }

    /* Azul marino */
    .bg-navy {
      background-color: #001f3f !important;
      color: white !important;
    }

    thead.table-primary th {
      background-color: #001f3f !important;
      color: white;
    }

    .btn-navy {
      background-color: #001f3f;
      color: white;
    }

    .btn-navy:hover {
      background-color: #003366;
      color: white;
    }
	
	
	
    .table td{
  font-size|: 12px;
	    font-weight: bold;	
    }
	
	    .table th{
  font-size: 15px;
		  color: white;
  background: #1E4E79;
    font-weight: bold;
    }
	
	
	


/* Hover animaci贸n */
#homeButton:hover {
  background-color: #0022aa;
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
}
	
  </style>
</head>
<body>
  <div class="container-fluid">

  <h2 class="mb-0">SERVICIOS GENERALES TICKETS<div class="d-flex align-items-center mb-4"> <br>
  <button id="homeButton" title="Inicio" class="btn btn-primary me-2">
    <i class="fas fa-home"></i>
  </button></h2>
</div>
  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <div class="row filter-group">
      <div class="col-md-4">
        <label for="filterNombre" class="form-label">Filtrar por Nombre:</label>
        <select id="filterNombre"  name="filterNombre"  class="form-select">
          <!-- Agrega más nombres aquí si agregas más filas -->
        </select>
      </div>

      <div class="col-md-3">
        <label for="filterFechaInicio" class="form-label">Fecha Inicio:</label>
  <input type="text" class="form-control datepicker" id="filterFechaInicio" name="filterFechaInicio" autocomplete="off">
      </div>

      <div class="col-md-3">
        <label for="filterFechaFin" class="form-label">Fecha Fin:</label>
      <input type="text" class="form-control datepicker" id="filterFechaFin" name="filterFechaFin" autocomplete="off">
      </div>

      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-navy w-100">Buscar</button>
      </div>
	  
	      </form>
    </div>

    <table id="ticketsTable" class="table table-striped table-bordered" style="width:100%">
     
    </table>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- jQuery UI para Datepicker -->
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

  <!-- Popper.js y Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <!-- Bootstrap Datepicker JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>

  <script>
    $(document).ready(function () {
      $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        language: 'es',
        autoclose: true,
        todayHighlight: true
      });
    });
  </script>
  <script>
  
  
    document.getElementById("homeButton").addEventListener("click", function() {
  // Reemplaza "index.html" con la ruta a tu p谩gina de inicio
  window.location.href = "http://desarollo-bacros/TicketBacros/MenSG.php";
});

  	var dataQ1 = <?php echo json_encode($array_tot1);?>;
	
	// alert(dataQ1)
  // Llenar el <select> con las opciones
// Obtener el elemento <select> por su ID
const select1 = document.getElementById("filterNombre");

// Llenar el <select> con las opciones
dataQ1.forEach(opcion => {
  const optionElement = document.createElement("option");
  optionElement.value = opcion.toLowerCase().replace(/\s+/g, '_'); // valor (ej: opcion_1)
  optionElement.textContent = opcion; // texto visible
  select1.appendChild(optionElement);
});
  ///////////////////////// datos tabla 
  var datatt1 = <?php echo json_encode($array_tt1);?>;
  var datatt2 = <?php echo json_encode($array_tt2);?>;
  var datatt3 = <?php echo json_encode($array_tt3);?>;
  var datatt4 = <?php echo json_encode($array_tt4);?>;
  var datatt5 = <?php echo json_encode($array_tt5);?>;
  var datatt6 = <?php echo json_encode($array_tt6);?>;
  var datatt7 = <?php echo json_encode($array_tt7);?>;
  var datatt8 = <?php echo json_encode($array_tt8);?>;
  var datatt9 = <?php echo json_encode($array_tt9);?>;
  var datatt10 = <?php echo json_encode($array_tt10);?>;
  var datatt11 = <?php echo json_encode($array_tt11);?>;
  
    ///////////////////////// datos tabla 
  
  	var datat1 = <?php echo json_encode($array_t1);?>;
	var datat2 = <?php echo json_encode($array_t2);?>;
    var datat3 = <?php echo json_encode($array_t3);?>;
	var datat4 = <?php echo json_encode($array_t4);?>;
	var datat5 = <?php echo json_encode($array_t5);?>;
	
	
// var select = document.getElementById("filterNombre");

// // Limpiar y agregar opción inicial
// select.innerHTML = '<option value="">-- Todos --</option>';

// // Crear conjunto para evitar duplicados
// const nombresUnicos = [...new Set(datat2)];

// // Llenar el <select>
// nombresUnicos.forEach(nombre => {
  // const option = document.createElement("option");
  // option.value = nombre;
  // option.textContent = nombre;
  // select.appendChild(option);
// });
  
  
  
var dataSet = [];


for (let i = 0; i < datatt11.length; i++) {

  dataSet.push([datatt1[i],datatt2[i],datatt4[i],datatt5[i],datatt6[i],datatt7[i],datatt8[i],datatt9[i],datatt10[i],datatt11[i]]);
};
   
    
new DataTable('#ticketsTable', {
  data: dataSet,
  columns: [
    { title: 'Id' },
    { title: 'Nombre' },
    { title: 'Prioridad' },
    { title: 'Departamento' },
    { title: 'Asunto' },
    { title: 'Problema' },
    { title: 'Mensaje' },
	{ title: 'Fecha' },
	{ title: 'Hora' },
    { title: 'Estatus' }
  ],
  paging: true,
  pageLength: 500, // 📌 Aumenta el número de filas por página (puedes ajustarlo a 50 o más si deseas)
  lengthMenu: [500,1000,1500,2000,2500], // 📌 Opciones de cantidad de filas por página
  ordering: true,
  responsive: true,
  ordering: true,
  order: [[8, 'asc']], // 🟢 Ordenar por Fecha (Checador) y luego Fecha (Reservación)
  scrollX: true,
  scrollY: true, // 📌 Scroll vertical habilitado con altura fija
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  },
  select: true,
  dom: 'frtip'
});
  
  
    $(document).ready(function () {
      $("#filterFechaInicio, #filterFechaFin").datepicker({
        dateFormat: "yy-mm-dd",
      });

      // var table = $("#ticketsTable").DataTable({
        // language: {
          // url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json",
        // },
        // order: [[8, "desc"]],
      // });

      // $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        // var min = $("#filterFechaInicio").val();
        // var max = $("#filterFechaFin").val();
        // var fecha = data[8];

        // if (
          // (min === "" && max === "") ||
          // (min === "" && fecha <= max) ||
          // (min <= fecha && max === "") ||
          // (min <= fecha && fecha <= max)
        // ) {
          // return true;
        // }
        // return false;
      // });

      $("#btnBuscar").on("click", function () {
        var nombre = $("#filterNombre").val();
        table.column(1).search(nombre);
        table.draw();
      });
    });
  </script>
</body>
</html>
