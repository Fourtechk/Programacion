<?php
$conexion = new mysqli($host, $user, $pass, $db, $port);
$mensaje = "";

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Verificar si el email ya existe
    $stmt = $conexion->prepare("SELECT id_miembro FROM miembro WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $mensaje = "El email ya está registrado. Intenta con otro.";
    } else {
        // Insertar nuevo miembro
        $stmt = $conexion->prepare("INSERT INTO miembro (nombre, email, password, aprobado) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $nombre, $email, $password);
        if ($stmt->execute()) {
            $mensaje = "Registro exitoso. Espera la aprobación del administrador.";
        } else {
            $mensaje = "Error: " . $stmt->error;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro</title>
  <style>
    body {
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      font-family: "Poppins", sans-serif;
      display: flex; justify-content: center; align-items: center;
      min-height: 100vh; margin: 0;
      font-family: "Poppins", sans-serif;
      display: flex; justify-content: center; align-items: center;
      min-height: 100vh; margin: 0;
    }
    .form-wrapper { text-align: center; color: white; }
    .titulo-principal { font-size: 26px; margin-bottom: 15px; }
    .form-box {
      background-color: #fff; padding: 25px; border-radius: 12px;
      max-width: 400px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      color: #2c3e50;
      height: 240px;
      
    }
    .form-box input {
      width: 93%; padding: 12px; margin: 8px 0;
      border: 1px solid #ccc; border-radius: 8px;
    }
    .btn {
      background-color: #2c3e50; color: white;
      border: none; padding: 12px; width: 100%;
      border-radius: 8px; cursor: pointer; font-weight: 600;
      margin-top: 10px; margin-bottom: 10px;
    }
    .btn:hover { background-color: #1a252f; }
    .mensaje { padding: 2px; font-size: 14px; color: green;
    background-color: #d4edda;  border-radius: 8px;
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

.volver {
  text-decoration: none;
  color :black;

}

</style>



</head>
<body>
  <header>
      <img src="logo.jpeg" alt="Logo Cooperativa">
    </a>
    
      
      
      
  
  </header>
  <main>
    <div class="form-wrapper">
      <h2 class="titulo-principal">Formulario de Registro</h2>
       <?php if ($mensaje) echo "<p class='mensaje'>$mensaje</p>"; ?>
      <div class="form-box">
        <form method="post">
          <input type="text" name="nombre" placeholder="Nombre completo" required>
          <input type="email" name="email" placeholder="Email" required>
          <input type="password" name="password" placeholder="Contraseña" required>
          <button type="submit" class="btn">Registrarse</button> 
          <a class="volver" href="index.html">volver</a>
            </form>
      </div>
    </div>
  </main>
</body>
</html>
