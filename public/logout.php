<?php
require_once __DIR__ . '/../app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        cerrar_sesion();
        redirigir('index.php?salida=1');
    }
    redirigir(isset($_SESSION['usuario_rol']) ? inicio_por_rol($_SESSION['usuario_rol']) : 'index.php');
}

if (!sesion_activa()) {
    redirigir('index.php?expirada=1');
}

if (time() - ($_SESSION['ultimo_acceso'] ?? 0) >= SESSION_TIMEOUT - 120) {
    cerrar_sesion();
    redirigir('index.php?expirada=1');
}

redirigir(inicio_por_rol($_SESSION['usuario_rol']));
