<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <title>BACROCORP | Plataforma Inteligente de Soporte Empresarial — Gestión 360° en Tiempo Real</title>

  <!-- Preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">

  <!-- Fuentes ejecutivas -->
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Favicon -->
  <link rel="icon" href="logo2.png" type="image/png">

  <style>
    :root {
      --primary: #0a2e5c;
      --primary-dark: #0a1f3a;
      --secondary: #1d4ed8;
      --accent: #3b82f6;
      --light-bg: #f0f9ff;
      --text-light: #ffffff;
      --text-dark: #0a2e5c;
      --glass-bg: rgba(255, 255, 255, 0.18);
      --glass-border: rgba(255, 255, 255, 0.3);
      --glass-glow: rgba(59, 130, 246, 0.6);
      --shadow-deep: rgba(10, 46, 92, 0.4);
      --transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      font-family: 'Inter', sans-serif;
      background: var(--light-bg);
      color: var(--text-dark);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
    }

    /* ✨ PRELOADER MEJORADO — CON MÁS CONTRASTE Y EFECTO GLASSMORPHISM INTENSO */
    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #051226, #0a1f3a, #051226);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      overflow: hidden;
      color: white;
    }

    #preloader.fade-out {
      opacity: 0;
      visibility: hidden;
      transition: opacity 1s ease 0.5s, visibility 1s ease 0.5s;
    }

    .preload-content {
      text-align: center;
      position: relative;
      z-index: 2;
      padding: 0 2rem;
    }

    /* 🌀 LOGO CON EFECTO 3D ELEGANTE — ROTACIÓN, ESCALA, GLOW, SOMBRA - MEJORADO */
    .preload-logo {
      width: 200px;
      margin-bottom: 3rem;
      
      /* ✨ EFECTO 3D REAL MEJORADO */
      transform-style: preserve-3d;
      animation: 
        float3D 4s ease-in-out infinite,
        glowPulse 3s ease-in-out infinite;
      
      /* ✨ ESTILO GLASSMORPHISM INTENSO CON MÁS CONTRASTE */
      filter: brightness(1.3) contrast(1.3) saturate(1.2);
      box-shadow: 
        0 0 80px var(--glass-glow),
        0 0 160px rgba(59, 130, 246, 0.5),
        0 30px 60px rgba(0, 0, 0, 0.6);
      border-radius: 24px;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 3px solid rgba(255, 255, 255, 0.3);
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(59,130,246,0.15));
      padding: 15px;
      position: relative;
      overflow: hidden;
    }
    
    /* Capa de glass adicional para el logo */
    .preload-logo::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
      animation: shineLogo 3s ease-in-out infinite;
      z-index: 1;
    }
    
    @keyframes shineLogo {
      0% { transform: translateX(-50%) translateY(-50%) rotate(45deg); }
      100% { transform: translateX(50%) translateY(50%) rotate(45deg); }
    }

    @keyframes float3D {
      0%, 100% {
        transform: translateY(0) rotateX(0deg) rotateY(0deg) scale(1);
      }
      25% {
        transform: translateY(-40px) rotateX(8deg) rotateY(8deg) scale(1.08);
      }
      50% {
        transform: translateY(0) rotateX(0deg) rotateY(15deg) scale(1.02);
      }
      75% {
        transform: translateY(-25px) rotateX(-8deg) rotateY(8deg) scale(1.05);
      }
    }

    @keyframes glowPulse {
      0%, 100% { 
        box-shadow: 
          0 0 80px var(--glass-glow),
          0 0 160px rgba(59,130,246,0.5),
          0 30px 60px rgba(0,0,0,0.6);
      }
      50% { 
        box-shadow: 
          0 0 120px var(--accent),
          0 0 200px rgba(59,130,246,0.7),
          0 35px 70px rgba(0,0,0,0.7);
      }
    }

    .preload-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.8rem;
      font-weight: 800;
      letter-spacing: 3px;
      margin-bottom: 1.6rem;
      text-shadow: 
        0 0 30px var(--accent),
        0 0 60px rgba(255,255,255,0.9),
        0 6px 12px rgba(0,0,0,0.7);
      background: linear-gradient(45deg, #ffffff, #a0d4ff, #ffffff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: titleGlow 2s ease-in-out infinite alternate;
      position: relative;
      z-index: 2;
    }
    
    /* Capa de glass para el título */
    .preload-title::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      border-radius: 2px;
      animation: shine 2s infinite;
      opacity: 0.9;
    }

    .preload-subtitle {
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-size: 1.35rem;
      font-weight: 500;
      opacity: 0.95;
      max-width: 85%;
      margin: 0 auto 3rem;
      line-height: 1.8;
      letter-spacing: 0.8px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.5);
      position: relative;
      z-index: 2;
    }
    
    /* Capa de glass para el contenedor completo */
    .preload-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border-radius: 30px;
      padding: 30px;
      box-shadow: 
        inset 0 0 30px rgba(255,255,255,0.1),
        0 10px 50px rgba(0,0,0,0.4);
      border: 1px solid rgba(255,255,255,0.1);
      z-index: -1;
      animation: pulseBorder 4s ease-in-out infinite;
    }
    
    @keyframes pulseBorder {
      0%, 100% { border-color: rgba(255,255,255,0.1); box-shadow: inset 0 0 30px rgba(255,255,255,0.1), 0 10px 50px rgba(0,0,0,0.4); }
      50% { border-color: rgba(59,130,246,0.4); box-shadow: inset 0 0 40px rgba(59,130,246,0.2), 0 15px 70px rgba(0,0,0,0.5); }
    }

    @keyframes titleGlow {
      from { text-shadow: 0 0 30px var(--accent), 0 0 60px rgba(255,255,255,0.9), 0 6px 12px rgba(0,0,0,0.7); }
      to { text-shadow: 0 0 45px var(--accent), 0 0 90px rgba(255,255,255,1), 0 9px 18px rgba(0,0,0,0.9), 0 0 120px var(--accent); }
    }

    /* 🏢 HEADER — FONDO AZUL OSCURO DIFUMINADO */
    header {
      background: rgba(10, 31, 58, 0.85);
      backdrop-filter: blur(32px);
      -webkit-backdrop-filter: blur(32px);
      border-bottom: 1px solid rgba(255,255,255,0.15);
      box-shadow: 
        0 8px 32px var(--shadow-deep),
        0 0 25px var(--glass-glow);
      color: var(--text-light);
      padding: 1.6rem 3rem;
      display: flex;
      justify-content: center;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      border-radius: 0 0 36px 36px;
      transform: translateY(-80px);
      opacity: 0;
      animation: headerEntrance 1s cubic-bezier(0.16, 1, 0.3, 1) forwards 0.8s;
    }

    @keyframes headerEntrance {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 1.6rem;
    }

    .logo-container img {
      height: 70px;
      border-radius: 18px;
      background: rgba(255,255,255,0.1);
      padding: 10px;
      box-shadow: 
        0 10px 40px rgba(0,0,0,0.3),
        0 0 30px var(--glass-glow);
      transition: var(--transition);
      border: 1px solid rgba(255,255,255,0.25);
      backdrop-filter: blur(10px);
    }

    .logo-container img:hover {
      transform: scale(1.1) rotate(8deg);
      box-shadow: 
        0 15px 50px rgba(0,0,0,0.4),
        0 0 50px var(--accent);
    }

    /* 🎯 TEXTO DEL HEADER — BRILLANTE, CON BORDE Y SOMBRA PROFUNDA */
    header h1 {
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-weight: 700;
      font-size: 2.4rem;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: white;
      text-shadow: 
        0 0 12px rgba(255, 255, 255, 0.9),
        0 0 24px var(--accent),
        3px 3px 6px rgba(0, 0, 0, 0.9),
        -2px -2px 4px rgba(0, 0, 0, 0.7);
      -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.95);
      text-stroke: 1.5px rgba(255, 255, 255, 0.95);
      position: relative;
      z-index: 2;
    }

    header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      border-radius: 2px;
      animation: shine 2.5s infinite;
      opacity: 0.8;
    }

    @keyframes shine {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    /* MAIN — SIN CAMBIOS */
    main {
      max-width: 1320px;
      margin: 6rem auto 7rem;
      padding: 0 3rem;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 3.5rem;
      perspective: 1500px;
    }

    .card {
      position: relative;
      height: 380px;
      border-radius: 32px;
      overflow: hidden;
      text-decoration: none;
      color: var(--text-light);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      transform-style: preserve-3d;
      transition: var(--transition);
      background: var(--glass-bg);
      backdrop-filter: blur(28px);
      -webkit-backdrop-filter: blur(28px);
      border: 1px solid var(--glass-border);
      box-shadow: 
        0 16px 64px rgba(10,46,92,0.15),
        inset 0 0 20px rgba(255,255,255,0.1);
      transform: rotateX(12deg) scale(0.96);
      opacity: 0;
      animation: cardFloatIn 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    .card:nth-child(1) { animation-delay: 0.3s; }
    .card:nth-child(2) { animation-delay: 0.5s; }
    .card:nth-child(3) { animation-delay: 0.7s; }
    .card:nth-child(4) { animation-delay: 0.9s; }

    @keyframes cardFloatIn {
      0% {
        opacity: 0;
        transform: translateY(60px) rotateX(20deg) scale(0.9);
      }
      100% {
        opacity: 1;
        transform: rotateX(0deg) scale(1);
      }
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      filter: blur(2px) brightness(0.8) saturate(1.2) contrast(1.1);
      z-index: -1;
      transition: var(--transition);
    }

    .card:hover {
      transform: translateY(-18px) scale(1.04) rotateX(5deg) rotateY(3deg);
      box-shadow: 
        0 28px 80px var(--shadow-deep),
        0 0 50px var(--glass-glow),
        inset 0 0 25px rgba(255,255,255,0.2);
      border-color: var(--accent);
    }

    .card:hover::before {
      transform: scale(1.1) rotate(3deg);
      filter: blur(3px) brightness(0.9) saturate(1.3) contrast(1.3);
    }

    .card-content {
      padding: 2.8rem;
      background: linear-gradient(0deg, rgba(10,46,92,0.7) 0%, rgba(10,46,92,0.3) 100%);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      z-index: 2;
      text-align: center;
      transition: var(--transition);
      border-radius: 0 0 32px 32px;
    }

    .card-content i {
      font-size: 4.5rem;
      margin-bottom: 1.6rem;
      color: #e0f2ff;
      transition: var(--transition);
      display: block;
      text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
    }

    .card:hover .card-content i {
      transform: translateY(-10px) scale(1.15) rotate(10deg);
      color: #ffffff;
      text-shadow: 0 0 40px var(--accent);
    }

    .card-content h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.1rem;
      font-weight: 700;
      margin-bottom: 0.8rem;
      letter-spacing: 1.2px;
      text-shadow: 0 4px 8px rgba(0,0,0,0.3);
      background: linear-gradient(to top, #ffffff, #cce6ff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .card-content p {
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-size: 1.15rem;
      font-weight: 500;
      opacity: 0.95;
      line-height: 1.7;
      letter-spacing: 0.3px;
    }

    /* Backgrounds con efecto glassmorphism 3D mejorado */
    .soporte::before { 
      background-image: url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=800&q=80'); 
      filter: blur(2px) brightness(0.8) saturate(1.2) contrast(1.1);
    }
    
    .mantenimiento::before { 
      background-image: url('https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=800&q=80'); 
      filter: blur(2px) brightness(0.8) saturate(1.2) contrast(1.1);
    }
    
    .generales::before { 
      background-image: url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=800&q=80'); 
      filter: blur(2px) brightness(0.8) saturate(1.2) contrast(1.1);
    }
    
    .servicios::before { 
      background-image: url('https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=800&q=80'); 
      filter: blur(2px) brightness(0.8) saturate(1.2) contrast(1.1);
    }

    /* Capa de glassmorphism adicional para mayor contraste */
    .card::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 50%, rgba(10,46,92,0.2) 100%);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 0;
      opacity: 0;
      transition: var(--transition);
    }

    .card:hover::after {
      opacity: 1;
    }

    /* 👣 FOOTER — FONDO AZUL OSCURO DIFUMINADO */
    footer {
      background: rgba(10, 31, 58, 0.9);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-top: 1px solid rgba(255,255,255,0.15);
      color: var(--text-light);
      text-align: center;
      padding: 2.2rem;
      font-family: 'Barlow Semi Condensed', sans-serif;
      font-weight: 500;
      letter-spacing: 0.8px;
      position: fixed;
      bottom: 0;
      width: 100%;
      z-index: 99;
      box-shadow: 
        0 -8px 32px var(--shadow-deep),
        0 0 25px var(--glass-glow);
      transform: translateY(70px);
      opacity: 0;
      transition: transform 0.9s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.9s;
    }

    footer.visible {
      transform: translateY(0);
      opacity: 1;
    }

    footer:hover {
      box-shadow: 
        0 -12px 60px var(--shadow-deep),
        0 0 40px var(--glass-glow);
    }

    /* Responsive */
    @media (max-width: 768px) {
      main {
        grid-template-columns: 1fr;
        padding: 0 1.5rem;
        margin: 5rem auto 6rem;
      }
      header {
        padding: 1.3rem 2rem;
        border-radius: 0 0 28px 28px;
      }
      header h1 { font-size: 1.9rem; }
      .logo-container img { height: 58px; }
      .card { height: 360px; }
      .card-content h2 { font-size: 1.8rem; }
      .preload-title { font-size: 2.3rem; }
      .preload-logo { width: 160px; margin-bottom: 2.5rem; }
      .preload-subtitle { font-size: 1.2rem; }
    }

    @media (max-width: 480px) {
      .preload-title { font-size: 1.9rem; letter-spacing: 2px; }
      .preload-logo { width: 140px; margin-bottom: 2rem; }
      .preload-subtitle { font-size: 1.1rem; }
    }

    /* Sonido toggle */
    .sound-toggle {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 1000;
      background: rgba(10, 31, 58, 0.7);
      color: var(--text-light);
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.2);
      box-shadow: 
        0 6px 24px rgba(0,0,0,0.3),
        0 0 20px var(--glass-glow);
      transition: var(--transition);
    }

    .sound-toggle:hover {
      background: rgba(59, 130, 246, 0.3);
      transform: scale(1.15) rotate(15deg);
      box-shadow: 
        0 10px 40px rgba(0,0,0,0.4),
        0 0 40px var(--accent);
    }
  </style>
</head>
<body>

  <!-- ✨ PRELOADER MEJORADO — CON MÁS CONTRASTE Y EFECTO GLASSMORPHISM INTENSO -->
  <div id="preloader">
    <div class="preload-content">
      <img src="logo2.png" alt="Logo BacroCorp" class="preload-logo">
      <h1 class="preload-title">PLATAFORMA INTELIGENTE BACROCORP</h1>
      <p class="preload-subtitle">Iniciando entorno de gestión empresarial 360°<br>Preparando interfaz con tecnología de misión crítica...</p>
    </div>
  </div>

  <!-- Toggle de sonido -->
  <div class="sound-toggle" id="soundToggle">
    <i class="fa-solid fa-volume-high" id="soundIcon"></i>
  </div>

  <!-- 🏢 HEADER SIN BOTÓN INICIO — SOLO LOGO + TÍTULO -->
  <header>
    <div class="logo-container">
      <img src="logo2.png" alt="Logo BacroCorp" id="logoHeader">
      <h1>PLATAFORMA DE SOPORTE EMPRESARIAL</h1>
    </div>
  </header>

  <main>
    <a href="http://desarollo-bacros/TicketBacros/Loginti.php" class="card soporte" aria-label="Soporte técnico">
      <div class="card-content">
        <i class="fa-solid fa-headset"></i>
        <h2>Soporte Técnico</h2>
        <p>Levanta tu ticket para soporte inmediato</p>
      </div>
    </a>

    <a href="http://192.168.100.95/TicketBacros/M/website-menu-05/index1.html" class="card mantenimiento" aria-label="Mantenimiento vehicular">
      <div class="card-content">
        <i class="fa-solid fa-car-side"></i>
        <h2>Mantenimiento Vehicular</h2>
        <p>Solicita mantenimiento programado</p>
      </div>
    </a>

    <a href="http://192.168.100.95/TicketBacros/MenSG.php" class="card generales" aria-label="Servicios Generales">
      <div class="card-content">
        <i class="fa-solid fa-people-carry-box"></i>
        <h2>Servicios Generales</h2>
        <p>Gestiona solicitudes administrativas y logísticas</p>
      </div>
    </a>

    <a href="#" class="card servicios" aria-label="Otros servicios">
      <div class="card-content">
        <i class="fa-solid fa-briefcase"></i>
        <h2>Más Servicios</h2>
        <p>Próximamente disponible</p>
      </div>
    </a>
  </main>

  <footer id="footer">
    &copy; 2025 BACROCORP — Plataforma Inteligente de Soporte Empresarial | Gestión 360° en Tiempo Real
  </footer>

  <script>
    // Preloader con impacto
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.getElementById('preloader').classList.add('fade-out');
      }, 3200);
    });

    // Footer animado
    window.addEventListener('scroll', () => {
      const footer = document.getElementById('footer');
      if (window.scrollY > 200) {
        footer.classList.add('visible');
      } else {
        footer.classList.remove('visible');
      }
    });

    // Audio (opcional)
    let soundEnabled = true;
    let audioContext;

    function initAudio() {
      audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    function playClickSound() {
      if (!soundEnabled || !audioContext) return;
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();
      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);
      oscillator.frequency.value = 580;
      gainNode.gain.value = 0.08;
      oscillator.start();
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.25);
      oscillator.stop(audioContext.currentTime + 0.25);
    }

    document.addEventListener('click', () => {
      if (!audioContext) initAudio();
    }, { once: true });

    document.querySelectorAll('.card').forEach(el => {
      el.addEventListener('click', playClickSound);
    });

    // Toggle sonido
    document.getElementById('soundToggle').addEventListener('click', () => {
      soundEnabled = !soundEnabled;
      const icon = document.getElementById('soundIcon');
      if (soundEnabled) {
        icon.classList.replace('fa-volume-xmark', 'fa-volume-high');
      } else {
        icon.classList.replace('fa-volume-high', 'fa-volume-xmark');
      }
    });

    // Hover 3D en cards
    document.querySelectorAll('.card').forEach(card => {
      card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-18px) scale(1.04) rotateX(5deg) rotateY(3deg)';
      });
      card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) scale(1) rotateX(0) rotateY(0)';
      });
    });
  </script>

</body>
</html>
