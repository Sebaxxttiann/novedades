<?php
require_once __DIR__ . '/../../app/config/config.php';
auth();

$reporte = obtener_reporte_autorizado(filtro_get_int('id'));
$archivo = $reporte && !empty($reporte['evidencia']) ? basename($reporte['evidencia']) : '';
$ruta = $archivo !== '' ? realpath(STORAGE_PATH . '/evidencias/' . $archivo) : false;
$directorio = realpath(STORAGE_PATH . '/evidencias');

if (!$ruta || !is_file($ruta) || !str_starts_with($ruta, $directorio . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit('La evidencia no está disponible.');
}

$extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
$tipos = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'heic' => 'image/heic',
    'pdf' => 'application/pdf',
];
$enLinea = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

header('Content-Type: ' . ($tipos[$extension] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: ' . ($enLinea ? 'inline' : 'attachment') . '; filename="evidencia_' . (int) $reporte['id'] . '.' . ($tipos[$extension] ?? false ? $extension : 'bin') . '"');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
readfile($ruta);
