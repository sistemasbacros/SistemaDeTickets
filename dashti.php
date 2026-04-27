<?php
/**
 * @file dashti.php
 * @brief Dashboard TI con API de datos para DataTables.
 *
 * @description
 * Módulo híbrido que sirve tanto como página de dashboard como endpoint
 * de API para obtener datos de tickets. Cuando se llama con el parámetro
 * action=getData, retorna JSON; de lo contrario, renderiza la vista HTML.
 *
 * La fuente de datos es el Rust API (GET /api/TicketBacros/tickets).
 * Los campos se remapean al formato que espera el JS del dashboard:
 *   - Empresa → Departamento
 *   - responsable → Responsable
 *   - id_ticket → NoTicket
 *
 * @note Migrado de sqlsrv directo al Rust API. Los timestamps de Pausa,
 *       Terminado y Cancelado no son devueltos por el endpoint actual;
 *       se incluyen como null para mantener compatibilidad.
 *
 * @api_endpoints
 * - GET ?action=getData → JSON con array de tickets (sin envolver en "data")
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 * @updated 2026-04-23
 */

// Dispatcher: XHR / JSON callers go through the API guard so they
// get HTTP 401 instead of a 302 redirect.
if (($_GET['action'] ?? '') === 'getData' || ($_POST['action'] ?? '') === 'getData') {
    require_once __DIR__ . '/auth_check_api.php';
} else {
    require_once __DIR__ . '/auth_check.php';
}

// ── Si se solicita la data en JSON (ajax) ─────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'getData') {
    header('Content-Type: application/json');

    require_once __DIR__ . '/config.php';

    // URL base del Rust API
    $apiUrl   = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');
    $endpoint = $apiUrl . '/api/TicketBacros/tickets';

    // Llamada al API con cURL
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode < 200 || $httpCode >= 300) {
        echo json_encode([]);
        exit;
    }

    $apiResponse = json_decode($responseBody, true);

    if (!isset($apiResponse['data']) || !is_array($apiResponse['data'])) {
        echo json_encode([]);
        exit;
    }

    // ── Transformación: snake_case del API → aliases que usa el JS del dashboard ─
    //
    // El Rust API devuelve TicketTiData (snake_case):
    //   nombre, correo, prioridad, empresa, asunto, mensaje, adjuntos,
    //   fecha, hora, id_ticket, estatus, responsable,
    //   fecha_en_proceso, hora_en_proceso
    //
    // El JS del dashboard espera:
    //   Nombre, Correo, Prioridad, Departamento, Asunto, Mensaje, Adjuntos,
    //   Fecha, Hora, NoTicket, Estatus, Responsable,
    //   FechaEnProceso, HoraEnProceso,
    //   FechaPausa, HoraPausa, FechaTerminado, HoraTerminado,
    //   FechaCancelado, HoraCancelado
    //
    // Campos no disponibles en el endpoint actual → null

    $data = [];
    foreach ($apiResponse['data'] as $ticket) {
        $data[] = [
            'Nombre'         => $ticket['nombre']           ?? '',
            'Correo'         => $ticket['correo']           ?? '',
            'Prioridad'      => $ticket['prioridad']        ?? '',
            'Departamento'   => $ticket['empresa']          ?? '',
            'Asunto'         => $ticket['asunto']           ?? '',
            'Mensaje'        => $ticket['mensaje']          ?? '',
            'Adjuntos'       => $ticket['adjuntos']         ?? '',
            'Fecha'          => $ticket['fecha']            ?? null,
            'Hora'           => $ticket['hora']             ?? null,
            'NoTicket'       => $ticket['id_ticket']        ?? '',
            'Estatus'        => $ticket['estatus']          ?? '',
            'Responsable'    => $ticket['responsable']      ?? null,
            'FechaEnProceso' => $ticket['fecha_en_proceso'] ?? null,
            'HoraEnProceso'  => $ticket['hora_en_proceso']  ?? null,
            // No disponibles en el endpoint actual
            'FechaPausa'     => null,
            'HoraPausa'      => null,
            'FechaTerminado' => null,
            'HoraTerminado'  => null,
            'FechaCancelado' => null,
            'HoraCancelado'  => null,
        ];
    }

    // El JS original espera un array plano (no envuelto en "data")
    echo json_encode($data);
    exit;
}

// ── A partir de aquí: renderizado HTML del dashboard ─────────────────────────
header('Content-Type: text/html; charset=utf-8');
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
