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
$mensajeFoto = ""; // Nueva variable para mensajes de la foto

// =========================================================================
// === CONSULTA SQL (SE MANTIENE, usando nombre AS nombre_completo) ===
// =========================================================================
$sql = "
    SELECT 
        m.es_miembro, 
        m.admin, 
        m.nombre AS nombre_completo, 
        m.email, 
        m.id_unidad, 
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

// =========================================================================
// === NUEVO BLOQUE: SUBIDA Y ACTUALIZACIÓN DE FOTO DE PERFIL ===
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["subir_foto_perfil"], $_POST["csrf"])) {
    $csrf_post = $_POST['csrf'];
    $csrf_session = $_SESSION['csrf'] ?? '';
    
    if (!is_string($csrf_post) || !is_string($csrf_session) || !hash_equals($csrf_session, $csrf_post)) {
        die("Token CSRF inválido");
    }

    if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] === 0) {
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES["foto_perfil"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $mensajeFoto = "❌ Solo se permiten archivos JPG, JPEG o PNG.";
        } elseif ($_FILES["foto_perfil"]["size"] > 2000000) { // Límite de 2MB
            $mensajeFoto = "❌ El archivo es demasiado grande (máximo 2MB).";
        } else {
            // Preparar directorio y ruta
            if (!is_dir("perfiles")) mkdir("perfiles", 0755);
            $nombre_archivo = "perfil_{$id}_".time().".".$ext;
            $ruta = "perfiles/" . $nombre_archivo;
            
            if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $ruta)) {
                
                // Borrar foto anterior (opcional, para liberar espacio)
                if (!empty($user['foto_perfil_url']) && file_exists($user['foto_perfil_url'])) {
                    unlink($user['foto_perfil_url']);
                }

                // Actualizar DB
                $updateFoto = $conexion->prepare("UPDATE miembro SET foto_perfil = ? WHERE id_miembro = ?");
                $updateFoto->bind_param("si", $ruta, $id);
                $updateFoto->execute();
                
                $mensajeFoto = "Foto subida correctamente. ✅";
                
                // Refrescar los datos del usuario en la sesión/variable para que la vea inmediatamente
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


// Verificar si existe fila en horas
$checkHoras = $conexion->prepare("SELECT id_horas FROM horas WHERE id_miembro = ?");
$checkHoras->bind_param("i", $id);
$checkHoras->execute();
$resHoras = $checkHoras->get_result();

if ($resHoras->num_rows === 0) {
    $insertHoras = $conexion->prepare("INSERT INTO horas (id_miembro, semanales_req, cumplidas, horas_pendientes, justificativos) VALUES (?, 10, 0, 0, '')");
    $insertHoras->bind_param("i", $id);
    $insertHoras->execute();
}

// Traer comprobantes
$stmt = $conexion->prepare("SELECT * FROM pago WHERE id_miembro=? ORDER BY fecha_p DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comprobantes = $stmt->get_result();

// Traer horas del miembro
$queryHoras = $conexion->prepare("SELECT * FROM horas WHERE id_miembro = ?");
$queryHoras->bind_param("i", $id);
$queryHoras->execute();
$resultHoras = $queryHoras->get_result();
$horas = $resultHoras ? $resultHoras->fetch_assoc() : [];


// === BLOQUE 1: CAMBIO DE ESTADO DE PAGO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['estado'], $_POST['csrf']) && !isset($_POST['guardar_asistencia'])) {
    $id_pago = intval($_POST['id']);
    $estado = $_POST['estado'];
    $csrf_post = $_POST['csrf'];
    $csrf_session = $_SESSION['csrf'] ?? '';

    if (!is_string($csrf_post) || !is_string($csrf_session) || !hash_equals($csrf_session, $csrf_post)) {
        die("Token CSRF inválido");
    }

    $valid = ['aprobado', 'rechazado'];
    if (!in_array($estado, $valid, true)) {
        die("Estado inválido");
    }

    $stmt = $conexion->prepare("UPDATE pago SET estado_pa = ? WHERE id_pago = ?");
    $stmt->bind_param("si", $estado, $id_pago);
    $stmt->execute();

    header("Location: pagos.php?estado=" . urlencode($estado));
    exit;
}


// === BLOQUE 2: REGISTRO DE ASISTENCIA ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_asistencia"], $_POST["csrf"])) {
    $csrf_post = $_POST['csrf'];
    $csrf_session = $_SESSION['csrf'] ?? '';

    if (!is_string($csrf_post) || !is_string($csrf_session) || !hash_equals($csrf_session, $csrf_post)) {
        die("Token CSRF inválido");
    }

    $asistio = intval($_POST["asistio"]);
    $registro = "";

    if ($asistio === 1) {
        $horasRealizadas = intval($_POST["horas_realizadas"]);
        $actividad = trim($_POST["actividad"]);
        $registro = "asistio=1;horas={$horasRealizadas};actividad={$actividad}";

        if ($horasRealizadas > 0) {
            $updateHoras = $conexion->prepare("
                UPDATE horas 
                SET horas_pendientes = horas_pendientes + ? 
                WHERE id_miembro = ?
            ");
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
                $ruta = "justificativos/just_{$id}_".time().".".$ext;
                move_uploaded_file($_FILES["justificativo_file"]["tmp_name"], $ruta);
                $justificativo .= ($justificativo ? " " : "") . $ruta;
            }
        }
        $registro = "asistio=0;justificativo={$justificativo}";
    }

    if ($registro !== "") {
        $updateJust = $conexion->prepare("
            UPDATE horas 
            SET justificativos = CONCAT_WS('|', justificativos, ?) 
            WHERE id_miembro = ?
        ");
        $updateJust->bind_param("si", $registro, $id);
        $updateJust->execute();
    }

    $mensajeHoras .= "Registro guardado correctamente ✅";

    $queryHoras->execute();
    $horas = $queryHoras->get_result()->fetch_assoc();

    header("Location: pagos.php?registro=ok");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos y Horas - Cooperativa</title>
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
  justify-content: flex-end;
  align-items: center;
  padding: 0 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

header a {
  color: #edf1f6;
  text-decoration: none;
  font-weight: 600;
  background: #6ebbe9;
  padding: 8px 16px;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
  transition: all 0.3s ease;
}

header a:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
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
</style>
</head>
<body>
    <header>
        <div class="top-bar">
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </header>

    <div class="sidebar">
        <ul class="lateral">
            <li class="activa-menu" data-target="seccion-inicio">🏠 Inicio</li>
            <li data-target="seccion-pagos">💳 Pagos</li>
            <li data-target="seccion-horas">⏰ Horas</li>
            <?php if (!empty($user) && $user['admin'] == 1): ?>
                <li data-target="seccion-admin">⚙️ Administración</li>
            <?php endif; ?>
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

        </div> </main>

    <script>
        // Código JavaScript sacado de la imagen

        document.addEventListener('DOMContentLoaded', () => {
            console.log("Script cargado");

            // 🍕 Navegación entre secciones
            const items = document.querySelectorAll(".lateral li");
            const secciones = document.querySelectorAll(".seccion");

            items.forEach(item => {
                item.addEventListener("click", () => {
                    // 1. Quitar la clase 'activa' y 'activa-menu' de todos
                    secciones.forEach(sec => sec.classList.remove("activa"));
                    items.forEach(i => i.classList.remove("activa-menu")); // Desactivar menú anterior

                    // 2. Obtener la sección de destino
                    const targetId = item.dataset.target; // Obtiene el valor de data-target
                    const target = document.getElementById(targetId);

                    // 3. Activar la sección y el elemento del menú
                    if (target) {
                        target.classList.add("activa");
                        item.classList.add("activa-menu"); // Activar menú actual
                    }
                });
            });
            
            // Asegurar que la primera sección se muestre al cargar
            const primeraSeccion = document.querySelector('.seccion');
            const primerMenuItem = document.querySelector('.lateral li');
            if(primeraSeccion && primerMenuItem) {
                primeraSeccion.classList.add('activa');
                primerMenuItem.classList.add('activa-menu');
            }
        });
    </script>
</body>
</html>