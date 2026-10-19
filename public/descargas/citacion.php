<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/config/config.php';
auth(['juridico']);

use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$reporte = false;

if ($id && $id > 0) {
    $stmt = $conn->prepare("SELECT r.colaborador_nombre, r.colaborador_cedula, r.colaborador_cargo, r.descripcion,
            r.fecha_citacion, o.nombre AS operacion_nombre
        FROM reportes_novedades r
        LEFT JOIN operaciones o ON o.id = r.operacion_id
        WHERE r.id = ? AND r.decision = 'proceso_disciplinario' AND r.fecha_citacion IS NOT NULL");
    $stmt->execute([$id]);
    $reporte = $stmt->fetch();
}

if (!$reporte) {
    http_response_code(404);
    $tituloPagina = 'Documento no disponible';
    include __DIR__ . '/../../app/includes/header.php';
    ?>
    <main class="container">
        <div class="table-card">
            <div class="empty-state">
                <i data-lucide="file-x"></i>
                <h3>Documento no disponible</h3>
                <p>El proceso no existe o aún no tiene una fecha de citación asignada.</p>
                <a href="juridico.php" class="btn btn-primary btn-sm">Volver al panel jurídico</a>
            </div>
        </div>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

$fechaCitacion = strtotime($reporte['fecha_citacion']);
$fechaDescargos = $dias[(int) date('w', $fechaCitacion)] . ' ' . date('j', $fechaCitacion) . ' de ' . $meses[(int) date('n', $fechaCitacion)] . ' de ' . date('Y', $fechaCitacion);
$fechaActual = date('j') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');

$hechos = array_values(array_filter(array_map('trim', preg_split('/\R/', $reporte['descripcion'])), 'strlen'));
if (!$hechos) {
    $hechos = ['Sin descripción registrada.'];
}

try {
    Settings::setOutputEscapingEnabled(true);
    $plantilla = new TemplateProcessor(STORAGE_PATH . '/plantillas/CITACION_DESCARGOS.docx');
    $plantilla->setValue('NOMBRE', mb_strtoupper($reporte['colaborador_nombre'], 'UTF-8'));
    $plantilla->setValue('CEDULA', $reporte['colaborador_cedula']);
    $plantilla->setValue('CARGO', mb_strtoupper($reporte['colaborador_cargo'] ?? '', 'UTF-8'));
    $plantilla->setValue('FECHA_ACTUAL', $fechaActual);
    $plantilla->setValue('FECHA_DESCARGOS', $fechaDescargos);
    $plantilla->setValue('CIUDAD', $reporte['operacion_nombre'] ?? 'Medellín');
    $plantilla->setValue('FIRMANTE', mb_strtoupper($_SESSION['usuario_nombre'], 'UTF-8'));
    $plantilla->cloneBlock('hechos', 0, true, false, array_map(fn($hecho) => ['HECHO' => htmlspecialchars($hecho, ENT_XML1 | ENT_QUOTES, 'UTF-8')], $hechos));

    $ruta = tempnam(sys_get_temp_dir(), 'citacion_');
    $plantilla->saveAs($ruta);
} catch (Throwable $e) {
    error_log('citacion: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo generar la citación. Intente nuevamente.');
}

$cedulaArchivo = preg_replace('/\D+/', '', $reporte['colaborador_cedula']) ?: 'documento';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="Citacion_Descargos_' . $cedulaArchivo . '.docx"');
header('Content-Length: ' . filesize($ruta));
readfile($ruta);
unlink($ruta);
exit;
