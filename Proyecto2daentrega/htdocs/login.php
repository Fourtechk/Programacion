<?php
session_start();
require "conexion.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Por favor completá todos los campos.";
    } else {
        $sql = "SELECT id_miembro, nombre, email, password, es_miembro, aprobado, admin FROM miembro WHERE email = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $u = $res->fetch_assoc();
            $stored = (string)$u["password"];
            $ok = false;

            // Verificación de contraseña
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
                if ((int)$u["aprobado"] !== 1) {
                    $error = "Tu cuenta aún no fue aprobada por el administrador.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION["id"] = (int)$u["id_miembro"];
                    $_SESSION["usuario"] = $u["nombre"] ?: $u["email"];
                    $_SESSION["email"] = $u["email"];
                    $_SESSION["es_miembro"] = (int)$u["es_miembro"];
                    $_SESSION["admin"] = (int)$u["admin"];

                    // Redirección según tipo de usuario
                    // Redirección según tipo de usuario
        if ((int)$u["admin"] === 1) {
          header("Location: pagos.php");
        } elseif ((int)$u["es_miembro"] === 1) {
          header("Location: pagos.php");
        } else {
          header("Location: pagina.php");
        }
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
    body {
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: #f1f5f9;
      font-family: "Poppins", sans-serif;
      display: flex; align-items: center; justify-content: center;
      height: 100vh; margin: 0;
    }
    .login-box {
      background: #334155;
      padding: 30px;
      border-radius: 12px;
      width: 360px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.25);
      text-align: center;
    }
    h2 { margin: 0 0 16px; }
    input {
      width: 93%; padding: 12px; margin: 8px 0;
      border: none; border-radius: 8px;
    }
    button {
      width: 100%; padding: 12px; margin-top: 8px;
      background: #3b82f6; border: none; border-radius: 8px;
      color: #fff; font-weight: 700; cursor: pointer;
    }
    button:hover { background: #2563eb; }
    .error {
      background: #b91c1c; color: #fff;
      padding: 10px; border-radius: 8px;
      margin-bottom: 12px; font-size: 14px;
    }
    header {
      background-color: rgba(44, 62, 80, 0.9);
      height: 60px;
      width: 97.9%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      position: absolute;
      top: 0px;
      z-index: 10;
    }
    .logo {
      display: flex;
      align-items: center;
      height: 100%;
      padding: 5px 0;
    }
    .logo img {
      height: 54px;
      width: auto;
      object-fit: contain;
      filter: drop-shadow(1px 1px 2px rgba(0, 0, 0, 0.4));
      border-radius: 50%;
    }
    .Volver{
      top:10px;
      text-decoration:none;
      display:block;
      position:relative;
      color:white;
    }
  </style>
</head>
<body>
  <header>
    <a href="landingpage.html" class="logo">
      <img src="logo.jpeg" alt="Logo Cooperativa">
    </a>
    <div class="top-bar"></div>
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
      <a class="Volver" href="landingpage.html">volver</a>
    </form>
  </div>
</body>
</html>
