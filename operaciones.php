<?php
require_once __DIR__ . '/app/config/config.php';
auth(['admin']);

const OPERACION_SELECT = "SELECT o.id, o.nombre, o.creado_en,
    (SELECT COUNT(*) FROM usuarios u WHERE u.operacion_id = o.id) AS usuarios,
    (SELECT COUNT(*) FROM headcount h WHERE h.operacion_id = o.id) AS colaboradores,
    (SELECT COUNT(*) FROM reportes_novedades r WHERE r.operacion_id = o.id) AS novedades
    FROM operaciones o";

function obtener_operacion(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare(OPERACION_SELECT . " WHERE o.id = ?");
    $stmt->execute([$id]);
    $op = $stmt->fetch();
    if (!$op) {
        return null;
    }
    foreach (['id', 'usuarios', 'colaboradores', 'novedades'] as $campo) {
        $op[$campo] = (int) $op[$campo];
    }
    return $op;
}

function fallo(string $mensaje, int $status = 200): never
{
    responder_json(['success' => false, 'mensaje' => $mensaje], $status);
}

function texto_post(string $clave): string
{
    $valor = $_POST[$clave] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    limitar('operaciones:' . $_SESSION['usuario_id'], 30, 60);

    $accion = texto_post('accion');

    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $editando = $accion === 'editar';
            $id = (int) texto_post('id');
            $nombre = texto_post('nombre');

            if ($editando && !obtener_operacion($conn, $id)) {
                fallo('La operación no existe.');
            }
            if ($nombre === '') {
                fallo('El nombre de la operación es obligatorio.');
            }
            if (mb_strlen($nombre, 'UTF-8') > 100) {
                fallo('El nombre no puede superar los 100 caracteres.');
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM operaciones WHERE nombre = ? AND id <> ?");
            $stmt->execute([$nombre, $editando ? $id : 0]);
            if ((int) $stmt->fetchColumn() > 0) {
                fallo('Ya existe una operación con ese nombre. Use un nombre diferente.');
            }

            try {
                if ($editando) {
                    $conn->prepare("UPDATE operaciones SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);
                } else {
                    $conn->prepare("INSERT INTO operaciones (nombre) VALUES (?)")->execute([$nombre]);
                    $id = (int) $conn->lastInsertId();
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    fallo('Ya existe una operación con ese nombre. Use un nombre diferente.');
                }
                throw $e;
            }

            responder_json([
                'success' => true,
                'mensaje' => $editando ? 'Operación actualizada correctamente.' : 'Operación creada correctamente.',
                'operacion' => obtener_operacion($conn, $id),
            ]);
        }

        if ($accion === 'eliminar') {
            $id = (int) texto_post('id');
            $op = obtener_operacion($conn, $id);
            if (!$op) {
                fallo('La operación no existe.');
            }

            $bloqueos = [];
            if ($op['usuarios'] > 0) {
                $bloqueos[] = $op['usuarios'] . ($op['usuarios'] === 1 ? ' usuario' : ' usuarios');
            }
            if ($op['colaboradores'] > 0) {
                $bloqueos[] = $op['colaboradores'] . ($op['colaboradores'] === 1 ? ' colaborador en headcount' : ' colaboradores en headcount');
            }
            if ($op['novedades'] > 0) {
                $bloqueos[] = $op['novedades'] . ($op['novedades'] === 1 ? ' novedad' : ' novedades');
            }
            if ($bloqueos) {
                fallo('No se puede eliminar la operación porque tiene asociados: ' . implode(', ', $bloqueos) . '.');
            }

            $conn->prepare("DELETE FROM operaciones WHERE id = ?")->execute([$id]);
            responder_json(['success' => true, 'id' => $id, 'mensaje' => 'Operación eliminada correctamente.']);
        }

        fallo('Acción no válida.', 400);
    } catch (Throwable $e) {
        error_log('operaciones.php: ' . $e->getMessage());
        fallo('No se pudo completar la operación. Intente de nuevo.', 500);
    }
}

$fq = filtro_get('q');
$where = '';
$params = [];
if ($fq !== '') {
    $where = " WHERE o.nombre LIKE ?";
    $params[] = '%' . addcslashes($fq, '%_\\') . '%';
}
$stmt = $conn->prepare(OPERACION_SELECT . $where . " ORDER BY o.id DESC");
$stmt->execute($params);
$operaciones = $stmt->fetchAll();

$tituloPagina = 'Operaciones';
include __DIR__ . '/app/includes/header.php';
?>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Gestión de Operaciones</h1>
            <p>Administre las sedes, ciudades o áreas operativas del sistema.</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" id="btnNuevaOperacion">
                <i data-lucide="plus" style="width: 18px; height: 18px;"></i> Nueva operación
            </button>
        </div>
    </div>

    <form class="filters-bar" id="formFiltros" method="get" action="operaciones.php">
        <div class="filter-field grow">
            <label for="filtroQ">Buscar</label>
            <div class="search-input">
                <i data-lucide="search"></i>
                <input type="search" class="form-control" id="filtroQ" name="q" value="<?= e($fq) ?>" placeholder="Nombre de la operación" autocomplete="off">
            </div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-filtro-limpiar>
            <i data-lucide="x" style="width: 14px; height: 14px;"></i> Limpiar
        </button>
    </form>

    <div class="table-card" id="operacionesCard">
        <div class="table-scroll">
            <table class="custom-table" id="tablaOperaciones">
                <thead>
                    <tr>
                        <th>ID Ref.</th>
                        <th>Nombre</th>
                        <th style="text-align: center;">Usuarios</th>
                        <th style="text-align: center;">Colaboradores</th>
                        <th style="text-align: center;">Novedades</th>
                        <th>Fecha de creación</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operaciones as $op): ?>
                        <?php
                        $datos = [
                            'id' => (int) $op['id'],
                            'nombre' => $op['nombre'],
                            'usuarios' => (int) $op['usuarios'],
                            'colaboradores' => (int) $op['colaboradores'],
                            'novedades' => (int) $op['novedades'],
                            'creado_en' => $op['creado_en'],
                        ];
                        ?>
                        <tr data-id="<?= (int) $op['id'] ?>">
                            <td><span class="id-badge">#<?= e(str_pad((string) $op['id'], 4, '0', STR_PAD_LEFT)) ?></span></td>
                            <td class="nowrap"><strong><?= e($op['nombre']) ?></strong></td>
                            <td style="text-align: center;"><?= (int) $op['usuarios'] ?></td>
                            <td style="text-align: center;"><?= (int) $op['colaboradores'] ?></td>
                            <td style="text-align: center;"><?= (int) $op['novedades'] ?></td>
                            <td>
                                <span class="date-cell">
                                    <i data-lucide="calendar"></i>
                                    <?= e(date('d/m/Y', strtotime($op['creado_en']))) ?>
                                </span>
                            </td>
                            <td class="col-actions">
                                <div class="row-actions">
                                    <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-editar-operacion="<?= e(json_encode($datos, JSON_UNESCAPED_UNICODE)) ?>">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-eliminar-operacion="<?= e(json_encode($datos, JSON_UNESCAPED_UNICODE)) ?>">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($operaciones)): ?>
                        <tr data-no-paginate="true">
                            <td colspan="7">
                                <div class="empty-state">
                                    <i data-lucide="map-pin-off"></i>
                                    <h3>No se encontraron operaciones</h3>
                                    <p>Ajuste la búsqueda o cree una nueva operación.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-overlay" id="modalOperacion">
    <div class="modal-content" style="max-width: 480px;">
        <form id="formOperacion" autocomplete="off">
            <input type="hidden" name="accion" id="operacionAccion" value="crear">
            <input type="hidden" name="id" id="operacionId" value="">

            <div class="modal-header">
                <h2 id="operacionTitulo">Nueva operación</h2>
                <button type="button" class="close-modal" aria-label="Cerrar" onclick="toggleModal('modalOperacion', false)">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="operacionNombre">Nombre de la sede o ciudad</label>
                    <input type="text" name="nombre" id="operacionNombre" class="form-control" required maxlength="100" placeholder="Ej: Medellín, Bogotá Sur, Centro Logístico">
                </div>

                <div class="info-text">
                    <i data-lucide="info"></i>
                    <p>La operación estará disponible de inmediato al registrar o editar usuarios del sistema.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="toggleModal('modalOperacion', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarOperacion">Guardar operación</button>
            </div>
        </form>
    </div>
</div>

<script>
    const formOperacion = document.getElementById('formOperacion');

    function refrescarTabla() {
        lucide.createIcons();
        initPagination('tablaOperaciones', { perPage: 10 });
    }

    function abrirModalOperacion(op) {
        const editando = !!op;
        formOperacion.reset();
        document.getElementById('operacionAccion').value = editando ? 'editar' : 'crear';
        document.getElementById('operacionId').value = editando ? op.id : '';
        document.getElementById('operacionTitulo').textContent = editando ? 'Editar operación' : 'Nueva operación';
        document.getElementById('btnGuardarOperacion').textContent = editando ? 'Guardar cambios' : 'Guardar operación';
        if (editando) document.getElementById('operacionNombre').value = op.nombre;
        toggleModal('modalOperacion', true);
    }

    function filaOperacionHtml(op) {
        const fecha = new Date(String(op.creado_en).replace(' ', 'T')).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const datos = escapeHtml(JSON.stringify(op));
        return `
            <td><span class="id-badge">#${String(op.id).padStart(4, '0')}</span></td>
            <td class="nowrap"><strong>${escapeHtml(op.nombre)}</strong></td>
            <td style="text-align: center;">${Number(op.usuarios)}</td>
            <td style="text-align: center;">${Number(op.colaboradores)}</td>
            <td style="text-align: center;">${Number(op.novedades)}</td>
            <td><span class="date-cell"><i data-lucide="calendar"></i> ${escapeHtml(fecha)}</span></td>
            <td class="col-actions">
                <div class="row-actions">
                    <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-editar-operacion="${datos}"><i data-lucide="pencil"></i></button>
                    <button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-eliminar-operacion="${datos}"><i data-lucide="trash-2"></i></button>
                </div>
            </td>`;
    }

    function pintarFilaOperacion(op) {
        const tbody = document.querySelector('#tablaOperaciones tbody');
        const vacia = tbody.querySelector('tr[data-no-paginate]');
        if (vacia) vacia.remove();
        let tr = tbody.querySelector(`tr[data-id="${op.id}"]`);
        if (tr) {
            tr.innerHTML = filaOperacionHtml(op);
        } else {
            tr = document.createElement('tr');
            tr.dataset.id = op.id;
            tr.innerHTML = filaOperacionHtml(op);
            tbody.prepend(tr);
        }
        refrescarTabla();
    }

    function quitarFilaOperacion(id) {
        const tbody = document.querySelector('#tablaOperaciones tbody');
        const tr = tbody.querySelector(`tr[data-id="${id}"]`);
        if (tr) tr.remove();
        if (!tbody.querySelector('tr')) {
            tbody.innerHTML = '<tr data-no-paginate="true"><td colspan="7"><div class="empty-state"><i data-lucide="map-pin-off"></i><h3>No se encontraron operaciones</h3><p>Ajuste la búsqueda o cree una nueva operación.</p></div></td></tr>';
        }
        refrescarTabla();
    }

    lucide.createIcons();
    initPagination('tablaOperaciones', { perPage: 10 });
    initFiltros('formFiltros', { targets: ['#operacionesCard'], onUpdate: () => initPagination('tablaOperaciones', { perPage: 10 }) });

    document.getElementById('btnNuevaOperacion').addEventListener('click', () => abrirModalOperacion(null));

    document.addEventListener('click', async (ev) => {
        const btnEditar = ev.target.closest('[data-editar-operacion]');
        if (btnEditar) {
            abrirModalOperacion(JSON.parse(btnEditar.dataset.editarOperacion));
            return;
        }

        const btnEliminar = ev.target.closest('[data-eliminar-operacion]');
        if (!btnEliminar) return;
        const op = JSON.parse(btnEliminar.dataset.eliminarOperacion);

        const ok = await confirmar({
            titulo: 'Eliminar operación',
            mensaje: `Se eliminará la operación "${op.nombre}". Esta acción no se puede deshacer.`,
            textoBoton: 'Eliminar',
            peligro: true
        });
        if (!ok) return;

        const datos = new FormData();
        datos.append('accion', 'eliminar');
        datos.append('id', op.id);
        btnEliminar.disabled = true;
        const data = await enviarFormulario('operaciones.php', datos);
        btnEliminar.disabled = false;

        if (!data.success) {
            showToast('No se pudo eliminar', data.mensaje, 'error');
            return;
        }
        quitarFilaOperacion(data.id);
        showToast('Operación eliminada', data.mensaje, 'success');
    });

    formOperacion.addEventListener('submit', async (e) => {
        e.preventDefault();
        const boton = document.getElementById('btnGuardarOperacion');
        const editando = document.getElementById('operacionAccion').value === 'editar';

        setBotonCargando(boton, true, 'Guardando...');
        const data = await enviarFormulario('operaciones.php', new FormData(formOperacion));
        setBotonCargando(boton, false);

        if (!data.success) {
            showToast('Atención', data.mensaje, 'error');
            return;
        }

        toggleModal('modalOperacion', false);
        pintarFilaOperacion(data.operacion);
        showToast(editando ? 'Operación actualizada' : 'Operación creada', data.mensaje, 'success');
    });
</script>
</body>
</html>
