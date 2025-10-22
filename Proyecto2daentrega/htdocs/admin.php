<?php
session_start(); // ← CORREGIDO: activa la sesión antes de usar $_SESSION
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

// Aprobar miembro
if (isset($_GET["aprobar"])) {
    $id = intval($_GET["aprobar"]);
    $conexion->query("UPDATE miembro SET aprobado=1 WHERE id_miembro=$id");
}

// Cambiar estado de miembro
if (isset($_GET["socio"]) && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    $socio = intval($_GET["socio"]);
    $conexion->query("UPDATE miembro SET es_miembro=$socio WHERE id_miembro=$id");
}

// Cambiar estado de administrador
if (isset($_GET["admin"]) && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    $admin = intval($_GET["admin"]);
    $conexion->query("UPDATE miembro SET admin=$admin WHERE id_miembro=$id");
}

// Traer todos los miembros
$usuarios = $conexion->query("SELECT * FROM miembro");

// Asignar unidad habitacional
if (isset($_GET["asignar_unidad"]) && isset($_GET["id"])) {
    $id_unidad = intval($_GET["asignar_unidad"]);
    $id_miembro = intval($_GET["id"]);

    if ($id_unidad < 1 || $id_unidad > 100) {
        echo "<p style='color:red; text-align:center;'>❌ Unidad fuera de rango (1–100)</p>";
    } else {
        // Verificar estado de la unidad
        $stmt = $conexion->prepare("SELECT estado_un FROM unidad_habitacional WHERE id_unidad = ?");
        $stmt->bind_param("i", $id_unidad);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            echo "<p style='color:red; text-align:center;'>❌ La unidad no existe</p>";
        } else {
            $estado = $res->fetch_assoc()['estado_un'];

            // Verificar si el miembro ya tiene esa unidad
            $check = $conexion->prepare("SELECT id_unidad FROM miembro WHERE id_miembro = ?");
            $check->bind_param("i", $id_miembro);
            $check->execute();
            $resCheck = $check->get_result();
            $unidadActual = $resCheck->fetch_assoc()['id_unidad'] ?? null;

            if ($unidadActual == $id_unidad) {
                echo "<p style='color:gray; text-align:center;'>ℹ️ El miembro ya tiene asignada la unidad #$id_unidad</p>";
            } elseif ($estado === 'mantenimiento') {
                echo "<p style='color:orange; text-align:center;'>⚠️ La unidad está en mantenimiento</p>";
            } elseif ($estado === 'ocupada') {
                echo "<p style='color:red; text-align:center;'>❌ La unidad ya está ocupada</p>";
            } else {
                // Asignar unidad al miembro y marcar como ocupada
                $conexion->query("UPDATE miembro SET id_unidad = $id_unidad WHERE id_miembro = $id_miembro");
                $conexion->query("UPDATE unidad_habitacional SET estado_un = 'ocupada' WHERE id_unidad = $id_unidad");
                echo "<p style='color:green; text-align:center;'>✅ Unidad #$id_unidad asignada correctamente</p>";
            }
        }
    }
}

?>



<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
      padding: 90px 20px 40px;
      min-height: 100vh;
      margin-right: 260px;
    }

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
      height: 50px;
      width: auto;
      border-radius: 50%;
      box-shadow: 0 0 8px rgba(255, 255, 255, 0.2);
    }

    h2 {
  text-align: center;
  font-size: 32px;
  color: #ffffffff;
  text-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
  margin-bottom: 30px;
  font-weight: 600;
}

    /* ======== TABLA ESTILIZADA ======== */
table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
  margin-bottom: 40px;
  animation: fadeIn 0.8s ease-in-out;
  margin-left: 300px;
}

th, td {
  padding: 14px 16px;
  font-size: 15px;
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

td a {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #1a2433;
  background: #6ebbe9;
  text-decoration: none;
  box-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
  transition: all 0.3s ease;
}

td a:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 12px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}


    a {
      display: inline-block;
      padding: 8px 14px;
      border-radius: 10px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      color: #fff;
      transition: all 0.3s ease;
      box-shadow: 0 0 6px rgba(255, 255, 255, 0.2);
    }

   a.verde {
  background: #6ebbe9;
  color: #1a2433;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
  transition: all 0.3s ease;
}
a.verde:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

a.azul,
a.rojo,
a.gris {
  background: #6ebbe9;
  color: #1a2433;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
  transition: all 0.3s ease;
}
a.azul:hover,
a.rojo:hover,
a.gris:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

    .btn {
      padding: 12px 24px;
      background-color: #6ebbe9;
      color: #1a2433;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
      margin-top: 20px;
      margin-right: 20px;
      box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
    }

    .btn:hover {
      background-color: #2b5f87;
      color: #fff;
      box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
      transform: translateY(-2px);
    }

    .btn-container {
  position: fixed;
  top: 70px;
  right: 0;
  width: 260px;
  height: calc(102% - 90px);
  background: rgba(26, 36, 51, 0.9);
  backdrop-filter: blur(10px);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.3);
  z-index: 9;
}

.btn-container form {
  width: 100%;
}

.btn-container .btn {
  width: 100%;
  padding: 12px;
  font-size: 15px;
  border-radius: 8px;
}

.btn:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}


    p {
      text-align: center;
      font-size: 16px;
      color: #edf1f6;
      opacity: 0.9;
      margin-top: 10px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      h2 { font-size: 28px; }
      table { font-size: 14px; }
      th, td { padding: 10px; }
      .btn { font-size: 14px; margin: 10px; }
    }
    @media (max-width: 1024px) {
  .btn-container {
    width: 220px;
  }

  body {
    margin-right: 220px;
  }
}

@media (max-width: 768px) {
  .btn-container {
    position: static;
    width: 100%;
    height: auto;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    padding: 15px;
    box-shadow: none;
    background: rgba(26, 36, 51, 0.8);
  }

  .btn-container .btn {
    width: auto;
    min-width: 140px;
    margin: 10px;
    font-size: 14px;
    padding: 10px 18px;
  }

  body {
    margin-right: 0;
    padding-top: 120px;
  }
}

  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="logo.png" alt="Logo">
    </div>
  </header>

  
</body>
</html>

<body>
  <header>
      <a class="logo">
      <img src="logo.jpeg" alt="Logo Cooperativa">
    </a>
     <div class="top-bar"></div>
  </header>
  <h2>Panel de Administración</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Email</th>
    <th>Miembro</th>
    <th>Admin</th>
    <th>Postulación</th>
    <th>Asignar Unidad</th> <!-- NUEVA COLUMNA -->
  </tr>
  <?php while($u = $usuarios->fetch_assoc()): ?>
  <tr>
    <td><?= $u["id_miembro"] ?></td>
    <td><?= $u["nombre"] ?></td>
    <td><?= $u["email"] ?></td>
    <td>
      <?= $u["es_miembro"] ? "✅ Sí" : "❌ No" ?><br>
      <?php if ($u["es_miembro"]): ?>
        <a class="rojo" href="?id=<?= $u["id_miembro"] ?>&socio=0">Quitar miembro</a>
      <?php else: ?>
        <a class="azul" href="?id=<?= $u["id_miembro"] ?>&socio=1">Hacer miembro</a>
      <?php endif; ?>
    </td>
    <td>
      <?= $u["admin"] ? "✅ Sí" : "❌ No" ?><br>
      <?php if ($u["admin"]): ?>
        <a class="rojo" href="?id=<?= $u["id_miembro"] ?>&admin=0">Quitar admin</a>
      <?php else: ?>
        <a class="azul" href="?id=<?= $u["id_miembro"] ?>&admin=1">Hacer admin</a>
      <?php endif; ?>
    </td>
    <td>
      <?php
      $id_miembro = $u["id_miembro"];
      $post = $conexion->query("SELECT id_postulacion FROM postulacion WHERE id_miembro = $id_miembro");
      if($post->num_rows > 0):
      ?>
        <a class="gris" href="admin_postulacion.php?id=<?= $id_miembro ?>">Ver Postulación</a>
      <?php else: ?>
        No hay
      <?php endif; ?>
    </td>
    <td>
      <form method="get" style="display:flex; flex-direction:column; align-items:center;">
        <input type="hidden" name="id" value="<?= $u["id_miembro"] ?>">
        <input type="number" name="asignar_unidad" min="1" max="100" placeholder="Unidad" style="width:80px; padding:6px; border-radius:6px; border:none; margin-bottom:6px;">
        <button type="submit" class="verde">Asignar</button>
      </form>
    </td>
  </tr>
  <?php endwhile; ?>
</table>


  <div class="btn-container">
  
  <form action="admin_comprobantes.php" method="post">
    <button type="submit" class="btn">Comprobantes</button>
  </form>
  <form action="admin_horas.php" method="post">
    <button type="submit" class="btn">Administrar Horas</button>
  </form>
  <form action="pagos.php" method="get">
    <button type="submit" class="btn">Ver Pagos</button>
  </form>
</div>
</body>
</html>
