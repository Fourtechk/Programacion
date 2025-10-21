<?php
session_start();
require_once "conexion.php";
$conexion = new mysqli($host, $user, $pass, $db, $port);

// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=sin_sesion");
    exit;
}

// Verificar si el usuario es administrador
$id = intval($_SESSION['id']);
$resultado = $conexion->query("SELECT admin FROM miembro WHERE id_miembro = $id");

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: index.html?error=usuario_no_encontrado");
    exit;
}

$datos = $resultado->fetch_assoc();
if (intval($datos['admin']) !== 1) {
    header("Location: index.html?error=no_admin");
    exit;
}

require "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_miembro = isset($_POST["id_miembro"]) ? intval($_POST["id_miembro"]) : 0;

    if ($id_miembro > 0) {
        // Verificar si existe la fila
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

            $update = $conexion->prepare("UPDATE horas SET semanales_req=?, cumplidas=? WHERE id_miembro=?");
            $update->bind_param("iii", $semanales_req, $cumplidas, $id_miembro);
            $update->execute();

            header("Location: admin_horas.php?msg=guardado&id=$id_miembro");
            exit;
        }

        if (isset($_POST["aprobar_pendientes"])) {
            // Leer cuántas horas pendientes hay
            $sel = $conexion->prepare("SELECT horas_pendientes FROM horas WHERE id_miembro = ?");
            $sel->bind_param("i", $id_miembro);
            $sel->execute();
            $resSel = $sel->get_result();
            $rowSel = $resSel->fetch_assoc();
            $pend = (int)($rowSel["horas_pendientes"] ?? 0);

            // Guardar registro en justificativos si hay pendientes
            if ($pend > 0) {
                $registro = "asistio=1;horas={$pend};actividad=Aprobadas por admin";
                $upJust = $conexion->prepare("
                    UPDATE horas
                    SET justificativos = CONCAT_WS('|', justificativos, ?)
                    WHERE id_miembro = ?
                ");
                $upJust->bind_param("si", $registro, $id_miembro);
                $upJust->execute();
            }

            // Mover pendientes a cumplidas
            $update = $conexion->prepare("
                UPDATE horas
                SET cumplidas = cumplidas + horas_pendientes,
                    horas_pendientes = 0
                WHERE id_miembro = ?
            ");
            $update->bind_param("i", $id_miembro);
            $update->execute();

            header("Location: admin_horas.php?msg=pendientes_aprobadas&id=$id_miembro");
            exit;
        }
    }
}

$usuarios = $conexion->query("
  SELECT m.id_miembro, m.nombre, m.email, h.semanales_req, h.cumplidas, h.horas_pendientes, h.justificativos
  FROM miembro m
  LEFT JOIN horas h ON m.id_miembro = h.id_miembro
  WHERE m.es_miembro = 1
  ORDER BY m.nombre ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Administrador de Horas - Root</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100px 20px 40px;
  color: #edf1f6;
}

/* ======== HEADER ======== */
header {
  background: rgba(26, 36, 51, 0.9);
  backdrop-filter: blur(10px);
  height: 70px;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

.logo {
  display: flex;
  align-items: center;
  height: 100%;
}
.logo img {
  height: 54px;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
}

/* ======== TÍTULO ======== */
h2 {
  text-align: center;
  font-size: 32px;
  color: #6ebbe9;
  text-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
  margin-bottom: 30px;
  font-weight: 600;
}

/* ======== TABLA ======== */
table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
  margin-bottom: 25px;
  animation: fadeIn 0.8s ease-in-out;
}

th, td {
  padding: 12px;
  font-size: 15px;
  text-align: center;
  color: #edf1f6;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

th {
  background: rgba(110, 187, 233, 0.15);
  color: #6ebbe9;
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

/* ======== INPUTS ======== */
input[type="number"] {
  width: 90px;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.1);
  color: #edf1f6;
  text-align: center;
  transition: 0.3s;
}
input[type="number"]:focus {
  border-color: #6ebbe9;
  background: rgba(255, 255, 255, 0.2);
  outline: none;
}

/* ======== BOTONES DE TABLA ======== */
button {
  background: #6ebbe9;
  color: #1a2433;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  margin: 4px 2px;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
}
button:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 12px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

/* ======== TEXTO DESTACADO ======== */
.pendientes {
  color: #f1c40f;
  font-weight: bold;
  text-shadow: 0 0 6px rgba(241, 196, 15, 0.4);
}

/* ======== CONTENEDOR DE BOTONES PRINCIPALES ======== */
.btn-container {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 20px;
  margin-top: 30px;
}

.btn {
  background: #6ebbe9;
  color: #1a2433;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
  transition: all 0.3s ease;
}
.btn:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

/* ======== ANIMACIÓN ======== */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ======== RESPONSIVE ======== */
@media (max-width: 768px) {
  h2 { font-size: 26px; }
  table { font-size: 14px; }
  th, td { padding: 8px; }
  .btn { font-size: 14px; padding: 10px 18px; }
}
</style>
</head>
<body>
<header>
  <a class="logo">
    <img src="logo.jpeg" alt="Logo Cooperativa">
  </a>
</header>



<!-- Aquí sigue tu tabla y funcionalidad PHP original -->
</body>
</html>

  <h2>Gestión de Horas de Socios</h2>

  <?php if (isset($_GET["msg"])): ?>
    <p style="background:#dff0d8;color:#3c763d;padding:10px;border-radius:8px;text-align:center;">
      ✅ <?= $_GET["msg"] === "guardado" ? "Cambios guardados" : "Pendientes aprobadas" ?> para el usuario #<?= htmlspecialchars($_GET["id"] ?? "") ?>
    </p>
  <?php endif; ?>

  <table>
    <tr>
      <th>Miembro</th>
      <th>Horas Semanales</th>
      <th>Horas Cumplidas</th>
      <th>Horas Pendientes</th>
      <th>Justificativos</th>
      <th>Acción</th>
    </tr>
    <?php while($u = $usuarios->fetch_assoc()): ?>
    <form method="POST">
      <tr>
        <td><?= htmlspecialchars($u['nombre']) ?> (<?= $u['email'] ?>)</td>
        <td><input type="number" name="semanales_req" value="<?= $u['semanales_req'] ?? 0 ?>"></td>
        <td><input type="number" name="cumplidas" value="<?= $u['cumplidas'] ?? 0 ?>"></td>
        <td class="pendientes"><?= $u['horas_pendientes'] ?? 0 ?></td>
        <td style="text-align:left; font-size:13px;">
  <?php
    $items = explode("|", $u['justificativos'] ?? '');
    foreach ($items as $item) {
      $item = trim($item);
      if ($item === '') continue;

      // Parsear formato "asistio=1;horas=..;actividad=.." o "asistio=0;justificativo=.."
      parse_str(str_replace(";", "&", $item), $data);

      if (isset($data['asistio']) && (int)$data['asistio'] === 1) {
          echo "✅ Asistió<br>";
          if (!empty($data['horas'])) {
              echo "Horas: " . (int)$data['horas'] . "<br>";
          }
          if (!empty($data['actividad'])) {
              echo "Actividad: " . htmlspecialchars($data['actividad']) . "<br>";
          }
      } else {
          echo "❌ No asistió<br>";
          if (!empty($data['justificativo'])) {
              $just = trim($data['justificativo']);
              $ext = strtolower(pathinfo($just, PATHINFO_EXTENSION));
              if ($just !== '' && in_array($ext, ['pdf','jpg','jpeg','png'])) {
                  // 🔥 Aquí la corrección: que siempre busque en la carpeta "justificativos"
                  $ruta = "justificativos/" . basename($just);
                  echo 'Justificativo: <a href="'.htmlspecialchars($ruta).'" target="_blank">'.htmlspecialchars(basename($just)).'</a><br>';
              } else {
                  echo "Justificativo: " . htmlspecialchars($just) . "<br>";
              }
          }
      }

      // Separador visual
      echo "<span style=\"display:block;height:1px;background:#e9ecef;margin:6px 0;\"></span>";
    }
  ?>
</td>

        <td>
          <input type="hidden" name="id_miembro" value="<?= $u['id_miembro'] ?>">
          <button type="submit" name="guardar">Guardar</button>
          <?php if (($u['horas_pendientes'] ?? 0) > 0): ?>
            <button type="submit" name="aprobar_pendientes">Aprobar</button>
          <?php endif; ?>
        </td>
      </tr>
    </form>
    <?php endwhile; ?>
  </table>

  <div class="btn-container">
    <form action="admin.php" method="post">
      <button type="submit" class="btn">Aprobar Usuarios</button>
    </form>
    <form action="admin_comprobantes.php" method="post">
      <button type="submit" class="btn">Comprobantes</button>
    </form>
    <form action="admin_postulacion.php" method="post">
      <button type="submit" class="btn">Administrar Posstulaciones</button>
    </form>
    <form action="pagos.php" method="get">
    <button type="submit" class="btn">Ver Pagos</button>
  </form>
  </div>
</body>
</html>
