<?php
session_start();
try {
    $conn = new PDO("mysql:host=localhost;dbname=si_disciplinario_v2;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error BD: " . $e->getMessage());
}

function auth() {
    if(!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }
}
?>