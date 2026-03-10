<?php
/**
 * @file MenSG.php
 * @brief Menú principal del módulo de Servicios Generales.
 *
 * @description
 * Página de navegación principal para el módulo de Servicios Generales.
 * Presenta un menú lateral (sidenav) con acceso a las diferentes
 * funcionalidades: crear ticket, consultar tickets, reportes, etc.
 *
 * Características de diseño:
 * - Sidebar lateral colapsable (250px)
 * - Fondo del sidebar: #2e3b4e (navy/slate)
 * - Tipografía Open Sans de Google Fonts
 * - Bootstrap 4.6.0 para layout
 * - Font Awesome 6.0.0 para iconos
 * - Sidebar inicia oculto (left: -250px)
 *
 * Opciones del menú:
 * - Dashboard de Servicios Generales
 * - Crear nuevo ticket
 * - Consultar tickets existentes
 * - Reportes y estadísticas
 * - Configuración (si aplica)
 *
 * @module Módulo de Servicios Generales
 * @access Público
 *
 * @dependencies
 * - CSS CDN: Google Fonts (Open Sans), Font Awesome 6.0.0, Bootstrap 4.6.0
 * - JS CDN: Bootstrap 4 (para collapsar sidebar)
 *
 * @ui_components
 * - Sidenav lateral con animación de apertura
 * - Área principal de contenido
 * - Botón hamburger para toggle del menú
 * - Links de navegación con iconos
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes" />
  <title>SERVICIOS GENERALES | BACROCORP</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: linear-gradient(145deg, #f0f5ff 0%, #e6f0ff 50%, #d4e4ff 100%);
      min-height: 100vh;
      padding: 15px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      overflow-x: hidden;
    }

    /* Fondo sutil */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        radial-gradient(circle at 10% 20%, rgba(0, 51, 102, 0.03) 0%, transparent 30%),
        radial-gradient(circle at 90% 70%, rgba(0, 102, 204, 0.03) 0%, transparent 40%);
      pointer-events: none;
      z-index: 0;
    }

    /* Logo compacto */
    .logo-container {
      position: absolute;
      top: 15px;
      right: 20px;
      width: 90px;
      height: 90px;
      z-index: 30;
      filter: drop-shadow(0 10px 20px rgba(0, 51, 102, 0.15));
      animation: floatLogo 6s infinite alternate ease-in-out;
    }

    @keyframes floatLogo {
      0% { transform: translateY(0) scale(1); }
      100% { transform: translateY(-5px) scale(1.02); }
    }

    .water-drop {
      position: relative;
      width: 100%;
      height: 100%;
      background: white;
      border-radius: 60% 40% 50% 50% / 40% 50% 50% 60%;
      box-shadow: 
        0 15px 25px rgba(0, 51, 102, 0.1),
        inset 0 -5px 10px rgba(0,0,0,0.02),
        inset 0 5px 10px rgba(255,255,255,0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      animation: morphDrop 8s infinite alternate ease-in-out;
    }

    @keyframes morphDrop {
      0% { border-radius: 60% 40% 50% 50% / 40% 50% 50% 60%; }
      50% { border-radius: 40% 60% 50% 50% / 50% 40% 60% 50%; }
      100% { border-radius: 60% 40% 50% 50% / 40% 50% 50% 60%; }
    }

    .water-drop img {
      width: 70%;
      height: 70%;
      object-fit: contain;
    }

    /* Botón home compacto */
    #homeButton {
      position: fixed;
      top: 15px;
      left: 15px;
      width: 50px;
      height: 50px;
      background: white;
      border: none;
      border-radius: 45% 55% 50% 50% / 50% 45% 55% 50%;
      font-size: 22px;
      cursor: pointer;
      box-shadow: 0 10px 20px rgba(0, 51, 102, 0.15);
      z-index: 1000;
      color: #003366;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    #homeButton:hover {
      background: #003366;
      color: white;
      transform: scale(1.1);
    }

    /* Contenedor principal */
    .premium-container {
      max-width: 1200px;
      width: 100%;
      margin: 80px auto 30px;
      position: relative;
      z-index: 10;
    }

    /* Tarjeta principal */
    .glass-card {
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border-radius: 50px 20px 50px 20px;
      padding: 30px;
      box-shadow: 0 30px 50px rgba(0, 51, 102, 0.1);
      border: 1px solid rgba(255,255,255,0.8);
    }

    /* Título compacto */
    .title-section {
      text-align: center;
      margin-bottom: 20px;
    }

    h1 {
      font-size: 2.5rem;
      font-weight: 800;
      color: #003366;
      margin-bottom: 5px;
      letter-spacing: -0.5px;
    }

    .subtitle {
      font-size: 1rem;
      color: #4a5b6e;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .subtitle i {
      color: #003366;
      font-size: 0.8rem;
    }

    /* Mensaje compacto */
    .welcome-message {
      text-align: center;
      margin: 15px auto 25px;
      padding: 12px 20px;
      background: rgba(255, 255, 255, 0.6);
      backdrop-filter: blur(5px);
      border-radius: 40px 10px 40px 10px;
      font-size: 1rem;
      color: #003366;
      font-weight: 500;
      max-width: 600px;
      border: 1px solid rgba(255,255,255,0.8);
    }

    .welcome-message i {
      margin: 0 8px;
      font-size: 0.9rem;
    }

    /* Grid de opciones */
    .options-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin: 25px 0;
    }

    /* Tarjetas de opciones */
    .option-card {
      background: white;
      backdrop-filter: blur(10px);
      border-radius: 30px 10px 30px 10px;
      padding: 25px 20px;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      display: block;
      border: 1px solid rgba(0, 51, 102, 0.1);
      box-shadow: 0 10px 25px rgba(0, 51, 102, 0.08);
    }

    .option-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 35px rgba(0, 51, 102, 0.15);
      border-color: #003366;
    }

    /* Icono */
    .icon-drop {
      width: 90px;
      height: 90px;
      margin: 0 auto 15px;
      background: linear-gradient(135deg, #003366, #0066cc);
      border-radius: 45% 55% 50% 50% / 50% 45% 55% 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: white;
      box-shadow: 0 10px 20px rgba(0, 51, 102, 0.2);
      transition: all 0.3s ease;
    }

    .option-card:hover .icon-drop {
      transform: scale(1.05) rotate(5deg);
      background: #003366;
    }

    .option-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: #003366;
      margin-bottom: 10px;
    }

    .option-description {
      color: #4a5b6e;
      font-size: 0.9rem;
      line-height: 1.5;
      margin-bottom: 15px;
    }

    /* Botón de acción */
    .action-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 25px;
      background: #003366;
      border: none;
      border-radius: 30px 8px 30px 8px;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(0, 51, 102, 0.2);
    }

    .option-card:hover .action-button {
      background: #0066cc;
      transform: translateX(5px);
    }

    .action-button i {
      transition: transform 0.3s ease;
      font-size: 0.8rem;
    }

    .option-card:hover .action-button i {
      transform: translateX(5px);
    }

    /* Footer compacto */
    .footer-premium {
      margin-top: 30px;
      text-align: center;
      padding: 20px;
      background: rgba(255, 255, 255, 0.6);
      backdrop-filter: blur(5px);
      border-radius: 30px 10px 30px 10px;
      font-size: 0.95rem;
      color: #003366;
      border: 1px solid rgba(255,255,255,0.8);
    }

    .footer-quote {
      font-style: italic;
      color: #4a5b6e;
      margin-top: 8px;
      font-size: 0.85rem;
    }

    .footer-quote i {
      margin: 0 5px;
      font-size: 0.7rem;
    }

    /* ===== RESPONSIVE PERFECTO ===== */
    @media (max-width: 992px) {
      .options-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      h1 {
        font-size: 2.2rem;
      }
    }

    @media (max-width: 768px) {
      .logo-container {
        width: 70px;
        height: 70px;
        top: 10px;
        right: 15px;
      }

      #homeButton {
        width: 45px;
        height: 45px;
        font-size: 20px;
        top: 10px;
        left: 10px;
      }

      .premium-container {
        margin-top: 70px;
      }

      .glass-card {
        padding: 20px;
      }

      h1 {
        font-size: 2rem;
      }

      .subtitle {
        font-size: 0.9rem;
      }

      .welcome-message {
        font-size: 0.9rem;
        padding: 10px 15px;
        margin: 10px auto 20px;
      }

      .options-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .icon-drop {
        width: 80px;
        height: 80px;
        font-size: 2.2rem;
      }

      .option-title {
        font-size: 1.2rem;
      }

      .option-description {
        font-size: 0.85rem;
        margin-bottom: 12px;
      }

      .action-button {
        padding: 8px 20px;
        font-size: 0.85rem;
      }

      .footer-premium {
        padding: 15px;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 10px;
      }

      .logo-container {
        width: 60px;
        height: 60px;
      }

      #homeButton {
        width: 40px;
        height: 40px;
        font-size: 18px;
      }

      .premium-container {
        margin-top: 60px;
      }

      h1 {
        font-size: 1.6rem;
      }

      .subtitle {
        font-size: 0.8rem;
      }

      .welcome-message {
        font-size: 0.85rem;
        padding: 8px 12px;
      }

      .icon-drop {
        width: 70px;
        height: 70px;
        font-size: 2rem;
      }

      .option-title {
        font-size: 1.1rem;
      }

      .option-description {
        font-size: 0.8rem;
      }

      .action-button {
        padding: 7px 18px;
        font-size: 0.8rem;
      }

      .footer-premium {
        padding: 12px;
        font-size: 0.85rem;
      }

      .footer-quote {
        font-size: 0.8rem;
      }
    }

    @media (max-width: 360px) {
      h1 {
        font-size: 1.4rem;
      }

      .icon-drop {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
      }

      .option-title {
        font-size: 1rem;
      }
    }

    /* Landscape mode */
    @media (max-width: 900px) and (orientation: landscape) {
      .options-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .icon-drop {
        width: 70px;
        height: 70px;
        font-size: 2rem;
      }

      .premium-container {
        margin-top: 50px;
      }
    }

    /* Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #e6f0ff;
    }

    ::-webkit-scrollbar-thumb {
      background: #003366;
      border-radius: 10px;
    }
  </style>
</head>
<body>

  <!-- Logo -->
  <div class="logo-container">
    <div class="water-drop">
      <img src="Logo2.png" alt="Logo Bacrocorp" />
    </div>
  </div>

  <!-- Botón home -->
  <a href="http://desarollo-bacros/TicketBacros/MenSG.php" id="homeButton" title="Inicio">
    <i class="fas fa-home"></i>
  </a>

  <!-- Contenedor principal -->
  <div class="premium-container">
    <div class="glass-card">

      <!-- Título compacto -->
      <div class="title-section">
        <h1>SERVICIOS GENERALES</h1>
        <div class="subtitle">
          <i class="fas fa-droplet"></i>
          Sistema de Gestión de Tickets
          <i class="fas fa-droplet"></i>
        </div>
      </div>

      <!-- Mensaje de bienvenida -->
      <div class="welcome-message">
        <i class="fas fa-quote-left"></i>
        Innovación y eficiencia para tus solicitudes corporativas
        <i class="fas fa-quote-right"></i>
      </div>

      <!-- Grid de opciones -->
      <div class="options-grid">

        <!-- Opción 1 -->
        <a href="FORMSGRES.php" class="option-card">
          <div class="icon-drop">
            <i class="fas fa-plus-circle"></i>
          </div>
          <h2 class="option-title">LEVANTA TU TICKET</h2>
          <p class="option-description">
            Crea una nueva solicitud de servicio
          </p>
          <div class="action-button">
            <span>Crear Ticket</span>
            <i class="fas fa-arrow-right"></i>
          </div>
        </a>

        <!-- Opción 2 -->
        <a href="Consultadata.php" class="option-card">
          <div class="icon-drop">
            <i class="fas fa-search"></i>
          </div>
          <h2 class="option-title">CONSULTA TU TICKET</h2>
          <p class="option-description">
            Da seguimiento a tus solicitudes
          </p>
          <div class="action-button">
            <span>Consultar</span>
            <i class="fas fa-arrow-right"></i>
          </div>
        </a>

        <!-- Opción 3 -->
        <a href="http://192.168.100.95/TicketBacros/Login_Procesar.php" class="option-card">
          <div class="icon-drop">
            <i class="fas fa-cogs"></i>
          </div>
          <h2 class="option-title">PROCESAR TICKETS</h2>
          <p class="option-description">
            Acceso exclusivo para personal autorizado
          </p>
          <div class="action-button">
            <span>Procesar</span>
            <i class="fas fa-arrow-right"></i>
          </div>
        </a>
      </div>

      <!-- Footer -->
      <div class="footer-premium">
        <i class="fas fa-quote-left"></i>
        Transformando tus necesidades en soluciones
        <i class="fas fa-quote-right"></i>
        <div class="footer-quote">
          <i class="fas fa-ticket-alt"></i> Tickets • Generales • Bacrocorp <i class="fas fa-ticket-alt"></i>
        </div>
      </div>
    </div>
  </div>

</body>
</html>