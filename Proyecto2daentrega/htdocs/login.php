<?php
session_start();
require "conexion.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Por favor completá todos los campos.";
    } else {
        $sql = "SELECT id_miembro, nombre, email, password, es_miembro, admin FROM miembro WHERE email = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $u = $res->fetch_assoc();
            $stored = (string)$u["password"];
            $ok = false;

            if (password_verify($password, $stored)) {
                $ok = true;
                if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $conexion->prepare("UPDATE miembro SET password = ? WHERE id_miembro = ?");
                    $up->bind_param("si", $newHash, $u["id_miembro"]);
                    $up->execute();
                }
            } else {
                $looksHashed = preg_match('/^\$2[aby]\$|\$argon2(id|i)\$/', $stored) === 1;
                if (!$looksHashed && hash_equals($stored, $password)) {
                    $ok = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $conexion->prepare("UPDATE miembro SET password = ? WHERE id_miembro = ?");
                    $up->bind_param("si", $newHash, $u["id_miembro"]);
                    $up->execute();
                }
            }

            if ($ok) {
                if ((int)$u["es_miembro"] !== 1 && (int)$u["admin"] !== 1) {
                    $error = "Tu cuenta no tiene permisos para ingresar.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION["id"] = (int)$u["id_miembro"];
                    $_SESSION["usuario"] = $u["nombre"] ?: $u["email"];
                    $_SESSION["email"] = $u["email"];
                    $_SESSION["es_miembro"] = (int)$u["es_miembro"];
                    $_SESSION["admin"] = (int)$u["admin"];

                    header("Location: pagos.php");
                    exit();
                }
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <title>Iniciar Sesión</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #1a2433 0%, #2b5f87 100%);
      color: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      overflow: hidden;
      user-select: auto; /* O anulas la herencia en inputs */
    -webkit-user-select: auto;
    caret-color: transparent;
    }

    header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 64px;
      background: rgba(26, 36, 51, 0.85);
      backdrop-filter: blur(8px);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      z-index: 10;
    }

    .logo img {
      height: 54px;
      width: auto;
      border-radius: 50%;
      object-fit: cover;
      filter: drop-shadow(0 0 4px rgba(255,255,255,0.15));
    }

    .login-box {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 40px 30px;
      width: 360px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
      text-align: center;
      animation: fadeIn 0.6s ease-out;

    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      margin-bottom: 24px;
      font-weight: 600;
      color: #ffffff;
    }

    input {
      width: 93%;
      padding: 12px 14px;
      margin: 10px 0;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      color: #fff;
      font-size: 15px;
      outline: none;
      transition: all 0.3s ease;
      user-select: auto; 
    -webkit-user-select: auto;
  
    }

    input:focus {
      border-color: #6ebbe9;
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 0 3px rgba(110, 187, 233, 0.3);
    }

    button {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      background: linear-gradient(135deg, #6ebbe9, #2b5f87);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      user-select: none; 
    -webkit-user-select: none; 
}
  

    button:hover {
      background: linear-gradient(135deg, #2b5f87, #6ebbe9);
      transform: scale(1.03);
      box-shadow: 0 4px 16px rgba(110, 187, 233, 0.4);
    }

    .Volver {
      display: inline-block;
      margin-top: 16px;
      color: #edf1f6;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    .Volver:hover {
      color: #6ebbe9;
    }

    .error {
      background: rgba(185, 28, 28, 0.85);
      color: #fff;
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 16px;
      font-size: 14px;
      box-shadow: 0 2px 10px rgba(185, 28, 28, 0.3);
      animation: shake 0.3s ease;
    }

    @keyframes shake {
      10%, 90% { transform: translateX(-1px); }
      20%, 80% { transform: translateX(2px); }
      30%, 50%, 70% { transform: translateX(-4px); }
      40%, 60% { transform: translateX(4px); }
    }

 

  </style>
</head>
<body>
  <header>
    <a href="index.html" class="logo">
      <img src="logo.jpeg" alt="Logo Cooperativa">
    </a>
  </header>

  <div class="login-box">
    <h2>Iniciar Sesión</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="email" name="email" placeholder="Correo electrónico" required />
      <input type="password" name="password" placeholder="Contraseña" required />
      <button type="submit">Ingresar</button>
      <a class="Volver" href="index.html">Volver</a>
    </form>
    
  </div>
</body>
</html>
