<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Corporativo</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      display: flex;
      height: 100vh;
      align-items: center;
      justify-content: center;
      background: linear-gradient(-45deg, #f8f8f8, #004d7a, #f8f8f8, #006699);
      background-size: 400% 400%;
      animation: gradientMove 15s ease infinite;
    }

    @keyframes gradientMove {
      0% {
        background-position: 0% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }

    .login-container {
      background: rgba(255, 255, 255, 0.9);
      padding: 40px 30px;
      border-radius: 10px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      text-align: center;
      width: 100%;
      max-width: 400px;
      backdrop-filter: blur(8px);
    }

    .logo img {
      width: 100px;
      margin-bottom: 20px;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    input {
      width: 100%;
      padding: 12px 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #004d7a;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background-color: #006699;
    }

    #message {
      margin-top: 15px;
      color: red;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="logo">
      <img src="Logo.png" alt="Logo Corporativo" />
    </div>
    <h2>Iniciar Sesión</h2>
    <form id="loginForm">
      <input type="text" id="username" placeholder="Usuario" required />
      <input type="password" id="password" placeholder="Contraseña" required />
      <button type="submit">Entrar</button>
    </form>
    <p id="message"></p>
  </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", function (e) {
      e.preventDefault();

      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value.trim();
      const message = document.getElementById("message");

      const validUser = "admin";
      const validPass = "1234";

      if (username === validUser && password === validPass) {
        localStorage.setItem("loggedIn", "true");
        message.style.color = "green";
        message.textContent = "¡Login exitoso! Redirigiendo...";
        setTimeout(() => {
          window.location.href = "http://192.168.100.95/TicketBacros/Asignarticket.php";
        }, 1500);
      } else {
        message.style.color = "red";
        message.textContent = "Usuario o contraseña incorrectos.";
      }
    });
  </script>
</body>
</html>
