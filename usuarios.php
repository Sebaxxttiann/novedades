<?php
require_once __DIR__ . '/app/config/config.php';
auth(['admin']);

const USUARIO_COLUMNAS = "u.id, u.nombre, u.email, u.rol, u.operacion_id, u.estado, o.nombre AS operacion";

function obtener_usuario(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT " . USUARIO_COLUMNAS . " FROM usuarios u LEFT JOIN operaciones o ON o.id = u.operacion_id WHERE u.id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) {
        return null;
    }
    $u['id'] = (int) $u['id'];
    $u['operacion_id'] = $u['operacion_id'] !== null ? (int) $u['operacion_id'] : null;
    $u['estado'] = (int) $u['estado'];
    return $u;
}

function otros_admins_activos(PDO $conn, int $excluirId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND estado = 1 AND id <> ?");
    $stmt->execute([$excluirId]);
    return (int) $stmt->fetchColumn();
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
    limitar('usuarios:' . $_SESSION['usuario_id'], 30, 60);

    $accion = texto_post('accion');
    $miId = (int) $_SESSION['usuario_id'];

    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $editando = $accion === 'editar';
            $id = (int) texto_post('id');
            $actual = null;

            if ($editando) {
                $actual = obtener_usuario($conn, $id);
                if (!$actual) {
                    fallo('El usuario no existe.');
                }
            }

            $nombre = texto_post('nombre');
            $email = mb_strtolower(texto_post('email'), 'UTF-8');
            $password = $_POST['password'] ?? '';
            $password = is_string($password) ? $password : '';
            $rol = texto_post('rol');
            $operacionId = (int) texto_post('operacion_id');
            $estado = $editando ? (texto_post('estado') === '1' ? 1 : 0) : 1;

            if ($nombre === '') {
                fallo('El nombre es obligatorio.');
            }
            if (mb_strlen($nombre, 'UTF-8') > 150) {
                fallo('El nombre no puede superar los 150 caracteres.');
            }
            if ($email === '' || mb_strlen($email, 'UTF-8') > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                fallo('Ingrese un correo electrónico válido.');
            }
            if (!array_key_exists($rol, ROLES)) {
                fallo('Seleccione un rol válido.');
            }

            if ($rol === 'normal') {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM operaciones WHERE id = ?");
                $stmt->execute([$operacionId]);
                if ($operacionId <= 0 || (int) $stmt->fetchColumn() === 0) {
                    fallo('Seleccione una operación válida para el rol Operaciones.');
                }
            } else {
                $operacionId = null;
            }

            if (!$editando || $password !== '') {
                if (mb_strlen($password, 'UTF-8') < 8) {
                    fallo('La contraseña debe tener mínimo 8 caracteres.');
                }
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id <> ?");
            $stmt->execute([$email, $editando ? $id : 0]);
            if ((int) $stmt->fetchColumn() > 0) {
                fallo('Ya existe un usuario con ese correo electrónico.');
            }

            if ($editando) {
                if ($id === $miId) {
                    if ($rol !== 'admin') {
                        fallo('No puede quitarse a sí mismo el rol de administrador.');
                    }
                    if ($estado !== 1) {
                        fallo('No puede desactivar su propia cuenta.');
                    }
                }
                $eraAdminActivo = $actual['rol'] === 'admin' && $actual['estado'] === 1;
                $seguiraAdminActivo = $rol === 'admin' && $estado === 1;
                if ($eraAdminActivo && !$seguiraAdminActivo && otros_admins_activos($conn, $id) === 0) {
                    fallo('Debe existir al menos un administrador activo en el sistema.');
                }

                if ($password !== '') {
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ?, operacion_id = ?, estado = ? WHERE id = ?");
                    $stmt->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT), $rol, $operacionId, $estado, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, operacion_id = ?, estado = ? WHERE id = ?");
                    $stmt->execute([$nombre, $email, $rol, $operacionId, $estado, $id]);
                }
                $mensaje = 'Usuario actualizado correctamente.';
            } else {
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, operacion_id, estado) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT), $rol, $operacionId]);
                $id = (int) $conn->lastInsertId();
                $mensaje = 'Usuario creado correctamente.';
            }

            responder_json(['success' => true, 'mensaje' => $mensaje, 'usuario' => obtener_usuario($conn, $id)]);
        }

        if ($accion === 'eliminar') {
            $id = (int) texto_post('id');
            $usuario = obtener_usuario($conn, $id);
            if (!$usuario) {
                fallo('El usuario no existe.');
            }
            if ($id === $miId) {
                fallo('No puede eliminar su propia cuenta.');
            }
            if ($usuario['rol'] === 'admin' && $usuario['estado'] === 1 && otros_admins_activos($conn, $id) === 0) {
                fallo('Debe existir al menos un administrador activo en el sistema.');
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM reportes_novedades WHERE creador_id = ?");
            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {
                $conn->prepare("UPDATE usuarios SET estado = 0 WHERE id = ?")->execute([$id]);
                responder_json([
                    'success' => true,
                    'desactivado' => true,
                    'mensaje' => 'El usuario tiene novedades registradas, por lo que se desactivó para conservar el historial.',
                    'usuario' => obtener_usuario($conn, $id),
                ]);
            }

            $conn->beginTransaction();
            $conn->prepare("UPDATE headcount SET creado_por = NULL WHERE creado_por = ?")->execute([$id]);
            $conn->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
            $conn->commit();

            responder_json(['success' => true, 'desactivado' => false, 'id' => $id, 'mensaje' => 'Usuario eliminado correctamente.']);
        }

        fallo('Acción no válida.', 400);
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('usuarios.php: ' . $e->getMessage());
        fallo('No se pudo completar la operación. Intente de nuevo.', 500);
    }
}

$fq = filtro_get('q');
$frol = filtro_get('rol', array_keys(ROLES));
$fop = filtro_get_int('operacion');
$festado = filtro_get('estado', ['activo', 'inactivo']);

$where = [];
$params = [];
if ($fq !== '') {
    $like = '%' . addcslashes($fq, '%_\\') . '%';
    $where[] = "(u.nombre LIKE ? OR u.email LIKE ?)";
    array_push($params, $like, $like);
}
if ($frol !== '') {
    $where[] = "u.rol = ?";
    $params[] = $frol;
}
if ($fop > 0) {
    $where[] = "u.operacion_id = ?";
    $params[] = $fop;
}
if ($festado !== '') {
    $where[] = "u.estado = ?";
    $params[] = $festado === 'activo' ? 1 : 0;
}

$sql = "SELECT " . USUARIO_COLUMNAS . " FROM usuarios u LEFT JOIN operaciones o ON o.id = u.operacion_id"
    . ($where ? " WHERE " . implode(' AND ', $where) : "") . " ORDER BY u.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$operaciones = $conn->query("SELECT id, nombre FROM operaciones ORDER BY nombre ASC")->fetchAll();
$miIdActual = (int) $_SESSION['usuario_id'];
$tonosRol = ['admin' => 'blue', 'juridico' => 'purple', 'normal' => 'slate'];

$tituloPagina = 'Usuarios';
include __DIR__ . '/app/includes/header.php';
?>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Gestión de Usuarios</h1>
            <p>Administre el acceso, los perfiles y la operación asignada a cada funcionario.</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" id="btnNuevoUsuario">
                <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i> Crear usuario
            </button>
        </div>
    </div>

    <form class="filters-bar" id="formFiltros" method="get" action="usuarios.php">
        <div class="filter-field grow">
            <label for="filtroQ">Buscar</label>
            <div class="search-input">
                <i data-lucide="search"></i>
                <input type="search" class="form-control" id="filtroQ" name="q" value="<?= e($fq) ?>" placeholder="Nombre o correo" autocomplete="off">
            </div>
        </div>
        <div class="filter-field">
            <label for="filtroRol">Rol</label>
            <select class="form-control" id="filtroRol" name="rol">
                <option value="">Todos</option>
                <?php foreach (ROLES as $clave => $etiqueta): ?>
                    <option value="<?= e($clave) ?>" <?= $frol === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label for="filtroOperacion">Operación</label>
            <select class="form-control" id="filtroOperacion" name="operacion">
                <option value="">Todas</option>
                <?php foreach ($operaciones as $op): ?>
                    <option value="<?= (int) $op['id'] ?>" <?= $fop === (int) $op['id'] ? 'selected' : '' ?>><?= e($op['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label for="filtroEstado">Estado</label>
            <select class="form-control" id="filtroEstado" name="estado">
                <option value="">Todos</option>
                <option value="activo" <?= $festado === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= $festado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" data-filtro-limpiar>
            <i data-lucide="x" style="width: 14px; height: 14px;"></i> Limpiar
        </button>
    </form>

    <div class="table-card" id="usuariosCard">
        <div class="table-scroll">
            <table class="custom-table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Operación</th>
                        <th>Estado</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $datos = [
                            'id' => (int) $u['id'],
                            'nombre' => $u['nombre'],
                            'email' => $u['email'],
                            'rol' => $u['rol'],
                            'operacion_id' => $u['operacion_id'] !== null ? (int) $u['operacion_id'] : null,
                            'operacion' => $u['operacion'],
                            'estado' => (int) $u['estado'],
                        ];
                        ?>
                        <tr data-id="<?= (int) $u['id'] ?>">
                            <td>
                                <div class="cell-person">
                                    <div class="avatar avatar-lg avatar-primary"><?= e(iniciales($u['nombre'])) ?></div>
                                    <div>
                                        <strong><?= e($u['nombre']) ?></strong>
                                        <span><?= e($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge tone-<?= e($tonosRol[$u['rol']] ?? 'slate') ?>"><?= e(ROLES[$u['rol']] ?? $u['rol']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['operacion']): ?>
                                    <?= e($u['operacion']) ?>
                                <?php else: ?>
                                    <span class="muted">Global</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $u['estado'] === 1): ?>
                                    <span class="badge tone-emerald">Activo</span>
                                <?php else: ?>
                                    <span class="badge tone-slate">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-actions">
                                <div class="row-actions">
                                    <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-editar-usuario="<?= e(json_encode($datos, JSON_UNESCAPED_UNICODE)) ?>">
                                        <i data-lucide="pencil"></i>
                                    </button>
                                    <?php if ((int) $u['id'] !== $miIdActual): ?>
                                        <button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-eliminar-usuario="<?= e(json_encode($datos, JSON_UNESCAPED_UNICODE)) ?>">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($usuarios)): ?>
                        <tr data-no-paginate="true">
                            <td colspan="5">
                                <div class="empty-state">
                                    <i data-lucide="users"></i>
                                    <h3>No se encontraron usuarios</h3>
                                    <p>Ajuste los filtros o cree un nuevo usuario.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal-overlay" id="modalUsuario">
    <div class="modal-content">
        <form id="formUsuario" autocomplete="off">
            <input type="hidden" name="accion" id="usuarioAccion" value="crear">
            <input type="hidden" name="id" id="usuarioId" value="">

            <div class="modal-header">
                <h2 id="usuarioTitulo">Nuevo usuario</h2>
                <button type="button" class="close-modal" aria-label="Cerrar" onclick="toggleModal('modalUsuario', false)">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="usuarioNombre">Nombre completo</label>
                    <input type="text" name="nombre" id="usuarioNombre" class="form-control" required maxlength="150" placeholder="Ej. Carlos Mendoza">
                </div>

                <div class="form-group">
                    <label class="form-label" for="usuarioEmail">Correo electrónico</label>
                    <input type="email" name="email" id="usuarioEmail" class="form-control" required maxlength="150" placeholder="carlos@empresa.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="usuarioPassword">Contraseña</label>
                    <div class="input-action">
                        <input type="password" name="password" id="usuarioPassword" class="form-control" minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                        <div class="input-buttons">
                            <button type="button" class="btn-icon" title="Mostrar u ocultar" aria-label="Mostrar u ocultar contraseña" id="btnVerPassword" onclick="togglePassword('usuarioPassword', this)">
                                <i data-lucide="eye"></i>
                            </button>
                            <button type="button" class="btn-icon" title="Generar contraseña" aria-label="Generar contraseña" id="btnGenerarPassword">
                                <i data-lucide="wand-2"></i>
                            </button>
                            <button type="button" class="btn-icon" title="Copiar contraseña" aria-label="Copiar contraseña" id="btnCopiarPassword">
                                <i data-lucide="copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-hint" id="passwordAyudaEditar" style="display: none;">Deje el campo vacío para conservar la contraseña actual.</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="selectRol">Rol en el sistema</label>
                        <select name="rol" id="selectRol" class="form-control" required>
                            <?php foreach (ROLES as $clave => $etiqueta): ?>
                                <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="selectOperacion">Operación</label>
                        <select name="operacion_id" id="selectOperacion" class="form-control">
                            <option value="">Seleccione una operación</option>
                            <?php foreach ($operaciones as $op): ?>
                                <option value="<?= (int) $op['id'] ?>"><?= e($op['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="grupoEstado" style="display: none;">
                    <label class="form-label" for="selectEstado">Estado</label>
                    <select name="estado" id="selectEstado" class="form-control">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="info-text" id="infoPasswordEditar" style="display: none;">
                    <i data-lucide="info"></i>
                    <p>Por seguridad las contraseñas se guardan cifradas y no se pueden ver. Para recordársela al usuario, genere una nueva, cópiela y compártala.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="toggleModal('modalUsuario', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarUsuario">Guardar usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
    const ROLES_UI = <?= json_encode(ROLES, JSON_UNESCAPED_UNICODE) ?>;
    const TONOS_ROL = { admin: 'blue', juridico: 'purple', normal: 'slate' };
    const MI_ID = <?= $miIdActual ?>;

    const formUsuario = document.getElementById('formUsuario');
    const selectRol = document.getElementById('selectRol');
    const selectOperacion = document.getElementById('selectOperacion');
    const inputPassword = document.getElementById('usuarioPassword');
    const btnVerPassword = document.getElementById('btnVerPassword');

    function refrescarTabla() {
        lucide.createIcons();
        initPagination('tablaUsuarios', { perPage: 10 });
    }

    function sincronizarOperacion() {
        const requiere = selectRol.value === 'normal';
        selectOperacion.disabled = !requiere;
        selectOperacion.required = requiere;
        if (!requiere) selectOperacion.value = '';
    }

    function restablecerVisibilidadPassword() {
        inputPassword.type = 'password';
        btnVerPassword.innerHTML = '<i data-lucide="eye"></i>';
        lucide.createIcons({ nodes: [btnVerPassword] });
    }

    function abrirModalUsuario(usuario) {
        const editando = !!usuario;
        formUsuario.reset();
        document.getElementById('usuarioAccion').value = editando ? 'editar' : 'crear';
        document.getElementById('usuarioId').value = editando ? usuario.id : '';
        document.getElementById('usuarioTitulo').textContent = editando ? 'Editar usuario' : 'Nuevo usuario';
        document.getElementById('btnGuardarUsuario').textContent = editando ? 'Guardar cambios' : 'Guardar usuario';
        document.getElementById('grupoEstado').style.display = editando ? '' : 'none';
        document.getElementById('passwordAyudaEditar').style.display = editando ? '' : 'none';
        document.getElementById('infoPasswordEditar').style.display = editando ? '' : 'none';
        inputPassword.required = !editando;
        inputPassword.placeholder = editando ? 'Nueva contraseña (opcional)' : 'Mínimo 8 caracteres';

        if (editando) {
            document.getElementById('usuarioNombre').value = usuario.nombre;
            document.getElementById('usuarioEmail').value = usuario.email;
            selectRol.value = usuario.rol;
            selectOperacion.value = usuario.operacion_id ?? '';
            document.getElementById('selectEstado').value = String(usuario.estado);
            const esYo = usuario.id === MI_ID;
            selectRol.disabled = esYo;
            document.getElementById('selectEstado').disabled = esYo;
        } else {
            selectRol.disabled = false;
            document.getElementById('selectEstado').disabled = false;
        }

        restablecerVisibilidadPassword();
        sincronizarOperacion();
        toggleModal('modalUsuario', true);
    }

    function filaUsuarioHtml(u) {
        const tono = TONOS_ROL[u.rol] || 'slate';
        const rolTexto = ROLES_UI[u.rol] || u.rol;
        const iniciales = Array.from((u.nombre || '').trim()).slice(0, 2).join('').toUpperCase();
        const operacion = u.operacion ? escapeHtml(u.operacion) : '<span class="muted">Global</span>';
        const estado = Number(u.estado) === 1 ? '<span class="badge tone-emerald">Activo</span>' : '<span class="badge tone-slate">Inactivo</span>';
        const datos = escapeHtml(JSON.stringify(u));
        const eliminar = u.id !== MI_ID
            ? `<button type="button" class="btn-icon danger" title="Eliminar" aria-label="Eliminar" data-eliminar-usuario="${datos}"><i data-lucide="trash-2"></i></button>`
            : '';
        return `
            <td>
                <div class="cell-person">
                    <div class="avatar avatar-lg avatar-primary">${escapeHtml(iniciales)}</div>
                    <div>
                        <strong>${escapeHtml(u.nombre)}</strong>
                        <span>${escapeHtml(u.email)}</span>
                    </div>
                </div>
            </td>
            <td><span class="badge tone-${tono}">${escapeHtml(rolTexto)}</span></td>
            <td>${operacion}</td>
            <td>${estado}</td>
            <td class="col-actions">
                <div class="row-actions">
                    <button type="button" class="btn-icon" title="Editar" aria-label="Editar" data-editar-usuario="${datos}"><i data-lucide="pencil"></i></button>
                    ${eliminar}
                </div>
            </td>`;
    }

    function pintarFilaUsuario(u) {
        const tbody = document.querySelector('#tablaUsuarios tbody');
        if (!tbody) return;
        const vacia = tbody.querySelector('tr[data-no-paginate]');
        if (vacia) vacia.remove();
        let tr = tbody.querySelector(`tr[data-id="${u.id}"]`);
        if (tr) {
            tr.innerHTML = filaUsuarioHtml(u);
        } else {
            tr = document.createElement('tr');
            tr.dataset.id = u.id;
            tr.innerHTML = filaUsuarioHtml(u);
            tbody.prepend(tr);
        }
        refrescarTabla();
    }

    function quitarFilaUsuario(id) {
        const tbody = document.querySelector('#tablaUsuarios tbody');
        const tr = tbody.querySelector(`tr[data-id="${id}"]`);
        if (tr) tr.remove();
        if (!tbody.querySelector('tr')) {
            tbody.innerHTML = '<tr data-no-paginate="true"><td colspan="5"><div class="empty-state"><i data-lucide="users"></i><h3>No se encontraron usuarios</h3><p>Ajuste los filtros o cree un nuevo usuario.</p></div></td></tr>';
        }
        refrescarTabla();
    }

    lucide.createIcons();
    initPagination('tablaUsuarios', { perPage: 10 });
    initFiltros('formFiltros', { targets: ['#usuariosCard'], onUpdate: () => initPagination('tablaUsuarios', { perPage: 10 }) });

    document.getElementById('btnNuevoUsuario').addEventListener('click', () => abrirModalUsuario(null));
    selectRol.addEventListener('change', sincronizarOperacion);

    document.getElementById('btnGenerarPassword').addEventListener('click', () => {
        inputPassword.value = generarPassword(12);
        inputPassword.type = 'text';
        btnVerPassword.innerHTML = '<i data-lucide="eye-off"></i>';
        lucide.createIcons({ nodes: [btnVerPassword] });
    });

    document.getElementById('btnCopiarPassword').addEventListener('click', () => {
        if (!inputPassword.value) {
            showToast('Atención', 'No hay una contraseña para copiar.', 'warning');
            return;
        }
        copiarTexto(inputPassword.value);
    });

    document.addEventListener('click', async (ev) => {
        const btnEditar = ev.target.closest('[data-editar-usuario]');
        if (btnEditar) {
            abrirModalUsuario(JSON.parse(btnEditar.dataset.editarUsuario));
            return;
        }

        const btnEliminar = ev.target.closest('[data-eliminar-usuario]');
        if (!btnEliminar) return;
        const usuario = JSON.parse(btnEliminar.dataset.eliminarUsuario);

        const ok = await confirmar({
            titulo: 'Eliminar usuario',
            mensaje: `Se eliminará a ${usuario.nombre}. Si tiene novedades registradas, solo se desactivará para conservar el historial.`,
            textoBoton: 'Eliminar',
            peligro: true
        });
        if (!ok) return;

        const datos = new FormData();
        datos.append('accion', 'eliminar');
        datos.append('id', usuario.id);
        btnEliminar.disabled = true;
        const data = await enviarFormulario('usuarios.php', datos);
        btnEliminar.disabled = false;

        if (!data.success) {
            showToast('Atención', data.mensaje, 'error');
            return;
        }
        if (data.desactivado) {
            pintarFilaUsuario(data.usuario);
            showToast('Usuario desactivado', data.mensaje, 'warning');
        } else {
            quitarFilaUsuario(data.id);
            showToast('Usuario eliminado', data.mensaje, 'success');
        }
    });

    formUsuario.addEventListener('submit', async (e) => {
        e.preventDefault();
        const boton = document.getElementById('btnGuardarUsuario');
        const datos = new FormData(formUsuario);
        const conPassword = inputPassword.value !== '';
        const editando = datos.get('accion') === 'editar';

        if (selectRol.disabled) datos.set('rol', selectRol.value);
        if (document.getElementById('selectEstado').disabled) datos.set('estado', document.getElementById('selectEstado').value);

        setBotonCargando(boton, true, 'Guardando...');
        const data = await enviarFormulario('usuarios.php', datos);
        setBotonCargando(boton, false);

        if (!data.success) {
            showToast('Atención', data.mensaje, 'error');
            return;
        }

        toggleModal('modalUsuario', false);
        pintarFilaUsuario(data.usuario);
        const aviso = conPassword ? ' Recuerde copiar la contraseña y compartirla con el usuario.' : '';
        showToast(editando ? 'Usuario actualizado' : 'Usuario creado', data.mensaje + aviso, 'success');
    });
</script>
</body>
</html>
