<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
   
    $nombre = trim($_POST["nombre"]);
    $cedula = trim($_POST["cedula"]);
    $edad = trim($_POST["edad"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);
    $situacion = trim($_POST["situacion"]);
    $cantidad_menores = $_POST["cantidad_menores"]; 
    $prioridad = trim($_POST["prioridad"]);


    $errores = [];

    if ($nombre === "") $errores[] = "El nombre es obligatorio.";
    if ($cedula === "") $errores[] = "La cédula es obligatoria.";
    if (!is_numeric($edad) || $edad < 18) $errores[] = "Edad inválida. Debe ser mayor de 18.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo inválido. Debe contener '@'.";
    if ($telefono === "") $errores[] = "El teléfono es obligatorio.";
    if ($situacion === "") $errores[] = "Debe seleccionar su situación habitacional.";


    if (!empty($errores)) {
        echo "<h2>Errores encontrados:</h2><ul>";
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul><a href='formulario.html'>Volver al formulario</a>";
        exit;
    }

   
    $contenido = "===== POSTULACIÓN =====\n";
    $contenido .= "Nombre completo: $nombre\n";
    $contenido .= "Cédula: $cedula\n";
    $contenido .= "Edad: $edad\n";
    $contenido .= "Correo: $email\n";
    $contenido .= "Teléfono: $telefono\n";
    $contenido .= "Situación habitacional: $situacion\n";
    $contenido .= "Cantidad de menores: $cantidad_menores\n";
    $contenido .= "Prioridad declarada: $prioridad\n";
    $contenido .= "Fecha y hora: " . date("Y-m-d H:i:s") . "\n";
    $contenido .= "=========================\n\n";

  
    $carpeta = __DIR__ . "/DatosFormulario";
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

  
    $archivo = $carpeta . "/postulacion_" . date("Ymd_His") . ".txt";

  
    file_put_contents($archivo, $contenido);

    echo "<h2 style='color:green;'>¡Postulación enviada con éxito!</h2>";
    echo "<p>Datos guardados en: <strong>fourtech/" . basename($archivo) . "</strong></p>";
    echo "<a href='formulariopostulacion.html'>Enviar otra postulación</a>";
} else {
    echo "Acceso inválido.";
}
?>