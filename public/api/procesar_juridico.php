<?php
require_once __DIR__ . '/../../app/config/config.php';
auth(['juridico']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(['success' => false, 'mensaje' => 'Método no permitido.'], 405);
}

limitar('juridico:' . $_SESSION['usuario_id'], 60, 60);

$accion = $_POST['accion'] ?? '';
$reporteId = filter_var($_POST['reporte_id'] ?? 0, FILTER_VALIDATE_INT);

if (!in_array($accion, ['ejecutar_fecha', 'actualizar_estatus'], true) || !$reporteId || $reporteId < 1) {
    responder_json(['success' => false, 'mensaje' => 'Solicitud inválida.'], 422);
}

try {
    $stmt = $conn->prepare("SELECT id, estado FROM reportes_novedades WHERE id = ? AND decision = 'proceso_disciplinario'");
    $stmt->execute([$reporteId]);
    $reporte = $stmt->fetch();

    if (!$reporte) {
        responder_json(['success' => false, 'mensaje' => 'El proceso disciplinario no existe.'], 404);
    }

    if ($accion === 'ejecutar_fecha') {
        $fecha = $_POST['fecha_citacion'] ?? '';
        $objetoFecha = is_string($fecha) ? DateTime::createFromFormat('Y-m-d', $fecha) : false;

        if (!$objetoFecha || $objetoFecha->format('Y-m-d') !== $fecha) {
            responder_json(['success' => false, 'mensaje' => 'La fecha de citación no es válida.'], 422);
        }
        if ($fecha < date('Y-m-d', strtotime('+5 days'))) {
            responder_json(['success' => false, 'mensaje' => 'La fecha de citación no puede ser menor a 5 días a partir de hoy.'], 422);
        }
        if (!in_array($reporte['estado'], ['registrado', 'citado'], true)) {
            responder_json(['success' => false, 'mensaje' => 'El proceso ya fue cerrado y no admite una nueva citación.'], 409);
        }

        $conn->prepare("UPDATE reportes_novedades SET estado = 'citado', fecha_citacion = ?, citado_en = ? WHERE id = ?")
            ->execute([$fecha, $citadoEn = date('Y-m-d H:i:s'), $reporteId]);

        $estado = 'citado';
        $fechaCitacion = $fecha;
        $mensaje = 'Fecha asignada correctamente.';
    } else {
        $nuevoEstatus = $_POST['nuevo_estatus'] ?? '';

        if (!is_string($nuevoEstatus) || !array_key_exists($nuevoEstatus, ESTADOS_CIERRE)) {
            responder_json(['success' => false, 'mensaje' => 'El estatus seleccionado no es válido.'], 422);
        }

        $conn->prepare("UPDATE reportes_novedades SET estado = ? WHERE id = ?")
            ->execute([$nuevoEstatus, $reporteId]);

        $estado = $nuevoEstatus;
        $fechaCitacion = null;
        $citadoEn = null;
        $mensaje = 'Estatus del proceso actualizado.';
    }

    $info = estado_info($estado);

    responder_json([
        'success' => true,
        'mensaje' => $mensaje,
        'id' => $reporteId,
        'estado' => $estado,
        'fecha_citacion' => $fechaCitacion,
        'limite_citacion' => limite_citacion($fechaCitacion, $citadoEn),
        'estado_label' => $info['label'],
        'estado_tone' => $info['tone'],
        'estado_icon' => $info['icon'],
    ]);
} catch (Throwable $e) {
    error_log('procesar_juridico: ' . $e->getMessage());
    responder_json(['success' => false, 'mensaje' => 'No se pudo procesar la solicitud. Intente de nuevo.'], 500);
}
