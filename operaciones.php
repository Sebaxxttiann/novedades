<?php
require_once __DIR__ . '/app/config/config.php';
auth();

if ($_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $nombre = trim($_POST['nombre']);

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'mensaje' => 'El nombre no puede estar vacío']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO operaciones (nombre) VALUES (?)");
        $stmt->execute([$nombre]);
        echo json_encode(['success' => true, 'mensaje' => 'Operación creada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error al crear la operación o nombre duplicado']);
    }
    exit;
}

$operaciones = $conn->query("SELECT * FROM operaciones ORDER BY id DESC")->fetchAll();

include __DIR__ . '/app/includes/header.php';
?>

<style>
    /* Estilos específicos compartidos para la interfaz Enterprise */
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

    /* Tabla Enterprise - Corregida para ocupar el 100% */
    .table-card {
        background: var(--surface);
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        width: 100%; /* Asegura que tome todo el espacio disponible */
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

    /* Elementos visuales de la tabla */
    .id-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        font-family: monospace;
    }

    .op-name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: var(--text-main);
    }

    .op-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #eff6ff;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .date-cell {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    /* Estado Vacío */
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

    .info-text {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        margin-top: 15px;
    }
</style>

<div class="container">
    <!-- Header de Sección -->
    <div class="page-header">
        <div class="page-title">
            <h1>Gestión de Operaciones</h1>
            <p>Administre las sedes, ciudades o áreas operativas del sistema.</p>
        </div>
        <button class="btn btn-primary" onclick="toggleModal('modalOperacion', true)">
            <i data-lucide="map-pin-plus" style="width: 18px;"></i> Nueva Operación
        </button>
    </div>

    <!-- Tabla de Operaciones -->
    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 150px;">ID Ref.</th>
                    <th>Nombre de la Operación (Ciudad/Sede)</th>
                    <th>Fecha de Creación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($operaciones as $op): ?>
                    <tr>
                        <td>
                            <span class="id-badge">#<?= str_pad($op['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        </td>
                        <td>
                            <div class="op-name">
                                <div class="op-icon">
                                    <i data-lucide="building-2" style="width: 16px; height: 16px;"></i>
                                </div>
                                <?= htmlspecialchars($op['nombre']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="date-cell">
                                <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                <?= date('d/m/Y', strtotime($op['creado_en'])) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($operaciones)): ?>
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i data-lucide="map-x" class="empty-state-icon"></i>
                                <h3>No hay operaciones registradas</h3>
                                <p>Haga clic en "Nueva Operación" para registrar su primera sede.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Operación -->
<div class="modal-overlay" id="modalOperacion">
    <div class="modal-content" style="max-width: 480px;">
        <form id="formOperacion">
            <div class="modal-header">
                <h2>Nueva Operación</h2>
                <button type="button" class="close-modal" onclick="toggleModal('modalOperacion', false)">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de la Sede o Ciudad</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej: Medellín, Bogotá Sur, Centro Logístico...">
                </div>
                
                <div class="info-text">
                    <i data-lucide="info" style="width: 18px; height: 18px; color: var(--primary); flex-shrink: 0; margin-top: 2px;"></i>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                        Al crear esta operación, estará disponible de inmediato en el selector al registrar o editar usuarios en el sistema.
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="toggleModal('modalOperacion', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarOp">
                    <span id="txtGuardarOp">Guardar Operación</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    document.getElementById('formOperacion').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardarOp');
        const txtGuardar = document.getElementById('txtGuardarOp');
        
        // Estado de carga
        btnGuardar.disabled = true;
        txtGuardar.textContent = 'Guardando...';

        try {
            const res = await fetch('operaciones.php', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const data = await res.json();

            if (data.success) {
                toggleModal('modalOperacion', false);
                showToast('Operación Exitosa', data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast('Atención', data.mensaje, 'error');
                btnGuardar.disabled = false;
                txtGuardar.textContent = 'Guardar Operación';
            }
        } catch (error) {
            showToast('Error', 'No se pudo conectar con el servidor.', 'error');
            btnGuardar.disabled = false;
            txtGuardar.textContent = 'Guardar Operación';
        }
    });
</script>
</body>
</html>