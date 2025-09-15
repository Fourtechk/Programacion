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


if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Traer todas las postulaciones junto con el nombre del miembro
$sql = "SELECT p.*, m.nombre AS nombre_miembro, m.email AS email_miembro 
        FROM postulacion p
        LEFT JOIN miembro m ON p.id_miembro = m.id_miembro
        ORDER BY p.fecha_postulacion DESC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Administrar Postulaciones</title>
<style>
  /* Estilo general */
  body {
      font-family: 'Arial', sans-serif;
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      margin: 0;
      padding: 40px;
      color: #2c3e50;
  }

  h2 {
      text-align: center;
      font-size: 32px;
      color: white;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.6);
      margin-bottom: 30px;
  }

  /* Tabla */
  table {
      width: 100%;
      border-collapse: collapse;
      background: rgba(255,255,255,0.95);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  }

  th, td {
      padding: 10px 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
      font-size: 14px;
  }

  th {
      background-color: #34495e;
      color: white;
      font-weight: 600;
  }

  tr:hover {
      background-color: rgba(52, 152, 219, 0.1);
  }

  /* Botones */
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

  /* Contenedor botones */
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
</style>
</head>
<header>
  <img src="logo.jpeg" alt="Logo">
  <span class="titulo-header">Panel Root - Administrador de Postulaciones</span>  
</header>
<body>

<h2>Listado de Postulaciones</h2>

<?php if ($resultado && $resultado->num_rows > 0): ?>
<table>
  <tr>
    <th>ID</th>
    <th>Miembro</th>
    <th>Cantidad menores</th>
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
    <th>Disponibilidad ayuda</th>
    <th>Motivación</th>
    <th>Presentado por</th>
    <th>Referencia</th>
    <th>Estado</th>
    <th>Comentario admin</th>
    <th>Fecha</th>
  </tr>
  <?php while($postulacion = $resultado->fetch_assoc()): ?>
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

<?php else: ?>
<p>No hay postulaciones registradas.</p>
<?php endif; ?>

<div class="btn-container">
  <form action="admin.php" method="post">
    <button type="submit" class="btn">Aprobar Usuarios</button>
  </form>
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
