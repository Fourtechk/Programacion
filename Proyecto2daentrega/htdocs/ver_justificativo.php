<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
require "conexion.php";

$datos = $conexion->prepare("SELECT justificativos FROM horas WHERE id_miembro = ?");
$datos->bind_param("i", $id);
$datos->execute();
$res = $datos->get_result();
$row = $res->fetch_assoc();

$items = explode("|", $row['justificativos'] ?? '');
$ultimo = end($items);
parse_str(str_replace(";", "&", $ultimo), $data);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Justificativo del Usuario</title>
</head>
<body>
  <h2>Justificativo del usuario #<?= $id ?></h2>

  <p><strong>Asistió:</strong> <?= $data['asistio'] ?? 'N/A' ?></p>

  <?php if (isset($data['horas'])): ?>
    <p><strong>Horas:</strong> <?= $data['horas'] ?></p>
  <?php endif; ?>

  <?php if (isset($data['actividad'])): ?>
    <p><strong>Actividad:</strong> <?= htmlspecialchars($data['actividad']) ?></p>
  <?php endif; ?>

  <?php
  if (!empty($data['justificativo'])) {
      $archivo = trim($data['justificativo']);
      $ruta = $_SERVER['DOCUMENT_ROOT'] . '/' . $archivo;

      if (file_exists($ruta)) {
          echo '<p><strong>Archivo:</strong> <a href="http://localhost/' . htmlspecialchars($archivo) . '" target="_blank">Abrir justificativo</a></p>';
      } else {
          echo "<p style='color:red;'>⚠ El archivo no fue encontrado en el servidor.</p>";
      }
  } else {
      echo "<p>No hay archivo de justificativo registrado.</p>";
  }
  ?>
</body>
</html>
