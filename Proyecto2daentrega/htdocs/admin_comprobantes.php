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
/* ======== ESTILO GENERAL ======== */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: "Poppins", sans-serif;
}

body {
  background: linear-gradient(135deg, #1a2433, #2b5f87);
  background-attachment: fixed;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100px 20px 40px;
  color: #edf1f6;
  margin-right: 260px;
}

/* ======== HEADER ======== */
header {
  background: rgba(26, 36, 51, 0.9);
  backdrop-filter: blur(10px);
  height: 70px;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 25px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

.logo {
  display: flex;
  align-items: center;
  height: 100%;
}
.logo img {
  height: 54px;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
}

/* ======== TÍTULO ======== */
h2 {
  text-align: center;
  font-size: 32px;
  color: #ffffffff;
  text-shadow: 0 0 8px rgba(110, 187, 233, 0.3);
  margin-bottom: 30px;
  font-weight: 600;
}

/* ======== TABLA ======== */
table {
  width: 100%;
  max-width: 1000px;
  border-collapse: collapse;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
  margin-bottom: 25px;
  animation: fadeIn 0.8s ease-in-out;
}

th, td {
  padding: 12px;
  font-size: 15px;
  text-align: center;
  color: #edf1f6;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

th {
  background: rgba(110, 187, 233, 0.15);
  color: #6ebbe9;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

tr:nth-child(even) {
  background: rgba(255, 255, 255, 0.04);
}
tr:hover {
  background: rgba(255, 255, 255, 0.1);
  transition: background 0.3s ease;
}

/* ======== BOTONES DE TABLA ======== */
button {
  background: #6ebbe9;
  color: #1a2433;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  margin: 4px 2px;
  box-shadow: 0 0 10px rgba(110, 187, 233, 0.3);
}
button:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 12px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

/* ======== LINKS ======== */
a {
  background: #6ebbe9;
  color: #1a2433;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  box-shadow: 0 2px 6px rgba(110, 187, 233, 0.3);
  transition: all 0.25s ease;
}
a:hover {
  background: #2b5f87;
  color: #fff;
  transform: scale(1.05);
}

/* ======== MENÚ LATERAL ======== */
.btn-container {
  position: fixed;
  top: 70px;
  right: 0;
  width: 260px;
  height: calc(102% - 90px);
  background: rgba(26, 36, 51, 0.9);
  backdrop-filter: blur(10px);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.3);
  z-index: 9;
}

.btn-container form {
  width: 100%;
}

.btn-container .btn {
  width: 100%;
  padding: 12px;
  font-size: 15px;
  border-radius: 8px;
}

.btn:hover {
  background: #2b5f87;
  color: #fff;
  box-shadow: 0 0 14px rgba(110, 187, 233, 0.5);
  transform: translateY(-2px);
}

/* ======== ANIMACIÓN ======== */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ======== RESPONSIVE ======== */
@media (max-width: 768px) {
  h2 { font-size: 26px; }
  table { font-size: 14px; }
  th, td { padding: 8px; }
  .btn { font-size: 14px; padding: 10px 18px; }
}

@media (max-width: 1024px) {
  .btn-container {
    width: 220px;
  }

  body {
    margin-right: 220px;
  }
}

@media (max-width: 768px) {
  .btn-container {
    position: static;
    width: 100%;
    height: auto;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    padding: 15px;
    box-shadow: none;
    background: rgba(26, 36, 51, 0.85);
  }

  .btn-container .btn {
    width: auto;
    min-width: 140px;
    margin: 10px;
    font-size: 14px;
    padding: 10px 18px;
  }

  body {
    margin-right: 0;
    padding-top: 120px;
  }
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

  <div class="btn-container">
  <form action="admin.php" method="post"><button type="submit" class="btn">Aprobar Usuarios</button></form>
  <form action="admin_horas.php" method="post"><button type="submit" class="btn">Administrar Horas</button></form>
  <form action="pagos.php" method="get"><button type="submit" class="btn">Ver Pagos</button></form>
</div>


</main>
</body>
</html>
