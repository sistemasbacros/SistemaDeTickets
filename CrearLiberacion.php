<?php
/**
 * @file CrearLiberacion.php
 * @brief Formulario para iniciar una solicitud de Liberación de Responsabilidades.
 *
 * RH captura los datos del empleado que causa baja y hace POST al API.
 * El API genera el folio y envía email al primer firmante.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

// ── Resolver URL del API (igual que FormNuevoUsuario.php) ─────────────────────
function getApiUrl(): string {
    $url = getenv('PDF_API_URL') ?: '';
    if (!$url) {
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                if (trim($k) === 'PDF_API_URL') { $url = trim($v); break; }
            }
        }
    }
    return rtrim($url ?: 'http://localhost:3000', '/');
}

$resultado  = null;
$folio      = null;
$errorMsg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiUrl = getApiUrl();

    $payload = json_encode([
        'emp_nombre'           => trim($_POST['nombre']           ?? ''),
        'emp_apellido_paterno' => trim($_POST['apellido_paterno'] ?? ''),
        'emp_apellido_materno' => trim($_POST['apellido_materno'] ?? '') ?: null,
        'emp_id_empleado'      => trim($_POST['id_empleado']      ?? ''),
        'emp_puesto'           => trim($_POST['puesto']           ?? ''),
        'emp_departamento'     => trim($_POST['departamento']     ?? ''),
        'emp_correo'           => trim($_POST['correo']           ?? '') ?: null,
        'emp_fecha_ingreso'    => $_POST['fecha_ingreso']         ?? '',
        'emp_fecha_baja'       => $_POST['fecha_baja']            ?? '',
        'emp_motivo_baja'      => $_POST['motivo_baja']           ?? '',
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($apiUrl . '/api/TicketBacros/liberacion');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp    = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        $errorMsg = 'No se pudo conectar al servidor: ' . htmlspecialchars($curlErr);
    } else {
        $resultado = json_decode($resp, true);
        if ($resultado['success'] ?? false) {
            $folio = $resultado['folio'] ?? '';
        } else {
            $errorMsg = htmlspecialchars($resultado['message'] ?? 'Error desconocido del servidor.');
        }
    }
}

// Motivos válidos (exactamente como los valida el API — con tildes)
$motivos = [
    'Renuncia voluntaria',
    'Terminación',
    'Fin de contrato',
    'Jubilación',
    'Abandono',
    'Fallecimiento',
    'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Iniciar Liberación de Responsabilidades</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

<style>
* { box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f0f0f0, #dcdcdc);
    color: #111;
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    position: relative;
}

#homeButton {
    position: fixed;
    top: 20px;
    left: 20px;
    width: 56px;
    height: 56px;
    background-color: #0033cc;
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0,0,0,.2);
    transition: all .3s ease;
    z-index: 1000;
}
#homeButton:hover { background-color: #0022aa; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,.3); }

img.logo {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 130px;
    height: 130px;
    object-fit: contain;
    filter: drop-shadow(0 0 3px #999);
    animation: float 4s ease-in-out infinite;
}
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

h2 {
    font-weight: 700;
    font-size: 2rem;
    margin: 10px 0 24px;
    color: #333;
    text-align: center;
    animation: pulse 4s ease-in-out infinite;
}
@keyframes pulse { 0%,100% { text-shadow: 0 0 8px #aaa; color: #222; } 50% { text-shadow: 0 0 20px #888; color: #444; } }

.container {
    background: #fff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 12px 25px rgba(0,0,0,.1);
    max-width: 700px;
    width: 100%;
}

.section-title {
    font-size: .85rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #0033cc;
    margin: 20px 0 10px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e0e7ff;
}

form { display: grid; gap: 18px; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .container { padding: 20px; } h2 { font-size: 1.5rem; } }

.form-group { display: flex; flex-direction: column; }

label {
    font-weight: 600;
    font-size: .95rem;
    margin-bottom: 5px;
    color: #444;
}
label .req { color: #dc3545; margin-left: 2px; }

input, select, textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #bbb;
    font-size: .97rem;
    background: #f9f9f9;
    color: #111;
    font-family: 'Inter', sans-serif;
    transition: border-color .2s, background .2s;
}
input:focus, select:focus, textarea:focus {
    background: #eaeaea;
    border-color: #0033cc;
    outline: none;
}

button[type="submit"] {
    background: #0033cc;
    color: #fff;
    font-size: 1.05rem;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background .3s, transform .2s;
    margin-top: 8px;
}
button[type="submit"]:hover { background: #0022aa; transform: translateY(-2px); }

.info-box {
    background: #e8f4fd;
    border-left: 4px solid #0066cc;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: .88rem;
    color: #1a4a7a;
    margin-bottom: 8px;
}
</style>
</head>
<body>

<button id="homeButton" title="Inicio"><i class="fas fa-home"></i></button>
<img src="Logo2.png" alt="Logo BacroCorp" class="logo" />

<h2><i class="fas fa-file-signature"></i> Liberación de Responsabilidades</h2>

<div class="container">

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        Complete los datos del empleado que causa baja. El sistema enviará un correo al primer firmante de forma automática.
    </div>

    <form method="POST" id="formLib">

        <div class="section-title"><i class="fas fa-user"></i> Datos del Empleado</div>

        <div class="form-row">
            <div class="form-group">
                <label>Nombre(s) <span class="req">*</span></label>
                <input type="text" name="nombre" placeholder="Ej. Juan" required
                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Apellido Paterno <span class="req">*</span></label>
                <input type="text" name="apellido_paterno" placeholder="Ej. Pérez" required
                       value="<?= htmlspecialchars($_POST['apellido_paterno'] ?? '') ?>" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Apellido Materno</label>
                <input type="text" name="apellido_materno" placeholder="Opcional"
                       value="<?= htmlspecialchars($_POST['apellido_materno'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>ID Empleado <span class="req">*</span></label>
                <input type="text" name="id_empleado" placeholder="Ej. EMP-001" required
                       value="<?= htmlspecialchars($_POST['id_empleado'] ?? '') ?>" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Puesto <span class="req">*</span></label>
                <input type="text" name="puesto" placeholder="Ej. Analista de TI" required
                       value="<?= htmlspecialchars($_POST['puesto'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Departamento <span class="req">*</span></label>
                <input type="text" name="departamento" placeholder="Ej. Sistemas" required
                       value="<?= htmlspecialchars($_POST['departamento'] ?? '') ?>" />
            </div>
        </div>

        <div class="form-group">
            <label>Correo del Empleado</label>
            <input type="email" name="correo" placeholder="correo@bacrocorp.com (opcional)"
                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" />
        </div>

        <div class="section-title"><i class="fas fa-calendar-alt"></i> Datos de la Baja</div>

        <div class="form-row">
            <div class="form-group">
                <label>Fecha de Ingreso <span class="req">*</span></label>
                <input type="date" name="fecha_ingreso" required
                       value="<?= htmlspecialchars($_POST['fecha_ingreso'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label>Fecha de Baja <span class="req">*</span></label>
                <input type="date" name="fecha_baja" required
                       value="<?= htmlspecialchars($_POST['fecha_baja'] ?? '') ?>" />
            </div>
        </div>

        <div class="form-group">
            <label>Motivo de Baja <span class="req">*</span></label>
            <select name="motivo_baja" required>
                <?php foreach ($motivos as $m): ?>
                    <option value="<?= $m ?>" <?= (($_POST['motivo_baja'] ?? '') === $m) ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" id="btnEnviar">
            <i class="fas fa-paper-plane"></i> Iniciar Proceso de Liberación
        </button>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = 'M/website-menu-05/index.html';
});

<?php if ($folio): ?>
Swal.fire({
    title: '¡Liberación Iniciada!',
    html: `
        <div style="text-align:center;padding:10px">
            <div style="font-size:56px;margin-bottom:14px">📋</div>
            <p>Se generó el folio:</p>
            <div style="background:#003366;color:#fff;padding:10px 20px;border-radius:20px;font-size:1.3rem;font-weight:bold;display:inline-block;margin:10px 0">
                <?= htmlspecialchars($folio) ?>
            </div>
            <p style="color:#555;font-size:.9rem;margin-top:12px">
                Se ha enviado un correo al primer firmante.<br>
                Puede consultar el avance en la página de estado.
            </p>
        </div>`,
    icon: 'success',
    confirmButtonColor: '#003366',
    confirmButtonText: 'Ver Estado',
    showCancelButton: true,
    cancelButtonText: 'Crear Otra',
}).then(r => {
    if (r.isConfirmed) {
        window.location.href = 'EstadoLiberacion.php?folio=<?= urlencode($folio) ?>';
    } else {
        document.getElementById('formLib').reset();
    }
});
<?php elseif ($errorMsg): ?>
Swal.fire({
    title: 'Error al crear solicitud',
    text: '<?= addslashes($errorMsg) ?>',
    icon: 'error',
    confirmButtonColor: '#003366',
});
<?php endif; ?>

// Prevenir doble envío
document.getElementById('formLib').addEventListener('submit', function() {
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
});
</script>
</body>
</html>
