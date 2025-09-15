<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["archivo"])) {
    $nombre = basename($_FILES["archivo"]["name"]);
    $carpeta = $_SERVER['DOCUMENT_ROOT'] . "/justificativos";
    $ruta = $carpeta . "/" . $nombre;

    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

    if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $ruta)) {
        echo "<p>✅ Archivo subido correctamente.</p>";
        echo '<p><a href="http://localhost/justificativos/' . htmlspecialchars($nombre) . '" target="_blank">Abrir archivo</a></p>';
    } else {
        echo "<p>❌ Error al subir el archivo.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Test de Subida</title>
</head>
<body>
  <h2>Subí un archivo de prueba</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png">
    <button type="submit">Subir</button>
  </form>
</body>
</html>
