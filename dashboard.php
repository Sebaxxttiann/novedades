<?php
require_once __DIR__ . '/app/config/config.php';
auth();
include __DIR__ . '/app/includes/header.php';

$operacion_id = $_SESSION['operacion_id'];
if ($_SESSION['usuario_rol'] == 'normal') {
    $stmt = $conn->prepare("SELECT * FROM reportes_novedades WHERE operacion_id = ? ORDER BY id DESC");
    $stmt->execute([$operacion_id]);
} else {
    $stmt = $conn->prepare("SELECT * FROM reportes_novedades ORDER BY id DESC");
    $stmt->execute();
}
$reportes = $stmt->fetchAll();
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .page-title h1 {
        font-size: 1.6rem;
        font-weight: 600;
        color: var(--text-main);
        letter-spacing: -0.5px;
    }

    .page-title p {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-top: 2px;
    }

    .table-card {
        background: var(--surface);
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        width: 100%;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .custom-table th {
        background: #f8fafc;
        padding: 1rem 1.2rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
    }

    .custom-table td {
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--border);
        font-size: 0.95rem;
        vertical-align: middle;
    }

    .custom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .custom-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e2e8f0;
        color: var(--text-main);
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .user-details strong {
        color: var(--text-main);
        font-weight: 600;
        display: block;
    }

    .user-details span {
        color: var(--text-muted);
        font-size: 0.8rem;
        font-family: monospace;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        margin-top: 4px;
        display: inline-block;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-llamado {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #ffedd5;
    }

    .badge-proceso {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fee2e2;
    }

    .badge-operativa {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .badge-seguridad {
        background: #f0fdf4;
        color: #15803d;
    }

    .badge-gestion {
        background: #faf5ff;
        color: #7e22ce;
    }

    .date-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .dynamic-section {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 10px;
        margin-top: 1.2rem;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--primary);
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: var(--text-muted);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .empty-state-icon {
        width: 48px;
        height: 48px;
        color: #cbd5e1;
        margin-bottom: 8px;
    }

    .custom-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 0;
    }

    .custom-checkbox input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
        cursor: pointer;
    }
</style>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Registro de Novedades</h1>
            <p>Gestione los llamados de atención y procesos disciplinarios de su operación.</p>
        </div>
        <?php if ($_SESSION['usuario_rol'] == 'normal' || $_SESSION['usuario_rol'] == 'admin'): ?>
            <button class="bt   n btn-primary" onclick="toggleModal('modalNovedad', true)">
                <i data-lucide="file-plus" style="width: 18px;"></i> Registrar Novedad
            </button>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Infracción</th>
                    <th>Decisión Tomada</th>
                    <th>Fecha de Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportes as $r):
                    $iniciales = strtoupper(substr($r['colaborador_nombre'], 0, 2));

                    $decisionClass = $r['decision'] === 'proceso_disciplinario' ? 'badge-proceso' : 'badge-llamado';
                    $decisionIcon = $r['decision'] === 'proceso_disciplinario' ? 'gavel' : 'file-warning';

                    $infraccionClass = 'badge-operativa';
                    $infraccionIcon = 'settings';
                    if ($r['tipo_infraccion'] === 'seguridad') {
                        $infraccionClass = 'badge-seguridad';
                        $infraccionIcon = 'shield-alert';
                    }
                    if ($r['tipo_infraccion'] === 'gestion_humana') {
                        $infraccionClass = 'badge-gestion';
                        $infraccionIcon = 'users';
                    }
                ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="avatar-circle"><?= $iniciales ?></div>
                                <div class="user-details">
                                    <strong><?= htmlspecialchars($r['colaborador_nombre']) ?></strong>
                                    <span>CC: <?= htmlspecialchars($r['colaborador_cedula']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $infraccionClass ?>">
                                <i data-lucide="<?= $infraccionIcon ?>" style="width: 14px; height: 14px;"></i>
                                <?= str_replace('_', ' ', $r['tipo_infraccion']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $decisionClass ?>">
                                <i data-lucide="<?= $decisionIcon ?>" style="width: 14px; height: 14px;"></i>
                                <?= str_replace('_', ' ', $r['decision']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="date-cell">
                                <i data-lucide="calendar" style="width: 15px; height: 15px;"></i>
                                <?= date('d/m/Y', strtotime($r['fecha_creacion'])) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($reportes)): ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i data-lucide="inbox" class="empty-state-icon"></i>
                                <h3>Bandeja Limpia</h3>
                                <p>No se han registrado novedades disciplinarias aún.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Novedad -->
<div class="modal-overlay" id="modalNovedad">
    <div class="modal-content" style="max-width: 650px;">
        <form id="formNovedad" enctype="multipart/form-data">
            <div class="modal-header">
                <h2>Registrar Novedad Disciplinaria</h2>
                <button type="button" class="close-modal" onclick="toggleModal('modalNovedad', false)">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Grid de 2 columnas para optimizar espacio -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Nombre del Colaborador</label>
                        <input type="text" name="colaborador_nombre" class="form-control" required placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Cédula de Ciudadanía</label>
                        <input type="text" name="colaborador_cedula" class="form-control" required placeholder="Número de documento sin puntos">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1.2rem;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Tipo de Infracción</label>
                        <select name="tipo_infraccion" class="form-control" required>
                            <option value="">Seleccione categoría...</option>
                            <option value="operativa">Operativa</option>
                            <option value="seguridad">Seguridad</option>
                            <option value="gestion_humana">Gestión Humana</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Carga de Evidencia (Opcional)</label>
                        <input type="file" name="evidencia" class="form-control" accept="image/*,.pdf" style="padding: 0.55rem 1rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción Detallada de los Hechos</label>
                    <textarea name="descripcion" class="form-control" rows="3" required placeholder="Describa el incidente de forma clara y objetiva..."></textarea>
                </div>

                <div class="form-group" style="margin-top: 1.5rem; border-top: 1px dashed var(--border); padding-top: 1.5rem;">
                    <label class="form-label" style="font-weight: 600; color: var(--text-main);">Decisión Administrativa a Tomar</label>
                    <select name="decision" id="selectDecision" class="form-control" style="background: #f8fafc;" required>
                        <option value="">Seleccione la acción a ejecutar...</option>
                        <option value="llamado_atencion">Generar Llamado de Atención</option>
                        <option value="proceso_disciplinario">Escalar a Proceso Disciplinario</option>
                    </select>
                </div>

                <!-- Secciones Dinámicas -->
                <div id="divLlamado" class="dynamic-section" style="display: none; border-left-color: #f59e0b;">
                    <label class="form-label" style="color: #92400e;">Causal del Llamado de Atención</label>
                    <select name="causal_llamado" class="form-control">
                        <option value="">Seleccione la causal específica...</option>
                        <option value="alcoholemia">Alcoholemia</option>
                        <option value="ausentismo">Ausentismo Injustificado</option>
                        <option value="llegar_tarde">Llegadas Tardías</option>
                        <option value="no_acatar_ordenes">No acatar directrices operativas</option>
                        <option value="no_marcar">Omisión de marcación (Entrada/Salida)</option>
                        <option value="bajo_rendimiento">Bajo rendimiento o negligencia</option>
                    </select>
                    <div style="display: flex; gap: 8px; margin-top: 12px; color: #b45309; background: #fffbeb; padding: 8px 12px; border-radius: 6px;">
                        <i data-lucide="file-down" style="width: 18px; flex-shrink: 0;"></i>
                        <p style="font-size: 0.8rem; margin: 0;">Al procesar, se descargará automáticamente la plantilla legal correspondiente en formato Word.</p>
                    </div>
                </div>

                <div id="divProceso" class="dynamic-section" style="display: none; border-left-color: #ef4444;">
                    <p class="form-label" style="margin-bottom: 0.8rem; color: #991b1b;">Evaluación Preliminar (Área Jurídica)</p>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="reincidente" value="1">
                        <span style="font-size: 0.9rem; color: var(--text-main);">El colaborador presenta reincidencia en faltas similares.</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="falta_grave" value="1">
                        <span style="font-size: 0.9rem; color: var(--text-main);">Los hechos configuran una presunta falta grave al reglamento.</span>
                    </label>
                    <div style="display: flex; gap: 8px; margin-top: 12px; color: #b91c1c; background: #fef2f2; padding: 8px 12px; border-radius: 6px;">
                        <i data-lucide="mail-warning" style="width: 18px; flex-shrink: 0;"></i>
                        <p style="font-size: 0.8rem; margin: 0;">Esta acción notificará inmediatamente al departamento Jurídico para iniciar la investigación formal.</p>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="toggleModal('modalNovedad', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarNovedad">
                    <span id="txtGuardarNovedad">Registrar y Procesar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    document.getElementById('selectDecision').addEventListener('change', function() {
        const val = this.value;
        const divLlamado = document.getElementById('divLlamado');
        const divProceso = document.getElementById('divProceso');

        divLlamado.style.display = val === 'llamado_atencion' ? 'block' : 'none';
        divProceso.style.display = val === 'proceso_disciplinario' ? 'block' : 'none';

        document.querySelector('select[name="causal_llamado"]').required = val === 'llamado_atencion';
    });

    document.getElementById('formNovedad').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btnGuardar = document.getElementById('btnGuardarNovedad');
        const txtGuardar = document.getElementById('txtGuardarNovedad');

        btnGuardar.disabled = true;
        txtGuardar.textContent = 'Procesando Novedad...';

        const formData = new FormData(e.target);

        try {
            const res = await fetch('procesar_novedad.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                toggleModal('modalNovedad', false);
                e.target.reset();
                document.getElementById('divLlamado').style.display = 'none';
                document.getElementById('divProceso').style.display = 'none';

                showToast('Novedad Registrada', data.mensaje, 'success');

                if (data.decision === 'llamado_atencion') {
                    setTimeout(() => {
                        window.location.href = `descargar_plantilla.php?causal=${data.causal}&nombre=${encodeURIComponent(data.nombre)}&cedula=${data.cedula}`;
                    }, 1000);
                }

                setTimeout(() => window.location.reload(), 2500);
            } else {
                showToast('Atención', data.mensaje, 'error');
                btnGuardar.disabled = false;
                txtGuardar.textContent = 'Registrar y Procesar';
            }
        } catch (error) {
            showToast('Error de red', 'No se pudo conectar con el servidor', 'error');
            btnGuardar.disabled = false;
            txtGuardar.textContent = 'Registrar y Procesar';
        }
    });
</script>

</body>

</html>