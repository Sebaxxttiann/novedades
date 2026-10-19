<?php
require_once __DIR__ . '/../../app/config/config.php';
auth(['normal']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(['success' => false, 'mensaje' => 'Método no permitido.'], 405);
}

limitar('novedad:' . $_SESSION['usuario_id'], 20, 600);

$operacion_id = (int) ($_SESSION['operacion_id'] ?? 0);
if ($operacion_id <= 0) {
    responder_json(['success' => false, 'mensaje' => 'Su usuario no tiene una operación asignada. Contacte al administrador.']);
}

$colaborador_nombre = trim($_POST['colaborador_nombre'] ?? '');
$colaborador_cedula = trim($_POST['colaborador_cedula'] ?? '');
$colaborador_cargo = trim($_POST['colaborador_cargo'] ?? '');
$tipo_infraccion = $_POST['tipo_infraccion'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');
$decision = $_POST['decision'] ?? '';
$causal = $_POST['causal_llamado'] ?? '';

$errores = [];
if ($colaborador_nombre === '' || mb_strlen($colaborador_nombre) > 150) $errores[] = 'el nombre del colaborador';
if (!preg_match('/^\d{5,15}$/', $colaborador_cedula)) $errores[] = 'la cédula (solo números, 5 a 15 dígitos)';
if (mb_strlen($colaborador_cargo) > 150) $errores[] = 'el cargo';
if (!is_string($tipo_infraccion) || !isset(TIPOS_INFRACCION[$tipo_infraccion])) $errores[] = 'el tipo de infracción';
if ($descripcion === '' || mb_strlen($descripcion) > 5000) $errores[] = 'la descripción (máximo 5000 caracteres)';
if (!is_string($decision) || !isset(DECISIONES[$decision])) $errores[] = 'la decisión';
if ($decision === 'llamado_atencion' && (!is_string($causal) || !isset(CAUSALES_LLAMADO[$causal]))) $errores[] = 'la causal del llamado';

if ($errores) {
    responder_json(['success' => false, 'mensaje' => 'Revise ' . implode(', ', $errores) . '.']);
}

$causal = $decision === 'llamado_atencion' ? $causal : null;
$reincidente = $decision === 'proceso_disciplinario' && isset($_POST['reincidente']) ? 1 : 0;
$falta_grave = $decision === 'proceso_disciplinario' && isset($_POST['falta_grave']) ? 1 : 0;

try {
    $evidencia = guardar_evidencia($_FILES['evidencia'] ?? []);
} catch (RuntimeException $e) {
    responder_json(['success' => false, 'mensaje' => $e->getMessage()]);
}

try {
    $stmt = $conn->prepare("INSERT INTO reportes_novedades
        (creador_id, operacion_id, colaborador_nombre, colaborador_cedula, colaborador_cargo, tipo_infraccion, descripcion, evidencia, decision, causal_llamado, reincidente, falta_grave)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['usuario_id'], $operacion_id, $colaborador_nombre, $colaborador_cedula, $colaborador_cargo ?: null,
        $tipo_infraccion, $descripcion, $evidencia, $decision, $causal, $reincidente, $falta_grave,
    ]);
    $id = (int) $conn->lastInsertId();
} catch (PDOException $e) {
    error_log('procesar_novedad: ' . $e->getMessage());
    if ($evidencia) {
        @unlink(STORAGE_PATH . '/evidencias/' . $evidencia);
    }
    responder_json(['success' => false, 'mensaje' => 'No se pudo registrar la novedad. Intente nuevamente.']);
}

$estado = estado_info('registrado');

responder_json([
    'success' => true,
    'mensaje' => $decision === 'proceso_disciplinario'
        ? 'Se registró el proceso disciplinario y se notificó al área Jurídica.'
        : 'Llamado de atención registrado. Descargando plantilla...',
    'decision' => $decision,
    'plantilla_url' => $decision === 'llamado_atencion' ? 'descargas/llamado_atencion.php?id=' . $id : null,
    'reporte' => [
        'id' => $id,
        'colaborador_nombre' => $colaborador_nombre,
        'colaborador_cedula' => $colaborador_cedula,
        'tipo_infraccion' => $tipo_infraccion,
        'tipo_label' => TIPOS_INFRACCION[$tipo_infraccion],
        'decision' => $decision,
        'decision_label' => DECISIONES[$decision],
        'estado_label' => $estado['label'],
        'estado_tone' => $estado['tone'],
        'estado_icon' => $estado['icon'],
        'fecha' => date('d/m/Y'),
    ],
]);
