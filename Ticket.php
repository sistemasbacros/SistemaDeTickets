<?php
/**
 * @file Ticket.php
 * @brief Plataforma ejecutiva de soporte empresarial - Vista pública de tickets.
 *
 * @description
 * Dashboard ejecutivo con diseño premium para la gestión de tickets 360°.
 * Esta es la página de entrada principal del sistema, con énfasis en
 * presentación corporativa y experiencia de usuario de alto nivel.
 *
 * Características del diseño:
 * - Tipografías ejecutivas: Barlow Semi Condensed, Inter, Playfair Display
 * - Paleta corporativa: Navy (#0a2e5c), Blue (#1d4ed8), Accent (#3b82f6)
 * - Optimización de carga: Preconnect a CDNs de Google Fonts
 * - Favicon corporativo (logo2.png)
 * - Diseño responsive y moderno
 *
 * Este archivo presenta una vista pública o de landing para el sistema
 * de tickets, sin requerir autenticación inmediata.
 *
 * @module Módulo Principal / Landing
 * @access Público
 *
 * @dependencies
 * - CSS CDN: Font Awesome 6.5.0, Google Fonts
 * - Assets: logo2.png (favicon)
 *
 * @css_variables
 * - --primary: #0a2e5c (navy principal)
 * - --primary-dark: #0a1f3a (navy oscuro)
 * - --secondary: #1d4ed8 (azul secundario)
 * - --accent: #3b82f6 (azul acento)
 * - --light-bg: #f0f9ff (fondo claro)
 * - --text-light: #ffffff (texto claro)
 *
 * @ui_design
 * - Estilo ejecutivo/corporativo premium
 * - Enfoque en branding BacroCorp
 * - Título descriptivo para SEO
 *
 * @author Equipo Tecnología BacroCorp
 * @version 2.0
 * @since 2024
 */
require_once __DIR__ . '/auth_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes" />
  
  <title>BACROCORP | Plataforma Inteligente de Soporte Empresarial — Gestión 360° en Tiempo Real</title>

  <!-- Preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">

  <!-- Google Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

  <!-- Fuentes ultra modernas -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Favicon -->
  <link rel="icon" href="logo2.png" type="image/png">

  <style>
    /* ===== VARIABLES AZUL Y BLANCO ===== */
    :root {
      --primary: #0033cc;
      --primary-dark: #0022aa;
      --primary-light: #0044ee;
      --accent: #0066ff;
      --accent-glow: #99bbff;
      --surface: #ffffff;
      --surface-glass: rgba(255, 255, 255, 0.9);
      --background: #f5f9ff;
      --text-primary: #0033cc;
      --text-secondary: #004488;
      --text-tertiary: #667799;
      --glass-border: rgba(0, 51, 204, 0.1);
      --glass-glow: rgba(0, 102, 255, 0.2);
      --shadow-sm: 0 4px 6px -1px rgba(0, 51, 204, 0.1), 0 2px 4px -1px rgba(0, 51, 204, 0.06);
      --shadow-md: 0 10px 15px -3px rgba(0, 51, 204, 0.1), 0 4px 6px -2px rgba(0, 51, 204, 0.05);
      --shadow-lg: 0 20px 25px -5px rgba(0, 51, 204, 0.1), 0 10px 10px -5px rgba(0, 51, 204, 0.04);
      --shadow-xl: 0 25px 50px -12px rgba(0, 51, 204, 0.25);
      --blur-sm: blur(8px);
      --blur-md: blur(16px);
      --blur-lg: blur(24px);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --transition-bounce: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--background);
      color: var(--text-primary);
      min-height: 100vh;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      position: relative;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
    }

    /* ===== FONDO CON PARTÍCULAS ===== */
    .particles-bg {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      overflow: hidden;
    }

    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(0, 51, 204, 0.08);
      border-radius: 50%;
      animation: float 20s infinite linear;
    }

    .particle:nth-child(1) { top: 10%; left: 20%; animation-duration: 25s; width: 6px; height: 6px; }
    .particle:nth-child(2) { top: 70%; left: 80%; animation-duration: 30s; width: 8px; height: 8px; background: rgba(0, 102, 255, 0.08); }
    .particle:nth-child(3) { top: 40%; left: 40%; animation-duration: 22s; width: 5px; height: 5px; }
    .particle:nth-child(4) { top: 80%; left: 30%; animation-duration: 28s; width: 7px; height: 7px; }
    .particle:nth-child(5) { top: 20%; left: 70%; animation-duration: 35s; width: 10px; height: 10px; }
    .particle:nth-child(6) { top: 60%; left: 60%; animation-duration: 32s; width: 6px; height: 6px; }

    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 0.8; }
      90% { opacity: 0.8; }
      100% { transform: translateY(-500px) rotate(360deg); opacity: 0; }
    }

    /* ===== PRELOADER ===== */
    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: white;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      transition: opacity 0.8s ease, visibility 0.8s ease;
    }

    #preloader.fade-out {
      opacity: 0;
      visibility: hidden;
    }

    .preload-content {
      text-align: center;
      padding: 0 2rem;
      max-width: 500px;
    }

    .preload-logo {
      width: 150px;
      margin: 0 auto 2rem;
      animation: pulse 2s ease-in-out infinite;
    }

    .preload-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px var(--accent-glow)); }
      50% { transform: scale(1.05); filter: drop-shadow(0 0 40px var(--accent-glow)); }
    }

    .preload-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 1rem;
    }

    .preload-subtitle {
      color: var(--text-secondary);
      font-size: 1rem;
      margin-bottom: 2rem;
    }

    .preload-progress {
      width: 280px;
      height: 4px;
      background: #e6f0ff;
      border-radius: 2px;
      overflow: hidden;
      margin: 2rem auto;
    }

    .preload-progress .bar {
      width: 0%;
      height: 100%;
      background: var(--primary);
      animation: progress 3s ease-in-out forwards;
    }

    @keyframes progress {
      0% { width: 0%; }
      20% { width: 30%; }
      50% { width: 60%; }
      70% { width: 80%; }
      100% { width: 100%; }
    }

    /* ===== HEADER RESPONSIVE ===== */
    .header-glass {
      position: sticky;
      top: 15px;
      z-index: 100;
      margin: 0 20px;
    }

    .glass-header {
      background: var(--surface-glass);
      backdrop-filter: var(--blur-lg);
      border: 1px solid var(--glass-border);
      border-radius: 60px;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow-lg);
      width: 100%;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
      flex: 1;
    }

    .logo-area img {
      height: 40px;
      width: auto;
      border-radius: 10px;
    }

    .header-title {
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--primary);
      white-space: nowrap;
    }

    .status-chip {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #e6f0ff;
      padding: 6px 16px;
      border-radius: 40px;
      color: var(--primary);
      font-weight: 500;
      font-size: 0.9rem;
      white-space: nowrap;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      background: #00cc66;
      border-radius: 50%;
      animation: pulseDot 2s infinite;
    }

    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(1.2); }
    }

    /* ===== MAIN CONTAINER RESPONSIVE ===== */
    .container {
      max-width: 1400px;
      width: 100%;
      margin: 40px auto;
      padding: 0 20px;
      flex: 1;
    }

    /* ===== WELCOME CARD RESPONSIVE ===== */
    .welcome-card {
      background: white;
      border-radius: 32px;
      padding: 30px;
      margin-bottom: 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--glass-border);
    }

    .welcome-text {
      flex: 1;
      min-width: 280px;
    }

    .welcome-text h2 {
      font-size: 2rem;
      font-weight: 800;
      color: var(--primary);
      margin-bottom: 10px;
      line-height: 1.2;
    }

    .welcome-text p {
      color: var(--text-secondary);
      font-size: 1rem;
      line-height: 1.5;
    }

    .fab-blue {
      width: 70px;
      height: 70px;
      background: var(--primary);
      border-radius: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-md);
      color: white;
      font-size: 2rem;
      transition: var(--transition-bounce);
      flex-shrink: 0;
    }

    .fab-blue:hover {
      transform: scale(1.1) rotate(5deg);
      background: var(--accent);
    }

    /* ===== GRID DE CARDS TOTALMENTE RESPONSIVE ===== */
    .grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .card {
      background: white;
      border-radius: 28px;
      overflow: hidden;
      text-decoration: none;
      color: var(--text-primary);
      box-shadow: var(--shadow-md);
      transition: var(--transition-bounce);
      display: flex;
      flex-direction: column;
      border: 1px solid var(--glass-border);
      height: 100%;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary-light);
    }

    .card-media {
      height: 140px;
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .card-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: white;
      padding: 5px 12px;
      border-radius: 30px;
      font-size: 0.7rem;
      font-weight: 600;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      gap: 5px;
      color: var(--primary);
    }

    .card-badge i {
      font-size: 0.5rem;
      color: #00cc66;
    }

    .card-content {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .card-icon {
      width: 45px;
      height: 45px;
      background: #e6f0ff;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: var(--primary);
      flex-shrink: 0;
    }

    .card-header h3 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--primary);
      line-height: 1.3;
    }

    .card-text {
      color: var(--text-tertiary);
      font-size: 0.85rem;
      line-height: 1.5;
      margin-bottom: 15px;
      flex: 1;
    }

    .card-stats {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 12px;
      border-top: 1px solid #e6f0ff;
      margin-top: auto;
    }

    .status-badge {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--primary);
    }

    .status-badge i {
      color: #00cc66;
      font-size: 0.5rem;
    }

    .card-arrow {
      width: 32px;
      height: 32px;
      background: #e6f0ff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      transition: var(--transition);
      font-size: 0.9rem;
    }

    .card:hover .card-arrow {
      background: var(--primary);
      color: white;
      transform: translateX(5px);
    }

    /* ===== FOOTER RESPONSIVE CORREGIDO ===== */
    .footer {
      background: white;
      border-top: 1px solid var(--glass-border);
      padding: 30px 20px;
      margin-top: 40px;
      width: 100%;
    }

    .footer-content {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }

    .footer-links {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .footer-links a {
      color: var(--text-secondary);
      text-decoration: none;
      transition: var(--transition);
      font-weight: 500;
      font-size: 0.95rem;
      position: relative;
    }

    .footer-links a::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary);
      transition: width 0.3s ease;
    }

    .footer-links a:hover::after {
      width: 100%;
    }

    .footer-links a:hover {
      color: var(--primary);
    }

    .footer-copyright {
      color: var(--text-tertiary);
      font-size: 0.9rem;
    }

    /* ===== SOUND TOGGLE ===== */
    .sound-toggle {
      position: fixed;
      bottom: 25px;
      right: 25px;
      z-index: 1000;
      width: 50px;
      height: 50px;
      background: var(--primary);
      border: none;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: var(--shadow-lg);
      transition: var(--transition-bounce);
      color: white;
      font-size: 1.2rem;
    }

    .sound-toggle:hover {
      background: var(--accent);
      transform: scale(1.1) rotate(5deg);
    }

    /* ===== RESPONSIVE BREAKPOINTS PERFECTOS ===== */
    /* Desktop grande */
    @media (min-width: 1400px) {
      .container {
        max-width: 1600px;
      }
      .grid {
        gap: 25px;
      }
    }

    /* Desktop medio */
    @media (max-width: 1200px) {
      .grid {
        grid-template-columns: repeat(3, 1fr);
      }
      .welcome-text h2 {
        font-size: 1.8rem;
      }
    }

    /* Tablet landscape */
    @media (max-width: 992px) {
      .grid {
        grid-template-columns: repeat(2, 1fr);
      }
      .header-title {
        font-size: 1rem;
      }
      .status-chip span {
        font-size: 0.8rem;
      }
    }

    /* Tablet portrait */
    @media (max-width: 768px) {
      .glass-header {
        padding: 10px 15px;
      }
      .header-title {
        display: none;
      }
      .logo-area img {
        height: 35px;
      }
      .status-chip {
        padding: 5px 12px;
      }
      .status-chip span {
        font-size: 0.75rem;
      }
      .welcome-card {
        padding: 25px;
      }
      .welcome-text h2 {
        font-size: 1.6rem;
      }
      .welcome-text p {
        font-size: 0.9rem;
      }
      .fab-blue {
        width: 60px;
        height: 60px;
        font-size: 1.8rem;
      }
      .grid {
        gap: 15px;
      }
      .card-media {
        height: 120px;
      }
      .footer-links {
        gap: 20px;
      }
      .footer-links a {
        font-size: 0.9rem;
      }
    }

    /* Mobile grande */
    @media (max-width: 640px) {
      .grid {
        grid-template-columns: 1fr;
        max-width: 450px;
        margin: 0 auto;
      }
      .welcome-card {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
      }
      .welcome-text h2 {
        font-size: 1.5rem;
      }
      .welcome-text p {
        font-size: 0.85rem;
      }
      .footer-content {
        flex-direction: column;
        text-align: center;
      }
      .footer-links {
        justify-content: center;
      }
    }

    /* Mobile mediano */
    @media (max-width: 480px) {
      .header-glass {
        margin: 0 10px;
      }
      .glass-header {
        padding: 8px 12px;
      }
      .logo-area img {
        height: 30px;
      }
      .status-chip {
        padding: 4px 10px;
      }
      .status-dot {
        width: 6px;
        height: 6px;
      }
      .container {
        padding: 0 15px;
        margin: 30px auto;
      }
      .welcome-card {
        padding: 20px 15px;
        margin-bottom: 30px;
      }
      .welcome-text h2 {
        font-size: 1.3rem;
      }
      .welcome-text p {
        font-size: 0.8rem;
      }
      .fab-blue {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
        border-radius: 18px;
      }
      .card-media {
        height: 140px;
      }
      .card-content {
        padding: 18px;
      }
      .card-header h3 {
        font-size: 1rem;
      }
      .card-text {
        font-size: 0.8rem;
      }
      .footer {
        padding: 20px 15px;
      }
      .footer-links {
        gap: 15px;
      }
      .footer-links a {
        font-size: 0.85rem;
      }
      .footer-copyright {
        font-size: 0.8rem;
      }
      .sound-toggle {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        font-size: 1rem;
      }
    }

    /* Mobile pequeño */
    @media (max-width: 360px) {
      .welcome-text h2 {
        font-size: 1.2rem;
      }
      .card-header {
        flex-direction: column;
        text-align: center;
      }
      .card-stats {
        flex-direction: column;
        gap: 10px;
      }
      .footer-links {
        flex-direction: column;
        align-items: center;
        gap: 12px;
      }
    }

    /* Landscape mode */
    @media (max-width: 900px) and (orientation: landscape) {
      .grid {
        grid-template-columns: repeat(3, 1fr);
      }
      .welcome-card {
        padding: 20px;
      }
    }

    @media (max-width: 700px) and (orientation: landscape) {
      .grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    /* Alta resolución */
    @media (min-width: 2000px) {
      .container {
        max-width: 1800px;
      }
      .grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
      }
      .card-media {
        height: 180px;
      }
    }

    /* Accesibilidad */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar {
      width: 10px;
      height: 10px;
    }

    ::-webkit-scrollbar-track {
      background: #e6f0ff;
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 10px;
      border: 2px solid #e6f0ff;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }

    /* Focus visible para accesibilidad */
    :focus-visible {
      outline: 3px solid var(--primary);
      outline-offset: 3px;
      border-radius: 4px;
    }

    /* Smooth scroll */
    html {
      scroll-behavior: smooth;
    }
  </style>
</head>
<body>
  <!-- Partículas de fondo -->
  <div class="particles-bg">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <!-- Preloader -->
  <div id="preloader">
    <div class="preload-content">
      <div class="preload-logo">
        <img src="logo2.png" alt="Logo BacroCorp">
      </div>
      <h1 class="preload-title">BACROCORP</h1>
      <p class="preload-subtitle">Plataforma Inteligente de Soporte Empresarial</p>
      <div class="preload-progress">
        <div class="bar"></div>
      </div>
    </div>
  </div>

  <!-- Sound toggle -->
  <button class="sound-toggle" id="soundToggle" aria-label="Toggle sound">
    <i class="fa-solid fa-volume-high" id="soundIcon"></i>
  </button>

  <!-- Header -->
  <div class="header-glass">
    <div class="glass-header">
      <div class="logo-area">
        <img src="logo2.png" alt="Logo BacroCorp">
        <span class="header-title">BACROCORP</span>
      </div>
      <div class="status-chip">
        <span class="status-dot"></span>
        <span>Sistema activo</span>
      </div>
    </div>
  </div>

  <!-- Contenido principal -->
  <div class="container">

    <!-- Welcome Card -->
    <div class="welcome-card">
      <div class="welcome-text">
        <h2>¡Bienvenido a BACROCORP!</h2>
        <p>Plataforma inteligente de gestión empresarial 360° para soporte, mantenimiento y servicios generales.</p>
      </div>
      <div class="fab-blue">
        <i class="fa-solid fa-star"></i>
      </div>
    </div>

    <!-- Grid de cards -->
    <div class="grid">

      <!-- Soporte Técnico -->
      <a href="Loginti.php" class="card">
        <div class="card-media" style="background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80')">
          <div class="card-badge">
            <i class="fa-solid fa-circle"></i>
            <span>ACTIVO</span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-header">
            <div class="card-icon">
              <i class="fa-solid fa-headset"></i>
            </div>
            <h3>Soporte Técnico</h3>
          </div>
          <p class="card-text">Tickets 24/7 · Respuesta inmediata</p>
          <div class="card-stats">
            <span class="status-badge">
              <i class="fa-solid fa-circle"></i>
              Disponible
            </span>
            <div class="card-arrow">
              <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </div>
      </a>

      <!-- Mantenimiento Vehicular -->
      <a href="M/website-menu-05/index1.html" class="card">
        <div class="card-media" style="background-image: url('https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=800&q=80')">
          <div class="card-badge">
            <i class="fa-solid fa-circle"></i>
            <span>ACTIVO</span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-header">
            <div class="card-icon">
              <i class="fa-solid fa-car-side"></i>
            </div>
            <h3>Mantenimiento Vehicular</h3>
          </div>
          <p class="card-text">Flotilla · Talleres certificados</p>
          <div class="card-stats">
            <span class="status-badge">
              <i class="fa-solid fa-circle"></i>
              Disponible
            </span>
            <div class="card-arrow">
              <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </div>
      </a>

      <!-- Servicios Generales -->
      <a href="MenSG.php" class="card">
        <div class="card-media" style="background-image: url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80')">
          <div class="card-badge">
            <i class="fa-solid fa-circle"></i>
            <span>ACTIVO</span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-header">
            <div class="card-icon">
              <i class="fa-solid fa-broom"></i>
            </div>
            <h3>Servicios Generales</h3>
          </div>
          <p class="card-text">Limpieza · Vigilancia · Administración</p>
          <div class="card-stats">
            <span class="status-badge">
              <i class="fa-solid fa-circle"></i>
              Disponible
            </span>
            <div class="card-arrow">
              <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </div>
      </a>

      <!-- Más Servicios -->
      <a href="#" class="card">
        <div class="card-media" style="background-image: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=800&q=80')">
          <div class="card-badge">
            <i class="fa-solid fa-circle"></i>
            <span>PRÓXIMO</span>
          </div>
        </div>
        <div class="card-content">
          <div class="card-header">
            <div class="card-icon">
              <i class="fa-solid fa-code-branch"></i>
            </div>
            <h3>Más Servicios</h3>
          </div>
          <p class="card-text">Nuevos módulos en desarrollo</p>
          <div class="card-stats">
            <span class="status-badge">
              <i class="fa-solid fa-hourglass"></i>
              Próximamente
            </span>
            <div class="card-arrow">
              <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Footer corregido -->
  <footer class="footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="#">Términos</a>
        <a href="#">Privacidad</a>
        <a href="#">Contacto</a>
        <a href="#">Soporte</a>
      </div>
      <div class="footer-copyright">
        &copy; 2026 BACROCORP — Plataforma Inteligente v3.0
      </div>
    </div>
  </footer>

  <script>
    // Preloader
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.getElementById('preloader').classList.add('fade-out');
      }, 2500);
    });

    // Sound toggle
    let soundEnabled = true;
    const soundToggle = document.getElementById('soundToggle');
    const soundIcon = document.getElementById('soundIcon');

    soundToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      soundEnabled = !soundEnabled;
      soundIcon.className = soundEnabled ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark';
    });

    // Detectar orientación para UX
    function handleOrientation() {
      const isLandscape = window.innerWidth > window.innerHeight;
      document.body.setAttribute('data-orientation', isLandscape ? 'landscape' : 'portrait');
    }

    window.addEventListener('resize', handleOrientation);
    handleOrientation();

    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  </script>
</body>
</html>