<?php
require_once 'config/config.php';
auth();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $operacion_id = $_SESSION['operacion_id'];
    $creador_id = $_SESSION['usuario_id'];
    $colaborador_nombre = $_POST['colaborador_nombre'];
    $colaborador_cedula = $_POST['colaborador_cedula'];
    $tipo_infraccion = $_POST['tipo_infraccion'];
    $descripcion = $_POST['descripcion'];
    $decision = $_POST['decision'];
    
    $causal = $_POST['causal_llamado'] ?? null;
    $reincidente = isset($_POST['reincidente']) ? 1 : 0;
    $falta_grave = isset($_POST['falta_grave']) ? 1 : 0;

    $evidencia = null;
    if(isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === 0) {
        $nombre_archivo = uniqid() . '_' . $_FILES['evidencia']['name'];
        move_uploaded_file($_FILES['evidencia']['tmp_name'], 'uploads/' . $nombre_archivo);
        $evidencia = $nombre_archivo;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO reportes_novedades 
            (creador_id, operacion_id, colaborador_nombre, colaborador_cedula, tipo_infraccion, descripcion, evidencia, decision, causal_llamado, reincidente, falta_grave) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $creador_id, $operacion_id, $colaborador_nombre, $colaborador_cedula, 
            $tipo_infraccion, $descripcion, $evidencia, $decision, $causal, $reincidente, $falta_grave
        ]);

        if($decision === 'proceso_disciplinario') {
            // CÓDIGO DE ENVIO DE CORREO ELECTRONICO A JURIDICOS
            $mensaje = "Se registró el proceso disciplinario. Se notificó al área Jurídica.";
        } else {
            $mensaje = "Llamado de atención registrado. Preparando plantilla...";
        }

        echo json_encode([
            'success' => true, 
            'mensaje' => $mensaje,
            'decision' => $decision,
            'causal' => $causal,
            'nombre' => $colaborador_nombre,
            'cedula' => $colaborador_cedula
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
    }
}