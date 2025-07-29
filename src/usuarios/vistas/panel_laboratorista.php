<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Laboratorista Animado</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body, html { height: 100%; margin: 0; padding: 0; overflow: hidden;}
    .bubbles {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }
    .bubble {
      position: absolute;
      bottom: -120px;
      border-radius: 50%;
      opacity: 0.4;
      animation: rise 12s linear infinite;
      background: radial-gradient(circle at 30% 30%, #00cec9 60%, #00b894 100%);
      filter: blur(1px);
    }
    @keyframes rise {
      0% {
        transform: translateY(0) scale(1);
        opacity: 0.3;
      }
      85% {
        opacity: 0.6;
      }
      100% {
        transform: translateY(-110vh) scale(1.2);
        opacity: 0;
      }
    }
    .lab-panel-outer {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      position: relative;
      z-index: 2;
    }
    .lab-panel-card2 {
      background: rgba(255,255,255,0.98);
      border-radius: 2.5rem;
      box-shadow: 0 16px 40px rgba(39, 174, 96, 0.15), 0 2px 8px rgba(39, 174, 96, 0.10);
      padding: 3rem 2.5rem 2.5rem 2.5rem;
      max-width: 440px;
      width: 100%;
      text-align: center;
      position: relative;
    }
    .lab-icon-spin {
      background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
      border-radius: 50%;
      width: 110px;
      height: 110px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: -70px auto 1.7rem auto;
      box-shadow: 0 8px 32px rgba(0, 150, 136, 0.20);
      border: 4px solid #fff;
      animation: pulseGlow 2.2s infinite;
      position: relative;
    }
    .microscope-svg {
      width: 64px;
      height: 64px;
      animation: rotateMicroscope 2.5s linear infinite;
    }
    @keyframes pulseGlow {
      0%, 100% { box-shadow: 0 0 0 0 rgba(0, 150, 136, 0.10);}
      50% { box-shadow: 0 0 32px 16px rgba(0, 150, 136, 0.11);}
    }
    @keyframes rotateMicroscope {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
    .lab-btn-gradient {
      background: linear-gradient(90deg, #00b894 0%, #00cec9 100%);
      color: #fff !important;
      border: none;
      border-radius: 2rem;
      padding: 0.8rem 2.8rem;
      font-size: 1.15rem;
      font-weight: 600;
      box-shadow: 0 4px 16px rgba(0, 150, 136, 0.11);
      transition: transform 0.18s, box-shadow 0.18s;
      margin-top: 1.5rem;
      letter-spacing: 0.5px;
    }
    .lab-btn-gradient:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 12px 32px rgba(0, 150, 136, 0.18);
      background: linear-gradient(90deg, #00cec9 0%, #00b894 100%);
    }
    .lab-panel-card2 h2 {
      color: #00b894;
      font-weight: 700;
      margin-bottom: 0.8rem;
      letter-spacing: 0.5px;
      font-size: 2.1rem;
    }
    .lab-panel-card2 p {
      color: #636e72;
      font-size: 1.08rem;
      margin-bottom: 0.8rem;
    }
    .lab-panel-card2 .welcome {
      font-size: 1.25rem;
      color: #0984e3;
      font-weight: 500;
      margin-bottom: 1.2rem;
      letter-spacing: 0.3px;
      text-shadow: 0 2px 8px rgba(0, 206, 201, 0.07);
    }
  </style>
</head>
<body>
  <!-- Fondo animado de burbujas -->
  <div class="bubbles"></div>
  <script>
    // Generador de burbujas animadas
    const bubbles = document.querySelector('.bubbles');
    for(let i=0; i<18; i++) {
      const bubble = document.createElement('div');
      bubble.classList.add('bubble');
      bubble.style.left = Math.random()*100 + 'vw';
      bubble.style.width = bubble.style.height = (Math.random()*40 + 30) + 'px';
      bubble.style.animationDelay = (Math.random()*12) + 's';
      bubbles.appendChild(bubble);
    }
  </script>
  <div class="lab-panel-outer">
    <div class="lab-panel-card2">
      <div class="lab-icon-spin">
        <!-- SVG Microscopio animado -->
        <svg class="microscope-svg" viewBox="0 0 64 64" fill="none">
          <circle cx="32" cy="32" r="30" fill="#fff" opacity="0.12"/>
          <rect x="28" y="43" width="8" height="15" rx="4" fill="#00b894"/>
          <rect x="19" y="52" width="26" height="6" rx="3" fill="#00cec9"/>
          <rect x="35" y="10" width="8" height="26" rx="4" transform="rotate(45 35 10)" fill="#00b894"/>
          <rect x="28" y="6" width="8" height="22" rx="4" fill="#0984e3"/>
          <circle cx="32" cy="32" r="6" fill="#00b894" stroke="#00cec9" stroke-width="2"/>
          <circle cx="32" cy="32" r="3" fill="#fff" stroke="#00cec9" stroke-width="1"/>
        </svg>
      </div>
      <div class="welcome">Panel exclusivo para Laboratoristas</div>
      <h2>¡Bienvenido!</h2>
      <p>
        Aquí puedes gestionar y editar los resultados de exámenes de laboratorio de manera eficiente y segura.<br>
        Utiliza el siguiente acceso para comenzar a trabajar con los resultados.
      </p>
      <a href="dashboard.php?vista=cotizaciones" class="btn lab-btn-gradient">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-clipboard2-check me-2" viewBox="0 0 16 16">
          <path d="M7.5 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm.5-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2.5 5a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5H6v1A1.5 1.5 0 0 0 7.5 6h1A1.5 1.5 0 0 0 10 4.5v-1h1a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5h-7zm7-11A1.5 1.5 0 0 0 12 1H4a1.5 1.5 0 0 0-1.5 1.5v11A1.5 1.5 0 0 0 4 15h8a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 12 1z"/>
          <path d="M10.854 7.146a.5.5 0 0 0-.708 0L8 9.293 7.354 8.646a.5.5 0 1 0-.708.708l1 1a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0 0-.708z"/>
        </svg>
        Editar Resultados
      </a>
    </div>
  </div>
</body>
</html>
