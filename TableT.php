<?php
/**
 * @file TableT.php
 * @brief Tabla de gestión de tickets con interfaz DataTables.
 *
 * @description
 * Módulo de visualización tabular de tickets usando DataTables con Bootstrap 5.
 * Presenta una tabla interactiva con ordenamiento, búsqueda, paginación y
 * acciones por ticket. Diseño con tema azul corporativo y efectos CSS modernos.
 *
 * Características:
 * - DataTables con Bootstrap 5 styling
 * - Variables CSS personalizadas para tema corporativo
 * - Color scheme: azul primario (#1e4e79), verde éxito, naranja warning, rojo error
 * - Sin verificación de sesión explícita
 * - SweetAlert2 para confirmaciones y alertas
 * - Responsive design
 *
 * Este archivo es una versión alternativa de TableT1.php, posiblemente
 * usada para un contexto diferente o como respaldo.
 *
 * @module Módulo de Gestión de Tickets
 * @access Público o restringido (según implementación)
 *
 * @dependencies
 * - JS CDN: Bootstrap 5.3.0, Font Awesome 6.4.0, DataTables 1.13.4, SweetAlert2 11
 * - CSS CDN: Bootstrap 5, DataTables Bootstrap 5
 *
 * @css_variables
 * - --primary-blue: #1e4e79 (color principal)
 * - --dark-blue: #163d60 (variante oscura)
 * - --light-blue: #e2eaf3 (fondos claros)
 * - --success-green: #28a745 (éxito)
 * - --warning-orange: #ffc107 (advertencia)
 * - --error-red: #dc3545 (error)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.5
 * @since 2024
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <title>Gestión de Tickets</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- DataTables Bootstrap 5 -->
  <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
  
  <style>
    :root {
      --primary-blue: #1e4e79;
      --dark-blue: #163d60;
      --light-blue: #e2eaf3;
      --success-green: #28a745;
      --warning-orange: #ffc107;
      --error-red: #dc3545;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      color: #333;
      padding: 20px;
      margin: 0;
    }
    
    .header-container {
      text-align: center;
      margin-bottom: 30px;
      padding: 20px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    h1 {
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--primary-blue);
      position: relative;
      display: inline-block;
    }
    
    h1:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--primary-blue);
      border-radius: 2px;
    }
    
    .subtitle {
      color: #666;
      font-size: 1.1rem;
      margin-top: 15px;
    }
    
    .top-bar {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
      margin-bottom: 25px;
    }
    
    .btn-primary {
      background-color: var(--primary-blue);
      border: none;
      font-weight: 600;
      padding: 12px 25px;
      border-radius: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 3px 6px rgba(30,78,121,0.3);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-primary:hover {
      background-color: var(--dark-blue);
      box-shadow: 0 5px 15px rgba(22,61,96,0.4);
      transform: translateY(-2px);
    }
    
    .btn-icon {
      background: var(--primary-blue);
      border: none;
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 3px 6px rgba(30,78,121,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-icon:hover {
      background-color: var(--dark-blue);
      box-shadow: 0 5px 15px rgba(22,61,96,0.4);
      transform: translateY(-2px);
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
    
    /* Sticky header con fondo y texto blanco */
    table.dataTable thead th {
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: var(--primary-blue);
      color: white;
      font-weight: 600;
      padding: 12px;
      white-space: nowrap;
      border-bottom: 2px solid var(--dark-blue);
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
      background-color: var(--light-blue);
      cursor: pointer;
    }
    
    table.dataTable tbody tr.selected {
      background-color: #c4d8ec !important;
    }
    
    /* DataTables Pagination styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 6px 14px;
      margin: 0 3px;
      border-radius: 6px;
      background-color: #f0f0f0;
      color: var(--primary-blue) !important;
      font-weight: 600;
      transition: background-color 0.2s;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background-color: #d1e1f0;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background-color: var(--primary-blue) !important;
      color: white !important;
    }
    
    .modal-content {
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .modal-header {
      background-color: var(--primary-blue);
      color: white;
      border-radius: 12px 12px 0 0;
    }
    
    .btn-close-white {
      filter: invert(1);
    }
    
    .form-control, .form-select {
      border-radius: 8px;
      padding: 10px 15px;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 0.2rem rgba(30, 78, 121, 0.25);
    }
    
    /* Responsive tweaks */
    @media (max-width: 768px) {
      .top-bar {
        flex-direction: column;
        align-items: center;
      }
      
      .btn-primary {
        width: 100%;
        max-width: 320px;
        justify-content: center;
      }
      
      table.dataTable thead th, table.dataTable tbody td {
        white-space: normal;
        word-break: break-word;
      }
    }
  </style>
</head>
<body>

  <div class="header-container">
    <h1>Gestión de Tickets - 2025</h1>
    <p class="subtitle">Sistema de administración y seguimiento de tickets de soporte</p>
  </div>

  <div class="top-bar">
    <a href="M/website-menu-05/index.html" class="btn btn-primary">
      <i class="fas fa-home"></i> INICIO
    </a>
    <button class="btn btn-primary" id="assignTicketBtn">
      <i class="fas fa-user-check"></i> Asignar Ticket
    </button>
    <button class="btn-icon" id="exportExcelBtn" aria-label="Exportar a Excel">
      <i class="fas fa-file-excel"></i>
    </button>
  </div>

  <div class="table-responsive">
    <table id="ticketsTable" class="table table-striped table-bordered" style="width:100%">
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
          <th>Hora Inicio</th>
          <th>No Ticket</th>
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

  <!-- Modal Asignar -->
  <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="assignForm" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-check me-2"></i>Asignar Ticket
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticketId" name="ticketId" />
          <div class="mb-3">
            <label for="responsable" class="form-label">
              <i class="fas fa-user me-2"></i>Responsable
            </label>
            <input type="text" id="responsable" name="responsable" class="form-control" required />
          </div>
          <div class="mb-3">
            <label for="estatus" class="form-label">
              <i class="fas fa-flag me-2"></i>Estatus
            </label>
            <select id="estatus" name="estatus" class="form-select" required>
              <option value="">Seleccione</option>
              <option value="En Proceso">En Proceso</option>
              <option value="Pausa">Pausa</option>
              <option value="Atendido">Atendido</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="asunto" class="form-label">
              <i class="fas fa-tags me-2"></i>Asunto
            </label>
            <select id="asunto" name="asunto" class="form-select" required>
              <option value="">Seleccione</option>
              <option value="COMEDOR">🍽️ COMEDOR</option>
              <option value="ERP">💼 ERP</option>
              <option value="LAPTOP / PC">💻 LAPTOP / PC</option>
              <option value="IMPRESORA">🖨️ IMPRESORA</option>
              <option value="CONTPAQi">📊 CONTPAQi</option>
              <option value="CORREO">📧 CORREO</option>
              <option value="NUEVO INGRESO">👤 NUEVO INGRESO</option>
              <option value="CARPETAS ACCESO">📁 CARPETAS ACCESO</option>
              <option value="SALIDA EQUIPO">🚪 SALIDA EQUIPO</option>
              <option value="DIGITALIZACIÓN">📄 DIGITALIZACIÓN</option>
              <option value="BLOQUEO USB">🔒 BLOQUEO USB</option>
              <option value="TELEFONÍA">📞 TELEFONÍA</option>
              <option value="INTERNET">🌐 INTERNET</option>
              <option value="SOFTWARE">🛠️ SOFTWARE</option>
              <option value="INFRAESTRUCTURA TI">🏗️ INFRAESTRUCTURA TI</option>
              <option value="OTROS">❓ OTROS</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Moment.js y plugin para ordenar fechas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
  <script src="https://cdn.datatables.net/plug-ins/1.13.4/sorting/datetime-moment.js"></script>

  <!-- SheetJS para exportar Excel -->
  <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/shim.min.js"></script>
  <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

  <script>
    let table;
    let selectedRowData = null;

    $(document).ready(function() {
      // Registrar el plugin para ordenar fechas con Moment.js
      $.fn.dataTable.moment('DD/MM/YYYY');

      table = $('#ticketsTable').DataTable({
        ajax: 'fetch_tickets.php',
        columns: [
          { data: 'Nombre' },
          { data: 'Correo' },
          { data: 'Prioridad' },
          { data: 'Empresa' },
          { data: 'Asunto' },
          { data: 'Mensaje' },
          { data: 'Adjuntos' },
          { data: 'Fecha' },
          { data: 'Hora' },
          { data: 'Id_Ticket' },
          { data: 'Estatus' },
          { data: 'PA' },
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
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        lengthMenu: [10, 25, 50],
        scrollY: '400px',
        scrollX: true,
        scrollCollapse: true
      });

      // Selección de fila
      $('#ticketsTable tbody').on('click', 'tr', function() {
        if ($(this).hasClass('selected')) {
          $(this).removeClass('selected');
          selectedRowData = null;
        } else {
          table.$('tr.selected').removeClass('selected');
          $(this).addClass('selected');
          selectedRowData = table.row(this).data();
        }
      });

      // Botón para abrir modal asignar
      $('#assignTicketBtn').on('click', function() {
        if (!selectedRowData) {
          Swal.fire({
            title: '⚠️ Selección Requerida',
            html: `
              <div style="text-align: center; padding: 20px;">
                <div style="font-size: 60px; margin-bottom: 20px;">📋</div>
                <h3 style="color: #ffc107; margin-bottom: 15px;">Selecciona un Ticket</h3>
                <p style="color: #666;">Por favor selecciona un ticket de la tabla para poder asignarlo.</p>
              </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#1e4e79',
            confirmButtonText: 'Entendido'
          });
          return;
        }

        $('#ticketId').val(selectedRowData.Id_Ticket);
        $('#responsable').val(selectedRowData.PA || '');
        $('#estatus').val(selectedRowData.Estatus || '');
        $('#asunto').val(selectedRowData.Asunto || '');

        let assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
        assignModal.show();
      });

      // Enviar formulario asignar
      $('#assignForm').on('submit', function(e) {
        e.preventDefault();

        let data = {
          id_ticket: $('#ticketId').val(),
          responsable: $('#responsable').val(),
          estatus: $('#estatus').val(),
          asunto: $('#asunto').val()
        };

        // Mostrar loading
        Swal.fire({
          title: '🔄 Procesando',
          html: `
            <div style="text-align: center;">
              <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; margin: 20px 0;" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p style="color: #666; margin-top: 10px;">Actualizando ticket y enviando notificaciones...</p>
            </div>
          `,
          showConfirmButton: false,
          allowOutsideClick: false
        });

        $.ajax({
          url: 'update_ticket.php',
          method: 'POST',
          data: data,
          dataType: 'json',
          success: function(response) {
            Swal.close();
            
            if (response.success) {
              // Verificar si se enviaron notificaciones
              let notificationStatus = '';
              let icon = 'success';
              
              if (response.notifications) {
                if (response.notifications.user && response.notifications.admin) {
                  notificationStatus = `
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 10px; margin: 15px 0;">
                      <h5 style="color: #28a745; margin-bottom: 10px;">✅ Notificaciones Enviadas</h5>
                      <p style="margin: 5px 0;">📧 <strong>Usuario:</strong> ${response.notifications.user ? 'Enviada' : 'Error'}</p>
                      <p style="margin: 5px 0;">👨‍💼 <strong>Administrador:</strong> ${response.notifications.admin ? 'Enviada' : 'Error'}</p>
                    </div>
                  `;
                } else if (response.notifications.user || response.notifications.admin) {
                  notificationStatus = `
                    <div style="background: #fff3cd; padding: 15px; border-radius: 10px; margin: 15px 0;">
                      <h5 style="color: #856404; margin-bottom: 10px;">⚠️ Notificaciones Parciales</h5>
                      <p style="margin: 5px 0;">📧 <strong>Usuario:</strong> ${response.notifications.user ? 'Enviada' : 'Error'}</p>
                      <p style="margin: 5px 0;">👨‍💼 <strong>Administrador:</strong> ${response.notifications.admin ? 'Enviada' : 'Error'}</p>
                    </div>
                  `;
                  icon = 'warning';
                } else {
                  notificationStatus = `
                    <div style="background: #f8d7da; padding: 15px; border-radius: 10px; margin: 15px 0;">
                      <h5 style="color: #721c24; margin-bottom: 10px;">❌ No se enviaron notificaciones</h5>
                      <p>Error en el sistema de notificaciones</p>
                    </div>
                  `;
                  icon = 'warning';
                }
              }

              Swal.fire({
                title: '🎉 ¡Ticket Asignado!',
                html: `
                  <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px; margin-bottom: 20px;">✅</div>
                    <h3 style="color: #28a745; margin-bottom: 15px;">Ticket Actualizado Correctamente</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                      <p style="margin: 5px 0;"><strong>Ticket ID:</strong> ${response.ticket_id}</p>
                      <p style="margin: 5px 0;"><strong>Estado:</strong> ${response.estatus}</p>
                      <p style="margin: 5px 0;"><strong>Responsable:</strong> ${response.responsable}</p>
                    </div>
                    ${notificationStatus}
                    <p style="color: #666; margin-top: 15px;">El ticket ha sido actualizado y se han enviado las notificaciones correspondientes.</p>
                  </div>
                `,
                icon: icon,
                confirmButtonColor: '#1e4e79',
                confirmButtonText: 'Continuar'
              }).then((result) => {
                table.ajax.reload(null, false);
                let modalEl = document.getElementById('assignModal');
                let modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                selectedRowData = null;
              });
            } else {
              Swal.fire({
                title: '❌ Error',
                html: `
                  <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px; margin-bottom: 20px;">😞</div>
                    <h3 style="color: #dc3545; margin-bottom: 15px;">Error al Actualizar</h3>
                    <p style="color: #666;">${response.msg || 'Error desconocido'}</p>
                  </div>
                `,
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Reintentar'
              });
            }
          },
          error: function(xhr, status, error) {
            Swal.close();
            
            let errorMsg = 'Error de conexión con el servidor';
            try {
              const errorResponse = JSON.parse(xhr.responseText);
              errorMsg = errorResponse.msg || errorMsg;
            } catch (e) {
              errorMsg = xhr.responseText || error;
            }

            Swal.fire({
              title: '❌ Error del Servidor',
              html: `
                <div style="text-align: center; padding: 20px;">
                  <div style="font-size: 60px; margin-bottom: 20px;">🔌</div>
                  <h3 style="color: #dc3545; margin-bottom: 15px;">Error de Comunicación</h3>
                  <p style="color: #666;">${errorMsg}</p>
                </div>
              `,
              icon: 'error',
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'Reintentar'
            });
          }
        });
      });

      // Exportar a Excel
      $('#exportExcelBtn').on('click', function() {
        Swal.fire({
          title: '📊 Preparando Exportación',
          html: `
            <div style="text-align: center;">
              <div class="spinner-border text-primary" role="status" style="margin: 20px 0;">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p style="color: #666;">Preparando archivo Excel...</p>
            </div>
          `,
          showConfirmButton: false,
          allowOutsideClick: false
        });

        setTimeout(() => {
          const dataToExport = [];
          const headers = [];
          $('#ticketsTable thead th').each(function() {
            headers.push($(this).text().trim());
          });
          dataToExport.push(headers);

          table.rows({ search: 'applied' }).every(function() {
            const rowData = this.data();
            dataToExport.push([
              rowData.Nombre,
              rowData.Correo,
              rowData.Prioridad,
              rowData.Empresa,
              rowData.Asunto,
              rowData.Mensaje,
              rowData.Adjuntos,
              rowData.Fecha,
              rowData.Hora,
              rowData.Id_Ticket,
              rowData.Estatus,
              rowData.PA,
              rowData.FechaEnProceso,
              rowData.HoraEnProceso,
              rowData.FechaPausa,
              rowData.HoraPausa,
              rowData.FechaTerminado,
              rowData.HoraTerminado,
              rowData.FechaCancelado,
              rowData.HoraCancelado
            ]);
          });

          const ws = XLSX.utils.aoa_to_sheet(dataToExport);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Tickets');

          const fechaActual = new Date().toLocaleDateString('es-ES').replace(/\//g, '-');
          const fileName = `Tickets_BacroCorp_${fechaActual}.xlsx`;
          
          XLSX.writeFile(wb, fileName);
          
          Swal.close();
          
          Swal.fire({
            title: '✅ Exportación Exitosa',
            html: `
              <div style="text-align: center; padding: 20px;">
                <div style="font-size: 60px; margin-bottom: 20px;">📄</div>
                <h3 style="color: #28a745; margin-bottom: 15px;">Archivo Generado</h3>
                <p style="color: #666;">El archivo <strong>${fileName}</strong> se ha descargado correctamente.</p>
              </div>
            `,
            icon: 'success',
            confirmButtonColor: '#1e4e79',
            confirmButtonText: 'Excelente'
          });
        }, 500);
      });

    });
  </script>

</body>
</html>