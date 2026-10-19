<?php
require_once __DIR__ . '/../app/config/config.php';
auth();

$obligatorio = !empty($_SESSION['debe_cambiar_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    limitar('cambiar_password:' . $_SESSION['usuario_id'], 10, 900);

    $actual = $_POST['password_actual'] ?? '';
    $nueva = $_POST['password_nueva'] ?? '';
    $confirmacion = $_POST['password_confirmacion'] ?? '';

    if (!is_string($actual) || !is_string($nueva) || !is_string($confirmacion)) {
        responder_json(['success' => false, 'mensaje' => 'Solicitud inválida.'], 422);
    }
    if (mb_strlen($nueva, 'UTF-8') < 8 || strlen($nueva) > 72) {
        responder_json(['success' => false, 'mensaje' => 'La nueva contraseña debe tener entre 8 y 72 caracteres.'], 422);
    }
    if (!preg_match('/[A-Za-z]/', $nueva) || !preg_match('/\d/', $nueva)) {
        responder_json(['success' => false, 'mensaje' => 'La nueva contraseña debe combinar letras y números.'], 422);
    }
    if (!hash_equals($nueva, $confirmacion)) {
        responder_json(['success' => false, 'mensaje' => 'La confirmación no coincide con la nueva contraseña.'], 422);
    }

    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($actual, $hash)) {
        responder_json(['success' => false, 'mensaje' => 'La contraseña actual no es correcta.'], 422);
    }
    if (password_verify($nueva, $hash)) {
        responder_json(['success' => false, 'mensaje' => 'La nueva contraseña debe ser diferente a la actual.'], 422);
    }

    $actualizadoEn = date('Y-m-d H:i:s');
    $conn->prepare("UPDATE usuarios SET password = ?, debe_cambiar_password = 0, password_actualizado_en = ? WHERE id = ?")
        ->execute([password_hash($nueva, PASSWORD_DEFAULT), $actualizadoEn, $_SESSION['usuario_id']]);

    session_regenerate_id(true);
    $_SESSION['password_version'] = $actualizadoEn;
    $_SESSION['debe_cambiar_password'] = false;

    responder_json([
        'success' => true,
        'mensaje' => 'Contraseña actualizada correctamente.',
        'redirect' => inicio_por_rol($_SESSION['usuario_rol']),
    ]);
}

$tituloPagina = 'Cambiar contraseña';
include __DIR__ . '/../app/includes/header.php';
?>

<main class="container" style="max-width: 560px;">
    <div class="page-header">
        <div class="page-title">
            <h1>Cambiar contraseña</h1>
            <p><?= $obligatorio
                ? 'Por seguridad debe definir una contraseña personal antes de continuar.'
                : 'Actualice la contraseña con la que ingresa al sistema.' ?></p>
        </div>
    </div>

    <div class="table-card" style="padding: 1.5rem;">
        <form id="formPassword" autocomplete="off" novalidate>
            <div class="form-group">
                <label class="form-label" for="passwordActual">Contraseña actual</label>
                <div class="input-action">
                    <input type="password" id="passwordActual" name="password_actual" class="form-control" required autocomplete="current-password">
                    <div class="input-buttons">
                        <button type="button" class="btn-icon" aria-label="Mostrar contraseña" onclick="togglePassword('passwordActual', this)"><i data-lucide="eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="passwordNueva">Nueva contraseña</label>
                <div class="input-action">
                    <input type="password" id="passwordNueva" name="password_nueva" class="form-control" required minlength="8" maxlength="72" autocomplete="new-password">
                    <div class="input-buttons">
                        <button type="button" class="btn-icon" aria-label="Mostrar contraseña" onclick="togglePassword('passwordNueva', this)"><i data-lucide="eye"></i></button>
                    </div>
                </div>
                <p class="form-hint">Mínimo 8 caracteres, combinando letras y números.</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="passwordConfirmacion">Confirmar nueva contraseña</label>
                <input type="password" id="passwordConfirmacion" name="password_confirmacion" class="form-control" required minlength="8" maxlength="72" autocomplete="new-password">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <?php if (!$obligatorio): ?>
                    <a href="<?= e(inicio_por_rol($_SESSION['usuario_rol'])) ?>" class="btn btn-ghost">Cancelar</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" id="btnGuardarPassword">
                    <i data-lucide="key-round" style="width: 16px;"></i> Guardar contraseña
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.getElementById('formPassword').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formulario = e.target;
        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }
        if (formulario.password_nueva.value !== formulario.password_confirmacion.value) {
            showToast('Atención', 'La confirmación no coincide con la nueva contraseña.', 'error');
            return;
        }

        const boton = document.getElementById('btnGuardarPassword');
        setBotonCargando(boton, true, 'Guardando...');
        const data = await enviarFormulario('cambiar_password.php', new FormData(formulario));
        setBotonCargando(boton, false);

        if (!data.success) {
            showToast('Atención', data.mensaje, 'error');
            return;
        }

        showToast('Listo', data.mensaje, 'success');
        setTimeout(() => { window.location.href = data.redirect; }, 700);
    });
</script>
</body>

</html>
