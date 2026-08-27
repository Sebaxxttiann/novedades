<?php
require_once 'config/config.php';
auth();

$causal = $_GET['causal'] ?? 'general';
$nombre = mb_strtoupper(htmlspecialchars($_GET['nombre'] ?? 'COLABORADOR'), 'UTF-8');
$cedula = htmlspecialchars($_GET['cedula'] ?? '000000');

$meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fecha_actual = date('d') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$nombre_creador = mb_strtoupper($_SESSION['usuario_nombre'], 'UTF-8');
$operacion_creador = $_SESSION['operacion_nombre'] ?? 'Central';

$stmt = $conn->prepare("SELECT descripcion FROM reportes_novedades WHERE colaborador_cedula = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$cedula]);
$reporte = $stmt->fetch();
$descripcion_hechos = $reporte ? nl2br(htmlspecialchars($reporte['descripcion'])) : '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . "://" . $host . $uri;

$logo_url = $base_url . '/uploads/actas/logo.png';
$sidebar_url = $base_url . '/uploads/actas/sidebar.png';
$footer_url = $base_url . '/uploads/actas/footer.png';

$asunto = "";
$contenido = "";

switch($causal) {
    case 'alcoholemia':
        $asunto = "LLAMADO DE ATENCIÓN POR NO ACATAR LAS INSTRUCCIONES IMPARTIDAS EN EL DESEMPEÑO DE SUS FUNCIONES RESPECTO AL SGSST";
        $contenido = "
            <p>Por medio del área de Gestión Humana de la compañía LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a que usted está incumpliendo normas de seguridad atentando contra su propia integridad física; ello se evidencia en los siguientes hechos reportados:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>La seguridad en el desempeño de sus funciones laborales es de estricto cumplimiento y ella hacen parte de los compromisos adquiridos en el momento que usted firma el contrato de trabajo con LOGISTICA INTELIGENTE SOLUTION SAS. Es su obligación como empleado ejecutar sus funciones laborales con sumo cuidado y atención y evitar accidentes laborales donde usted sea el más afectado. Y no menos importante evitar que nuestro cliente desconfié del servicio que se presta.</p>
            <p>Usted incumplió con lo pactado en su contrato de trabajo en su cláusula PRIMERA: OBJETO. “EL EMPLEADOR contrata los servicios personales de LA TRABAJADOR(A) y este se obliga: a) a poner al servicio de EL EMPLEADOR toda su capacidad normal de trabajo, en forma exclusiva en el desempeño de las funciones propias del oficio mencionado...”</p>
            <p>Por consiguiente, se hace este llamado de atención de conformidad al artículo 58 numerales 7 del Código Sustantivo del Trabajo, articulo 62 numerales 6 Y 9 del Código Sustantivo del Trabajo, Reglamento Interno de Trabajo en su CAPITULO XVI artículo 60 numerales 1, 11, 13, 16 y 37...</p>
            <br clear='all' style='page-break-before:always' />
            <p>Aprovechamos la oportunidad para invitarlo a reflexionar sobre su comportamiento y cumplir con las normas para evitar enfermedades laborales a futuro y que esto no afecte su parte cognitiva y mental. Le recuerdo que el alcohol afecta su capacidad de pensamiento, la memoria y la coordinación de actividades. Además de verse inmerso en procesos más drásticos.</p>
        ";
        break;

    case 'ausentismo':
        $asunto = "LLAMADO DE ATENCIÓN POR AUSENCIA INJUSTIFICADA.";
        $contenido = "
            <p>Por medio de la Gerencia de Gestión Humana Operaciones de la compañía LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a que usted faltó sin justa causa a sus labores. Los detalles de la novedad son los siguientes:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>Si bien es sabido, se aclara que usted fue programado para laborar por LOGISTICA INTELIGENTE SOLUTION SAS, con el tiempo suficiente y reglamentado, LIS actuó dentro del poder subordinante como su empleador con facultades por razones objetivas, es decir por la necesidad del servicio.</p>
            <p>Usted incumplió con lo pactado en su contrato de trabajo en su cláusula QUINTA: JORNADA DE TRABAJO. “Las partes acuerdan que EL TRABAJADOR prestará los servicios contratados por EL EMPLEADOR bajo la modalidad de trabajo por días, EL TRABAJADOR se obliga dentro de la jornada máxima laboral salvo estipulación expresa, a cumplir con los turnos y horarios que señale EL EMPLEADOR...”</p>
            <br clear='all' style='page-break-before:always' />
            <p>Por consiguiente, se hace este llamado de atención de conformidad al Reglamento Interno de Trabajo en su CAPITULO XIII PRESCRIPCIONES DE ORDEN artículo 55 numeral 8, articulo 60 numeral 1 y 12, articulo 62 numeral 4, articulo 58 OBLIGACIONES ESPECIALES DEL TRABAJADOR numeral 1 y el articulo 60 PROHIBICIÓN DE LOS TRABAJADORES en su numeral 4. del Código Sustantivo del Trabajo.</p>
            <p>Aprovechamos la oportunidad para invitarlo a que este tipo de comportamientos no sea recurrente ya que esto ocasiona retrasos en la operación y verse usted interferido en procesos mucho más drásticos.</p>
        ";
        break;

    case 'llegar_tarde':
        $asunto = "LLAMADO DE ATENCIÓN POR LLEGADA TARDE A LABORAR.";
        $contenido = "
            <p>Por medio de la Coordinación de Gestión Humana de la compañía LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a que se ha presentado tarde a trabajar sin justificación valedera, según el siguiente reporte:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>Le recordamos que la jornada laboral empieza en el horario establecido y programado.</p>
            <p>Se hace este llamado de atención de conformidad al Reglamento Interno de Trabajo en su artículo 67 Amonestación o Llamado de Atención Verbal/Escrita. De acuerdo a la falta señalada tipificada en el REGLAMENTO INTERNO DE TRABAJO en su artículo 68. Se establecen las siguientes clases de faltas leves y sus sanciones disciplinarías, así:</p>
            <p>El retardo hasta de Diez (10) minutos en la hora de entrada al trabajo sin excusa suficiente, cuando no cause perjuicio de consideración a LIS, implica por primera vez, llamado de atención verbal; por la segunda vez llamado de atención escrito; por la tercera vez, suspensión en el trabajo hasta por tres (3) días...</p>
            <br clear='all' style='page-break-before:always' />
            <p>Aprovechamos la oportunidad para invitarlo a que este tipo de comportamientos no sea recurrente ya que esto ocasiona retrasos en la operación y verse usted interferido en procesos mucho más drásticos.</p>
        ";
        break;

    case 'no_acatar_ordenes':
        $asunto = "LLAMADO DE ATENCIÓN POR NO ACATAR LAS INSTRUCCIONES IMPARTIDAS EN EL DESEMPEÑO DE SUS FUNCIONES";
        $contenido = "
            <p>Por medio del área de Gestión Humana de la sociedad LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a que usted está incumpliendo con las ordenes e instrucciones impartidas; ello se evidencia en el siguiente reporte:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>Las ordenes e instrucciones en el desempeño de sus funciones laborales es de estricto cumplimiento y ella hacen parte de los compromisos adquiridos en el momento que usted firma el contrato de trabajo con LOGISTICA INTELIGENTE SOLUTION SAS. Es su obligación como empleado ejecutar las ordenes e instrucciones que se le dan.</p>
            <p>Usted incumplió con lo pactado en su contrato de trabajo en su cláusula PRIMERA: OBJETO. “EL EMPLEADOR contrata los servicios personales de LA TRABAJADOR(A) y este se obliga: a) a poner al servicio de EL EMPLEADOR toda su capacidad normal de trabajo...”</p>
            <br clear='all' style='page-break-before:always' />
            <p>Por consiguiente, se hace este llamado de atención de conformidad al artículo 58 numerales 1 del Código Sustantivo del Trabajo, Reglamento Interno de Trabajo en su CAPITULO XVI artículo 60 numerales 1, 11, 13, 16 y 37 CAPITULO XVII PROHIBICIONES ESPECIALES PARA LOS TRABAJADORES articulo 62 numeral 17, 38 y 73.</p>
            <p>Aprovechamos la oportunidad para invitarlo a que este tipo de comportamientos no sea recurrente ya que esto ocasiona inconformidad con nuestro cliente y retrasos en los procesos internos administrativos.</p>
        ";
        break;

    case 'no_marcar':
        $asunto = "LLAMADO DE ATENCIÓN POR NO MARCACIÓN CONTROL DE INGRESO Y SALIDA (BIOMETRICO)";
        $contenido = "
            <p>Por medio del área de Administración de Personal de la compañía LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a novedades en sus marcaciones de ingreso/salida:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>Es su obligación como empleado de LIS hacer la marcación tanto de ingreso a la jornada laboral como el egreso de la misma. Con esa falta cometida ha generado a nuestro cliente insatisfacción en el servicio prestado por tanto queja por la falta de control a nuestros trabajadores.</p>
            <p>Usted incumplió con lo pactado en su contrato de trabajo en su cláusula PRIMERA: OBJETO. “EL EMPLEADOR contrata los servicios personales de LA TRABAJADOR(A) y este se obliga: a) a poner al servicio de EL EMPLEADOR toda su capacidad normal de trabajo...”</p>
            <br clear='all' style='page-break-before:always' />
            <p>Por consiguiente, se hace este llamado de atención de conformidad al Reglamento Interno de Trabajo en su CAPITULO XVI artículo 60 numeral 24 y 33; CAPITULO XVII articulo 62 numeral 17 y 25 y articulo 62 numerales 6 del Código Sustantivo del Trabajo.</p>
            <p>Aprovechamos la oportunidad para invitarlo a que este tipo de comportamientos no sea recurrente ya que esto ocasiona Inconformidad con nuestro cliente y retrasos en los procesos internos administrativos.</p>
        ";
        break;

    case 'bajo_rendimiento':
        $asunto = "LLAMADO DE ATENCIÓN POR DEFICIENTE RENDIMIENTO EN EL TRABAJO CON RELACIÓN A SU CAPACIDAD LABORAL.";
        $contenido = "
            <p>Por medio del área de Gestión Humana de la compañía LOGISTICA INTELIGENTE SOLUTION SAS, le hacemos un llamado de atención debido a las novedades detectadas en su área de labor, las cuales reflejan un incumplimiento a los estándares establecidos por la compañía:</p>
            <p style='background-color: #f9f9f9; padding: 10px; border-left: 3px solid #ccc;'>{$descripcion_hechos}</p>
            <p>Es importante resaltar que es su obligación como empleado(a) ejecutar sus funciones laborales con sumo cuidado y atención y evitar reprocesos en el equipo de trabajo, y no menos importante evitar que nuestro cliente desconfié del servicio que se presta.</p>
            <br clear='all' style='page-break-before:always' />
            <p>Usted incumplió con lo pactado en su contrato de trabajo en su cláusula PRIMERA: OBJETO. Por consiguiente, se hace este llamado de atención de conformidad al Reglamento Interno de Trabajo en su CAPITULO XVI artículo 60 numerales 1, 11, 13, 16 y 37 CAPITULO XVII PROHIBICIONES ESPECIALES PARA LOS TRABAJADORES articulo 62 numeral 31, articulo 62 numerales 6 Y 9 del Código Sustantivo del Trabajo y el artículo 7.3 inciso E de la Circular Normativa.</p>
            <p>Aprovechamos la oportunidad para invitarlo a que este tipo de comportamientos no sea recurrente ya que esto ocasiona inconformidad con nuestro cliente y retrasos en los procesos internos administrativos y verse usted interferido (a) en procesos mucho más drásticos.</p>
        ";
        break;

    default:
        $asunto = "LLAMADO DE ATENCIÓN DISCIPLINARIO.";
        $contenido = "<p>Por medio de la presente se le notifica el siguiente reporte disciplinario:</p><p>{$descripcion_hechos}</p>";
        break;
}

$html = "
<html xmlns:v='urn:schemas-microsoft-com:vml'
xmlns:o='urn:schemas-microsoft-com:office:office'
xmlns:w='urn:schemas-microsoft-com:office:word'
xmlns:m='http://schemas.microsoft.com/office/2004/12/omml'
xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset='utf-8'>
<title>Llamado de Atencion LIS</title>
<style>
@page Section1 {
    size: 8.5in 11.0in;
    margin: 1.5in 1.0in 1.5in 1.3in;
    mso-header-margin: 0.5in;
    mso-footer-margin: 0.2in;
    mso-header: h1;
    mso-footer: f1;
}
div.Section1 { page: Section1; }
body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #000; text-align: justify; }
p { line-height: 1.15; margin-bottom: 12px; margin-top: 0; }
.firma-linea { border-top: 1px solid #000; width: 300px; padding-top: 5px; }
</style>
</head>
<body>

<div style='mso-element:header' id='h1'>
    <img src='{$sidebar_url}' style='position:absolute; z-index:-1; margin-left:-1.3in; margin-top:-0.5in; width:65px; height:11in;'>
    <table width='100%' border='0' cellpadding='0' cellspacing='0' style='margin-bottom: 20px;'>
        <tr>
            <td width='60%' valign='top'>
                <img src='{$logo_url}' width='180'>
            </td>
            <td width='40%' align='right' valign='top' style='padding-top: 15px;'>
                <span style='font-size:10pt;'>{$operacion_creador}, {$fecha_actual}</span>
            </td>
        </tr>
    </table>
</div>

<div style='mso-element:footer' id='f1'>
    <img src='{$footer_url}' style='position:absolute; margin-left:-1.3in; width:8.5in; height:auto;'>
</div>

<div class='Section1'>
    <br><br>
    <p>Señor(a)<br>
    <strong>{$nombre}</strong><br>
    <strong>C.C. No. {$cedula}</strong><br>
    <strong>COLABORADOR - LIS</strong><br>
    Ciudad.</p>
    <br>
    <p>ASUNTO: &nbsp;&nbsp;&nbsp; <strong>{$asunto}</strong></p>
    <br>
    <p>Cordial saludo,</p>
    {$contenido}
    <p>Se hace este llamado de atención para aportar copia a la hoja de vida.</p>
    <br>
    <p>Agradeciendo su valiosa atención,</p>
    <br><br><br>
    <div class='firma-linea'>
        <strong>{$nombre_creador}</strong><br>
        Gestión Operativa / Talento Humano<br>
        LOGISTICA INTELIGENTE SOLUTION SAS<br>
        NIT: 900966185 – 4
    </div>
    <br><br><br>
    <p>Notificado(a),</p>
    <br><br><br>
    <div class='firma-linea'>
        <strong>{$nombre}</strong><br>
        C.C. No. {$cedula}<br>
        COLABORADOR - LIS
    </div>
    <br>
    <p style='font-size: 8pt; color: #555;'><em>Nota: Autenticidad del presente documento en razón a la ley 2213 de 2022, por medio de la cual se establece la vigencia permanente del Decreto Legislativo 806 de 2020; artículo 7 de la ley 527 de 1999.</em></p>
</div>

</body>
</html>
";

$directorio_actas = __DIR__ . '/uploads/actas/';
if (!is_dir($directorio_actas)) {
    mkdir($directorio_actas, 0777, true);
}

$nombre_archivo = "Llamado_Atencion_{$cedula}_" . time() . ".doc";
$ruta_completa = $directorio_actas . $nombre_archivo;

file_put_contents($ruta_completa, $html);

header("Content-type: application/vnd.ms-word");
header("Content-Disposition: attachment;Filename={$nombre_archivo}");
echo $html;
exit;