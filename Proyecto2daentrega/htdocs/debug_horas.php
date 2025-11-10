<?php
session_start();
require "conexion.php";

if (!isset($_SESSION["id"])) {
    echo "No hay sesión activa.";
    exit();
}

$id = $_SESSION["id"];
echo "<h2>Depuración de horas para miembro ID: $id</h2>";

$query = $conexion->prepare("SELECT * FROM horas WHERE id_miembro = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>❌ No existe fila en la tabla <strong>horas</strong> para este miembro.</p>";
} else {
    $horas = $result->fetch_assoc();
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    foreach ($horas as $campo => $valor) {
        echo "<tr><td>$campo</td><td>$valor</td></tr>";
    }
    echo "</table>";

    echo "<br><strong>Resumen:</strong><br>";
    echo "✅ Horas cumplidas: " . ($horas["cumplidas"] ?? 0) . "<br>";
    echo "⏳ Horas pendientes: " . ($horas["horas_pendientes"] ?? 0) . "<br>";
    echo "📌 Horas requeridas: " . ($horas["semanales_req"] ?? 0) . "<br>";
    echo "🧮 Horas restantes: " . max(0, ($horas["semanales_req"] ?? 0) - ($horas["cumplidas"] ?? 0)) . "<br>";
}
?>