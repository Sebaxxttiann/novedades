<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
auth(['normal']);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

const HC_MAX_TEXTO = 150;
const HC_MAX_FILAS = 5000;
const HC_MAX_BYTES = 5 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_json(['success' => false, 'mensaje' => 'Método no permitido.'], 405);
}

$usuario_id = (int) $_SESSION['usuario_id'];
$operacion_id = (int) ($_SESSION['operacion_id'] ?? 0);
if ($operacion_id <= 0) {
    responder_json(['success' => false, 'mensaje' => 'Su usuario no tiene una operación asignada.'], 403);
}

limitar('headcount:' . $usuario_id, 60, 60);

$accion = $_POST['accion'] ?? '';

function hc_validar(string $nombre, string $cedula, string $cargo): ?string
{
    if ($nombre === '' || $cedula === '' || $cargo === '') {
        return 'Todos los campos son obligatorios.';
    }
    if (mb_strlen($nombre, 'UTF-8') > HC_MAX_TEXTO) {
        return 'El nombre no puede superar los ' . HC_MAX_TEXTO . ' caracteres.';
    }
    if (mb_strlen($cargo, 'UTF-8') > HC_MAX_TEXTO) {
        return 'El cargo no puede superar los ' . HC_MAX_TEXTO . ' caracteres.';
    }
    if (!preg_match('/^\d{5,15}$/', $cedula)) {
        return 'La cédula debe contener solo dígitos (entre 5 y 15).';
    }
    return null;
}

function hc_es_duplicado(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? 0) === 1062;
}

function hc_obtener(PDO $conn, int $id, int $operacion_id): ?array
{
    $stmt = $conn->prepare("SELECT id, nombre, cedula, cargo, fecha_creacion FROM headcount WHERE id = ? AND operacion_id = ?");
    $stmt->execute([$id, $operacion_id]);
    return $stmt->fetch() ?: null;
}

function hc_celda($valor): string
{
    if (is_float($valor)) {
        return trim(sprintf('%.0f', $valor));
    }
    return trim((string) $valor);
}

if ($accion === 'agregar') {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $cedula = trim((string) ($_POST['cedula'] ?? ''));
    $cargo = trim((string) ($_POST['cargo'] ?? ''));

    $error = hc_validar($nombre, $cedula, $cargo);
    if ($error) {
        responder_json(['success' => false, 'mensaje' => $error], 422);
    }

    try {
        $stmt = $conn->prepare("INSERT INTO headcount (nombre, cedula, cargo, operacion_id, creado_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $cedula, $cargo, $operacion_id, $usuario_id]);
        $colaborador = hc_obtener($conn, (int) $conn->lastInsertId(), $operacion_id);
        responder_json(['success' => true, 'mensaje' => 'Colaborador agregado al headcount correctamente.', 'colaborador' => $colaborador]);
    } catch (PDOException $e) {
        if (hc_es_duplicado($e)) {
            responder_json(['success' => false, 'mensaje' => 'Ya existe un colaborador con esa cédula en su operación.'], 409);
        }
        error_log('headcount agregar: ' . $e->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo guardar el colaborador. Intente de nuevo.'], 500);
    }
}

if ($accion === 'editar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $cedula = trim((string) ($_POST['cedula'] ?? ''));
    $cargo = trim((string) ($_POST['cargo'] ?? ''));

    if ($id <= 0) {
        responder_json(['success' => false, 'mensaje' => 'Colaborador no válido.'], 422);
    }
    $error = hc_validar($nombre, $cedula, $cargo);
    if ($error) {
        responder_json(['success' => false, 'mensaje' => $error], 422);
    }
    if (!hc_obtener($conn, $id, $operacion_id)) {
        responder_json(['success' => false, 'mensaje' => 'El colaborador no existe en su operación.'], 404);
    }

    try {
        $stmt = $conn->prepare("UPDATE headcount SET nombre = ?, cedula = ?, cargo = ? WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$nombre, $cedula, $cargo, $id, $operacion_id]);
        responder_json(['success' => true, 'mensaje' => 'Colaborador actualizado correctamente.', 'colaborador' => hc_obtener($conn, $id, $operacion_id)]);
    } catch (PDOException $e) {
        if (hc_es_duplicado($e)) {
            responder_json(['success' => false, 'mensaje' => 'Ya existe otro colaborador con esa cédula en su operación.'], 409);
        }
        error_log('headcount editar: ' . $e->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo actualizar el colaborador. Intente de nuevo.'], 500);
    }
}

if ($accion === 'cargar_excel') {
    limitar('headcount_excel:' . $usuario_id, 10, 300);

    $archivo = $_FILES['archivo_excel'] ?? null;
    if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
        responder_json(['success' => false, 'mensaje' => 'Debe seleccionar un archivo Excel válido.'], 422);
    }
    if ($archivo['size'] > HC_MAX_BYTES) {
        responder_json(['success' => false, 'mensaje' => 'El archivo supera el tamaño máximo de 5 MB.'], 422);
    }
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'], true)) {
        responder_json(['success' => false, 'mensaje' => 'El archivo debe tener formato .xlsx o .xls.'], 422);
    }

    try {
        $lector = IOFactory::createReader($ext === 'xls' ? 'Xls' : 'Xlsx');
        if (!$lector->canRead($archivo['tmp_name'])) {
            responder_json(['success' => false, 'mensaje' => 'El contenido del archivo no corresponde a un Excel válido.'], 422);
        }

        $info = $lector->listWorksheetInfo($archivo['tmp_name']);
        if (empty($info) || (int) $info[0]['totalRows'] - 1 > HC_MAX_FILAS) {
            responder_json(['success' => false, 'mensaje' => 'El archivo supera el máximo de ' . HC_MAX_FILAS . ' filas.'], 422);
        }

        $lector->setReadDataOnly(true);
        $lector->setLoadSheetsOnly([$info[0]['worksheetName']]);
        $lector->setReadFilter(new class implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return in_array($columnAddress, ['A', 'B', 'C'], true) && $row <= HC_MAX_FILAS + 1;
            }
        });
        $hoja = $lector->load($archivo['tmp_name'])->getActiveSheet();

        $filas = $hoja->toArray(null, false, false, false);
    } catch (Throwable $e) {
        error_log('headcount excel lectura: ' . $e->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo leer el archivo. Verifique que sea un Excel válido.'], 422);
    }

    $insertados = 0;
    $actualizados = 0;
    $omitidos = 0;
    $errores = [];

    try {
        $conn->beginTransaction();

        $stmtBuscar = $conn->prepare("SELECT id FROM headcount WHERE cedula = ? AND operacion_id = ?");
        $stmtInsert = $conn->prepare("INSERT INTO headcount (nombre, cedula, cargo, operacion_id, creado_por) VALUES (?, ?, ?, ?, ?)");
        $stmtUpdate = $conn->prepare("UPDATE headcount SET nombre = ?, cargo = ? WHERE id = ? AND operacion_id = ?");

        foreach ($filas as $i => $fila) {
            if ($i === 0) {
                continue;
            }

            $nombre = hc_celda($fila[0] ?? '');
            $cedula = hc_celda($fila[1] ?? '');
            $cargo = hc_celda($fila[2] ?? '');

            if ($nombre === '' && $cedula === '' && $cargo === '') {
                continue;
            }

            $error = hc_validar($nombre, $cedula, $cargo);
            if ($error) {
                $omitidos++;
                $errores[] = 'Fila ' . ($i + 1) . ': ' . $error;
                continue;
            }

            $stmtBuscar->execute([$cedula, $operacion_id]);
            $existente = $stmtBuscar->fetchColumn();

            if ($existente) {
                $stmtUpdate->execute([$nombre, $cargo, $existente, $operacion_id]);
                $actualizados++;
            } else {
                $stmtInsert->execute([$nombre, $cedula, $cargo, $operacion_id, $usuario_id]);
                $insertados++;
            }
        }

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('headcount excel carga: ' . $e->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo procesar el archivo. No se guardó ningún cambio.'], 500);
    }

    $mensaje = "Carga finalizada: {$insertados} nuevos, {$actualizados} actualizados";
    if ($omitidos > 0) {
        $mensaje .= ", {$omitidos} omitidos por datos inválidos";
    }
    responder_json(['success' => true, 'mensaje' => $mensaje . '.', 'errores' => array_slice($errores, 0, 100)]);
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        responder_json(['success' => false, 'mensaje' => 'Colaborador no válido.'], 422);
    }

    try {
        $stmt = $conn->prepare("DELETE FROM headcount WHERE id = ? AND operacion_id = ?");
        $stmt->execute([$id, $operacion_id]);
        if ($stmt->rowCount() === 0) {
            responder_json(['success' => false, 'mensaje' => 'El colaborador no existe en su operación.'], 404);
        }
        responder_json(['success' => true, 'mensaje' => 'Registro eliminado correctamente.']);
    } catch (PDOException $e) {
        error_log('headcount eliminar: ' . $e->getMessage());
        responder_json(['success' => false, 'mensaje' => 'No se pudo eliminar el registro. Intente de nuevo.'], 500);
    }
}

responder_json(['success' => false, 'mensaje' => 'Acción no reconocida.'], 400);
