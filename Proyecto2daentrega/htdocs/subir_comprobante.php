<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$id_miembro = $_SESSION['id_miembro'] ?? $_SESSION['id'];

// Validar datos
$monto = $_POST['monto'] ?? null;
$concepto = $_POST['concepto'] ?? null;

if (!$monto || !$concepto || !isset($_FILES['archivo'])) {
    header("Location: pagos.php?err=Datos incompletos");
    exit;
}

// Validar archivo
$archivo = $_FILES['archivo'];
$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$permitidos = ['jpg','jpeg','png','pdf'];

if (!in_array($ext, $permitidos)) {
    header("Location: pagos.php?err=Formato no permitido");
    exit;
}

if ($archivo['size'] > 5 * 1024 * 1024) { // 5 MB
    header("Location: pagos.php?err=Archivo demasiado grande");
    exit;
}

// Crear carpeta comprobantes si no existe
$carpeta = "comprobantes/";
if (!is_dir($carpeta)) { mkdir($carpeta, 0777, true); }

// Guardar archivo
$nombre_archivo = $carpeta . time() . "_" . basename($archivo['name']);
if (!move_uploaded_file($archivo['tmp_name'], $nombre_archivo)) {
    header("Location: pagos.php?err=Error al subir el archivo");
    exit;
}

if (isset($_POST["monto"])) {
    $monto = floatval($_POST["monto"]);

    if ($monto < 0) {
        // Manejar el error, por ejemplo, establecer el monto a 0 o abortar la operación.
        $monto = 0; 
        // O: die("Error: El monto no puede ser negativo.");
    }
    
    // Continuar con la inserción en la base de datos usando $monto
    // ...
}

// Insertar registro en BD
$stmt = $conexion->prepare("
    INSERT INTO pago (id_miembro, monto, concepto, comprobante, estado_pa, fecha_p)
    VALUES (?, ?, ?, ?, 'pendiente', NOW())
");
$stmt->bind_param("idss", $id_miembro, $monto, $concepto, $nombre_archivo);

if ($stmt->execute()) {
    header("Location: pagos.php?ok=1");
} else {
    header("Location: pagos.php?err=Error al guardar en la base de datos");
}
exit;
