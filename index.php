<?php
require_once __DIR__ . '/app/config/config.php';
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT u.*, o.nombre as operacion_nombre FROM usuarios u LEFT JOIN operaciones o ON u.operacion_id = o.id WHERE u.email = ? AND u.estado = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol'] = $user['rol'];
        $_SESSION['operacion_id'] = $user['operacion_id'];
        $_SESSION['operacion_nombre'] = $user['operacion_nombre'] ?? 'Global';
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Credenciales incorrectas o usuario inactivo.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema Disciplinario</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #60a5fa;
            --navy: #1e3a8a;
            --navy-deep: #0f1f4d;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error: #ef4444;
            --error-bg: #fef2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html,
        body {
            height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle at 15% 20%, #f8f8fa 0%, transparent 45%),
                radial-gradient(circle at 85% 15%, #f8fafe 0%, transparent 50%),
                radial-gradient(circle at 50% 100%, #e8e9ed 0%, transparent 60%),
                linear-gradient(160deg, var(--navy-deep) 0%, var(--navy) 55%, #17296b 100%);
            background-size: 200% 200%, 200% 200%, 200% 200%, 100% 100%;
            animation: meshMove 18s ease-in-out infinite;
        }

        @keyframes meshMove {

            0%,
            100% {
                background-position: 0% 0%, 100% 0%, 50% 100%, 0 0;
            }

            50% {
                background-position: 20% 30%, 70% 30%, 60% 80%, 0 0;
            }
        }

        /* Rejilla de puntos flotando sobre el fondo, muy sutil */
        .mesh-grid {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 34px 34px;
            z-index: 0;
            pointer-events: none;
        }


        @keyframes drift {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(60px, -40px);
            }
        }

        .stage {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Video circular flotante, ancla visual arriba de la tarjeta */
        .avatar-video {
            width: 128px;
            height: 128px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35), 0 0 0 8px rgba(255, 255, 255, 0.08);
            margin-bottom: -64px;
            position: relative;
            z-index: 3;
            background: var(--navy);
            animation: floatSoft 5s ease-in-out infinite;
            opacity: 0;
            transform: scale(0.7) translateY(10px);
            animation: floatSoft 5s ease-in-out infinite, popIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
        }

        @keyframes popIn {
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes floatSoft {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .avatar-video video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Tarjeta glass */
        .card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 84px 2.75rem 2.75rem;
            box-shadow: 0 40px 80px -20px rgba(2, 8, 30, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.5);
            opacity: 0;
            transform: translateY(20px);
            animation: riseIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.05s forwards;
        }

        @keyframes riseIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-pill {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-bottom: 1rem;
        }

        .status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.18);
        }

        .status-pill span.label {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .card h1 {
            text-align: center;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .card>p.subtitle {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .input-group {
            margin-bottom: 1.1rem;
        }

        .input-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--text-main);
            margin-bottom: 7px;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 18px;
            height: 18px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.7rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.92rem;
            color: var(--text-main);
            background: #f8fafc;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:hover {
            border-color: #cbd5e1;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .form-control:focus+.input-icon {
            color: var(--primary);
        }

        .form-meta {
            display: flex;
            justify-content: flex-end;
            margin: -0.3rem 0 1.4rem;
        }

        .form-meta a {
            font-size: 0.8rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .form-meta a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--navy-deep) 100%);
            background-size: 160% 160%;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.96rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease, background-position 0.5s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 14px 30px -10px rgba(37, 99, 235, 0.6);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background-position: 100% 0%;
            box-shadow: 0 18px 34px -8px rgba(37, 99, 235, 0.7);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            cursor: not-allowed;
            opacity: 0.85;
            transform: none;
        }

        #btnIcon {
            transition: transform 0.2s ease;
        }

        .btn-primary:hover #btnIcon {
            transform: translateX(3px);
        }

        #error-msg {
            background: var(--error-bg);
            color: var(--error);
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            display: none;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.2rem;
            border: 1px solid #fecaca;
            animation: shake 0.4s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .spinner {
            display: none;
            width: 17px;
            height: 17px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .footer-note {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.6);
            position: relative;
            z-index: 2;
        }

        @media (max-width: 480px) {
            .card {
                padding: 76px 1.75rem 2.25rem;
                border-radius: 22px;
            }

            .avatar-video {
                width: 108px;
                height: 108px;
                margin-bottom: -54px;
            }

            .card h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>



    <div class="stage">
        <div class="avatar-video">
            <video src="uploads/hello.webm" autoplay loop muted playsinline></video>
        </div>

        <div class="card">
            <div class="status-pill">
                <span class="dot"></span>
                <span class="label"></span>
            </div>

            <h1>¡HOLAA!</h1>
            <p class="subtitle">Ingresa tus credenciales para continuar</p>

            <div id="error-msg">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span id="error-text"></span>
            </div>

            <form id="loginForm">
                <div class="input-group">
                    <label>Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" required placeholder="correo@empresa.com">
                        <i data-lucide="mail" class="input-icon"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label>Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        <i data-lucide="lock" class="input-icon"></i>
                    </div>
                </div>

                <div class="form-meta">
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <span id="btnText">Ingresar al sistema</span>
                    <i data-lucide="arrow-right" id="btnIcon" style="width: 18px;"></i>
                    <div class="spinner" id="btnSpinner"></div>
                </button>
            </form>
        </div>

        <p class="footer-note">Sistema de Gestión de Novedades y Operaciones</p>
    </div>

    <script>
        lucide.createIcons();

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const errBox = document.getElementById('error-msg');
            const errText = document.getElementById('error-text');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            const btnSpinner = document.getElementById('btnSpinner');

            errBox.style.display = 'none';
            submitBtn.disabled = true;
            btnText.textContent = 'Verificando...';
            btnIcon.style.display = 'none';
            btnSpinner.style.display = 'block';

            try {
                const res = await fetch('index.php', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await res.json();

                if (data.success) {
                    btnText.textContent = 'Redirigiendo...';
                    window.location.href = 'dashboard.php';
                } else {
                    submitBtn.disabled = false;
                    btnText.textContent = 'Ingresar al sistema';
                    btnIcon.style.display = 'block';
                    btnSpinner.style.display = 'none';
                    errText.textContent = data.mensaje;
                    errBox.style.display = 'flex';
                }
            } catch (error) {
                submitBtn.disabled = false;
                btnText.textContent = 'Ingresar al sistema';
                btnIcon.style.display = 'block';
                btnSpinner.style.display = 'none';
                errText.textContent = "Ocurrió un error de conexión.";
                errBox.style.display = 'flex';
            }
        });
    </script>
</body>

</html>