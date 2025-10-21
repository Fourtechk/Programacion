<?php
session_start();
require_once "conexion.php";
$conexion = new mysqli($host, $user, $pass, $db, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST)) {
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Primero verificamos si el email ya existe en miembro
    $stmt = $conexion->prepare("SELECT id_miembro FROM miembro WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id_miembro_existente);
    $stmt->fetch();
    $stmt->close();

    if ($id_miembro_existente) {
        // Si ya existe el usuario, usamos su id
        $id_miembro = $id_miembro_existente;
    } else {
        // Si no existe, lo registramos
        $stmt = $conexion->prepare("INSERT INTO miembro (nombre, email, password, aprobado) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $nombre, $email, $password);
        if ($stmt->execute()) {
            $id_miembro = $stmt->insert_id;
        } else {
            die("Error al registrar miembro: " . $stmt->error);
        }
        $stmt->close();
    }

    // Ahora insertamos la postulación
    $cantidad_menores = intval($_POST["cantidad_menores"] ?? 0);
    $trabajo = $_POST["trabajo"] ?? '';
    $tipo_contrato = $_POST["tipo_contrato"] ?? '';
    $ingresos_nominales = floatval($_POST["ingresos_nominales"] ?? 0);
    $ingresos_familiares = floatval($_POST["ingresos_familiares"] ?? 0);
    $observacion_salud = $_POST["observacion_salud"] ?? '';
    $constitucion_familiar = $_POST["constitucion_familiar"] ?? '';
    $vivienda_actual = $_POST["vivienda_actual"] ?? '';     
    $gasto_vivienda = floatval($_POST["gasto_vivienda"] ?? 0);
    $nivel_educativo = $_POST["nivel_educativo"] ?? '';
    $hijos_estudiando = intval($_POST["hijos_estudiando"] ?? 0);
    $patrimonio = $_POST["patrimonio"] ?? '';
    $disponibilidad_ayuda = $_POST["disponibilidad_ayuda"] ?? '';
    $motivacion = $_POST["motivacion"] ?? '';
    $presentado_por = $_POST["presentado_por"] ?? '';
    $referencia_contacto = $_POST["referencia_contacto"] ?? '';

    $stmt = $conexion->prepare("INSERT INTO postulacion (
        id_miembro, nombre, email, password, cantidad_menores, trabajo, tipo_contrato,
        ingresos_nominales, ingresos_familiares, observacion_salud, constitucion_familiar,
        vivienda_actual, gasto_vivienda, nivel_educativo, hijos_estudiando, patrimonio,
        disponibilidad_ayuda, motivacion, presentado_por, referencia_contacto, estado_po
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')");

    $stmt->bind_param("isssissddssssissssss",
        $id_miembro, $nombre, $email, $password, $cantidad_menores, $trabajo, $tipo_contrato,
        $ingresos_nominales, $ingresos_familiares, $observacion_salud, $constitucion_familiar,
        $vivienda_actual, $gasto_vivienda, $nivel_educativo, $hijos_estudiando, $patrimonio,
        $disponibilidad_ayuda, $motivacion, $presentado_por, $referencia_contacto
    );

    if ($stmt->execute()) {
        $mensaje = "Tu registro y postulación fueron enviados correctamente.";
    } else {
        $mensaje = "Error al enviar la postulación: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro y Postulación</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* mismo estilo que tu formulario original de postulación */
    body {
      background: linear-gradient(135deg, #1a2433 0%, #2b5f87 100%);
      color: #f1f5f9;
      font-family: "Poppins", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
    }
    header {
      background: rgba(26, 36, 51, 0.85);
      height: 64px;
      display: flex;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    header img {
      height: 54px;
      border-radius: 50%;
    }
    main {
      flex: 1;
      display: flex;
      justify-content: center;
      padding: 40px 15px;
    }
    .form-wrapper {
      width: 100%;
      max-width: 800px;
      background: rgba(255,255,255,0.05);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
      backdrop-filter: blur(12px);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #fff;
    }
    .mensaje {
      text-align: center;
      background: #d4edda;
      color: #1b4332;
      padding: 10px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    label { display:block; margin-top:10px; }
    input, textarea, select {
      width:100%;
      padding:10px;
      border-radius:10px;
      border:none;
      background:rgba(255,255,255,0.1);
      color:white;
      margin-top:5px;
    }
    .postularme {
      background: linear-gradient(135deg, #6ebbe9, #2b5f87);
      border:none;
      padding:14px;
      border-radius:10px;
      color:white;
      font-size:16px;
      cursor:pointer;
      margin-top:15px;
    }
    .postularme:hover {
      transform: scale(1.03);
    }
    a { color:#fff; text-align:center; display:block; margin-top:10px; }
  </style>
</head>
<body>
  <header><img src="logo.jpeg" alt="Logo"></header>
  <main>
    <div class="form-wrapper">
      <h2>Registro y Postulación</h2>
      <?php if ($mensaje) echo "<p class='mensaje'>$mensaje</p>"; ?>
      <form method="POST">
        <label>Nombre Completo</label>
        <input type="text" name="nombre" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>

        <label>Cantidad de menores a cargo</label>
        <input type="number" name="cantidad_menores">

        <label>Trabajo actual</label>
        <input type="text" name="trabajo">

        <label>Tipo de contrato</label>
        <select name="tipo_contrato">
          <option value="permanente">Permanente</option>
          <option value="eventual">Eventual</option>
          <option value="informal">Informal</option>
        </select>

        <label>Ingresos nominales</label>
        <input type="number" step="0.01" name="ingresos_nominales">

        <label>Ingresos familiares</label>
        <input type="number" step="0.01" name="ingresos_familiares">

        <label>Observación de salud</label>
        <textarea name="observacion_salud"></textarea>

        <label>Constitución familiar</label>
        <textarea name="constitucion_familiar"></textarea>

        <label>Vivienda actual</label>
        <input type="text" name="vivienda_actual">

        <label>Gasto mensual de vivienda</label>
        <input type="number" step="0.01" name="gasto_vivienda">

        <label>Nivel educativo</label>
        <input type="text" name="nivel_educativo">

        <label>Hijos estudiando</label>
        <input type="number" name="hijos_estudiando">

        <label>Patrimonio</label>
        <textarea name="patrimonio"></textarea>

        <label>Disponibilidad para ayuda mutua</label>
        <textarea name="disponibilidad_ayuda"></textarea>

        <label>Motivación</label>
        <textarea name="motivacion"></textarea>

        <label>Presentado por</label>
        <input type="text" name="presentado_por">

        <label>Referencia contacto</label>
        <input type="text" name="referencia_contacto">

        <button type="submit" class="postularme">Enviar Postulación</button>
      </form>
      <a href="index.html">Volver</a>
    </div>
  </main>
</body>
</html>
