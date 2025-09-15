<?php
session_start();

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
<style>
body {
  font-family: "Poppins", sans-serif;
  background-image: url('landingpage.jpg');
  background-size: cover;
  background-position: center;
  margin: 0;
  padding: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
header {
  background-color: rgba(44,62,80,0.9);
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  font-weight: 600;
}
main {
  flex: 1;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
}
h1 {
  color: white;
  margin-bottom: 20px;
}
table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  background: rgba(255,255,255,0.95);
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  margin-bottom: 20px;
}
th, td {
  border: 1px solid #ddd;
  padding: 10px;
  font-size: 14px;
  text-align: center;
}
th {
  background: #224358;
  color: white;
}
input[type="number"] {
  width: 80px;
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
}
button {
  background: #27ae60;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  margin: 4px 2px;
}
button:hover {
  background: #219150;
}
.pendientes {
  color: #e67e22;
  font-weight: bold;
}
.btn-container {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 30px;
  flex-wrap: wrap;
}
.btn-container form {
  margin: 0;
}
.btn {
  background: linear-gradient(to right, #224358, #163041ff);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.25);
  background: linear-gradient(to right, #000508ff, #000502ff);
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
</style>
</head>
<body>
<header>
  <img src="logo.jpeg" alt="Logo">
  <span class="titulo-header">Panel Root - Administrador de Horas</span>  
</header>
<main>
  <h1>Gestión de Horas de Socios</h1>

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
</main>
</body>
</html>
