<?php
/**
 * @file FormTic1.php
 * @brief Formulario avanzado de creación de tickets TI con subida de imágenes.
 *
 * @description
 * Formulario completo y avanzado para la creación de tickets de soporte TI.
 * Requiere autenticación previa y precarga los datos del usuario desde la sesión.
 * Incluye funcionalidad de subida de imágenes, generación automática de ID de ticket,
 * y notificaciones por email mediante PHPMailer.
 *
 * Este es el formulario principal usado por empleados autenticados para
 * reportar incidencias de TI. El archivo es extenso (~3000 líneas) debido a
 * la inclusión de estilos CSS inline y lógica JavaScript compleja.
 *
 * Características principales:
 * - Verificación de sesión con redirección a login
 * - Precarga de datos del usuario (nombre, área, ID empleado)
 * - Subida de imágenes (máx 5MB, formatos: jpeg, jpg, png, gif, webp)
 * - Generación automática de carpeta uploads/ si no existe
 * - Preview de imagen antes de envío
 * - Zona horaria configurada: America/Mexico_City
 * - Diseño glassmorphism con fondo animado
 * - Integración con bases de datos de contactos y tickets
 *
 * @module Módulo de Tickets TI
 * @access Privado (requiere sesión activa desde Loginti.php)
 *
 * @dependencies
 * - PHP: session, date_default_timezone_set, file functions
 * - PHPMailer: Notificaciones por email
 * - JS CDN: Bootstrap 5, SweetAlert2, Font Awesome
 * - Interno: Loginti.php (autenticación)
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: T3 (inserción de nuevos tickets)
 *
 * @session
 * - $_SESSION['logged_in']: Verificación de autenticación
 * - $_SESSION['user_name']: Nombre del solicitante (precargado)
 * - $_SESSION['user_id']: ID del empleado
 * - $_SESSION['user_area']: Área del usuario
 * - $_SESSION['user_username']: Username
 *
 * @uploads
 * - Ruta: ../uploads/ (relativa al archivo)
 * - Tamaño máximo: 5MB
 * - Tipos permitidos: image/jpeg, image/jpg, image/png, image/gif, image/webp
 * - Permisos carpeta: 0777
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.5
 * @since 2024
 * @updated 2025-01-20
 */

// INICIO DE SESIÓN Y VERIFICACIÓN
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirigir al login si no está autenticado
    header("Location: Loginti.php");
    exit();
}

// Forzar zona horaria de México
date_default_timezone_set('America/Mexico_City');

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

// FUNCIÓN PARA GENERAR CORREO AUTOMÁTICAMENTE BASADO EN EL NOMBRE DE USUARIO
function generarCorreoDesdeUsuario($usuario) {
    // Si el usuario ya tiene formato de correo (contiene @), devolverlo
    if (strpos($usuario, '@') !== false) {
        return $usuario;
    }
    
    // Limpiar el usuario y convertir a minúsculas
    $correo_base = strtolower(trim($usuario));
    
    // Eliminar espacios y caracteres especiales, mantener puntos y guiones
    $correo_base = preg_replace('/[^a-z0-9._-]/', '', $correo_base);
    
    // Lista de dominios de la empresa
    $dominios_empresa = ['bacrocorp.com', 'bacrocorp.com.mx', 'bacrocorp.mx'];
    
    // Si el usuario tiene punto, asumimos que es nombre.apellido
    if (strpos($correo_base, '.') !== false) {
        return $correo_base . '@' . $dominios_empresa[0];
    }
    
    // Si no tiene punto, intentar separar nombre y apellido del nombre completo
    global $nombre;
    $nombre_partes = explode(' ', trim($nombre ?? ''));
    
    if (count($nombre_partes) >= 2) {
        $primer_nombre = strtolower($nombre_partes[0]);
        $primer_apellido = strtolower(end($nombre_partes));
        return $primer_nombre . '.' . $primer_apellido . '@' . $dominios_empresa[0];
    }
    
    // Por defecto, usar el usuario con el dominio principal
    return $correo_base . '@' . $dominios_empresa[0];
}

// Generar el correo automáticamente
$correo_automatico = generarCorreoDesdeUsuario($usuario);

// Crear iniciales para el avatar
$iniciales = substr($nombre, 0, 2);

// Definir los tipos y subtipos con sus prioridades, responsables e iconos
$tipo_opciones = [
    'SOLICITUDES' => [
        'ALTAS DE USUARIO' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '👤'],
        'BAJAS DE USUARIO' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '👤❌'],
        'GESTIÓN/MODIFICACIÓN DE USUARIOS' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '👤🔄'],
        'PERMISOS A SISTEMAS(CONTPAQ,CONTROL VEHICULAR GOOGLE SHEET)' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '🔐'],
        'ACCESO A CARPETAS' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '📁'],
        'ACCESO A VPN' => ['prioridad' => 'BAJA', 'responsable' => 'Ariel', 'icono' => '🌐'],
        'INSTALACIÓN DE SOFTWARE' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo / Ariel', 'icono' => '⬇️'],
        'SOLICITUD DE NUEVOS SISTEMAS' => ['prioridad' => 'BAJA', 'responsable' => 'Luis Salvador', 'icono' => '🆕'],
        'ASIGNACIÓN(REASIGNACION DE PC)' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '💻'],
        'CAMBIO DE EQUIPO' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo', 'icono' => '💻🔄'],
        'SALIDA DE EQUIPO' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '💻🚪'],
        'SOLICITUD DE PERIFÉRICO(MONITOR,TECLADO Y MOUSE)' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo', 'icono' => '🖱️'],
        'ALTA DE LINEA TELEFONICA' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo', 'icono' => '📞'],
        'CAMBIO DE NUMERO MOVIL' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo', 'icono' => '📱'],
        'REPOSICIÓN DE SIM' => ['prioridad' => 'BAJA', 'responsable' => 'Alfredo', 'icono' => '📱🔄'],
        'ASIGNACION DE EXTENSIÓN' => ['prioridad' => 'BAJA', 'responsable' => 'Ariel', 'icono' => '☎️'],
        'SOLICITUD DE CONEXIÓN AL PROYECTOR DE SALA DE JUNTAS' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '📽️'],
        'OTROS' => ['prioridad' => 'BAJA', 'responsable' => 'Ariel', 'icono' => '❓']
    ],
    'PROBLEMAS' => [
        'SIN ACCESO A INTERNET O FALLA DE INTERNET' => ['prioridad' => 'URGENTE', 'responsable' => 'Alfredo', 'icono' => '🌐❌'],
        'INTERNET LENTO' => ['prioridad' => 'URGENTE', 'responsable' => 'Alfredo', 'icono' => '🌐🐌'],
        'PROBLEMAS DE IMPRESIÓN' => ['prioridad' => 'URGENTE', 'responsable' => 'Alfredo', 'icono' => '🖨️❌'],
        'PROBLEMAS CON CAMARAS' => ['prioridad' => 'URGENTE', 'responsable' => 'Ariel', 'icono' => '📷❌'],
        'SIN ACCESO A CONTPAQ' => ['prioridad' => 'URGENTE', 'responsable' => 'Ariel', 'icono' => '💼❌'],
        'ERRORES DEL SISTEMA' => ['prioridad' => 'URGENTE', 'responsable' => 'Ariel', 'icono' => '⚠️'],
        'SIN ACCESO A TELEFONIA MOVIL' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '📱❌'],
        'SIN ACCESO A EXTENSIÓN' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '☎️❌'],
        'REVISION DE EQUIPO DE COMPUTO POR FALLA' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '💻🔧'],
        'APLICACIONES QUE NO ABREN' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '🚫'],
        'SIN ACCESO A CORREO ELECTRÓNICO' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '📧❌'],
        'PROBLEMAS DE CARPETAS Y ACCESOS' => ['prioridad' => 'MEDIA', 'responsable' => 'Alfredo', 'icono' => '📁❌'],
        'PROBLEMAS CON CHECADOR' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '⏰❌'],
        'COMEDOR' => ['prioridad' => 'MEDIA', 'responsable' => 'Luis', 'icono' => '🍽️'],
        'CONTROL VEHICULAR' => ['prioridad' => 'MEDIA', 'responsable' => 'Luis', 'icono' => '🚗'],
        'OTROS' => ['prioridad' => 'MEDIA', 'responsable' => 'Ariel', 'icono' => '❓']
    ]
];

// Mapeo de responsables a correos
$responsable_emails = [
    'Alfredo' => 'alfredo.rosales@bacrocorp.com',
    'Ariel' => 'ariel.sanchez@bacrocorp.com',
    'Boris' => 'boris.ramirez@bacrocorp.com',
    'Luis' => 'luis.romero@bacrocorp.com',
    'Luis Salvador' => 'luis.medina@bacrocorp.com',
    'Alfredo / Ariel' => 'alfredo.rosales@bacrocorp.com, ariel.sanchez@bacrocorp.com'
];

// Reglas de reemplazo
$reemplazos = [
    'Ariel' => 'Boris',
    'Alfredo' => 'Luis',
    'Luis' => 'Ariel',
    'Boris' => 'Ariel'
];

// Función para determinar el responsable final basado en carga de trabajo
function determinarResponsableFinal($responsable_principal, $subtipo) {
    global $reemplazos;
    
    // Para casos de múltiples responsables (ej: "Alfredo / Ariel")
    if (strpos($responsable_principal, '/') !== false) {
        $responsables = array_map('trim', explode('/', $responsable_principal));
        $responsable_principal = $responsables[0]; // Tomar el primero como principal
    }
    
    // Conectar a la base de datos para verificar carga de trabajo
    $serverName = $DB_HOST;
    $connectionInfo = array(
        "Database"=>$DB_DATABASE,
        "UID"=>$DB_USERNAME,
        "PWD"=>$DB_PASSWORD,
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true,
        "Encrypt" => true
    );
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    
    if ($conn) {
        // Contar tickets en proceso del responsable principal
        $sql = "SELECT COUNT(*) as total FROM T3 WHERE PA = ? AND Estatus = 'En proceso'";
        $params = array($responsable_principal);
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $tickets_en_proceso = $row['total'];
            
            // Si tiene más de 5 tickets en proceso, asignar al reemplazo
            if ($tickets_en_proceso >= 5) {
                $reemplazo = isset($reemplazos[$responsable_principal]) ? $reemplazos[$responsable_principal] : $responsable_principal;
                
                // Verificar si el reemplazo también tiene muchos tickets
                $sql_reemplazo = "SELECT COUNT(*) as total FROM T3 WHERE PA = ? AND Estatus = 'En proceso'";
                $params_reemplazo = array($reemplazo);
                $stmt_reemplazo = sqlsrv_query($conn, $sql_reemplazo, $params_reemplazo);
                
                if ($stmt_reemplazo) {
                    $row_reemplazo = sqlsrv_fetch_array($stmt_reemplazo, SQLSRV_FETCH_ASSOC);
                    $tickets_reemplazo = $row_reemplazo['total'];
                    
                    // Si el reemplazo ya atendió al menos 1 ticket, regresar al principal
                    if ($tickets_reemplazo > 0) {
                        sqlsrv_free_stmt($stmt_reemplazo);
                        sqlsrv_free_stmt($stmt);
                        sqlsrv_close($conn);
                        return $responsable_principal;
                    }
                }
                
                sqlsrv_free_stmt($stmt_reemplazo);
                sqlsrv_free_stmt($stmt);
                sqlsrv_close($conn);
                return $reemplazo;
            }
            
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
            return $responsable_principal;
        }
        
        sqlsrv_close($conn);
    }
    
    // Si hay error de conexión, devolver el responsable principal
    return $responsable_principal;
}

// Función para obtener el correo del responsable
function obtenerEmailResponsable($responsable) {
    global $responsable_emails;
    
    // Si hay múltiples responsables, tomar el primero
    if (strpos($responsable, '/') !== false) {
        $responsables = array_map('trim', explode('/', $responsable));
        $responsable = $responsables[0];
    }
    
    return isset($responsable_emails[$responsable]) ? $responsable_emails[$responsable] : 'tickets@bacrocorp.com';
}
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
  
  /* Overlay para procesamiento */
  .processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(2, 12, 27, 0.9);
    backdrop-filter: blur(10px);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }
  
  .processing-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease-out;
  }
  
  .processing-modal {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: var(--glass-shadow);
    animation: slideUpModal 0.5s ease-out;
  }
  
  .processing-spinner {
    width: 80px;
    height: 80px;
    margin: 0 auto 25px;
    border: 4px solid rgba(59, 130, 246, 0.2);
    border-top-color: var(--accent-blue);
    border-radius: 50%;
    animation: spin 1s infinite linear;
  }
  
  .processing-title {
    font-family: var(--font-heading);
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 15px;
    background: var(--gradient-blue);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  
  .processing-message {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 25px;
    line-height: 1.6;
  }
  
  .processing-steps {
    text-align: left;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
  }
  
  .step-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
  }
  
  .step-item i {
    width: 20px;
    color: var(--accent-blue);
  }
  
  .step-item .fa-check-circle {
    color: #10b981;
  }
  
  .step-item .fa-spinner {
    color: var(--accent-blue);
    animation: spin 1s infinite linear;
  }
  
  .processing-progress {
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
    margin: 20px 0;
  }
  
  .progress-bar-fill {
    height: 100%;
    background: var(--gradient-blue);
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 3px;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
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
  
  select.form-control option {
    background-color: var(--navy-secondary);
    color: white;
    padding: 10px;
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
  
  /* Estilos para prioridades */
  .priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
  }
  
  .priority-urgente {
    background-color: rgba(220, 53, 69, 0.2);
    color: #ff6b6b;
    border: 1px solid rgba(220, 53, 69, 0.4);
  }
  
  .priority-media {
    background-color: rgba(255, 193, 7, 0.2);
    color: #ffd166;
    border: 1px solid rgba(255, 193, 7, 0.4);
  }
  
  .priority-baja {
    background-color: rgba(40, 167, 69, 0.2);
    color: #51cf66;
    border: 1px solid rgba(40, 167, 69, 0.4);
  }
  
  /* Estilo para responsables */
  .responsable-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    background-color: rgba(108, 117, 125, 0.2);
    color: #adb5bd;
    border: 1px solid rgba(108, 117, 125, 0.4);
  }
  
  .option-with-icon {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .option-icon {
    font-size: 1.2em;
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
    
    .processing-modal {
      padding: 25px;
    }
    
    .processing-title {
      font-size: 1.5rem;
    }
    
    .processing-message {
      font-size: 1rem;
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
  <!-- Overlay de Procesamiento -->
  <div class="processing-overlay" id="processingOverlay">
    <div class="processing-modal">
      <div class="processing-spinner"></div>
      <h2 class="processing-title">Procesando Ticket</h2>
      <p class="processing-message">Estamos guardando tu solicitud. Por favor, no cierres esta ventana ni hagas clic en ningún botón.</p>
      
      <div class="processing-steps">
        <div class="step-item" id="step1">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Validando información del ticket...</span>
        </div>
        <div class="step-item" id="step2">
          <i class="fas fa-circle-notch"></i>
          <span>Asignando responsable automáticamente...</span>
        </div>
        <div class="step-item" id="step3">
          <i class="fas fa-circle-notch"></i>
          <span>Guardando en base de datos...</span>
        </div>
        <div class="step-item" id="step4">
          <i class="fas fa-circle-notch"></i>
          <span>Enviando notificaciones por correo...</span>
        </div>
      </div>
      
      <div class="processing-progress">
        <div class="progress-bar-fill" id="progressBar"></div>
      </div>
      
      <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.85rem;">
        Este proceso puede tomar unos segundos...
      </p>
    </div>
  </div>

  <!-- Partículas de fondo -->
  <div class="particles-container" id="particlesContainer"></div>
  
  <!-- Botón Home -->
  <button id="homeButton" onclick="window.location.href='dashboard.php'">
    <i class="fas fa-home"></i>
  </button>
  
  <!-- Header Section -->
  <div class="header-section animate-in">
    <div class="logo-container">
      <div class="logo-glow">
        <i class="fas fa-shield-alt"></i>
      </div>
    </div>
    
    <h1 class="system-title">BACROCORP</h1>
    <p class="system-subtitle">Sistema Automatizado de Gestión de Tickets TI</p>
    
    <div class="security-badge">
      <i class="fas fa-badge-check"></i>
      Sistema Certificado - Asignación Automática Inteligente
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
          <strong>ID:</strong> <?php echo htmlspecialchars($id_empleado); ?> |
          <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?>
        </div>
      </div>
      
      <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" id="ticketForm" enctype="multipart/form-data">
        <!-- Sección Información Personal (PRE-LLENADA AUTOMÁTICAMENTE) -->
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
                <input type="email" class="form-control" id="elect" name="elect" value="<?php echo htmlspecialchars($correo_automatico); ?>" readonly />
                <i class="fas fa-lock input-icon" style="color: #10b981;"></i>
                <small style="color: rgba(255, 255, 255, 0.6); font-size: 0.7rem; position: absolute; bottom: -18px; left: 0;">
                  * Generado automáticamente desde: <?php echo htmlspecialchars($usuario); ?>
                </small>
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
              <label for="tipo" class="form-label">
                <i class="fas fa-layer-group"></i>
                Clasificación
              </label>
              <div class="input-container">
                <select class="form-control" id="tipo" name="tipo" required onchange="updateSubtipoOptions()">
                  <option value="">Seleccione una clasificación...</option>
                  <option value="SOLICITUDES">📝 SOLICITUDES</option>
                  <option value="PROBLEMAS">⚠️ PROBLEMAS</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="subtipo" class="form-label">
                <i class="fas fa-tags"></i>
                Tipo Específico (Asunto)
              </label>
              <div class="input-container">
                <select class="form-control" id="subtipo" name="subtipo" required onchange="updatePrioridadYResponsable()">
                  <option value="">Primero seleccione una clasificación</option>
                </select>
                <i class="fas fa-chevron-down select-arrow"></i>
              </div>
            </div>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="prio" class="form-label">
                <i class="fas fa-flag"></i>
                Nivel de Prioridad
              </label>
              <div class="input-container">
                <select class="form-control" id="prio" name="prio" required readonly>
                  <option value="">Se asignará automáticamente</option>
                </select>
                <i class="fas fa-lock input-icon"></i>
              </div>
            </div>
            
            <div class="form-group">
              <label for="responsable" class="form-label">
                <i class="fas fa-user-tie"></i>
                Persona Asignada (PA)
              </label>
              <div class="input-container">
                <input type="text" class="form-control" id="responsable" name="responsable" value="" readonly />
                <i class="fas fa-user-cog input-icon"></i>
              </div>
              <small style="color: rgba(255, 255, 255, 0.6); font-size: 0.8rem; margin-top: 5px;">
                * Se asigna automáticamente según tipo y carga de trabajo
              </small>
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
        
        <!-- Sección Descripción del Problema -->
        <div class="form-section animate-in delay-3">
          <h3 class="section-title">
            <i class="fas fa-clipboard-list"></i>
            Descripción del Problema/Solicitud
          </h3>
          
          <div class="form-group">
            <label for="adj" class="form-label">
              <i class="fas fa-heading"></i>
              Título del Ticket
            </label>
            <div class="input-container">
              <input type="text" class="form-control" id="adj" name="adj" placeholder="Describa brevemente el problema o solicitud..." required />
              <i class="fas fa-pen input-icon"></i>
            </div>
          </div>
          
          <div class="form-group">
            <label for="men" class="form-label">
              <i class="fas fa-align-left"></i>
              Detalles Completos
            </label>
            <div class="input-container">
              <textarea class="form-control" id="men" name="men" placeholder="Proporcione todos los detalles relevantes..."></textarea>
              <i class="fas fa-file-alt input-icon" style="top: 18px; transform: none;"></i>
            </div>
          </div>
          
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
              <span class="preview-label">Clasificación:</span>
              <span class="preview-value" id="previewTipo">No seleccionado</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Tipo Específico:</span>
              <span class="preview-value" id="previewSubtipo">No seleccionado</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Prioridad:</span>
              <span class="preview-value" id="previewPriority">No asignada</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Responsable (PA):</span>
              <span class="preview-value" id="previewResponsable">Por asignar</span>
            </div>
            <div class="preview-item">
              <span class="preview-label">Estatus:</span>
              <span class="preview-value" style="color: #f59e0b; font-weight: bold;">En proceso</span>
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
            <span>Generar Ticket</span>
          </div>
        </button>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Datos de tipos y subtipos con prioridades, responsables e iconos
    const tipoOpciones = <?php echo json_encode($tipo_opciones); ?>;
    
    // Variables para el modal de procesamiento
    let processingInterval;
    let currentStep = 1;
    let progressValue = 0;
    
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
    
    // Mostrar overlay de procesamiento
    function showProcessingModal() {
      const overlay = document.getElementById('processingOverlay');
      overlay.classList.add('active');
      
      // Resetear pasos y progreso
      currentStep = 1;
      progressValue = 0;
      
      // Actualizar pasos
      updateProcessingSteps(1);
      
      // Iniciar animación de progreso
      processingInterval = setInterval(updateProcessingProgress, 800);
    }
    
    // Actualizar pasos del procesamiento
    function updateProcessingSteps(step) {
      const steps = [
        { id: 'step1', icon: 'fa-spinner', text: 'Validando información del ticket...' },
        { id: 'step2', icon: 'fa-circle-notch', text: 'Asignando responsable automáticamente...' },
        { id: 'step3', icon: 'fa-circle-notch', text: 'Guardando en base de datos...' },
        { id: 'step4', icon: 'fa-circle-notch', text: 'Enviando notificaciones por correo...' }
      ];
      
      for (let i = 1; i <= 4; i++) {
        const stepElement = document.getElementById(`step${i}`);
        const iconElement = stepElement.querySelector('i');
        
        if (i < step) {
          // Pasos completados
          iconElement.className = 'fas fa-check-circle';
          iconElement.style.color = '#10b981';
          stepElement.style.opacity = '0.8';
        } else if (i === step) {
          // Paso actual
          iconElement.className = 'fas fa-spinner fa-spin';
          iconElement.style.color = 'var(--accent-blue)';
          stepElement.style.opacity = '1';
        } else {
          // Pasos pendientes
          iconElement.className = 'fas fa-circle-notch';
          iconElement.style.color = 'rgba(255, 255, 255, 0.3)';
          stepElement.style.opacity = '0.5';
        }
      }
    }
    
    // Actualizar progreso
    function updateProcessingProgress() {
      if (currentStep <= 4) {
        // Aumentar progreso según el paso
        if (currentStep === 1) {
          progressValue = Math.min(25, progressValue + 5);
        } else if (currentStep === 2) {
          progressValue = Math.min(50, progressValue + 5);
        } else if (currentStep === 3) {
          progressValue = Math.min(75, progressValue + 5);
        } else if (currentStep === 4) {
          progressValue = Math.min(95, progressValue + 5);
        }
        
        document.getElementById('progressBar').style.width = progressValue + '%';
        
        // Cambiar al siguiente paso si se alcanzó el progreso necesario
        if (currentStep === 1 && progressValue >= 25) {
          currentStep = 2;
          updateProcessingSteps(2);
        } else if (currentStep === 2 && progressValue >= 50) {
          currentStep = 3;
          updateProcessingSteps(3);
        } else if (currentStep === 3 && progressValue >= 75) {
          currentStep = 4;
          updateProcessingSteps(4);
        }
      }
    }
    
    // Completar procesamiento
    function completeProcessing() {
      clearInterval(processingInterval);
      
      // Marcar todos los pasos como completados
      for (let i = 1; i <= 4; i++) {
        const stepElement = document.getElementById(`step${i}`);
        const iconElement = stepElement.querySelector('i');
        iconElement.className = 'fas fa-check-circle';
        iconElement.style.color = '#10b981';
        stepElement.style.opacity = '1';
      }
      
      // Completar barra de progreso
      document.getElementById('progressBar').style.width = '100%';
    }
    
    // Ocultar overlay de procesamiento
    function hideProcessingModal() {
      const overlay = document.getElementById('processingOverlay');
      overlay.classList.remove('active');
      clearInterval(processingInterval);
    }
    
    // Actualizar opciones de subtipo según el tipo seleccionado
    function updateSubtipoOptions() {
      const tipoSelect = document.getElementById('tipo');
      const subtipoSelect = document.getElementById('subtipo');
      const prioSelect = document.getElementById('prio');
      const responsableInput = document.getElementById('responsable');
      
      const tipoSeleccionado = tipoSelect.value;
      
      subtipoSelect.innerHTML = '<option value="">Seleccione un tipo específico...</option>';
      prioSelect.innerHTML = '<option value="">Se asignará automáticamente</option>';
      responsableInput.value = '';
      
      if (tipoSeleccionado && tipoOpciones[tipoSeleccionado]) {
        const subtipos = tipoOpciones[tipoSeleccionado];
        
        for (const [subtipo, datos] of Object.entries(subtipos)) {
          const option = document.createElement('option');
          option.value = subtipo;
          
          const icono = datos.icono || '';
          option.textContent = `${icono} ${subtipo}`;
          
          option.dataset.prioridad = datos.prioridad;
          option.dataset.responsable = datos.responsable;
          option.dataset.icono = datos.icono;
          
          subtipoSelect.appendChild(option);
        }
        
        subtipoSelect.disabled = false;
      } else {
        subtipoSelect.disabled = true;
      }
      
      updatePreview();
    }
    
    // Actualizar prioridad y responsable según el subtipo seleccionado
    function updatePrioridadYResponsable() {
      const tipoSelect = document.getElementById('tipo');
      const subtipoSelect = document.getElementById('subtipo');
      const prioSelect = document.getElementById('prio');
      const responsableInput = document.getElementById('responsable');
      
      const tipoSeleccionado = tipoSelect.value;
      const subtipoSeleccionado = subtipoSelect.value;
      
      if (tipoSeleccionado && subtipoSeleccionado && tipoOpciones[tipoSeleccionado]) {
        const datos = tipoOpciones[tipoSeleccionado][subtipoSeleccionado];
        const prioridad = datos.prioridad;
        const responsable = datos.responsable;
        const icono = datos.icono;
        
        // Actualizar prioridad
        prioSelect.innerHTML = '';
        let selectValue = '';
        let displayText = '';
        
        switch(prioridad) {
          case 'URGENTE':
            selectValue = 'Alto';
            displayText = '🔴 URGENTE - Requiere atención inmediata';
            break;
          case 'MEDIA':
            selectValue = 'Medio';
            displayText = '🟡 MEDIA - Atención en las próximas horas';
            break;
          case 'BAJA':
            selectValue = 'Bajo';
            displayText = '🟢 BAJA - Atención en días hábiles';
            break;
          default:
            selectValue = 'Medio';
            displayText = 'Medio';
        }
        
        const option = document.createElement('option');
        option.value = selectValue;
        option.textContent = displayText;
        option.selected = true;
        prioSelect.appendChild(option);
        
        // Actualizar responsable
        responsableInput.value = responsable;
        
        // Actualizar vista previa
        const priorityElement = document.getElementById('previewPriority');
        priorityElement.className = 'preview-value';
        priorityElement.textContent = displayText;
        
        if (prioridad === 'URGENTE') {
          priorityElement.classList.add('priority-urgente');
        } else if (prioridad === 'MEDIA') {
          priorityElement.classList.add('priority-media');
        } else if (prioridad === 'BAJA') {
          priorityElement.classList.add('priority-baja');
        }
        
        document.getElementById('previewResponsable').textContent = responsable;
        document.getElementById('previewSubtipo').textContent = `${icono} ${subtipoSeleccionado}`;
      } else {
        prioSelect.innerHTML = '<option value="">Se asignará automáticamente</option>';
        responsableInput.value = '';
        document.getElementById('previewSubtipo').textContent = subtipoSeleccionado || 'No seleccionado';
        document.getElementById('previewResponsable').textContent = 'Por asignar';
      }
      
      updatePreview();
    }
    
    // Actualizar vista previa en tiempo real
    function updatePreview() {
      const tipo = document.getElementById('tipo').value;
      const subtipo = document.getElementById('subtipo').value;
      const prioridad = document.getElementById('prio').value;
      const responsable = document.getElementById('responsable').value;
      
      let tipoDisplay = 'No seleccionado';
      if (tipo) {
        tipoDisplay = tipo === 'SOLICITUDES' ? '📝 SOLICITUDES' : '⚠️ PROBLEMAS';
      }
      document.getElementById('previewTipo').textContent = tipoDisplay;
      
      if (!subtipo) {
        document.getElementById('previewSubtipo').textContent = 'No seleccionado';
      }
      
      const priorityElement = document.getElementById('previewPriority');
      if (prioridad.includes('Alto') || prioridad.includes('URGENTE')) {
        priorityElement.style.color = '#ef4444';
        priorityElement.style.fontWeight = 'bold';
      } else if (prioridad.includes('Medio') || prioridad.includes('MEDIA')) {
        priorityElement.style.color = '#f59e0b';
      } else if (prioridad.includes('Bajo') || prioridad.includes('BAJA')) {
        priorityElement.style.color = '#10b981';
      }
      
      if (responsable) {
        document.getElementById('previewResponsable').textContent = responsable;
      }
    }
    
    // Manejo de archivos subidos
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
      
      fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
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
          
          fileName.textContent = file.name;
          fileSize.textContent = formatFileSize(file.size);
          fileType.textContent = file.type.split('/')[1].toUpperCase();
          
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
      
      removeFileBtn.addEventListener('click', function() {
        fileInput.value = '';
        filePreview.classList.remove('active');
        imagePreview.classList.remove('active');
        previewImage.textContent = 'No';
      });
      
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
    
    function validateForm() {
      // El correo ya está pre-llenado y es válido
      const tipo = document.getElementById('tipo').value;
      const subtipo = document.getElementById('subtipo').value;
      
      if (!tipo) {
        Swal.fire({
          title: 'Clasificación no seleccionada',
          text: 'Por favor, seleccione una clasificación (SOLICITUDES o PROBLEMAS).',
          icon: 'error',
          confirmButtonColor: '#003366'
        });
        return false;
      }
      
      if (!subtipo) {
        Swal.fire({
          title: 'Tipo específico no seleccionado',
          text: 'Por favor, seleccione un tipo específico.',
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
      setupFileUpload();
      
      setInterval(updateDateTime, 1000);
      
      document.getElementById('tipo').addEventListener('change', updatePreview);
      document.getElementById('subtipo').addEventListener('change', updatePreview);
      document.getElementById('prio').addEventListener('change', updatePreview);
      
      const elements = document.querySelectorAll('.animate-in');
      elements.forEach((el, index) => {
        el.style.animationDelay = `${(index + 1) * 0.1}s`;
      });
      
      document.getElementById('ticketForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
          return false;
        }
        
        // Mostrar modal de procesamiento
        showProcessingModal();
        
        // Prevenir múltiples envíos
        const submitBtn = this.querySelector('.btn-submit');
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.8';
        
        // El formulario se enviará después de mostrar el modal
        return true;
      });
    });
    
    // Escuchar eventos de envío completado (desde PHP)
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    window.onload = function() {
      <?php if (isset($stmt) && $stmt !== false): ?>
      // Ocultar modal de procesamiento
      hideProcessingModal();
      
      // Mostrar resultado
      <?php
      if (isset($emailResults)) {
        showSuccessAlert($name1, $name2, $name10, $persona_asignada, 
          $emailResults['user'], $emailResults['admin'], $emailResults['responsable'], 
          $imagen_nombre ?? '', $clasificacion ?? '', $subtipo ?? '');
      }
      ?>
      <?php endif; ?>
    };
    <?php endif; ?>
  </script>
</body>
</html>

<?php
// Incluir los archivos necesarios de PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Variables para la imagen
  $imagen_url = '';
  $imagen_nombre = '';
  $imagen_tipo = '';
  $imagen_size = 0;
  
  if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['imagen']['tmp_name'];
    $file_name = basename($_FILES['imagen']['name']);
    $file_type = $_FILES['imagen']['type'];
    $file_size = $_FILES['imagen']['size'];
    
    if ($file_size <= 5 * 1024 * 1024) {
      $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (in_array($file_type, $allowed_types)) {
        if (getimagesize($file_tmp)) {
          $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
          $unique_name = 'ticket_' . time() . '_' . uniqid() . '.' . $file_ext;
          $destination = '../uploads/' . $unique_name;
          
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
  
  // Obtener datos del formulario
  $name1 = $nombre;
  $name2 = test_input($_POST["elect"]); // Correo automático
  $name3 = test_input($_POST["prio"]);
  $name4 = $area;
  $name5 = test_input($_POST["subtipo"]);
  $name6 = test_input($_POST["men"]);
  $name7 = test_input($_POST["adj"]);
  $name8 = test_input($_POST["fecha"]);
  $name9 = test_input($_POST["Hora"]);
  $name10 = test_input($_POST["tik"]);
  $clasificacion = test_input($_POST["tipo"]);
  $subtipo = test_input($_POST["subtipo"]);
  
  // Determinar responsable final basado en carga de trabajo
  $responsable_principal = isset($tipo_opciones[$clasificacion][$subtipo]['responsable']) 
    ? $tipo_opciones[$clasificacion][$subtipo]['responsable'] 
    : 'Ariel';
  
  $persona_asignada = determinarResponsableFinal($responsable_principal, $subtipo);
  
  // Obtener fecha y hora actual con formato para SQL Server
  $fecha_actual = date('Y-m-d');
  $hora_actual_completa = date('Y-m-d H:i:s') . '.0000000'; // Formato: 2026-02-17 11:45:51.0000000
  
  // Insertar en la base de datos con estatus "En proceso", persona asignada y fecha/hora de proceso
  $serverName = $DB_HOST;
  $connectionInfo = array(
    "Database"=>$DB_DATABASE,
    "UID"=>$DB_USERNAME,
    "PWD"=>$DB_PASSWORD,
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => true
  );
  $conn = sqlsrv_connect($serverName, $connectionInfo);

  if ($conn) {
    $sql = "INSERT INTO T3 (
      [Nombre],[Correo],[Prioridad],[Empresa],[Asunto],[Mensaje],[Adjuntos],
      [Fecha],[Hora],[Id_Ticket],[Estatus],[PA],[imagen_url],[imagen_nombre],[imagen_tipo],[imagen_size],[Clasificacion],
      [FechaEnProceso],[HoraEnProceso]
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En proceso', ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = array(
      $name1, $name2, $name3, $name4, $name5, $name6, $name7, 
      $name8, $name9, $name10, $persona_asignada, $imagen_url, $imagen_nombre, $imagen_tipo, $imagen_size, $clasificacion,
      $fecha_actual, $hora_actual_completa
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
      error_log("Error en la base de datos: " . print_r(sqlsrv_errors(), true));
      showErrorAlert("Error al guardar el ticket en la base de datos.");
    } else {
      sqlsrv_free_stmt($stmt);
      
      // Enviar correos de notificación
      $emailResults = sendNotificationEmails(
        $name1, $name2, $name3, $name4, $name5, $name6, $name7, 
        $name8, $name9, $name10, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo
      );
      
      if ($emailResults['user'] && $emailResults['admin'] && $emailResults['responsable']) {
        showSuccessAlert($name1, $name2, $name10, $persona_asignada, true, true, true, $imagen_nombre, $clasificacion, $subtipo);
      } elseif ($emailResults['user'] || $emailResults['admin'] || $emailResults['responsable']) {
        showSuccessAlert($name1, $name2, $name10, $persona_asignada, 
          $emailResults['user'], $emailResults['admin'], $emailResults['responsable'], 
          $imagen_nombre, $clasificacion, $subtipo);
      } else {
        showWarningAlert($name10, "No se pudieron enviar los correos de notificación.");
      }
    }
    
    sqlsrv_close($conn);
  } else {
    error_log("Error de conexión a la base de datos: " . print_r(sqlsrv_errors(), true));
    showErrorAlert("Error de conexión a la base de datos.");
  }
}

// Función para enviar todos los correos de notificación
function sendNotificationEmails($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  $results = [
    'user' => false,
    'admin' => false,
    'responsable' => false
  ];
  
  // Enviar correo al usuario
  $results['user'] = sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
  
  // Enviar correo al administrador
  $results['admin'] = sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
  
  // Enviar correo al responsable asignado
  $results['responsable'] = sendResponsableNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
  
  return $results;
}

// Función para enviar correo al usuario
function sendUserConfirmationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  try {
    $mail = new PHPMailer(true);
    
    configurarSMTP($mail);
    
    $mail->setFrom('tickets@bacrocorp.com', 'Departamento de TI - BacroCorp');
    $mail->addAddress($email, $name);
    
    $mail->isHTML(true);
    $mail->Subject = '✅ Ticket #' . $ticketId . ' Creado - Departamento de TI BacroCorp';
    $mail->Body = createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
    
    return $mail->send();
    
  } catch (Exception $e) {
    error_log("Error enviando correo al usuario: " . $e->getMessage());
    return false;
  }
}

// Función para enviar correo al administrador
function sendAdminNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  try {
    $mail = new PHPMailer(true);
    
    configurarSMTP($mail);
    
    $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
    $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
    
    $mail->isHTML(true);
    $mail->Subject = '📋 NUEVO TICKET #' . $ticketId . ' - Asignado a: ' . $persona_asignada;
    $mail->Body = createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
    
    return $mail->send();
    
  } catch (Exception $e) {
    error_log("Error enviando correo al administrador: " . $e->getMessage());
    return false;
  }
}

// Función para enviar correo al responsable asignado
function sendResponsableNotificationEmail($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  try {
    $mail = new PHPMailer(true);
    
    configurarSMTP($mail);
    
    $mail->setFrom('tickets@bacrocorp.com', 'Sistema de Tickets BacroCorp');
    
    // Obtener el correo del responsable
    $email_responsable = obtenerEmailResponsable($persona_asignada);
    $mail->addAddress($email_responsable, $persona_asignada);
    
    $mail->isHTML(true);
    $mail->Subject = '🎯 NUEVO TICKET ASIGNADO #' . $ticketId . ' - ' . $priority . ' - ' . $subtipo;
    $mail->Body = createResponsableEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo);
    
    return $mail->send();
    
  } catch (Exception $e) {
    error_log("Error enviando correo al responsable: " . $e->getMessage());
    return false;
  }
}

// Función para crear plantilla de correo al usuario
function createUserEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  $priorityColor = getPriorityColor($priority);
  $icono = getIconoForSubtipo($clasificacion, $subtipo);
  
  $imagen_info = '';
  if (!empty($imagen_nombre)) {
    $imagen_info = '
    <div class="ticket-detail">
        <span class="ticket-label">Imagen Adjunta:</span>
        <span>📎 ' . htmlspecialchars($imagen_nombre) . '</span>
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
          .status-badge {
              display: inline-block;
              padding: 6px 12px;
              border-radius: 15px;
              font-size: 12px;
              font-weight: bold;
              background-color: #ffc107;
              color: #856404;
          }
      </style>
  </head>
  <body>
      <div class="container">
          <div class="header">
              <h1>🎯 Departamento de TI - BacroCorp</h1>
              <p>Sistema Automatizado de Gestión de Tickets</p>
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
                      <span class="ticket-label">Estatus:</span>
                      <span class="status-badge">En proceso</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Responsable Asignado:</span>
                      <span><strong>' . $persona_asignada . '</strong></span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Departamento:</span>
                      <span>' . $department . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Clasificación:</span>
                      <span>' . $clasificacion . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Asunto:</span>
                      <span>' . $icono . ' ' . $subject . '</span>
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

// Función para crear plantilla de correo al administrador
function createAdminEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  $priorityColor = getPriorityColor($priority);
  $urgencyIcon = getUrgencyIcon($priority);
  $icono = getIconoForSubtipo($clasificacion, $subtipo);
  
  $imagen_info = '';
  if (!empty($imagen_nombre)) {
    $imagen_info = '
    <div class="ticket-detail">
        <span class="ticket-label">Imagen Adjunta:</span>
        <span><strong>📎 ' . htmlspecialchars($imagen_nombre) . '</strong></span>
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
              background: linear-gradient(135deg, #003366 0%, #0066cc 100%);
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
              <h1>📊 REPORTE DE TICKET - SISTEMA AUTOMATIZADO</h1>
          </div>
          <div class="content">
              <div style="text-align: center; margin-bottom: 20px;">
                  <span style="background: #003366; color: white; padding: 10px 25px; border-radius: 20px; font-size: 18px; font-weight: bold;">TICKET #: ' . $ticketId . '</span>
              </div>
              
              <div class="ticket-card">
                  <h3 style="margin-top: 0; color: #2c3e50;">📋 Información del Ticket</h3>
                  <div class="ticket-detail">
                      <span class="ticket-label">Fecha y Hora:</span>
                      <span>' . $date . ' - ' . $time . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Prioridad:</span>
                      <span class="priority-badge">' . $priority . '</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Estatus:</span>
                      <span style="background: #ffc107; color: #856404; padding: 4px 10px; border-radius: 12px; font-weight: bold;">En proceso</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Persona Asignada:</span>
                      <span><strong>' . $persona_asignada . '</strong></span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Clasificación:</span>
                      <span><strong>' . $clasificacion . '</strong></span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Asunto:</span>
                      <span><strong>' . $icono . ' ' . $subject . '</strong></span>
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
                  <h3 style="margin-top: 0; color: #856404;">📝 Descripción del Problema/Solicitud</h3>
                  <p><strong>' . $description . '</strong></p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
              </div>
              
              <div style="text-align: center; color: #666; font-size: 14px; margin-top: 20px;">
                  <p>Este es un mensaje automático del Sistema de Tickets BacroCorp - Asignación Automática</p>
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

// Función para crear plantilla de correo al responsable
function createResponsableEmailTemplate($name, $email, $priority, $department, $subject, $message, $description, $date, $time, $ticketId, $persona_asignada, $imagen_nombre, $clasificacion, $subtipo) {
  $priorityColor = getPriorityColor($priority);
  $urgencyIcon = getUrgencyIcon($priority);
  $icono = getIconoForSubtipo($clasificacion, $subtipo);
  
  $imagen_info = '';
  if (!empty($imagen_nombre)) {
    $imagen_info = '
    <div class="ticket-detail">
        <span class="ticket-label">Imagen Adjunta:</span>
        <span><strong>📎 ' . htmlspecialchars($imagen_nombre) . '</strong></span>
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
          }
          .container {
              max-width: 700px;
              margin: 20px auto;
              background-color: #ffffff;
              border-radius: 12px;
              overflow: hidden;
              box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          }
          .header {
              background: linear-gradient(135deg, ' . $priorityColor . ' 0%, ' . adjustBrightness($priorityColor, -20) . ' 100%);
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
              min-width: 160px;
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
          .action-required {
              background: #fff3cd;
              border: 2px solid #ffc107;
              border-radius: 8px;
              padding: 15px;
              margin: 20px 0;
              text-align: center;
          }
          .footer {
              background-color: #2c3e50;
              color: white;
              padding: 20px;
              text-align: center;
              font-size: 12px;
          }
          .btn {
              display: inline-block;
              padding: 10px 20px;
              background: ' . $priorityColor . ';
              color: white;
              text-decoration: none;
              border-radius: 5px;
              font-weight: bold;
              margin: 10px 5px;
          }
      </style>
  </head>
  <body>
      <div class="container">
          <div class="header">
              <h1>' . $urgencyIcon . ' NUEVO TICKET ASIGNADO - REQUIERE TU ATENCIÓN</h1>
              <p>' . $persona_asignada . ', se te ha asignado un nuevo ticket</p>
          </div>
          <div class="content">
              <div class="action-required">
                  <h3 style="margin-top: 0; color: #856404;">⚠️ ACCIÓN REQUERIDA</h3>
                  <p>Por favor, revisa este ticket y actualiza su estatus en el sistema.</p>
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
                      <span class="ticket-label">Estatus Actual:</span>
                      <span style="background: #ffc107; color: #856404; padding: 4px 10px; border-radius: 12px; font-weight: bold;">En proceso</span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Asignado a:</span>
                      <span><strong style="color: ' . $priorityColor . ';">' . $persona_asignada . ' (TÚ)</strong></span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Clasificación:</span>
                      <span><strong>' . $clasificacion . '</strong></span>
                  </div>
                  <div class="ticket-detail">
                      <span class="ticket-label">Asunto:</span>
                      <span><strong>' . $icono . ' ' . $subject . '</strong></span>
                  </div>
                  ' . $imagen_info . '
              </div>
              
              <div style="background: #e8f4fd; border-radius: 8px; padding: 15px; margin: 15px 0;">
                  <h3 style="margin-top: 0; color: #2c3e50;">👤 Información del Solicitante</h3>
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
              
              <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin: 15px 0;">
                  <h3 style="margin-top: 0; color: #2c3e50;">📋 Descripción del Problema</h3>
                  <p><strong>' . $description . '</strong></p>
                  ' . ($message ? '<p><strong>Detalles adicionales:</strong></p><p>' . $message . '</p>' : '') . '
              </div>
              
              <div style="text-align: center; margin: 25px 0;">
                  <p style="font-weight: bold; color: #2c3e50;">Acciones Recomendadas:</p>
                  <div>
                      <a href="#" class="btn">📋 Ver en Sistema</a>
                      <a href="#" class="btn" style="background: #28a745;">✅ Marcar como Resuelto</a>
                      <a href="#" class="btn" style="background: #6c757d;">📞 Contactar Usuario</a>
                  </div>
              </div>
          </div>
          <div class="footer">
              <p>Departamento de TI - BacroCorp | Sistema de Asignación Automática</p>
              <p>&copy; ' . date('Y') . ' BacroCorp - Todos los derechos reservados</p>
          </div>
      </div>
  </body>
  </html>';
}

function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    
    $color_parts = str_split($hex, 2);
    $return = '#';
    
    foreach ($color_parts as $color) {
        $color   = hexdec($color);
        $color   = max(0, min(255, $color + $steps));
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }
    
    return $return;
}

function getPriorityColor($priority) {
  if (strpos($priority, 'Alto') !== false || strpos($priority, 'URGENTE') !== false) {
    return '#dc3545';
  } elseif (strpos($priority, 'Medio') !== false || strpos($priority, 'MEDIA') !== false) {
    return '#ffc107';
  } elseif (strpos($priority, 'Bajo') !== false || strpos($priority, 'BAJA') !== false) {
    return '#28a745';
  } else {
    return '#6c757d';
  }
}

function getUrgencyIcon($priority) {
  if (strpos($priority, 'Alto') !== false || strpos($priority, 'URGENTE') !== false) {
    return '🚨🔥';
  } elseif (strpos($priority, 'Medio') !== false || strpos($priority, 'MEDIA') !== false) {
    return '⚠️📋';
  } elseif (strpos($priority, 'Bajo') !== false || strpos($priority, 'BAJA') !== false) {
    return 'ℹ️📥';
  } else {
    return '📝';
  }
}

function getIconoForSubtipo($tipo, $subtipo) {
  global $tipo_opciones;
  
  if (isset($tipo_opciones[$tipo][$subtipo]['icono'])) {
    return $tipo_opciones[$tipo][$subtipo]['icono'];
  }
  
  return '';
}

function showSuccessAlert($name, $email, $ticketId, $persona_asignada, $userEmailSent, $adminEmailSent, $responsableEmailSent, $imagen_nombre = '', $clasificacion = '', $subtipo = '') {
  $emailStatus = '';
  
  if ($userEmailSent && $adminEmailSent && $responsableEmailSent) {
    $emailStatus = '<p style="color: #28a745; font-weight: bold;">✓ Correos enviados al usuario, administrador y responsable</p>';
  } else {
    $emailStatus = '<p style="color: #ffc107; font-weight: bold;">';
    if ($userEmailSent) $emailStatus .= '✓ Usuario ';
    if ($adminEmailSent) $emailStatus .= '✓ Administrador ';
    if ($responsableEmailSent) $emailStatus .= '✓ Responsable ';
    $emailStatus .= '</p>';
  }
  
  $imagenInfo = '';
  if (!empty($imagen_nombre)) {
    $imagenInfo = '<p style="margin: 5px 0;"><strong>Imagen adjunta:</strong> 📎 ' . htmlspecialchars($imagen_nombre) . '</p>';
  }
  
  $icono = getIconoForSubtipo($clasificacion, $subtipo);
  $tipoInfo = '';
  if (!empty($clasificacion) && !empty($subtipo)) {
    $tipoInfo = '<p style="margin: 5px 0;"><strong>Clasificación:</strong> ' . ($clasificacion === 'SOLICITUDES' ? '📝 SOLICITUDES' : '⚠️ PROBLEMAS') . '</p>';
    $tipoInfo .= '<p style="margin: 5px 0;"><strong>Asunto:</strong> ' . $icono . ' ' . htmlspecialchars($subtipo) . '</p>';
  }
  
  echo '
  <script>
  Swal.fire({
      title: "✅ ¡Ticket Creado y Asignado!",
      html: `
          <div style="text-align: center; padding: 20px;">
              <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">🎉</div>
              <h2 style="color: #003366; margin-bottom: 15px;">Solicitud Registrada y Asignada</h2>
              
              <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;">
                  <p style="margin: 5px 0;"><strong>Nombre:</strong> ' . $name . '</p>
                  <p style="margin: 5px 0;"><strong>Correo:</strong> ' . $email . '</p>
                  ' . $tipoInfo . '
                  ' . $imagenInfo . '
                  <p style="margin: 5px 0;"><strong>Estatus:</strong> 
                      <span style="background: #ffc107; color: #856404; padding: 4px 10px; border-radius: 12px; font-weight: bold;">En proceso</span>
                  </p>
                  <p style="margin: 5px 0;"><strong>Persona Asignada (PA):</strong> 
                      <span style="background: #6c757d; color: white; padding: 4px 10px; border-radius: 12px; font-weight: bold;">' . $persona_asignada . '</span>
                  </p>
                  <p style="margin: 10px 0;"><strong>Ticket ID:</strong> 
                      <span style="background: #003366; color: white; padding: 8px 15px; border-radius: 20px; font-size: 16px; font-weight: bold;">' . $ticketId . '</span>
                  </p>
              </div>
              
              ' . $emailStatus . '
              
              <p style="color: #666; font-size: 14px; margin-top: 15px;">El ticket ha sido registrado en el sistema y asignado automáticamente.</p>
          </div>
      `,
      icon: "success",
      confirmButtonColor: "#003366",
      confirmButtonText: "Volver al Dashboard",
      allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      // window.location.href = "dashboard.php";
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