<?php
// Conexión SQL Server
$serverName = "DESAROLLO-BACRO\SQLEXPRESS";
$connectionInfo = array("Database" => "Ticket", "UID" => "Larome03", "PWD" => "Larome03", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo);
if (!$conn) die(print_r(sqlsrv_errors(), true));

// Obtener personal (conped)
$sql = "SELECT * FROM conped ORDER BY nombre";
$stmt = sqlsrv_query($conn, $sql);
$usuarios = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $usuarios[] = $row['Nombre'];
}

// Obtener tickets
$sql1 = "SELECT Id_Ticket AS Id, Nombre, Correo, Prioridad, Empresa AS Departamento, Asunto, Adjuntos AS Problema, Mensaje, 
    CAST(Fecha AS CHAR) AS Fecha_capturat, LEFT(CAST(Hora AS CHAR), 8) AS Hora_capturat, Estatus ,
	 CAST(fecha_proceso AS CHAR) as fecha_proceso,LEFT(CAST(hora_proceso AS CHAR), 8) AS hora_proceso,
	 CAST(Fecha_Termino AS CHAR) as Fecha_Termino,LEFT(CAST(Hora_Termino AS CHAR), 8)  AS Hora_Termino,Enlace
	FROM TicketsSG";
$stmt1 = sqlsrv_query($conn, $sql1);
$tickets = [];
while ($row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
    $tickets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>CONSULTA TU TICKET</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
  
  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  
 <!-- DataTables Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script> 
  
  
  <style>
    body {
      padding: 20px;
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif; /* Fuente general */
    }

    h2.mb-4 {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      color: #001f3f; /* Azul fuerte para el título */
    }

    /* Header del DataTable */
    table.dataTable thead th {
      background-color: #001f3f !important;
      color: white !important;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 16px;
    }

    /* Estilo para las celdas */
    table.dataTable tbody td {
      font-size: 13px;
      font-weight: 500;
    }

    /* Botones azul marino */
    .btn-navy {
      background-color: #001f3f;
      color: white;
    }
    .btn-navy:hover {
      background-color: #003366;
      color: white;
    }
	
	/* Hover animaci贸n */
#homeButton:hover {
  background-color: #0022aa;
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
}

  </style>
</head>
<body class="bg-light p-4">
<div class="container-fluid">

  <button id="homeButton" title="Inicio" class="btn btn-primary me-2">
    <i class="fas fa-home"></i>
  </button>
  <h2 class="mb-4">Procesa tus tickets</h2>

  <table id="ticketsTable" class="table table-striped table-bordered w-100"></table>
</div>
<!-- MODAL Asignar Ticket -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="assignForm" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="assignModalLabel">Asignar Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ticketId" name="ticketId" />
        <div class="mb-3">
          <label class="form-label">Asignar a:</label>
          <input type="text" id="asignadoA" name="asignadoA" placeholder="Escribe tu nombre completo" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Nuevo estatus:</label>
          <select id="nuevoEstatus" name="nuevoEstatus" class="form-select" required>
            <option value="Pendiente">Pendiente</option>
            <option value="En proceso">En proceso</option>
            <option value="Resuelto">Resuelto</option>
            <option value="Cancelado">Cancelado</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Evidencia fotográfica:</label>
          <input type="file" id="evidenciaFoto" name="evidenciaFoto" accept="image/*" />
          <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Máx 5MB.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" />
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
  $('#assignForm').on('submit', function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
      url: 'actualizar_ticket.php',
      type: 'POST',
      data: formData,
      processData: false,  // importante para subir archivos
      contentType: false,  // importante para subir archivos
      success: function (resp) {
        alert('Se cargo tu imagen con éxito');
        location.reload();
      },
      error: function () {
        alert('Error al actualizar el ticket');
      }
    });
  });
</script>


<script>

    document.getElementById("homeButton").addEventListener("click", function() {
  // Reemplaza "index.html" con la ruta a tu p谩gina de inicio
  window.location.href = "http://192.168.100.95/TicketBacros/MenSG.php";
});



const tickets = <?php echo json_encode($tickets); ?>;
let selectedRow = null;
const table = new DataTable('#ticketsTable', {
  data: tickets.map(t => [
    t.Id, t.Nombre, t.Correo, t.Prioridad, t.Departamento,
    t.Asunto, t.Problema, t.Mensaje, t.Fecha_capturat, t.Hora_capturat,
    t.Estatus, t.fecha_proceso, t.hora_proceso, t.Fecha_Termino, t.Hora_Termino,
    t.Enlace ? `<a href="http://192.168.100.95/TicketBacros/${t.Enlace}" target="_blank">http://192.168.100.95/TicketBacros/${t.Enlace}</a>` : 'Sin evidencia'
  ]),
  columns: [
    { title: 'ID' }, { title: 'Nombre' }, { title: 'Correo' },
    { title: 'Prioridad' }, { title: 'Departamento' }, { title: 'Asunto' },
    { title: 'Problema' }, { title: 'Mensaje' }, { title: 'Fecha' },
    { title: 'Hora' }, { title: 'Estatus' }, { title: 'Fecha Proceso' },
    { title: 'Hora Proceso' }, { title: 'Fecha Término' }, { title: 'Hora Término' },
    { title: 'Evidencia' },
    {
      title: 'Acciones',
      orderable: false,
      render: function (data, type, row, meta) {
        return `<button class="btn btn-sm btn-primary assign-btn" data-id="${row[0]}">Asignar</button>`;
      }
    }
  ],
  pageLength: 20,
  responsive: true,
  language: {
    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
  },
  dom: 'Bfrtip', // Muestra botones arriba
  buttons: [
    {
      extend: 'excelHtml5',
      text: '<i class="fas fa-file-excel"></i> Exportar a Excel',
      className: 'btn btn-success btn-sm',
      exportOptions: {
        columns: ':not(:last-child)' // Excluye la columna de acciones
      }
    }
  ]
});


// Mostrar modal
$(document).on('click', '.assign-btn', function () {
  const ticketId = $(this).data('id');
  $('#ticketId').val(ticketId);
  $('#assignModal').modal('show');
});

// Enviar asignación
$('#assignForm').on('submit', function (e) {
  e.preventDefault();
  $.post('actualizar_ticket.php', $(this).serialize(), function (resp) {
    alert('Actualizado con éxito');
    location.reload();
  }).fail(function () {
    alert('Error al actualizar el ticket');
  });
});
</script>
</body>
</html>
