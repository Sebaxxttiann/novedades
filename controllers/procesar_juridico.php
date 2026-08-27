<?php
require_once '../config/config.php';
date_default_timezone_set('America/Bogota'); 
auth();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $accion = $_POST['accion'] ?? '';
    $reporte_id = $_POST['reporte_id'] ?? 0;

    try {
        if($accion === 'ejecutar_fecha') {
            $fecha_citacion = $_POST['fecha_citacion'];
            $nombre = $_POST['colaborador_nombre'];
            $cedula = $_POST['colaborador_cedula'];

            $stmt = $conn->prepare("UPDATE reportes_novedades SET estado = 'citado', fecha_citacion = ? WHERE id = ?");
            $stmt->execute([$fecha_citacion, $reporte_id]);

            echo json_encode([
                'success' => true, 
                'mensaje' => 'Fecha asignada correctamente.',
                'nombre' => $nombre,
                'cedula' => $cedula,
                'fecha_citacion' => $fecha_citacion
            ]);

        } elseif($accion === 'actualizar_estatus') {
            $nuevo_estatus = $_POST['nuevo_estatus'];
            
            $stmt = $conn->prepare("UPDATE reportes_novedades SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estatus, $reporte_id]);

            echo json_encode(['success' => true, 'mensaje' => 'Estatus del proceso actualizado.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error en la base de datos.']);
    }
}
?>