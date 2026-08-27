<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operaciones | Novedades</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-glass: rgba(255, 255, 255, 0.85);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --border: #e2e8f0;
            --danger: #ef4444;
            --success: #10b981;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }


        #global-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #global-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loader-video {
            width: 190px;
            height: auto;
            margin-bottom: 1rem;
            border-radius: 16px;
            mix-blend-mode: multiply;
        }

        .loader-text {
            color: var(--text-muted);
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 1px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }


        .navbar {
            background: var(--surface-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .nav-brand {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-main);
            font-size: 1.1rem;
        }

        .nav-brand i {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .user-profile {
            display: flex;
            gap: 1rem;
            align-items: center;
            border-left: 1px solid var(--border);
            padding-left: 1.5rem;
            margin-left: 0.5rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius);
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
        }

        .btn-ghost:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .btn-icon-danger {
            color: var(--text-muted);
            background: transparent;
            padding: 0.5rem;
        }

        .btn-icon-danger:hover {
            background: #fef2f2;
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: var(--transition);
            font-size: 0.95rem;
            margin-top: 6px;
            background: #f8fafc;
            color: var(--text-main);
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
        }

        /* ==========================================================================
           MODALES
           ========================================================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
            z-index: 2000;
            padding: 1rem;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: var(--surface);
            width: 100%;
            max-width: 550px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.95) translateY(20px);
            transition: var(--transition);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--surface);
            z-index: 10;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .close-modal {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 6px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .close-modal:hover {
            background: #f1f5f9;
            color: var(--danger);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.2rem 2rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            background: #f8fafc;
        }

        /* ==========================================================================
           TOAST NOTIFICATIONS
           ========================================================================== */
        #toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 320px;
            max-width: 400px;
            padding: 1rem 1.2rem;
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            overflow: hidden;
        }

        .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .toast.success::before {
            background: var(--success);
        }

        .toast.error::before {
            background: var(--danger);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-icon {
            margin-top: 2px;
        }

        .toast.success .toast-icon {
            color: var(--success);
        }

        .toast.error .toast-icon {
            color: var(--danger);
        }

        .toast-title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 2px;
            color: var(--text-main);
        }

        .toast-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>

    <!-- PANTALLA DE CARGA (SPLASH SCREEN) -->
    <div id="global-loader">
        <!-- Tu logo animado "pensando" -->
        <video src="uploads/loading.webm" class="loader-video" autoplay loop muted playsinline></video>
        <div class="loader-text">Cargando sistema...</div>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <nav class="navbar">
            <div class="nav-brand">
                <i data-lucide="layers"></i>
                <span>Novedades | <span style="font-weight: 400; color: var(--text-muted);"><?= htmlspecialchars($_SESSION['operacion_nombre'] ?? 'Global') ?></span></span>
            </div>

            <!-- Menú de Navegación -->
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-ghost"><i data-lucide="layout-dashboard" style="width: 18px;"></i> Inicio</a>

                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                    <a href="usuarios.php" class="btn btn-ghost"><i data-lucide="users" style="width: 18px;"></i> Usuarios</a>
                    <a href="operaciones.php" class="btn btn-ghost"><i data-lucide="map" style="width: 18px;"></i> Operaciones</a>
                <?php endif; ?>
            </div>

            <!-- Info del Usuario y Salir -->
            <div class="user-profile">
                <div style="text-align: right; line-height: 1.3;">
                    <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--primary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><?= $_SESSION['usuario_rol'] ?></div>
                </div>
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 600; font-size: 0.9rem;">
                    <?= strtoupper(substr($_SESSION['usuario_nombre'], 0, 2)) ?>
                </div>
                <a href="logout.php" class="btn btn-icon-danger" title="Cerrar sesión">
                    <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
                </a>
            </div>
        </nav>
    <?php endif; ?>

    <div id="toast-container"></div>

    <script>
        // 1. Inicializar iconos
        lucide.createIcons();

        // 2. Lógica para desaparecer el SplashScreen al cargar la página
        window.addEventListener('load', () => {
            const loader = document.getElementById('global-loader');
            // Delay de 2000ms (2 segundos) garantizados para apreciar la animación
            setTimeout(() => {
                loader.classList.add('hidden');
            }, 2000);
        });

        // 3. Sistema de Notificaciones (Toasts) Mejorado
        function showToast(title, description, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const iconName = type === 'success' ? 'check-circle-2' : 'alert-octagon';

            toast.innerHTML = `
            <i data-lucide="${iconName}" class="toast-icon"></i>
            <div>
                <div class="toast-title">${title}</div>
                <div class="toast-desc">${description}</div>
            </div>
        `;

            container.appendChild(toast);
            lucide.createIcons();

            // Animación de entrada
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.classList.add('show');
                });
            });

            // Animación de salida y limpieza
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500); // Espera que termine la transición CSS
            }, 4000);
        }

        // 4. Sistema de Modales
        function toggleModal(modalId, show) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            if (show) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Evita scroll en el fondo
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    </script>