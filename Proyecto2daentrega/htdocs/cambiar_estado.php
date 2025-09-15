<?php
session_start();
require_once "conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit('Método no permitido');
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) exit('CSRF inválido');

$id = (int)($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';

if ($id <= 0 || !in_array($estado, ['aprobado','rechazado'], true)) exit('Datos inválidos');

$stmt = $conexion->prepare("UPDATE pago SET estado_pa = ? WHERE id_pago = ?");
$stmt->bind_param("si", $estado, $id);
$stmt->execute();

header('Location: admin_comprobantes.php');
exit;
