<?php
date_default_timezone_set('America/Bogota');

const SESSION_TIMEOUT = 1800;

define('ROOT_PATH', dirname(__DIR__, 2));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('APP_BASE', rtrim(str_replace('\\', '/', substr(
    realpath(ROOT_PATH),
    strlen(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '')
)), '/'));

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', (string) (SESSION_TIMEOUT + 600));

if (session_status() === PHP_SESSION_NONE) {
    session_name('NOVEDADES_SID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => APP_BASE . '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

header_remove('X-Powered-By');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

try {
    $conn = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'si_disciplinario_v2') . ';charset=utf8mb4',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Error BD: ' . $e->getMessage());
    http_response_code(500);
    exit('El servicio no está disponible en este momento. Intente más tarde.');
}

require_once __DIR__ . '/../includes/helpers.php';
