<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <meta name="description" content="Plataforma de mantenimiento vehicular corporativo Bacrocorp" />

  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" />

  <title>SERVICIOS GENERALES</title>

  <style>
    body, html {
      margin: 0;
      padding: 0;
      font-family: 'Open Sans', sans-serif;
      height: 100%;
      overflow-x: hidden;
      background-color: #f7f7f7;
    }

    .sidenav {
      position: fixed;
      top: 0;
      left: -250px;
      width: 250px;
      height: 100%;
      background-color: #2e3b4e;
      padding-top: 40px;
      z-index: 1000;
      transition: left 0.4s ease;
      box-shadow: 4px 0 15px rgba(0,0,0,0.3);
    }

    .sidenav.open {
      left: 0;
    }

    .sidenav a {
      display: block;
      padding: 20px 35px;
      text-decoration: none;
      color: #f0f0f0;
      font-weight: 700;
      font-size: 20px;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }

    .sidenav a:hover {
      background: rgba(255, 204, 0, 0.15);
      color: #ffcc00;
      border-left: 4px solid #ffcc00;
      box-shadow: inset 4px 0 10px rgba(255, 204, 0, 0.2);
      transform: translateX(10px) scale(1.05);
    }

    .main-content {
      margin-left: 0;
      padding: 30px;
      transition: margin-left 0.4s ease;
    }

    .sidenav.open ~ .main-content {
      margin-left: 250px;
    }

    .hero {
      position: relative;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      overflow: hidden;
      perspective: 1000px;
    }

    .hero::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-image:
        linear-gradient(rgba(0, 0, 0, 0.4), rgba(20, 20, 20, 0.6)),
        url('herra.jpg');
      background-size: cover;
      background-position: center;
      filter: blur(1px) brightness(0.95);
      transform: scale(1.03);
      z-index: 1;
      animation: moveBg 2.5s ease-in-out infinite alternate;
    }

    @keyframes moveBg {
      0% {
        transform: scale(1.03) translateY(-10px);
      }
      100% {
        transform: scale(1.03) translateY(10px);
      }
    }

    .hero h1 {
      position: relative;
      z-index: 2;
      color: #ffcc00;
      font-size: 2.8rem;
      font-weight: 700;
      line-height: 1.3;
      max-width: 800px;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3), 2px 2px 6px rgba(0, 0, 0, 0.2);
      transform: translateZ(20px);
      transition: transform 0.3s ease;
      margin: 0 15px;
    }

    .hero h1:hover {
      transform: translateZ(35px) scale(1.03);
    }

    .menu-toggle {
      position: fixed;
      top: 15px;
      left: 20px;
      font-size: 30px;
      color: #2e3b4e;
      cursor: pointer;
      z-index: 1100;
      background: #ffcc00;
      padding: 5px 10px;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      border: none;
    }

    .menu-toggle:hover {
      background: #e6b800;
    }

    @media (max-width: 768px) {
      .sidenav {
        width: 100%;
        height: auto;
        position: relative;
        left: 0;
        box-shadow: none;
        padding-top: 15px;
      }

      .main-content {
        margin-left: 0 !important;
        padding: 15px;
      }

      .hero h1 {
        font-size: 1.8rem;
        padding: 0 10px;
      }
    }
  </style>
</head>

<body>

  <!-- Botón de menú -->
  <button class="menu-toggle" onclick="toggleSidenav()" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Menú lateral -->
  <nav class="sidenav" id="sidenav" aria-label="Sidebar navigation">
  <br>
  <br>
    <a href="http://192.168.100.95/TicketBacros/Ticket.php"><i class="fas fa-home"></i> INICIO</a>
    <a href="FormSG.php"><i class="fas fa-plus-circle"></i> LEVANTA TU TICKET</a>
    <a href="Consultadata.php"><i class="fas fa-cogs"></i> ESTATUS DE TU TICKET</a>
    <a href="http://192.168.100.95/TicketBacros/Login_Procesar.php"><i class="fas fa-user-cog"></i> PROCESAR TICKETS</a>
  </nav>

  <!-- Sección principal -->
  <main>
    <section class="hero" role="banner">
      <h1>"Tu aliado integral en limpieza, mantenimiento y bienestar corporativo."</h1>
    </section>
  </main>

  <!-- Scripts -->
  <script>
    const sidenav = document.getElementById('sidenav');
    const toggleBtn = document.querySelector('.menu-toggle');

    function toggleSidenav() {
      sidenav.classList.toggle('open');
    }

    document.addEventListener('click', function(event) {
      if (!sidenav.contains(event.target) && !toggleBtn.contains(event.target)) {
        sidenav.classList.remove('open');
      }
    });
  </script>

</body>
</html>
