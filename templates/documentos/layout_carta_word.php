<?php
/**
 * Layout de carta compatible con Word (encabezado/pie mso) para los llamados de atención.
 * Requiere en scope: $sidebar_url, $logo_url, $operacion_creador, $fecha_actual,
 *                     $nombre, $cedula, $asunto, $contenido, $nombre_creador, $footer_url
 * Produce: $html
 */

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
