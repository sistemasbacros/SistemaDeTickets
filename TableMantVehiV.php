<?php
/**
 * @file TableMantVehiV.php
 * @brief Tabla de mantenimiento vehicular - Vista alternativa.
 *
 * @description
 * Variante de TableMantVehi.php. Misma fuente de datos, diferente presentación.
 *
 * Migrado a Rust API:
 * - GET /api/TicketBacros/vehiculos  → lista todos los registros de TMANVEHI
 *
 * @see TableMantVehi.php
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */

require_once __DIR__ . '/config.php';
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

// Obtener registros de mantenimiento vehicular desde la API
$vehiculos = [];
$ch = curl_init($apiUrl . '/api/TicketBacros/vehiculos');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if (!$curlErr && $httpCode === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data)) {
        $vehiculos = $data;
    }
}

// Construir arrays paralelos (misma estructura que el original)
$array1  = []; $array2  = []; $array3  = []; $array4  = [];
$array5  = []; $array6  = []; $array7  = []; $array8  = [];
$array9  = []; $array10 = []; $array11 = []; $array12 = [];
$array13 = []; $array14 = []; $array15 = [];

foreach ($vehiculos as $row) {
    $array1[]  = $row['IDT']          ?? $row['idt']          ?? '';
    $array2[]  = $row['PLACAS']       ?? $row['placas']       ?? '';
    $array3[]  = $row['PROBLEMA']     ?? $row['problema']     ?? '';
    $array4[]  = $row['PRIORIDAD']    ?? $row['prioridad']    ?? '';
    $array5[]  = $row['FECHA']        ?? $row['fecha']        ?? '';
    $array6[]  = $row['NOMBRE']       ?? $row['nombre']       ?? '';
    $array7[]  = $row['DEPARTAMENTO'] ?? $row['departamento'] ?? '';
    $array8[]  = $row['Fecha_Ingreso'] ?? $row['fecha_ingreso'] ?? '';
    $array9[]  = $row['Hora_Ingreso']  ?? $row['hora_ingreso']  ?? '';
    $array10[] = $row['ASIGNADO']     ?? $row['asignado']     ?? '';
    $array11[] = $row['FECHA_RES']    ?? $row['fecha_res']    ?? '';
    $array12[] = $row['HORA']         ?? $row['hora']         ?? '';
    $array13[] = $row['ESTADO']       ?? $row['estado']       ?? '';
    $array14[] = $row['Diagnostico']  ?? $row['diagnostico']  ?? '';
    $array15[] = $row['COMENTARIOS']  ?? $row['comentarios']  ?? '';
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap.min.css" rel="stylesheet">

<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.2/moment.min.js"></script>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>

<style>
  /* Tabla moderna */
  .table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border-radius: 8px;
  }

  .table th {
    font-size: 16px;
    color: #ffffff;
    background: linear-gradient(90deg, #1E4E79, #2C6EB2);
    font-weight: 600;
    padding: 12px;
    text-align: left;
    letter-spacing: 0.5px;
  }

  .table td {
    font-size: 13px;
    font-weight: 500;
    padding: 10px 12px;
    color: #333;
    background-color: #f9f9f9;
    border-bottom: 1px solid #ddd;
    transition: background-color 0.3s;
  }

  .table tr:hover td {
    background-color: #eef5fc;
  }

  /* Botón personalizado */
  .btn-custom {
    font-size: 16px;
    font-weight: 600;
    padding: 10px 20px;
    border: none;
    background-color: #1E4E79;
    color: #fff;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 10px;
    transition: background-color 0.3s ease;
  }

  .btn-custom:hover {
    background-color: #163b5c;
  }

  /* Contenedor de botones en el encabezado */
  .header-buttons {
    margin-bottom: 24px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
</style>

<a href="M/website-menu-05/index1.html" style="color:black;font-size:25px;font-weight: bold;">INICIO</a>

<div>
  <div class="inner1">
    <table id="example" class="table table-striped table-bordered" width="100%">


<!-- Ordenes de Servicio -->

<script>

var darray1  = <?php echo json_encode($array1);  ?>;
var darray2  = <?php echo json_encode($array2);  ?>;
var darray3  = <?php echo json_encode($array3);  ?>;
var darray4  = <?php echo json_encode($array4);  ?>;
var darray5  = <?php echo json_encode($array5);  ?>;
var darray6  = <?php echo json_encode($array6);  ?>;
var darray7  = <?php echo json_encode($array7);  ?>;
var darray8  = <?php echo json_encode($array8);  ?>;
var darray9  = <?php echo json_encode($array9);  ?>;
var darray10 = <?php echo json_encode($array10); ?>;
var darray11 = <?php echo json_encode($array11); ?>;
var darray12 = <?php echo json_encode($array12); ?>;
var darray13 = <?php echo json_encode($array13); ?>;
var darray14 = <?php echo json_encode($array14); ?>;
var darray15 = <?php echo json_encode($array15); ?>;

var dataSet = [];

for (var i = 0; i < darray1.length; i++) {
    dataSet.push([darray1[i],darray2[i],darray3[i],darray4[i],darray5[i],darray6[i],darray7[i],darray8[i],darray9[i],darray10[i],darray11[i],darray12[i],darray13[i],darray14[i],darray15[i]]);
}

document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((el) => {
    el.addEventListener('shown.bs.tab', () => {
        DataTable.tables({ visible: true, api: true }).columnsv.adjust();
    });
});

new DataTable('#example', {
    columns: [
        { title: 'ID',                   searchable: false },
        { title: 'PLACAS',               searchable: false },
        { title: 'INCIDENCIA/PROBLEMA',  searchable: false },
        { title: 'PRIORIDAD',            searchable: false },
        { title: 'FECHA',                searchable: false },
        { title: 'NOMBRE',               searchable: false },
        { title: 'DEPARTAMENTO',         searchable: false },
        { title: 'FECHA_INGRESO',        searchable: false },
        { title: 'HORA_INGRESO',         searchable: false },
        { title: 'ASIGNADO',             searchable: true  },
        { title: 'FECHA CAMBIO ESTADO',  searchable: true  },
        { title: 'HORA CAMBIO ESTADO',   searchable: true  },
        { title: 'ESTADO',               searchable: true  },
        { title: 'DIAGNÓSTICO',          searchable: true  },
        { title: 'COMENTARIOS',          searchable: true  }
    ],
    data: dataSet,
    scrollX: 700,
    scrollY: 550,
    scrollCollapse: true,
    lengthMenu: [
        [200, -1],
        [200, 'All']
    ],
    columnDefs: [
        {
            targets: 4,
            render: DataTable.render.datetime('YYYY-MM-DD')
        }
    ]
});

function ExportToExcel(type, fn, dl) {
    var elt = document.getElementById('example');
    var wb  = XLSX.utils.table_to_book(elt, { raw: true, sheet: "Tickets" });
    return dl ?
        XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
        XLSX.writeFile(wb, fn || ('CentroCostos.' + (type || 'xlsx')));
}

var table = $('#example').DataTable();
var prueba = [];

$('#example').on('click', 'tr', function () {
    prueba = table.row(this).data();
});

table.on('click', 'tbody tr', (e) => {
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
    } else {
        table.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        classList.add('selected');
    }
});

function theFunction1() {
    var arrayJson = JSON.stringify(prueba);
    $.post("t1.php", { arrayDeValores: arrayJson }, function(data) {
        window.location.href = 'AsigBien.php';
    });
}
</script>
