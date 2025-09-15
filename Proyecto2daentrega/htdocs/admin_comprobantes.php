<?php
session_start();

$conexion = new mysqli("localhost", "root", "", "sistema", $port);

// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=sin_sesion");
    exit;
}

// Verificar si el usuario es administrador
$id = intval($_SESSION['id']);
$resultado = $conexion->query("SELECT admin FROM miembro WHERE id_miembro = $id");

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: landingpage.html?error=usuario_no_encontrado");
    exit;
}

$datos = $resultado->fetch_assoc();
if (intval($datos['admin']) !== 1) {
    header("Location: landingpage.html?error=no_admin");
    exit;
}


require_once "conexion.php";

// Filtrar por estado
$estado = $_GET['estado'] ?? '';
$valid = ['','pendiente','aprobado','rechazado'];
if (!in_array($estado, $valid, true)) $estado = '';

$sql = "SELECT p.id_pago, m.nombre, m.email, p.monto, p.concepto, p.comprobante, p.estado_pa, p.fecha_p
        FROM pago p
        INNER JOIN miembro m ON m.id_miembro = p.id_miembro";
$params = [];
$types = '';
if ($estado !== '') {
    $sql .= " WHERE p.estado_pa = ?";
    $params[] = $estado;
    $types = 's';
}
$sql .= " ORDER BY p.fecha_p DESC";

$stmt = $conexion->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();

// CSRF token
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Admin - Comprobantes</title>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}

body {
  background: url('landingpage.jpg') center/cover fixed;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  color: #2c3e50;
}

/* HEADER */
header {
  background: linear-gradient(135deg, #2c3e50, #34495e);
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  color: white;
  font-size: 20px;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0,0,0,0.25);
}

header .logo {
  position: absolute;
  left: 20px;
  display: flex;
  align-items: center;
  height: 100%;
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
}

header .logo:focus,
header .logo:active,
header .logo img,
header .logo img:focus,
header .logo img:active {
  outline: none !important;
  border: none !important;
  box-shadow: none !important;
}

header .logo img {
  height: 50px;
  border-radius: 50%;
  object-fit: contain;
  filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.4));
}

/* MAIN */
main {
  flex: 1;
  padding: 25px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Contenedor centrado */
.contenedor {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
}

h2 {
  margin: 25px 0;
  color: white;
  text-align: center;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
}

/* FILTRO */
form.filter {
  margin-bottom: 20px;
  color: white;
  font-weight: 600;
  text-align: center;
}

form.filter select {
  margin-left: 10px;
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

/* TABLA */
table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

th, td {
  padding: 12px;
  border-bottom: 1px solid #eee;
  text-align: center;
  font-size: 14px;
}

th {
  background: linear-gradient(135deg, #34495e, #2c3e50);
  color: white;
  font-weight: 600;
}

tr:nth-child(even) {
  background-color: #f9f9f9;
}

tr:hover {
  background-color: #eef6ff;
  transition: background 0.2s ease;
}

/* LINKS */
a {
  background: #2980b9;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
}

a:hover {
  background: #3498db;
}

/* BOTONES */
button {
  border: none;
  border-radius: 20px;
  height: 35px;
  width: 90px;
  font-weight: 700;
  color: white;
  cursor: pointer;
  transition: transform 0.2s, background 0.2s;
}

button.aprobar {
  background: #27ae60;
}

button.rechazado {
  background: #c0392b;
  margin-top: 4px;
}

button:hover {
  transform: scale(1.05);
}

/* NAV BUTTONS */
.nav-buttons {
  display: flex;
  gap: 15px;
  margin: 20px 0;
  flex-wrap: wrap;
  justify-content: center;
}

.nav-buttons button {
  border-radius: 6px;
  width: 200px;
  height: 50px;
  font-size: 15px;
  background: #34495e;
}

.nav-buttons button:hover {
  background: #2c3e50;
}

/* RESPONSIVE */
@media(max-width:700px) {
  th, td { font-size: 12px; }
  button { width: 75px; font-size: 12px; }
  .nav-buttons { flex-direction: column; }
}
</style>
</head>
<body>
<header>
  <a href="landingpage.html" class="logo">
    <img src="logo.jpeg" alt="Logo">
  </a>
  <span class="titulo-header">Panel Root - Gestión de Comprobantes</span>
</header>


  <h2>Gestión de Comprobantes</h2>

  <form method="get" class="filter">
    <label>Filtrar por estado:</label>
    <select name="estado" onchange="this.form.submit()">
      <option value="" <?= $estado===''?'selected':'' ?>>Todos</option>
      <option <?= $estado==='Pendiente'?'selected':'' ?>>Pendiente</option>
      <option <?= $estado==='Aprobado'?'selected':'' ?>>Aprobado</option>
      <option <?= $estado==='Rechazado'?'selected':'' ?>>Rechazado</option>
    </select>
  </form>

  <table>
    <tr>
      <th>ID</th>
      <th>Socio</th>
      <th>Email</th>
      <th>Monto</th>
      <th>Concepto</th>
      <th>Archivo</th>
      <th>Estado</th>
      <th>Fecha</th>
      <th>Acciones</th>
    </tr>
    <?php while($c = $res->fetch_assoc()): ?>
<tr>
  <td><?= (int)$c['id_pago'] ?></td>
  <td><?= htmlspecialchars($c['nombre']) ?></td>
  <td><?= htmlspecialchars($c['email']) ?></td>
  <td><?= number_format((float)$c['monto'],2,',','.') ?></td>
  <td><?= htmlspecialchars($c['concepto']) ?></td>
  <td><a href="<?= htmlspecialchars($c['comprobante']) ?>" target="_blank">Ver</a></td>
  <td>
    <?php
      if($c['estado_pa'] === 'aprobado') echo "✅ <span style='color:green;'>Aprobado</span>";
      elseif($c['estado_pa'] === 'rechazado') echo "❌ <span style='color:red;'>Rechazado</span>";
      else echo "⏳ Pendiente";
    ?>
  </td>
  <td><?= htmlspecialchars($c['fecha_p']) ?></td>
  <td>
    <form method="post" action="cambiar_estado.php">
      <input type="hidden" name="id" value="<?= (int)$c['id_pago'] ?>">
      <input type="hidden" name="estado" value="aprobado">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <button class="aprobar" type="submit">Aprobar</button>
    </form>
    <form method="post" action="cambiar_estado.php">
      <input type="hidden" name="id" value="<?= (int)$c['id_pago'] ?>">
      <input type="hidden" name="estado" value="rechazado">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <button class="rechazado" type="submit">Rechazar</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>

  </table>

  <div class="nav-buttons">
    <form action="admin.php" method="post"><button type="submit">Aprobar Usuarios</button></form>
    <form action="admin_horas.php" method="post"><button type="submit">Administrar Horas</button></form>
    
  <form action="pagos.php" method="get">
    <button type="submit" class="btn">Ver Pagos</button>
  </form>
  </div>

</main>
</body>
</html>
