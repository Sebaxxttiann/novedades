<?php
require_once 'config/config.php';
auth();

$nombre = htmlspecialchars($_GET['nombre'] ?? 'Colaborador');
$cedula = htmlspecialchars($_GET['cedula'] ?? '000000');
$fecha_ejecucion = htmlspecialchars($_GET['fecha'] ?? date('Y-m-d'));
$fecha_actual = date('d de M de Y');

// Forzar la descarga en formato Word (.doc)
header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename=Citacion_Descargos_{$cedula}.doc");

?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head><meta charset="utf-8"><title>Citación a Descargos</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">

    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="margin: 0;">CITACIÓN A DILIGENCIA DE DESCARGOS</h2>
        <p style="margin: 0; color: #555;"><strong>Departamento Jurídico y Gestión Humana</strong></p>
    </div>

    <p><strong>Fecha de Emisión:</strong> <?= $fecha_actual ?></p>
    <p><strong>Señor(a):</strong> <?= $nombre ?></p>
    <p><strong>Cédula de Ciudadanía:</strong> <?= $cedula ?></p>

    <hr style="margin: 20px 0;">

    <p>Estimado(a) colaborador(a),</p>
    
    <p style="text-align: justify;">
        Por medio de la presente comunicación, el Área Jurídica le informa que se ha iniciado un <strong>Proceso Disciplinario</strong> en su contra debido a los reportes de novedades registrados recientemente en la operación.
    </p>

    <p style="text-align: justify;">
        Garantizando su derecho a la defensa y al debido proceso, se le cita formalmente a diligencia de descargos, la cual ha sido programada y confirmada en el sistema para la siguiente fecha:
    </p>

    <div style="background-color: #f4f4f4; border: 1px solid #ccc; padding: 15px; text-align: center; margin: 20px 0;">
        <h3 style="margin: 0; color: #b91c1c;">FECHA DE DILIGENCIA: <?= date('d/m/Y', strtotime($fecha_ejecucion)) ?></h3>
    </div>

    <p style="text-align: justify;">
        Le recordamos que su asistencia es de carácter <strong>obligatorio</strong>. En caso de no presentarse sin una justa causa debidamente comprobada, el proceso continuará su curso y la empresa tomará la decisión administrativa correspondiente con base en las pruebas aportadas en el expediente.
    </p>

    <p>Atentamente,</p>

    <br><br>
    <p>___________________________________</p>
    <p><strong>Área Jurídica / Gestión Humana</strong></p>

</body>
</html> 