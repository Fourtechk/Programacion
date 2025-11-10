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
    $fecha_nacimiento = $_POST["fecha_nacimiento"]; // 🟢 nuevo campo

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
        // 🟢 Insertar nuevo miembro con fecha de nacimiento
        $stmt = $conexion->prepare("INSERT INTO miembro (nombre, email, password, fecha_nacimiento) VALUES (?, ?, ?, ?)");
        if ($stmt === false) { die("Error INSERT miembro: " . $conexion->error); }
        $stmt->bind_param("ssss", $nombre, $email, $password, $fecha_nacimiento);
        if (!$stmt->execute()) {
            die("Error al registrar miembro: " . $stmt->error);
        }
        $id_miembro = $stmt->insert_id;
        $stmt->close();
    }
$stmt = $conexion->prepare("INSERT INTO calendario (titulo, descripcion, fecha_evento, creado_por) VALUES (?, ?, ?, ?)");
if ($stmt === false) { die("Error INSERT calendario: " . $conexion->error); }

$titulo = "Cumpleaños de $nombre";
$descripcion = "Fecha de nacimiento de $nombre";
$stmt->bind_param("sssi", $titulo, $descripcion, $fecha_nacimiento, $id_miembro);
$stmt->execute();
$stmt->close();
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

 form select option {
    /* Usamos un color oscuro que contraste con el texto blanco */
    background-color: #1a2433; /* El mismo color del fondo principal/header */
    color: #fff;
}

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
<input type="text" name="nombre" maxlength="150" required> 

<label>Email</label>
<input type="email" name="email" minlength="11" maxlength="100" required> 

<label>Contraseña</label> 
<input type="password" name="password" minlength="4" maxlength="255" required> 

<label for="fecha_nacimiento">Fecha de nacimiento:</label>
<input type="date" id="fecha_nacimiento" name="fecha_nacimiento" max="<?php echo date('Y-m-d'); ?>" required>

<label>Cantidad de menores a cargo:</label> 
<input type="number" name="cantidad_menores" min="0" max="20" required>

<label>Trabajo actual:</label> 
<input type="text" name="trabajo" maxlength="150" required> 

<label>Tipo de contrato:</label> 
<select name="tipo_contrato" required> 
  <option value="">Seleccione una opción</option> 
  <option value="permanente">Permanente</option> <option value="eventual">Eventual</option> 
  <option value="informal">Informal</option> 
</select>

<label>Ingresos nominales (personales):</label>
<input type="number" step="0.01" name="ingresos_nominales" min="0" max="1000000" required>

<label>Ingresos familiares totales:</label> 
<input type="number" step="0.01" name="ingresos_familiares" min="0" max="5000000"> 

<label>Observación de salud:</label> 
<textarea name="observacion_salud" maxlength="500"></textarea> 

<label>Constitución del núcleo familiar:</label> 
<textarea name="constitucion_familiar" minlength="1" maxlength="500" required></textarea> 

<label>Vivienda actual:</label> 
<input type="text" name="vivienda_actual" minlength="1" maxlength="500">

<label>Gasto mensual de vivienda:</label> 
<input type="number" step="0.01" name="gasto_vivienda" min="0" max="500000"> 

<label>Nivel educativo alcanzado:</label> 
<input type="text" name="nivel_educativo" maxlength="500"> 

<label>Hijos estudiando (cantidad):</label>
<input type="number" name="hijos_estudiando" min="0" max="10">

<label>Patrimonio (terreno, casa, vehículo, etc.):</label> 
<textarea name="patrimonio" maxlength="500"></textarea> 

<label>Disponibilidad para ayuda mutua:</label> 
<textarea name="disponibilidad_ayuda" maxlength="500"></textarea> 

<label>Motivación para ingresar a la cooperativa:</label> 
<textarea name="motivacion" maxlength="500"></textarea>

<label>Presentado por:</label> 
<input type="text" name="presentado_por" maxlength="500"> 

<label>Referencia personal (nombre y teléfono):</label> 
<input type="text" name="referencia_contacto" maxlength="500">
                
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

<script>
document.addEventListener("DOMContentLoaded", function() {
  const form = document.querySelector("form");

  form.addEventListener("submit", function(e) {
    const campos = form.querySelectorAll("input, textarea, select");
    for (let campo of campos) {
      if (campo.hasAttribute("required") && campo.value.trim() === "") {
        alert("El campo '" + (campo.previousElementSibling?.innerText || campo.name) + "' no puede quedar vacío.");
        campo.focus();
        e.preventDefault();
        return;
      }
    }

    const nombre = document.querySelector('input[name="nombre"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value.trim();
    const menores = document.querySelector('input[name="cantidad_menores"]').value.trim();
    const trabajo = document.querySelector('input[name="trabajo"]').value.trim();
    const tipoContrato = document.querySelector('select[name="tipo_contrato"]').value.trim();
    const ingresosNom = document.querySelector('input[name="ingresos_nominales"]').value.trim();
    const ingresosFam = document.querySelector('input[name="ingresos_familiares"]').value.trim();
    const observacion = document.querySelector('textarea[name="observacion_salud"]').value.trim();
    const constitucion = document.querySelector('textarea[name="constitucion_familiar"]').value.trim();
    const vivienda = document.querySelector('input[name="vivienda_actual"]').value.trim();
    const gasto = document.querySelector('input[name="gasto_vivienda"]').value.trim();
    const nivelEduc = document.querySelector('input[name="nivel_educativo"]').value.trim();
    const hijosEst = document.querySelector('input[name="hijos_estudiando"]').value.trim();
    const patrimonio = document.querySelector('textarea[name="patrimonio"]').value.trim();
    const ayuda = document.querySelector('textarea[name="disponibilidad_ayuda"]').value.trim();
    const motivacion = document.querySelector('textarea[name="motivacion"]').value.trim();
    const presentado = document.querySelector('input[name="presentado_por"]').value.trim();
    const referencia = document.querySelector('input[name="referencia_contacto"]').value.trim();

    const letras = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
    const numeros = /^\d+$/;
    const decimal = /^\d+(\.\d{1,2})?$/;
    const emailValido = /^[^@\s]+@(gmail.com|hotmail.com)$/i;
    const passValido = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;


    // --- VALIDACIONES DE DOMINIO (LÍMITES) ---
    
// Nombre Completo (Min. 1, Máx. 150)
if (nombre.length < 1 || nombre.length > 150) {
    alert("El nombre debe tener entre 1 y 150 caracteres.");
    e.preventDefault();
    return;
}
if (!letras.test(nombre)) {
  alert("El nombre solo puede contener letras y espacios.");
  e.preventDefault();
  return;
}

// Email (Min. 11, Máx. 100)
if (email.length < 11 || email.length > 100) {
    alert("El email debe tener entre 11 y 100 caracteres.");
    e.preventDefault();
    return;
}
if (!emailValido.test(email)) {
  alert("El dominio de email no es válido. Debe ser @gmail.com o @hotmail.com.");
  e.preventDefault();
  return;
}

// Contraseña (Min. 4, Máx. 255)
if (password.length < 4 || password.length > 255) {
    alert("La contraseña debe tener entre 4 y 255 caracteres.");
    e.preventDefault();
    return;
}
// MANTENER la validación de passValido si quieres que sea alfanumérica y mín. 6:
/*
if (!passValido.test(password)) {
  alert("La contraseña debe tener al menos 6 caracteres con letras y números.");
  e.preventDefault();
  return;
}
*/

// Cantidad de menores (Min. 0, Máx. 20)
if (!numeros.test(menores) || parseInt(menores) < 0 || parseInt(menores) > 20) {
  alert("La cantidad de menores debe ser un número entero positivo entre 0 y 20.");
  e.preventDefault();
  return;
}

// Trabajo actual (Min. 1, Máx. 150)
if (trabajo.length < 1 || trabajo.length > 150) {
    alert("El campo 'Trabajo actual' debe tener entre 1 y 150 caracteres.");
    e.preventDefault();
    return;
}

// Ingresos nominales (Max. $1,000,000)
if (!decimal.test(ingresosNom) || parseFloat(ingresosNom) > 1000000) {
  alert("El ingreso nominal debe ser un número válido (máx. 2 decimales) y no exceder $1,000,000.");
  e.preventDefault();
  return;
}

// Ingresos familiares (Max. $5,000,000)
if (ingresosFam !== "" && (!decimal.test(ingresosFam) || parseFloat(ingresosFam) > 5000000)) {
  alert("El ingreso familiar debe ser un número válido (máx. 2 decimales) y no exceder $5,000,000.");
  e.preventDefault();
  return;
}

// Constitución Familiar (Min. 1, Máx. 500)
if (constitucion.length < 1 || constitucion.length > 500) {
    alert("La 'Constitución del núcleo familiar' debe tener entre 1 y 500 caracteres.");
    e.preventDefault();
    return;
}

// Vivienda actual (Min. 1, Máx. 500)
if (vivienda.length < 1 || vivienda.length > 500) {
    alert("El campo 'Vivienda actual' debe tener entre 1 y 500 caracteres.");
    e.preventDefault();
    return;
}

// Gasto vivienda (Max. $500,000)
if (gasto !== "" && (!decimal.test(gasto) || parseFloat(gasto) > 500000)) {
    alert("El gasto de vivienda debe ser un número válido (máx. 2 decimales) y no exceder $500,000.");
    e.preventDefault();
    return;
}

// Hijos estudiando (Min. 0, Máx. 10)
if (hijosEst !== "" && (!numeros.test(hijosEst) || parseInt(hijosEst) < 0 || parseInt(hijosEst) > 10)) {
    alert("El campo 'Hijos estudiando' debe ser un número entero entre 0 y 10.");
    e.preventDefault();
    return;
}

// Validación de Máx. 500 para Textareas/Text (los demás campos de texto libre)
if (observacion.length > 500 || nivelEduc.length > 500 || patrimonio.length > 500 || ayuda.length > 500 || motivacion.length > 500 || presentado.length > 500 || referencia.length > 500) {
    alert("Uno o más campos de texto libre exceden el límite de 500 caracteres.");
    e.preventDefault();
    return;
}

// MANTENER estas validaciones de tipo de texto (si quieres limitar a solo letras)
if (!letras.test(nivelEduc) && nivelEduc !== "") {
    alert("El campo 'Nivel educativo' solo debe contener letras.");
    e.preventDefault();
    return;
}

if (!letras.test(presentado) && presentado !== "") {
    alert("El campo 'Presentado por' solo puede contener letras.");
    e.preventDefault();
    return;
}

if (referencia !== "" && !/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s0-9\-\+]+$/.test(referencia)) {
    alert("El campo 'Referencia personal' solo puede contener letras, números y signos + o -.");
    e.preventDefault();
    return;
}

    if (!letras.test(nombre)) {
      alert("El nombre solo puede contener letras y espacios.");
      e.preventDefault();
      return;
    }

   
   

    if (!numeros.test(menores)) {
      alert("La cantidad de menores debe ser un número entero positivo.");
      e.preventDefault();
      return;
    }

    if (!letras.test(trabajo)) {
      alert("El campo 'Trabajo actual' solo debe contener letras.");
      e.preventDefault();
      return;
    }

    if (!decimal.test(ingresosNom)) {
      alert("El ingreso nominal debe ser un número válido (máx. 2 decimales).");
      e.preventDefault();
      return;
    }

    if (ingresosFam !== "" && !decimal.test(ingresosFam)) {
      alert("El ingreso familiar debe ser un número válido (máx. 2 decimales).");
      e.preventDefault();
      return;
    }

    if (!letras.test(nivelEduc)) {
      alert("El campo 'Nivel educativo' solo debe contener letras.");
      e.preventDefault();
      return;
    }

    if (hijosEst !== "" && !numeros.test(hijosEst)) {
      alert("El campo 'Hijos estudiando' debe ser numérico.");
      e.preventDefault();
      return;
    }

    if (!letras.test(presentado) && presentado !== "") {
      alert("El campo 'Presentado por' solo puede contener letras.");
      e.preventDefault();
      return;
    }

    if (referencia !== "" && !/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s0-9\-\+]+$/.test(referencia)) {
      alert("El campo 'Referencia personal' solo puede contener letras, números y signos + o -.");
      e.preventDefault();
      return;
    }
  });

  // 🔸 Restricciones en tiempo real
  document.querySelectorAll('input[name="nombre"], input[name="trabajo"], input[name="nivel_educativo"], input[name="presentado_por"]').forEach(campo => {
    campo.addEventListener("input", e => e.target.value = e.target.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, ""));
  });
  document.querySelectorAll('input[type="number"]').forEach(campo => {
    campo.addEventListener("input", e => e.target.value = e.target.value.replace(/[^0-9.]/g, ""));
  });
});
</script>

</body> 
</html>