<?php
require_once __DIR__ . '/../app/config/config.php';
auth(['normal']);

$operacionId = (int) ($_SESSION['operacion_id'] ?? 0);
$q = filtro_get('q');
$headcount = [];

if ($operacionId > 0) {
    $sql = "SELECT id, nombre, cedula, cargo, fecha_creacion FROM headcount WHERE operacion_id = ?";
    $params = [$operacionId];
    if ($q !== '') {
        $sql .= " AND (nombre LIKE ? OR cedula LIKE ? OR cargo LIKE ?)";
        $like = '%' . addcslashes($q, '%_\\') . '%';
        array_push($params, $like, $like, $like);
    }
    $stmt = $conn->prepare($sql . " ORDER BY id DESC");
    $stmt->execute($params);
    $headcount = $stmt->fetchAll();
}

$tituloPagina = 'Headcount';
include __DIR__ . '/../app/includes/header.php';
?>

<style>
    .header-actions .btn svg,
    .modal-header .close-modal svg {
        width: 18px;
        height: 18px;
    }

    .help-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 0.85rem;
    }

    .help-table th, .help-table td {
        border: 1px solid var(--border);
        padding: 0.6rem 0.8rem;
        text-align: left;
    }

    .help-table th {
        background: var(--surface-alt);
        font-weight: 600;
        color: var(--text-main);
    }

    .help-table td {
        color: var(--text-muted);
    }

    .upload-drop {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        color: var(--text-muted);
        cursor: pointer;
        transition: var(--transition);
    }

    .upload-drop svg {
        width: 32px;
        height: 32px;
    }

    .upload-drop:hover {
        border-color: var(--primary);
        background: var(--surface-alt);
    }

    .upload-drop.has-file {
        border-color: var(--success);
        background: #f0fdf4;
        color: #15803d;
    }

    .errores-box {
        margin-top: 12px;
        max-height: 140px;
        overflow-y: auto;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.8rem;
        color: #92400e;
    }
</style>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Headcount</h1>
            <p>Gestione el personal (nombre, cédula y cargo) de su operación.</p>
        </div>

        <?php if ($operacionId > 0): ?>
            <div class="header-actions">
                <button type="button" class="btn btn-outline" id="btnAyuda">
                    <i data-lucide="help-circle"></i> Formato
                </button>
                <button type="button" class="btn btn-outline" id="btnExcel">
                    <i data-lucide="file-spreadsheet"></i> Cargar Excel
                </button>
                <button type="button" class="btn btn-primary" id="btnAgregar">
                    <i data-lucide="user-plus"></i> Agregar Colaborador
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($operacionId <= 0): ?>
        <div class="table-card">
            <div class="empty-state">
                <i data-lucide="alert-triangle"></i>
                <h3>Sin operación asignada</h3>
                <p>Su usuario no tiene una operación asignada.</p>
            </div>
        </div>
    <?php else: ?>
        <form class="filters-bar" id="formFiltros" method="get" action="headcount.php">
            <div class="filter-field grow">
                <label for="filtroQ">Buscar</label>
                <div class="search-input">
                    <i data-lucide="search"></i>
                    <input type="text" id="filtroQ" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Nombre, cédula o cargo" autocomplete="off">
                </div>
            </div>
            <div class="filter-field">
                <button type="button" class="btn btn-ghost btn-sm" data-filtro-limpiar>Limpiar</button>
            </div>
        </form>

        <div class="table-card" id="tableCardHeadcount">
            <div class="table-scroll">
                <table class="custom-table" id="tablaHeadcount">
                    <thead>
                        <tr>
                            <th class="nowrap">ID Ref.</th>
                            <th>Colaborador</th>
                            <th class="nowrap">Cédula</th>
                            <th>Cargo</th>
                            <th class="nowrap">Fecha de registro</th>
                            <th class="col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($headcount as $h): ?>
                            <tr data-id="<?= (int) $h['id'] ?>">
                                <td class="nowrap"><span class="id-badge">#<?= str_pad((string) $h['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                                <td>
                                    <div class="cell-person">
                                        <div class="avatar avatar-primary"><?= e(iniciales($h['nombre'])) ?></div>
                                        <strong><?= e($h['nombre']) ?></strong>
                                    </div>
                                </td>
                                <td class="nowrap mono"><?= e($h['cedula']) ?></td>
                                <td><span class="badge tone-emerald"><?= e($h['cargo']) ?></span></td>
                                <td class="nowrap"><span class="date-cell"><i data-lucide="calendar"></i><?= e(date('d/m/Y', strtotime($h['fecha_creacion']))) ?></span></td>
                                <td class="col-actions">
                                    <div class="row-actions">
                                        <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-accion="editar" data-colaborador="<?= e(json_encode(['id' => (int) $h['id'], 'nombre' => $h['nombre'], 'cedula' => $h['cedula'], 'cargo' => $h['cargo']], JSON_UNESCAPED_UNICODE)) ?>">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-accion="eliminar">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($headcount)): ?>
                            <tr data-no-paginate="true">
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i data-lucide="users-round"></i>
                                        <?php if ($q !== ''): ?>
                                            <h3>Sin resultados</h3>
                                            <p>Ningún colaborador coincide con la búsqueda.</p>
                                        <?php else: ?>
                                            <h3>No hay personal registrado</h3>
                                            <p>Agregue colaboradores manualmente o cargue un archivo Excel.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php if ($operacionId > 0): ?>
    <div class="modal-overlay" id="modalHeadcount">
        <div class="modal-content">
            <form id="formHeadcount">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="id" value="">
                <div class="modal-header">
                    <h2 id="tituloModalHeadcount">Agregar Colaborador</h2>
                    <button type="button" class="close-modal" data-cerrar="modalHeadcount" aria-label="Cerrar">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="hcNombre">Nombre completo</label>
                        <input type="text" id="hcNombre" name="nombre" class="form-control" required maxlength="150" placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hcCedula">Cédula</label>
                        <input type="text" id="hcCedula" name="cedula" class="form-control" required inputmode="numeric" pattern="\d{5,15}" maxlength="15" placeholder="Solo dígitos, sin puntos ni espacios">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hcCargo">Cargo</label>
                        <input type="text" id="hcCargo" name="cargo" class="form-control" required maxlength="150" placeholder="Ej: Auxiliar Logístico">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-cerrar="modalHeadcount">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarHeadcount">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalExcel">
        <div class="modal-content">
            <form id="formExcel" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="cargar_excel">
                <div class="modal-header">
                    <h2>Cargar Headcount desde Excel</h2>
                    <button type="button" class="close-modal" data-cerrar="modalExcel" aria-label="Cerrar">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <label class="upload-drop" id="dropZone" for="inputExcel">
                        <i data-lucide="upload-cloud"></i>
                        <div id="dropZoneText">Haga clic para seleccionar un archivo .xlsx</div>
                        <input type="file" id="inputExcel" name="archivo_excel" accept=".xlsx,.xls" required hidden>
                    </label>

                    <div class="info-text">
                        <i data-lucide="info"></i>
                        <p>Máximo 5 MB y 5000 filas. ¿No conoce el formato requerido? Use el botón <strong>Formato</strong> para ver un ejemplo.</p>
                    </div>

                    <div id="erroresExcel" class="errores-box" hidden></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-cerrar="modalExcel">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnCargarExcel">Cargar archivo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalAyuda">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Formato del archivo Excel</h2>
                <button type="button" class="close-modal" data-cerrar="modalAyuda" aria-label="Cerrar">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="form-hint">
                    El archivo debe ser <strong>.xlsx</strong> y tener exactamente 3 columnas, en este orden, con la fila 1 como encabezado. Los datos van a partir de la fila 2.
                </p>
                <table class="help-table">
                    <thead>
                        <tr>
                            <th>Columna A</th>
                            <th>Columna B</th>
                            <th>Columna C</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Nombre</strong></td>
                            <td><strong>Cedula</strong></td>
                            <td><strong>Cargo</strong></td>
                        </tr>
                        <tr>
                            <td>Juan Pérez</td>
                            <td>1020304050</td>
                            <td>Auxiliar Logístico</td>
                        </tr>
                        <tr>
                            <td>María Gómez</td>
                            <td>1030405060</td>
                            <td>Supervisor de Turno</td>
                        </tr>
                    </tbody>
                </table>
                <div class="info-text">
                    <i data-lucide="lightbulb"></i>
                    <p>La cédula debe tener solo dígitos (5 a 15). Si una cédula ya existe en el headcount de su operación, se actualizarán el nombre y el cargo en lugar de duplicar el registro.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-cerrar="modalAyuda">Entendido</button>
            </div>
        </div>
    </div>

    <script>
        const POR_PAGINA = 10;
        const formHeadcount = document.getElementById('formHeadcount');
        const formExcel = document.getElementById('formExcel');
        const inputExcel = document.getElementById('inputExcel');
        const dropZone = document.getElementById('dropZone');
        const dropZoneText = document.getElementById('dropZoneText');
        const erroresBox = document.getElementById('erroresExcel');
        const TEXTO_DROP = 'Haga clic para seleccionar un archivo .xlsx';

        const refrescarTabla = initFiltros('formFiltros', {
            targets: ['#tableCardHeadcount'],
            onUpdate: () => initPagination('tablaHeadcount', { perPage: POR_PAGINA })
        });

        lucide.createIcons();
        initPagination('tablaHeadcount', { perPage: POR_PAGINA });

        document.querySelectorAll('[data-cerrar]').forEach(btn => {
            btn.addEventListener('click', () => toggleModal(btn.dataset.cerrar, false));
        });

        function abrirModalHeadcount(colaborador) {
            formHeadcount.reset();
            const editando = !!colaborador;
            formHeadcount.elements.accion.value = editando ? 'editar' : 'agregar';
            formHeadcount.elements.id.value = editando ? colaborador.id : '';
            if (editando) {
                formHeadcount.elements.nombre.value = colaborador.nombre;
                formHeadcount.elements.cedula.value = colaborador.cedula;
                formHeadcount.elements.cargo.value = colaborador.cargo;
            }
            document.getElementById('tituloModalHeadcount').textContent = editando ? 'Editar Colaborador' : 'Agregar Colaborador';
            toggleModal('modalHeadcount', true);
        }

        document.getElementById('btnAgregar').addEventListener('click', () => abrirModalHeadcount(null));
        document.getElementById('btnExcel').addEventListener('click', () => toggleModal('modalExcel', true));
        document.getElementById('btnAyuda').addEventListener('click', () => toggleModal('modalAyuda', true));

        function formatoFecha(texto) {
            const fecha = new Date(String(texto).replace(' ', 'T'));
            return isNaN(fecha) ? '' : fecha.toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }

        function construirFila(h) {
            const tr = document.createElement('tr');
            tr.dataset.id = h.id;
            tr.innerHTML = `
                <td class="nowrap"><span class="id-badge">#${escapeHtml(String(h.id).padStart(4, '0'))}</span></td>
                <td>
                    <div class="cell-person">
                        <div class="avatar avatar-primary">${escapeHtml(String(h.nombre).trim().substring(0, 2).toUpperCase())}</div>
                        <strong>${escapeHtml(h.nombre)}</strong>
                    </div>
                </td>
                <td class="nowrap mono">${escapeHtml(h.cedula)}</td>
                <td><span class="badge tone-emerald">${escapeHtml(h.cargo)}</span></td>
                <td class="nowrap"><span class="date-cell"><i data-lucide="calendar"></i>${escapeHtml(formatoFecha(h.fecha_creacion))}</span></td>
                <td class="col-actions">
                    <div class="row-actions">
                        <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-accion="editar"><i data-lucide="pencil"></i></button>
                        <button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-accion="eliminar"><i data-lucide="trash-2"></i></button>
                    </div>
                </td>`;
            tr.querySelector('[data-accion="editar"]').dataset.colaborador = JSON.stringify({ id: h.id, nombre: h.nombre, cedula: h.cedula, cargo: h.cargo });
            return tr;
        }

        function repintarTabla() {
            lucide.createIcons();
            initPagination('tablaHeadcount', { perPage: POR_PAGINA });
        }

        document.addEventListener('click', async (e) => {
            const boton = e.target.closest('#tablaHeadcount [data-accion]');
            if (!boton) return;
            const fila = boton.closest('tr[data-id]');
            if (!fila) return;

            if (boton.dataset.accion === 'editar') {
                abrirModalHeadcount(JSON.parse(boton.dataset.colaborador));
                return;
            }

            const nombre = fila.querySelector('.cell-person strong').textContent;
            const ok = await confirmar({
                titulo: 'Eliminar colaborador',
                mensaje: `¿Eliminar a "${nombre}" del headcount? Esta acción no se puede deshacer.`,
                textoBoton: 'Eliminar',
                peligro: true
            });
            if (!ok) return;

            const datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', fila.dataset.id);
            const data = await enviarFormulario('api/procesar_headcount.php', datos);

            if (!data.success) {
                showToast('Atención', data.mensaje, 'error');
                return;
            }
            showToast('Eliminado', data.mensaje, 'success');
            fila.remove();
            if (!document.querySelector('#tablaHeadcount tbody tr[data-id]')) {
                refrescarTabla();
            } else {
                repintarTabla();
            }
        });

        formHeadcount.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarHeadcount');
            const editando = formHeadcount.elements.accion.value === 'editar';
            setBotonCargando(btn, true, 'Guardando...');
            const data = await enviarFormulario('api/procesar_headcount.php', new FormData(formHeadcount));
            setBotonCargando(btn, false);

            if (!data.success) {
                showToast('Atención', data.mensaje, 'error');
                return;
            }

            toggleModal('modalHeadcount', false);
            showToast(editando ? 'Colaborador actualizado' : 'Colaborador agregado', data.mensaje, 'success');

            const nuevaFila = construirFila(data.colaborador);
            if (editando) {
                const actual = document.querySelector(`#tablaHeadcount tr[data-id="${CSS.escape(String(data.colaborador.id))}"]`);
                if (actual) actual.replaceWith(nuevaFila);
            } else {
                const vacia = document.querySelector('#tablaHeadcount tbody tr[data-no-paginate]');
                if (vacia) vacia.remove();
                document.querySelector('#tablaHeadcount tbody').prepend(nuevaFila);
            }
            repintarTabla();
        });

        inputExcel.addEventListener('change', () => {
            const tiene = inputExcel.files.length > 0;
            dropZone.classList.toggle('has-file', tiene);
            dropZoneText.textContent = tiene ? inputExcel.files[0].name : TEXTO_DROP;
        });

        formExcel.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnCargarExcel');
            erroresBox.hidden = true;
            erroresBox.replaceChildren();
            setBotonCargando(btn, true, 'Procesando...');
            const data = await enviarFormulario('api/procesar_headcount.php', new FormData(formExcel));
            setBotonCargando(btn, false);

            if (!data.success) {
                showToast('Atención', data.mensaje, 'error');
                return;
            }

            showToast('Carga exitosa', data.mensaje, 'success');
            if (data.errores && data.errores.length > 0) {
                const titulo = document.createElement('strong');
                titulo.textContent = 'Filas con problemas:';
                erroresBox.appendChild(titulo);
                data.errores.forEach(texto => {
                    const linea = document.createElement('div');
                    linea.textContent = texto;
                    erroresBox.appendChild(linea);
                });
                erroresBox.hidden = false;
            } else {
                toggleModal('modalExcel', false);
            }
            formExcel.reset();
            dropZone.classList.remove('has-file');
            dropZoneText.textContent = TEXTO_DROP;
            refrescarTabla();
        });
    </script>
<?php endif; ?>
</body>
</html>
