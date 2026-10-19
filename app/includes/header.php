<?php
$rolActual = $_SESSION['usuario_rol'] ?? '';
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
$menu = [
    'admin' => [
        ['dashboard.php', 'layout-dashboard', 'Novedades'],
        ['analisis.php', 'bar-chart-3', 'Análisis'],
        ['usuarios.php', 'users', 'Usuarios'],
        ['operaciones.php', 'map', 'Operaciones'],
    ],
    'normal' => [
        ['dashboard.php', 'layout-dashboard', 'Inicio'],
        ['headcount.php', 'briefcase', 'Headcount'],
    ],
    'juridico' => [
        ['juridico.php', 'gavel', 'Jurídico'],
    ],
][$rolActual] ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= e(APP_BASE) ?>/">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($tituloPagina ?? 'Novedades') ?> | Sistema Disciplinario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" integrity="sha384-RkASv+6KfBMW9eknReJIJ6b3UnjKOKC5bOUaNgIY778NFbQ8MtWq9Lr/khUgqtTt" crossorigin="anonymous">
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" integrity="sha384-ieG+IKD0d/ZPXyCBTMVAbqsQdns8QGJR/e26WMw7M4fkaI/rHcS/YIoi+ah9WGge" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" integrity="sha384-5JqMv4L/Xa0hfvtF06qboNdhvuYXUku9ZrhZh3bSk8VXF0A/RuSLHpLsSV9Zqhl6" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js" integrity="sha384-j/aEP2b+3OKmGqank2qCSosSrlrF9jpIpdgApXq2ryJYBpLSbEi63/PDdL+rKmcQ" crossorigin="anonymous"></script>
    <script>
        try { if (sessionStorage.getItem('actass_loader_visto')) document.documentElement.classList.add('sin-loader'); } catch (e) {}
    </script>
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --surface-glass: rgba(255, 255, 255, 0.85);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #eff6ff;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 10px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
            min-height: 100vh;
        }

        a {
            color: inherit;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }

        #global-loader {
            position: fixed;
            inset: 0;
            background: var(--bg);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        #global-loader.hidden,
        .sin-loader #global-loader {
            opacity: 0;
            visibility: hidden;
        }

        .loader-video {
            width: 170px;
            height: auto;
            margin-bottom: 1rem;
            border-radius: 16px;
            mix-blend-mode: multiply;
        }

        .loader-text {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 1px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        main.container {
            animation: fadeUp 0.35s ease both;
        }

        .navbar {
            background: var(--surface-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 0.7rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
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
            font-size: 1.05rem;
            white-space: nowrap;
            text-decoration: none;
        }

        .nav-brand i {
            color: var(--primary);
        }

        .nav-brand small {
            font-weight: 400;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .nav-links {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            flex: 1;
            justify-content: center;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.55rem 1rem;
            border-radius: var(--radius);
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .nav-toggle {
            display: none;
        }

        .user-profile {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            border-left: 1px solid var(--border);
            padding-left: 1.2rem;
        }

        .user-profile .user-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .user-profile .user-role {
            font-size: 0.72rem;
            color: var(--primary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .avatar-lg {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
        }

        .avatar-primary {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius);
            border: 1px solid transparent;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-hover);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: var(--danger-hover);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-muted);
        }

        .btn-ghost:hover:not(:disabled) {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .btn-outline {
            background: var(--surface);
            color: var(--text-main);
            border-color: var(--border);
        }

        .btn-outline:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: var(--border-strong);
        }

        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        .btn-icon {
            width: 34px;
            height: 34px;
            padding: 0;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-icon:hover {
            background: #f1f5f9;
            color: var(--primary);
        }

        .btn-icon.danger:hover,
        .btn-icon-danger:hover {
            background: #fef2f2;
            color: var(--danger);
        }

        .btn-icon-danger {
            color: var(--text-muted);
            background: transparent;
            padding: 0.5rem;
        }

        .btn-icon svg {
            width: 16px;
            height: 16px;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: var(--transition);
            font-size: 0.92rem;
            margin-top: 6px;
            background: var(--surface-alt);
            color: var(--text-main);
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            resize: vertical;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 1.1rem;
        }

        .form-label {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .form-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .input-action {
            position: relative;
        }

        .input-action .form-control {
            padding-right: 5.5rem;
        }

        .input-action .input-buttons {
            position: absolute;
            right: 6px;
            top: calc(50% + 3px);
            transform: translateY(-50%);
            display: flex;
            gap: 2px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-title h1 {
            font-size: 1.55rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .page-title p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .header-actions {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 0.5rem;
        }

        .breadcrumb:hover {
            color: var(--primary);
        }

        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: opacity 0.2s ease;
        }

        .filters-bar.is-loading {
            opacity: 0.6;
        }

        .filters-bar .filter-field {
            display: flex;
            flex-direction: column;
            min-width: 160px;
            flex: 1 1 160px;
        }

        .filters-bar .filter-field.grow {
            flex: 2 1 260px;
        }

        .filters-bar label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .filters-bar .form-control {
            padding: 0.55rem 0.8rem;
            font-size: 0.88rem;
        }

        .search-input {
            position: relative;
        }

        .search-input svg {
            position: absolute;
            left: 10px;
            top: calc(50% + 3px);
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input .form-control {
            padding-left: 2.1rem;
        }

        .date-picker {
            position: relative;
        }

        .date-picker > svg {
            position: absolute;
            left: 10px;
            top: calc(50% + 3px);
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--text-muted);
            pointer-events: none;
            z-index: 1;
        }

        .date-picker .form-control {
            padding-left: 2.1rem;
            padding-right: 2rem;
            cursor: pointer;
        }

        .date-picker .date-clear {
            position: absolute;
            right: 6px;
            top: calc(50% + 3px);
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            border-radius: 6px;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .date-picker .date-clear:hover {
            background: #f1f5f9;
            color: var(--danger);
        }

        .date-picker.has-value .date-clear {
            display: inline-flex;
        }

        .date-picker .date-clear svg {
            width: 14px;
            height: 14px;
        }

        .flatpickr-calendar {
            font-family: 'Poppins', sans-serif;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            font-size: 0.85rem;
        }

        .flatpickr-calendar.arrowTop::before,
        .flatpickr-calendar.arrowTop::after {
            display: none;
        }

        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-weekdays,
        span.flatpickr-weekday {
            background: transparent;
            color: var(--text-main);
            fill: var(--text-main);
        }

        .flatpickr-current-month {
            font-size: 0.95rem;
            font-weight: 600;
        }

        span.flatpickr-weekday {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
        }

        .flatpickr-day {
            border-radius: 8px;
            font-weight: 500;
        }

        .flatpickr-day.today {
            border-color: var(--primary);
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected:hover,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .flatpickr-day.inRange {
            background: var(--primary-soft);
            border-color: var(--primary-soft);
            box-shadow: -5px 0 0 var(--primary-soft), 5px 0 0 var(--primary-soft);
            color: var(--primary);
        }

        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #cbd5e1;
        }

        .table-card {
            background: var(--surface);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            width: 100%;
        }

        .table-scroll {
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .custom-table th {
            background: var(--surface-alt);
            padding: 0.85rem 1.1rem;
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            vertical-align: middle;
        }

        .custom-table td {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .custom-table th.col-actions,
        .custom-table td.col-actions {
            text-align: right;
            white-space: nowrap;
            width: 1%;
        }

        .custom-table td.nowrap,
        .custom-table th.nowrap {
            white-space: nowrap;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .custom-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .custom-table tbody tr:hover {
            background-color: var(--surface-alt);
        }

        .row-actions {
            display: inline-flex;
            gap: 4px;
            align-items: center;
            justify-content: flex-end;
        }

        .cell-person {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
        }

        .cell-person strong {
            display: block;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .cell-person span {
            display: block;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.82rem;
        }

        .id-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            white-space: nowrap;
        }

        .muted {
            color: var(--text-muted);
        }

        .date-cell {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 0.86rem;
            white-space: nowrap;
        }

        .date-cell svg {
            width: 14px;
            height: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.28rem 0.7rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 600;
            border: 1px solid transparent;
            white-space: nowrap;
            line-height: 1.4;
        }

        .badge svg {
            width: 13px;
            height: 13px;
        }

        .tone-amber { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .tone-blue { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .tone-emerald { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .tone-orange { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .tone-red { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .tone-slate { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .tone-purple { background: #faf5ff; color: #7e22ce; border-color: #e9d5ff; }

        .empty-state {
            padding: 3.5rem 2rem;
            text-align: center;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .empty-state svg {
            width: 44px;
            height: 44px;
            color: #cbd5e1;
            margin-bottom: 6px;
        }

        .empty-state h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #475569;
        }

        .empty-state p {
            font-size: 0.86rem;
        }

        .info-text {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            background: var(--surface-alt);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-top: 12px;
            font-size: 0.84rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .info-text svg {
            width: 18px;
            height: 18px;
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
            margin-bottom: 0.85rem;
        }

        .detail-item {
            background: var(--surface-alt);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            min-width: 0;
        }

        .detail-item.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 0.92rem;
            color: var(--text-main);
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .detail-value.text {
            font-weight: 400;
            line-height: 1.55;
            white-space: pre-wrap;
            font-size: 0.9rem;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
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
            transform: scale(0.97) translateY(10px);
            transition: transform 0.2s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            background: var(--surface);
            z-index: 10;
        }

        .modal-header h2 {
            font-size: 1.15rem;
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
            display: inline-flex;
        }

        .close-modal:hover {
            background: #f1f5f9;
            color: var(--danger);
        }

        .modal-body {
            padding: 1.5rem 1.6rem;
        }

        .modal-footer {
            padding: 1rem 1.6rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: var(--surface-alt);
            position: sticky;
            bottom: 0;
        }

        .confirm-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            background: #fef2f2;
            color: var(--danger);
        }

        .confirm-icon.info {
            background: var(--primary-soft);
            color: var(--primary);
        }

        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: min(380px, calc(100vw - 48px));
            padding: 0.9rem 1.1rem;
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateX(30px);
            opacity: 0;
            transition: all 0.3s ease;
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

        .toast.success::before { background: var(--success); }
        .toast.error::before { background: var(--danger); }
        .toast.warning::before { background: var(--warning); }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-icon {
            margin-top: 2px;
            flex-shrink: 0;
        }

        .toast.success .toast-icon { color: var(--success); }
        .toast.error .toast-icon { color: var(--danger); }
        .toast.warning .toast-icon { color: var(--warning); }

        .toast-title {
            font-weight: 600;
            font-size: 0.92rem;
            margin-bottom: 2px;
            color: var(--text-main);
        }

        .toast-desc {
            font-size: 0.84rem;
            color: var(--text-muted);
        }

        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 0.85rem 1.1rem;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        .pagination-info {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .page-btn {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-main);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .page-btn:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .page-ellipsis {
            padding: 0 4px;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        @media (max-width: 1100px) {
            .navbar {
                flex-wrap: wrap;
                padding: 0.7rem 1rem;
            }

            .nav-toggle {
                display: inline-flex;
                order: 2;
            }

            .user-profile {
                order: 3;
                border-left: none;
                padding-left: 0;
                margin-left: auto;
            }

            .user-profile .user-meta {
                display: none;
            }

            .nav-links {
                order: 4;
                flex-basis: 100%;
                flex-direction: column;
                align-items: stretch;
                display: none;
                padding-top: 0.5rem;
            }

            .nav-links.open {
                display: flex;
            }

            .container {
                padding: 1.25rem 1rem;
            }

            .form-grid,
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    <div id="global-loader">
        <video src="assets/media/loading.webm" class="loader-video" autoplay loop muted playsinline></video>
        <div class="loader-text">Cargando sistema...</div>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <nav class="navbar">
            <a href="<?= e(inicio_por_rol($rolActual)) ?>" class="nav-brand">
                <i data-lucide="layers"></i>
                <span>Novedades <small>| <?= e($_SESSION['operacion_nombre'] ?? 'Global') ?></small></span>
            </a>

            <button type="button" class="btn-icon nav-toggle" aria-label="Abrir menú" onclick="document.getElementById('navLinks').classList.toggle('open')">
                <i data-lucide="menu"></i>
            </button>

            <div class="nav-links" id="navLinks">
                <?php foreach ($menu as [$href, $icono, $texto]): ?>
                    <a href="<?= e($href) ?>" class="nav-link <?= $paginaActual === $href ? 'active' : '' ?>">
                        <i data-lucide="<?= e($icono) ?>" style="width: 18px; height: 18px;"></i> <?= e($texto) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="user-profile">
                <div class="user-meta" style="text-align: right; line-height: 1.3;">
                    <div class="user-name"><?= e($_SESSION['usuario_nombre']) ?></div>
                    <div class="user-role"><?= e(ROLES[$rolActual] ?? $rolActual) ?></div>
                </div>
                <a href="cambiar_password.php" class="avatar" title="Cambiar contraseña" aria-label="Cambiar contraseña" style="text-decoration: none;"><?= e(iniciales($_SESSION['usuario_nombre'])) ?></a>
                <form method="post" action="logout.php" style="display: inline-flex;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn-icon danger" title="Cerrar sesión" aria-label="Cerrar sesión">
                        <i data-lucide="log-out" style="width: 19px; height: 19px;"></i>
                    </button>
                </form>
            </div>
        </nav>
    <?php endif; ?>

    <div id="toast-container" aria-live="polite"></div>

    <div class="modal-overlay" id="modalConfirmar">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-body" style="padding-bottom: 1rem;">
                <div class="confirm-icon" id="confirmarIcono"><i data-lucide="alert-triangle"></i></div>
                <h2 id="confirmarTitulo" style="font-size: 1.1rem; font-weight: 600; margin-bottom: 6px;"></h2>
                <p id="confirmarMensaje" style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" id="confirmarCancelar">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarAceptar">Confirmar</button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="modal-overlay" id="modalSesion">
            <div class="modal-content" style="max-width: 420px;">
                <div class="modal-body" style="padding-bottom: 1rem;">
                    <div class="confirm-icon info"><i data-lucide="timer"></i></div>
                    <h2 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 6px;">Su sesión está por expirar</h2>
                    <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;">
                        Por seguridad cerraremos la sesión por inactividad en <strong id="sesionSegundos">60</strong> segundos.
                    </p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn btn-ghost">Cerrar sesión</button>
                    </form>
                    <button type="button" class="btn btn-primary" id="btnSeguirConectado">Seguir conectado</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        const SESION_ACTIVA = <?= isset($_SESSION['usuario_id']) ? 'true' : 'false' ?>;
        const SESION_TIMEOUT_MS = <?= SESSION_TIMEOUT ?> * 1000;
        const SESION_AVISO_MS = 60 * 1000;

        lucide.createIcons();
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

        (function () {
            const loader = document.getElementById('global-loader');
            const inicio = performance.now();
            const ocultar = () => {
                const espera = Math.max(0, 700 - (performance.now() - inicio));
                setTimeout(() => loader.classList.add('hidden'), document.documentElement.classList.contains('sin-loader') ? 0 : espera);
                try { sessionStorage.setItem('actass_loader_visto', '1'); } catch (e) {}
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ocultar);
            } else {
                ocultar();
            }
        })();

        window.addEventListener('pageshow', (e) => {
            if (e.persisted && SESION_ACTIVA) window.location.reload();
        });

        function sesionExpirada() {
            window.location.href = 'logout.php?expirada=1';
        }

        const fetchOriginal = window.fetch.bind(window);
        window.fetch = async (input, init = {}) => {
            const headers = new Headers(init.headers || {});
            headers.set('X-Requested-With', 'XMLHttpRequest');
            const metodo = (init.method || 'GET').toUpperCase();
            if (metodo !== 'GET' && metodo !== 'HEAD') headers.set('X-CSRF-Token', CSRF_TOKEN);

            const res = await fetchOriginal(input, { ...init, headers, credentials: 'same-origin' });

            if (res.status === 401) {
                sesionExpirada();
                return new Promise(() => {});
            }
            if (SESION_ACTIVA) registrarActividadSesion();
            return res;
        };

        let temporizadorAviso = null;
        let temporizadorCierre = null;
        let intervaloCuenta = null;

        function ultimaActividad() {
            try { return parseInt(localStorage.getItem('actass_ultima_actividad') || '0', 10); } catch (e) { return 0; }
        }

        function programarSesion() {
            clearTimeout(temporizadorAviso);
            clearTimeout(temporizadorCierre);
            const transcurrido = Date.now() - (ultimaActividad() || Date.now());
            const restante = Math.max(0, SESION_TIMEOUT_MS - transcurrido);
            temporizadorAviso = setTimeout(mostrarAvisoSesion, Math.max(0, restante - SESION_AVISO_MS));
            temporizadorCierre = setTimeout(verificarCierreSesion, restante);
        }

        function registrarActividadSesion() {
            try { localStorage.setItem('actass_ultima_actividad', String(Date.now())); } catch (e) {}
            ocultarAvisoSesion();
            programarSesion();
        }

        function verificarCierreSesion() {
            if (Date.now() - ultimaActividad() >= SESION_TIMEOUT_MS - 1000) {
                sesionExpirada();
            } else {
                ocultarAvisoSesion();
                programarSesion();
            }
        }

        function mostrarAvisoSesion() {
            if (Date.now() - ultimaActividad() < SESION_TIMEOUT_MS - SESION_AVISO_MS - 1000) {
                programarSesion();
                return;
            }
            const contador = document.getElementById('sesionSegundos');
            clearInterval(intervaloCuenta);
            intervaloCuenta = setInterval(() => {
                const restante = Math.max(0, Math.ceil((SESION_TIMEOUT_MS - (Date.now() - ultimaActividad())) / 1000));
                contador.textContent = restante;
            }, 1000);
            toggleModal('modalSesion', true);
        }

        function ocultarAvisoSesion() {
            clearInterval(intervaloCuenta);
            const modal = document.getElementById('modalSesion');
            if (modal && modal.classList.contains('active')) toggleModal('modalSesion', false);
        }

        if (SESION_ACTIVA) {
            registrarActividadSesion();
            window.addEventListener('storage', (e) => {
                if (e.key === 'actass_ultima_actividad') {
                    ocultarAvisoSesion();
                    programarSesion();
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && Date.now() - ultimaActividad() >= SESION_TIMEOUT_MS) sesionExpirada();
            });
            document.getElementById('btnSeguirConectado').addEventListener('click', async () => {
                const res = await fetch('api/sesion.php');
                if (res.ok) showToast('Sesión extendida', 'Puede continuar trabajando.', 'success');
            });
        }

        function showToast(title, description, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.setAttribute('role', 'status');

            const iconos = { success: 'check-circle-2', error: 'alert-octagon', warning: 'alert-triangle' };
            const icono = document.createElement('i');
            icono.setAttribute('data-lucide', iconos[type] || 'info');
            icono.className = 'toast-icon';

            const cuerpo = document.createElement('div');
            const titulo = document.createElement('div');
            titulo.className = 'toast-title';
            titulo.textContent = title;
            const desc = document.createElement('div');
            desc.className = 'toast-desc';
            desc.textContent = description || '';
            cuerpo.append(titulo, desc);

            toast.append(icono, cuerpo);
            container.appendChild(toast);
            lucide.createIcons({ nodes: [toast] });

            requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"'`]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;' }[c]));
        }

        function toggleModal(modalId, show) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.toggle('active', show);
            document.body.style.overflow = document.querySelector('.modal-overlay.active') ? 'hidden' : '';
            if (show) {
                const foco = modal.querySelector('input:not([type=hidden]):not([disabled]), select, textarea');
                if (foco) setTimeout(() => foco.focus(), 120);
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const abiertos = Array.from(document.querySelectorAll('.modal-overlay.active')).filter(m => m.id !== 'modalSesion');
            const ultimo = abiertos[abiertos.length - 1];
            if (ultimo) ultimo.id === 'modalConfirmar' ? document.getElementById('confirmarCancelar').click() : toggleModal(ultimo.id, false);
        });

        document.addEventListener('mousedown', (e) => {
            if (e.target.classList && e.target.classList.contains('modal-overlay') && e.target.classList.contains('active') && !['modalSesion', 'modalConfirmar'].includes(e.target.id)) {
                toggleModal(e.target.id, false);
            }
        });

        function confirmar({ titulo = '¿Está seguro?', mensaje = '', textoBoton = 'Confirmar', peligro = true } = {}) {
            return new Promise((resolve) => {
                document.getElementById('confirmarTitulo').textContent = titulo;
                document.getElementById('confirmarMensaje').textContent = mensaje;
                const aceptar = document.getElementById('confirmarAceptar');
                const cancelar = document.getElementById('confirmarCancelar');
                aceptar.textContent = textoBoton;
                aceptar.className = 'btn ' + (peligro ? 'btn-danger' : 'btn-primary');
                document.getElementById('confirmarIcono').className = 'confirm-icon' + (peligro ? '' : ' info');

                const cerrar = (valor) => {
                    aceptar.onclick = null;
                    cancelar.onclick = null;
                    toggleModal('modalConfirmar', false);
                    resolve(valor);
                };
                aceptar.onclick = () => cerrar(true);
                cancelar.onclick = () => cerrar(false);
                toggleModal('modalConfirmar', true);
                setTimeout(() => aceptar.focus(), 120);
            });
        }

        function setBotonCargando(boton, cargando, texto) {
            if (!boton) return;
            if (cargando) {
                boton.dataset.textoOriginal = boton.innerHTML;
                boton.disabled = true;
                boton.textContent = texto || 'Procesando...';
            } else {
                boton.disabled = false;
                if (boton.dataset.textoOriginal) boton.innerHTML = boton.dataset.textoOriginal;
                lucide.createIcons({ nodes: [boton] });
            }
        }

        async function enviarFormulario(url, datos) {
            try {
                const res = await fetch(url, { method: 'POST', body: datos });
                const data = await res.json().catch(() => ({ success: false, mensaje: 'Respuesta inesperada del servidor.' }));
                return data;
            } catch (error) {
                return { success: false, mensaje: 'No se pudo conectar con el servidor.' };
            }
        }

        function initPagination(tableId, options = {}) {
            const perPage = options.perPage || 10;
            const param = options.param || 'pagina';
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const container = options.container ? document.querySelector(options.container) : (table.closest('.table-card') || table.parentElement);
            const existingBar = container.querySelector(`.pagination-bar[data-for="${tableId}"]`);
            if (existingBar) existingBar.remove();

            const rows = Array.from(tbody.children).filter(tr => tr.tagName === 'TR' && !tr.hasAttribute('data-no-paginate'));
            rows.forEach(tr => tr.style.display = '');
            if (rows.length <= perPage) {
                actualizarParametroUrl(param, null);
                return;
            }

            const bar = document.createElement('div');
            bar.className = 'pagination-bar';
            bar.setAttribute('data-for', tableId);
            container.appendChild(bar);

            const totalPages = Math.ceil(rows.length / perPage);
            const desdeUrl = parseInt(new URLSearchParams(window.location.search).get(param) || '1', 10);
            let currentPage = Math.min(Math.max(1, isNaN(desdeUrl) ? 1 : desdeUrl), totalPages);

            function pageList() {
                const pages = new Set([1, totalPages]);
                for (let p = currentPage - 1; p <= currentPage + 1; p++) {
                    if (p > 1 && p < totalPages) pages.add(p);
                }
                return Array.from(pages).sort((a, b) => a - b);
            }

            function irA(pagina) {
                currentPage = pagina;
                actualizarParametroUrl(param, currentPage > 1 ? currentPage : null);
                render();
            }

            function render() {
                rows.forEach((tr, i) => {
                    tr.style.display = Math.floor(i / perPage) + 1 === currentPage ? '' : 'none';
                });

                const start = (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, rows.length);

                let html = `<div class="pagination-info">Mostrando ${start}-${end} de ${rows.length}</div><div class="pagination-controls">`;
                html += `<button type="button" class="page-btn" data-action="prev" aria-label="Anterior" ${currentPage === 1 ? 'disabled' : ''}><i data-lucide="chevron-left" style="width:16px;height:16px;"></i></button>`;

                let last = 0;
                pageList().forEach(p => {
                    if (p - last > 1) html += `<span class="page-ellipsis">&hellip;</span>`;
                    html += `<button type="button" class="page-btn ${p === currentPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
                    last = p;
                });

                html += `<button type="button" class="page-btn" data-action="next" aria-label="Siguiente" ${currentPage === totalPages ? 'disabled' : ''}><i data-lucide="chevron-right" style="width:16px;height:16px;"></i></button></div>`;

                bar.innerHTML = html;
                lucide.createIcons({ nodes: [bar] });

                bar.querySelectorAll('[data-page]').forEach(btn => {
                    btn.addEventListener('click', () => irA(parseInt(btn.dataset.page, 10)));
                });
                bar.querySelector('[data-action="prev"]').addEventListener('click', () => { if (currentPage > 1) irA(currentPage - 1); });
                bar.querySelector('[data-action="next"]').addEventListener('click', () => { if (currentPage < totalPages) irA(currentPage + 1); });
            }

            actualizarParametroUrl(param, currentPage > 1 ? currentPage : null);
            render();
        }

        function actualizarParametroUrl(clave, valor) {
            const url = new URL(window.location.href);
            if (valor === null || valor === undefined || valor === '') {
                url.searchParams.delete(clave);
            } else {
                url.searchParams.set(clave, valor);
            }
            if (url.href !== window.location.href) history.replaceState(null, '', url);
        }

        function initFiltros(formId, { targets = [], onUpdate = null } = {}) {
            const form = document.getElementById(formId);
            if (!form) return;
            let temporizador = null;
            let controlador = null;

            async function aplicar() {
                const params = new URLSearchParams();
                new FormData(form).forEach((valor, clave) => {
                    if (String(valor).trim() !== '') params.append(clave, String(valor).trim());
                });
                const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                history.replaceState(null, '', url);

                if (controlador) controlador.abort();
                controlador = new AbortController();
                form.classList.add('is-loading');

                try {
                    const res = await fetch(url, { signal: controlador.signal });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    targets.forEach(selector => {
                        const nuevo = doc.querySelector(selector);
                        const actual = document.querySelector(selector);
                        if (nuevo && actual) actual.replaceWith(document.importNode(nuevo, true));
                    });
                    lucide.createIcons();
                    if (onUpdate) onUpdate();
                } catch (error) {
                    if (error.name !== 'AbortError') showToast('Error', 'No se pudieron aplicar los filtros.', 'error');
                } finally {
                    form.classList.remove('is-loading');
                }
            }

            form.addEventListener('submit', (e) => { e.preventDefault(); aplicar(); });
            form.querySelectorAll('select, input[type="date"], input[type="checkbox"]').forEach(el => el.addEventListener('change', aplicar));
            form.querySelectorAll('input[type="search"], input[type="text"]').forEach(el => el.addEventListener('input', () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(aplicar, 350);
            }));
            form.addEventListener('rangochange', aplicar);
            form.querySelectorAll('[data-filtro-limpiar]').forEach(btn => btn.addEventListener('click', () => {
                form.querySelectorAll('input:not([type="hidden"]), select').forEach(el => {
                    if (el.type === 'checkbox') el.checked = false; else el.value = '';
                });
                form.querySelectorAll('[data-rango-fechas]').forEach(cont => {
                    if (cont.limpiarRango) cont.limpiarRango();
                });
                aplicar();
            }));

            return aplicar;
        }

        function fechaIso(fecha) {
            return flatpickr.formatDate(fecha, 'Y-m-d');
        }

        function fechaDesdeIso(valor) {
            return /^\d{4}-\d{2}-\d{2}$/.test(valor || '') ? new Date(valor + 'T00:00:00') : null;
        }

        function initCalendarios(raiz = document) {
            if (!window.flatpickr) return;

            raiz.querySelectorAll('[data-rango-fechas]:not([data-listo])').forEach(cont => {
                cont.dataset.listo = '1';
                const visible = cont.querySelector('input[type="text"]');
                const [desde, hasta] = cont.querySelectorAll('input[type="hidden"]');
                const botonLimpiar = cont.querySelector('.date-clear');

                const sincronizar = (fechas, notificar) => {
                    const previo = desde.value + '|' + hasta.value;
                    desde.value = fechas[0] ? fechaIso(fechas[0]) : '';
                    hasta.value = fechas[1] ? fechaIso(fechas[1]) : desde.value;
                    cont.classList.toggle('has-value', desde.value !== '');
                    if (notificar && previo !== desde.value + '|' + hasta.value) {
                        cont.dispatchEvent(new Event('rangochange', { bubbles: true }));
                    }
                };

                const fp = flatpickr(visible, {
                    mode: 'range',
                    locale: 'es',
                    dateFormat: 'd/m/Y',
                    disableMobile: true,
                    maxDate: fechaDesdeIso(cont.dataset.max),
                    defaultDate: [fechaDesdeIso(desde.value), fechaDesdeIso(hasta.value)].filter(Boolean),
                    onClose: (fechas) => sincronizar(fechas, true),
                });

                cont.limpiarRango = () => {
                    fp.clear(false);
                    sincronizar([], false);
                };
                cont.classList.toggle('has-value', desde.value !== '');
                if (botonLimpiar) {
                    botonLimpiar.addEventListener('click', () => {
                        fp.clear(false);
                        sincronizar([], true);
                    });
                }
            });

            raiz.querySelectorAll('[data-fecha-picker]:not([data-listo])').forEach(cont => {
                cont.dataset.listo = '1';
                const visible = cont.querySelector('input[type="text"]');
                const oculto = cont.querySelector('input[type="hidden"]');

                const fp = flatpickr(visible, {
                    locale: 'es',
                    dateFormat: 'd/m/Y',
                    disableMobile: true,
                    minDate: fechaDesdeIso(cont.dataset.min),
                    maxDate: fechaDesdeIso(cont.dataset.max),
                    defaultDate: fechaDesdeIso(oculto.value),
                    onChange: (fechas) => {
                        oculto.value = fechas[0] ? fechaIso(fechas[0]) : '';
                        cont.classList.toggle('has-value', oculto.value !== '');
                    },
                });

                cont.limpiarFecha = () => {
                    fp.clear(false);
                    oculto.value = '';
                    cont.classList.remove('has-value');
                };
            });
        }

        document.addEventListener('DOMContentLoaded', () => initCalendarios());

        function togglePassword(inputId, boton) {
            const input = document.getElementById(inputId);
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            boton.innerHTML = `<i data-lucide="${visible ? 'eye' : 'eye-off'}"></i>`;
            lucide.createIcons({ nodes: [boton] });
        }

        function generarPassword(longitud = 12) {
            const caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%*';
            const valores = crypto.getRandomValues(new Uint32Array(longitud));
            let pass = '';
            valores.forEach(v => pass += caracteres[v % caracteres.length]);
            return /\d/.test(pass) && /[A-Z]/.test(pass) && /[a-z]/.test(pass) ? pass : generarPassword(longitud);
        }

        async function copiarTexto(texto) {
            try {
                await navigator.clipboard.writeText(texto);
                showToast('Copiado', 'Se copió al portapapeles.', 'success');
            } catch (e) {
                showToast('Atención', 'No se pudo copiar automáticamente.', 'warning');
            }
        }
    </script>
