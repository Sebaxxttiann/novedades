<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/config.php';
auth(['normal']);

use PhpOffice\PhpWord\TemplateProcessor;

$reporte = obtener_reporte_autorizado(filtro_get_int('id'));

if (!$reporte || $reporte['decision'] !== 'llamado_atencion') {
    http_response_code(404);
    exit('El llamado de atención no existe o no tiene permisos para descargarlo.');
}

$causal = $reporte['causal_llamado'] ?? '';
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha_actual = date('d') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$asuntos = [
    'alcoholemia' => 'LLAMADO DE ATENCIÓN POR PRESENTARSE EN ESTADO DE ALCOHOLEMIA',
    'ausentismo' => 'LLAMADO DE ATENCIÓN POR AUSENCIA INJUSTIFICADA.',
    'llegar_tarde' => 'LLAMADO DE ATENCIÓN POR LLEGADA TARDE A LABORAR.',
    'no_acatar_ordenes' => 'LLAMADO DE ATENCIÓN POR NO ACATAR LAS INSTRUCCIONES IMPARTIDAS',
    'no_marcar' => 'LLAMADO DE ATENCIÓN POR NO MARCACIÓN CONTROL DE INGRESO Y SALIDA (BIOMETRICO)',
    'bajo_rendimiento' => 'LLAMADO DE ATENCIÓN POR DEFICIENTE RENDIMIENTO EN EL TRABAJO',
];

try {
    $plantilla = new TemplateProcessor(__DIR__ . '/plantillas/PLANTILLA_UNIVERSAL.docx');
    $plantilla->setValue('NOMBRE', mb_strtoupper($reporte['colaborador_nombre'], 'UTF-8'));
    $plantilla->setValue('CEDULA', $reporte['colaborador_cedula']);
    $plantilla->setValue('ASUNTO', $asuntos[$causal] ?? 'LLAMADO DE ATENCIÓN DISCIPLINARIO.');
    $plantilla->setValue('NOMBRE_CREADOR', mb_strtoupper($reporte['creador_nombre'] ?? $_SESSION['usuario_nombre'], 'UTF-8'));
    $plantilla->setValue('OPERACION_CREADOR', $reporte['operacion_nombre'] ?? '');
    $plantilla->setValue('FECHA_ACTUAL', $fecha_actual);
    $plantilla->setValue('DESCRIPCION_HECHOS', $reporte['descripcion']);

    foreach (array_keys(CAUSALES_LLAMADO) as $c) {
        $plantilla->cloneBlock('block_' . $c, $c === $causal ? 1 : 0, true, false);
    }

    $ruta = tempnam(sys_get_temp_dir(), 'llamado_');
    $plantilla->saveAs($ruta);
} catch (Throwable $e) {
    error_log('descargar_plantilla: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo generar la plantilla. Intente nuevamente.');
}

$nombreArchivo = 'Llamado_Atencion_' . preg_replace('/\D/', '', $reporte['colaborador_cedula']) . '_' . date('Ymd') . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . filesize($ruta));
readfile($ruta);
unlink($ruta);
exit;
