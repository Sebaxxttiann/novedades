<?php
require_once 'config/config.php';
verificarSesion();

$id = $_GET['id'] ?? null;
if(!$id) { header("Location: dashboard.php"); exit; }

$stmt = $conn->prepare("SELECT r.*, e.nombre as empleado_nombre, e.cedula as empleado_cedula, e.cargo, e.area, u.nombre as creador_nombre FROM reportes r JOIN empleados e ON r.empleado_id = e.id JOIN usuarios u ON r.creador_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$reporte = $stmt->fetch();

if(!$reporte) { header("Location: dashboard.php"); exit; }

include 'header.php';
?>

<div class="container" style="max-width: 700px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 600;">Detalle del Reporte #<?= $reporte['id'] ?></h1>
            <p style="color: var(--text-muted);">Registrado por <?= htmlspecialchars($reporte['creador_nombre']) ?></p>
        </div>
        <a href="dashboard.php" class="btn" style="background: var(--bg-color); border: 1px solid var(--border);">Volver</a>
    </div>

    <div class="card">
        <div style="margin-bottom: 1.5rem;">
            <strong style="color: var(--text-muted); font-size: 0.85rem;">INFORMACIÓN DEL INFRACTOR</strong>
            <p style="font-size: 1.1rem; font-weight: 600; margin-top: 0.2rem;"><?= htmlspecialchars($reporte['empleado_nombre']) ?></p>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Cédula: <?= htmlspecialchars($reporte['empleado_cedula']) ?> | Cargo: <?= htmlspecialchars($reporte['cargo']) ?> | Área: <?= htmlspecialchars($reporte['area']) ?></p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <strong style="color: var(--text-muted); font-size: 0.85rem;">TIPO DE INFRACCIÓN</strong>
                <p style="text-transform: capitalize; font-weight: 500;"><?= $reporte['tipo_infraccion'] ?></p>
            </div>
            <div>
                <strong style="color: var(--text-muted); font-size: 0.85rem;">DECISIÓN</strong>
                <p style="text-transform: capitalize; font-weight: 500;"><?= str_replace('_', ' ', $reporte['decision']) ?></p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <strong style="color: var(--text-muted); font-size: 0.85rem;">DESCRIPCIÓN</strong>
            <p style="background: var(--bg-color); padding: 1rem; border-radius: var(--radius); margin-top: 0.3rem;"><?= nl2br(htmlspecialchars($reporte['descripcion'])) ?></p>
        </div>

        <?php if(!empty($reporte['evidencia'])): ?>
            <div style="margin-bottom: 1.5rem;">
                <strong style="color: var(--text-muted); font-size: 0.85rem;">EVIDENCIA CARGADA</strong>
                <div style="margin-top: 0.5rem;">
                    <a href="<?= htmlspecialchars($reporte['evidencia']) ?>" target="_blank" class="btn" style="background: var(--bg-color); border: 1px solid var(--border); font-size: 0.85rem;">
                        <i data-lucide="file-text"></i> Ver Archivo de Evidencia
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if($reporte['decision'] === 'proceso_disciplinario'): ?>
            <div style="margin-top: 1.5rem;">
                <a href="juridico.php?id=<?= $reporte['id'] ?>" class="btn btn-primary">
                    <i data-lucide="scale"></i> Ver Gestión Jurídica
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>