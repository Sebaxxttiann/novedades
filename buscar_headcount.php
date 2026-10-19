<?php
require_once __DIR__ . '/app/config/config.php';
auth(['normal']);

$operacion_id = (int) ($_SESSION['operacion_id'] ?? 0);
if ($operacion_id <= 0) {
    responder_json(['found' => false, 'success' => false, 'mensaje' => 'Su usuario no tiene una operación asignada.'], 403);
}

limitar('buscar_hc:' . $_SESSION['usuario_id'], 60, 60);

$cedula = trim((string) ($_GET['cedula'] ?? ''));

if (!preg_match('/^\d{5,15}$/', $cedula)) {
    responder_json(['found' => false]);
}

try {
    $stmt = $conn->prepare("SELECT nombre, cargo FROM headcount WHERE cedula = ? AND operacion_id = ? LIMIT 1");
    $stmt->execute([$cedula, $operacion_id]);
    $colaborador = $stmt->fetch();
} catch (PDOException $e) {
    error_log('buscar_headcount: ' . $e->getMessage());
    responder_json(['found' => false, 'success' => false, 'mensaje' => 'No se pudo realizar la búsqueda.'], 500);
}

if ($colaborador) {
    responder_json(['found' => true, 'nombre' => $colaborador['nombre'], 'cargo' => $colaborador['cargo']]);
}

responder_json(['found' => false]);
