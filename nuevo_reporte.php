<?php
require_once 'config/config.php';
verificarSesion();

$empleados = $conn->query("SELECT * FROM empleados ORDER BY nombre ASC")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $empleado_id = $_POST['empleado_id'] ?? '';
    $tipo_infraccion = $_POST['tipo_infraccion'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $decision = $_POST['decision'] ?? '';
    $destinatarios = $_POST['destinatarios'] ?? [];

    $evidencia_path = null;
    if(isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid('evidencia_') . '.' . $ext;
        $upload_dir = 'assets/uploads/';
        if(!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $evidencia_path = $upload_dir . $nombre_archivo;
        move_uploaded_file($_FILES['evidencia']['tmp_name'], $evidencia_path);
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO reportes (creador_id, empleado_id, tipo_infraccion, descripcion, evidencia, decision, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $estado_inicial = ($decision === 'proceso_disciplinario') ? 'pendiente' : 'completado';
        $stmt->execute([$_SESSION['usuario_id'], $empleado_id, $tipo_infraccion, $descripcion, $evidencia_path, $decision, $estado_inicial]);
        $reporte_id = $conn->lastInsertId();

        if($decision === 'proceso_disciplinario') {
            $reincidente = isset($_POST['reincidente']) ? 1 : 0;
            $falta_grave = isset($_POST['falta_grave']) ? 1 : 0;
            $fecha_citacion = $_POST['fecha_citacion'] ?? null;

            $stmtDetalle = $conn->prepare("INSERT INTO detalles_disciplinario (reporte_id, reincidente, falta_grave, fecha_citacion_sugerida) VALUES (?, ?, ?, ?)");
            $stmtDetalle->execute([$reporte_id, $reincidente, $falta_grave, $fecha_citacion]);
        }

        if(!empty($destinatarios)) {
            $stmtNoti = $conn->prepare("INSERT INTO notificados_reporte (reporte_id, email_destino) VALUES (?, ?)");
            foreach($destinatarios as $correo) {
                if(filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $stmtNoti->execute([$reporte_id, $correo]);
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'mensaje' => 'Reporte registrado exitosamente']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'mensaje' => 'Error al registrar: ' . $e->getMessage()]);
    }
    exit;
}

include 'header.php';
?>

<div class="container" style="max-width: 800px;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 600;">Nuevo Reporte Disciplinario</h1>
        <p style="color: var(--text-muted);">Complete los campos para registrar una infracción</p>
    </div>

    <div class="card">
        <form id="formReporte" enctype="multipart/form-data">
            <div class="form-group">
                <label>Seleccionar Infractor / Empleado</label>
                <select name="empleado_id" class="form-control" required>
                    <option value="">Seleccione un empleado...</option>
                    <?php foreach($empleados as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?> (Cédula: <?= htmlspecialchars($emp['cedula']) ?> - Área: <?= htmlspecialchars($emp['area']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tipo de Infracción</label>
                <select name="tipo_infraccion" class="form-control" required>
                    <option value="">Seleccione tipo...</option>
                    <option value="operativa">Operativa</option>
                    <option value="seguridad">Seguridad</option>
                    <option value="gestion_humana">Gestión Humana</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descripción de la Infracción</label>
                <textarea name="descripcion" class="form-control" rows="4" required placeholder="Describa detalladamente los hechos..."></textarea>
            </div>

            <div class="form-group">
                <label>Cargar Evidencia (Imagen o Documento)</label>
                <input type="file" name="evidencia" class="form-control" accept="image/*,.pdf">
            </div>

            <div class="form-group">
                <label>Decisión a Tomar</label>
                <select name="decision" id="selectDecision" class="form-control" required>
                    <option value="">Seleccione decisión...</option>
                    <option value="llamado_atencion">Llamado de Atención</option>
                    <option value="proceso_disciplinario">Proceso Disciplinario</option>
                </select>
            </div>

            <div id="bloqueProceso" style="display: none; background: var(--bg-color); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 1.5rem;">
                <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--primary);">Configuración del Proceso Disciplinario</h3>
                
                <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 400;">
                        <input type="checkbox" name="reincidente" value="1" style="width: 18px; height: 18px;"> ¿Fue reincidente?
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 400;">
                        <input type="checkbox" name="falta_grave" value="1" style="width: 18px; height: 18px;"> ¿Fue una falta grave?
                    </label>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Fecha Sugerida para Citación</label>
                    <input type="date" name="fecha_citacion" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Correos adicionales para notificar (A quiénes les llegará copia)</label>
                <div id="listaCorreos" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="email" name="destinatarios[]" class="form-control" placeholder="correo@empresa.com">
                        <button type="button" class="btn" onclick="agregarCorreo()" style="background: var(--bg-color); border: 1px solid var(--border);"><i data-lucide="plus"></i></button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Registrar y Enviar Reporte
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('selectDecision').addEventListener('change', function() {
        const bloque = document.getElementById('bloqueProceso');
        if(this.value === 'proceso_disciplinario') {
            bloque.style.display = 'block';
        } else {
            bloque.style.display = 'none';
        }
    });

    function agregarCorreo() {
        const container = document.getElementById('listaCorreos');
        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.gap = '0.5rem';
        div.innerHTML = `
            <input type="email" name="destinatarios[]" class="form-control" placeholder="correo@empresa.com">
            <button type="button" class="btn" onclick="this.parentElement.remove()" style="background: #fee2e2; color: var(--danger); border: none;"><i data-lucide="trash-2" style="width: 16px; height: 16px;"></i></button>
        `;
        container.appendChild(div);
        lucide.createIcons();
    }

    document.getElementById('formReporte').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
            const response = await fetch('nuevo_reporte.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if(data.success) {
                mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.href = 'dashboard.php', 1500);
            } else {
                mostrarAlerta(data.mensaje, 'error');
            }
        } catch(error) {
            mostrarAlerta('Error en la conexión', 'error');
        }
    });
</script>

</body>
</html>