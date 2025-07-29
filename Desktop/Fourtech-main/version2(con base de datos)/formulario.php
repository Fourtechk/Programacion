<?php 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include("conexion.php");

    $nombre = trim($_POST["nombre"]);
    $cedula = trim($_POST["cedula"]);
    $edad = trim($_POST["edad"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);
    $situacion = trim($_POST["situacion"]);
    $cantidad_menores = intval($_POST["cantidad_menores"]);
    $prioridad = trim($_POST["prioridad"]);

    $errores = [];

    if ($nombre === "") $errores[] = "El nombre es obligatorio.";
    if ($cedula === "") $errores[] = "La cédula es obligatoria.";
    if (!is_numeric($edad) || $edad < 18) $errores[] = "Edad inválida. Debe ser mayor de 18.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo inválido.";
    if ($telefono === "") $errores[] = "El teléfono es obligatorio.";
    if ($situacion === "") $errores[] = "Debe seleccionar su situación habitacional.";

    if (!empty($errores)) {
        echo "<h2>Errores encontrados:</h2><ul>";
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul><a href='postularse.html'>Volver al formulario</a>";
        exit;
    }


    $stmt = $conn->prepare("INSERT INTO postulaciones 
        (nombre, cedula, edad, email, telefono, situacion, cantidad_menores, prioridad, fecha)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("ssisssis", $nombre, $cedula, $edad, $email, $telefono, $situacion, $cantidad_menores, $prioridad);

    if ($stmt->execute()) {
      
        $respaldo = "respaldo_postulaciones.txt";
        $contenido = "Nombre: $nombre\nCédula: $cedula\nEdad: $edad\nEmail: $email\nTeléfono: $telefono\nSituación: $situacion\nCantidad de menores: $cantidad_menores\nPrioridad: $prioridad\nFecha: " . date("Y-m-d H:i:s") . "\n--------------------------\n";
        file_put_contents($respaldo, $contenido, FILE_APPEND);

        echo "<h2 style='color:green;'>¡Postulación guardada con éxito!</h2>";
        echo "<a href='postularse.html'>Enviar otra postulación</a>";
    } else {
        echo "Error al guardar en la base de datos: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Acceso inválido.";
}
?>
