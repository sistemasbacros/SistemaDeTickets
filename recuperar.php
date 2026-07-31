<?php
/**
 * recuperar.php — Solicitud de recuperación de contraseña.
 *
 * Delega TODO al login unificado: POST /api/identidad/auth/recuperar.
 * El backend decide (nunca este PHP):
 *   · Con correo registrado → envía enlace con token de un solo uso (30 min).
 *   · Sin correo → crea solicitud y avisa al jefe directo (organigrama) y a RRHH.
 * La respuesta SIEMPRE es genérica (anti-enumeración de usuarios): esta página
 * muestra el mismo mensaje exista o no la cuenta.
 */
declare(strict_types=1);

if (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/api_client.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Sesión expirada. Vuelve a intentarlo.';
    } else {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        if ($usuario === '') {
            $error = 'Escribe tu usuario o tu correo.';
        } else {
            $r = api_call('POST', '/api/identidad/auth/recuperar', ['usuario' => $usuario]);
            if ($r['error']) {
                $error = 'No se pudo conectar al servidor de autenticación. Intenta más tarde.';
            } else {
                // 200 genérico por diseño: no revelamos si la cuenta existe.
                $mensaje = 'Si la cuenta existe, enviamos las instrucciones por correo. '
                         . 'Si no tienes correo registrado, avisamos a tu jefe y a Recursos Humanos '
                         . 'para que te entreguen una contraseña temporal.';
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar contraseña - BACROS</title>
<style>
  *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,sans-serif;
    background:linear-gradient(135deg,#1e3a8a,#0f172a);min-height:100vh;display:flex;
    align-items:center;justify-content:center;padding:16px}
  .card{background:#fff;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.25);
    padding:32px;width:100%;max-width:420px}
  h1{margin:0 0 6px;font-size:20px;color:#0f172a} p.sub{margin:0 0 20px;color:#64748b;font-size:14px}
  label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px}
  input{width:100%;padding:11px 13px;border:1px solid #cbd5e1;border-radius:9px;font-size:15px}
  input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  button{width:100%;margin-top:16px;padding:12px;border:0;border-radius:9px;background:#2563eb;
    color:#fff;font-size:15px;font-weight:600;cursor:pointer} button:hover{background:#1d4ed8}
  .msg{margin-top:16px;padding:12px;border-radius:9px;font-size:14px;line-height:1.45}
  .ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
  .err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .back{display:block;margin-top:18px;text-align:center;color:#2563eb;text-decoration:none;font-size:14px}
</style>
</head>
<body>
  <div class="card">
    <h1>Recuperar contraseña</h1>
    <p class="sub">Usa las mismas credenciales de todo el sistema BACROS.</p>

    <?php if ($mensaje): ?><div class="msg ok"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$mensaje): ?>
    <form method="POST" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <label for="usuario">Usuario o correo</label>
      <input type="text" id="usuario" name="usuario" required autofocus
             placeholder="nombre.apellido o correo@bacrocorp.com">
      <button type="submit">Enviar instrucciones</button>
    </form>
    <?php endif; ?>

    <a class="back" href="Loginti.php">← Volver al inicio de sesión</a>
  </div>
</body>
</html>
<?php ob_end_flush(); ?>
