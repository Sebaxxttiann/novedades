<?php
require_once __DIR__ . '/../../app/config/config.php';
auth();

$reporte = obtener_reporte_autorizado(filtro_get_int('id'));

if (!$reporte) {
    responder_json(['success' => false, 'mensaje' => 'El registro no existe o no tiene permisos para verlo.'], 404);
}

$estado = estado_info($reporte['estado']);

responder_json([
    'success' => true,
    'reporte' => [
        'id' => (int) $reporte['id'],
        'colaborador_nombre' => $reporte['colaborador_nombre'],
        'colaborador_cedula' => $reporte['colaborador_cedula'],
        'colaborador_cargo' => $reporte['colaborador_cargo'] ?: 'No registrado',
        'operacion' => $reporte['operacion_nombre'] ?? 'Sin operación',
        'creador' => $reporte['creador_nombre'] ?? 'Usuario eliminado',
        'tipo_infraccion' => TIPOS_INFRACCION[$reporte['tipo_infraccion']] ?? $reporte['tipo_infraccion'],
        'decision' => $reporte['decision'],
        'decision_label' => DECISIONES[$reporte['decision']] ?? $reporte['decision'],
        'causal' => $reporte['causal_llamado'] ? (CAUSALES_LLAMADO[$reporte['causal_llamado']] ?? $reporte['causal_llamado']) : null,
        'reincidente' => (bool) $reporte['reincidente'],
        'falta_grave' => (bool) $reporte['falta_grave'],
        'descripcion' => $reporte['descripcion'],
        'estado' => $reporte['estado'],
        'estado_label' => $estado['label'],
        'estado_tone' => $estado['tone'],
        'estado_icon' => $estado['icon'],
        'fecha_creacion' => date('d/m/Y h:i a', strtotime($reporte['fecha_creacion'])),
        'fecha_citacion' => $reporte['fecha_citacion'] ? date('d/m/Y', strtotime($reporte['fecha_citacion'])) : null,
        'tiene_evidencia' => !empty($reporte['evidencia']),
        'evidencia_url' => !empty($reporte['evidencia']) ? 'descargas/evidencia.php?id=' . (int) $reporte['id'] : null,
        'plantilla_url' => $reporte['decision'] === 'llamado_atencion' && $_SESSION['usuario_rol'] === 'normal'
            ? 'descargas/llamado_atencion.php?id=' . (int) $reporte['id'] : null,
    ],
]);
