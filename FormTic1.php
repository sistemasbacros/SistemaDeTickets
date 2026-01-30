<?php
// INICIO DE SESIÓN Y VERIFICACIÓN
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirigir al login si no está autenticado
    header("Location: Loginti.php");
    exit();
}

// Configuración para subida de archivos
$max_file_size = 5 * 1024 * 1024; // 5MB máximo
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$upload_path = '../uploads/'; // Ruta relativa a tu carpeta de uploads

// Verificar y crear carpeta de uploads si no existe
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Obtener datos del usuario desde la sesión
$nombre = $_SESSION['user_name'] ?? 'Usuario';
$id_empleado = $_SESSION['user_id'] ?? 'N/A';
$area = $_SESSION['user_area'] ?? 'N/A';
$usuario = $_SESSION['user_username'] ?? 'N/A';

// Crear iniciales para el avatar
$iniciales = substr($nombre, 0, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Ticket Soporte TI - BacroCorp</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
  :root {
    --navy-primary: #0a192f;
    --navy-secondary: #112240;
    --navy-dark: #020c1b;
    --accent-blue: #64a8ff;
    --accent-teal: #64ffda;
    --accent-purple: #7c3aed;
    --snow-white: #f8fafc;
    --pearl-white: #f1f5f9;
    --glass-bg: rgba(255, 255, 255, 0.08);
    --glass-border: rgba(255, 255, 255, 0.15);
    --glass-shadow: 0 8px 32px rgba(2, 12, 27, 0.4);
    --gradient-primary: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    --gradient-accent: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
    --gradient-blue: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    --font-primary: 'Outfit', sans-serif;
    --font-heading: 'Manrope', sans-serif;
  }
  
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  
  body {
    background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-primary) 50%, var(--navy-secondary) 100%);
    min-height: 100vh;
    font-family: var(--font-primary);
    overflow-x: hidden;
    color: white;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
  }
  
  /* Efectos de partículas en el fondo */
  .particles-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2;
    overflow: hidden;
  }
  
  .particle {
    position: absolute;
    background: rgba(100, 168, 255, 0.1);
    border-radius: 50%;
    animation: floatParticle 20s infinite linear;
  }
  
  @keyframes floatParticle {
    0% {
      transform: translateY(100vh) rotate(0deg);
      opacity: 0;
    }
    10% {
      opacity: 0.3;
    }
    90% {
      opacity: 0.3;
    }
    100% {
      transform: translateY(-100px) rotate(360deg);
      opacity: 0;
    }
  }
  
  .glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    box-shadow: var(--glass-shadow);
    border-radius: 24px;
    position: relative;
    overflow: hidden;
  }
  
  .glass-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent-blue), transparent);
  }
  
  .container {
    width: 100%;
    max-width: 900px;
    margin-top: 10px;
  }
  
  .header-section {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
  }
  
  .logo-container {
    position: relative;
    display: inline-block;
    margin-bottom: 15px;
  }
  
  .logo-glow {
    width: 100px;
    height: 100px;
    margin: 0 auto;
    border-radius: 50%;
    background: var(--gradient-blue);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 36px;
    position: relative;
    animation: logoPulse 3s ease-in-out infinite;
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
  }
  
  .logo-glow::after {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    background: var(--gradient-blue);
    border-radius: 50%;
    z-index: -1;
    filter: blur(12px);
    opacity: 0.6;
    animation: logoPulse 3s ease-in-out infinite 0.5s;
  }
  
  @keyframes logoPulse {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
    }
    50% {
      transform: scale(1.03);
      box-shadow: 0 0 40px rgba(59, 130, 246, 0.6);
    }
  }
  
  .system-title {
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: 2.2rem;
    margin-bottom: 8px;
    background: var(--gradient-blue);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.02em;
    text-align: center;
    position: relative;
  }
  
  .system-title::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 2px;
    background: var(--gradient-blue);
    border-radius: 2px;
  }
  
  .system-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 5px;
    font-weight: 400;
    text-align: center;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
  }
  
  .security-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #dbeafe;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 10px;
    backdrop-filter: blur(10px);
  }
  
  form {
    display: grid;
    gap: 25px;
  }
  
  .form-section {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 18px;
    padding: 25px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.3s ease;
  }
  
  .form-section:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
  }
  
  .section-title {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: var(--accent-blue);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .section-title i {
    color: var(--accent-blue);
    font-size: 1.3rem;
  }
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    position: relative;
  }
  
  .form-label {
    font-weight: 600;
    font-family: var(--font-heading);
    margin-bottom: 10px;
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .form-label i {
    color: var(--accent-blue);
    font-size: 0.85rem;
  }
  
  .input-container {
    position: relative;
  }
  
  .form-control {
    background: rgba(255, 255, 255, 0.07);
    border: 2px solid rgba(255, 255, 255, 0.12);
    color: white;
    border-radius: 14px;
    padding: 14px 18px;
    font-family: var(--font-primary);
    font-size: 0.95rem;
    font-weight: 400;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
    position: relative;
    z-index: 1;
  }
  
  .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
  }
  
  .form-control:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--accent-blue);
    color: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    transform: translateY(-2px);
    outline: none;
  }
  
  .form-control:hover {
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
  }
  
  .input-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.5);
    z-index: 2;
    transition: all 0.3s ease;
  }
  
  .form-control:focus + .input-icon {
    color: var(--accent-blue);
  }
  
  textarea.form-control {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
  }
  
  select.form-control {
    cursor: pointer;
    appearance: none;
  }
  
  /* SOLUCIÓN: Estilo para las opciones del select */
  select.form-control option {
    background-color: var(--navy-secondary);
    color: white;
  }
  
  .select-arrow {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.5);
    pointer-events: none;
    z-index: 2;
  }
  
  /* Estilos para subida de archivos */
  .file-upload-container {
    background: rgba(255, 255, 255, 0.05);
    border: 2px dashed rgba(100, 168, 255, 0.3);
    border-radius: 14px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    margin-top: 10px;
  }
  
  .file-upload-container:hover {
    background: rgba(100, 168, 255, 0.08);
    border-color: var(--accent-blue);
    transform: translateY(-2px);
  }
  
  .file-upload-container.dragover {
    background: rgba(100, 168, 255, 0.12);
    border-color: var(--accent-blue);
    border-style: solid;
  }
  
  .file-upload-icon {
    font-size: 2.5rem;
    color: var(--accent-blue);
    margin-bottom: 10px;
  }
  
  .file-upload-text {
    font-size: 1rem;
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.9);
  }
  
  .file-upload-subtext {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 15px;
  }
  
  .file-input-hidden {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
  }
  
  .file-preview {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.15);
  }
  
  .file-preview.active {
    display: block;
    animation: slideUp 0.3s ease-out;
  }
  
  .preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }
  
  .preview-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--accent-blue);
  }
  
  .remove-file {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fecaca;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  
  .remove-file:hover {
    background: rgba(239, 68, 68, 0.3);
    color: white;
  }
  
  .file-info {
    display: flex;
    align-items: center;
    gap: 15px;
  }
  
  .file-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid rgba(255, 255, 255, 0.1);
  }
  
  .file-details {
    flex: 1;
  }
  
  .file-name {
    font-weight: 600;
    margin-bottom: 5px;
    word-break: break-all;
  }
  
  .file-size {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
  }
  
  .file-type {
    display: inline-block;
    background: rgba(100, 168, 255, 0.2);
    color: var(--accent-blue);
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 5px;
  }
  
  .image-preview-container {
    margin-top: 15px;
  }
  
  .image-preview {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    display: none;
  }
  
  .image-preview.active {
    display: block;
    animation: fadeIn 0.3s ease-out;
  }
  
  .btn-submit {
    background: var(--gradient-blue);
    border: none;
    color: white;
    padding: 16px 28px;
    border-radius: 14px;
    width: 100%;
    font-weight: 700;
    font-family: var(--font-heading);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-size: 1.1rem;
    letter-spacing: 0.02em;
    position: relative;
    overflow: hidden;
    margin-top: 15px;
    box-shadow: 0 6px 25px rgba(59, 130, 246, 0.3);
  }
  
  .btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
    letter-spacing: 0.03em;
  }
  
  .btn-submit:active {
    transform: translateY(-1px);
  }
  
  .btn-submit::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
  }
  
  .btn-submit:hover::before {
    left: 100%;
  }
  
  .btn-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    position: relative;
    z-index: 1;
  }
  
  #homeButton {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 55px;
    height: 55px;
    background: var(--gradient-blue);
    color: #ffffff;
    border: none;
    border-radius: 16px;
    font-size: 22px;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  #homeButton:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
  }
  
  .security-notice {
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #dbeafe;
    padding: 16px;
    border-radius: 14px;
    margin-bottom: 25px;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-left: 4px solid var(--accent-blue);
  }
  
  .ticket-preview {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 14px;
    padding: 20px;
    margin-top: 15px;
    border: 1px dashed rgba(255, 255, 255, 0.2);
  }
  
  .preview-title {
    font-family: var(--font-heading);
    font-weight: 600;
    color: var(--accent-blue);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
  }
  
  .preview-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    font-size: 0.9rem;
  }
  
  .preview-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .preview-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
  }
  
  .preview-value {
    color: white;
    font-weight: 600;
  }
  
  @media (max-width: 768px) {
    .container {
      padding: 0 15px;
    }
    
    .system-title {
      font-size: 1.8rem;
    }
    
    .system-subtitle {
      font-size: 0.9rem;
    }
    
    .logo-glow {
      width: 80px;
      height: 80px;
      font-size: 28px;
    }
    
    .form-grid {
      grid-template-columns: 1fr;
    }
    
    .form-section {
      padding: 20px 18px;
    }
    
    #homeButton {
      top: 15px;
      left: 15px;
      width: 48px;
      height: 48px;
      font-size: 18px;
      border-radius: 14px;
    }
    
    .file-info {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .file-thumbnail {
      width: 100%;
      height: auto;
      max-height: 150px;
    }
  }
  
  /* Animaciones */
  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }
  
  .animate-in {
    animation: slideUp 0.5s ease-out forwards;
  }
  
  .delay-1 { animation-delay: 0.1s; }
  .delay-2 { animation-delay: 0.2s; }
  .delay-3 { animation-delay: 0.3s; }
  .delay-4 { animation-delay: 0.4s; }
</style>
</head>

<body>
  <!-- Partículas de fondo -->
  <div class="particles-container" id="particlesContainer"></div>
  
  
  <!-- Header Section -->
  <div class="header-section animate-in">
    <div class="logo-container">
      <div class="logo-glow">
        <i class="fas fa-shield-alt"></i>
      </div>
    </div>
    
    <h1 class="system-title">BACROCORP</h1>
    <p class="system-subtitle">Sistema Premium de Gestión de Tickets de Soporte Técnico</p>
    
    <div class="security-badge">
      <i class="fas fa-badge-check"></i>
      Sistema Certificado - Comunicación Segura
    </div>
  </div>
  
  <!-- Contenedor principal -->
  <div class="container">
    <div class="glass-card animate-in delay-1" style="padding: 35px 30px;">
      <!-- Información del Usuario Autenticado -->
      <div class="security-notice">
        <i class="fas fa-user-check"></i>
        <div>
          <strong>Usuario Autenticado:</strong> <?php echo htmlspecialchars($nombre); ?> | 
          <strong>Área:</strong> <?php echo htmlspecialchars($area); ?> | 
          <strong>ID:</strong> <?php echo htmlspecialchars($id_empleado); ?>
        </div>
      </div>
      
      <!-- MODIFICACIÓN: Agregado enctype para subida de archivos -->
      <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="ticketForm" enctype="multipart/form-data">
        <!-- Sección Información Personal (PRE-LLENADA) -->
        <div class="form-section animate-in delay-1">
          <h3 class="section-title">
            <i class="fas fa-user-circle"></i>
            Información Personal
          </h3>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="Nombre" class="form-label">
                <i class="fas fa-signature"></i>
                Nombre Completo
              </label>
              <div class="input-container">
                <input type="text" class="form-control" id="Nombre" name="Nombre" value="<?php echo htmlspecialchars($nombre); ?>" readonly />
                <i class="fas fa-user-check input-icon" style="color: #10b981;"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="elect" class="form-label">
                <i class="fas fa-envelope"></i>
                Correo Electrónico
              </label>
              <div class="input-container">
                <input type="email" class="form-control" id="elect" name="elect" placeholder="correo@empresa.com" required />
                <i class="fas fa-at input-icon"></i>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Sección Detalles del Ticket -->
        <div class="form-section animate-in delay-2">
          <h3 class="section-title">
            <i class="fas fa-ticket-alt"></i>
            Detalles del Ticket
          </h3>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="prio" class="form-label">
                <i class="fas fa-flag"></i>
                Nivel de Prioridad
              </label>
              <div class="input-container">
                <select class="form-control" id="prio" name="prio" required>
                  <option value="Bajo">🟢 Bajo - Sin impacto crítico</option>
                  <option value="Medio">🟡 Medio - Impacto moderado</option>
                  <option value="Alto">🔴 Alto - Impacto crítico</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="empre" class="form-label">
                <i class="fas fa-building"></i>
                Departamento
              </label>
              <div class="input-container">
                <input type="text" class="form-control" id="empre" name="empre" value="<?php echo htmlspecialchars($area); ?>" required />
                <i class="fas fa-sitemap input-icon"></i>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label for="Asunto" class="form-label">
              <i class="fas fa-tags"></i>
              Tipo de Solicitud
            </label>
            <div class="input-container">
              <select class="form-control" id="Asunto" name="Asunto" required>
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
              <i class="fas fa-chevron-down select-arrow"></i>
            </div>
          </div>
        </div>
        
        <!-- Sección Descripción del Problema -->
        <div class="form-section animate-in delay-3">
          <h3 class="section-title">
            <i class="fas fa-clipboard-list"></i>
            Descripción del Problema
          </h3>
          
          <div class="form-group">
            <label for="adj" class="form-label">
              <i class="fas fa-heading"></i>
              Resumen Ejecutivo
            </label>
            <div class="input-container">
              <input type="text" class="form-control" id="adj" name="adj" placeholder="Describa brevemente el problema..." required />
              <i class="fas fa-pen input-icon"></i>
            </div>
          </div>
          
          <div class="form-group">
            <label for="men" class="form-label">
              <i class="fas fa-align-left"></i>
              Detalles Completos
            </label>
            <div class="input-container">
              <textarea class="form-control" id="men" name="men" placeholder="Proporcione todos los detalles relevantes del problema..."></textarea>
              <i class="fas fa-file-alt input-icon" style="top: 18px; transform: none;"></i>
            </div>
          </div>
          
          <!-- MODIFICACIÓN: Sección para adjuntar imágenes -->
          <div class="form-group">
            <label class="form-label">
              <i class="fas fa-image"></i>
              Adjuntar Imagen (Opcional)
            </label>
            <div class="file-upload-container" id="fileUploadContainer">
              <div class="file-upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
              </div>
              <div class="file-upload-text">
                Haga clic para seleccionar una imagen
              </div>
              <div class="file-upload-subtext">
                PNG, JPG, GIF, WebP - Máximo 5MB
              </div>
              <input type="file" class="file-input-hidden" id="imagen" name="imagen" accept="image/*">
            </div>
            
            <!-- Preview del archivo -->
            <div class="file-preview" id="filePreview">
              <div class="preview-header">
                <div class="preview-title">
                  <i class="fas fa-file-image"></i>
                  <span>Imagen seleccionada</span>
                </div>
                <button type="button" class="remove-file" id="removeFile">
                  <i class="fas fa-times"></i>
                  Quitar
                </button>
              </div>
              <div class="file-info">
                <img class="file-thumbnail" id="fileThumbnail" src="" alt="Preview">
                <div class="file-details">
                  <div class="file-name" id="fileName"></div>
                  <div class="file-size" id="fileSize"></div>
                  <div class="file-type" id="fileType"></div>
                </div>
              </div>
              <div class="image-preview-container">
                <img class="image-preview" id="imagePreview" src="" alt="Vista previa">
              </div>
            </div>
          </div>
        </div>
        
        <!-- Sección Información del Sistema -->
        <div class="form-section animate-in delay-4">
          <h3 class="section-title">
            <i class="fas fa-cogs"></i>
            Información del Sistema
          </h3>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="fecha" class="form-label">
                <i class="fas fa-calendar"></i>
                Fecha de Creación
              </label>
              <div class="input-container">
                <input class="form-control" id="fecha" name="fecha" type="text" required readonly />
                <i class="fas fa-calendar-day input-icon"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="Hora" class="form-label">
                <i class="fas fa-clock"></i>
                Hora Exacta
              </label>
              <div class="input-container">
                <input type="text" class="form-control" id="Hora" name="Hora" required readonly />
                <i class="fas fa-clock input-icon"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="tik" class="form-label">
                <i class="fas fa-fingerprint"></i>
                ID del Ticket
              </label>
              <div class="input-container">
                <input type="text" class="form-control" id="tik" name="tik" required readonly />
                <i class="fas fa-hashtag input-icon"></i>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Vista Previa -->
        <div class="ticket-preview animate-in delay-4">
          <h4 class="preview-title">
            <i class="fas fa-eye"></i>
            Vista Previa del Ticket
          </h4>
          <div class="preview-info" id="ticketPreview">
            <div class="preview-item">
              <span class="preview-label">ID Ticket:</span>
              <span class="preview-value" id="previewId">TI-000000</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Fecha/Hora:</span>
              <span class="preview-value" id="previewDateTime">Cargando...</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Prioridad:</span>
              <span class="preview-value" id="previewPriority">No seleccionada</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Solicitante:</span>
              <span class="preview-value"><?php echo htmlspecialchars($nombre); ?></span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Imagen Adjunta:</span>
              <span class="preview-value" id="previewImage">No</span>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn-submit">
          <div class="btn-content">
            <i class="fas fa-paper-plane"></i>
            <!-- MODIFICACIÓN: Cambiado el texto del botón -->
            <span>Generar Ticket con Imagen</span>
          </div>
        </button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Crear partículas de fondo
    function createParticles() {
      const container = document.getElementById('particlesContainer');
      const particleCount = 12;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        const size = Math.random() * 5 + 2;
        const left = Math.random() * 100;
        const delay = Math.random() * 20;
        const duration = Math.random() * 10 + 20;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${left}%`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;
        
        container.appendChild(particle);
      }
    }
    
    function pad(n) { return n < 10 ? '0' + n : n; }
    
    function updateDateTime() {
      const now = new Date();
      const date = now.toLocaleDateString('es-ES');
      const time = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
      
      document.getElementById('fecha').value = date;
      document.getElementById('Hora').value = time;
      
      // Actualizar vista previa
      document.getElementById('previewDateTime').textContent = `${date} ${time}`;
    }
    
    function generateTicketID() {
      const ticket = document.getElementById('tik');
      if (!ticket.value) {
        const ticketId = 'TI-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
        ticket.value = ticketId;
        document.getElementById('previewId').textContent = ticketId;
      }
    }
    
    // Actualizar vista previa en tiempo real
    function updatePreview() {
      const priority = document.getElementById('prio').value;
      document.getElementById('previewPriority').textContent = priority;
      
      // Actualizar color según prioridad
      const priorityElement = document.getElementById('previewPriority');
      priorityElement.className = 'preview-value';
      if (priority === 'Alto') priorityElement.style.color = '#ef4444';
      else if (priority === 'Medio') priorityElement.style.color = '#f59e0b';
      else if (priority === 'Bajo') priorityElement.style.color = '#10b981';
    }
    
    // MODIFICACIÓN: Manejo de archivos subidos
    function setupFileUpload() {
      const fileInput = document.getElementById('imagen');
      const filePreview = document.getElementById('filePreview');
      const fileThumbnail = document.getElementById('fileThumbnail');
      const imagePreview = document.getElementById('imagePreview');
      const fileName = document.getElementById('fileName');
      const fileSize = document.getElementById('fileSize');
      const fileType = document.getElementById('fileType');
      const removeFileBtn = document.getElementById('removeFile');
      const uploadContainer = document.getElementById('fileUploadContainer');
      const previewImage = document.getElementById('previewImage');
      
      // Evento para selección de archivo
      fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          // Validar tipo de archivo
          if (!file.type.startsWith('image/')) {
            Swal.fire({
              title: 'Tipo de archivo no válido',
              text: 'Por favor, selecciona solo archivos de imagen (JPG, PNG, GIF, WebP).',
              icon: 'error',
              confirmButtonColor: '#003366'
            });
            fileInput.value = '';
            return;
          }
          
          // Validar tamaño (5MB máximo)
          if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
              title: 'Archivo demasiado grande',
              text: 'El archivo no debe exceder los 5MB.',
              icon: 'error',
              confirmButtonColor: '#003366'
            });
            fileInput.value = '';
            return;
          }
          
          // Mostrar información del archivo
          fileName.textContent = file.name;
          fileSize.textContent = formatFileSize(file.size);
          fileType.textContent = file.type.split('/')[1].toUpperCase();
          
          // Crear preview de miniatura
          const reader = new FileReader();
          reader.onload = function(e) {
            fileThumbnail.src = e.target.result;
            imagePreview.src = e.target.result;
            filePreview.classList.add('active');
            imagePreview.classList.add('active');
            previewImage.textContent = 'Sí - ' + file.name;
          };
          reader.readAsDataURL(file);
        }
      });
      
      // Evento para quitar archivo
      removeFileBtn.addEventListener('click', function() {
        fileInput.value = '';
        filePreview.classList.remove('active');
        imagePreview.classList.remove('active');
        previewImage.textContent = 'No';
      });
      
      // Drag & drop
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, preventDefaults, false);
      });
      
      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      ['dragenter', 'dragover'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, highlight, false);
      });
      
      ['dragleave', 'drop'].forEach(eventName => {
        uploadContainer.addEventListener(eventName, unhighlight, false);
      });
      
      function highlight() {
        uploadContainer.classList.add('dragover');
      }
      
      function unhighlight() {
        uploadContainer.classList.remove('dragover');
      }
      
      // Handle drop
      uploadContainer.addEventListener('drop', handleDrop, false);
      
      function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
          fileInput.files = files;
          fileInput.dispatchEvent(new Event('change'));
        }
      }
    }
    
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // MODIFICACIÓN: Validación de formulario
    function validateForm() {
      const email = document.getElementById('elect').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      
      if (!emailRegex.test(email)) {
        Swal.fire({
          title: 'Correo electrónico inválido',
          text: 'Por favor, ingresa un correo electrónico válido.',
          icon: 'error',
          confirmButtonColor: '#003366'
        });
        return false;
      }
      
      const fileInput = document.getElementById('imagen');
      if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
          Swal.fire({
            title: 'Archivo demasiado grande',
            text: 'La imagen no debe exceder los 5MB.',
            icon: 'error',
            confirmButtonColor: '#003366'
          });
          return false;
        }
      }
      
      return true;
    }
    
    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
      createParticles();
      updateDateTime();
      generateTicketID();
      setupFileUpload(); // MODIFICACIÓN: Inicializar subida de archivos
      
      setInterval(updateDateTime, 1000);
      
      // Event listeners para actualizar vista previa
      document.getElementById('prio').addEventListener('change', updatePreview);
      
      // Efectos de entrada escalonados
      const elements = document.querySelectorAll('.animate-in');
      elements.forEach((el, index) => {
        el.style.animationDelay = `${(index + 1) * 0.1}s`;
      });
      
      // MODIFICACIÓN: Validación de formulario mejorada
      document.getElementById('ticketForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
          return false;
        }
        
        const submitBtn = this.querySelector('.btn-submit');
        const btnContent = submitBtn.querySelector('.btn-content');
        
        btnContent.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Procesando ticket con imagen...</span>';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.8';
        
        // Simular proceso de subida
        setTimeout(() => {
          btnContent.innerHTML = '<i class="fas fa-check"></i><span>Ticket Generado</span>';
        }, 2000);
      });
    });
  </script>
</body>
</html>

<?php
// Incluir los archivos necesarios de PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de correos
define('ADMIN_EMAIL', 'tickets@bacrocorp.com');
define('ADMIN_NAME', 'Administrador TI BacroCorp');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Variables para la imagen
  $imagen_url = '';
  $imagen_nombre = '';
  $imagen_tipo = '';
  $imagen_size = 0;
  
  // MODIFICACIÓN: Procesar archivo subido si existe
  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['imagen']['tmp_name'];
    $file_name = basename($_FILES['imagen']['name']);
    $file_type = $_FILES['imagen']['type'];
    $file_size = $_FILES['imagen']['size'];
    
    // Validar tamaño del archivo (5MB máximo)
    if ($file_size <= 5 * 1024 * 1024) {
      // Validar tipo de archivo
      $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (in_array($file_type, $allowed_types)) {
        // Validar si es realmente una imagen
        if (getimagesize($file_tmp)) {
          // Generar nombre único para el archivo
          $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
          $unique_name = 'ticket_' . time() . '_' . uniqid() . '.' . $file_ext;
          $destination = '../uploads/' . $unique_name;
          
          // Mover archivo al directorio de uploads
          if (move_uploaded_file($file_tmp, $destination)) {
            $imagen_url = $destination;
            $imagen_nombre = $file_name;
            $imagen_tipo = $file_type;
            $imagen_size = $file_size;
          }
        }
      }
    }
  }
  
  // Usar las variables de sesión para nombre y área
  $name1 = $nombre; // Ya viene de la sesión
  $name2 = test_input($_POST["elect"]);
  $name3 = test_input($_POST["prio"]);
  $name4 = $area; // Ya viene de la sesión
  $name5 = test_input($_POST["Asunto"]);
  $name6 = test_input($_POST["men"]);
  $name7 = test_input($_POST["adj"]);
  $name8 = test_input($_POST["fecha"]);
  $name9 = test_input($_POST["Hora"]);
  $name10 = test_input($_POST["tik"]);
  
  // MODIFICACIÓN: Insertar en la base de datos con información de imagen
  $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
  $connectionInfo = array( 
    "Database"=>"Ticket", 
    "UID"=>"Larome03", 
    "PWD"=>"Larome03",
    "CharacterSet" => "UTF-8"
  );
  $conn = sqlsrv_connect($serverName, $connectionInfo);

  if ($conn) {
    // MODIFICACIÓN: Consulta actualizada con campos de imagen
    $sql = "INSERT INTO T3 (
      [Nombre],[Correo],[Prioridad],[Empresa],[Asunto],[Mensaje],[Adjuntos],
      [Fecha],[Hora],[Id_Ticket],[Estatus],[PA],[imagen_url],[imagen_nombre],[imagen_tipo],[imagen_size]
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', '', ?, ?, ?, ?)";
    
    $params = array(
      $name1, $name2, $name3, $name4, $name5, $name6, $name7, 
      $name8, $name9, $name10, $imagen_url, $imagen_nombre, $imagen_tipo, $imagen_size
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
      error_log("Error en la base de datos: " . print_r(sqlsrv_errors(), true));
      showErrorAlert("Error al guardar el ticket en la base de datos.");
    } else {
      sqlsrv_free_stmt($stmt);
      
      // Enviar correos de confirmación
      // MODIFICACIÓN: Pasar información de imagen a la función
      $emailResults = sendConfirmationEmails(
        $name1, $name2, $name3, $name4, $name5, $name6, $name7, 
        $name8, $name9, $name10, $imagen_nombre
      );
      
      if ($emailResults['user'] && $emailResults['admin']) {
        showSuccessAlert($name1, $name2, $name10, true, true, $imagen_nombre);
      } elseif ($emailResults['user'] && !$emailResults['admin']) {
        showSuccessAlert($name1, $name2, $name10, true, false, $imagen_nombre);
      } elseif (!$emailResults['user'] && $emailResults['admin']) {
        showSuccessAlert($name1, $name2, $name10, false, true, $imagen_nombre);
      } else {
        showWarningAlert($name10, "No se pudieron enviar los correos de confirmación.");
      }
    }
    
    sqlsrv_close($conn);
  } else {
    error_log("Error de conexión a la base de datos: " . print_r(sqlsrv_errors(), true));
    showErrorAlert("Error de conexión a la base de datos.");
  }
}

// MODIFICACIÓN: Función actualizada para enviar correos con información de imagen
function sendConfirmationEmails($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre) {
  $results = [
    'user' => false,
    'admin' => false
  ];
  
  // Enviar correo al usuario
  $results['user'] = sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre);
  
  // Enviar correo al administrador
  $results['admin'] = sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre);
  
  return $results;
}

// MODIFICACIÓN: Función actualizada para enviar correo al usuario
function sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre) {
  try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tickets@bacrocorp.com';
    $mail->Password = 'XTqzA0GkA#';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Destinatarios
    $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
    $mail->addAddress($email, $name);
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = '✅ Confirmación de Ticket #' . $ticketId . ' - Departamento de TI BacroCorp';
    
    // MODIFICACIÓN: Cuerpo del correo con información de imagen
    $mail->Body = createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre);
    
    return $mail->send();
    
  } catch (Exception $e) {
    error_log("Error enviando correo al usuario: " . $e->getMessage());
    return false;
  }
}

// MODIFICACIÓN: Función actualizada para enviar correo al administrador
function sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre) {
  try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tickets@bacrocorp.com';
    $mail->Password = 'XTqzA0GkA#';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Destinatarios
    $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
    $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = '🚨 NUEVO TICKET #' . $ticketId . ' - ' . $priority . ' - ' . $subject;
    
    // MODIFICACIÓN: Cuerpo del correo para administrador con información de imagen
    $mail->Body = createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre);
    
    return $mail->send();
    
  } catch (Exception $e) {
    error_log("Error enviando correo al administrador: " . $e->getMessage());
    return false;
  }
}

// MODIFICACIÓN: Función actualizada para crear plantilla de correo al usuario
function createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre) {
  $priorityColor = getPriorityColor($priority);
  
  // MODIFICACIÓN: Agregar información de imagen si existe
  $imagen_info = '';
  if (!empty($imagen_nombre)) {
    $imagen_info = '
    <div class="ticket-detail">
        <span class="ticket-label">Imagen Adjunta:</span>
        <span>✓ ' . htmlspecialchars($imagen_nombre) . '</span>
    </div>';
  }
  
  return '
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="UTF-8">
      <style>
          body {
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
              color: #333;
              margin: 0;
              padding: 0;
              background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
          }
          .container {
              max-width: 600px;
              margin: 20px auto;
              background-color: #ffffff;
              border-radius: 12px;
              overflow: hidden;
              box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          }
          .header {
              background: linear-gradient(135deg, #003366 0%, #0066cc 100%);
              color: white;
              padding: 30px 20px;
              text-align: center;
          }
          .header h1 {
              margin: 0;
              font-size: 28px;
              font-weight: 600;
          }
          .content {
              padding: 30px;
          }
          .ticket-info {
              background-color: #f8f9fa;
              border-radius: 8px;
              padding: 20px;
              margin: 20px 0;
              border-left: 4px solid #0066cc;
          }
          .ticket-detail {
              margin-bottom: 10px;
              display: flex;
          }
          .ticket-label {
              font-weight: 600;
              min-width: 120px;
          }
          .priority-badge {
              padding: 4px 12px;
              border-radius: 15px;
              font-size: 12px;
              font-weight: bold;
              color: white;
          }
          .message {
              background-color: #e8f4fd;
              border-radius: 8px;
              padding: 15px;
              margin: 20px 0;
              border-left: 4px solid #3498db;
          }
          .footer {
              background-color: #f1f1f1;
              padding: 20px;
              text-align: center;
              font-size: 14px;
              color: #666;
          }
          .thank-you {
              font-size: 18px;
              color: #2c3e50;
              margin-bottom: 20px;
              text-align: center;
          }
          .ticket-id {
              background: #003366;
              color: white;
              padding: 10px;
              border-radius: 5px;
              text-align: center;
              font-size: 18px;
              font-weight: bold;
              margin: 15px 0;
          }
      </style>
  </head>
  <body>
      <div class="container">
          <div class="header">
              <h1>🎯 Departamento de TI - BacroCorp</h1>
              <p>Sistema de Gestión de Tickets</p>
          </div>
          <div class="content">
              <div class="thank-you">
                  <strong>Estimado/a ' . $name . ',</strong>
              </div>
              <p>Hemos recibido correctamente tu solicitud de soporte técnico y hemos creado un ticket con la siguiente información:</p>
              
              <div class="ticket-id">Ticket #: ' . $ticketId . '</div>
              
              <div class="ticket-info">
                  <div class="ticket-detail">
                      <span class="ticket-label">Fecha y Hora:</span>
                      <span>' . $date . ' a las ' . $time . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Departamento:</span>
                      <span>' . $department . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Tipo de Solicitud:</span>
                      <span>' . $subject . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Prioridad:</span>
                      <span class="priority-badge" style="background: ' . $priorityColor . ';">' . $priority . '</span>
                  </div>
                  ' . $imagen_info . '
              </div>
              
              <div class="message">
                  <p><strong>Descripción del problema:</strong></p>
                  <p>' . $description . '</p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
              </div>
              
              <p>Nuestro equipo de especialistas revisará tu solicitud a la mayor brevedad posible y te mantendremos informado sobre el progreso.</p>
              
              <p>Si necesitas agregar información adicional a tu ticket o tienes alguna pregunta, por favor responde a este correo haciendo referencia al número de ticket proporcionado.</p>
              
              <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #555;">
                  <p>Atentamente,<br>
                  <strong>Equipo de Soporte Técnico</strong><br>
                  Departamento de TI - BacroCorp<br>
                  <em>"Innovación y eficiencia a tu servicio"</em></p>
              </div>
          </div>
          <div class="footer">
              <p>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
              <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
          </div>
      </div>
  </body>
  </html>';
}

// MODIFICACIÓN: Función actualizada para crear plantilla de correo al administrador
function createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $imagen_nombre) {
  $priorityColor = getPriorityColor($priority);
  $urgencyIcon = getUrgencyIcon($priority);
  
  // MODIFICACIÓN: Agregar información de imagen si existe
  $imagen_info = '';
  if (!empty($imagen_nombre)) {
    $imagen_info = '
    <div class="ticket-detail">
        <span class="ticket-label">Imagen Adjunta:</span>
        <span><strong>✓ ' . htmlspecialchars($imagen_nombre) . '</strong></span>
    </div>';
  }
  
  return '
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset="UTF-8">
      <style>
          body {
              font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
              color: #333;
              margin: 0;
              padding: 0;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          }
          .container {
              max-width: 700px;
              margin: 20px auto;
              background-color: #ffffff;
              border-radius: 12px;
              overflow: hidden;
              box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
          }
          .header {
              background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
              color: white;
              padding: 25px 20px;
              text-align: center;
          }
          .header h1 {
              margin: 0;
              font-size: 24px;
              font-weight: 600;
          }
          .content {
              padding: 25px;
          }
          .alert-banner {
              background: #fff3cd;
              border: 1px solid #ffeaa7;
              border-radius: 8px;
              padding: 15px;
              margin: 15px 0;
              text-align: center;
              font-weight: bold;
              color: #856404;
          }
          .ticket-card {
              background: #f8f9fa;
              border-radius: 8px;
              padding: 20px;
              margin: 20px 0;
              border-left: 5px solid ' . $priorityColor . ';
          }
          .ticket-detail {
              margin-bottom: 8px;
              display: flex;
          }
          .ticket-label {
              font-weight: 600;
              min-width: 140px;
              color: #2c3e50;
          }
          .priority-badge {
              background: ' . $priorityColor . ';
              color: white;
              padding: 6px 15px;
              border-radius: 20px;
              font-size: 14px;
              font-weight: bold;
          }
          .user-info {
              background: #e8f4fd;
              border-radius: 8px;
              padding: 15px;
              margin: 15px 0;
          }
          .problem-description {
              background: #fff3cd;
              border-radius: 8px;
              padding: 15px;
              margin: 15px 0;
              border-left: 4px solid #ffc107;
          }
          .action-buttons {
              text-align: center;
              margin: 25px 0;
              padding: 20px;
              background: #f8f9fa;
              border-radius: 8px;
          }
          .btn {
              display: inline-block;
              padding: 10px 20px;
              margin: 0 10px;
              background: #007bff;
              color: white;
              text-decoration: none;
              border-radius: 5px;
              font-weight: bold;
          }
          .footer {
              background-color: #2c3e50;
              color: white;
              padding: 20px;
              text-align: center;
              font-size: 12px;
          }
      </style>
  </head>
  <body>
      <div class="container">
          <div class="header">
              <h1>' . $urgencyIcon . ' NUEVO TICKET DE SOPORTE - REQUIERE ATENCIÓN</h1>
          </div>
          <div class="content">
              <div class="alert-banner">
                  ⚠️ Se ha generado un nuevo ticket con prioridad ' . $priority . ' que requiere tu atención inmediata.
              </div>
              
              <div class="ticket-card">
                  <div style="text-align: center; margin-bottom: 15px;">
                      <span style="background: #003366; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
                  </div>
                  
                  <div class="ticket-detail">
                      <span class="ticket-label">Fecha y Hora:</span>
                      <span>' . $date . ' - ' . $time . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Prioridad:</span>
                      <span class="priority-badge">' . $priority . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Tipo de Solicitud:</span>
                      <span><strong>' . $subject . '</strong></span>
                  </div>
                  ' . $imagen_info . '
              </div>
              
              <div class="user-info">
                  <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Usuario</h3>
                  <div class="ticket-detail">
                      <span class="ticket-label">Nombre:</span>
                      <span>' . $name . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Correo:</span>
                      <span>' . $email . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Departamento:</span>
                      <span>' . $department . '</span>
                  </div>
              </div>
              
              <div class="problem-description">
                  <h3 style="margin-top: 0; color: #856404;">📋 Descripción del Problema</h3>
                  <p><strong>' . $description . '</strong></p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
              </div>
              
              <div class="action-buttons">
                  <p style="font-weight: bold; margin-bottom: 15px;">🚀 Acciones Recomendadas:</p>
                  <p>• Revisar el ticket en el sistema de gestión<br>
                  • Verificar imagen adjunta si existe<br>
                  • Asignar técnico según la prioridad<br>
                  • Contactar al usuario si se requiere información adicional</p>
              </div>
              
              <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                  <p>Este es un mensaje automático del Sistema de Tickets BacroCorp</p>
              </div>
          </div>
          <div class="footer">
              <p>Departamento de TI - BacroCorp | Sistema Automatizado de Notificaciones</p>
              <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
          </div>
      </div>
  </body>
  </html>';
}

function getPriorityColor($priority) {
  switch ($priority) {
    case 'Alto': return '#dc3545';
    case 'Medio': return '#ffc107';
    case 'Bajo': return '#28a745';
    default: return '#6c757d';
  }
}

function getUrgencyIcon($priority) {
  switch ($priority) {
    case 'Alto': return '🚨🔥';
    case 'Medio': return '⚠️📋';
    case 'Bajo': return 'ℹ️📥';
    default: return '📝';
  }
}

// MODIFICACIÓN: Función actualizada para mostrar alerta de éxito con información de imagen
function showSuccessAlert($name, $email, $ticketId, $userEmailSent, $adminEmailSent, $imagen_nombre = '') {
  $emailStatus = '';
  
  if ($userEmailSent && $adminEmailSent) {
    $emailStatus = '<p style="color: #28a745; font-weight: bold;">✓ Correos enviados al usuario y al administrador</p>';
  } elseif ($userEmailSent && !$adminEmailSent) {
    $emailStatus = '<p style="color: #ffc107; font-weight: bold;">✓ Correo enviado al usuario<br>⚠ No se pudo enviar al administrador</p>';
  } elseif (!$userEmailSent && $adminEmailSent) {
    $emailStatus = '<p style="color: #ffc107; font-weight: bold;">⚠ No se pudo enviar al usuario<br>✓ Correo enviado al administrador</p>';
  } else {
    $emailStatus = '<p style="color: #dc3545; font-weight: bold;">✗ No se pudieron enviar los correos</p>';
  }
  
  // MODIFICACIÓN: Agregar información de imagen
  $imagenInfo = '';
  if (!empty($imagen_nombre)) {
    $imagenInfo = '<p style="margin: 5px 0;"><strong>Imagen adjunta:</strong> ' . htmlspecialchars($imagen_nombre) . '</p>';
  }
  
  echo '
  <script>
  Swal.fire({
      title: "✅ ¡Ticket Creado Exitosamente!",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">🎉</div>
              <h2 style="color: #003366; margin-bottom: 15px;">Solicitud Registrada</h2>
              
              <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                  <p style="margin: 5px 0;"><strong>Nombre:</strong> ' . $name . '</p>
                  <p style="margin: 5px 0;"><strong>Correo:</strong> ' . $email . '</p>
                  ' . $imagenInfo . '
                  <p style="margin: 10px 0;"><strong>Ticket ID:</strong> 
                      <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">' . $ticketId . '</span>
                  </p>
              </div>
              
              ' . $emailStatus . '
              
              <p style="color: #666; font-size: 14px; margin-top: 15px;">El ticket ha sido registrado en el sistema correctamente.</p>
          </div>
      `,
      icon: "success",
      confirmButtonColor: "#003366",
      confirmButtonText: "Volver al Dashboard",
      allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "dashboard.php";
    }
  });
  </script>';
}

function showWarningAlert($ticketId, $error) {
  echo '
  <script>
  Swal.fire({
      title: "⚠️ Ticket Creado",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #ffc107; margin-bottom: 20px;">📝</div>
              <h2 style="color: #003366; margin-bottom: 15px;">¡Solicitud Registrada!</h2>
              
              <p style="margin-bottom: 20px;"><strong>Ticket ID:</strong> 
                  <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">' . $ticketId . '</span>
              </p>
              
              <p style="color: #666; font-size: 14px; margin-bottom: 10px;">El ticket se ha guardado en el sistema correctamente.</p>
              <p style="color: #dc3545; font-size: 14px; font-weight: bold;">Error: ' . addslashes($error) . '</p>
          </div>
      `,
      icon: "warning",
      confirmButtonColor: "#003366",
      confirmButtonText: "Volver al Dashboard",
      allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "dashboard.php";
    }
  });
  </script>';
}

function showErrorAlert($message) {
  echo '
  <script>
  Swal.fire({
      title: "❌ Error",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #dc3545; margin-bottom: 20px;">⚠️</div>
              <p style="color: #2c3e50;">' . $message . '</p>
              <p style="color: #666; font-size: 14px; margin-top: 15px;">Por favor, intente nuevamente.</p>
          </div>
      `,
      icon: "error",
      confirmButtonColor: "#003366",
      confirmButtonText: "Entendido"
  });
  </script>';
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>