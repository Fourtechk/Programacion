<?php
session_start();

$conexion = new mysqli($host, $user, $pass, $db, $port);

if (!isset($_SESSION['id'])) {
    die("Error: Debes iniciar sesión para postularte.");
}

$mensaje = "";
$id_miembro = intval($_SESSION['id']);

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST)) {
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
        id_miembro, cantidad_menores, trabajo, tipo_contrato, ingresos_nominales, ingresos_familiares,
        observacion_salud, constitucion_familiar, vivienda_actual, gasto_vivienda, nivel_educativo,
        hijos_estudiando, patrimonio, disponibilidad_ayuda, motivacion, presentado_por, referencia_contacto,
        estado_po
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')");

    $stmt->bind_param("iissddsssssisssss",
        $id_miembro, $cantidad_menores, $trabajo, $tipo_contrato, $ingresos_nominales, $ingresos_familiares,
        $observacion_salud, $constitucion_familiar, $vivienda_actual, $gasto_vivienda, $nivel_educativo,
        $hijos_estudiando, $patrimonio, $disponibilidad_ayuda, $motivacion, $presentado_por, $referencia_contacto
    );

    if ($stmt->execute()) {
    header("Location: index.html?mensaje=ok");
    exit;
}



    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulario de Postulación</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #333;
    }

    header {
      background-color: rgba(44, 62, 80, 0.9);
      height: 60px;
      display: flex;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    header img {
      height: 54px;
      border-radius: 50%;
    }

    main {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px 15px;
      text-align: center;
    }

    .form-wrapper {
      text-align: center;
      max-width: 700px;
      margin: 0 auto;
      color: white;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.6);
    }

    .titulo-principal {
      font-size: 28px;
      margin-bottom: 15px;
      color: white;
    }

    .titulo-principal span {
      color: #a2d4ff;
      font-weight: 600;
    }

    .btn-info {
      display: block;
      background-color: #ecf0f1;
      padding: 12px;
      border-radius: 10px;
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 25px;
    }

    .form-box {
      background-color: rgba(255, 255, 255, 0.95);
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      color: #2c3e50;
      text-align: left;
    }

    form label {
      font-weight: 600;
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
    }

    form input,
    form select,
    form textarea {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    form textarea {
      resize: vertical;
      min-height: 60px;
    }

    .postularme {
      background-color: #2c3e50;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 18px;
      font-weight: 600;
      margin-top: 20px;
      width: 100%;
      transition: 0.3s;
    }

    .postularme:hover {
      background-color: #1a252f;
      transform: scale(1.03);
    }

    @media (max-width: 600px) {
      .titulo-principal {
        font-size: 22px;
      }

      .form-box {
        padding: 20px;
      }
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
      <h2 class="titulo-principal">Formulario de <span>Postulación</span></h2>

      <?php if($mensaje !== ""): ?>
        <p class="btn-info"><?= htmlspecialchars($mensaje) ?></p>
      <?php endif; ?>

      <div class="form-box">
        <form method="POST">
          <label>Cantidad de menores a cargo:</label>
          <input type="number" name="cantidad_menores" required>

          <label>Trabajo actual:</label>
          <input type="text" name="trabajo" required>

          <label>Tipo de contrato:</label>
          <select name="tipo_contrato" required>
            <option value="permanente">Permanente</option>
            <option value="eventual">Eventual</option>
            <option value="informal">Informal</option>
          </select>

          <label>Ingresos nominales (personales):</label>
          <input type="number" step="0.01" name="ingresos_nominales" required>

          <label>Ingresos familiares totales:</label>
          <input type="number" step="0.01" name="ingresos_familiares">

          <label>Observación de salud:</label>
          <textarea name="observacion_salud"></textarea>

          <label>Constitución del núcleo familiar:</label>
          <textarea name="constitucion_familiar" required></textarea>

          <label>Vivienda actual:</label>
          <input type="text" name="vivienda_actual">

          <label>Gasto mensual de vivienda:</label>
          <input type="number" step="0.01" name="gasto_vivienda">

          <label>Nivel educativo alcanzado:</label>
          <input type="text" name="nivel_educativo">

          <label>Hijos estudiando (cantidad):</label>
          <input type="number" name="hijos_estudiando">

          <label>Patrimonio (terreno, casa, vehículo, etc.):</label>
          <textarea name="patrimonio"></textarea>

          <label>Disponibilidad para ayuda mutua:</label>
          <textarea name="disponibilidad_ayuda"></textarea>

          <label>Motivación para ingresar a la cooperativa:</label>
          <textarea name="motivacion"></textarea>

          <label>Presentado por:</label>
          <input type="text" name="presentado_por">

          <label>Referencia personal (nombre y teléfono):</label>
          <input type="text" name="referencia_contacto">

          <button type="submit" class="postularme">Enviar Postulación</button>
        </form>

        <a href="landingpage.html">Volver</a>
      </div>
    </div>
  </main>

</body>
</html>
