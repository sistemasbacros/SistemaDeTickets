<?php
/**
 * cambiar_contrasena.php — Cambio de contraseña con la sesión activa.
 *
 * POST /api/identidad/auth/cambiar-contrasena (JWT), verificando la actual.
 * Se usa en dos casos:
 *   1. Voluntario (el usuario quiere cambiarla).
 *   2. OBLIGATORIO (?obligatorio=1) cuando el login unificado devuelve
 *      `requiere_cambio` — contraseña temporal emitida por RRHH.
 * La credencial vive en IDENTIDAD y sirve para todo el ecosistema BACROS.
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

// Requiere sesión con JWT (no se puede cambiar sin estar autenticado).
if (empty($_SESSION['logged_in']) || empty($_SESSION['api_jwt'])) {
    header('Location: Loginti.php');
    ob_end_flush();
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$obligatorio = isset($_GET['obligatorio']) || !empty($_SESSION['requiere_cambio']);
$ok    = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Sesión expirada. Vuelve a intentarlo.';
    } else {
        $actual  = (string) ($_POST['actual'] ?? '');
        $nueva   = (string) ($_POST['nueva'] ?? '');
        $repetir = (string) ($_POST['repetir'] ?? '');

        if ($actual === '' || $nueva === '') {
            $error = 'Completa todos los campos.';
        } elseif (strlen($nueva) < 8) {
            $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($nueva !== $repetir) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } elseif ($nueva === $actual) {
            $error = 'La nueva contraseña debe ser distinta de la actual.';
        } else {
            $r = api_call('POST', '/api/identidad/auth/cambiar-contrasena', [
                'actual' => $actual,
                'nueva'  => $nueva,
            ]);
            if ($r['error']) {
                $error = 'No se pudo conectar al servidor de autenticación. Intenta más tarde.';
            } elseif ($r['ok']) {
                $ok = true;
                $_SESSION['requiere_cambio'] = false;
            } elseif ($r['http'] === 401 || $r['http'] === 403) {
                $error = 'La contraseña actual no es correcta.';
            } else {
                $msg   = $r['json']['message'] ?? $r['json']['error'] ?? '';
                $error = $msg !== '' ? $msg : 'No se pudo cambiar la contraseña. Intenta de nuevo.';
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
<title>Cambiar contraseña - BACROS</title>
<style>
  *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,sans-serif;
    background:linear-gradient(135deg,#1e3a8a,#0f172a);min-height:100vh;display:flex;
    align-items:center;justify-content:center;padding:16px}
  .card{background:#fff;border-radius:16px;box-shadow:0 20px 40px rgba(0,0,0,.25);
    padding:32px;width:100%;max-width:440px}
  h1{margin:0 0 6px;font-size:20px;color:#0f172a} p.sub{margin:0 0 18px;color:#64748b;font-size:14px}
  label{display:block;font-size:13px;font-weight:600;color:#334155;margin:12px 0 6px}
  input{width:100%;padding:11px 13px;border:1px solid #cbd5e1;border-radius:9px;font-size:15px}
  input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  button{width:100%;margin-top:18px;padding:12px;border:0;border-radius:9px;background:#2563eb;
    color:#fff;font-size:15px;font-weight:600;cursor:pointer} button:hover{background:#1d4ed8}
  .msg{margin-top:16px;padding:12px;border-radius:9px;font-size:14px;line-height:1.45}
  .ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
  .err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
  .hint{font-size:12px;color:#64748b;margin-top:6px}
  .back{display:block;margin-top:18px;text-align:center;color:#2563eb;text-decoration:none;font-size:14px}
</style>
</head>
<body>
  <div class="card">
    <h1>Cambiar contraseña</h1>
    <p class="sub">Aplica a todo el sistema BACROS (tickets, comedor, RRHH y demás módulos).</p>

    <?php if ($obligatorio && !$ok): ?>
      <div class="msg warn">Tu contraseña fue restablecida por Recursos Humanos.
        Debes definir una nueva para continuar.</div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="msg ok">Contraseña actualizada correctamente.</div>
      <a class="back" href="IniSoport.php">Continuar al sistema →</a>
    <?php else: ?>
      <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="actual">Contraseña actual</label>
        <input type="password" id="actual" name="actual" required autofocus>
        <label for="nueva">Nueva contraseña</label>
        <input type="password" id="nueva" name="nueva" required minlength="8">
        <div class="hint">Mínimo 8 caracteres. Evita datos obvios (nombre, fecha de nacimiento).</div>
        <label for="repetir">Repetir nueva contraseña</label>
        <input type="password" id="repetir" name="repetir" required minlength="8">
        <button type="submit">Guardar contraseña</button>
      </form>
      <?php if (!$obligatorio): ?>
        <a class="back" href="IniSoport.php">← Volver al sistema</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
<?php ob_end_flush(); ?>
