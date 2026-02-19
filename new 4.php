<?php
/**
 * @file new 4.php
 * @brief Página de acceso al sistema con diseño moderno animado.
 *
 * @description
 * Formulario de login con diseño moderno usando Bootstrap 4.6.2 y
 * animaciones CSS. Presenta una tarjeta centrada con efecto de sombra
 * y animación de entrada fadeIn.
 *
 * Nota: Este archivo parece ser un prototipo o versión de prueba de
 * la página de login. El nombre "new 4.php" sugiere que es una
 * iteración de diseño. Considerar renombrar o integrar con Loginti.php.
 *
 * Características de diseño:
 * - Fondo con gradiente gris y patrón de cubos (Transparent Textures)
 * - Card centrada con sombra 3D
 * - Tipografía Poppins de Google Fonts
 * - Paleta corporativa azul (#1E4E79)
 * - Animación fadeIn al cargar
 * - Botón con efecto hover (scale)
 *
 * @module Módulo de Autenticación
 * @access Público
 *
 * @dependencies
 * - JS CDN: jQuery 3.7.1, Popper.js 1.16.1, Bootstrap 4.6.2
 * - CSS CDN: Bootstrap 4.6.2, Google Fonts (Poppins)
 * - Externa: Transparent Textures (patrón de fondo)
 *
 * @ui_components
 * - Container global centrado
 * - Login card con border-radius y sombra
 * - Formulario con campos username/password
 * - Botón submit con hover animation
 * - Link de registro
 *
 * @css_animations
 * - fadeIn: opacity 0→1, translateY 30px→0
 * - Button hover: scale(1.03)
 *
 * @todo
 * - Renombrar archivo a nombre descriptivo
 * - Integrar con sistema de autenticación real
 * - Añadir validación de formulario
 * - Conectar con backend PHP
 *
 * @author Equipo Tecnología BacroCorp
 * @version 0.4 (desarrollo)
 * @since 2024
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Acceso al Sistema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body, html { height: 100%; margin: 0; font-family: 'Poppins', sans-serif; background: linear-gradient(145deg, #e0e0e0, #f0f0f0); }
    .global-container { display:flex; align-items:center; justify-content:center; height:100%; background-image:url('https://www.transparenttextures.com/patterns/cubes.png'); }
    .login-card { width:360px; border-radius:15px; box-shadow:0 12px 30px rgba(0,0,0,0.2); background:#fff; animation: fadeIn 1s ease-out; position:relative; overflow:hidden; }
    .card-body { padding:30px; position:relative; z-index:2; }
    .card-title { font-size:26px; font-weight:600; text-align:center; color:#1E4E79; margin-bottom:20px; }
    .signupbtn { width:100%; padding:10px; background:#1E4E79; color:#fff; border:none; border-radius:8px; margin-top:20px; font-weight:600; transition:0.3s; }
    .signupbtn:hover { background:#163d60; transform:scale(1.03); }
    label { font-weight:500; }
    .sign-up { text-align:center; margin-top:20px; font-size:13px; }
    .sign-up a { color:#1E4E79; font-weight:600; text-decoration:none; }
    @keyframes fadeIn { from {opacity:0;transform:translateY(30px);} to{opacity:1;transform:translateY(0);} }

    /* SVG animación */
    .svg-bg { position:absolute; top:0; left:0; width:100%; height:200px; }
    .draw path { stroke: #1E4E79; stroke-width:2; fill:none; stroke-dasharray:300; stroke-dashoffset:300; animation: draw 2s ease-out forwards; }
    @keyframes draw { to { stroke-dashoffset:0; } }
  </style>
</head>

<body>
  <div class="global-container">
    <div class="login-card">
      <svg class="svg-bg" viewBox="0 0 360 200">
        <g class="draw">
          <path d="M60,160 h240 v-80 h-240 z"/> <!-- monitor -->
          <path d="M150,160 v20 h60 v-20"/> <!-- base -->
          <path d="M80,175 h200"/> <!-- teclado -->
        </g>
      </svg>

      <div class="card-body">
        <h3 class="card-title">Bienvenido</h3>
        <form>
          <div class="form-group">
            <label for="inputUser">Usuario</label>
            <input id="inputUser" type="text" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="inputPass">Contraseña</label>
            <input id="inputPass" type="password" class="form-control" required>
          </div>
          <button type="button" onclick="Funcion();" class="signupbtn">Aceptar</button>
          <div class="sign-up">¿No tienes acceso? <a href="#">Contacta soporte técnico</a></div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function Funcion(){
      var u = document.getElementById("inputUser").value;
      var p = document.getElementById("inputPass").value;
      if(u==="TI"&&p==="TI01") window.location.replace("http://192.168.100.95/TicketBacros/dashti.php");
      else if(u==="TI"&&p==="TI02") window.location.replace("http://192.168.100.95/TicketBacros/contact.html");
      else alert("Por favor ingrese información válida");
    }
  </script>
</body>
</html>
