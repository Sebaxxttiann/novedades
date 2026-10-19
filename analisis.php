<?php
require_once __DIR__ . '/app/config/config.php';
auth(['admin']);

require_once __DIR__ . '/app/includes/analitica.php';

$operaciones = $conn->query("SELECT id, nombre FROM operaciones ORDER BY nombre ASC")->fetchAll();
$idsExistentes = array_map('intval', array_column($operaciones, 'id'));
$idsSeleccionados = [];
if (is_string($_GET['operaciones'] ?? null) && $_GET['operaciones'] !== '') {
    foreach (explode(',', $_GET['operaciones']) as $parte) {
        $parte = trim($parte);
        if (ctype_digit($parte) && in_array((int) $parte, $idsExistentes, true)) {
            $idsSeleccionados[] = (int) $parte;
        }
    }
    $idsSeleccionados = array_values(array_unique($idsSeleccionados));
}
if (count($idsSeleccionados) === count($idsExistentes)) {
    $idsSeleccionados = [];
}
$todasSeleccionadas = !$idsSeleccionados;
$analitica = obtenerAnalitica($conn, $idsSeleccionados);
$tituloPagina = 'Análisis';
if ($todasSeleccionadas) {
    $etiquetaFiltro = 'Todas las operaciones';
} elseif (count($idsSeleccionados) === 1) {
    $etiquetaFiltro = '';
    foreach ($operaciones as $op) {
        if ((int) $op['id'] === $idsSeleccionados[0]) {
            $etiquetaFiltro = $op['nombre'];
        }
    }
} else {
    $etiquetaFiltro = count($idsSeleccionados) . ' operaciones seleccionadas';
}

include __DIR__ . '/app/includes/header.php';
?>

<style>
    .filtro-wrap {
        position: relative;
    }

    .filtro-panel {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 300px;
        max-height: 360px;
        overflow-y: auto;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        padding: 0.75rem;
        z-index: 100;
    }

    .filtro-panel.open {
        display: block;
    }

    .filtro-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.5rem 0.4rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.88rem;
        color: var(--text-main);
    }

    .filtro-item:hover {
        background: #f8fafc;
    }

    .filtro-item input {
        width: 16px;
        height: 16px;
        accent-color: var(--primary);
        cursor: pointer;
    }

    .filtro-divider {
        border-top: 1px solid var(--border);
        margin: 6px 0;
    }

    .filtro-actions {
        margin-top: 8px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .kpi-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.3rem 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kpi-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.1;
    }

    .kpi-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .insight-card {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        padding: 1.3rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .insight-card .kpi-icon {
        background: white;
        color: var(--primary);
        border: 1px solid #bfdbfe;
    }

    .insight-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
    }

    .insight-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.2rem;
        margin-bottom: 1.2rem;
    }

    .chart-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.4rem 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .chart-card h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 1rem;
    }

    .chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
        text-align: center;
        gap: 8px;
    }

    @media (max-width: 1000px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Análisis de Novedades</h1>
            <p>Panorama disciplinario de todas las operaciones: tendencias, reincidencia y distribución de casos.</p>
        </div>

        <div class="filtro-wrap">
            <button type="button" class="btn btn-ghost" style="border: 1px solid var(--border);" id="btnFiltro">
                <i data-lucide="filter" style="width: 18px;"></i>
                <span id="filtroLabel"><?= e($etiquetaFiltro) ?></span>
                <i data-lucide="chevron-down" style="width: 16px;"></i>
            </button>
            <div class="filtro-panel" id="filtroPanel">
                <label class="filtro-item">
                    <input type="checkbox" id="chkTodas" <?= $todasSeleccionadas ? 'checked' : '' ?>>
                    <strong>Todas las operaciones</strong>
                </label>
                <div class="filtro-divider"></div>
                <?php foreach ($operaciones as $op): ?>
                    <label class="filtro-item">
                        <input type="checkbox" class="chk-operacion" value="<?= (int) $op['id'] ?>" <?= $todasSeleccionadas || in_array((int) $op['id'], $idsSeleccionados, true) ? 'checked' : '' ?>>
                        <?= e($op['nombre']) ?>
                    </label>
                <?php endforeach; ?>
                <div class="filtro-actions">
                    <button type="button" class="btn btn-primary" id="btnAplicarFiltro" style="width: 100%;">Aplicar filtro</button>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-grid" id="kpiGrid"></div>

    <div class="insight-grid" id="insightGrid"></div>

    <div class="charts-grid">
        <div class="chart-card">
            <h3>Llamados de Atención y Procesos Disciplinarios (últimos 12 meses)</h3>
            <div id="chartMensual"></div>
        </div>
        <div class="chart-card">
            <h3>Distribución por Tipo de Infracción</h3>
            <div id="chartTipo"></div>
        </div>
    </div>

    <div class="charts-grid" style="grid-template-columns: 1fr 1fr;">
        <div class="chart-card">
            <h3>Llamados vs Procesos por Operación</h3>
            <div id="chartOperacion"></div>
        </div>
        <div class="chart-card">
            <h3>Top Colaboradores Reincidentes</h3>
            <div id="chartColaboradores"></div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0"></script>
<script>
    lucide.createIcons();

    const initialData = <?= json_encode($analitica, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const coloresDecision = { llamados: '#f97316', procesos: '#ef4444' };
    const coloresTipo = { operativa: '#2563eb', seguridad: '#10b981', gestion_humana: '#7c3aed' };
    const etiquetasTipo = { operativa: 'Operativa', seguridad: 'Seguridad', gestion_humana: 'Gestión Humana' };

    let chartMensual, chartTipo, chartOperacion, chartColaboradores;

    function formatearMes(claveMes) {
        const [anio, mes] = claveMes.split('-');
        const fecha = new Date(anio, mes - 1, 1);
        return fecha.toLocaleDateString('es-CO', { month: 'short', year: 'numeric' });
    }

    function renderKPIs(kpi) {
        const kpiGrid = document.getElementById('kpiGrid');
        kpiGrid.innerHTML = `
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#eff6ff; color:#2563eb;"><i data-lucide="file-text" style="width:22px;"></i></div>
                <div><div class="kpi-value">${Number(kpi.total)}</div><div class="kpi-label">Total de Novedades</div></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fff7ed; color:#f97316;"><i data-lucide="file-warning" style="width:22px;"></i></div>
                <div><div class="kpi-value">${Number(kpi.llamados)}</div><div class="kpi-label">Llamados de Atención</div></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#fef2f2; color:#ef4444;"><i data-lucide="gavel" style="width:22px;"></i></div>
                <div><div class="kpi-value">${Number(kpi.procesos)}</div><div class="kpi-label">Procesos Disciplinarios</div></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#faf5ff; color:#7c3aed;"><i data-lucide="repeat" style="width:22px;"></i></div>
                <div><div class="kpi-value">${Number(kpi.reincidentes)}</div><div class="kpi-label">Colaboradores Reincidentes</div></div>
            </div>
        `;
        lucide.createIcons();
    }

    function renderInsights(porOperacion, topColaboradores) {
        const insightGrid = document.getElementById('insightGrid');
        const topOp = porOperacion[0];
        const topColab = topColaboradores[0];

        insightGrid.innerHTML = `
            <div class="insight-card">
                <div class="kpi-icon"><i data-lucide="map-pin" style="width:22px;"></i></div>
                <div>
                    <div class="insight-name">${topOp ? escapeHtml(topOp.operacion) : 'Sin datos'}</div>
                    <div class="insight-label">Operación con más novedades${topOp ? ` (${Number(topOp.total)})` : ''}</div>
                </div>
            </div>
            <div class="insight-card">
                <div class="kpi-icon"><i data-lucide="user-round" style="width:22px;"></i></div>
                <div>
                    <div class="insight-name">${topColab ? escapeHtml(topColab.nombre) : 'Sin reincidencias'}</div>
                    <div class="insight-label">Colaborador más reincidente${topColab ? ` (${Number(topColab.total)} novedades)` : ''}</div>
                </div>
            </div>
        `;
        lucide.createIcons();
    }

    function renderChartMensual(porMes) {
        const opciones = {
            chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'Poppins, sans-serif' },
            series: [
                { name: 'Llamados de Atención', data: porMes.map(m => m.llamados) },
                { name: 'Procesos Disciplinarios', data: porMes.map(m => m.procesos) }
            ],
            xaxis: { categories: porMes.map(m => formatearMes(m.mes)) },
            colors: [coloresDecision.llamados, coloresDecision.procesos],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid: { borderColor: '#e2e8f0' }
        };

        if (chartMensual) {
            chartMensual.updateOptions(opciones);
        } else {
            chartMensual = new ApexCharts(document.querySelector('#chartMensual'), opciones);
            chartMensual.render();
        }
    }

    function renderChartTipo(porTipo) {
        const el = document.querySelector('#chartTipo');
        if (!porTipo.length) {
            el.innerHTML = '<div class="chart-empty"><i data-lucide="pie-chart" style="width:32px;"></i>Sin datos para este filtro</div>';
            lucide.createIcons();
            if (chartTipo) { chartTipo.destroy(); chartTipo = null; }
            return;
        }

        const opciones = {
            chart: { type: 'donut', height: 320, fontFamily: 'Poppins, sans-serif' },
            series: porTipo.map(t => t.total),
            labels: porTipo.map(t => etiquetasTipo[t.tipo] || t.tipo),
            colors: porTipo.map(t => coloresTipo[t.tipo] || '#94a3b8'),
            legend: { position: 'bottom' },
            dataLabels: { enabled: true, formatter: (val) => val.toFixed(1) + '%' }
        };

        if (chartTipo) {
            chartTipo.updateOptions(opciones);
        } else {
            el.innerHTML = '';
            chartTipo = new ApexCharts(el, opciones);
            chartTipo.render();
        }
    }

    function renderChartOperacion(porOperacion) {
        const el = document.querySelector('#chartOperacion');
        if (!porOperacion.length) {
            el.innerHTML = '<div class="chart-empty"><i data-lucide="bar-chart-3" style="width:32px;"></i>Sin datos para este filtro</div>';
            lucide.createIcons();
            if (chartOperacion) { chartOperacion.destroy(); chartOperacion = null; }
            return;
        }

        const opciones = {
            chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Poppins, sans-serif' },
            series: [
                { name: 'Llamados de Atención', data: porOperacion.map(o => Number(o.llamados)) },
                { name: 'Procesos Disciplinarios', data: porOperacion.map(o => Number(o.procesos)) }
            ],
            xaxis: { categories: porOperacion.map(o => o.operacion) },
            colors: [coloresDecision.llamados, coloresDecision.procesos],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top', horizontalAlign: 'left' },
            grid: { borderColor: '#e2e8f0' }
        };

        if (chartOperacion) {
            chartOperacion.updateOptions(opciones);
        } else {
            el.innerHTML = '';
            chartOperacion = new ApexCharts(el, opciones);
            chartOperacion.render();
        }
    }

    function renderChartColaboradores(topColaboradores) {
        const el = document.querySelector('#chartColaboradores');
        if (!topColaboradores.length) {
            el.innerHTML = '<div class="chart-empty"><i data-lucide="users" style="width:32px;"></i>Aún no hay colaboradores reincidentes</div>';
            lucide.createIcons();
            if (chartColaboradores) { chartColaboradores.destroy(); chartColaboradores = null; }
            return;
        }

        const opciones = {
            chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Poppins, sans-serif' },
            series: [{ name: 'Novedades', data: topColaboradores.map(c => Number(c.total)) }],
            xaxis: { categories: topColaboradores.map(c => c.nombre) },
            colors: ['#7c3aed'],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            dataLabels: { enabled: true },
            grid: { borderColor: '#e2e8f0' }
        };

        if (chartColaboradores) {
            chartColaboradores.updateOptions(opciones);
        } else {
            el.innerHTML = '';
            chartColaboradores = new ApexCharts(el, opciones);
            chartColaboradores.render();
        }
    }

    function renderTodo(data) {
        renderKPIs(data.kpi);
        renderInsights(data.porOperacion, data.topColaboradores);
        renderChartMensual(data.porMes);
        renderChartTipo(data.porTipo);
        renderChartOperacion(data.porOperacion);
        renderChartColaboradores(data.topColaboradores);
    }

    renderTodo(initialData);

    const btnFiltro = document.getElementById('btnFiltro');
    const filtroPanel = document.getElementById('filtroPanel');
    const chkTodas = document.getElementById('chkTodas');
    const chksOperacion = Array.from(document.querySelectorAll('.chk-operacion'));
    const filtroLabel = document.getElementById('filtroLabel');

    btnFiltro.addEventListener('click', (e) => {
        e.stopPropagation();
        filtroPanel.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
        if (!filtroPanel.contains(e.target) && e.target !== btnFiltro) {
            filtroPanel.classList.remove('open');
        }
    });

    chkTodas.addEventListener('change', () => {
        chksOperacion.forEach(chk => chk.checked = chkTodas.checked);
    });

    chksOperacion.forEach(chk => {
        chk.addEventListener('change', () => {
            chkTodas.checked = chksOperacion.every(c => c.checked);
        });
    });

    document.getElementById('btnAplicarFiltro').addEventListener('click', async () => {
        const seleccionadas = chksOperacion.filter(c => c.checked);

        if (seleccionadas.length === 0) {
            showToast('Atención', 'Seleccione al menos una operación.', 'error');
            return;
        }

        filtroPanel.classList.remove('open');

        if (seleccionadas.length === chksOperacion.length) {
            filtroLabel.textContent = 'Todas las operaciones';
        } else if (seleccionadas.length === 1) {
            filtroLabel.textContent = seleccionadas[0].closest('.filtro-item').textContent.trim();
        } else {
            filtroLabel.textContent = `${seleccionadas.length} operaciones seleccionadas`;
        }

        const idsParam = seleccionadas.length === chksOperacion.length
            ? ''
            : seleccionadas.map(c => c.value).join(',');

        const url = new URL(window.location.href);
        if (idsParam) url.searchParams.set('operaciones', idsParam); else url.searchParams.delete('operaciones');
        history.replaceState(null, '', url);

        try {
            const res = await fetch(`app/controllers/analisis_data.php?operaciones=${encodeURIComponent(idsParam)}`);
            const data = await res.json();
            if (data.success) {
                renderTodo(data.data);
            } else {
                showToast('Error', data.mensaje || 'No se pudo actualizar el análisis.', 'error');
            }
        } catch (error) {
            showToast('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });
</script>
</body>
</html>
