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

// Verificar que sea miembro o admin
$sql = "SELECT es_miembro, admin, nombre FROM miembro WHERE id_miembro = ?";
$stmtUser = $conexion->prepare($sql);
$stmtUser->bind_param("i", $id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser ? $resultUser->fetch_assoc() : null;

if (!$user || ($user["es_miembro"] != 1 && $user["admin"] != 1)) {
    header("Location: pagina.php");
    exit();
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
  <link rel="stylesheet" href="landingpage.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a2433, #2b5f87);
            color: #edf1f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            color: #6ebbe9;
            text-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
        }

        .tabla-contenedor {
            width: 100%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            padding: 20px;
            animation: fadeIn 0.8s ease-in-out;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            background: rgba(255, 255, 255, 0.1);
        }

        th, td {
            padding: 14px 16px;
            text-align: center;
        }

        th {
            color: #6ebbe9;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        tr {
            transition: background 0.3s ease;
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.04);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        td {
            color: #edf1f6;
            font-weight: 300;
        }

        .boton-volver {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 22px;
            background-color: #6ebbe9;
            color: #1a2433;
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(110, 187, 233, 0.4);
        }

        .boton-volver:hover {
            background-color: #2b5f87;
            color: #fff;
            box-shadow: 0 0 15px rgba(110, 187, 233, 0.6);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .tabla-contenedor {
                padding: 10px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
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
  <main>
    <div class="form-wrapper">
      <?php if ($user): ?>
        <h1 class="titulo-principal">Bienvenido, <span><?= htmlspecialchars($user['nombre']) ?></span></h1>
      <?php endif; ?>

      <!-- Caja: Subir comprobante -->
      <div class="form-box">
        <h2>Subir comprobante</h2>
        <form action="subir_comprobante.php" method="POST" enctype="multipart/form-data">
          <input type="number" name="monto" placeholder="Monto" required>
          <input type="text" name="concepto" placeholder="Concepto" required>
          <input type="file" name="archivo" required>
          <button type="submit" class="postularme">Subir comprobante</button>
        </form>
      </div>

      <!-- Caja: Historial de comprobantes -->
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
      <!-- Caja: Mis Horas -->
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
                      if ($just !== '' && in_array($ext, ['pdf','jpg','jpeg','png'])) {
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

    

  </main>

 
<?php if (!empty($user) && $user['admin'] == 1): ?>
  <div style="margin-top: 30px; text-align: center;">
    <form action="admin.php" method="post">
      <button type="submit" style="
        background: #34495e;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 20px;
        font-size: 15px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        transition: background 0.2s ease;
      ">Ir al Panel de Administración</button>
    </form>
  </div>
<?php endif; ?>

</div>

</body>
</html>