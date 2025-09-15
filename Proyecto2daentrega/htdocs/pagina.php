<?php

session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pagina</title>
  <style>
    body {
      background-image: url('landingpage.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      font-family: "Poppins", sans-serif;
      display: flex; justify-content: center; align-items: center;
      min-height: 100vh; margin: 0;
      font-family: "Poppins", sans-serif;
      display: flex; justify-content: center; align-items: center;
      height: 100vh; margin: 0; color: white;
      flex-direction: column; text-align: center;
    }
    h1 { font-size: 32px; margin-bottom: 15px; position: absolute; height: 79%;}
    a {
      position: absolute;
      top: 20%;
      background: #fff; color: #2C3E50;
      padding: 10px 20px; border-radius: 8px;
      text-decoration: none; font-weight: bold;
    }
    .textoinicio{
      position: absolute;
      top: 15%;
    }
    a:hover { background: #d5dbdb; }

    .contenedor1{
      position: absolute;
      top: 40%;
      left: 3%;
      height: 558px;
      width: 905px;
      background: rgba(30, 41, 59, 0.7);
      border-radius: 24px;
      
    }

    .contenedor-texto{
      position: absolute;
      top: 40%;
      left: 4%;
       z-index: 9999;
    }

    .texto-div{
      text-align: left;
    }

    .h2-div{
      font-size: 44px;
    }

    .texto-div{
      font-size: 25px;
    }

    .boton-div{
      height: 100px;
      width: 400px;
      position: absolute;
      left: 2%;
      border-radius:24px;
      top: 115%;  
      border: none;
      background-color:#4FAFEA;
      font-size: 29px;
      font-weight: 800;
      color: #FFFF;
    }

    .boton-div:hover{
      cursor: pointer;
      transition: 1s;
      background-color:#0E1630;
    }

    .contenedor2{
      position: absolute;
      top: 40%;
      left: 54%;
      height: 930px;
      width: 803px;
      background: #2C3E50;
      border-radius: 24px;
      opacity: 0.6;
      
    }

    .h22-div{
      margin-top:100px;
      margin-left: 15px;
      text-align: justify;
      font-size: 44px;
      
    }

    .contenedor2-texto{
      z-index: 9999;
      margin-left: 277px;
      left: 55%;
      top: 40%;
    }

    .texto2-div{
      text-align: justify;
      font-size: 24px;
    }

  .boton-nuevo {
      
  height: 80px;
  width: 340px;
  border-radius: 12px;
  border: none;
  background-color: #4FAFEA; 
  color: white;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.3s;
  margin-top: 30px; 
  margin-left: -10px
}

.boton-nuevo:hover {
  transition: 1s ;
  background-color: #0E1630;
  cursor: pointer;
}

  </style>
</head>
<body>
  <h1>Bienvenido, <?= $_SESSION["usuario"] ?> 🎉</h1>
  <p class="textoinicio">Has ingresado correctamente a la página.</p>
  <a href="logout.php">Cerrar sesión</a>

  
  <div class="contenedor-texto">
    <h2 class="h2-div">¿Querés unirte a nuestras cooperativas?</h2>
    <p class="texto-div">
      Se te hará un formulario de ingreso en el cual<br>
      ingresarás tus datos personales que nos ayudarán<br>
      a elegirte para ser parte de nuestra cooperativa
    </p>
    <form action="postulacion.php" method="post">
    <button class="boton-div">POSTULARME</button>
    </form>
  </div>

 
  <div class="contenedor1"></div>

 
 
  

  
  <style>
    .coop-section {
      background: rgba(30, 41, 59, 0.7);
      margin-left: 50%;
      margin-top: 920px; /* lo empuja bien abajo de tu contenido */
      padding: 40px 20px;
      color: #f1f5f9;
      font-family: "Poppins", sans-serif;
      width: 35%; 
      border-radius: 24px;
    }
    .coop-container {
      max-width: 1400px;
      margin: auto;
    }
    .coop-header {
      font-size: 39px;
      font-weight: bold;
      margin-bottom: 20px;
      border-bottom: 3px solid #3b82f6;
      display: inline-block;
    }
    .coop-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .coop-card {
      background: #334155;
      padding: 20px;
      border-radius: 12px;
    }
    .coop-card h3 {
      margin-top: 0;
      font-size: 20px;
      color: #93c5fd;
    }
    .coop-card ul {
      margin: 10px 0 0;
      padding-left: 20px;
    }
    .coop-card li {
      margin-bottom: 6px;
    }
    .coop-footer {
      margin-top: 40px;
      text-align: center;
      font-size: 14px;
      color: #94a3b8;
    }
  </style>

  <section class="coop-section">
    <div class="coop-container">
      <h2 class="coop-header">Información sobre las cooperativas</h2>
      
      <div class="coop-grid">
        <div class="coop-card">
          <h3>Administración</h3>
          <ul>
            <li>Presidente</li>
            <li>Secretario</li>
            <li>Tesorero</li>
            <li>Asesoría de contador del IAT</li>
          </ul>
        </div>
        <div class="coop-card">
          <h3>Actividades para recaudar fondos</h3>
          <ul>
            <li>Venta de tortas fritas</li>
            <li>Rifas</li>
            <li>Ventas en ferias</li>
            <li>Cuota mensual de cooperativistas</li>
          </ul>
        </div>
        <div class="coop-card">
          <h3>Fundación de la Cooperativa</h3>
          <p>Requiere al menos 10 personas.<br>El IAT asigna arquitectos, asistentes sociales, contadores y escribano.</p>
        </div>
        <div class="coop-card">
          <h3>Reglamentos</h3>
          <p>- General: de la ANV<br>- Interno: votado por la cooperativa</p>
          <h4>Puntos Clave</h4>
          <ul>
            <li>Cuotas y sanciones</li>
            <li>Multas progresivas</li>
            <li>Expulsión por deuda</li>
            <li>Asamblea y orden del día</li>
          </ul>
        </div>
        <div class="coop-card">
          <h3>Requisitos para ser Socio</h3>
          <ul>
            <li>Mayor de 18 años</li>
            <li>No tener vivienda propia</li>
            <li>No vivir a menos de 100 km</li>
            <li>Un socio por núcleo habitacional</li>
          </ul>
        </div>
        <div class="coop-card">
          <h3>Valores y Principios</h3>
          <ul>
            <li>Ayuda mutua</li>
            <li>Democracia</li>
            <li>Solidaridad</li>
            <li>Transparencia</li>
          </ul>
        </div>
        <div class="coop-card">
          <h3>Préstamos a Cooperativas</h3>
          <p>Hasta 75% de la inversión, con un máximo de 7.500 UI por socio. <br>Destinado a obras comunes o mejoras habitacionales.</p>
        </div>
      </div>

      <div class="coop-footer">
        Contacto: info, tel, mail, etc. — Derechos Reservados
      </div>
    </div>
  </section>
</body>
</html>
