<?php
/**
 * restablecer.php — Fija la nueva contraseña con el token del correo.
 *
 * POST /api/identidad/auth/restablecer (público, token de UN SOLO USO que el
 * backend valida y expira). Este PHP no toca la BD ni sabe de hashes: la
 * credencial vive en IDENTIDAD (Argon2) y sirve para todo el ecosistema.
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

// El token llega por querystring desde el enlace del correo.
$token   = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$ok      = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Sesión expirada. Abre de nuevo el enlace del correo.';
    } else {
        $nueva   = (string) ($_POST['nueva'] ?? '');
        $repetir = (string) ($_POST['repetir'] ?? '');

        if ($token === '') {
            $error = 'Falta el token: abre el enlace que te llegó por correo.';
        } elseif (strlen($nueva) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($nueva !== $repetir) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $r = api_call('POST', '/api/identidad/auth/restablecer', [
                'token'            => $token,
                'nueva_contrasena' => $nueva,
            ]);
            if ($r['error']) {
                $error = 'No se pudo conectar al servidor de autenticación. Intenta más tarde.';
            } elseif ($r['ok']) {
                $ok = true;
            } else {
                $msg = $r['json']['message'] ?? $r['json']['error'] ?? '';
                $error = $msg !== ''
                    ? $msg
                    : 'El enlace no es válido o ya venció. Solicita uno nuevo.';
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
<title>Nueva contraseña - BACROS</title>
<style>
  *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,sans-serif;
    background:linear-gradient(135deg,#1e3a8a,#0f172a);min-height:100vh;display:flex;
    align-items:center;justify-content:center;padding:16px}
  .card{background:#fff;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.25);
    padding:32px;width:100%;max-width:420px}
  h1{margin:0 0 6px;font-size:20px;color:#0f172a} p.sub{margin:0 0 20px;color:#64748b;font-size:14px}
  label{display:block;font-size:13px;font-weight:600;color:#334155;margin:12px 0 6px}
  input{width:100%;padding:11px 13px;border:1px solid #cbd5e1;border-radius:9px;font-size:15px}
  input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  button{width:100%;margin-top:18px;padding:12px;border:0;border-radius:9px;background:#2563eb;
    color:#fff;font-size:15px;font-weight:600;cursor:pointer} button:hover{background:#1d4ed8}
  .msg{margin-top:16px;padding:12px;border-radius:9px;font-size:14px;line-height:1.45}
  .ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
  .err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .hint{font-size:12px;color:#64748b;margin-top:6px}
  .back{display:block;margin-top:18px;text-align:center;color:#2563eb;text-decoration:none;font-size:14px}
</style>
</head>
<body>
  <div class="card">
    <h1>Definir nueva contraseña</h1>
    <p class="sub">Será tu contraseña para todo el sistema BACROS.</p>

    <?php if ($ok): ?>
      <div class="msg ok">Contraseña actualizada. Ya puedes iniciar sesión con ella.</div>
      <a class="back" href="Loginti.php">Ir al inicio de sesión →</a>
    <?php else: ?>
      <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($token === ''): ?>
        <div class="msg err">Este enlace no trae token. Solicita uno nuevo desde “Olvidé mi contraseña”.</div>
        <a class="back" href="recuperar.php">Solicitar enlace →</a>
      <?php else: ?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label for="nueva">Nueva contraseña</label>
        <input type="password" id="nueva" name="nueva" required minlength="8" autofocus>
        <div class="hint">Mínimo 8 caracteres. Evita datos obvios (nombre, fecha de nacimiento).</div>
        <label for="repetir">Repetir contraseña</label>
        <input type="password" id="repetir" name="repetir" required minlength="8">
        <button type="submit">Guardar contraseña</button>
      </form>
      <a class="back" href="Loginti.php">← Volver al inicio de sesión</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
<?php ob_end_flush(); ?>
