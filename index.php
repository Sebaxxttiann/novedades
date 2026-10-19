<?php
require_once __DIR__ . '/app/config/config.php';

if (sesion_activa()) {
    redirigir(inicio_por_rol($_SESSION['usuario_rol']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || mb_strlen($email) > 150 || strlen($password) > 200) {
        responder_json(['success' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 422);
    }

    $claveIp = 'login_ip:' . ip_cliente();
    $claveEmail = 'login_email:' . mb_strtolower($email);

    if (limite_excedido($claveIp, 20, 900) || limite_excedido($claveEmail, 5, 900)) {
        responder_json(['success' => false, 'mensaje' => 'Demasiados intentos. Intente de nuevo en 15 minutos.'], 429);
    }

    try {
        $stmt = $conn->prepare("SELECT u.id, u.nombre, u.rol, u.operacion_id, u.password, o.nombre AS operacion_nombre
            FROM usuarios u LEFT JOIN operaciones o ON u.operacion_id = o.id
            WHERE u.email = ? AND u.estado = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
    } catch (Throwable $ex) {
        error_log('login: ' . $ex->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo procesar la solicitud. Intente de nuevo.'], 500);
    }

    $hashDummy = '$2y$10$O8ReepCemxQtE1iqKgh2Y.Zh7CRwhx36VpHlwht3ICxBlPa0iNPh2';
    $hash = $usuario ? $usuario['password'] : $hashDummy;
    $valido = password_verify($password, $hash) && $usuario;

    if (!$valido) {
        registrar_intento($claveIp);
        registrar_intento($claveEmail);
        responder_json(['success' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 401);
    }

    limpiar_intentos($claveEmail);

    if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
        try {
            $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_DEFAULT), $usuario['id']]);
        } catch (Throwable $ex) {
            error_log('login rehash: ' . $ex->getMessage());
        }
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['operacion_id'] = $usuario['operacion_id'];
    $_SESSION['operacion_nombre'] = $usuario['operacion_nombre'] ?? 'Global';
    $_SESSION['ultimo_acceso'] = time();
    unset($_SESSION['csrf_token']);
    csrf_token();

    responder_json(['success' => true, 'redirect' => inicio_por_rol($usuario['rol'])]);
}

$aviso = '';
if (($_GET['expirada'] ?? '') === '1') {
    $aviso = 'Tu sesión expiró por inactividad. Ingresa de nuevo.';
} elseif (($_GET['salida'] ?? '') === '1') {
    $aviso = 'Cerraste sesión correctamente.';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Sistema Disciplinario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --navy-deep: #0f1f4d;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --info: #1d4ed8;
            --info-bg: #eff6ff;
            --info-border: #bfdbfe;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #eef2f7;
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .login-card {
            width: 100%;
            max-width: 940px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
        }

        .login-brand {
            background: linear-gradient(160deg, var(--navy-deep) 0%, var(--primary) 100%);
            color: #ffffff;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 1.5rem;
        }

        .brand-video {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.85);
            background: rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .brand-video video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand-title {
            font-size: 1.45rem;
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.3px;
        }

        .brand-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            text-align: left;
            margin-top: 0.5rem;
        }

        .brand-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.92);
        }

        .brand-list .icono {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-list svg {
            width: 18px;
            height: 18px;
        }

        .login-form-panel {
            padding: 3rem 2.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-panel h1 {
            font-size: 1.65rem;
            font-weight: 600;
            letter-spacing: -0.4px;
            margin-bottom: 0.35rem;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }

        .mensaje {
            display: none;
            align-items: flex-start;
            gap: 8px;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            border: 1px solid transparent;
            line-height: 1.45;
        }

        .mensaje.visible {
            display: flex;
        }

        .mensaje svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .mensaje.error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: var(--danger-border);
        }

        .mensaje.aviso {
            background: var(--info-bg);
            color: var(--info);
            border-color: var(--info-border);
        }

        .input-group {
            margin-bottom: 1.1rem;
        }

        .input-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.92rem;
            color: var(--text-main);
            background: var(--surface-alt);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .input-wrapper.con-accion .form-control {
            padding-right: 2.8rem;
        }

        .btn-ojo {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-ojo:hover {
            color: var(--primary);
            background: #f1f5f9;
        }

        .btn-ojo svg {
            width: 18px;
            height: 18px;
        }

        .btn-ingresar {
            width: 100%;
            margin-top: 0.5rem;
            padding: 0.85rem;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.2s ease;
        }

        .btn-ingresar:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .btn-ingresar:disabled {
            opacity: 0.75;
            cursor: not-allowed;
        }

        .btn-ingresar svg {
            width: 18px;
            height: 18px;
        }

        .spinner {
            display: none;
            width: 17px;
            height: 17px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: girar 0.8s linear infinite;
        }

        .cargando .spinner {
            display: block;
        }

        .cargando .btn-flecha {
            display: none;
        }

        @keyframes girar {
            to {
                transform: rotate(360deg);
            }
        }

        .ayuda {
            margin-top: 1.5rem;
            font-size: 0.82rem;
            color: var(--text-muted);
            text-align: center;
        }

        @media (max-width: 820px) {
            body {
                padding: 16px;
                align-items: flex-start;
            }

            .login-card {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .login-brand {
                flex-direction: row;
                padding: 1.25rem 1.5rem;
                text-align: left;
                gap: 1rem;
            }

            .brand-video {
                width: 64px;
                height: 64px;
                border-width: 2px;
            }

            .brand-title {
                font-size: 1.05rem;
            }

            .brand-list {
                display: none;
            }

            .login-form-panel {
                padding: 2rem 1.5rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    <main class="login-card">
        <section class="login-brand">
            <div class="brand-video">
                <video src="uploads/hello.webm" autoplay loop muted playsinline></video>
            </div>
            <div>
                <h2 class="brand-title">Sistema de Gestión de Novedades</h2>
                <ul class="brand-list">
                    <li><span class="icono"><i data-lucide="clipboard-list"></i></span>Registro de novedades</li>
                    <li><span class="icono"><i data-lucide="gavel"></i></span>Seguimiento jurídico</li>
                    <li><span class="icono"><i data-lucide="bar-chart-3"></i></span>Análisis por operación</li>
                </ul>
            </div>
        </section>

        <section class="login-form-panel">
            <h1>Iniciar sesión</h1>
            <p class="subtitle">Ingresa tus credenciales para continuar.</p>

            <div class="mensaje error" id="cajaError" role="alert">
                <i data-lucide="alert-circle"></i>
                <span id="textoError"></span>
            </div>

            <?php if ($aviso !== ''): ?>
                <div class="mensaje aviso visible" role="status">
                    <i data-lucide="info"></i>
                    <span><?= e($aviso) ?></span>
                </div>
            <?php endif; ?>

            <form id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="input-group">
                    <label for="email">Correo electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="form-control" required autofocus autocomplete="username" placeholder="correo@empresa.com">
                        <i data-lucide="mail" class="input-icon"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper con-accion">
                        <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Tu contraseña">
                        <i data-lucide="lock" class="input-icon"></i>
                        <button type="button" class="btn-ojo" id="btnOjo" aria-label="Mostrar u ocultar contraseña"><i data-lucide="eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn-ingresar" id="btnIngresar">
                    <span id="btnTexto">Ingresar al sistema</span>
                    <i data-lucide="arrow-right" class="btn-flecha"></i>
                    <span class="spinner"></span>
                </button>
            </form>

            <p class="ayuda">¿Olvidaste tu contraseña? Contacta al administrador.</p>
        </section>
    </main>

    <script>
        try {
            localStorage.removeItem('actass_ultima_actividad');
            sessionStorage.removeItem('actass_loader_visto');
        } catch (e) {}

        lucide.createIcons();

        const form = document.getElementById('loginForm');
        const cajaError = document.getElementById('cajaError');
        const textoError = document.getElementById('textoError');
        const btnIngresar = document.getElementById('btnIngresar');
        const btnTexto = document.getElementById('btnTexto');
        const inputPassword = document.getElementById('password');
        const btnOjo = document.getElementById('btnOjo');

        btnOjo.addEventListener('click', () => {
            const visible = inputPassword.type === 'text';
            inputPassword.type = visible ? 'password' : 'text';
            btnOjo.innerHTML = `<i data-lucide="${visible ? 'eye' : 'eye-off'}"></i>`;
            lucide.createIcons({ nodes: [btnOjo] });
        });

        function mostrarError(mensaje) {
            textoError.textContent = mensaje;
            cajaError.classList.add('visible');
        }

        function setCargando(cargando) {
            btnIngresar.disabled = cargando;
            btnIngresar.classList.toggle('cargando', cargando);
            btnTexto.textContent = cargando ? 'Verificando...' : 'Ingresar al sistema';
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            cajaError.classList.remove('visible');

            if (!form.email.value.trim() || !inputPassword.value) {
                mostrarError('Ingresa tu correo y tu contraseña.');
                return;
            }

            setCargando(true);

            try {
                const res = await fetch('index.php', {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({ success: false, mensaje: 'Respuesta inesperada del servidor.' }));

                if (data.success) {
                    btnTexto.textContent = 'Redirigiendo...';
                    window.location.href = data.redirect || 'dashboard.php';
                    return;
                }

                setCargando(false);
                mostrarError(data.mensaje || 'No se pudo iniciar sesión.');
            } catch (error) {
                setCargando(false);
                mostrarError('Ocurrió un error de conexión.');
            }
        });
    </script>
</body>

</html>
