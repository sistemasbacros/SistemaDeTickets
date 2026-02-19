<?php
/**
 * config.php — Configuración central de credenciales y parámetros del sistema.
 *
 * Todas las credenciales se leen desde variables de entorno inyectadas por Docker Compose.
 * Nunca hardcodear valores en este archivo; usar el archivo .env en el servidor.
 */

// ─── Base de datos: Ticket (módulo TI / Servicios Generales) ─────────────────
$DB_HOST     = getenv('DB_HOST');
$DB_PORT     = getenv('DB_PORT')     ?: '1433';
$DB_DATABASE = getenv('DB_DATABASE');
$DB_USERNAME = getenv('DB_USERNAME');
$DB_PASSWORD = getenv('DB_PASSWORD');

// ─── Base de datos: Comedor (autenticación de empleados) ──────────────────────
$DB_DATABASE_COMEDOR = getenv('DB_DATABASE_COMEDOR');

// ─── Base de datos: Comercial (contactos / lista LBS) ────────────────────────
$DB_HOST_COMERCIAL     = getenv('DB_HOST_COMERCIAL');
$DB_DATABASE_COMERCIAL = getenv('DB_DATABASE_COMERCIAL');
$DB_USERNAME_COMERCIAL = getenv('DB_USERNAME_COMERCIAL');
$DB_PASSWORD_COMERCIAL = getenv('DB_PASSWORD_COMERCIAL');

// ─── Base de datos: Operaciones (contratos / licitaciones) ───────────────────
$DB_HOST_OPERACIONES     = getenv('DB_HOST_OPERACIONES');
$DB_DATABASE_OPERACIONES = getenv('DB_DATABASE_OPERACIONES');
$DB_USERNAME_OPERACIONES = getenv('DB_USERNAME_OPERACIONES');
$DB_PASSWORD_OPERACIONES = getenv('DB_PASSWORD_OPERACIONES');

// ─── SMTP ─────────────────────────────────────────────────────────────────────
$SMTP_HOST     = getenv('SMTP_HOST');
$SMTP_PORT     = (int)(getenv('SMTP_PORT') ?: 587);
$SMTP_USERNAME = getenv('SMTP_USERNAME');
$SMTP_PASSWORD = getenv('SMTP_PASSWORD');

// ─── Correo administrador ─────────────────────────────────────────────────────
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: $SMTP_USERNAME);
}
if (!defined('ADMIN_NAME')) {
    define('ADMIN_NAME', getenv('ADMIN_NAME') ?: 'Administrador TI BacroCorp');
}

/**
 * Configura el transporte SMTP en una instancia de PHPMailer.
 * Usar dentro de funciones de envío de correo para evitar problemas de scope.
 */
function configurarSMTP(\PHPMailer\PHPMailer\PHPMailer $mail): void
{
    $mail->isSMTP();
    $mail->Host      = getenv('SMTP_HOST');
    $mail->SMTPAuth  = true;
    $mail->Username  = getenv('SMTP_USERNAME');
    $mail->Password  = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port      = (int)(getenv('SMTP_PORT') ?: 587);
    $mail->CharSet   = 'UTF-8';
}
