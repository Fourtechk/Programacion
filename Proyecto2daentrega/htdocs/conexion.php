<?php
$host = "localhost";
$user = "root";   // en XAMPP normalmente es root
$pass = "";       // en XAMPP suele estar vacío
$db   = "sistema"; // ⚠️ poné acá el nombre real de tu base de datos
$port = 3306;

$conexion = new mysqli($host, $user, $pass, $db, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
