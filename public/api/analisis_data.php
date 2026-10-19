<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/includes/analitica.php';
auth(['admin']);

$operacionIds = [];
if (!empty($_GET['operaciones']) && is_string($_GET['operaciones'])) {
    $operacionIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $_GET['operaciones'])))));
}

responder_json(['success' => true, 'data' => obtenerAnalitica($conn, $operacionIds)]);
