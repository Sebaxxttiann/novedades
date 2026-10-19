<?php

function obtenerAnalitica(PDO $conn, array $operacionIds): array
{
    $conds = [];
    $params = [];

    if (!empty($operacionIds)) {
        $placeholders = implode(',', array_fill(0, count($operacionIds), '?'));
        $conds[] = "r.operacion_id IN ($placeholders)";
        $params = $operacionIds;
    }

    $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    $whereMes = $conds
        ? 'WHERE ' . implode(' AND ', array_merge($conds, ["r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)"]))
        : 'WHERE r.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)';

    $stmt = $conn->prepare("SELECT COUNT(*) AS total,
            SUM(r.decision = 'llamado_atencion') AS llamados,
            SUM(r.decision = 'proceso_disciplinario') AS procesos
        FROM reportes_novedades r $where");
    $stmt->execute($params);
    $kpi = $stmt->fetch();

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM (
            SELECT r.colaborador_cedula FROM reportes_novedades r $where
            GROUP BY r.colaborador_cedula HAVING COUNT(*) > 1
        ) t");
    $stmt->execute($params);
    $reincidentes = (int) $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT DATE_FORMAT(r.fecha_creacion, '%Y-%m') AS mes,
            SUM(r.decision = 'llamado_atencion') AS llamados,
            SUM(r.decision = 'proceso_disciplinario') AS procesos
        FROM reportes_novedades r
        $whereMes
        GROUP BY mes ORDER BY mes ASC");
    $stmt->execute($params);
    $porMesRaw = $stmt->fetchAll();

    $meses = [];
    for ($i = 11; $i >= 0; $i--) {
        $clave = date('Y-m', strtotime("-$i months"));
        $meses[$clave] = ['mes' => $clave, 'llamados' => 0, 'procesos' => 0];
    }
    foreach ($porMesRaw as $fila) {
        if (isset($meses[$fila['mes']])) {
            $meses[$fila['mes']]['llamados'] = (int) $fila['llamados'];
            $meses[$fila['mes']]['procesos'] = (int) $fila['procesos'];
        }
    }

    $stmt = $conn->prepare("SELECT o.nombre AS operacion,
            SUM(r.decision = 'llamado_atencion') AS llamados,
            SUM(r.decision = 'proceso_disciplinario') AS procesos,
            COUNT(*) AS total
        FROM reportes_novedades r
        JOIN operaciones o ON o.id = r.operacion_id
        $where
        GROUP BY o.id, o.nombre
        ORDER BY total DESC");
    $stmt->execute($params);
    $porOperacion = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT r.colaborador_cedula AS cedula, MAX(r.colaborador_nombre) AS nombre, COUNT(*) AS total
        FROM reportes_novedades r
        $where
        GROUP BY r.colaborador_cedula
        HAVING total > 1
        ORDER BY total DESC
        LIMIT 8");
    $stmt->execute($params);
    $topColaboradores = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT r.tipo_infraccion AS tipo, COUNT(*) AS total
        FROM reportes_novedades r
        $where
        GROUP BY r.tipo_infraccion");
    $stmt->execute($params);
    $porTipo = $stmt->fetchAll();

    return [
        'kpi' => [
            'total' => (int) $kpi['total'],
            'llamados' => (int) $kpi['llamados'],
            'procesos' => (int) $kpi['procesos'],
            'reincidentes' => $reincidentes,
        ],
        'porMes' => array_values($meses),
        'porOperacion' => $porOperacion,
        'topColaboradores' => $topColaboradores,
        'porTipo' => $porTipo,
    ];
}
