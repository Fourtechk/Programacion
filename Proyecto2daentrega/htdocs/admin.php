<?php
session_start(); // ← CORREGIDO: activa la sesión antes de usar $_SESSION
$conexion = new mysqli("localhost", "root", "", "sistema");

// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=sin_sesion");
    exit;
}

// Verificar si el usuario es administrador
$id = intval($_SESSION['id']);
$resultado = $conexion->query("SELECT admin FROM miembro WHERE id_miembro = $id");

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: landingpage.html?error=usuario_no_encontrado");
    exit;
}

$datos = $resultado->fetch_assoc();
if (intval($datos['admin']) !== 1) {
    header("Location: landingpage.html?error=no_admin");
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin</title>
  <style>
    body {
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      font-family: "Poppins", sans-serif;
      padding: 20px;
      margin: 0;
      color: #2c3e50;
    }

    h2 {
      text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
      text-align: center;
      margin: 40px 0 30px;
      color: #ffffff;
      font-size: 36px;
      font-weight: bold;
      letter-spacing: 1px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255,255,255,0.95);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(0,0,0,0.25);
      margin-bottom: 40px;
    }

    th, td {
      padding: 14px;
      text-align: center;
    }

    th {
      background: linear-gradient(135deg, #34495e, #2c3e50);
      color: white;
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    tr:nth-child(even) { background: #f4f6f7; }
    tr:nth-child(odd) { background: #ffffff; }

    td {
      font-size: 15px;
      border-bottom: 1px solid #ddd;
    }

    a {
      display: inline-block;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      color: white;
      transition: 0.3s ease;
    }

    a.verde { background: #27ae60; }
    a.verde:hover { background: #2ecc71; }

    a.azul { background: #2980b9; }
    a.azul:hover { background: #3498db; }

    a.rojo { background: #c0392b; }
    a.rojo:hover { background: #e74c3c; }

    a.gris { background: #7f8c8d; }
    a.gris:hover { background: #95a5a6; }

    .btn {
      padding: 12px 24px;
      background-color: #34495e;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s, transform 0.2s;
      margin-top: 20px;
      margin-right: 20px;
    }

    .btn:hover {
      background-color: #2c3e50;
      transform: translateY(-2px);
    }

    .btn-container {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 30px;
    }

    p {
      text-align: center;
      font-size: 16px;
      color: white;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }
    header {
      background-color: rgba(44, 62, 80, 0.9);
      height: 60px;
      width: 99.9%;
      left:0px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      position: absolute;
      top: 0px;

      z-index: 10;
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
    <a href="landingpage.html" class="logo">
      <img src="logo.jpeg" alt="Logo Cooperativa">
    </a>
    <div class="top-bar"></div>
  </header>
  <h2>📋 Panel de Administración</h2>
  
  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Email</th>
      <th>Aprobado</th>
      <th>Miembro</th>
      <th>Admin</th>
      <th>Acciones</th>
      <th>Postulación</th>
    </tr>
    <?php while($u = $usuarios->fetch_assoc()): ?>
    <tr>
      <td><?= $u["id_miembro"] ?></td>
      <td><?= $u["nombre"] ?></td>
      <td><?= $u["email"] ?></td>
      <td><?= $u["aprobado"] ? "✅ Sí" : "❌ No" ?></td>
      <td><?= $u["es_miembro"] ? "✅ Sí" : "❌ No" ?></td>
      <td>
        <?= $u["admin"] ? "✅ Sí" : "❌ No" ?><br>
        <?php if ($u["admin"]): ?>
          <a class="rojo" href="?id=<?= $u["id_miembro"] ?>&admin=0">Quitar admin</a>
        <?php else: ?>
          <a class="azul" href="?id=<?= $u["id_miembro"] ?>&admin=1">Hacer admin</a>
        <?php endif; ?>
      </td>
      <td>
        <?php if (!$u["aprobado"]): ?>
          <a class="verde" href="?aprobar=<?= $u["id_miembro"] ?>">Aprobar</a>
        <?php endif; ?>

        <?php if ($u["es_miembro"]): ?>
          <a class="rojo" href="?id=<?= $u["id_miembro"] ?>&socio=0">Quitar miembro</a>
        <?php else: ?>
          <a class="azul" href="?id=<?= $u["id_miembro"] ?>&socio=1">Hacer miembro</a>
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
  <!-- Nuevo botón para ir a pagos.php -->
  <form action="pagos.php" method="get">
    <button type="submit" class="btn">Ver Pagos</button>
  </form>
</div>
</body>
</html>
