<?php
/**
 * @file FormServVehi.php
 * @brief Formulario de servicio y mantenimiento vehicular.
 *
 * @description
 * Formulario para reportar solicitudes de mantenimiento vehicular.
 * Genera automáticamente un ID de ticket único combinando un contador
 * secuencial con un código aleatorio.
 *
 * Características:
 * - Conexión directa a SQL Server
 * - Generación automática de ID de ticket
 * - Función de sanitización de inputs (test_input)
 * - Inserta en tabla TMANVEHI
 *
 * Algoritmo de generación de ID:
 * 1. Consulta COUNT(*) + 1 de TMANVEHI
 * 2. Genera uniqid() y extrae últimos 2 caracteres
 * 3. Añade segundos actuales
 * 4. Formato final: {contador}_{otp}{segundos}
 *
 * @module Módulo de Mantenimiento Vehicular
 * @access Público (sin autenticación)
 *
 * @dependencies
 * - PHP: sqlsrv extension, date functions
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\SQLEXPRESS
 * - Base de datos: Ticket
 * - Tabla: TMANVEHI (INSERT)
 *
 * @functions
 * - test_input($data): Sanitiza datos de entrada
 *   Aplica: trim, stripslashes, htmlspecialchars
 *
 * @security
 * - Sanitización de inputs con htmlspecialchars
 * - ADVERTENCIA: Sin autenticación de sesión
 * - die() expone errores de SQL
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.5
 * @since 2024
 */

// ----------------- CONEXIÓN Y PROCESAMIENTO DEL FORMULARIO -----------------
require_once __DIR__ . '/config.php';
$serverName = $DB_HOST;
$connectionInfo = array(
    "Database" => $DB_DATABASE,
    "UID" => $DB_USERNAME,
    "PWD" => $DB_PASSWORD,
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => true
);
$conn = sqlsrv_connect($serverName, $connectionInfo);
if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Generar ID automático
$sqlIDT = "SELECT COUNT(*) + 1 AS Total FROM TMANVEHI";
$stmtIDT = sqlsrv_query($conn, $sqlIDT);
$row = sqlsrv_fetch_array($stmtIDT, SQLSRV_FETCH_ASSOC);
$newID = $row['Total'] ?? 1;

$number = uniqid();
$varray = str_split($number);
$otp = implode('', array_slice($varray, -2));
$ticketID = $newID . '_' . $otp . date("s");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $placas       = test_input($_POST["placas"]);
    $problema     = test_input($_POST["incidencia"]);
    $prioridad    = test_input($_POST["prioridad"]);
    $fecha        = test_input($_POST["fecha"]);
    $nombre       = test_input($_POST["nombre"]);
    $departamento = test_input($_POST["departamento"]);
    $comentarios  = test_input($_POST["mensaje"]);
    $id           = test_input($_POST["id"]);

    // Subida de imágenes
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFiles = [];

    if (!empty($_FILES['evidencias']['name'][0])) {
        foreach ($_FILES['evidencias']['tmp_name'] as $index => $tmpName) {
            $fileName = basename($_FILES['evidencias']['name'][$index]);
            $sanitized = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
            $targetPath = $uploadDir . time() . '_' . $sanitized;

            $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png'];

            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $targetPath;
                }
            }
        }
    }

    $evidenciasDB = implode(',', $uploadedFiles);

    $sql = "INSERT INTO TMANVEHI 
        (PLACAS, PROBLEMA, PRIORIDAD, FECHA, NOMBRE, ASIGNADO, IDT, COMENTARIOS, Departamento, EVIDENCIAS) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $params = [$placas, $problema, $prioridad, $fecha, $nombre, '', $id, $comentarios, $departamento, $evidenciasDB];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo "<script>
        alert('Ticket guardado correctamente.');
        window.location.href = 'index.php';
    </script>";
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