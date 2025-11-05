<?php
session_start();

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

require "conexion.php";

// Verificar login
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION["id"];
$mensajeHoras = "";
$mensajeFoto = "";

// =========================================================================
// === CONSULTA USUARIO ===
$sql = "
    SELECT 
        m.es_miembro, 
        m.admin, 
        m.nombre AS nombre_completo, 
        m.email, 
        m.id_unidad, 
        m.fecha_nacimiento,
        m.fecha_ingreso AS fecha_socio,
        m.foto_perfil AS foto_perfil_url
    FROM 
        miembro m
    WHERE 
        m.id_miembro = ?
";
$stmtUser = $conexion->prepare($sql);
$stmtUser->bind_param("i", $id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser ? $resultUser->fetch_assoc() : null;

if (!$user || ($user["es_miembro"] != 1 && $user["admin"] != 1)) {
    header("Location: pagina.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'listar_foro') {

    // Evitar romper la carga normal del sitio
    if (
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: text/html; charset=utf-8');

        // 🔹 Cargar los mensajes del foro con el nombre del autor
        $mensajes = $conexion->query("
            SELECT f.id, f.titulo, f.mensaje, f.fecha, m.nombre
            FROM foro f
            JOIN miembro m ON f.usuario_id = m.id_miembro
            ORDER BY f.fecha DESC
        ");

        if ($mensajes && $mensajes->num_rows > 0) {
            while ($row = $mensajes->fetch_assoc()) {
                echo "<div class='card mb-3 p-3'>";
                echo "<h5>" . htmlspecialchars($row['titulo']) . "</h5>";
                echo "<p>" . nl2br(htmlspecialchars($row['mensaje'])) . "</p>";
                echo "<small>Por " . htmlspecialchars($row['nombre']) . " el " . $row['fecha'] . "</small>";

                // 🔹 Respuestas al mensaje (tabla foro_respuestas)
                $respuestas = $conexion->prepare("
                    SELECT r.respuesta, r.fecha, m.nombre
                    FROM foro_respuestas r
                    JOIN miembro m ON r.usuario_id = m.id_miembro
                    WHERE r.foro_id = ?
                    ORDER BY r.fecha ASC
                ");
                $respuestas->bind_param("i", $row['id']);
                $respuestas->execute();
                $res = $respuestas->get_result();

                echo "<div class='mt-3 ms-3 border-start ps-3'>";
                if ($res && $res->num_rows > 0) {
                    while ($r = $res->fetch_assoc()) {
                        echo "<div class='respuesta mb-2'>";
                        echo "<strong>" . htmlspecialchars($r['nombre']) . ":</strong> ";
                        echo nl2br(htmlspecialchars($r['respuesta'])) . "<br>";
                        echo "<small>" . $r['fecha'] . "</small>";
                        echo "</div>";
                    }
                } else {
                    echo "<p class='text-muted'><em>No hay respuestas aún.</em></p>";
                }
                echo "</div></div>";
            }
        } else {
            echo "<p><em>No hay mensajes en el foro aún.</em></p>";
        }

        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["publicar_foro"], $_POST["csrf"])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");

    $titulo = trim($_POST['titulo']);
    $mensaje = trim($_POST['mensaje']);

    if ($titulo !== "" && $mensaje !== "") {
        $stmt = $conexion->prepare("INSERT INTO foro (usuario_id, titulo, mensaje) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id, $titulo, $mensaje);
        $stmt->execute();

        header("Location: pagos.php?foro=ok");
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["responder_foro"], $_POST["csrf"])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");

    $foro_id = intval($_POST['foro_id']);
    $respuesta = trim($_POST['respuesta']);

    if ($foro_id > 0 && $respuesta !== "") {
        $stmt = $conexion->prepare("
            INSERT INTO foro_respuestas (foro_id, usuario_id, respuesta)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $foro_id, $id, $respuesta);
        $stmt->execute();

        header("Location: pagos.php?foro_respuesta=ok");
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_foro'], $_POST['foro_id'])) {
    $foro_id = intval($_POST['foro_id']);
    $stmt = $conexion->prepare("DELETE FROM foro WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $stmt->bind_param("iii", $foro_id, $id, $user['admin']);
    $stmt->execute();
    header("Location: pagos.php#seccion-foro");
    exit;
}

// 🔹 Editar mensaje del foro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_foro'], $_POST['foro_id'])) {
    $foro_id = intval($_POST['foro_id']);
    $foro_data = $conexion->prepare("SELECT titulo, mensaje FROM foro WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $foro_data->bind_param("iii", $foro_id, $id, $user['admin']);
    $foro_data->execute();
    $foro_res = $foro_data->get_result();
    if ($foro_res->num_rows > 0) {
        $foro_edit = $foro_res->fetch_assoc();
        $_SESSION['editar_foro'] = ['id' => $foro_id, 'titulo' => $foro_edit['titulo'], 'mensaje' => $foro_edit['mensaje']];
    }
    header("Location: pagos.php#seccion-foro");
    exit;
}

// 🔹 Guardar edición del mensaje del foro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_edicion_foro'])) {
    $foro_id = intval($_POST['foro_id']);
    $titulo = trim($_POST['titulo']);
    $mensaje = trim($_POST['mensaje']);
    $stmt = $conexion->prepare("UPDATE foro SET titulo = ?, mensaje = ? WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $stmt->bind_param("ssiii", $titulo, $mensaje, $foro_id, $id, $user['admin']);
    $stmt->execute();
    unset($_SESSION['editar_foro']);
    header("Location: pagos.php#seccion-foro");
    exit;
}

// 🔹 Eliminar respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_respuesta'], $_POST['respuesta_id'])) {
    $respuesta_id = intval($_POST['respuesta_id']);
    $stmt = $conexion->prepare("DELETE FROM foro_respuestas WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $stmt->bind_param("iii", $respuesta_id, $id, $user['admin']);
    $stmt->execute();
    header("Location: pagos.php#seccion-foro");
    exit;
}

// 🔹 Editar respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_respuesta'], $_POST['respuesta_id'])) {
    $respuesta_id = intval($_POST['respuesta_id']);
    $res_data = $conexion->prepare("SELECT respuesta FROM foro_respuestas WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $res_data->bind_param("iii", $respuesta_id, $id, $user['admin']);
    $res_data->execute();
    $res_res = $res_data->get_result();
    if ($res_res->num_rows > 0) {
        $res_edit = $res_res->fetch_assoc();
        $_SESSION['editar_respuesta'] = ['id' => $respuesta_id, 'respuesta' => $res_edit['respuesta']];
    }
    header("Location: pagos.php#seccion-foro");
    exit;
}

// 🔹 Guardar edición de respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_edicion_respuesta'])) {
    $respuesta_id = intval($_POST['respuesta_id']);
    $texto = trim($_POST['respuesta']);
    $stmt = $conexion->prepare("UPDATE foro_respuestas SET respuesta = ? WHERE id = ? AND (usuario_id = ? OR ? = 1)");
    $stmt->bind_param("siii", $texto, $respuesta_id, $id, $user['admin']);
    $stmt->execute();
    unset($_SESSION['editar_respuesta']);
    header("Location: pagos.php#seccion-foro");
    exit;
}
// =========================================================================
// === AGREGAR EVENTO (SOLO ADMIN) ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["agregar_evento"], $_POST["csrf"])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");
    if ($user['admin'] != 1) die("No autorizado");

    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_evento = $_POST['fecha_evento'] ?? '';

    if ($titulo !== "" && $fecha_evento !== "") {
        $insertEvento = $conexion->prepare("
            INSERT INTO calendario (titulo, descripcion, fecha_evento, creado_por)
            VALUES (?, ?, ?, ?)
        ");
        $insertEvento->bind_param("sssi", $titulo, $descripcion, $fecha_evento, $id);
        $insertEvento->execute();
        header("Location: pagos.php?evento=ok");
        exit;
    }
}

if (isset($_GET['action'])) {
  header('Content-Type: application/json');

  // 📅 Listar eventos
  if ($_GET['action'] === 'listar_eventos') {
    $res = $conexion->query("SELECT id_evento AS id, titulo AS title, descripcion AS description, fecha_evento AS start FROM calendario");
    $eventos = [];
    while ($row = $res->fetch_assoc()) $eventos[] = $row;
    echo json_encode($eventos);
    exit;
  }

  // ✏️ Editar evento
  if ($_GET['action'] === 'editar_evento') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);
    $titulo = $conexion->real_escape_string($data['titulo']);
    $descripcion = $conexion->real_escape_string($data['descripcion']);
    $fecha = $conexion->real_escape_string($data['fecha']);
    $conexion->query("UPDATE calendario SET titulo='$titulo', descripcion='$descripcion', fecha_evento='$fecha' WHERE id_evento=$id");
    echo json_encode(['ok' => $conexion->affected_rows > 0]);
    exit;
  }

  // 🗑️ Eliminar evento
  if ($_GET['action'] === 'eliminar_evento') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id']);
    $conexion->query("DELETE FROM calendario WHERE id_evento=$id");
    echo json_encode(['ok' => $conexion->affected_rows > 0]);
    exit;
  }
}

// =========================================================================
// === SUBIDA Y ACTUALIZACIÓN DE FOTO DE PERFIL ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["subir_foto_perfil"], $_POST["csrf"])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");

    if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] === 0) {
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES["foto_perfil"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $mensajeFoto = "❌ Solo se permiten archivos JPG, JPEG o PNG.";
        } elseif ($_FILES["foto_perfil"]["size"] > 2000000) {
            $mensajeFoto = "❌ El archivo es demasiado grande (máximo 2MB).";
        } else {
            if (!is_dir("perfiles")) mkdir("perfiles", 0755);
            $nombre_archivo = "perfil_{$id}_".time().".".$ext;
            $ruta = "perfiles/" . $nombre_archivo;

            if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $ruta)) {
                if (!empty($user['foto_perfil_url']) && file_exists($user['foto_perfil_url'])) unlink($user['foto_perfil_url']);
                $updateFoto = $conexion->prepare("UPDATE miembro SET foto_perfil = ? WHERE id_miembro = ?");
                $updateFoto->bind_param("si", $ruta, $id);
                $updateFoto->execute();
                $mensajeFoto = "Foto subida correctamente. ✅";
                $user['foto_perfil_url'] = $ruta;
                header("Location: pagos.php?foto=ok");
                exit;
            } else {
                $mensajeFoto = "❌ Error al mover el archivo subido.";
            }
        }
    } else {
        $mensajeFoto = "❌ Error al subir el archivo: " . $_FILES["foto_perfil"]["error"];
    }
}

// =========================================================================
// === VERIFICAR HORAS DEL USUARIO ===
$checkHoras = $conexion->prepare("SELECT id_horas FROM horas WHERE id_miembro = ?");
$checkHoras->bind_param("i", $id);
$checkHoras->execute();
$resHoras = $checkHoras->get_result();

if ($resHoras->num_rows === 0) {
    $insertHoras = $conexion->prepare("INSERT INTO horas (id_miembro, semanales_req, cumplidas, horas_pendientes, justificativos) VALUES (?, 10, 0, 0, '')");
    $insertHoras->bind_param("i", $id);
    $insertHoras->execute();
}

// =========================================================================
// === TRAER COMPROBANTES ===
$stmt = $conexion->prepare("SELECT * FROM pago WHERE id_miembro=? ORDER BY fecha_p DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comprobantes = $stmt->get_result();

// =========================================================================
// === TRAER HORAS ===
$queryHoras = $conexion->prepare("SELECT * FROM horas WHERE id_miembro = ?");
$queryHoras->bind_param("i", $id);
$queryHoras->execute();
$resultHoras = $queryHoras->get_result();
$horas = $resultHoras ? $resultHoras->fetch_assoc() : [];

// =========================================================================
// === CAMBIO DE ESTADO DE PAGO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['estado'], $_POST['csrf']) && !isset($_POST['guardar_asistencia'])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");

    $id_pago = intval($_POST['id']);
    $estado = $_POST['estado'];
    $valid = ['aprobado', 'rechazado'];
    if (!in_array($estado, $valid, true)) die("Estado inválido");

    $stmt = $conexion->prepare("UPDATE pago SET estado_pa = ? WHERE id_pago = ?");
    $stmt->bind_param("si", $estado, $id_pago);
    $stmt->execute();

    header("Location: pagos.php?estado=" . urlencode($estado));
    exit;
}

// =========================================================================
// === REGISTRO DE ASISTENCIA ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_asistencia"], $_POST["csrf"])) {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Token CSRF inválido");

    $asistio = intval($_POST["asistio"]);
    $registro = "";

    if ($asistio === 1) {
        $horasRealizadas = intval($_POST["horas_realizadas"]);
        $actividad = trim($_POST["actividad"]);
        $registro = "asistio=1;horas={$horasRealizadas};actividad={$actividad}";

        if ($horasRealizadas > 0) {
            $updateHoras = $conexion->prepare("UPDATE horas SET horas_pendientes = horas_pendientes + ? WHERE id_miembro = ?");
            $updateHoras->bind_param("ii", $horasRealizadas, $id);
            $updateHoras->execute();
        }

    } else {
        $justificativo = trim($_POST["justificativo_texto"]);
        if (isset($_FILES["justificativo_file"]) && $_FILES["justificativo_file"]["error"] === 0) {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES["justificativo_file"]["name"], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                if (!is_dir("justificativos")) mkdir("justificativos", 0755);
                $nombreArchivo = "justificativo_{$id}_".time().".".$ext;
                $rutaArchivo = "justificativos/" . $nombreArchivo;
                move_uploaded_file($_FILES["justificativo_file"]["tmp_name"], $rutaArchivo);
                $justificativo .= " | Archivo: $rutaArchivo";
            }
        }
        $registro = "asistio=0;justificativo={$justificativo}";
    }

    $stmtInsertAsistencia = $conexion->prepare("INSERT INTO asistencia (id_miembro, registro) VALUES (?, ?)");
    $stmtInsertAsistencia->bind_param("is", $id, $registro);
    $stmtInsertAsistencia->execute();

    header("Location: pagos.php?asistencia=ok");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
     <title>Pagos y Horas - Cooperativa</title>
      <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
      <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>


    <style>
/* ======== ESTILO GENERAL ======== */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: "Poppins", sans-serif;
}

body {
  background: linear-gradient(135deg, #1a2433, #2b5f87);
  background-attachment: fixed;
  min-height: 100vh;
  padding: 0; /* Quitamos padding aquí para controlarlo en main-container */
  color: #edf1f6;
  display: flex; /* Para el layout */
}

/* ======== HEADER (TOP BAR) ======== */
header {
  background: rgba(26, 36, 51, 0.9);
  backdrop-filter: blur(10px);
  height: 70px;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  justify-content: flex-start; 
  align-items: center;
  padding: 0 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 10;
}


.header-logo-container {
    display: flex;
    align-items: center;
    gap: 10px; 
}


.header-logo {
    height: 50px; 
    width: auto;
    border-radius:100%;
   
    image-rendering: optimizeQuality; 
}


.header-title {
    color: #ffffffff; /
    font-size: 30px;
    font-weight: 700;
    letter-spacing: 1px;
  
    text-shadow: 0 0 5px rgba(110, 187, 233, 0.7); 
}

/* ------------------------------------- */
/* ======== NAVEGACIÓN LATERAL ======== */
/* ------------------------------------- */

.sidebar {
    width: 250px;
    background: rgba(26, 36, 51, 0.95);
    padding-top: 100px; /* Espacio para el header */
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    z-index: 5;
}

.lateral {
    list-style: none;
    padding: 0;
}

.lateral li {
    padding: 15px 25px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.3s ease, color 0.3s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.lateral li:hover {
    background: rgba(110, 187, 233, 0.1);
}

.lateral li.activa-menu {
    background: #6ebbe9;
    color: #1a2433;
    font-weight: 700;
    box-shadow: inset 5px 0 0 #2b5f87;
}

/* ------------------------------------- */
/* ======== CONTENIDO PRINCIPAL ======== */
/* ------------------------------------- */

.main-container {
    margin-left: 250px; /* Mismo ancho que el sidebar */
    padding: 100px 20px 40px; /* Padding superior para el header */
    width: calc(100% - 250px);
}

.form-wrapper {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto; /* Centrar el contenido dentro del main-container */
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.seccion {
    /* Ocultar todas las secciones por defecto */
    display: none;
}

.seccion.activa {
    /* Mostrar solo la sección activa */
    display: block;
    animation: fadeIn 0.8s ease-in-out;
}

/* Resto de estilos del contenido que ya tenías */

/* ======== TÍTULOS ======== */
h1, h2, h3 {
    text-align: center;
    color: #ffffffff;
    text-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
    margin-bottom: 20px;
    font-weight: 600;
}

/* ======== CONTENEDORES ======== */
.form-box {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
}

/* ======== FORMULARIOS ======== */
input, textarea, select {
    width: 100%;
    padding: 10px;
    margin-top: 8px;
    margin-bottom: 16px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: #edf1f6;
    resize: vertical;
}

input[type="radio"] {
    width: auto;
    margin-right: 8px;
}

label {
    font-weight: 500;
    color: #edf1f6;
}

/* ======== BOTONES ======== */
button, .postularme {
    background: #6ebbe9;
    color: #1a2433;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
}

button:hover, .postularme:hover {
    background: #2b5f87;
    color: #fff;
    box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
    transform: translateY(-2px);
}

/* ======== TABLA ======== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
}

th, td {
    padding: 14px;
    text-align: center;
    font-size: 14px;
    color: #edf1f6;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

th {
    background: rgba(110, 187, 233, 0.15);
    color: #6ebbe9;
    font-weight: 600;
    text-transform: uppercase;
}

tr:nth-child(even) {
    background-color: rgba(255, 255, 255, 0.04);
}

tr:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transition: background 0.25s ease;
}

/* ======== LINKS ======== */
a {
    color: #6ebbe9;
    text-decoration: underline;
    font-weight: 500;
}

a:hover {
    color: #fff;
}

/* ------------------------------------- */
/* ======== NUEVO BOTÓN CERRAR SESIÓN (FORMULARIO) ======== */
/* ------------------------------------- */

.logout-form {
    /* El formulario debe actuar como un elemento de menú */
    display: block;
    padding: 0;
    margin: 0;
}

.logout-btn {
    /* Hereda los estilos visuales del menú LI */
    display: block; 
    width: 100%; /* Ocupa el 100% del ancho del sidebar */
    text-align: left;
      box-shadow: none;
    /* Copia el padding de los otros LI */
    padding: 15px 25px; 
    
    /* Estilos visuales */
    background: transparent; /* Fondo transparente por defecto */
    border: none;
    cursor: pointer;
    font-size: 16px; /* Ajusta al tamaño de fuente de tu menú */
    font-weight: 500;
    border-radius: 0; /* Sin bordes redondeados */
    
    
    /* El color y la transición */
    color: #F44336; /* Color rojo para el texto */
    transition: background 0.3s ease, color 0.3s ease;
    
    /* Para evitar la selección de texto al hacer doble clic rápido */
    user-select: none; 
    -webkit-user-select: none; 
}

/* Efecto Hover para todo el botón */
.logout-btn:hover {
    background: rgba(244, 67, 54, 0.2); /* Fondo de hover rojo claro */
    color: #F44336;
    transform: none; /* Asegura que no se mueva */
    box-shadow: none; /* Elimina sombras de botón estándar si existen */
}


.logout-btn:active {
    background: rgba(244, 67, 54, 0.3); /* Un poco más oscuro al presionar */
}

/* ======== ANIMACIÓN ======== */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ======== RESPONSIVE ======== */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-top: 70px; /* Solo espacio para el header */
    }

    .main-container {
        margin-left: 0;
        width: 100%;
        padding-top: 20px; /* Ya no necesita tanto offset */
    }
    
    .lateral li {
        display: inline-block;
        width: 50%;
        text-align: center;
        padding: 10px 5px;
    }

    .form-wrapper {
        padding: 10px;
    }

    table {
        font-size: 13px;
    }

    th, td {
        padding: 10px;
    }

    button {
        font-size: 14px;
        padding: 8px 16px;
    }
}
.lateral li {
    padding: 15px 25px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.3s ease, color 0.3s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    
    /* === AÑADE ESTA PROPIEDAD CLAVE === */
    user-select: none; 
    /* También puedes usar los prefijos para compatibilidad: */
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* IE10+ */
}

#calendar {
  width: 95%;
  max-width: 1200px;
  margin: 40px auto;
  background-color: #fff;
  border-radius: 10px;
  padding: 20px;
  min-height: 700px;
  height: auto !important;
  overflow: hidden; /* 🔥 sin scroll */
  box-sizing: border-box;
}
/* FullCalendar interno */
.fc {
  max-width: 100%;
  box-sizing: border-box;
}

/* Cabecera del calendario */
.fc-toolbar {
  background-color: #007bff;
  border-radius: 10px;
  padding: 10px;
  color: white;
}
.fc-toolbar-title {
  color: white !important;
  font-size: 1.8rem !important;
  font-weight: bold;
}

/* Botones */
.fc-button-primary {
  background-color: #0056b3 !important;
  border: none !important;
  border-radius: 6px !important;
}
.fc-button-primary:hover {
  background-color: #003f88 !important;
}

/* Días y hover */
.fc-daygrid-day {
  background-color: #fafafa;
  transition: background-color 0.25s ease;
}
.fc-daygrid-day:hover {
  background-color: #e7f1ff;
}

/* Evento */
.fc-event {
  background-color: #17a2b8 !important;
  border: none !important;
  border-radius: 6px !important;
  color: white !important;
  font-size: 0.9rem;
  padding: 2px 4px;
}
.activa { display:block; }
.seccion { display:none; }
.activa-menu { font-weight:bold; }
</style>

</head>
<body>
  <header>
    <div class="header-logo-container">
        <img src="logo.jpeg" alt="Logo Cooperativa" class="header-logo">
    </div>
    </header>
   <div class="sidebar">
    <ul class="lateral">
        <li class="activa-menu" data-target="seccion-inicio">🏠 Inicio</li>
        <li data-target="seccion-pagos">💳 Pagos</li>
        <li data-target="seccion-horas">⏰ Horas</li>
        <li data-target="seccion-foro">💬 Foro</li>
        <li data-target="seccion-calendario">📅 Calendario</li>

        
        <?php if (!empty($user) && $user['admin'] == 1): ?>
            <li data-target="seccion-admin">⚙️ Administración</li>
        <?php endif; ?>
        
        <form method="post" action="logout.php" class="logout-form">
            <button type="submit" class="logout-btn">
                Cerrar Sesión
            </button>
        </form>
    </ul>
</div>
    
    <main class="main-container">
       

        <div class="form-wrapper">

           <div id="seccion-inicio" class="seccion activa">
             <?php if ($user): ?>
            <h1 class="titulo-principal">Bienvenido, <span><?= htmlspecialchars($user['nombre_completo']) ?></span></h1>
        <?php endif; ?>
    <div class="form-box" style="padding: 20px; border: 1px solid #ccc; border-radius: 8px; max-width: 400px; margin: 0 auto; text-align: center;">

        <h2 style="color: #6ebbe9; border-bottom: 2px solid #6ebbe9; padding-bottom: 10px; margin-top: 0;">Información Personal</h2>

        <div style="margin-bottom: 20px;">
            <?php if (!empty($user['foto_perfil_url'])): ?>
                <img src="<?= htmlspecialchars($user['foto_perfil_url']) ?>?t=<?= time() ?>" alt="Foto de Perfil" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #6ebbe9;">
            <?php else: ?>
                <div style="width: 100px; height: 100px; border-radius: 50%; background-color: rgba(110, 187, 233, 0.3); display: inline-flex; align-items: center; justify-content: center; font-size: 14px; color: #1a2433; border: 1px solid #ddd;">
                    Sin Foto
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-bottom: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                
                <label for="foto_perfil" style="display: block; margin-bottom: 10px; color: #6ebbe9;">Cambiar Foto de Perfil (JPG, PNG - Máx 2MB):</label>
                
                <input 
                    type="file" 
                    name="foto_perfil" 
                    id="foto_perfil" 
                    accept=".jpg, .jpeg, .png" 
                    required
                    style="width: 100%; margin-bottom: 15px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); padding: 8px;"
                >
                
                <button type="submit" name="subir_foto_perfil" class="postularme" style="width: 100%;">Subir Foto</button>
            </form>
            
            <?php 
            // Mensajes de éxito y error
            if (isset($_GET['foto']) && $_GET['foto'] === 'ok'): ?>
                <p style="margin-top: 10px; font-weight: bold; color: green;">Foto subida correctamente. ✅</p>
            <?php elseif (!empty($mensajeFoto)): ?>
                <p style="margin-top: 10px; font-weight: bold; color: <?= strpos($mensajeFoto, '❌') !== false ? 'red' : 'green' ?>;"><?= $mensajeFoto ?></p>
            <?php endif; ?>
        </div>
        <p style="font-size: 20px; font-weight: bold; color: #6ebbe9; margin-bottom: 5px;">
            <?= htmlspecialchars($user['nombre_completo'] ?? 'Nombre No Disponible') ?>
        </p>

        <p style="font-size: 14px; color: #ccc; margin-bottom: 15px;">
            <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email'] ?? 'correo@ejemplo.com') ?>
        </p>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

        <div style="text-align: left; margin-top: 15px;">
            <p style="margin-bottom: 8px;">
                <strong style="color: #6ebbe9;">Unidad Asignada:</strong>
                <?php if (!empty($user['id_unidad'])): ?>
                    <span style="font-size: 16px; font-weight: bold; float: right; color: #fff;">#<?= htmlspecialchars($user['id_unidad']) ?></span>
                <?php else: ?>
                    <em style="color: #888; float: right;">No asignada</em>
                <?php endif; ?>
            </p>

       <p style="margin-bottom: 8px;">
    <strong style="color: #6ebbe9;">Fecha de nacimiento:</strong> 
     <?php if (!empty($user['fecha_nacimiento'])): ?>
            <span style="float: right; color: #fff;"><?= htmlspecialchars(date('d/m/Y', strtotime($user['fecha_nacimiento']))) ?></span>
        <?php else: ?>
            <em style="color: #888; float: right;">Dato no disponible</em>
        <?php endif; ?>
</p>

            <p style="margin-bottom: 0;">
                <strong style="color: #6ebbe9;">Socio Desde:</strong>
                <?php if (!empty($user['fecha_socio'])): ?>
                    <span style="float: right; color: #fff;"><?= htmlspecialchars(date('d/m/Y', strtotime($user['fecha_socio']))) ?></span>
                <?php else: ?>
                    <em style="color: #888; float: right;">Dato no disponible</em>
                <?php endif; ?>
            </p>
        </div>

    </div>
</div>

            <div id="seccion-pagos" class="seccion">
                
                <div class="form-box">
                    <h2>Subir comprobante</h2>
                    <form action="subir_comprobante.php" method="POST" enctype="multipart/form-data">
                        <input type="number" name="monto" placeholder="Monto" required>
                        <input type="text" name="concepto" placeholder="Concepto" required>
                        <input type="file" name="archivo" required>
                        <button type="submit" class="postularme">Subir comprobante</button>
                    </form>
                </div>

                <div class="form-box">
                    <h2>Historial de comprobantes</h2>
                    <table>
                        <tr>
                            <th>Monto</th>
                            <th>Concepto</th>
                            <th>Archivo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                        <?php if ($comprobantes && $comprobantes->num_rows > 0): ?>
                            <?php while($c = $comprobantes->fetch_assoc()): ?>
                                <tr>
                                    <td>$<?= number_format((float)$c['monto'],2,',','.') ?></td>
                                    <td><?= htmlspecialchars($c['concepto']) ?></td>
                                    <td>
                                        <?php if (!empty($c['comprobante'])): ?>
                                            <a href="<?= htmlspecialchars($c['comprobante']) ?>" target="_blank">Ver</a>
                                        <?php else: ?>
                                            <em>Sin archivo</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            if($c['estado_pa'] === 'aprobado') echo "✅ <span style='color:green;'>Aprobado</span>";
                                            elseif($c['estado_pa'] === 'rechazado') echo "❌ <span style='color:red;'>Rechazado</span>";
                                            else echo "⏳ Pendiente";
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($c['fecha_p']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5"><em>No hay comprobantes</em></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div id="seccion-horas" class="seccion">
                <div class="form-box">
                    <h2>Mis Horas</h2>
                    <p>Horas cumplidas: <strong><?= $horas['cumplidas'] ?? 0 ?></strong></p>
                    <p>Horas semanales requeridas: <strong><?= $horas['semanales_req'] ?? 0 ?></strong></p>
                    <p>Horas pendientes de aprobación: <strong><?= $horas['horas_pendientes'] ?? 0 ?></strong></p>
                    <p>Horas restantes: <strong><?= max(0, ($horas['semanales_req'] ?? 0) - ($horas['cumplidas'] ?? 0)) ?></strong></p>

                    <?php if (!empty($mensajeHoras)): ?>
                        <p style="color: green; font-weight: bold;"><?= $mensajeHoras ?></p>
                    <?php endif; ?>

                    <h3>Registrar asistencia</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                        <label><input type="radio" name="asistio" value="1" required> Asistí</label>
                        <label><input type="radio" name="asistio" value="0" required> No asistí</label>

                        <div>
                            <label>Horas realizadas:</label>
                            <input type="number" name="horas_realizadas" min="1">
                        </div>
                        <div>
                            <label>Actividad realizada:</label>
                            <textarea name="actividad"></textarea>
                        </div>

                        <div>
                            <label>Justificativo (si no asistió):</label>
                            <textarea name="justificativo_texto"></textarea>
                            <input type="file" name="justificativo_file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        <button type="submit" name="guardar_asistencia" class="postularme">Guardar</button>
                    </form>

                    <?php if (!empty($horas['justificativos'])): ?>
                        <p><strong>Historial de asistencia:</strong></p>
                        <ul>
                            <?php
                                $items = explode("|", $horas['justificativos']);
                                foreach ($items as $item) {
                                    $item = trim($item);
                                    if ($item === "") continue;
                                    parse_str(str_replace(";", "&", $item), $data);

                                    if (isset($data['asistio']) && (int)$data['asistio'] === 1) {
                                        $h = isset($data['horas']) ? (int)$data['horas'] : 0;
                                        $act = isset($data['actividad']) ? htmlspecialchars($data['actividad']) : '';
                                        echo "<li>✅ Asistió - {$h} horas - Actividad: {$act}</li>";
                                    } else {
                                        $just = isset($data['justificativo']) ? trim($data['justificativo']) : '';
                                        $ext = strtolower(pathinfo($just, PATHINFO_EXTENSION));
                                        if ($just !== '' && in_array($ext, ['pdf','jpg','jpeg','png']) && file_exists($just)) { // Se añadió file_exists para seguridad
                                            $href = htmlspecialchars($just);
                                            $name = htmlspecialchars(basename($just));
                                            echo "<li>❌ No asistió - Justificativo: <a href=\"{$href}\" target=\"_blank\">{$name}</a></li>";
                                        } else {
                                            echo "<li>❌ No asistió - Justificativo: " . htmlspecialchars($just) . "</li>";
                                        }
                                    }
                                }
                            ?>
                        </ul>
                    <?php else: ?>
                        <p><em>No hay registros aún.</em></p>
                    <?php endif; ?>
                </div> 
            </div> 

            <?php if (!empty($user) && $user['admin'] == 1): ?>
            <div id="seccion-admin" class="seccion">
                <div class="form-box" style="margin-top: 30px; text-align: center;">
                    <h2>Panel de Administración</h2>
                    <p>Accede a las herramientas de administración de la cooperativa.</p>
                    <form action="admin.php" method="post" style="margin-top: 20px;">
                        <button type="submit" class="btn">Ir al Panel de Administración</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
<?php if (isset($_SESSION['editar_foro'])): ?>
  <div style="margin-bottom: 20px; background:rgba(255,255,255,0.08); padding:15px; border-radius:10px;">
    <h3>✏️ Editar mensaje</h3>
    <form method="POST" action="">
        <input type="hidden" name="foro_id" value="<?= $_SESSION['editar_foro']['id'] ?>">
        <input type="text" name="titulo" value="<?= htmlspecialchars($_SESSION['editar_foro']['titulo']) ?>" required>
        <textarea name="mensaje" required><?= htmlspecialchars($_SESSION['editar_foro']['mensaje']) ?></textarea>
        <button type="submit" name="guardar_edicion_foro" class="postularme">Guardar cambios</button>
    </form>
  </div>
<?php endif; ?>            
<div id="seccion-foro" class="seccion">
  <div class="form-box">
    <h2>💬 Foro Comunitario</h2>

    <!-- Formulario para publicar un nuevo mensaje -->
    <form method="POST" action="">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <input type="text" name="titulo" placeholder="Título del mensaje" required>
        <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>
        <button type="submit" name="publicar_foro" class="postularme">Publicar</button>
    </form>

    <hr style="margin: 20px 0; border-color: rgba(255,255,255,0.2);">

    <h3>Mensajes recientes</h3>
    <div style="max-height: 500px; overflow-y: auto;">

<?php        
$foro = $conexion->query("
    SELECT f.id, f.titulo, f.mensaje, f.fecha, f.usuario_id, m.nombre AS autor
    FROM foro f
    JOIN miembro m ON f.usuario_id = m.id_miembro
    ORDER BY f.fecha DESC
");

if (!$foro) {
    echo "<p style='color:red'>❌ Error en la consulta del foro: " . htmlspecialchars($conexion->error) . "</p>";
} else {
    if ($foro->num_rows > 0):
        while ($fila = $foro->fetch_assoc()):
?>
        <div style="position:relative; margin-bottom:20px; padding:15px; background:rgba(255,255,255,0.05); border-radius:10px;">

            <!-- 🔹 Menú de opciones (solo si es tu mensaje o eres admin) -->
            <?php if ($fila['usuario_id'] == $id || $_SESSION['admin'] ?? false): ?>
                <div style="position:absolute; top:10px; right:10px;">
                    <div class="menu-opciones" style="position:relative; display:inline-block;">
                        <button type="button" onclick="toggleMenu(this)" style="background:none; border:none; color:white; font-size:18px; cursor:pointer;">⋮</button>
                        <div class="dropdown-menu" style="display:none; position:absolute; right:0; background:#333; border-radius:6px; padding:5px; z-index:10;">
                            <form method="POST" action="" style="margin:0;">
                                <input type="hidden" name="foro_id" value="<?= $fila['id'] ?>">
                                <button type="submit" name="editar_foro" style="background:none; border:none; color:white; padding:5px 10px; width:100%; text-align:left;">✏️ Editar</button>
                            </form>
                            <form method="POST" action="" style="margin:0;">
                                <input type="hidden" name="foro_id" value="<?= $fila['id'] ?>">
                                <button type="submit" name="eliminar_foro" onclick="return confirm('¿Eliminar este mensaje?');" style="background:none; border:none; color:#f66; padding:5px 10px; width:100%; text-align:left;">🗑️ Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <strong style="color:#6ebbe9;"><?= htmlspecialchars($fila['autor']) ?></strong> 
            <span style="color:#aaa; font-size:12px;">
                (<?= date("d/m/Y H:i", strtotime($fila['fecha'])) ?>)
            </span>
            <h4 style="margin:8px 0;"><?= htmlspecialchars($fila['titulo']) ?></h4>
            <p style="margin-bottom:10px;"><?= nl2br(htmlspecialchars($fila['mensaje'])) ?></p>

            <!-- 🔹 Mostrar respuestas -->
            <div style="margin-left:20px; border-left:2px solid rgba(255,255,255,0.1); padding-left:10px; margin-top:10px;">
            <?php
            $respuestas = $conexion->prepare("
                SELECT r.id, r.respuesta, r.fecha, r.usuario_id, m.nombre
                FROM foro_respuestas r
                JOIN miembro m ON r.usuario_id = m.id_miembro
                WHERE r.foro_id = ?
                ORDER BY r.fecha ASC
            ");
            $respuestas->bind_param("i", $fila['id']);
            $respuestas->execute();
            $res = $respuestas->get_result();

            if ($res && $res->num_rows > 0):
                while ($r = $res->fetch_assoc()):
            ?>
                <div style="position:relative; margin-top:8px; padding:6px 8px; background:rgba(255,255,255,0.03); border-radius:6px;">
                    <!-- 🔹 Menú en respuestas -->
                    <?php if ($r['usuario_id'] == $id || $_SESSION['admin'] ?? false): ?>
                        <div style="position:absolute; top:5px; right:5px;">
                            <div class="menu-opciones" style="position:relative; display:inline-block;">
                                <button type="button" onclick="toggleMenu(this)" style="background:none; border:none; color:white; font-size:14px; cursor:pointer;">⋮</button>
                                <div class="dropdown-menu" style="display:none; position:absolute; right:0; background:#333; border-radius:6px; padding:5px; z-index:10;">
                                    <form method="POST" action="" style="margin:0;">
                                        <input type="hidden" name="respuesta_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="editar_respuesta" style="background:none; border:none; color:white; padding:5px 10px; width:100%; text-align:left;">✏️ Editar</button>
                                    </form>
                                    <form method="POST" action="" style="margin:0;">
                                        <input type="hidden" name="respuesta_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="eliminar_respuesta" onclick="return confirm('¿Eliminar esta respuesta?');" style="background:none; border:none; color:#f66; padding:5px 10px; width:100%; text-align:left;">🗑️ Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <strong><?= htmlspecialchars($r['nombre']) ?>:</strong>
                    <?= nl2br(htmlspecialchars($r['respuesta'])) ?>
                    <div style="font-size:11px; color:#aaa;">
                        <?= date("d/m/Y H:i", strtotime($r['fecha'])) ?>
                    </div>
                </div>
            <?php
                endwhile;
            else:
                echo "<em style='color:#888;'>No hay respuestas aún.</em>";
            endif;
            ?>
            </div>

            <!-- 🔹 Formulario de respuesta -->
            <form method="POST" action="" style="margin-top:10px;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="foro_id" value="<?= $fila['id'] ?>">
                <textarea name="respuesta" placeholder="Escribe una respuesta..." required></textarea>
                <button type="submit" name="responder_foro" class="postularme">Responder</button>
            </form>
        </div>
<?php
        endwhile;
    else:
        echo "<p><em>No hay mensajes aún.</em></p>";
    endif;
}
?>   

<script>
function toggleMenu(btn) {
  const menu = btn.nextElementSibling;
  const visible = menu.style.display === 'block';
  document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
  menu.style.display = visible ? 'none' : 'block';
}
document.addEventListener('click', e => {
  if (!e.target.closest('.menu-opciones')) {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
  }
});
</script>
<?php if (isset($_SESSION['editar_respuesta'])): ?>
  <div style="margin-bottom: 20px; background:rgba(255,255,255,0.08); padding:15px; border-radius:10px;">
    <h3>✏️ Editar respuesta</h3>
    <form method="POST" action="">
        <input type="hidden" name="respuesta_id" value="<?= $_SESSION['editar_respuesta']['id'] ?>">
        <textarea name="respuesta" required><?= htmlspecialchars($_SESSION['editar_respuesta']['respuesta']) ?></textarea>
        <button type="submit" name="guardar_edicion_respuesta" class="postularme">Guardar cambios</button>
    </form>
  </div>
<?php endif; ?>


<!-- ==================== SECCIÓN CALENDARIO ==================== -->
<div id="seccion-calendario" class="seccion">
  <div class="form-box">
    <h2>📅 Calendario de Eventos</h2>

    <?php if ($user['admin'] == 1): ?>
      <form method="POST" action="">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
          <input type="text" name="titulo" placeholder="Título del evento" required>
          <textarea name="descripcion" placeholder="Descripción (opcional)"></textarea>
          <label>Fecha del evento:</label>
          <input type="date" name="fecha_evento" required>
          <button type="submit" name="agregar_evento" class="postularme">Agregar evento</button>
      </form>
      <hr style="margin: 20px 0; border-color: rgba(255,255,255,0.2);">
    <?php endif; ?>

    <div id="calendar" style="max-width: 900px; margin: 0 auto; background: transparent; box-shadow: none;"></div>
  </div>
</div>
</div> 
</main>

<!-- Librerías -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  console.log("✅ Script calendario cargado");

  const items = document.querySelectorAll(".lateral li");
  const secciones = document.querySelectorAll(".seccion");

  // --- Función para activar secciones ---
  function activarSeccion(id) {
    secciones.forEach(sec => sec.classList.remove("activa"));
    items.forEach(i => i.classList.remove("activa-menu"));

    const target = document.getElementById(id);
    const menuItem = document.querySelector(`.lateral li[data-target="${id}"]`);

    if (target) target.classList.add("activa");
    if (menuItem) menuItem.classList.add("activa-menu");

    if (id === "seccion-calendario" && window.__fc_instance) {
      setTimeout(() => {
        window.__fc_instance.refetchEvents();
        window.__fc_instance.render();
      }, 200);
    }
  }

  // --- Menú lateral ---
  items.forEach(item => {
    item.addEventListener("click", () => {
      const targetId = item.dataset.target;
      activarSeccion(targetId);
      history.replaceState(null, '', `#${targetId}`);
    });
  });

  // --- Inicializar calendario ---
  const calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'es',
      height: 'auto',
      events: 'pagos.php?action=listar_eventos',
      eventColor: '#6ebbe9',
      eventTextColor: '#fff',
      eventDisplay: 'block',

      // Mostrar modal al hacer click en evento
      eventClick: async function(info) {
        info.jsEvent.preventDefault();
        const titulo = info.event.title;
        const descripcion = info.event.extendedProps.description || 'Sin descripción';
        const fecha = info.event.startStr;
        const idEvento = info.event.id;

        const result = await Swal.fire({
          title: titulo,
          html: `<b>📅 Fecha:</b> ${fecha}<br><br><b>📝 Descripción:</b><br>${descripcion}`,
          icon: 'info',
          confirmButtonText: 'Cerrar',
          showDenyButton: <?= ($user['admin'] == 1 ? 'true' : 'false') ?>,
          denyButtonText: 'Editar / Eliminar',
          confirmButtonColor: '#3085d6',
          denyButtonColor: '#6ebbe9',
          width: 400
        });

        // --- Solo si el admin elige editar/eliminar ---
        if (result.isDenied) {
          const action = await Swal.fire({
            title: 'Editar o eliminar evento',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Editar',
            denyButtonText: 'Eliminar',
            cancelButtonText: 'Cerrar'
          });

          // Editar evento
          if (action.isConfirmed) {
            const { value: formValues } = await Swal.fire({
              title: 'Editar evento',
              html:
                `<input id="swal-titulo" class="swal2-input" placeholder="Título" value="${titulo}">` +
                `<textarea id="swal-desc" class="swal2-textarea" placeholder="Descripción">${descripcion}</textarea>` +
                `<input id="swal-fecha" type="date" class="swal2-input" value="${fecha}">`,
              focusConfirm: false,
              confirmButtonText: 'Guardar cambios',
              cancelButtonText: 'Cerrar',
              showCancelButton: true,
              preConfirm: () => {
                return {
                  titulo: document.getElementById('swal-titulo').value,
                  descripcion: document.getElementById('swal-desc').value,
                  fecha: document.getElementById('swal-fecha').value
                };
              }
            });

            if (formValues) {
              const resp = await fetch('pagos.php?action=editar_evento', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: idEvento, ...formValues })
              });
              const data = await resp.json();
              if (data.ok) {
                Swal.fire('✅ Evento actualizado', '', 'success');
                calendar.refetchEvents();
              } else {
                Swal.fire('❌ Error al actualizar', '', 'error');
              }
            }
          }

          // Eliminar evento
          else if (action.isDenied) {
            const conf = await Swal.fire({
              title: '¿Eliminar evento?',
              text: 'Esta acción no se puede deshacer.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cerrar'
            });

            if (conf.isConfirmed) {
              const resp = await fetch('pagos.php?action=eliminar_evento', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: idEvento })
              });
              const data = await resp.json();
              if (data.ok) {
                Swal.fire('🗑️ Evento eliminado', '', 'success');
                calendar.refetchEvents();
              } else {
                Swal.fire('❌ Error al eliminar', '', 'error');
              }
            }
          }
        }
      }
    });

    calendar.render();
    window.__fc_instance = calendar;
  }

  // --- Mostrar la sección correcta al recargar ---
  const hash = window.location.hash.replace('#', '');
  if (hash && document.getElementById(hash)) {
    activarSeccion(hash);
  } else {
    activarSeccion('seccion-calendario');
    history.replaceState(null, '', '#seccion-calendario');
  }
});
</script>
</body>
</html>