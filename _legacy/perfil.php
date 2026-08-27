<?php
require_once 'config/config.php';
verificarSesion();

if($_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'normal';

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $passwordHash, $rol]);
        echo json_encode(['success' => true, 'mensaje' => 'Usuario creado correctamente']);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error: El correo ya existe o faltan datos']);
    }
    exit;
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();

include 'header.php';
?>

<div class="container">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 600;">Gestión de Usuarios</h1>
        <p style="color: var(--text-muted);">Administre los accesos al sistema</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <div class="card" style="height: fit-content;">
            <h2 style="font-size: 1.2rem; margin-bottom: 1.5rem;">Crear Nuevo Usuario</h2>
            <form id="formUsuario">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre del usuario">
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="correo@empresa.com">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Rol del Sistema</label>
                    <select name="rol" class="form-control" required>
                        <option value="normal">Normal (Líder / Operaciones)</option>
                        <option value="juridico">Jurídico (Abogado)</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Crear Usuario
                </button>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden; height: fit-content;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: var(--bg-color); border-bottom: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted);">
                        <th style="padding: 1rem;">Nombre</th>
                        <th style="padding: 1rem;">Correo</th>
                        <th style="padding: 1rem;">Rol</th>
                        <th style="padding: 1rem;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $usr): ?>
                        <tr style="border-bottom: 1px solid var(--border); font-size: 0.9rem;">
                            <td style="padding: 1rem; font-weight: 600;"><?= htmlspecialchars($usr['nombre']) ?></td>
                            <td style="padding: 1rem; color: var(--text-muted);"><?= htmlspecialchars($usr['email']) ?></td>
                            <td style="padding: 1rem; text-transform: capitalize;"><?= $usr['rol'] ?></td>
                            <td style="padding: 1rem;">
                                <span style="padding: 0.2rem 0.6rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #059669;">
                                    Activo
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('formUsuario').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
            const response = await fetch('perfil.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if(data.success) {
                mostrarAlerta(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarAlerta(data.mensaje, 'error');
            }
        } catch(error) {
            mostrarAlerta('Error de conexión', 'error');
        }
    });
</script>

</body>
</html>