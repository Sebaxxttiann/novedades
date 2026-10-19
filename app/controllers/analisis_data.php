<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/analitica.php';
auth();

header('Content-Type: application/json');

if ($_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado.']);
    exit;
}

$operacionIds = [];
if (!empty($_GET['operaciones'])) {
    $operacionIds = array_values(array_filter(array_map('intval', explode(',', $_GET['operaciones']))));
}

echo json_encode(['success' => true, 'data' => obtenerAnalitica($conn, $operacionIds)]);
