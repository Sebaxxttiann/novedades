<?php

const ROLES = [
    'normal' => 'Operaciones',
    'juridico' => 'Jurídico',
    'admin' => 'Administrador',
];

const TIPOS_INFRACCION = [
    'operativa' => 'Operativa',
    'seguridad' => 'Seguridad',
    'gestion_humana' => 'Gestión Humana',
];

const DECISIONES = [
    'llamado_atencion' => 'Llamado de atención',
    'proceso_disciplinario' => 'Proceso disciplinario',
];

const CAUSALES_LLAMADO = [
    'alcoholemia' => 'Alcoholemia',
    'ausentismo' => 'Ausentismo injustificado',
    'llegar_tarde' => 'Llegadas tardías',
    'no_acatar_ordenes' => 'No acatar directrices operativas',
    'no_marcar' => 'Omisión de marcación (entrada/salida)',
    'bajo_rendimiento' => 'Bajo rendimiento o negligencia',
];

const ESTADOS_CIERRE = [
    'llamado_atencion_escrito' => 'Llamado de atención escrito',
    'suspension_dias' => 'Suspensión de días',
    'terminacion_contrato' => 'Terminación de contrato',
    'archivado_sin_merito' => 'Archivado (sin mérito)',
];

const ESTADOS = [
    'registrado' => ['label' => 'Registrado', 'icon' => 'clock', 'tone' => 'amber'],
    'citado' => ['label' => 'Citado a descargos', 'icon' => 'calendar-check', 'tone' => 'blue'],
    'llamado_atencion_escrito' => ['label' => 'Llamado escrito', 'icon' => 'file-check', 'tone' => 'emerald'],
    'suspension_dias' => ['label' => 'Suspensión', 'icon' => 'pause-circle', 'tone' => 'orange'],
    'terminacion_contrato' => ['label' => 'Terminación', 'icon' => 'user-x', 'tone' => 'red'],
    'archivado_sin_merito' => ['label' => 'Archivado', 'icon' => 'archive', 'tone' => 'slate'],
];

const EVIDENCIA_EXTENSIONES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'pdf'];
const EVIDENCIA_MAX_BYTES = 10 * 1024 * 1024;

function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function iniciales(string $nombre): string
{
    return mb_strtoupper(mb_substr(trim($nombre), 0, 2, 'UTF-8'), 'UTF-8');
}

function estado_info(?string $estado): array
{
    return ESTADOS[$estado] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $estado)), 'icon' => 'circle', 'tone' => 'slate'];
}

function es_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function responder_json(array $datos, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirigir(string $ruta): never
{
    header('Location: ' . APP_BASE . '/' . ltrim($ruta, '/'));
    exit;
}

function inicio_por_rol(?string $rol): string
{
    return $rol === 'juridico' ? 'juridico.php' : 'dashboard.php';
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function sesion_activa(): bool
{
    global $conn;

    if (empty($_SESSION['usuario_id'])) {
        return false;
    }

    if (time() - ($_SESSION['ultimo_acceso'] ?? 0) > SESSION_TIMEOUT) {
        cerrar_sesion();
        return false;
    }

    $stmt = $conn->prepare("SELECT u.nombre, u.rol, u.operacion_id, u.estado, u.debe_cambiar_password, u.password_actualizado_en, o.nombre AS operacion_nombre
        FROM usuarios u LEFT JOIN operaciones o ON o.id = u.operacion_id WHERE u.id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || (int) $usuario['estado'] !== 1 || (string) $usuario['password_actualizado_en'] !== ($_SESSION['password_version'] ?? '')) {
        cerrar_sesion();
        return false;
    }

    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['operacion_id'] = $usuario['operacion_id'];
    $_SESSION['operacion_nombre'] = $usuario['operacion_nombre'] ?? 'Global';
    $_SESSION['debe_cambiar_password'] = (int) $usuario['debe_cambiar_password'] === 1;

    return true;
}

function auth(array $roles = []): void
{
    if (!sesion_activa()) {
        if (es_ajax()) {
            responder_json(['success' => false, 'sesion_expirada' => true, 'mensaje' => 'Su sesión expiró. Inicie sesión nuevamente.'], 401);
        }
        redirigir('index.php?expirada=1');
    }

    $_SESSION['ultimo_acceso'] = time();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!empty($_SESSION['debe_cambiar_password']) && basename($_SERVER['SCRIPT_NAME']) !== 'cambiar_password.php') {
        if (es_ajax()) {
            responder_json(['success' => false, 'mensaje' => 'Debe cambiar su contraseña antes de continuar.'], 403);
        }
        redirigir('cambiar_password.php');
    }

    if ($roles && !in_array($_SESSION['usuario_rol'], $roles, true)) {
        if (es_ajax()) {
            responder_json(['success' => false, 'mensaje' => 'No tiene permisos para realizar esta acción.'], 403);
        }
        redirigir(inicio_por_rol($_SESSION['usuario_rol']));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf();
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificar_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        responder_json(['success' => false, 'mensaje' => 'El token de seguridad no es válido. Recargue la página e intente de nuevo.', 'csrf_invalido' => true], 403);
    }
}

function ip_cliente(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function limite_excedido(string $clave, int $maximo, int $ventanaSegundos): bool
{
    global $conn;

    if (random_int(1, 50) === 1) {
        $conn->exec("DELETE FROM rate_limits WHERE creado_en < (NOW() - INTERVAL 1 DAY)");
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE clave = ? AND creado_en > (NOW() - INTERVAL ? SECOND)");
    $stmt->execute([hash('sha256', $clave), $ventanaSegundos]);
    return (int) $stmt->fetchColumn() >= $maximo;
}

function registrar_intento(string $clave): void
{
    global $conn;
    $conn->prepare("INSERT INTO rate_limits (clave) VALUES (?)")->execute([hash('sha256', $clave)]);
}

function limpiar_intentos(string $clave): void
{
    global $conn;
    $conn->prepare("DELETE FROM rate_limits WHERE clave = ?")->execute([hash('sha256', $clave)]);
}

function limitar(string $clave, int $maximo, int $ventanaSegundos): void
{
    if (limite_excedido($clave, $maximo, $ventanaSegundos)) {
        responder_json(['success' => false, 'mensaje' => 'Demasiadas solicitudes. Espere un momento e intente de nuevo.'], 429);
    }
    registrar_intento($clave);
}

function filtro_get(string $clave, array $permitidos = []): string
{
    $valor = $_GET[$clave] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    $valor = trim(mb_substr($valor, 0, 100, 'UTF-8'));
    if ($permitidos && !in_array($valor, $permitidos, true)) {
        return '';
    }
    return $valor;
}

function filtro_get_int(string $clave): int
{
    $valor = $_GET[$clave] ?? 0;
    return is_numeric($valor) ? max(0, (int) $valor) : 0;
}

function filtro_get_fecha(string $clave): string
{
    $valor = filtro_get($clave);
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha && $fecha->format('Y-m-d') === $valor ? $valor : '';
}

function guardar_evidencia(array $archivo): ?string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('No se pudo cargar la evidencia.');
    }
    if ($archivo['size'] > EVIDENCIA_MAX_BYTES) {
        throw new RuntimeException('La evidencia supera el tamaño máximo de 10 MB.');
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, EVIDENCIA_EXTENSIONES, true)) {
        throw new RuntimeException('Formato de evidencia no permitido. Use imágenes (JPG, PNG, GIF, WEBP, HEIC) o PDF.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $mimeValido = str_starts_with($mime, 'image/') || $mime === 'application/pdf'
        || ($extension === 'heic' && in_array($mime, ['application/octet-stream', 'image/heif', 'image/heic'], true));
    if (!$mimeValido) {
        throw new RuntimeException('El contenido del archivo no corresponde a una imagen o PDF.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($archivo['tmp_name'], STORAGE_PATH . '/evidencias/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la evidencia.');
    }
    return $nombre;
}

function obtener_reporte_autorizado(int $id): ?array
{
    global $conn;

    $stmt = $conn->prepare("SELECT r.*, o.nombre AS operacion_nombre, u.nombre AS creador_nombre
        FROM reportes_novedades r
        LEFT JOIN operaciones o ON o.id = r.operacion_id
        LEFT JOIN usuarios u ON u.id = r.creador_id
        WHERE r.id = ?");
    $stmt->execute([$id]);
    $reporte = $stmt->fetch();

    if (!$reporte) {
        return null;
    }

    $rol = $_SESSION['usuario_rol'] ?? '';
    $permitido = $rol === 'admin'
        || ($rol === 'normal' && (int) $reporte['operacion_id'] === (int) $_SESSION['operacion_id'])
        || ($rol === 'juridico' && $reporte['decision'] === 'proceso_disciplinario');

    return $permitido ? $reporte : null;
}

function limite_citacion(?string $fechaCitacion, ?string $citadoEn): ?string
{
    if (!$fechaCitacion) {
        return null;
    }
    $hora = $citadoEn ? date('H:i:s', strtotime($citadoEn)) : '00:00:00';
    return $fechaCitacion . 'T' . $hora . '-05:00';
}
