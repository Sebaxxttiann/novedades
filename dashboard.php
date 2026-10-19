<?php
require_once __DIR__ . '/app/config/config.php';
auth(['admin', 'normal']);

$esAdmin = $_SESSION['usuario_rol'] === 'admin';
$operacionSeleccionada = null;
$resumenOperaciones = [];

if ($esAdmin) {
    $resumenOperaciones = $conn->query("SELECT o.id, o.nombre,
            COUNT(r.id) AS total,
            COALESCE(SUM(r.decision = 'llamado_atencion'), 0) AS llamados,
            COALESCE(SUM(r.decision = 'proceso_disciplinario'), 0) AS procesos,
            COALESCE(SUM(r.decision = 'proceso_disciplinario' AND r.estado IN ('registrado', 'citado')), 0) AS abiertos,
            MAX(r.fecha_creacion) AS ultima
        FROM operaciones o
        LEFT JOIN reportes_novedades r ON r.operacion_id = o.id
        GROUP BY o.id, o.nombre
        ORDER BY total DESC, o.nombre ASC")->fetchAll();

    $idOperacion = filtro_get_int('operacion');
    foreach ($resumenOperaciones as $op) {
        if ((int) $op['id'] === $idOperacion) {
            $operacionSeleccionada = $op;
            break;
        }
    }
    $operacionFiltro = $operacionSeleccionada ? (int) $operacionSeleccionada['id'] : 0;
} else {
    $operacionFiltro = (int) ($_SESSION['operacion_id'] ?? 0);
}

$filtros = [
    'q' => filtro_get('q'),
    'decision' => filtro_get('decision', array_keys(DECISIONES)),
    'tipo' => filtro_get('tipo', array_keys(TIPOS_INFRACCION)),
    'estado' => filtro_get('estado', array_keys(ESTADOS)),
    'desde' => filtro_get_fecha('desde'),
    'hasta' => filtro_get_fecha('hasta'),
];
$hayFiltros = (bool) array_filter($filtros);
$mostrarTabla = !$esAdmin || $operacionSeleccionada;
$reportes = [];
$kpi = ['total' => 0, 'llamados' => 0, 'procesos' => 0, 'abiertos' => 0];

if ($mostrarTabla) {
    $condiciones = ['r.operacion_id = ?'];
    $parametros = [$operacionFiltro];

    if ($filtros['q'] !== '') {
        $condiciones[] = '(r.colaborador_nombre LIKE ? OR r.colaborador_cedula LIKE ? OR r.colaborador_cargo LIKE ?)';
        $like = '%' . $filtros['q'] . '%';
        array_push($parametros, $like, $like, $like);
    }
    if ($filtros['decision'] !== '') {
        $condiciones[] = 'r.decision = ?';
        $parametros[] = $filtros['decision'];
    }
    if ($filtros['tipo'] !== '') {
        $condiciones[] = 'r.tipo_infraccion = ?';
        $parametros[] = $filtros['tipo'];
    }
    if ($filtros['estado'] !== '') {
        $condiciones[] = 'r.estado = ?';
        $parametros[] = $filtros['estado'];
    }
    if ($filtros['desde'] !== '') {
        $condiciones[] = 'r.fecha_creacion >= ?';
        $parametros[] = $filtros['desde'] . ' 00:00:00';
    }
    if ($filtros['hasta'] !== '') {
        $condiciones[] = 'r.fecha_creacion <= ?';
        $parametros[] = $filtros['hasta'] . ' 23:59:59';
    }

    $stmt = $conn->prepare("SELECT r.id, r.colaborador_nombre, r.colaborador_cedula, r.colaborador_cargo, r.tipo_infraccion,
            r.decision, r.estado, r.reincidente, r.falta_grave, r.fecha_creacion, r.evidencia, u.nombre AS creador_nombre
        FROM reportes_novedades r
        LEFT JOIN usuarios u ON u.id = r.creador_id
        WHERE " . implode(' AND ', $condiciones) . "
        ORDER BY r.id DESC");
    $stmt->execute($parametros);
    $reportes = $stmt->fetchAll();

    foreach ($reportes as $r) {
        $kpi['total']++;
        $r['decision'] === 'llamado_atencion' ? $kpi['llamados']++ : $kpi['procesos']++;
        if ($r['decision'] === 'proceso_disciplinario' && in_array($r['estado'], ['registrado', 'citado'], true)) {
            $kpi['abiertos']++;
        }
    }
}

$tituloPagina = $esAdmin ? 'Novedades por operación' : 'Inicio';
include __DIR__ . '/app/includes/header.php';
?>

<style>
    .op-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }

    .op-card {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.2rem;
        text-decoration: none;
        color: var(--text-main);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .op-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .op-card-head {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .op-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .op-card-head h3 {
        font-size: 1rem;
        font-weight: 600;
    }

    .op-card-head p {
        font-size: 0.78rem;
        color: var(--text-muted);
    }

    .op-card-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .op-stat {
        background: var(--surface-alt);
        border-radius: 10px;
        padding: 0.6rem;
        text-align: center;
    }

    .op-stat strong {
        display: block;
        font-size: 1.15rem;
        font-weight: 600;
    }

    .op-stat span {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .op-card-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .op-card-foot .ver {
        color: var(--primary);
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .kpi-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .kpi {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 0.9rem 1rem;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--shadow-sm);
    }

    .kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kpi-icon svg {
        width: 18px;
        height: 18px;
    }

    .kpi strong {
        display: block;
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .kpi span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .opcion-decision {
        display: none;
        padding: 1rem 1.1rem;
        border-radius: 10px;
        margin-bottom: 1.1rem;
    }

    .opcion-decision.visible {
        display: block;
    }

    .opcion-llamado {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-left: 4px solid #f97316;
    }

    .opcion-proceso {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 4px solid #ef4444;
    }

    .check-line {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.88rem;
        margin-top: 8px;
        cursor: pointer;
    }

    .check-line input {
        width: 16px;
        height: 16px;
        accent-color: var(--danger);
    }

    .cedula-estado {
        font-size: 0.76rem;
        margin-top: 5px;
        min-height: 1rem;
        color: var(--text-muted);
    }

    .cedula-estado.ok {
        color: #047857;
    }

    .cedula-estado.aviso {
        color: #b45309;
    }

    @media (max-width: 900px) {
        .kpi-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1101px) {
        .form-grid.tres {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
</style>

<main class="container">
    <?php if ($esAdmin && !$operacionSeleccionada): ?>
        <div class="page-header">
            <div class="page-title">
                <h1>Novedades por operación</h1>
                <p>Seleccione una operación para revisar sus registros, estados y detalles.</p>
            </div>
        </div>

        <?php if (empty($resumenOperaciones)): ?>
            <div class="table-card">
                <div class="empty-state">
                    <i data-lucide="map"></i>
                    <h3>No hay operaciones registradas</h3>
                    <p>Cree una operación desde el módulo Operaciones.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="op-grid">
                <?php foreach ($resumenOperaciones as $op): ?>
                    <a class="op-card" href="dashboard.php?operacion=<?= (int) $op['id'] ?>">
                        <div class="op-card-head">
                            <div class="op-card-icon"><i data-lucide="building-2" style="width: 20px;"></i></div>
                            <div>
                                <h3><?= e($op['nombre']) ?></h3>
                                <p><?= (int) $op['total'] ?> novedad<?= (int) $op['total'] === 1 ? '' : 'es' ?> registrada<?= (int) $op['total'] === 1 ? '' : 's' ?></p>
                            </div>
                        </div>
                        <div class="op-card-stats">
                            <div class="op-stat"><strong><?= (int) $op['llamados'] ?></strong><span>Llamados</span></div>
                            <div class="op-stat"><strong><?= (int) $op['procesos'] ?></strong><span>Procesos</span></div>
                            <div class="op-stat"><strong style="color: <?= (int) $op['abiertos'] > 0 ? '#b45309' : 'inherit' ?>;"><?= (int) $op['abiertos'] ?></strong><span>Abiertos</span></div>
                        </div>
                        <div class="op-card-foot">
                            <span><?= $op['ultima'] ? 'Último: ' . e(date('d/m/Y', strtotime($op['ultima']))) : 'Sin registros' ?></span>
                            <span class="ver">Ver registros <i data-lucide="arrow-right" style="width: 14px;"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="page-header">
            <div class="page-title">
                <?php if ($esAdmin): ?>
                    <a href="dashboard.php" class="breadcrumb"><i data-lucide="arrow-left" style="width: 14px;"></i> Todas las operaciones</a>
                    <h1><?= e($operacionSeleccionada['nombre']) ?></h1>
                    <p>Registros de novedades de esta operación con su estado actual.</p>
                <?php else: ?>
                    <h1>Registro de Novedades</h1>
                    <p>Gestione los llamados de atención y procesos disciplinarios de su operación.</p>
                <?php endif; ?>
            </div>
            <?php if (!$esAdmin): ?>
                <div class="header-actions">
                    <button type="button" class="btn btn-primary" onclick="abrirModalNovedad()">
                        <i data-lucide="file-plus" style="width: 18px;"></i> Registrar Novedad
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <form class="filters-bar" id="formFiltros" method="get" autocomplete="off">
            <?php if ($esAdmin): ?>
                <input type="hidden" name="operacion" value="<?= (int) $operacionFiltro ?>">
            <?php endif; ?>
            <div class="filter-field grow">
                <label for="fQ">Buscar</label>
                <div class="search-input">
                    <i data-lucide="search"></i>
                    <input type="search" id="fQ" name="q" class="form-control" placeholder="Nombre, cédula o cargo" value="<?= e($filtros['q']) ?>">
                </div>
            </div>
            <div class="filter-field">
                <label for="fDecision">Decisión</label>
                <select id="fDecision" name="decision" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach (DECISIONES as $clave => $texto): ?>
                        <option value="<?= e($clave) ?>" <?= $filtros['decision'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="fTipo">Infracción</label>
                <select id="fTipo" name="tipo" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach (TIPOS_INFRACCION as $clave => $texto): ?>
                        <option value="<?= e($clave) ?>" <?= $filtros['tipo'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="fEstado">Estado</label>
                <select id="fEstado" name="estado" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach (ESTADOS as $clave => $info): ?>
                        <option value="<?= e($clave) ?>" <?= $filtros['estado'] === $clave ? 'selected' : '' ?>><?= e($info['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field" style="min-width: 240px;">
                <label>Rango de fechas</label>
                <div class="date-picker" data-rango-fechas>
                    <i data-lucide="calendar-range"></i>
                    <input type="text" class="form-control" placeholder="Seleccione un rango" readonly>
                    <input type="hidden" name="desde" value="<?= e($filtros['desde']) ?>">
                    <input type="hidden" name="hasta" value="<?= e($filtros['hasta']) ?>">
                    <button type="button" class="date-clear" aria-label="Quitar fechas"><i data-lucide="x"></i></button>
                </div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-filtro-limpiar style="margin-bottom: 4px;">
                <i data-lucide="x" style="width: 14px;"></i> Limpiar
            </button>
        </form>

        <div id="zonaResultados">
            <div class="kpi-row">
                <div class="kpi"><div class="kpi-icon tone-blue"><i data-lucide="files"></i></div><div><strong><?= $kpi['total'] ?></strong><span>Registros</span></div></div>
                <div class="kpi"><div class="kpi-icon tone-orange"><i data-lucide="file-warning"></i></div><div><strong><?= $kpi['llamados'] ?></strong><span>Llamados de atención</span></div></div>
                <div class="kpi"><div class="kpi-icon tone-red"><i data-lucide="gavel"></i></div><div><strong><?= $kpi['procesos'] ?></strong><span>Procesos disciplinarios</span></div></div>
                <div class="kpi"><div class="kpi-icon tone-amber"><i data-lucide="hourglass"></i></div><div><strong><?= $kpi['abiertos'] ?></strong><span>Procesos abiertos</span></div></div>
            </div>

            <div class="table-card" id="novedadesCard">
                <div class="table-scroll">
                    <table class="custom-table" id="tablaNovedades">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Infracción</th>
                                <th>Decisión</th>
                                <th>Estado</th>
                                <th>Reportado por</th>
                                <th>Fecha</th>
                                <th class="col-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportes as $r):
                                $estado = estado_info($r['estado']);
                                $esProceso = $r['decision'] === 'proceso_disciplinario';
                            ?>
                                <tr data-id="<?= (int) $r['id'] ?>">
                                    <td>
                                        <div class="cell-person">
                                            <div class="avatar avatar-lg"><?= e(iniciales($r['colaborador_nombre'])) ?></div>
                                            <div>
                                                <strong><?= e($r['colaborador_nombre']) ?></strong>
                                                <span class="mono">CC <?= e($r['colaborador_cedula']) ?><?= $r['colaborador_cargo'] ? ' · ' . e($r['colaborador_cargo']) : '' ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="nowrap"><?= e(TIPOS_INFRACCION[$r['tipo_infraccion']] ?? $r['tipo_infraccion']) ?></td>
                                    <td>
                                        <span class="badge <?= $esProceso ? 'tone-red' : 'tone-orange' ?>">
                                            <i data-lucide="<?= $esProceso ? 'gavel' : 'file-warning' ?>"></i>
                                            <?= e(DECISIONES[$r['decision']] ?? $r['decision']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge tone-<?= e($estado['tone']) ?>">
                                            <i data-lucide="<?= e($estado['icon']) ?>"></i>
                                            <?= e($estado['label']) ?>
                                        </span>
                                    </td>
                                    <td class="nowrap muted"><?= e($r['creador_nombre'] ?? '—') ?></td>
                                    <td><span class="date-cell"><i data-lucide="calendar"></i><?= e(date('d/m/Y', strtotime($r['fecha_creacion']))) ?></span></td>
                                    <td class="col-actions">
                                        <div class="row-actions">
                                            <?php if (!empty($r['evidencia'])): ?>
                                                <span class="btn-icon" title="Tiene evidencia adjunta" style="cursor: default;"><i data-lucide="paperclip"></i></span>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="verDetalleNovedad(<?= (int) $r['id'] ?>)">
                                                <i data-lucide="eye" style="width: 14px;"></i> Ver
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($reportes)): ?>
                                <tr data-no-paginate="true">
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i data-lucide="<?= $hayFiltros ? 'search-x' : 'inbox' ?>"></i>
                                            <h3><?= $hayFiltros ? 'Sin resultados' : 'Bandeja limpia' ?></h3>
                                            <p><?= $hayFiltros ? 'No hay registros que coincidan con los filtros aplicados.' : 'No se han registrado novedades disciplinarias aún.' ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php if (!$esAdmin): ?>
    <div class="modal-overlay" id="modalNovedad">
        <div class="modal-content" style="max-width: 760px;">
            <form id="formNovedad" enctype="multipart/form-data" novalidate>
                <div class="modal-header">
                    <h2>Registrar Novedad Disciplinaria</h2>
                    <button type="button" class="close-modal" onclick="toggleModal('modalNovedad', false)" aria-label="Cerrar"><i data-lucide="x" style="width: 20px;"></i></button>
                </div>

                <div class="modal-body">
                    <div class="form-grid tres">
                        <div class="form-group">
                            <label class="form-label" for="inputCedula">Cédula de ciudadanía</label>
                            <input type="text" id="inputCedula" name="colaborador_cedula" class="form-control" required inputmode="numeric" pattern="\d{5,15}" maxlength="15" placeholder="Sin puntos ni espacios" autocomplete="off">
                            <p id="cedulaStatus" class="cedula-estado"></p>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputNombre">Nombre del colaborador</label>
                            <input type="text" id="inputNombre" name="colaborador_nombre" class="form-control" required maxlength="150" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputCargo">Cargo</label>
                            <input type="text" id="inputCargo" name="colaborador_cargo" class="form-control" maxlength="150" placeholder="Se completa automáticamente">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="selectTipo">Tipo de infracción</label>
                            <select id="selectTipo" name="tipo_infraccion" class="form-control" required>
                                <option value="">Seleccione categoría...</option>
                                <?php foreach (TIPOS_INFRACCION as $clave => $texto): ?>
                                    <option value="<?= e($clave) ?>"><?= e($texto) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputEvidencia">Evidencia (opcional)</label>
                            <input type="file" id="inputEvidencia" name="evidencia" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.heic,.pdf">
                            <p class="form-hint">Imágenes o PDF, máximo 10 MB.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="inputDescripcion">Descripción detallada de los hechos</label>
                        <textarea id="inputDescripcion" name="descripcion" class="form-control" rows="3" required maxlength="5000" placeholder="Describa el incidente de forma clara y objetiva..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="selectDecision">Decisión administrativa</label>
                        <select name="decision" id="selectDecision" class="form-control" required>
                            <option value="">Seleccione la acción a ejecutar...</option>
                            <option value="llamado_atencion">Generar llamado de atención</option>
                            <option value="proceso_disciplinario">Escalar a proceso disciplinario</option>
                        </select>
                    </div>

                    <div id="divLlamado" class="opcion-decision opcion-llamado">
                        <label class="form-label" for="selectCausal">Causal del llamado de atención</label>
                        <select name="causal_llamado" id="selectCausal" class="form-control" style="background: white;">
                            <option value="">Seleccione la causal específica...</option>
                            <?php foreach (CAUSALES_LLAMADO as $clave => $texto): ?>
                                <option value="<?= e($clave) ?>"><?= e($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint" style="color: #9a3412;">Al registrar se descargará la plantilla legal en formato Word.</p>
                    </div>

                    <div id="divProceso" class="opcion-decision opcion-proceso">
                        <strong style="font-size: 0.88rem; color: #991b1b;">Evaluación preliminar (área Jurídica)</strong>
                        <label class="check-line"><input type="checkbox" name="reincidente" value="1"> El colaborador presenta reincidencia en faltas similares.</label>
                        <label class="check-line"><input type="checkbox" name="falta_grave" value="1"> Los hechos configuran una presunta falta grave al reglamento.</label>
                        <p class="form-hint" style="color: #991b1b;">Se notificará al área Jurídica para iniciar la investigación formal.</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="toggleModal('modalNovedad', false)">Cancelar</button>
                    <button type="submit" id="btnGuardarNovedad" class="btn btn-primary">Registrar y procesar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/app/includes/modal_detalle.php'; ?>

<script>
    const paginar = () => initPagination('tablaNovedades', { perPage: 10, container: '#novedadesCard' });
    paginar();
    const refrescarNovedades = initFiltros('formFiltros', { targets: ['#zonaResultados'], onUpdate: paginar });
</script>

<?php if (!$esAdmin): ?>
    <script>
        const formNovedad = document.getElementById('formNovedad');
        const inputCedula = document.getElementById('inputCedula');
        const inputNombre = document.getElementById('inputNombre');
        const inputCargo = document.getElementById('inputCargo');
        const cedulaStatus = document.getElementById('cedulaStatus');
        const selectDecision = document.getElementById('selectDecision');
        const selectCausal = document.getElementById('selectCausal');
        let cedulaTimeout = null;

        function abrirModalNovedad() {
            toggleModal('modalNovedad', true);
        }

        function reiniciarFormularioNovedad() {
            formNovedad.reset();
            cedulaStatus.textContent = '';
            cedulaStatus.className = 'cedula-estado';
            actualizarDecision();
        }

        function actualizarDecision() {
            const valor = selectDecision.value;
            document.getElementById('divLlamado').classList.toggle('visible', valor === 'llamado_atencion');
            document.getElementById('divProceso').classList.toggle('visible', valor === 'proceso_disciplinario');
            selectCausal.required = valor === 'llamado_atencion';
        }

        selectDecision.addEventListener('change', actualizarDecision);

        inputCedula.addEventListener('input', () => {
            inputCedula.value = inputCedula.value.replace(/\D/g, '');
            clearTimeout(cedulaTimeout);
            const cedula = inputCedula.value;
            cedulaStatus.textContent = '';
            cedulaStatus.className = 'cedula-estado';
            if (cedula.length < 5) return;

            cedulaTimeout = setTimeout(async () => {
                cedulaStatus.textContent = 'Buscando en headcount...';
                try {
                    const res = await fetch(`buscar_headcount.php?cedula=${encodeURIComponent(cedula)}`);
                    const data = await res.json();
                    if (inputCedula.value !== cedula) return;
                    if (data.found) {
                        inputNombre.value = data.nombre;
                        inputCargo.value = data.cargo;
                        cedulaStatus.textContent = 'Colaborador encontrado en el headcount.';
                        cedulaStatus.className = 'cedula-estado ok';
                    } else {
                        cedulaStatus.textContent = 'No está en el headcount. Complete los datos manualmente.';
                        cedulaStatus.className = 'cedula-estado aviso';
                    }
                } catch (error) {
                    cedulaStatus.textContent = '';
                }
            }, 450);
        });

        formNovedad.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!formNovedad.checkValidity()) {
                formNovedad.reportValidity();
                return;
            }

            const archivo = document.getElementById('inputEvidencia').files[0];
            if (archivo && archivo.size > 10 * 1024 * 1024) {
                showToast('Atención', 'La evidencia supera el tamaño máximo de 10 MB.', 'error');
                return;
            }

            const boton = document.getElementById('btnGuardarNovedad');
            setBotonCargando(boton, true, 'Procesando...');
            const data = await enviarFormulario('procesar_novedad.php', new FormData(formNovedad));
            setBotonCargando(boton, false);

            if (!data.success) {
                showToast('Atención', data.mensaje, 'error');
                return;
            }

            toggleModal('modalNovedad', false);
            reiniciarFormularioNovedad();
            showToast('Novedad registrada', data.mensaje, 'success');
            if (refrescarNovedades) refrescarNovedades();

            if (data.plantilla_url) {
                setTimeout(() => { window.location.href = data.plantilla_url; }, 800);
            }
        });
    </script>
<?php endif; ?>

</body>

</html>
