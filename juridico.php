<?php
require_once __DIR__ . '/app/config/config.php';
auth(['juridico']);

$estadosFiltro = array_merge(['registrado', 'citado'], array_keys(ESTADOS_CIERRE));

$q = filtro_get('q');
$estadoFiltro = filtro_get('estado', $estadosFiltro);
$operacionFiltro = filtro_get_int('operacion');
$desde = filtro_get_fecha('desde');
$hasta = filtro_get_fecha('hasta');

$condiciones = ["r.decision = 'proceso_disciplinario'"];
$parametros = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(r.colaborador_nombre LIKE ? OR r.colaborador_cedula LIKE ?)';
    array_push($parametros, $like, $like);
}
if ($operacionFiltro > 0) {
    $condiciones[] = 'r.operacion_id = ?';
    $parametros[] = $operacionFiltro;
}
if ($desde !== '') {
    $condiciones[] = 'DATE(r.fecha_creacion) >= ?';
    $parametros[] = $desde;
}
if ($hasta !== '') {
    $condiciones[] = 'DATE(r.fecha_creacion) <= ?';
    $parametros[] = $hasta;
}

$whereBase = implode(' AND ', $condiciones);

$stmt = $conn->prepare("SELECT COUNT(*) AS total,
        COALESCE(SUM(r.estado = 'registrado'), 0) AS pendientes,
        COALESCE(SUM(r.estado = 'citado'), 0) AS citados
    FROM reportes_novedades r WHERE $whereBase");
$stmt->execute($parametros);
$kpi = $stmt->fetch();
$kpiTotal = (int) $kpi['total'];
$kpiPendientes = (int) $kpi['pendientes'];
$kpiCitados = (int) $kpi['citados'];
$kpiCerrados = $kpiTotal - $kpiPendientes - $kpiCitados;

$whereLista = $whereBase;
$parametrosLista = $parametros;
if ($estadoFiltro !== '') {
    $whereLista .= ' AND r.estado = ?';
    $parametrosLista[] = $estadoFiltro;
}

$stmt = $conn->prepare("SELECT r.id, r.colaborador_nombre, r.colaborador_cedula, r.tipo_infraccion, r.falta_grave,
        r.estado, r.fecha_citacion, r.fecha_creacion, o.nombre AS operacion_nombre
    FROM reportes_novedades r
    LEFT JOIN operaciones o ON r.operacion_id = o.id
    WHERE $whereLista
    ORDER BY r.id DESC");
$stmt->execute($parametrosLista);
$reportes = $stmt->fetchAll();

$operaciones = $conn->query("SELECT id, nombre FROM operaciones ORDER BY nombre")->fetchAll();

$fechaMinima = date('Y-m-d', strtotime('+5 days'));

$tituloPagina = 'Panel Jurídico';
include __DIR__ . '/app/includes/header.php';
?>

<style>
    .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .kpi { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem; box-shadow: var(--shadow-sm); }
    .kpi-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); }
    .kpi-value { font-size: 1.5rem; font-weight: 600; color: var(--text-main); line-height: 1.3; }
    .countdown-timer { margin-top: 6px; font-size: 0.74rem; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 600; color: #c2410c; background: #fff7ed; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #ffedd5; }
    .countdown-timer svg { width: 12px; height: 12px; }
    .countdown-timer.expired { color: #15803d; background: #f0fdf4; border-color: #dcfce3; }
    @media (max-width: 720px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Panel Jurídico</h1>
            <p>Gestión, revisión y ejecución de procesos disciplinarios.</p>
        </div>
    </div>

    <div class="kpi-grid" id="kpisJuridico">
        <div class="kpi"><div class="kpi-label">Pendientes</div><div class="kpi-value" id="kpiPendientes"><?= $kpiPendientes ?></div></div>
        <div class="kpi"><div class="kpi-label">Citados</div><div class="kpi-value" id="kpiCitados"><?= $kpiCitados ?></div></div>
        <div class="kpi"><div class="kpi-label">Cerrados</div><div class="kpi-value" id="kpiCerrados"><?= $kpiCerrados ?></div></div>
        <div class="kpi"><div class="kpi-label">Total</div><div class="kpi-value" id="kpiTotal"><?= $kpiTotal ?></div></div>
    </div>

    <form class="filters-bar" id="formFiltrosJuridico" method="get">
        <div class="filter-field grow">
            <label for="filtroQ">Buscar</label>
            <div class="search-input">
                <i data-lucide="search"></i>
                <input type="search" id="filtroQ" name="q" class="form-control" placeholder="Nombre o cédula" value="<?= e($q) ?>">
            </div>
        </div>
        <div class="filter-field">
            <label for="filtroEstado">Estado</label>
            <select id="filtroEstado" name="estado" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($estadosFiltro as $clave): ?>
                    <option value="<?= e($clave) ?>" <?= $estadoFiltro === $clave ? 'selected' : '' ?>><?= e(estado_info($clave)['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label for="filtroOperacion">Operación</label>
            <select id="filtroOperacion" name="operacion" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($operaciones as $op): ?>
                    <option value="<?= (int) $op['id'] ?>" <?= $operacionFiltro === (int) $op['id'] ? 'selected' : '' ?>><?= e($op['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field" style="min-width: 240px;">
            <label>Rango de fechas</label>
            <div class="date-picker" data-rango-fechas>
                <i data-lucide="calendar-range"></i>
                <input type="text" class="form-control" placeholder="Seleccione un rango" readonly>
                <input type="hidden" name="desde" value="<?= e($desde) ?>">
                <input type="hidden" name="hasta" value="<?= e($hasta) ?>">
                <button type="button" class="date-clear" aria-label="Quitar fechas"><i data-lucide="x"></i></button>
            </div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-filtro-limpiar><i data-lucide="x"></i> Limpiar</button>
    </form>

    <div class="table-card" id="tarjetaJuridico">
        <div class="table-scroll">
            <table class="custom-table" id="tablaJuridico">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Infracción / Gravedad</th>
                        <th>Estado</th>
                        <th class="nowrap">Fecha registro</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$reportes): ?>
                        <tr data-no-paginate="true">
                            <td colspan="5">
                                <div class="empty-state">
                                    <i data-lucide="gavel"></i>
                                    <h3>Sin procesos disciplinarios</h3>
                                    <p>No hay resultados con los filtros aplicados.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($reportes as $r):
                        $info = estado_info($r['estado']);
                        $cerrado = isset(ESTADOS_CIERRE[$r['estado']]);
                    ?>
                        <tr data-id="<?= (int) $r['id'] ?>" data-estado="<?= e($r['estado']) ?>" data-nombre="<?= e($r['colaborador_nombre']) ?>">
                            <td>
                                <div class="cell-person">
                                    <div class="avatar avatar-lg avatar-primary"><?= e(iniciales($r['colaborador_nombre'])) ?></div>
                                    <div>
                                        <strong><?= e($r['colaborador_nombre']) ?></strong>
                                        <span>CC: <?= e($r['colaborador_cedula']) ?></span>
                                        <span class="badge tone-slate" style="display: inline-flex; margin-top: 4px;"><i data-lucide="map-pin"></i> <?= e($r['operacion_nombre'] ?? 'Global') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?= e(TIPOS_INFRACCION[$r['tipo_infraccion']] ?? $r['tipo_infraccion']) ?></div>
                                <?php if ($r['falta_grave']): ?>
                                    <span class="badge tone-red">Falta grave</span>
                                <?php endif; ?>
                            </td>
                            <td class="celda-estado">
                                <span class="badge tone-<?= e($info['tone']) ?>"><i data-lucide="<?= e($info['icon']) ?>"></i> <?= e($info['label']) ?></span>
                                <?php if ($r['estado'] === 'citado' && !empty($r['fecha_citacion'])): ?>
                                    <div><span class="countdown-timer" data-fecha="<?= e($r['fecha_citacion']) ?>T08:00:00-05:00"><i data-lucide="timer"></i><span class="countdown-texto">Calculando...</span></span></div>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap"><span class="date-cell"><i data-lucide="calendar"></i> <?= e(date('d/m/Y', strtotime($r['fecha_creacion']))) ?></span></td>
                            <td class="col-actions celda-acciones">
                                <div class="row-actions">
                                    <button type="button" class="btn btn-outline btn-sm" data-ver="<?= (int) $r['id'] ?>"><i data-lucide="eye"></i> Ver</button>
                                    <?php if ($r['estado'] === 'registrado'): ?>
                                        <button type="button" class="btn btn-outline btn-sm" data-agendar="<?= (int) $r['id'] ?>"><i data-lucide="calendar"></i> Agendar fecha</button>
                                    <?php endif; ?>
                                    <?php if (!$cerrado): ?>
                                        <button type="button" class="btn btn-outline btn-sm" data-cierre="<?= (int) $r['id'] ?>"><i data-lucide="activity"></i> Cierre</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-overlay" id="modalEjecutar">
    <div class="modal-content" style="max-width: 450px;">
        <form id="formEjecutar">
            <div class="modal-header">
                <h2>Agendar descargos</h2>
                <button type="button" class="close-modal" data-cerrar-modal="modalEjecutar" aria-label="Cerrar"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p class="muted" style="font-size: 0.9rem; margin-bottom: 1rem;">
                    Agende la diligencia de descargos para <strong id="txtNombreEjecutar"></strong>.
                </p>
                <input type="hidden" name="reporte_id" id="exec_reporte_id">
                <input type="hidden" name="accion" value="ejecutar_fecha">
                <div class="form-group">
                    <label class="form-label" for="exec_fecha">Fecha de citación a descargos</label>
                    <div class="date-picker" data-fecha data-min="<?= e($fechaMinima) ?>" id="exec_fecha_picker">
                        <i data-lucide="calendar"></i>
                        <input type="text" id="exec_fecha" class="form-control" placeholder="Seleccione la fecha" readonly>
                        <input type="hidden" name="fecha_citacion" id="exec_fecha_valor">
                    </div>
                    <div class="info-text"><i data-lucide="info"></i><span>La fecha no puede ser menor a 5 días a partir de hoy.</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-cerrar-modal="modalEjecutar">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar y generar citación</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEstatus">
    <div class="modal-content" style="max-width: 450px;">
        <form id="formEstatus">
            <div class="modal-header">
                <h2>Cierre del proceso</h2>
                <button type="button" class="close-modal" data-cerrar-modal="modalEstatus" aria-label="Cerrar"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="reporte_id" id="estatus_reporte_id">
                <input type="hidden" name="accion" value="actualizar_estatus">
                <div class="form-group">
                    <label class="form-label" for="estatus_select">Decisión final</label>
                    <select id="estatus_select" name="nuevo_estatus" class="form-control" required>
                        <option value="">Seleccione el resultado...</option>
                        <?php foreach (ESTADOS_CIERRE as $clave => $etiqueta): ?>
                            <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-cerrar-modal="modalEstatus">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar estatus</button>
            </div>
        </form>
    </div>
</div>

<script>
    const URL_PROCESAR = 'app/controllers/procesar_juridico.php';
    const ESTADOS_CERRADOS = <?= json_encode(array_keys(ESTADOS_CIERRE)) ?>;

    function actualizarContadores() {
        const ahora = Date.now();
        document.querySelectorAll('.countdown-timer').forEach(cont => {
            const destino = new Date(cont.dataset.fecha).getTime();
            const texto = cont.querySelector('.countdown-texto');
            if (!texto || isNaN(destino)) return;
            const distancia = destino - ahora;
            if (distancia < 0) {
                texto.textContent = 'Diligencia en proceso o cumplida';
                cont.classList.add('expired');
                return;
            }
            cont.classList.remove('expired');
            const dias = Math.floor(distancia / 86400000);
            const horas = Math.floor((distancia % 86400000) / 3600000);
            const minutos = Math.floor((distancia % 3600000) / 60000);
            const segundos = Math.floor((distancia % 60000) / 1000);
            texto.textContent = `Faltan: ${dias}d ${horas}h ${minutos}m ${segundos}s`;
        });
    }

    function actualizarKpis() {
        const filas = Array.from(document.querySelectorAll('#tablaJuridico tbody tr[data-id]'));
        const pendientes = filas.filter(f => f.dataset.estado === 'registrado').length;
        const citados = filas.filter(f => f.dataset.estado === 'citado').length;
        document.getElementById('kpiPendientes').textContent = pendientes;
        document.getElementById('kpiCitados').textContent = citados;
        document.getElementById('kpiCerrados').textContent = filas.length - pendientes - citados;
        document.getElementById('kpiTotal').textContent = filas.length;
    }

    function refrescarTabla() {
        initPagination('tablaJuridico', { perPage: 10 });
        actualizarContadores();
    }

    function actualizarFila(datos) {
        const fila = document.querySelector(`#tablaJuridico tbody tr[data-id="${datos.id}"]`);
        if (!fila) return;
        fila.dataset.estado = datos.estado;

        let html = `<span class="badge tone-${escapeHtml(datos.estado_tone)}"><i data-lucide="${escapeHtml(datos.estado_icon)}"></i> ${escapeHtml(datos.estado_label)}</span>`;
        if (datos.estado === 'citado' && datos.fecha_citacion) {
            html += `<div><span class="countdown-timer" data-fecha="${escapeHtml(datos.fecha_citacion)}T08:00:00-05:00"><i data-lucide="timer"></i><span class="countdown-texto">Calculando...</span></span></div>`;
        }
        fila.querySelector('.celda-estado').innerHTML = html;

        let acciones = `<button type="button" class="btn btn-outline btn-sm" data-ver="${datos.id}"><i data-lucide="eye"></i> Ver</button>`;
        if (datos.estado === 'registrado') {
            acciones += `<button type="button" class="btn btn-outline btn-sm" data-agendar="${datos.id}"><i data-lucide="calendar"></i> Agendar fecha</button>`;
        }
        if (!ESTADOS_CERRADOS.includes(datos.estado)) {
            acciones += `<button type="button" class="btn btn-outline btn-sm" data-cierre="${datos.id}"><i data-lucide="activity"></i> Cierre</button>`;
        }
        fila.querySelector('.celda-acciones').innerHTML = `<div class="row-actions">${acciones}</div>`;

        lucide.createIcons({ nodes: [fila] });
        actualizarContadores();
        actualizarKpis();
    }

    document.addEventListener('click', (e) => {
        const cerrar = e.target.closest('[data-cerrar-modal]');
        if (cerrar) {
            toggleModal(cerrar.dataset.cerrarModal, false);
            return;
        }

        const ver = e.target.closest('[data-ver]');
        if (ver) {
            verDetalleNovedad(parseInt(ver.dataset.ver, 10));
            return;
        }

        const agendar = e.target.closest('[data-agendar]');
        if (agendar) {
            const fila = agendar.closest('tr');
            document.getElementById('exec_reporte_id').value = agendar.dataset.agendar;
            document.getElementById('txtNombreEjecutar').textContent = fila ? fila.dataset.nombre : '';
            document.getElementById('exec_fecha_picker').limpiarFecha();
            toggleModal('modalEjecutar', true);
            return;
        }

        const cierre = e.target.closest('[data-cierre]');
        if (cierre) {
            document.getElementById('estatus_reporte_id').value = cierre.dataset.cierre;
            document.getElementById('estatus_select').value = '';
            toggleModal('modalEstatus', true);
        }
    });

    document.getElementById('formEjecutar').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!document.getElementById('exec_fecha_valor').value) {
            showToast('Atención', 'Seleccione la fecha de citación.', 'error');
            return;
        }
        const boton = e.target.querySelector('button[type="submit"]');
        setBotonCargando(boton, true, 'Guardando...');
        const datos = await enviarFormulario(URL_PROCESAR, new FormData(e.target));
        setBotonCargando(boton, false);

        if (!datos.success) {
            showToast('Error', datos.mensaje, 'error');
            return;
        }
        toggleModal('modalEjecutar', false);
        showToast('Éxito', datos.mensaje, 'success');
        actualizarFila(datos);
        window.location.href = 'plantilla_juridica.php?id=' + encodeURIComponent(datos.id);
    });

    document.getElementById('formEstatus').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formulario = e.target;
        const nuevoEstatus = formulario.elements['nuevo_estatus'].value;
        if (!nuevoEstatus) return;

        if (nuevoEstatus === 'terminacion_contrato') {
            const aceptado = await confirmar({
                titulo: 'Terminar contrato',
                mensaje: 'Esta decisión cierra el proceso con terminación de contrato y no se puede deshacer. ¿Desea continuar?',
                textoBoton: 'Sí, terminar contrato',
                peligro: true
            });
            if (!aceptado) return;
        }

        const boton = formulario.querySelector('button[type="submit"]');
        setBotonCargando(boton, true, 'Guardando...');
        const datos = await enviarFormulario(URL_PROCESAR, new FormData(formulario));
        setBotonCargando(boton, false);

        if (!datos.success) {
            showToast('Error', datos.mensaje, 'error');
            return;
        }
        toggleModal('modalEstatus', false);
        showToast('Actualizado', datos.mensaje, 'success');
        actualizarFila(datos);
    });

    initFiltros('formFiltrosJuridico', {
        targets: ['#kpisJuridico', '#tarjetaJuridico'],
        onUpdate: refrescarTabla
    });

    lucide.createIcons();
    refrescarTabla();
    setInterval(actualizarContadores, 1000);
</script>

<?php include __DIR__ . '/app/includes/modal_detalle.php'; ?>
</body>
</html>
