<?php
// =================================================================
// 1. CONFIGURACIÓN INICIAL, CONEXIÓN Y SEGURIDAD
// =================================================================
session_start();
// Asegúrate de que tu archivo 'conexion.php' esté en la misma carpeta
require_once "conexion.php"; 

// Conexión a la base de datos
$conexion = new mysqli($host, $user, $pass, $db, $port);

// 1.1. Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión a la Base de Datos: " . $conexion->connect_error);
}

// 1.2. Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=sin_sesion");
    exit;
}

// 1.3. Verificar si el usuario es administrador
$id = intval($_SESSION['id']);
// Usamos prepare para SEGURIDAD
$stmt_admin = $conexion->prepare("SELECT admin FROM miembro WHERE id_miembro = ?");
$stmt_admin->bind_param("i", $id);
$stmt_admin->execute();
$resultado = $stmt_admin->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: index.html?error=usuario_no_encontrado");
    exit;
}

$datos = $resultado->fetch_assoc();
if (intval($datos['admin']) !== 1) {
    header("Location: index.html?error=no_admin");
    exit;
}

// 1.4. Determinar la vista actual y el título
$view = $_GET['view'] ?? 'miembros';
$page_title = "Panel de Admin - ";

switch ($view) {
    case 'miembros': $page_title .= "Miembros"; break;
    case 'comprobantes': $page_title .= "Comprobantes"; break;
    case 'horas': $page_title .= "Horas"; break;
    case 'postulaciones': $page_title .= "Postulaciones"; break;
    default: $view = 'miembros'; $page_title .= "Miembros"; break;
}

// Inicializar variables de mensaje y CSRF
$mensajeUnidad = ""; 
$mensajeHoras = ""; 
$mensajePago = ""; 
$csrf = '';        

// =================================================================
// 2. LÓGICA DE ACCIONES (POST y GET)
// =================================================================

// --- 2.1. Lógica de Comprobantes (Integración de cambiar_estado.php) ---
if ($view === 'comprobantes' && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['estado'])) {
    
    if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
        $msg = "❌ Error de seguridad (CSRF inválido).";
    } else {
        $id_pago = (int)($_POST['id']);
        $estado_nuevo = $_POST['estado'];

        if ($id_pago <= 0 || !in_array($estado_nuevo, ['aprobado','rechazado'], true)) {
            $msg = "❌ Datos inválidos.";
        } else {
            // Ejecutar la actualización con prepare
            $stmt = $conexion->prepare("UPDATE pago SET estado_pa = ? WHERE id_pago = ?");
            $stmt->bind_param("si", $estado_nuevo, $id_pago);
            $stmt->execute();
            
            $msg = "✅ Pago #$id_pago " . ($estado_nuevo === 'aprobado' ? 'Aprobado' : 'Rechazado') . " correctamente.";
        }
    }

    // Redirigir de vuelta a la vista de comprobantes con el mensaje
    header("Location: admin.php?view=comprobantes&msg_pago=" . urlencode($msg));
    exit;
}

// --- 2.2. Lógica de Horas (Integración de admin_horas.php POST) ---
if ($view === 'horas' && $_SERVER["REQUEST_METHOD"] === "POST") {
    $id_miembro = isset($_POST["id_miembro"]) ? intval($_POST["id_miembro"]) : 0;

    if ($id_miembro > 0) {
        // Verificar/crear fila en tabla 'horas' si no existe
        $check = $conexion->prepare("SELECT id_horas FROM horas WHERE id_miembro = ?");
        $check->bind_param("i", $id_miembro);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows === 0) {
            $insert = $conexion->prepare("INSERT INTO horas (id_miembro, semanales_req, cumplidas, horas_pendientes, justificativos) VALUES (?, 10, 0, 0, '')");
            $insert->bind_param("i", $id_miembro);
            $insert->execute();
        }

        if (isset($_POST["guardar"])) {
            $semanales_req = intval($_POST["semanales_req"]);
            $cumplidas = intval($_POST["cumplidas"]);
            // CORRECCIÓN CLAVE: Permitir al admin actualizar Horas Pendientes manualmente
            $horas_pendientes_manual = intval($_POST["horas_pendientes"]); 

            $update = $conexion->prepare("UPDATE horas SET semanales_req=?, cumplidas=?, horas_pendientes=? WHERE id_miembro=?");
            // Ahora se bindan 4 parámetros (iii): semanales_req, cumplidas, horas_pendientes_manual, id_miembro
            $update->bind_param("iiii", $semanales_req, $cumplidas, $horas_pendientes_manual, $id_miembro);
            $update->execute();
            $msg = "Cambios de horas guardados";

        } elseif (isset($_POST["aprobar_pendientes"])) {
            // Lógica para mover pendientes a cumplidas y registrar justificación
            $sel = $conexion->prepare("SELECT horas_pendientes FROM horas WHERE id_miembro = ?");
            $sel->bind_param("i", $id_miembro);
            $sel->execute();
            $resSel = $sel->get_result();
            $rowSel = $resSel->fetch_assoc();
            $pend = (int)($rowSel["horas_pendientes"] ?? 0);

            if ($pend > 0) {
                // Registrar el justificativo de aprobación
                $registro = "asistio=1;horas={$pend};actividad=Aprobadas por admin";
                $upJust = $conexion->prepare("UPDATE horas SET justificativos = CONCAT_WS('|', justificativos, ?) WHERE id_miembro = ?");
                $upJust->bind_param("si", $registro, $id_miembro);
                $upJust->execute();
            }

            // Mover las horas pendientes a cumplidas y poner pendientes en 0
            $update = $conexion->prepare("UPDATE horas SET cumplidas = cumplidas + horas_pendientes, horas_pendientes = 0 WHERE id_miembro = ?");
            $update->bind_param("i", $id_miembro);
            $update->execute();
            $msg = "Horas pendientes aprobadas";
        }
    }
    // Redirección POST/REDIRECT/GET
    header("Location: admin.php?view=horas&msg_horas=" . urlencode("✅ " . ($msg ?? "Acción realizada") . " para el miembro #" . ($id_miembro ?? '')));
    exit;
}

// --- 2.3. Lógica de Miembros (GET) ---

// Cambiar estado de miembro (socio)
if ($view === 'miembros' && isset($_GET["socio"]) && isset($_GET["id"])) {
    $id_miembro_socio = intval($_GET["id"]);
    $socio = intval($_GET["socio"]);
    
    $stmt = $conexion->prepare("UPDATE miembro SET es_miembro = ? WHERE id_miembro = ?");
    $stmt->bind_param("ii", $socio, $id_miembro_socio);
    $stmt->execute();

    header("Location: admin.php?view=miembros");
    exit;
}

// Cambiar estado de administrador
if ($view === 'miembros' && isset($_GET["admin"]) && isset($_GET["id"])) {
    $id_miembro_admin = intval($_GET["id"]);
    $admin = intval($_GET["admin"]);
    
    $stmt = $conexion->prepare("UPDATE miembro SET admin = ? WHERE id_miembro = ?");
    $stmt->bind_param("ii", $admin, $id_miembro_admin);
    $stmt->execute();
    
    header("Location: admin.php?view=miembros");
    exit;
}

// Asignar unidad habitacional
if ($view === 'miembros' && isset($_GET["asignar_unidad"]) && isset($_GET["id"])) {
    $id_unidad = intval($_GET["asignar_unidad"]);
    $id_miembro = intval($_GET["id"]);
    $mensajeUnidad = ""; 

    if ($id_unidad < 1 || $id_unidad > 100) {
        $mensajeUnidad = "❌ Unidad fuera de rango (1–100)";
    } else {
        // 1. Obtener estado de la unidad de destino
        $stmt_unidad = $conexion->prepare("SELECT estado_un FROM unidad_habitacional WHERE id_unidad = ?");
        $stmt_unidad->bind_param("i", $id_unidad);
        $stmt_unidad->execute();
        $res_unidad = $stmt_unidad->get_result();

        if ($res_unidad->num_rows === 0) {
            $mensajeUnidad = "❌ La unidad #$id_unidad no existe";
        } else {
            $estado_destino = $res_unidad->fetch_assoc()['estado_un'];

            // 2. Obtener la unidad actual del miembro
            $stmt_check = $conexion->prepare("SELECT id_unidad FROM miembro WHERE id_miembro = ?");
            $stmt_check->bind_param("i", $id_miembro);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $unidadActual = $res_check->fetch_assoc()['id_unidad'] ?? null;

            if ($unidadActual == $id_unidad) {
                $mensajeUnidad = "ℹ️ El miembro ya tiene asignada la unidad #$id_unidad";
            } elseif ($estado_destino === 'mantenimiento') {
                $mensajeUnidad = "⚠️ La unidad #$id_unidad está en mantenimiento";
            } elseif ($estado_destino === 'ocupada') {
                $mensajeUnidad = "❌ La unidad #$id_unidad ya está ocupada";
            } else {
                // 3. Proceder con la asignación
                // Actualizar miembro
                $stmt_upd_miembro = $conexion->prepare("UPDATE miembro SET id_unidad = ? WHERE id_miembro = ?");
                $stmt_upd_miembro->bind_param("ii", $id_unidad, $id_miembro);
                $stmt_upd_miembro->execute();
                
                // Liberar unidad anterior si existe
                if ($unidadActual) {
                    $stmt_upd_old_unidad = $conexion->prepare("UPDATE unidad_habitacional SET estado_un = 'libre' WHERE id_unidad = ?");
                    $stmt_upd_old_unidad->bind_param("i", $unidadActual);
                    $stmt_upd_old_unidad->execute();
                }
                
                // Ocupar nueva unidad
                $stmt_upd_new_unidad = $conexion->prepare("UPDATE unidad_habitacional SET estado_un = 'ocupada' WHERE id_unidad = ?");
                $stmt_upd_new_unidad->bind_param("i", $id_unidad);
                $stmt_upd_new_unidad->execute();
                
                $mensajeUnidad = "✅ Unidad #$id_unidad asignada correctamente";
            }
        }
    }
    // Redirección POST/REDIRECT/GET para limpiar el formulario y mantener el mensaje
    header("Location: admin.php?view=miembros&msg_unidad=" . urlencode(strip_tags($mensajeUnidad)));
    exit;
}

// --- 2.4. Recuperar mensajes de estado si existen ---
if (isset($_GET['msg_unidad'])) { $mensajeUnidad = urldecode($_GET['msg_unidad']); }
if (isset($_GET['msg_horas'])) { $mensajeHoras = urldecode($_GET['msg_horas']); }
if (isset($_GET['msg_pago'])) { $mensajePago = urldecode($_GET['msg_pago']); }


// =================================================================
// 3. RECUPERACIÓN DE DATOS PARA LA VISTA
// =================================================================

$datos_vista = [];

switch ($view) {
    case 'miembros':
        // Traer todos los miembros
        $datos_vista['usuarios'] = $conexion->query("SELECT * FROM miembro");
        break;

    case 'comprobantes':
        // Generar CSRF token
        if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
        $csrf = $_SESSION['csrf'];

        $sql_c = "SELECT p.id_pago, m.nombre, m.email, p.monto, p.concepto, p.comprobante, p.estado_pa, p.fecha_p
                FROM pago p
                INNER JOIN miembro m ON m.id_miembro = p.id_miembro
                ORDER BY p.fecha_p DESC";
        $datos_vista['comprobantes'] = $conexion->query($sql_c);
        break;

    case 'horas':
        // Traer datos de horas
        $datos_vista['horas'] = $conexion->query("
            SELECT m.id_miembro, m.nombre, m.email, h.semanales_req, h.cumplidas, h.horas_pendientes, h.justificativos
            FROM miembro m
            LEFT JOIN horas h ON m.id_miembro = h.id_miembro
            WHERE m.es_miembro = 1
            ORDER BY m.nombre ASC
        ");
        break;

    case 'postulaciones':
        // Traer todas las postulaciones
        $sql_p = "SELECT p.*, m.nombre AS nombre_miembro, m.email AS email_miembro
                FROM postulacion p
                LEFT JOIN miembro m ON p.id_miembro = m.id_miembro
                ORDER BY p.fecha_postulacion DESC";
        $datos_vista['postulaciones'] = $conexion->query($sql_p);
        break;
}

// =================================================================
// 4. HTML (ESTRUCTURA UNIFICADA CON ESTILOS COMBINADOS)
// =================================================================
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
    color: #edf1f6;
    padding: 0;
    min-height: 100vh;
    display: flex; 
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

.logo {
    display: flex;
    align-items: center;
    height: 100%;
    text-decoration: none;
    color: #edf1f6;
    font-size: 1.2em;
    font-weight: 600;
}

.logo img {
    height: 50px;
    width: auto;
    border-radius: 50%;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.2);
    margin-right: 15px;
    object-fit: contain;
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
/* ------------------------------------- */
/* ======== NAVEGACIÓN LATERAL (IZQUIERDA) ======== */
/* ------------------------------------- */

.sidebar {
    width: 250px;
    background: rgba(26, 36, 51, 0.95);
    padding-top: 70px; 
    position: fixed;
    top: 0;
    left: 0; 
    height: 100vh;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3); 
    z-index: 9;
     font-weight: 500;
}

.lateral {
    list-style: none;
    padding: 0;
    
}

.lateral li {
    padding: 15px 25px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.lateral li a {
    display: block;
    color: #edf1f6;
    text-decoration: none;
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

.lateral li.activa-menu a {
    color: #1a2433;
}


/* ------------------------------------- */
/* ======== CONTENIDO PRINCIPAL ======== */
/* ------------------------------------- */

.main-content {
    margin-left: 250px; 
    padding: 90px 20px 40px; 
    /* CAMBIO CLAVE: Eliminar max-width y usar cálculo de ancho */
    width: calc(100% - 250px); /* Ocupa el 100% menos el ancho de la barra lateral */
    max-width: none; /* <--- Esto es clave para que se estire */
    margin-right: 0;
}

h2 {
    text-align: center;
    font-size: 32px;
    color: #ffffffff;
    text-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
    margin-bottom: 30px;
    font-weight: 600;
}

/* ======== TABLA ESTILIZADA (Común) ======== */
table {
    width: 100%; 
    max-width: 100%; /* Asegura que la tabla no exceda el ancho del contenedor */
    
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    /* overflow: hidden;  <-- Descomentar si el scroll debe estar fuera del contenedor de scroll */
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
    margin: 0 auto 40px; /* Centra la tabla dentro de su contenedor (.main-content) */
}

th, td {
    padding: 14px 10px;
    font-size: 14px;
    text-align: center;
    color: #edf1f6;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

th {
    background: rgba(110, 187, 233, 0.15);
    color: #6ebbe9;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.04);
}

tr:hover {
    background: rgba(255, 255, 255, 0.1);
    transition: background 0.3s ease;
}

/* Estilos Específicos para Acciones (Botones/Links) */
td a, td button {
    display: block;
    padding: 6px 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    margin: 4px auto;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    text-align: center;
    width: 90%;
    max-width: 120px;
}

a.verde, button.verde, button.aprobar {
    background: #4CAF50;
    color: #fff;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.4);
}
a.verde:hover, button.verde:hover, button.aprobar:hover { background: #388E3C; }

a.azul {
    background: #6ebbe9;
    color: #1a2433;
    box-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
}
a.azul:hover { background: #2b5f87; color: #fff; }

a.rojo, button.rechazado {
    background: #F44336;
    color: #fff;
    box-shadow: 0 0 8px rgba(244, 67, 54, 0.4);
}
a.rojo:hover, button.rechazado:hover { background: #D32F2F; }

a.gris {
    background: #9E9E9E;
    color: #1a2433;
}
a.gris:hover { background: #616161; color: #fff; }

/* Estilo para el input de unidad */
td input[type="number"] {
    width: 60px !important;
    padding: 4px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: #edf1f6;
    text-align: center;
    margin: 4px auto;
    font-size: 13px;
}
td input[type="number"][name="horas_pendientes"] {
    border: 1px solid #f1c40f;
    color: #f1c40f;
    font-weight: 600;
}

.msg-unidad, .msg-horas, .msg-pago {
    padding: 10px;
    margin: 20px auto;
    width: fit-content;
    border-radius: 8px;
    background: rgba(110, 187, 233, 0.2);
    color: white;
    box-shadow: 0 0 10px rgba(110, 187, 233, 0.5);
}

/* Estilos Específicos Horas */
.pendientes-display {
  color: #f1c40f;
  font-weight: bold;
  text-shadow: 0 0 6px rgba(241, 196, 15, 0.4);
}
td form {
    margin-bottom: 5px;
}

.justificativos-scroll {
    max-height: 150px; /* Define la altura máxima */
    overflow-y: auto; /* Habilita el scroll vertical */
    text-align: left; 
    padding-right: 5px;
    border-radius: 8px; /* Opcional: para que se vea mejor el contenido */
}
/* Responsive */
@media (max-width: 1024px) {
    .sidebar { width: 200px; }
    .main-content {
        margin-left: 200px;
        padding: 90px 10px 40px;
        width: calc(100% - 200px); /* Ajuste para el sidebar de 200px */
        max-width: none;
    }
    table { font-size: 12px; }
    th, td { padding: 8px 5px; }
    td a, td button { font-size: 11px; padding: 4px 6px; }
}

@media (max-width: 768px) {
    .sidebar {
        position: static;
        width: 100%;
        height: auto;
        padding-top: 70px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    .lateral {
        display: flex;
        flex-wrap: wrap;
    }
    .lateral li {
        width: 50%; 
        text-align: center;
        padding: 10px 5px;
        border-bottom: none;
    }
    .lateral li.activa-menu {
        box-shadow: inset 0 -4px 0 #2b5f87;
    }
    .main-content {
        margin-left: 0;
        padding: 20px 10px 40px;
        max-width: 100%;
    }
    body {
        flex-direction: column;
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

    </style>
</head>
<body>
    <header>
        <a href="admin.php" class="logo">
            <img src="logo.jpeg" alt="Logo Cooperativa">
            Panel Admin
        </a>
    </header>

    <div class="sidebar">
        <ul class="lateral">
            <li class="<?= $view === 'miembros' ? 'activa-menu' : '' ?>"><a href="admin.php?view=miembros">👥 Miembros</a></li>
            <li class="<?= $view === 'comprobantes' ? 'activa-menu' : '' ?>"><a href="admin.php?view=comprobantes">💳 Comprobantes</a></li>
            <li class="<?= $view === 'horas' ? 'activa-menu' : '' ?>"><a href="admin.php?view=horas">⏰ Horas</a></li>
            <li class="<?= $view === 'postulaciones' ? 'activa-menu' : '' ?>"><a href="admin.php?view=postulaciones">📄 Postulaciones</a></li>
            <li><a href="pagos.php">🚪 Área Miembro</a></li>
               <form method="post" action="logout.php" class="logout-form">
            <button type="submit" class="logout-btn">
                Cerrar Sesión
            </button>
        </form>
    </div>

    <div class="main-content">

        <?php switch ($view): case 'miembros': ?>
            <h2>Administración de Miembros</h2>

            <?php if (!empty($mensajeUnidad)): ?>
                <div class="msg-unidad">
                    <p style='color: white; text-align:center;'><?= $mensajeUnidad ?></p>
                </div>
            <?php endif; ?>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Miembro</th>
                    <th>Admin</th>
                    <th>Postulación</th>
                    <th>Asignar Unidad</th>
                </tr>
                <?php if (isset($datos_vista['usuarios'])): while($u = $datos_vista['usuarios']->fetch_assoc()): ?>
                <tr>
                    <td><?= $u["id_miembro"] ?></td>
                    <td><?= htmlspecialchars($u["nombre"]) ?></td>
                    <td><?= htmlspecialchars($u["email"]) ?></td>
                    <td>
                        <?= $u["es_miembro"] ? "✅ Sí" : "❌ No" ?><br>
                        <?php if ($u["es_miembro"]): ?>
                            <a class="rojo" href="?view=miembros&id=<?= $u["id_miembro"] ?>&socio=0">Quitar Miembro</a>
                        <?php else: ?>
                            <a class="azul" href="?view=miembros&id=<?= $u["id_miembro"] ?>&socio=1">Hacer Miembro</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $u["admin"] ? "✅ Sí" : "❌ No" ?><br>
                        <?php if ($u["admin"]): ?>
                            <a class="rojo" href="?view=miembros&id=<?= $u["id_miembro"] ?>&admin=0">Quitar Admin</a>
                        <?php else: ?>
                            <a class="azul" href="?view=miembros&id=<?= $u["id_miembro"] ?>&admin=1">Hacer Admin</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $post_check = $conexion->query("SELECT id_postulacion FROM postulacion WHERE id_miembro = " . $u["id_miembro"]);
                        if($post_check->num_rows > 0):
                        ?>
                            <a class="gris" href="admin.php?view=postulaciones">Ver Postulación</a>
                        <?php else: ?>
                            No hay
                        <?php endif; ?>
                    </td>
                    <td>
                        <p style="font-size:12px; margin-bottom: 5px;">Actual: <strong><?= $u["id_unidad"] ?? 'N/A' ?></strong></p>
                        <form method="get" style="display:flex; flex-direction:column; align-items:center;">
                            <input type="hidden" name="view" value="miembros">
                            <input type="hidden" name="id" value="<?= $u["id_miembro"] ?>">
                            <input type="number" name="asignar_unidad" min="1" max="100" placeholder="Unidad">
                            <button type="submit" class="verde">Asignar Unidad</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </table>
            <?php break; case 'comprobantes': ?>
            <h2>Gestión de Comprobantes</h2>

            <?php if (!empty($mensajePago)): ?>
                <div class="msg-pago">
                    <p style='color: white; text-align:center;'><?= $mensajePago ?></p>
                </div>
            <?php endif; ?>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Socio</th>
                    <th>Email</th>
                    <th>Monto</th>
                    <th>Concepto</th>
                    <th>Archivo</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
                <?php if (isset($datos_vista['comprobantes'])): while($c = $datos_vista['comprobantes']->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$c['id_pago'] ?></td>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= number_format((float)$c['monto'],2,',','.') ?></td>
                    <td><?= htmlspecialchars($c['concepto']) ?></td>
                    <td>
                        <?php 
                            // Corrección de enlace: usa la ruta tal cual está en la BD.
                            $comprobante_url = htmlspecialchars($c['comprobante']);
                        ?>
                        <a href="<?= $comprobante_url ?>" target="_blank" class="azul" style="max-width:100%;">Ver Archivo</a>
                    </td>
                    <td>
                        <?php
                        if($c['estado_pa'] === 'aprobado') echo "✅ <span style='color:green;'>Aprobado</span>";
                        elseif($c['estado_pa'] === 'rechazado') echo "❌ <span style='color:red;'>Rechazado</span>";
                        else echo "⏳ Pendiente";
                        ?>
                    </td>
                    <td><?= htmlspecialchars($c['fecha_p']) ?></td>
                    <td>
                        <form method="post" action="admin.php?view=comprobantes">
                            <input type="hidden" name="id" value="<?= (int)$c['id_pago'] ?>">
                            <input type="hidden" name="estado" value="aprobado">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <button class="aprobar" type="submit" <?= $c['estado_pa'] === 'aprobado' ? 'disabled' : '' ?>>Aprobar</button>
                        </form>
                        <form method="post" action="admin.php?view=comprobantes">
                            <input type="hidden" name="id" value="<?= (int)$c['id_pago'] ?>">
                            <input type="hidden" name="estado" value="rechazado">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <button class="rechazado" type="submit" <?= $c['estado_pa'] === 'rechazado' ? 'disabled' : '' ?>>Rechazar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </table>
            <?php break; case 'horas': ?>
            <h2>Gestión de Horas de Socios</h2>

            <?php if (!empty($mensajeHoras)): ?>
                <div class="msg-horas">
                    <p style='color: white; text-align:center;'><?= $mensajeHoras ?></p>
                </div>
            <?php endif; ?>

            <table>
                <tr>
                    <th>Miembro</th>
                    <th>Horas Semanales (Req)</th>
                    <th>Horas Cumplidas</th>
                    <th>Horas Pendientes (Manual)</th> <th>Justificativos</th>
                    <th>Acción</th>
                </tr>
                <?php if (isset($datos_vista['horas'])): while($u = $datos_vista['horas']->fetch_assoc()): ?>
                <form method="POST" action="admin.php?view=horas">
                    <tr>
                        <td><?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['email']) ?>)</td>
                        <td><input type="number" name="semanales_req" value="<?= (int)($u['semanales_req'] ?? 0) ?>"></td>
                        <td><input type="number" name="cumplidas" value="<?= (int)($u['cumplidas'] ?? 0) ?>"></td>
                        
                        
                        <td><input type="number" name="horas_pendientes" value="<?= (int)($u['horas_pendientes'] ?? 0) ?>"></td>

                            <td>
                            <div class="justificativos-scroll" style="font-size:13px;">
                            <?php
                              $items = explode("|", $u['justificativos'] ?? '');
                                foreach ($items as $item) {
                                $item = trim($item);
                                if ($item === '') continue;


                                      parse_str(str_replace(";", "&", $item), $data); 

                                        if (isset($data['asistio']) && (int)$data['asistio'] === 1) {
                                            echo "✅ Asistió<br>";
                                        if (!empty($data['horas'])) { echo "Horas: " . (int)$data['horas'] . "<br>"; }
                                        if (!empty($data['actividad'])) { echo "Actividad: " . htmlspecialchars($data['actividad']) . "<br>"; }
                                            } else {
                                                    echo "❌ No asistió<br>";
                                        if (!empty($data['justificativo'])) {
                                            $just = trim($data['justificativo']);
                                            $ext = strtolower(pathinfo($just, PATHINFO_EXTENSION));
                                        if ($just !== '' && in_array($ext, ['pdf','jpg','jpeg','png'])) {
                                            $ruta = "justificativos/" . basename($just); 
                                            echo 'Justificativo: <a href="'.htmlspecialchars($ruta).'" target="_blank">'.htmlspecialchars(basename($just)).'</a><br>';
                                            } else {
                                                    echo "Justificativo: " . htmlspecialchars($just) . "<br>"; }
                                          }
                                        }
                                          echo "<span style=\"display:block;height:1px;background:rgba(255,255,255,0.1);margin:6px 0;\"></span>";
                                      }
                              ?>
                            </div>
                            </td>
                            <td>

                            <input type="hidden" name="id_miembro" value="<?= $u['id_miembro'] ?>">
                            <button type="submit" name="guardar" class="azul">Guardar</button>
                            
                            <?php if ((int)($u['horas_pendientes'] ?? 0) > 0): ?>
                                <button type="submit" name="aprobar_pendientes" class="aprobar">Aprobar Pendientes</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                </form>
                <?php endwhile; endif; ?>
            </table>
            <?php break; case 'postulaciones': ?>
            <h2>Listado de Postulaciones</h2>

            <?php if (isset($datos_vista['postulaciones']) && $datos_vista['postulaciones']->num_rows > 0): ?>
            <div style="overflow-x: auto;">
               <table style="width: 100%;">
                    <tr>
                    <th>ID</th>
                    <th>Miembro</th>
                    <th>Cant. menores</th>
                    <th>Trabajo</th>
                    <th>Tipo contrato</th>
                    <th>Ingresos nominales</th>
                    <th>Ingresos familiares</th>
                    <th>Observación salud</th>
                    <th>Constitución familiar</th>
                    <th>Vivienda actual</th>
                    <th>Gasto vivienda</th>
                    <th>Nivel educativo</th>
                    <th>Hijos estudiando</th>
                    <th>Patrimonio</th>
                    <th>Disp. ayuda</th>
                    <th>Motivación</th>
                    <th>Presentado por</th>
                    <th>Referencia</th>
                    <th>Estado</th>
                    <th>Comentario admin</th>
                    <th>Fecha</th>
                </tr>
                <?php while($postulacion = $datos_vista['postulaciones']->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($postulacion["id_postulacion"]) ?></td>
                    <td><?= htmlspecialchars($postulacion["nombre_miembro"] ?? $postulacion["email_miembro"] ?? "Desconocido") ?></td>
                    <td><?= htmlspecialchars($postulacion["cantidad_menores"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["trabajo"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["tipo_contrato"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["ingresos_nominales"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["ingresos_familiares"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["observacion_salud"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["constitucion_familiar"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["vivienda_actual"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["gasto_vivienda"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["nivel_educativo"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["hijos_estudiando"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["patrimonio"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["disponibilidad_ayuda"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["motivacion"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["presentado_por"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["referencia_contacto"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["estado_po"] ?? "pendiente") ?></td>
                    <td><?= htmlspecialchars($postulacion["comentarios_admin"] ?? "") ?></td>
                    <td><?= htmlspecialchars($postulacion["fecha_postulacion"] ?? "") ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
            </div>

            <?php else: ?>
            <p>No hay postulaciones registradas.</p>
            <?php endif; ?>
            <?php break; endswitch; ?>

    </div>
</body>
</html>