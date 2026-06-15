<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SERVICIOS GENERALES | BACROCORP PREMIUM</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(145deg, #f0f7ff 0%, #e6f0fa 50%, #d4e4f5 100%);
      min-height: 100vh;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }

    /* Patrón de fondo sutil */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: radial-gradient(circle at 20% 30%, rgba(0, 51, 102, 0.03) 0%, transparent 30%),
                       radial-gradient(circle at 80% 70%, rgba(0, 102, 204, 0.03) 0%, transparent 30%);
      pointer-events: none;
    }

    /* Modal de carga */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 51, 102, 0.9);
      backdrop-filter: blur(8px);
      z-index: 9999;
      display: none;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    .loading-overlay.active {
      display: flex;
      animation: fadeIn 0.3s ease-out;
    }

    .loading-modal {
      background: white;
      border-radius: 30px;
      padding: 40px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      animation: slideUpModal 0.5s ease-out;
    }

    .loading-spinner {
      width: 80px;
      height: 80px;
      margin: 0 auto 25px;
      border: 5px solid #e1e9f5;
      border-top-color: #0066cc;
      border-radius: 50%;
      animation: spin 1s infinite linear;
    }

    .loading-title {
      font-family: 'Manrope', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #003366;
      margin-bottom: 15px;
    }

    .loading-message {
      font-size: 1rem;
      color: #4a5b6e;
      margin-bottom: 25px;
      line-height: 1.6;
    }

    .loading-progress {
      height: 8px;
      background: #e1e9f5;
      border-radius: 4px;
      overflow: hidden;
      margin: 20px 0;
    }

    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #003366, #0066cc, #0099ff);
      width: 0%;
      transition: width 0.3s ease;
      border-radius: 4px;
    }

    .loading-step {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 10px 0;
      padding: 8px 12px;
      background: #f8fcff;
      border-radius: 12px;
    }

    .step-icon {
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .step-text {
      font-size: 0.95rem;
      color: #1a2b3c;
    }

    .step-pending {
      opacity: 0.5;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideUpModal {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Contenedor principal */
    .premium-container {
      max-width: 1000px;
      width: 100%;
      margin: 30px auto;
      position: relative;
      z-index: 10;
    }

    /* Tarjeta principal */
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 102, 204, 0.15);
      border-radius: 40px;
      box-shadow: 0 25px 50px -12px rgba(0, 51, 102, 0.25), 0 0 0 1px rgba(0, 102, 204, 0.1) inset;
      padding: 40px;
      position: relative;
      overflow: hidden;
    }

    .glass-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #003366, #0066cc, #0099ff);
    }

    /* Header con logo */
    .header-section {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid rgba(0, 102, 204, 0.1);
    }

    .title-container {
      display: flex;
      flex-direction: column;
    }

    .system-title {
      font-family: 'Manrope', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #003366, #0066cc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -0.5px;
      line-height: 1.2;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .system-title i {
      font-size: 2rem;
      background: linear-gradient(135deg, #003366, #0066cc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .system-subtitle {
      font-size: 0.95rem;
      color: #4a5b6e;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 5px;
    }

    .system-subtitle i {
      color: #0066cc;
      font-size: 0.8rem;
    }

    .logo-wrapper {
      width: 100px;
      height: 100px;
      background: white;
      border-radius: 25px;
      box-shadow: 0 15px 30px rgba(0, 102, 204, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(0, 102, 204, 0.2);
      padding: 12px;
    }

    .logo-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    /* Grid del formulario */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    .full-width {
      grid-column: span 3;
    }

    .double-width {
      grid-column: span 2;
    }

    /* Grupos de formulario */
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #003366;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-label i {
      color: #0066cc;
      font-size: 0.95rem;
      width: 20px;
    }

    /* Inputs y selects */
    .form-control, .select2-container--bootstrap-5 .select2-selection {
      width: 100%;
      padding: 14px 16px;
      background: #ffffff;
      border: 2px solid #e1e9f5;
      border-radius: 16px;
      font-size: 0.95rem;
      font-family: 'Outfit', sans-serif;
      font-weight: 500;
      color: #1a2b3c;
      transition: all 0.3s ease;
      box-shadow: 0 2px 6px rgba(0, 102, 204, 0.05);
      height: auto !important;
    }

    .form-control:hover {
      border-color: #99c2ff;
      background: #ffffff;
      box-shadow: 0 8px 15px rgba(0, 102, 204, 0.1);
    }

    .form-control:focus {
      border-color: #0066cc;
      background: #ffffff;
      box-shadow: 0 10px 25px rgba(0, 102, 204, 0.15);
      outline: none;
    }

    /* Estilos Select2 */
    .select2-container--bootstrap-5 .select2-selection {
      padding: 8px 16px;
      min-height: 52px;
      display: flex;
      align-items: center;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
      padding: 0;
      color: #1a2b3c;
      font-family: 'Outfit', sans-serif;
      font-size: 0.95rem;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
      height: 100%;
      right: 16px;
    }

    .select2-container--bootstrap-5 .select2-dropdown {
      border: 2px solid #0066cc;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0, 102, 204, 0.15);
    }

    .select2-container--bootstrap-5 .select2-results__option {
      padding: 12px 16px;
      font-family: 'Outfit', sans-serif;
      font-size: 0.95rem;
    }

    .select2-container--bootstrap-5 .select2-results__option--highlighted {
      background: #e6f0ff;
      color: #003366;
    }

    .select2-search__field {
      padding: 10px !important;
      border: 2px solid #e1e9f5 !important;
      border-radius: 12px !important;
      font-family: 'Outfit', sans-serif !important;
    }

    .select2-search__field:focus {
      border-color: #0066cc !important;
      outline: none !important;
      box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1) !important;
    }

    /* Select normal */
    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%230066cc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
      background-repeat: no-repeat;
      background-position: right 16px center;
      background-size: 14px;
      padding-right: 45px;
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
      line-height: 1.6;
    }

    /* Campos de solo lectura */
    .form-control[readonly] {
      background: #f0f7ff;
      border-color: #cce0ff;
      color: #003366;
      font-weight: 600;
      cursor: default;
    }

    /* Input icon */
    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #99b8d9;
      pointer-events: none;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      z-index: 2;
    }

    .form-control:focus ~ .input-icon {
      color: #0066cc;
    }

    /* Botón submit */
    .btn-submit {
      background: linear-gradient(135deg, #003366, #0066cc, #0099ff);
      color: white;
      font-size: 1.2rem;
      font-weight: 700;
      padding: 18px 30px;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.4s ease;
      margin-top: 35px;
      width: 100%;
      text-transform: uppercase;
      letter-spacing: 2px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 15px 30px rgba(0, 102, 204, 0.3);
    }

    .btn-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.6s;
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 20px 40px rgba(0, 102, 204, 0.4);
    }

    .btn-submit:hover::before {
      left: 100%;
    }

    .btn-submit i {
      font-size: 1.3rem;
      transition: transform 0.3s ease;
    }

    .btn-submit:hover i {
      transform: translateX(5px);
    }

    /* Vista previa - Iconos fijos con Font Awesome */
    .ticket-preview {
      background: #f8fcff;
      border: 2px dashed rgba(0, 102, 204, 0.2);
      border-radius: 25px;
      padding: 25px;
      margin-top: 30px;
    }

    .preview-title {
      font-family: 'Manrope', sans-serif;
      font-weight: 700;
      color: #003366;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.1rem;
    }

    .preview-title i {
      color: #0066cc;
    }

    .preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
    }

    .preview-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid rgba(0, 102, 204, 0.1);
    }

    .preview-label {
      color: #4a5b6e;
      font-weight: 500;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .preview-label i {
      color: #0066cc;
      font-size: 0.9rem;
      width: 18px;
    }

    .preview-value {
      color: #003366;
      font-weight: 700;
      font-size: 0.9rem;
    }

    /* Botón home */
    #homeButton {
      position: fixed;
      top: 25px;
      left: 25px;
      width: 60px;
      height: 60px;
      background: white;
      border: none;
      border-radius: 20px;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 10px 25px rgba(0, 51, 102, 0.15);
      transition: all 0.3s ease;
      z-index: 1000;
      color: #0066cc;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(0, 102, 204, 0.2);
      text-decoration: none;
    }

    #homeButton:hover {
      background: #0066cc;
      color: white;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 15px 35px rgba(0, 102, 204, 0.3);
      border-color: #0066cc;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .glass-card {
        padding: 25px;
      }

      .header-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }

      .logo-wrapper {
        width: 80px;
        height: 80px;
        align-self: flex-end;
      }

      .system-title {
        font-size: 1.8rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .full-width, .double-width {
        grid-column: span 1;
      }

      #homeButton {
        top: 15px;
        left: 15px;
        width: 50px;
        height: 50px;
        font-size: 20px;
      }

      .loading-modal {
        padding: 25px;
      }
      
      .loading-title {
        font-size: 1.5rem;
      }
    }

    /* Animaciones */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .glass-card {
      animation: fadeInUp 0.6s ease-out;
    }

    ::placeholder {
      color: #99b8d9;
      font-weight: 300;
    }
  </style>
</head>
<body>
  <!-- Modal de Carga -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-modal">
      <div class="loading-spinner"></div>
      <h2 class="loading-title">Procesando Ticket</h2>
      <p class="loading-message">Estamos guardando tu solicitud. Por favor espera...</p>
      
      <div class="loading-progress">
        <div class="progress-bar-fill" id="progressBar"></div>
      </div>
      
      <div class="loading-step" id="step1">
        <div class="step-icon" id="step1Icon"><i class="fas fa-spinner fa-spin" style="color: #0066cc;"></i></div>
        <div class="step-text" id="step1Text">Validando información del ticket...</div>
      </div>
      
      <div class="loading-step step-pending" id="step2">
        <div class="step-icon" id="step2Icon"><i class="fas fa-circle-notch" style="color: #99b8d9;"></i></div>
        <div class="step-text" id="step2Text">Guardando en base de datos...</div>
      </div>
      
      <div class="loading-step step-pending" id="step3">
        <div class="step-icon" id="step3Icon"><i class="fas fa-circle-notch" style="color: #99b8d9;"></i></div>
        <div class="step-text" id="step3Text">Generando ID de ticket...</div>
      </div>
      
      <div class="loading-step step-pending" id="step4">
        <div class="step-icon" id="step4Icon"><i class="fas fa-circle-notch" style="color: #99b8d9;"></i></div>
        <div class="step-text" id="step4Text">Finalizando proceso...</div>
      </div>
    </div>
  </div>

  <!-- Botón Home -->
  <a href="http://desarollo-bacros/TicketBacros/MenSG.php" id="homeButton" title="Inicio">
    <i class="fas fa-home"></i>
  </a>

  <!-- Contenedor Principal -->
  <div class="premium-container">
    <div class="glass-card">
      <!-- Header con logo -->
      <div class="header-section">
        <div class="title-container">
          <h1 class="system-title">
            <i class="fas fa-shield-alt"></i>
            SERVICIOS GENERALES
          </h1>
          <div class="system-subtitle">
            <i class="fas fa-circle" style="font-size: 0.5rem; color: #00cc66;"></i>
            <span>Sistema de Gestión de Tickets | BACROCORP</span>
          </div>
        </div>
        <div class="logo-wrapper">
          <img src="Logo2.png" alt="Logo BacroCorp" />
        </div>
      </div>

      <!-- Formulario -->
      <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="ticketForm">
        <div class="form-grid">
          <!-- 1. NOMBRE (con búsqueda) -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-user-circle"></i> NOMBRE DEL SOLICITANTE
            </label>
            <div class="input-wrapper">
              <select class="form-control select-search" id="Nombre" name="Nombre" required>
                <option value="" disabled selected>👤 Seleccione un nombre...</option>
              </select>
              <i class="fas fa-search input-icon"></i>
            </div>
            <small style="color: #4a5b6e; font-size: 0.75rem; margin-top: 5px;">
              <i class="fas fa-info-circle"></i> Escriba para buscar por nombre
            </small>
          </div>

          <!-- 2. PRIORIDAD -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-flag"></i> PRIORIDAD
            </label>
            <div class="input-wrapper">
              <select class="form-control" id="prio" name="prio" required>
                <option value="Bajo">🟢 Bajo</option>
                <option value="Medio">🟡 Medio</option>
                <option value="Alto">🔴 Alto</option>
              </select>
              <i class="fas fa-bolt input-icon"></i>
            </div>
          </div>

          <!-- 3. UBICACIÓN (se guarda en Empresa) -->
          <div class="form-group double-width">
            <label class="form-label">
              <i class="fas fa-map-marker-alt"></i> UBICACIÓN (PISO/LUGAR)
            </label>
            <div class="input-wrapper">
              <select class="form-control" id="empre" name="empre" required>
                <option value="PRIMERO">🏢 PRIMER PISO</option>
                <option value="SEGUNDO">🏢 SEGUNDO PISO</option>
                <option value="TERCER">🏢 TERCER PISO</option>
                <option value="CUARTO">🏢 CUARTO PISO</option>
                <option value="QUINTO">🏢 QUINTO PISO</option>
                <option value="CASA">🏠 CASA</option>
                <option value="PATIO">🌳 PATIO</option>
                <option value="ESTACIONAMIENTO">🅿️ ESTACIONAMIENTO</option>
                <option value="ESTACIONAMIENTO_EXTERIOR">🅿️ ESTACIONAMIENTO EXTERIOR</option>
                <option value="TALLER_EXTERNO">🔧 TALLER EXTERNO</option>
                <option value="COMISION">📋 COMISIÓN</option>
              </select>
              <i class="fas fa-location-dot input-icon"></i>
            </div>
          </div>

          <!-- 4. ÁREA (se guarda en Area_Piso) -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-building"></i> ÁREA (DEPARTAMENTO)
            </label>
            <div class="input-wrapper">
              <select class="form-control" id="area" name="area" required>
                <option value="MANTENIMIENTOS">🔧 MANTENIMIENTOS</option>
                <option value="INTENDENCIA">🧹 INTENDENCIA</option>
                <option value="VIGILANCIA">🚔 VIGILANCIA</option>
                <option value="OTRO">📌 OTRO</option>
              </select>
              <i class="fas fa-sitemap input-icon"></i>
            </div>
          </div>

          <!-- 5. TIPO DE TICKET (se guarda en Asunto) -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-ticket-alt"></i> TIPO DE TICKET
            </label>
            <div class="input-wrapper">
              <select class="form-control" id="tipo_ticket" name="tipo_ticket" required>
                <option value="PREVENTIVO">🛡️ PREVENTIVO</option>
                <option value="CORRECTIVO">🔧 CORRECTIVO</option>
                <option value="PREDICTIVO">📊 PREDICTIVO</option>
                <option value="SOMION">⚙️ COMISIÓN</option>
              </select>
              <i class="fas fa-tag input-icon"></i>
            </div>
          </div>

          <!-- 6. DESCRIPCIÓN (se guarda en Falla) -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-align-left"></i> DESCRIPCIÓN (FALLA)
            </label>
            <div class="input-wrapper">
              <input type="text" class="form-control" id="adj" name="adj" placeholder="Ej: Falla en iluminación, problema de plomería..." required />
              <i class="fas fa-pen input-icon"></i>
            </div>
          </div>

          <!-- 7. MENSAJE (se guarda en Mensaje) -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-message"></i> MENSAJE (DETALLES)
            </label>
            <div class="input-wrapper">
              <textarea class="form-control" id="men" name="men" placeholder="Proporcione todos los detalles necesarios..."></textarea>
              <i class="fas fa-file-alt input-icon" style="top: 18px; transform: none;"></i>
            </div>
          </div>

          <!-- 8. FECHA Y HORA -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-calendar"></i> FECHA
            </label>
            <div class="input-wrapper">
              <input class="form-control" id="fecha" name="fecha" type="text" required readonly />
              <i class="fas fa-calendar-day input-icon"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-clock"></i> HORA
            </label>
            <div class="input-wrapper">
              <input type="text" class="form-control" id="Hora" name="Hora" required readonly />
              <i class="fas fa-clock input-icon"></i>
            </div>
          </div>

          <!-- 9. ID TICKET -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-qrcode"></i> ID TICKET
            </label>
            <div class="input-wrapper">
              <input type="text" class="form-control" id="tik" name="tik" required readonly />
              <i class="fas fa-hashtag input-icon"></i>
            </div>
          </div>
        </div>

        <!-- Vista Previa - CON ICONOS DE FONT AWESOME FIJOS -->
        <div class="ticket-preview">
          <div class="preview-title">
            <i class="fas fa-eye"></i> VISTA PREVIA DEL TICKET
          </div>
          <div class="preview-grid">
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-id-card"></i> ID:</span>
              <span class="preview-value" id="previewId">TI-000000</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-calendar"></i> Fecha:</span>
              <span class="preview-value" id="previewDate">Cargando...</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-clock"></i> Hora:</span>
              <span class="preview-value" id="previewTime">Cargando...</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-flag"></i> Prioridad:</span>
              <span class="preview-value" id="previewPriority">No asignada</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-map-marker-alt"></i> Ubicación:</span>
              <span class="preview-value" id="previewLocation">No seleccionada</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-building"></i> Área:</span>
              <span class="preview-value" id="previewArea">No seleccionada</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-ticket-alt"></i> Tipo:</span>
              <span class="preview-value" id="previewTipoTicket">No seleccionado</span>
            </div>
            <div class="preview-item">
              <span class="preview-label"><i class="fas fa-exclamation-triangle"></i> Falla:</span>
              <span class="preview-value" id="previewFalla">-</span>
            </div>
          </div>
        </div>

        <!-- Botón Submit -->
        <button type="submit" class="btn-submit" id="submitBtn">
          <i class="fas fa-paper-plane"></i>
          GENERAR TICKET
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    // Variables para el modal de carga
    let loadingInterval;
    let currentStep = 1;
    let progressValue = 0;

    // Funciones para el modal de carga
    function showLoadingModal() {
      const overlay = document.getElementById('loadingOverlay');
      overlay.classList.add('active');
      
      // Resetear pasos
      currentStep = 1;
      progressValue = 0;
      document.getElementById('progressBar').style.width = '0%';
      
      // Resetear iconos
      document.getElementById('step1Icon').innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #0066cc;"></i>';
      document.getElementById('step2Icon').innerHTML = '<i class="fas fa-circle-notch" style="color: #99b8d9;"></i>';
      document.getElementById('step3Icon').innerHTML = '<i class="fas fa-circle-notch" style="color: #99b8d9;"></i>';
      document.getElementById('step4Icon').innerHTML = '<i class="fas fa-circle-notch" style="color: #99b8d9;"></i>';
      
      document.getElementById('step1').classList.remove('step-pending');
      document.getElementById('step2').classList.add('step-pending');
      document.getElementById('step3').classList.add('step-pending');
      document.getElementById('step4').classList.add('step-pending');
      
      // Iniciar animación
      loadingInterval = setInterval(updateLoadingProgress, 600);
    }

    function updateLoadingProgress() {
      if (currentStep === 1) {
        progressValue = Math.min(25, progressValue + 5);
        if (progressValue >= 25) {
          currentStep = 2;
          document.getElementById('step1Icon').innerHTML = '<i class="fas fa-check-circle" style="color: #00cc66;"></i>';
          document.getElementById('step2Icon').innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #0066cc;"></i>';
          document.getElementById('step2').classList.remove('step-pending');
        }
      } else if (currentStep === 2) {
        progressValue = Math.min(50, progressValue + 5);
        if (progressValue >= 50) {
          currentStep = 3;
          document.getElementById('step2Icon').innerHTML = '<i class="fas fa-check-circle" style="color: #00cc66;"></i>';
          document.getElementById('step3Icon').innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #0066cc;"></i>';
          document.getElementById('step3').classList.remove('step-pending');
        }
      } else if (currentStep === 3) {
        progressValue = Math.min(75, progressValue + 5);
        if (progressValue >= 75) {
          currentStep = 4;
          document.getElementById('step3Icon').innerHTML = '<i class="fas fa-check-circle" style="color: #00cc66;"></i>';
          document.getElementById('step4Icon').innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #0066cc;"></i>';
          document.getElementById('step4').classList.remove('step-pending');
        }
      } else if (currentStep === 4) {
        progressValue = Math.min(100, progressValue + 5);
        if (progressValue >= 100) {
          clearInterval(loadingInterval);
          document.getElementById('step4Icon').innerHTML = '<i class="fas fa-check-circle" style="color: #00cc66;"></i>';
        }
      }
      
      document.getElementById('progressBar').style.width = progressValue + '%';
    }

    function hideLoadingModal() {
      const overlay = document.getElementById('loadingOverlay');
      overlay.classList.remove('active');
      clearInterval(loadingInterval);
    }

    // Fecha y hora
    function pad(n) { return n < 10 ? '0' + n : n; }
    
    function updateDateTime() {
      const now = new Date();
      const year = now.getFullYear();
      const month = pad(now.getMonth() + 1);
      const day = pad(now.getDate());
      const dateStr = `${year}-${month}-${day}`;
      const timeStr = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}.0000000`;
      
      document.getElementById('fecha').value = dateStr;
      document.getElementById('Hora').value = timeStr;
      document.getElementById('previewDate').textContent = dateStr;
      document.getElementById('previewTime').textContent = timeStr.split('.')[0];
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ID Ticket
    function generateTicketID() {
      const ticket = document.getElementById('tik');
      if (!ticket.value) {
        const id = 'TI-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
        ticket.value = id;
        document.getElementById('previewId').textContent = id;
      }
    }
    generateTicketID();

    // Actualizar preview
    document.getElementById('prio').addEventListener('change', function() {
      document.getElementById('previewPriority').textContent = this.value;
    });
    
    document.getElementById('empre').addEventListener('change', function() {
      const text = this.options[this.selectedIndex].text;
      document.getElementById('previewLocation').textContent = text.replace(/[🏢🏠🌳🅿️🔧📋]\s/g, '');
    });

    document.getElementById('area').addEventListener('change', function() {
      const text = this.options[this.selectedIndex].text;
      document.getElementById('previewArea').textContent = text.replace(/[🔧🧹🚔📌]\s/g, '');
    });

    document.getElementById('tipo_ticket').addEventListener('change', function() {
      const text = this.options[this.selectedIndex].text;
      document.getElementById('previewTipoTicket').textContent = text.replace(/[🛡️🔧📊⚙️]\s/g, '');
    });

    document.getElementById('adj').addEventListener('input', function() {
      document.getElementById('previewFalla').textContent = this.value || '-';
    });

    // Validar formulario antes de enviar
    document.getElementById('ticketForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validar campos requeridos
      if (!document.getElementById('Nombre').value) {
        Swal.fire({
          title: 'Campo requerido',
          text: 'Debe seleccionar un nombre',
          icon: 'warning',
          confirmButtonColor: '#003366'
        });
        return false;
      }

      // Mostrar modal de carga
      showLoadingModal();
      
      // Deshabilitar botón
      document.getElementById('submitBtn').disabled = true;
      
      // Enviar formulario después de 2 segundos
      setTimeout(() => {
        this.submit();
      }, 2000);
    });
  </script>

  <?php
  // PHP - Backend con INSERT actualizado
  session_start();
  date_default_timezone_set('America/Mexico_City');

  // Conexión a base de datos para nombres
  $serverName1 = "WIN-44O80L37Q7M\COMERCIAL";
  $connectionInfo1 = array( 
    "Database"=>"BASENUEVA", 
    "UID"=>"SA", 
    "PWD"=>"Administrador1*",
    "CharacterSet" => "UTF-8"
  );
  $conn1 = sqlsrv_connect($serverName1, $connectionInfo1);

  $array_tot1 = [];
  if ($conn1) {
    $sql = "SELECT ContactName FROM [dbo].[vwLBSContactList]";
    $stmt = sqlsrv_query($conn1, $sql);
    if ($stmt) {
      while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        array_push($array_tot1, $row['ContactName']);
      }
      sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn1);
  }

  if (empty($array_tot1)) {
    $array_tot1 = [
      "LUIS ANTONIO ROMERO LOPEZ", 
      "MARIA FERNANDA LOPEZ", 
      "JUAN CARLOS RODRIGUEZ", 
      "ANA SOFIA MARTINEZ", 
      "CARLOS ALBERTO PEREZ"
    ];
  }

  // Función para limpiar datos
  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

  // Procesar formulario
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtener todos los datos del formulario
    $nombre = test_input($_POST["Nombre"] ?? '');
    $prioridad = test_input($_POST["prio"] ?? '');
    $ubicacion = test_input($_POST["empre"] ?? ''); // Se guarda en Empresa
    $area_piso = test_input($_POST["area"] ?? ''); // Se guarda en Area_Piso
    $tipo_ticket = test_input($_POST["tipo_ticket"] ?? ''); // Se guarda en Asunto
    $falla = test_input($_POST["adj"] ?? ''); // Se guarda en Falla
    $mensaje = test_input($_POST["men"] ?? ''); // Se guarda en Mensaje
    $fecha = test_input($_POST["fecha"] ?? '');
    $hora = test_input($_POST["Hora"] ?? '');
    $id_ticket = test_input($_POST["tik"] ?? '');
    
    // Correo (puedes obtenerlo de sesión o dejarlo NULL)
    $correo = $_SESSION['user_email'] ?? null;
    
    // Fecha y hora actual para proceso
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s') . '.0000000';
    
    // Estatus inicial
    $estatus = 'En proceso';
    $pa = ''; // Vacío inicialmente
    $adjuntos = ''; // Vacío por ahora
    $enlace = ''; // Vacío por ahora
    
    $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
    $connectionInfo = array(
      "Database"=>"Ticket", 
      "UID"=>"Larome03", 
      "PWD"=>"Larome03",
      "CharacterSet" => "UTF-8"
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    if ($conn) {
      
      // INSERT actualizado con todos los campos
      $sql = "INSERT INTO TicketsSG (
          [Nombre], [Correo], [Prioridad], [Empresa], [Asunto], [Mensaje], [Adjuntos], 
          [Fecha], [Hora], [Id_Ticket], [Estatus], [PA], [Area_Piso], [Falla], 
          [Fecha_Termino], [Hora_Termino], [ENLACE], [fecha_proceso], [hora_proceso]
      ) VALUES (
          ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?
      )";
      
      $params = array(
          $nombre,        // Nombre
          $correo,        // Correo (puede ser NULL)
          $prioridad,     // Prioridad
          $ubicacion,     // Empresa (UBICACIÓN)
          $tipo_ticket,   // Asunto (TIPO DE TICKET)
          $mensaje,       // Mensaje
          $adjuntos,      // Adjuntos
          $fecha,         // Fecha
          $hora,          // Hora
          $id_ticket,     // Id_Ticket
          $estatus,       // Estatus
          $pa,            // PA
          $area_piso,     // Area_Piso (ÁREA)
          $falla,         // Falla
          $enlace,        // ENLACE
          $fecha_actual,  // fecha_proceso
          $hora_actual    // hora_proceso
      );
      
      $stmt = sqlsrv_query($conn, $sql, $params);
      
      if ($stmt) {
        // Éxito - SweetAlert2 de éxito
        echo '<script>
          setTimeout(function() {
            hideLoadingModal();
            Swal.fire({
              title: "¡Ticket Generado!",
              html: `
                <div style="text-align: center;">
                  <i class="fas fa-check-circle" style="font-size: 5rem; color: #00cc66; margin: 20px 0;"></i>
                  <h3 style="color: #003366; margin: 15px 0;">Ticket #' . $id_ticket . '</h3>
                  <p style="color: #4a5b6e;">El ticket se ha guardado correctamente</p>
                  <div style="background: #f8fcff; border-radius: 15px; padding: 15px; margin: 20px 0; text-align: left;">
                    <p><strong>👤 Nombre:</strong> ' . $nombre . '</p>
                    <p><strong>📍 Ubicación:</strong> ' . $ubicacion . '</p>
                    <p><strong>🏢 Área:</strong> ' . $area_piso . '</p>
                    <p><strong>🎫 Tipo:</strong> ' . $tipo_ticket . '</p>
                    <p><strong>⚡ Prioridad:</strong> ' . $prioridad . '</p>
                  </div>
                </div>
              `,
              icon: "success",
              confirmButtonColor: "#003366",
              confirmButtonText: "Aceptar",
              background: "white",
              showCloseButton: true
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = window.location.href;
              }
            });
          }, 500);
        </script>';
        sqlsrv_free_stmt($stmt);
      } else {
        // Error
        $errors = sqlsrv_errors();
        $error_msg = "Error al guardar el ticket";
        if (!empty($errors)) {
          $error_msg .= ": " . $errors[0]['message'];
        }
        echo '<script>
          setTimeout(function() {
            hideLoadingModal();
            Swal.fire({
              title: "Error",
              text: "' . $error_msg . '",
              icon: "error",
              confirmButtonColor: "#003366"
            });
          }, 500);
        </script>';
      }
      
      sqlsrv_close($conn);
    } else {
      echo '<script>
        setTimeout(function() {
          hideLoadingModal();
          Swal.fire({
            title: "Error",
            text: "Error de conexión a la base de datos",
            icon: "error",
            confirmButtonColor: "#003366"
          });
        }, 500);
      </script>';
    }
  }
  ?>

  <script>
    // Llenar select de nombres con Select2
    const nombres = <?php echo json_encode($array_tot1, JSON_UNESCAPED_UNICODE); ?>;
    const select = document.getElementById('Nombre');
    
    select.innerHTML = '<option value="" disabled selected>👤 Seleccione un nombre...</option>';
    
    const emojis = ['👤', '👨‍💼', '👩‍💼', '👨‍🔧', '👩‍🔧', '👨‍🏫', '👩‍🏫'];
    
    nombres.forEach((nombre, index) => {
      if (nombre && nombre.trim() !== '') {
        const option = document.createElement('option');
        const emoji = emojis[index % emojis.length];
        option.value = nombre;
        option.textContent = `${emoji} ${nombre}`;
        select.appendChild(option);
      }
    });

    // Inicializar Select2
    $(document).ready(function() {
      $('#Nombre').select2({
        theme: 'bootstrap-5',
        placeholder: '👤 Busque o seleccione un nombre...',
        allowClear: false,
        width: '100%',
        language: {
          noResults: function() {
            return "No se encontraron nombres";
          },
          searching: function() {
            return "Buscando...";
          }
        }
      });
    });
  </script>
</body>
</html>