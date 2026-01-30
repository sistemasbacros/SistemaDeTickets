<?php
// -- BACKEND PHP: Conexión y consulta --

header('Content-Type: text/html; charset=utf-8');
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = [
  "Database" => "Ticket",
  "UID" => "Larome03",
  "PWD" => "Larome03",
  "CharacterSet" => "UTF-8"
];

// Conectar a SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Si se solicita la data en JSON (ajax)
if (isset($_GET['action']) && $_GET['action'] === 'getData') {
    $sql = "
    SELECT 
        [Nombre], [Correo], [Prioridad], [Empresa] AS Departamento, [Asunto], [Mensaje], [Adjuntos],
        CONVERT(varchar, TRY_CONVERT(date, fecha, 103), 103) AS Fecha,  
        CONVERT(varchar(8), Hora, 108) AS Hora,
        [Id_Ticket] AS NoTicket, [Estatus], [PA] AS Responsable,
        CONVERT(varchar, TRY_CONVERT(date, FechaEnProceso, 103), 103) AS FechaEnProceso,
        CONVERT(varchar(8), HoraEnProceso, 108) AS HoraEnProceso,
        CONVERT(varchar, TRY_CONVERT(date, FechaPausa, 103), 103) AS FechaPausa,
        CONVERT(varchar(8), HoraPausa, 108) AS HoraPausa,
        CONVERT(varchar, TRY_CONVERT(date, FechaTerminado, 103), 103) AS FechaTerminado,
        CONVERT(varchar(8), HoraTerminado, 108) AS HoraTerminado,
        CONVERT(varchar, TRY_CONVERT(date, FechaCancelado, 103), 103) AS FechaCancelado,
        CONVERT(varchar(8), HoraCancelado, 108) AS HoraCancelado
    FROM [dbo].[T3] 
    WHERE YEAR(TRY_CONVERT(date, fecha, 103)) = YEAR(GETDATE()) 
      AND MONTH(TRY_CONVERT(date, fecha, 103)) = MONTH(GETDATE())
    ORDER BY TRY_CONVERT(date, fecha, 103) DESC;
    ";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo json_encode(["error" => sqlsrv_errors()]);
        exit;
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Ejecutivo de Tickets</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
  <style>
    /* Aquí va el estilo que me diste y mejoras para controles y espacio */
    body {
      font-family: 'Inter', sans-serif;
      background: #f9fbfc;
      color: #1e4e79;
      padding: 20px;
      margin: 0;
    }
    h1 {
      font-weight: 700;
      margin-bottom: 30px;
      text-align: center;
    }
    .top-bar {
      background: #1e4e79;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      color: white;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
      font-size: 18px;
      font-weight: 600;
    }
    .top-bar div span {
      color: #a9d3ff;
      font-weight: 700;
      font-size: 22px;
      margin-left: 5px;
    }
    .filters {
      margin-bottom: 30px;
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .filters label {
      font-weight: 600;
      color: #1e4e79;
      margin-right: 8px;
    }
    .filters input[type="date"] {
      padding: 6px 12px;
      border-radius: 6px;
      border: 1.8px solid #1e4e79;
      font-size: 16px;
      font-weight: 600;
      color: #1e4e79;
      min-width: 160px;
      transition: border-color 0.3s ease;
    }
    .filters input[type="date"]:focus {
      border-color: #163d60;
      outline: none;
      box-shadow: 0 0 8px #163d60aa;
    }
    .dashboard-charts {
      display: grid;
      grid-template-columns: repeat(auto-fit,minmax(280px,1fr));
      gap: 35px;
      margin-bottom: 40px;
    }
    .chart-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 320px;
    }
    .chart-card h3 {
      margin-bottom: 15px;
      color: #1e4e79;
      font-weight: 700;
    }
    .table-responsive {
      overflow-x: auto;
      border-radius: 12px;
      background: white;
      padding: 15px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    table.dataTable {
      width: 100% !important;
      font-size: 13px;
      border-collapse: separate !important;
      border-spacing: 0;
    }
    table.dataTable thead th {
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: #1e4e79;
      color: white;
      font-weight: 600;
      padding: 12px;
      white-space: nowrap;
      border-bottom: 2px solid #14314d;
    }
    table.dataTable tbody td {
      padding: 10px;
      vertical-align: middle;
      white-space: nowrap;
    }
    table.dataTable tbody tr:nth-child(odd) {
      background-color: #f5f7fa;
    }
    table.dataTable tbody tr:hover {
      background-color: #e2eaf3;
      cursor: pointer;
    }
    table.dataTable tbody tr.selected {
      background-color: #c4d8ec !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 6px 14px;
      margin: 0 3px;
      border-radius: 6px;
      background-color: #f0f0f0;
      color: #1e4e79 !important;
      font-weight: 600;
      transition: background-color 0.2s;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background-color: #d1e1f0;
    }
    @media (max-width: 768px) {
      .filters {
        flex-direction: column;
        align-items: center;
      }
      .top-bar {
        flex-direction: column;
        gap: 10px;
      }
      .dashboard-charts {
        grid-template-columns: 1fr;
      }
      table.dataTable thead th, table.dataTable tbody td {
        white-space: normal;
        word-break: break-word;
      }
    }
  </style>
</head>
<body>

<h1>Dashboard Ejecutivo de Tickets</h1>

<div class="top-bar">
  <div>Total Tickets: <span id="total-tickets">0</span></div>
  <div>Tickets Atendidos: <span id="tickets-atendidos">0</span></div>
  <div>Tickets Pendientes: <span id="tickets-pendientes">0</span></div>
  <div>Tiempo Medio (hrs): <span id="avg-time">0</span></div>
</div>

<div class="filters">
  <label for="fecha-inicio">Fecha Inicio:</label>
  <input type="date" id="fecha-inicio" />
  <label for="fecha-fin">Fecha Fin:</label>
  <input type="date" id="fecha-fin" />
</div>

<div class="dashboard-charts">
  <div class="chart-card">
    <h3>Estatus</h3>
    <canvas id="estatusChart"></canvas>
  </div>
  <div class="chart-card">
    <h3>Asunto</h3>
    <canvas id="asuntoChart"></canvas>
  </div>
  <div class="chart-card">
    <h3>Departamento</h3>
    <canvas id="departamentoChart"></canvas>
  </div>
  <div class="chart-card">
    <h3>Prioridad</h3>
    <canvas id="prioridadChart"></canvas>
  </div>
  <div class="chart-card">
    <h3>Responsable</h3>
    <canvas id="responsableChart"></canvas>
  </div>
</div>

<div class="table-responsive">
  <table id="ticketsTable" class="display nowrap" style="width:100%">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Prioridad</th>
        <th>Departamento</th>
        <th>Asunto</th>
        <th>Mensaje</th>
        <th>Adjunto</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>No. Ticket</th>
        <th>Estatus</th>
        <th>Responsable</th>
        <th>FechaEnProceso</th>
        <th>HoraEnProceso</th>
        <th>FechaPausa</th>
        <th>HoraPausa</th>
        <th>FechaTerminado</th>
        <th>HoraTerminado</th>
        <th>FechaCancelado</th>
        <th>HoraCancelado</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  let ticketsData = [];
  let tabla;
  let estatusChart, asuntoChart, departamentoChart, prioridadChart, responsableChart;

  function parseDateDMY(dateStr) {
    if (!dateStr) return null;
    const parts = dateStr.split('/');
    if(parts.length !== 3) return null;
    return new Date(+parts[2], parts[1] - 1, +parts[0]);
  }

  function calcularKPIs(data) {
    const total = data.length;
    const atendidos = data.filter(t => t.Estatus.toLowerCase() === 'atendido').length;
    const pendientes = total - atendidos;

    let totalHoras = 0;
    let countConTiempo = 0;
    data.forEach(t => {
      const inicio = parseDateDMY(t.Fecha);
      const fin = parseDateDMY(t.FechaTerminado);
      if (inicio && fin && fin >= inicio) {
        // Considera también Hora si quisieras precisión a nivel horas/minutos
        const diffMs = fin - inicio;
        const diffHrs = diffMs / 1000 / 60 / 60;
        totalHoras += diffHrs;
        countConTiempo++;
      }
    });
    const avg = countConTiempo > 0 ? (totalHoras / countConTiempo).toFixed(2) : 0;

    return { total, atendidos, pendientes, avg };
  }

  function contarPorCampo(data, campo) {
    const counts = {};
    data.forEach(item => {
      const key = item[campo] || "Sin asignar";
      counts[key] = (counts[key] || 0) + 1;
    });
    return counts;
  }

  function crearChart(ctx, labels, data, bgColors, tipo='doughnut') {
    return new Chart(ctx, {
      type: tipo,
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: bgColors,
          borderWidth: 1,
          borderColor: '#fff',
          hoverOffset: 20,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#1e4e79',
              font: { weight: '600' },
              padding: 20,
              boxWidth: 18,
              boxHeight: 18,
              usePointStyle: true,
            }
          },
          tooltip: {
            enabled: true,
            backgroundColor: '#1e4e79',
            titleColor: '#fff',
            bodyColor: '#fff',
            cornerRadius: 6,
            padding: 12,
            displayColors: false,
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.parsed}`,
            }
          },
        },
        cutout: tipo === 'doughnut' ? '60%' : undefined,
        animation: {
          duration: 1000,
          easing: 'easeOutQuart'
        },
        layout: {
          padding: 10
        }
      }
    });
  }

  function actualizarCharts(data) {
    const estatusData = contarPorCampo(data, 'Estatus');
    const asuntoData = contarPorCampo(data, 'Asunto');
    const departamentoData = contarPorCampo(data, 'Departamento');
    const prioridadData = contarPorCampo(data, 'Prioridad');
    const responsableData = contarPorCampo(data, 'Responsable');

    const palette = [
      '#1e4e79', '#3a7bd5', '#00c6ff', '#6a11cb', '#2575fc', '#009fff', '#005f73',
      '#9d4edd', '#f72585', '#720026', '#3f0071', '#f9c74f', '#f9844a', '#90be6d'
    ];

    function obtenerColores(n) {
      let colors = [];
      for (let i = 0; i < n; i++) {
        colors.push(palette[i % palette.length]);
      }
      return colors;
    }

    if (estatusChart) estatusChart.destroy();
    if (asuntoChart) asuntoChart.destroy();
    if (departamentoChart) departamentoChart.destroy();
    if (prioridadChart) prioridadChart.destroy();
    if (responsableChart) responsableChart.destroy();

    estatusChart = crearChart(
      document.getElementById('estatusChart').getContext('2d'),
      Object.keys(estatusData),
      Object.values(estatusData),
      obtenerColores(Object.keys(estatusData).length)
    );

    asuntoChart = crearChart(
      document.getElementById('asuntoChart').getContext('2d'),
      Object.keys(asuntoData),
      Object.values(asuntoData),
      obtenerColores(Object.keys(asuntoData).length)
    );

    departamentoChart = crearChart(
      document.getElementById('departamentoChart').getContext('2d'),
      Object.keys(departamentoData),
      Object.values(departamentoData),
      obtenerColores(Object.keys(departamentoData).length)
    );

    prioridadChart = crearChart(
      document.getElementById('prioridadChart').getContext('2d'),
      Object.keys(prioridadData),
      Object.values(prioridadData),
      obtenerColores(Object.keys(prioridadData).length)
    );

    responsableChart = crearChart(
      document.getElementById('responsableChart').getContext('2d'),
      Object.keys(responsableData),
      Object.values(responsableData),
      obtenerColores(Object.keys(responsableData).length)
    );
  }

  function inicializarTabla(data) {
    if ($.fn.dataTable.isDataTable('#ticketsTable')) {
      tabla.clear().rows.add(data).draw();
    } else {
      tabla = $('#ticketsTable').DataTable({
        data: data,
        columns: [
          { data: 'Nombre' },
          { data: 'Correo' },
          { data: 'Prioridad' },
          { data: 'Departamento' },
          { data: 'Asunto' },
          { data: 'Mensaje' },
          { data: 'Adjuntos' },
          { data: 'Fecha' },
          { data: 'Hora' },
          { data: 'NoTicket' },
          { data: 'Estatus' },
          { data: 'Responsable' },
          { data: 'FechaEnProceso' },
          { data: 'HoraEnProceso' },
          { data: 'FechaPausa' },
          { data: 'HoraPausa' },
          { data: 'FechaTerminado' },
          { data: 'HoraTerminado' },
          { data: 'FechaCancelado' },
          { data: 'HoraCancelado' }
        ],
        order: [[7, 'desc']],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50],
        scrollX: true,
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
      });
    }
  }

  function filtrarPorFecha(data, inicio, fin) {
    if (!inicio && !fin) return data;

    const startDate = inicio ? new Date(inicio) : null;
    const endDate = fin ? new Date(fin) : null;

    return data.filter(d => {
      const fecha = parseDateDMY(d.Fecha);
      if (!fecha) return false;
      if (startDate && fecha < startDate) return false;
      if (endDate && fecha > endDate) return false;
      return true;
    });
  }

  function actualizarDashboard(data) {
    // KPIs
    const kpis = calcularKPIs(data);
    $('#total-tickets').text(kpis.total);
    $('#tickets-atendidos').text(kpis.atendidos);
    $('#tickets-pendientes').text(kpis.pendientes);
    $('#avg-time').text(kpis.avg);

    // Charts
    actualizarCharts(data);

    // Table
    inicializarTabla(data);
  }

  $(document).ready(function() {
    // Cargar datos del backend
    $.ajax({
      url: '?action=getData',
      method: 'GET',
      dataType: 'json',
      success: function(res) {
        ticketsData = res;
        actualizarDashboard(ticketsData);
      },
      error: function() {
        alert('Error al cargar los datos del servidor.');
      }
    });

    // Filtrado fecha
    $('#fecha-inicio, #fecha-fin').on('change', function() {
      const inicio = $('#fecha-inicio').val();
      const fin = $('#fecha-fin').val();
      const datosFiltrados = filtrarPorFecha(ticketsData, inicio, fin);
      actualizarDashboard(datosFiltrados);
    });
  });
</script>

</body>
</html>
