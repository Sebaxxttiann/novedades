<?php
require_once __DIR__ . '/app/config/config.php';
cerrar_sesion();
redirigir(isset($_GET['expirada']) ? 'index.php?expirada=1' : 'index.php?salida=1');
