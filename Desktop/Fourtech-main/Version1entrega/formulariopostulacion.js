/*

En la segunda entrega incorporaremos que la validacion sea realizada en javascript en el frontend, 
para mantener una buena practica y que funcione mas rapido la web.

document.getElementById('formulario').addEventListener('submit', function (e) {
    const nombre = document.getElementById('nombre').value.trim();
    const cedula = document.getElementById('cedula').value.trim();
    const edad = parseInt(document.getElementById('edad').value);
    const email = document.getElementById('email').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const situacion = document.getElementById('situacion').value;
    const errores = [];

    if (nombre === "") errores.push("El nombre es obligatorio.");
    if (cedula === "") errores.push("La cédula es obligatoria.");
    if (isNaN(edad) || edad < 18) errores.push("Debes tener al menos 18 años.");
    if (!email.includes('@')) errores.push("El correo debe contener '@'.");
    if (telefono === "") errores.push("El teléfono es obligatorio.");
    if (situacion === "") errores.push("Debes seleccionar tu situación habitacional.");

    const mensajeDiv = document.getElementById("errorMensaje");

    if (errores.length > 0) {
        e.preventDefault();
        mensajeDiv.innerHTML = "<strong>Por favor corrige los siguientes errores:</strong><ul>" +
            errores.map(error => `<li>${error}</li>`).join('') + "</ul>";
        mensajeDiv.style.display = "block";
    } else {
        mensajeDiv.style.display = "none";
    }
});
