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

    // Verificar si el email ya existe
    $stmt = $conexion->prepare("SELECT id_miembro FROM miembro WHERE email = ?");
    if ($stmt === false) { die("Error SELECT miembro: " . $conexion->error); }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id_existente);
    $stmt->fetch();
    $stmt->close();

    if ($id_existente) {
        $id_miembro = $id_existente;
    } else {
        // Insertar nuevo miembro
        $stmt = $conexion->prepare("INSERT INTO miembro (nombre, email, password) VALUES (?, ?, ?)");
        if ($stmt === false) { die("Error INSERT miembro: " . $conexion->error); }
        $stmt->bind_param("sss", $nombre, $email, $password);
        if (!$stmt->execute()) {
            die("Error al registrar miembro: " . $stmt->error);
        }
        $id_miembro = $stmt->insert_id;
        $stmt->close();
    }

    // Campos de postulación
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

    // Insertar la postulación
    $sql = "INSERT INTO postulacion (
        id_miembro, cantidad_menores, trabajo, tipo_contrato,
        ingresos_nominales, ingresos_familiares, observacion_salud,
        constitucion_familiar, vivienda_actual, gasto_vivienda,
        nivel_educativo, hijos_estudiando, patrimonio,
        disponibilidad_ayuda, motivacion, presentado_por, referencia_contacto
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    if ($stmt === false) { die("Error INSERT postulacion: " . $conexion->error); }

    $types = "isssddssssissssss";
    $stmt->bind_param($types,
        $id_miembro, $cantidad_menores, $trabajo, $tipo_contrato,
        $ingresos_nominales, $ingresos_familiares, $observacion_salud,
        $constitucion_familiar, $vivienda_actual, $gasto_vivienda,
        $nivel_educativo, $hijos_estudiando, $patrimonio,
        $disponibilidad_ayuda, $motivacion, $presentado_por, $referencia_contacto
    );

    if ($stmt->execute()) {
        $mensaje = "Tu postulacion fue enviada con éxito, gracias por confiar en nosotros.";
    } else {
        $mensaje = "Error al guardar la postulación: " . $stmt->error;
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
 @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap'); 

   * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }

    body { background: linear-gradient(135deg, #1a2433 0%, #2b5f87 100%);
           color: #f1f5f9; 
           min-height: 100vh; 
           display: flex; 
           flex-direction: column; } 

    header { background: rgba(26, 36, 51, 0.85);
             backdrop-filter: blur(8px); 
             height: 64px; 
             display: flex; 
             align-items: center; 
             justify-content: start;
             padding: 0 20px; 
             box-shadow: 0 4px 12px rgba(0,0,0,0.3); } 

    header img { height: 54px; 
                 border-radius: 50%; 
                 object-fit: cover; 
                 filter: drop-shadow(0 0 4px rgba(255,255,255,0.15)); } 

    main { flex: 1; display: flex; 
           justify-content: center; 
           align-items: flex-start; 
           padding: 40px 15px; } 
    .form-wrapper { width: 100%; 
                    max-width: 800px; 
                    background: rgba(255,255,255,0.05); 
                    border-radius: 20px; padding: 40px; 
                    box-shadow: 0 8px 32px rgba(0,0,0,0.4); 
                    backdrop-filter: blur(12px); 
                    color: #ffffff; 
                    animation: fadeIn 0.6s ease-out; } 

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } 
    to { opacity: 1; transform: translateY(0); } } 

    .titulo-principal { font-size: 28px; 
                        text-align: center; 
                        margin-bottom: 24px; 
                        font-weight: 600; 
                        color: #ffffff; } 

    .titulo-principal span { color: #6ebbe9; } 

    .btn-info { display: block; 
                text-align: center; 
                background: rgba(110, 187, 233, 0.2); 
                padding: 12px; 
                border-radius: 10px; 
                color: #e0f2ff; 
                font-weight: 600; 
                margin-bottom: 25px; } 

    .form-box { background: rgba(255, 255, 255, 0.05); 
                border: 1px solid rgba(255,255,255,0.1); 
                padding: 30px; 
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); 
                color: #f1f5f9; }

    form label { font-weight: 500; 
                 display: block; 
                 margin-top: 15px; 
                 margin-bottom: 5px; 
                 color: #dbeafe; } 

    form input, form select, form textarea { width: 100%; 
                                             padding: 12px; 
                                             border-radius: 10px; 
                                             border: 1px solid rgba(255,255,255,0.2); 
                                             background: rgba(255,255,255,0.1); 
                                             color: #fff; 
                                             font-size: 14px; 
                                             outline: none; 
                                             transition: 0.3s ease; } 

    form input:focus, form select:focus, form textarea:focus { border-color: #6ebbe9; 
                                                               background: rgba(255,255,255,0.15);
                                                              box-shadow: 0 0 0 3px rgba(110,187,233,0.3); } 

    form textarea { resize: vertical; 
                    min-height: 70px; } 

    .postularme { background: linear-gradient(135deg, #6ebbe9, #2b5f87); 
                  color: white;
                  border: none; 
                  padding: 14px; 
                  border-radius: 10px; 
                  cursor: pointer; 
                  font-size: 17px; 
                  font-weight: 600; 
                  margin-top: 20px; 
                  width: 100%; 
                  transition: 0.3s ease; } 

    .postularme:hover { background: linear-gradient(135deg, #2b5f87, #6ebbe9); 
                        transform: scale(1.03); 
                        box-shadow: 0 4px 16px rgba(110, 187, 233, 0.4); } 

    a { display: inline-block; 
        margin-top: 20px; 
        color: #edf1f6; 
        text-decoration: none; 
        font-weight: 500; 
        text-align: center; 
        width: 100%; 
        transition: color 0.3s; }

    a:hover { color: #6ebbe9; }

    @media (max-width: 700px) { 
      .form-wrapper { padding: 20px; } 
      .titulo-principal { font-size: 22px; } 
      .form-box { padding: 20px; } }
  </style>
</head>

<body>
  <header> 
  <img src="logo.jpeg" alt="Logo Cooperativa"> 
</header>
 <main> <div class="form-wrapper"> 
  <h2 class="titulo-principal">Formulario de <span>Postulación</span></h2> 
  <div class="form-box"> 
    <form method="POST"> 
      <label>Nombre Completo</label>
       <input type="text" name="nombre"> 

       <label>Email</label>
        <input type="email" name="email"> 

       <label>Contraseña</label> 
       <input type="password" name="password"> 

       <label>Cantidad de menores a cargo:</label> 
       <input type="number" name="cantidad_menores" required>

        <label>Trabajo actual:</label> 
        <input type="text" name="trabajo" required> 

        <label>Tipo de contrato:</label> 
        <select name="tipo_contrato" required> 
        <option value="permanente">Permanente</option> <option value="eventual">Eventual</option> 
        <option value="informal">Informal</option> </select>

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
                
        <button type="submit" class="postularme">Enviar Postulación</button> </form>
               <a href="index.html">Volver</a>
      </div>
   </div>
</main> 

<?php if (!empty($mensaje)): ?>
<div id="toast" class="toast"><?php echo htmlspecialchars($mensaje); ?></div>
<script>
  const toast = document.getElementById("toast");
  toast.classList.add("show");

  setTimeout(() => {
    window.location.href = "index.html";
  }, 1500);
</script>

<style>
.toast {
  visibility: hidden;
  min-width: 320px;
  background: linear-gradient(135deg, #2b5f87, #6ebbe9);
  color: #fff;
  text-align: center;
  border-radius: 12px;
  padding: 14px 20px;
  position: fixed;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  font-weight: 600;
  font-size: 16px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  opacity: 0;
  transition: top 0.4s ease, opacity 0.4s ease;
}
.toast.show {
  visibility: visible;
  top: 25px;
  opacity: 1;
}
</style>
<?php endif; ?>


</body> 
</html>