<?php
/**
 * @file FormServVehi.php
 * @brief Formulario de servicio y mantenimiento vehicular.
 *
 * @description
 * Formulario para reportar solicitudes de mantenimiento vehicular.
 * El ID de ticket es generado en el frontend con JavaScript (mismo algoritmo
 * que el original: contador + código aleatorio + segundos).
 *
 * Migrado a Rust API:
 * - POST /api/TicketBacros/vehiculos  → inserta en TMANVEHI
 *   (el conteo para generar el ID se obtiene también de la API)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';
$apiUrl = rtrim(getenv('PDF_API_URL') ?: 'http://host.docker.internal:3000', '/');

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Obtener conteo actual de registros para generar el ID de ticket
// (mismo algoritmo que el original: COUNT(*) + 1)
$totalRecords = 1;
$ch = curl_init($apiUrl . '/api/TicketBacros/vehiculos');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode === 200 && $resp) {
    $data = json_decode($resp, true);
    if (is_array($data)) {
        $totalRecords = count($data) + 1;
    }
}

// Generar ID con el mismo algoritmo que el PHP original
$number   = uniqid();
$varray   = str_split($number);
$otp      = implode('', array_slice($varray, -2));
$ticketID = $totalRecords . '_' . $otp . date("s");

// Procesar formulario POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $placas       = test_input($_POST["placas"]       ?? '');
    $problema     = test_input($_POST["incidencia"]   ?? '');
    $prioridad    = test_input($_POST["prioridad"]    ?? '');
    $fecha        = test_input($_POST["fecha"]        ?? '');
    $nombre       = test_input($_POST["nombre"]       ?? '');
    $departamento = test_input($_POST["departamento"] ?? '');
    $comentarios  = test_input($_POST["mensaje"]      ?? '');
    $id           = test_input($_POST["id"]           ?? $ticketID);

    // Validación server-side de longitudes (defensa contra payloads abusivos)
    $lenChecks = [
        'placas'       => [$placas,       20],
        'incidencia'   => [$problema,     200],
        'nombre'       => [$nombre,       100],
        'departamento' => [$departamento, 100],
        'mensaje'      => [$comentarios,  3000],
    ];
    foreach ($lenChecks as $field => [$val, $max]) {
        if (strlen($val) > $max) {
            $ipLog   = $_SERVER['REMOTE_ADDR'] ?? '-';
            $selfLog = basename(__FILE__);
            error_log("UPLOAD reject ip={$ipLog} script={$selfLog} reason=field_too_long field={$field} len=" . strlen($val));
            echo "<script>
                alert('Datos del formulario exceden los límites permitidos.');
                window.history.back();
            </script>";
            exit();
        }
    }

    // Subida de imágenes (se mantiene local, igual que antes)
    $uploadDir = __DIR__ . '/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $base = realpath($uploadDir);

    $self = basename(__FILE__);
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';

    $uploadedFiles = [];
    if (!empty($_FILES['evidencias']['name'][0]) && $base !== false) {
        $maxSize = 5 * 1024 * 1024; // 5 MB
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        foreach ($_FILES['evidencias']['tmp_name'] as $index => $tmpName) {
            $err  = $_FILES['evidencias']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            $size = (int) ($_FILES['evidencias']['size'][$index] ?? 0);

            // Ignorar silenciosamente cuando el usuario no adjuntó archivo
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                error_log("UPLOAD reject ip={$ip} script={$self} reason=upload_error code={$err}");
                continue;
            }
            if ($size <= 0 || $size > $maxSize) {
                error_log("UPLOAD reject ip={$ip} script={$self} reason=bad_size size={$size}");
                continue;
            }

            // Validar contenido real (NO confiar en $_FILES[...]['type'])
            $info = @getimagesize($tmpName);
            if ($info === false || empty($info['mime'])) {
                error_log("UPLOAD reject ip={$ip} script={$self} reason=not_an_image");
                continue;
            }
            if (!in_array($info['mime'], $allowedMimes, true)) {
                error_log("UPLOAD reject ip={$ip} script={$self} reason=bad_mime mime={$info['mime']}");
                continue;
            }

            $ext = match ($info['mime']) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            };

            $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = $base . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($tmpName, $destPath)) {
                error_log("UPLOAD reject ip={$ip} script={$self} reason=move_failed");
                continue;
            }

            // Defensa anti path-traversal: confirmar que el destino quedó dentro del directorio
            $resolved = realpath($destPath);
            if ($resolved === false || strpos($resolved, $base . DIRECTORY_SEPARATOR) !== 0) {
                @unlink($destPath);
                error_log("UPLOAD reject ip={$ip} script={$self} reason=path_escape");
                continue;
            }

            @chmod($destPath, 0644);
            error_log("UPLOAD ok ip={$ip} script={$self} file={$safeName} size={$size} mime={$info['mime']}");

            // Conservar el contrato del API: rutas relativas separadas por coma
            $uploadedFiles[] = 'uploads/' . $safeName;
        }
    }
    $evidenciasDB = implode(',', $uploadedFiles);

    // Enviar a la API Rust
    $payload = json_encode([
        'placas'       => $placas,
        'problema'     => $problema,
        'prioridad'    => $prioridad,
        'fecha'        => $fecha,
        'nombre'       => $nombre,
        'asignado'     => '',
        'idt'          => $id,
        'comentarios'  => $comentarios,
        'departamento' => $departamento,
        'evidencias'   => $evidenciasDB,
    ]);

    $ch = curl_init($apiUrl . '/api/TicketBacros/vehiculos');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || ($httpCode !== 200 && $httpCode !== 201)) {
        $errorMsg = $curlErr ?: "Error HTTP $httpCode";
        error_log("API error al crear ticket vehicular: $errorMsg — $resp");
        echo "<script>
            alert('Error al guardar el ticket. Por favor intenta de nuevo.');
            window.history.back();
        </script>";
    } else {
        echo "<script>
            alert('Ticket guardado correctamente.');
            window.location.href = 'index.php';
        </script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Ticket Vehicular</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background-color: #f4f6f9;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 700px;
      background: white;
      padding: 2rem;
      border-radius: 10px;
      margin-top: 2rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    header {
      background-color: #2D6DA6;
      color: white;
      padding: 1rem;
      border-radius: 0.5rem 0.5rem 0 0;
      text-align: center;
      margin-bottom: 1rem;
    }
    button[type="submit"] {
      background-color: #2D6DA6;
      border: none;
    }
    button[type="submit"]:hover {
      background-color: #1a4e7a;
    }
  </style>
</head>
<body>

<div class="container">
  <header>
    <h2>Levantar Ticket Vehicular</h2>
  </header>

  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="id" class="form-label">ID</label>
      <input type="text" class="form-control" name="id" id="id" value="<?php echo htmlspecialchars($ticketID); ?>" readonly />
    </div>

    <div class="mb-3">
      <label for="placas" class="form-label">Placas</label>
      <input type="text" class="form-control" name="placas" id="placas" required />
    </div>

    <div class="mb-3">
      <label for="incidencia" class="form-label">Incidencia</label>
      <select class="form-select" name="incidencia" required>
        <option value="" selected disabled>Seleccione...</option>
        <option value="Preventivo">Preventivo</option>
        <option value="Correctivo">Correctivo</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="prioridad" class="form-label">Prioridad</label>
      <select class="form-select" name="prioridad" required>
        <option value="" selected disabled>Seleccione...</option>
        <option value="Alta">Alta</option>
        <option value="Media">Media</option>
        <option value="Baja">Baja</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="fecha" class="form-label">Fecha</label>
      <input type="date" class="form-control" name="fecha" required />
    </div>

    <div class="mb-3">
      <label for="nombre" class="form-label">Nombre</label>
      <input type="text" class="form-control" name="nombre" required />
    </div>

    <div class="mb-3">
      <label for="departamento" class="form-label">Departamento</label>
      <select class="form-select" name="departamento" required>
        <option value="" selected disabled>Seleccione...</option>
        <option>Operaciones</option>
        <option>Finanzas</option>
        <option>Administración</option>
        <option>Licitaciones</option>
      </select>
    </div>

    <div class="mb-3">
      <label for="mensaje" class="form-label">Mensaje</label>
      <textarea class="form-control" name="mensaje" rows="4"></textarea>
    </div>

    <div class="mb-3">
      <label for="evidencias" class="form-label">Evidencias (fotos)</label>
      <input type="file" name="evidencias[]" class="form-control" accept="image/*" multiple />
    </div>

    <button type="submit" class="btn btn-primary w-100">Enviar Ticket</button>
  </form>
</div>

</body>
</html>
