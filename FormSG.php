<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SERVICIOS GENERALES</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />

  <style>
    * {
      box-sizing: border-box;
    }

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
      position: relative; /* Necesario para que el logo se posicione de manera absoluta */
    }

    .container {
      background: #ffffff;
      padding: 30px 40px;
      border-radius: 15px;
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
      max-width: 650px;
      width: 100%;
    }

    h2 {
      font-weight: 700;
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: #333;
      text-align: center;
      animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% {
        text-shadow: 0 0 8px #aaa;
        color: #222;
      }
      50% {
        text-shadow: 0 0 20px #888;
        color: #444;
      }
    }

    /* Logo a la parte superior derecha */
    img.logo {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 150px;
      height: 150px;
      object-fit: contain;
      filter: drop-shadow(0 0 3px #999);
      animation: float 4s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }

    form {
      display: grid;
      gap: 20px;
    }

    label {
      font-weight: 600;
      font-size: 1.05rem;
      margin-bottom: 6px;
      color: #444;
    }

    input[type="text"],
    select,
    textarea {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #bbb;
      font-size: 1rem;
      background: #f9f9f9;
      color: #111;
      transition: all 0.2s ease-in-out;
    }

    input[type="text"]:focus,
    select:focus,
    textarea:focus {
      background: #eaeaea;
      border-color: #888;
      outline: none;
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    button[type="submit"] {
      background: #444;
      color: white;
      font-size: 1.1rem;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    button[type="submit"]:hover {
      background: #222;
      transform: translateY(-2px);
    }

    @media (max-width: 640px) {
      .container {
        padding: 25px 20px;
      }

      h2 {
        font-size: 2rem;
      }
    }
	
	

/* Botón flotante - esquina superior izquierda */
#homeButton {
  position: fixed;
  top: 20px;
  left: 20px;
  width: 56px;
  height: 56px;
  background-color: #0033cc; /* azul fuerte */
  color: #ffffff; /* blanco */
  border: none;
  border-radius: 50%;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
  z-index: 1000;
}

/* Hover animación */
#homeButton:hover {
  background-color: #0022aa;
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
}
  </style>
</head>
<body>
 <button id="homeButton" title="Inicio">🏠</button>
  <h2>SERVICIOS GENERALES</h2>
  <img src="Logo2.png" alt="Logo" class="logo" />
  

  <div class="container">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      <div class="form-group">
        <label for="Nombre">Nombre</label>
	     <select id="Nombre" name="Nombre" required>
        </select>
      </div>

      <div class="form-group">
        <label for="prio">Prioridad</label>
        <select id="prio" name="prio" required>
          <option value="Bajo">Bajo</option>
          <option value="Medio">Medio</option>
          <option value="Alto">Alto</option>
        </select>
      </div>

      <div class="form-group">
        <label for="empre">Ubicación</label>
	     <select id="empre" name="empre" required>
          <option value="PRIMERO">PRIMER PISO</option>
          <option value="SEGUNDO">SEGUNDO PISO</option>
		  <option value="TERCER">TERCER PISO</option>
          <option value="CASA">CASA</option>
		  <option value="PATIO">PATIO</option>
		  <option value="VIGILANCIA">VIGILANCIA</option>
        </select>	
		
      </div>

      <div class="form-group">
        <label for="Asunto">Tipo</label>
        <select id="Asunto" name="Asunto" required>
          <option value="MANTENIMIENTO_GENERAL">MANTENIMIENTO EDIFICIO</option>
		   <option value="INTENDENDCIA">INTENDENDCIA</option>
          <option value="OTROS">OTROS</option>
        </select>
      </div>

      <div class="form-group">
        <label for="adj">Descripción</label>
        <input type="text" id="adj" name="adj" placeholder="Resumen breve del problema" required />
      </div>

      <div class="form-group">
        <label for="men">Mensaje</label>
        <textarea id="men" name="men" placeholder="Detalles completos del problema..."></textarea>
      </div>

      <div class="form-group">
        <label for="fecha">Fecha</label>
        <input id="fecha" name="fecha" type="text" required readonly />
      </div>

      <div class="form-group">
        <label for="Hora">Hora</label>
        <input type="text" id="Hora" name="Hora" required readonly />
      </div>

      <div class="form-group">
        <label for="tik">ID Ticket</label>
        <input type="text" id="tik" name="tik" required readonly />
      </div>

      <button type="submit">Solicitar</button>
    </form>
  </div>

  <script>
  
  document.getElementById("homeButton").addEventListener("click", function() {
  // Reemplaza "index.html" con la ruta a tu página de inicio
  window.location.href = "http://desarollo-bacros/TicketBacros/MenSG.php";
});
    // Fecha y hora automáticas
    function pad(n) { return n < 10 ? '0' + n : n; }
    function updateDateTime() {
      const now = new Date();
	
	const formattedDate = now.toLocaleDateString('es-ES', {
  year: 'numeric',
  month: '2-digit',
  day: '2-digit'
}).split('/').reverse().join('-');
	
      document.getElementById('fecha').value = formattedDate;
      document.getElementById('Hora').value = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Generar ID
    function generateTicketID() {
      const ticket = document.getElementById('tik');
      if (!ticket.value) {
        ticket.value = 'TI-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
      }
    }
    generateTicketID();
  </script>

</body>
</html>

<?php

$serverName1 = "WIN-44O80L37Q7M\COMERCIAL"; //serverName\instanceName
$connectionInfo1 = array( "Database"=>"BASENUEVA", "UID"=>"SA", "PWD"=>"Administrador1*","CharacterSet" => "UTF-8");
$conn1 = sqlsrv_connect( $serverName1, $connectionInfo1);

/////Query ordenes de cancelación de alimentos.
$sql = "Select ContactName,NickName, MainAddress from [dbo].[vwLBSContactList]";
/////Query ordenes de cancelación de alimentos.

$stmt = sqlsrv_query( $conn1, $sql );

$array_tot1 = [];

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
/////////////////////////////////////////////////// Array nuevas variables
// echo $row['COLABORADOR'];
array_push($array_tot1,$row['ContactName']);
}




if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name1 = test_input($_POST["Nombre"]);
  $name2 = test_input($_POST["elect"]);
  $name3 = test_input($_POST["prio"]);
  $name4 = test_input($_POST["empre"]);
  $name5 = test_input($_POST["Asunto"]);
  $name6 = test_input($_POST["men"]);
  $name7 = test_input($_POST["adj"]);
  $name8 = test_input($_POST["fecha"]);
  $name9 = test_input($_POST["Hora"]);
  $name10 = test_input($_POST["tik"]);
  
  
  


  $serverName = "DESAROLLO-BACRO\SQLEXPRESS";
  $connectionInfo = array("Database"=>"Ticket", "UID"=>"Larome03", "PWD"=>"Larome03","CharacterSet" => "UTF-8");
  $conn = sqlsrv_connect($serverName, $connectionInfo);

  $sql = "INSERT INTO TicketsSG  ([Nombre], [Prioridad], [Empresa], [Asunto], [Mensaje], [Adjuntos], [Fecha], [Hora], [Id_Ticket], [Estatus], [PA])
          VALUES ('$name1', '$name3', '$name4', '$name5', '$name6', '$name7', '$name8', '$name9', '$name10', '', '')";

  $stmt = sqlsrv_query($conn, $sql);
  if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
  }

  sqlsrv_free_stmt($stmt);

  echo '<script type="text/javascript">
          alert("Se levantó de forma correcta tu ticket");
        </script>';
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>

<script>
	var dataQ1 = <?php echo json_encode($array_tot1);?>;
	
	

const select = document.getElementById("Nombre");

// Llenar el <select> con las opciones
dataQ1.forEach(opcion => {
  const optionElement = document.createElement("option");
  optionElement.value = opcion.toLowerCase().replace(/\s+/g, '_'); // valor (ej: opcion_1)
  optionElement.textContent = opcion; // texto visible
  select.appendChild(optionElement);
});
</script>
	