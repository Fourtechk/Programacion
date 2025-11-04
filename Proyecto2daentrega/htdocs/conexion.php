<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistema";
$port = 3306;

$conexion = new mysqli($host, $user, $pass, $db, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>