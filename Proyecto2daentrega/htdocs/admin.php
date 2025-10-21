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
      margin: 40px 0 30px;
      font-weight: 600;
      color: #6ebbe9;
      text-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
      letter-spacing: 1px;
      font-size: 34px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(8px);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
      margin-bottom: 40px;
      animation: fadeIn 0.8s ease-in-out;
    }

    th, td {
      padding: 14px;
      text-align: center;
      color: #edf1f6;
    }

    th {
      background: rgba(110, 187, 233, 0.15);
      color: #6ebbe9;
      font-size: 15px;
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

    td {
      font-size: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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

    a.verde { background: #27ae60; }
    a.verde:hover { background: #2ecc71; box-shadow: 0 0 12px rgba(46, 204, 113, 0.4); }

    a.azul { background: #2980b9; }
    a.azul:hover { background: #3498db; box-shadow: 0 0 12px rgba(52, 152, 219, 0.4); }

    a.rojo { background: #c0392b; }
    a.rojo:hover { background: #e74c3c; box-shadow: 0 0 12px rgba(231, 76, 60, 0.4); }

    a.gris { background: #7f8c8d; }
    a.gris:hover { background: #95a5a6; box-shadow: 0 0 12px rgba(149, 165, 166, 0.4); }

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
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 40px;
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
    </tr>
    <?php while($u = $usuarios->fetch_assoc()): ?>
    <tr>
      <td><?= $u["id_miembro"] ?></td>
      <td><?= $u["nombre"] ?></td>
      <td><?= $u["email"] ?></td>
      <td><?= $u["es_miembro"] ? "✅ Sí" : "❌ No" ?> <br>
      <?php if ($u["es_miembro"]): ?>
          <a class="rojo" href="?id=<?= $u["id_miembro"] ?>&socio=0">Quitar miembro</a>
        <?php else: ?>
          <a class="azul" href="?id=<?= $u["id_miembro"] ?>&socio=1">Hacer miembro</a>
        <?php endif; ?></td>
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
