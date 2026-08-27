<?php
require_once __DIR__ . '/app/config/config.php';
auth();

$causal = $_GET['causal'] ?? 'general';
$nombre = mb_strtoupper(htmlspecialchars($_GET['nombre'] ?? 'COLABORADOR'), 'UTF-8');
$cedula = htmlspecialchars($_GET['cedula'] ?? '000000');

$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fecha_actual = date('d') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$nombre_creador = mb_strtoupper($_SESSION['usuario_nombre'], 'UTF-8');
$operacion_creador = $_SESSION['operacion_nombre'] ?? 'Central';

$stmt = $conn->prepare("SELECT descripcion FROM reportes_novedades WHERE colaborador_cedula = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$cedula]);
$reporte = $stmt->fetch();
$descripcion_hechos = $reporte ? nl2br(htmlspecialchars($reporte['descripcion'])) : '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . "://" . $host . $uri;

$logo_url = $base_url . '/uploads/actas/logo.png';
$sidebar_url = $base_url . '/uploads/actas/sidebar.png';
$footer_url = $base_url . '/uploads/actas/footer.png';

// Define $asunto y $contenido según la causal
require __DIR__ . '/templates/documentos/llamado_atencion_causales.php';

// Compone el $html final con el layout de la carta
require __DIR__ . '/templates/documentos/layout_carta_word.php';

$directorio_actas = __DIR__ . '/uploads/actas/';
if (!is_dir($directorio_actas)) {
    mkdir($directorio_actas, 0777, true);
}

$nombre_archivo = "Llamado_Atencion_{$cedula}_" . time() . ".doc";
$ruta_completa = $directorio_actas . $nombre_archivo;

file_put_contents($ruta_completa, $html);

header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename={$nombre_archivo}");
echo $html;
exit;
