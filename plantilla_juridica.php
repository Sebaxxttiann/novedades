<?php
require_once __DIR__ . '/app/config/config.php';
auth(['juridico']);

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$reporte = false;

if ($id && $id > 0) {
    $stmt = $conn->prepare("SELECT colaborador_nombre, colaborador_cedula, fecha_citacion
        FROM reportes_novedades
        WHERE id = ? AND decision = 'proceso_disciplinario' AND fecha_citacion IS NOT NULL");
    $stmt->execute([$id]);
    $reporte = $stmt->fetch();
}

if (!$reporte) {
    http_response_code(404);
    $tituloPagina = 'Documento no disponible';
    include __DIR__ . '/app/includes/header.php';
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
    <script>lucide.createIcons();</script>
    </body>
    </html>
    <?php
    exit;
}

$meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$nombre = e($reporte['colaborador_nombre']);
$cedula = e($reporte['colaborador_cedula']);
$fechaEjecucion = $reporte['fecha_citacion'];
$fechaActual = date('d') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');

$cedulaArchivo = preg_replace('/\D+/', '', $reporte['colaborador_cedula']) ?: 'documento';

header('Content-Type: application/vnd.ms-word; charset=utf-8');
header('Content-Disposition: attachment; filename="Citacion_Descargos_' . $cedulaArchivo . '.doc"');

require __DIR__ . '/templates/documentos/citacion_descargos.php';
