<?php
require_once __DIR__ . '/app/config/config.php';
auth();

$nombre = htmlspecialchars($_GET['nombre'] ?? 'Colaborador');
$cedula = htmlspecialchars($_GET['cedula'] ?? '000000');
$fecha_ejecucion = htmlspecialchars($_GET['fecha'] ?? date('Y-m-d'));
$fecha_actual = date('d de M de Y');

// Forzar la descarga en formato Word (.doc)
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename=Citacion_Descargos_{$cedula}.doc");

require __DIR__ . '/templates/documentos/citacion_descargos.php';
