<?php
require_once 'config/config.php';
auth();
if($_SESSION['usuario_rol'] !== 'admin') { header("Location: dashboard.php"); exit; }

$operaciones = $conn->query("SELECT * FROM operaciones ORDER BY nombre ASC")->fetchAll();
$usuarios = $conn->query("SELECT u.*, o.nombre as operacion FROM usuarios u LEFT JOIN operaciones o ON u.operacion_id = o.id ORDER BY u.id DESC")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $operacion_id = empty($_POST['operacion_id']) ? null : $_POST['operacion_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol, operacion_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $rol, $operacion_id]);
        echo json_encode(['success' => true, 'mensaje' => 'Usuario creado correctamente']);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'El correo ya existe o hubo un error al guardar']);
    }
    exit;
}

include 'header.php';
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
        font-size: 0.9rem;
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
        color: var(--primary);
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

    .badge-admin { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-juridico { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
    .badge-normal { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

    .tag-operacion {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: var(--text-main);
        font-weight: 500;
    }

    .tag-global {
        color: var(--text-muted);
        font-style: italic;
    }
</style>

<div class="container">
    <!-- Header de Sección -->
    <div class="page-header">
        <div class="page-title">
            <h1>Gestión de Usuarios</h1>
            <p>Administre el acceso, perfiles e itinerario operativo de los funcionarios.</p>
        </div>
        <button class="btn btn-primary" onclick="toggleModal('modalUsuario', true)">
            <i data-lucide="user-plus" style="width: 18px;"></i> Crear Usuario
        </button>
    </div>

    <!-- Tabla Principal -->
    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol de Sistema</th>
                    <th>Operación Asignada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): 
                    $iniciales = strtoupper(substr($u['nombre'], 0, 2));
                    
                    // Clase CSS según el rol
                    $badgeClass = 'badge-normal';
                    if ($u['rol'] === 'admin') $badgeClass = 'badge-admin';
                    if ($u['rol'] === 'juridico') $badgeClass = 'badge-juridico';
                ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar-circle"><?= $iniciales ?></div>
                            <div class="user-details">
                                <strong><?= htmlspecialchars($u['nombre']) ?></strong>
                                <span><?= htmlspecialchars($u['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <i data-lucide="shield" style="width: 12px; height: 12px;"></i>
                            <?= $u['rol'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['operacion']): ?>
                            <span class="tag-operacion">
                                <i data-lucide="map-pin" style="width: 15px; color: var(--primary);"></i>
                                <?= htmlspecialchars($u['operacion']) ?>
                            </span>
                        <?php else: ?>
                            <span class="tag-global">Global (Todas las áreas)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalUsuario">
    <div class="modal-content">
        <form id="formUsuario">
            <div class="modal-header">
                <h2>Nuevo Usuario</h2>
                <button type="button" class="close-modal" onclick="toggleModal('modalUsuario', false)">
                    <i data-lucide="x" style="width: 20px;"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej. Carlos Mendoza">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="carlos@empresa.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña de Acceso</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem;">
                    <div class="form-group">
                        <label class="form-label">Rol en el Sistema</label>
                        <select name="rol" id="selectRol" class="form-control" required>
                            <option value="normal">Normal (Operaciones)</option>
                            <option value="juridico">Jurídico</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Asignar Operación</label>
                        <select name="operacion_id" id="selectOperacion" class="form-control">
                            <option value="">Ninguna (Global)</option>
                            <?php foreach($operaciones as $op): ?>
                                <option value="<?= $op['id'] ?>"><?= htmlspecialchars($op['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="toggleModal('modalUsuario', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardar">
                    <span id="txtGuardar">Guardar Usuario</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    const selectRol = document.getElementById('selectRol');
    const selectOperacion = document.getElementById('selectOperacion');

    selectRol.addEventListener('change', (e) => {
        if(e.target.value === 'admin') {
            selectOperacion.value = "";
            selectOperacion.disabled = true;
        } else {
            selectOperacion.disabled = false;
        }
    });

    document.getElementById('formUsuario').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardar');
        const txtGuardar = document.getElementById('txtGuardar');
        
        btnGuardar.disabled = true;
        txtGuardar.textContent = 'Guardando...';

        try {
            const res = await fetch('usuarios.php', { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            
            if(data.success) {
                toggleModal('modalUsuario', false);
                showToast('¡Operación Exitosa!', data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast('Atención', data.mensaje, 'error');
                btnGuardar.disabled = false;
                txtGuardar.textContent = 'Guardar Usuario';
            }
        } catch (error) {
            showToast('Error', 'No se pudo procesar la solicitud.', 'error');
            btnGuardar.disabled = false;
            txtGuardar.textContent = 'Guardar Usuario';
        }
    });
</script>
</body>
</html>