<?php
session_start();
require_once "conexion.php";
$conexion = new mysqli($host, $user, $pass, $db, $port);

// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php?error=sin_sesion");
    exit;
}

// Verificar si el usuario es administrador
$id = intval($_SESSION['id']);
$resultado = $conexion->query("SELECT admin FROM miembro WHERE id_miembro = $id");

if (!$resultado || $resultado->num_rows === 0) {
    header("Location: index.html?error=usuario_no_encontrado");
    exit;
}

$datos = $resultado->fetch_assoc();
if (intval($datos['admin']) !== 1) {
    header("Location: index.html?error=no_admin");
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
  background: linear-gradient(135deg, #0a2239, #1e3d58);
  background-attachment: fixed;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  color: #eaf6ff;
}

/* HEADER */
header {
  background-color: rgba(10, 34, 57, 0.9);
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  color: #eaf6ff;
  font-size: 20px;
  font-weight: 600;
  box-shadow: 0 3px 10px rgba(0,0,0,0.35);
  letter-spacing: 0.5px;
}

header .logo {
  position: absolute;
  left: 20px;
  display: flex;
  align-items: center;
  height: 100%;
  background: none !important;
  border: none !important;
  outline: none !important;
}

header .logo img {
  height: 50px;
  border-radius: 50%;
  object-fit: contain;
  filter: drop-shadow(1px 1px 3px rgba(0,0,0,0.5));
  pointer-events: none;
  user-select: none;
}

/* MAIN */
main {
  flex: 1;
  padding: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* CONTENEDOR */
.contenedor {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
}

h2 {
  text-align: center;
  font-size: 32px;
  color: #eaf6ff;
  text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
  margin-bottom: 30px;
  font-weight: 700;
}

/* FILTRO */
form.filter {
  margin-bottom: 25px;
  color: #eaf6ff;
  font-weight: 600;
  text-align: center;
  background: rgba(255,255,255,0.08);
  padding: 10px 20px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  backdrop-filter: blur(5px);
}

form.filter select {
  margin-left: 10px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.3);
  background: rgba(255,255,255,0.1);
  color: #eaf6ff;
}

/* TABLA */
table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(255,255,255,0.05);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 6px 25px rgba(0,0,0,0.3);
  backdrop-filter: blur(10px);
}

th, td {
  padding: 14px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  text-align: center;
  font-size: 14px;
  color: #f1f7fc;
}

th {
  background: linear-gradient(135deg, #1e5f9e, #144e78);
  color: #fff;
  font-weight: 600;
  text-transform: uppercase;
}

tr:nth-child(even) {
  background-color: rgba(255,255,255,0.05);
}

tr:hover {
  background-color: rgba(110,187,233,0.15);
  transition: background 0.25s ease;
}

/* LINKS */
a {
  background: #1e6fa8;
  color: #fff;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  transition: all 0.25s ease;
}

a:hover {
  background: #3498db;
  transform: scale(1.05);
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
  border-radius: 10px;
  width: 200px;
  height: 50px;
  font-size: 15px;
  background: linear-gradient(135deg, #1e5f9e, #144e78);
  color: #fff;
  box-shadow: 0 3px 10px rgba(0,0,0,0.35);
  transition: background 0.25s ease, transform 0.25s ease;
}

.nav-buttons button:hover {
  background: linear-gradient(135deg, #2980b9, #1c5f8c);
  transform: translateY(-2px);
}

/* RESPONSIVE */
@media(max-width:700px) {
  th, td { font-size: 12px; padding: 10px; }
  button { width: 75px; font-size: 12px; }
  .nav-buttons { flex-direction: column; align-items: center; }
  form.filter { font-size: 14px; }
}
</style>

</head>
<body>
<header>
  <a href="#" class="logo">
    <img src="logo.jpeg" alt="logo-cooperativa">
  </a>
</header>


  <h2>Gestión de Comprobantes</h2>

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
