<?php
require_once 'config/config.php';
date_default_timezone_set('America/Bogota'); // HORA COLOMBIA
auth();

if($_SESSION['usuario_rol'] !== 'juridico' && $_SESSION['usuario_rol'] !== 'admin') { 
    header("Location: dashboard.php"); 
    exit; 
}

include 'header.php';

// Cruzamos con la tabla operaciones para traer el nombre de la sede
$stmt = $conn->prepare("
    SELECT r.*, o.nombre as operacion_nombre 
    FROM reportes_novedades r 
    LEFT JOIN operaciones o ON r.operacion_id = o.id 
    WHERE r.decision = 'proceso_disciplinario' 
    ORDER BY r.id DESC
");
$stmt->execute();
$reportes = $stmt->fetchAll();

// Fecha mínima permitida (Hoy + 5 días)
$fecha_minima = date('Y-m-d', strtotime('+5 days'));
?>

<style>
    /* Estilos Enterprise */
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .page-title h1 { font-size: 1.6rem; font-weight: 600; color: var(--text-main); letter-spacing: -0.5px; }
    .page-title p { color: var(--text-muted); font-size: 0.9rem; margin-top: 2px; }

    .table-card { background: var(--surface); border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; width: 100%; }
    .custom-table { width: 100%; border-collapse: collapse; text-align: left; }
    .custom-table th { background: #f8fafc; padding: 1rem 1.2rem; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 1px solid var(--border); }
    .custom-table td { padding: 1rem 1.2rem; border-bottom: 1px solid var(--border); font-size: 0.95rem; vertical-align: middle; }
    .custom-table tbody tr:hover { background-color: #f8fafc; }

    .user-info { display: flex; align-items: center; gap: 12px; }
    .avatar-circle { width: 40px; height: 40px; border-radius: 50%; background: #e2e8f0; color: var(--text-main); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .user-details strong { color: var(--text-main); font-weight: 600; display: block; }
    .user-details span { color: var(--text-muted); font-size: 0.8rem; font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block; }

    .badge { display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
    .badge-registrado { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    .badge-citado { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
    .badge-cerrado { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce3; }
    .badge-op { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; font-size: 0.7rem; padding: 2px 6px; }

    .action-btn { background: white; border: 1px solid var(--border); padding: 6px 10px; border-radius: 6px; cursor: pointer; color: var(--text-main); font-weight: 500; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px; transition: 0.2s; }
    .action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .btn-exec { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
    .btn-exec:hover { background: #dbeafe; }
    
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
    .detail-item { background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid var(--border); }
    .detail-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
    .detail-value { font-size: 0.95rem; color: var(--text-main); font-weight: 500; }
    
    /* Contador CSS */
    .countdown-timer { margin-top: 8px; font-size: 0.75rem; font-family: monospace; font-weight: 600; color: #c2410c; background: #fff7ed; padding: 4px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #ffedd5; }
    .countdown-timer.expired { color: #15803d; background: #f0fdf4; border-color: #dcfce3; }
</style>

<div class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>Panel Jurídico</h1>
            <p>Gestión, revisión y ejecución de procesos disciplinarios.</p>
        </div>
    </div>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Infractor / Sede</th>
                    <th>Infracción / Gravedad</th>
                    <th>Estatus Actual</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reportes as $r): 
                    $iniciales = strtoupper(substr($r['colaborador_nombre'], 0, 2));
                    
                    $estadoClass = 'badge-registrado';
                    $estadoIcon = 'clock';
                    if($r['estado'] === 'citado') { $estadoClass = 'badge-citado'; $estadoIcon = 'calendar-check'; }
                    elseif($r['estado'] !== 'registrado') { $estadoClass = 'badge-cerrado'; $estadoIcon = 'check-circle'; }
                ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="avatar-circle"><?= $iniciales ?></div>
                            <div class="user-details">
                                <strong><?= htmlspecialchars($r['colaborador_nombre']) ?></strong>
                                <span>CC: <?= htmlspecialchars($r['colaborador_cedula']) ?></span>
                                <span class="badge-op"><i data-lucide="map-pin" style="width: 10px;"></i> <?= htmlspecialchars($r['operacion_nombre'] ?? 'Global') ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 0.9rem; text-transform: capitalize; font-weight: 500;"><?= str_replace('_', ' ', $r['tipo_infraccion']) ?></div>
                        <?php if($r['falta_grave']): ?>
                            <span style="font-size: 0.7rem; background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; margin-top: 4px; display: inline-block;">Falta Grave</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $estadoClass ?>">
                            <i data-lucide="<?= $estadoIcon ?>" style="width: 14px; height: 14px;"></i>
                            <?= str_replace('_', ' ', $r['estado']) ?>
                        </span>
                        
                        <!-- Si el estado es citado y hay fecha, mostramos el contador -->
                        <?php if($r['estado'] === 'citado' && !empty($r['fecha_citacion'])): ?>
                            <br>
                            <!-- Se envía la fecha con zona horaria de Colombia UTC-5 -->
                            <div class="countdown-timer" data-fecha="<?= $r['fecha_citacion'] ?>T08:00:00-05:00">
                                <i data-lucide="timer" style="width: 12px; height: 12px;"></i> Calculando...
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="action-btn" onclick="abrirModalVer(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)">
                                <i data-lucide="eye" style="width: 14px;"></i> Ver
                            </button>
                            
                            <!-- SOLO MUESTRA EL BOTÓN DE FECHA SI AÚN NO HA SIDO CITADO -->
                            <?php if(empty($r['fecha_citacion']) || $r['estado'] === 'registrado'): ?>
                                <button class="action-btn btn-exec" onclick="abrirModalEjecutar(<?= $r['id'] ?>, '<?= htmlspecialchars($r['colaborador_nombre']) ?>', '<?= htmlspecialchars($r['colaborador_cedula']) ?>')">
                                    <i data-lucide="calendar" style="width: 14px;"></i> Fecha
                                </button>
                            <?php endif; ?>
                            
                            <button class="action-btn" onclick="abrirModalEstatus(<?= $r['id'] ?>)">
                                <i data-lucide="activity" style="width: 14px;"></i> Cierre
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 0: Ver Detalles -->
<div class="modal-overlay" id="modalVer">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2>Expediente del Proceso</h2>
            <button type="button" class="close-modal" onclick="toggleModal('modalVer', false)"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Colaborador Involucrado</div>
                    <div class="detail-value" id="ver_nombre"></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">CC: <span id="ver_cedula"></span></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Sede / Operación</div>
                    <div class="detail-value" style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="map-pin" style="width: 16px; color: var(--primary);"></i>
                        <span id="ver_operacion"></span>
                    </div>
                </div>
            </div>

            <div class="detail-grid" style="margin-bottom: 1rem;">
                <div class="detail-item">
                    <div class="detail-label">Tipo de Infracción</div>
                    <div class="detail-value" id="ver_infraccion" style="text-transform: capitalize;"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Evaluación Preliminar</div>
                    <div class="detail-value" style="display: flex; gap: 8px;">
                        <span id="badge_reincidente" style="font-size: 0.75rem; padding: 2px 6px; border-radius: 4px;"></span>
                        <span id="badge_gravedad" style="font-size: 0.75rem; padding: 2px 6px; border-radius: 4px;"></span>
                    </div>
                </div>
            </div>

            <div class="detail-item" style="margin-bottom: 1.5rem;">
                <div class="detail-label">Observación / Descripción de los Hechos</div>
                <div class="detail-value" id="ver_descripcion" style="margin-top: 8px; line-height: 1.5; font-size: 0.9rem; white-space: pre-wrap;"></div>
            </div>

            <div id="contenedor_evidencia" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; border-radius: 8px; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px; color: #15803d;">
                    <i data-lucide="paperclip"></i>
                    <span style="font-weight: 500; font-size: 0.9rem;">Evidencia Adjunta Disponible</span>
                </div>
                <a id="ver_evidencia" href="#" target="_blank" class="btn btn-primary" style="background: #166534; padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                    Ver Archivo
                </a>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="toggleModal('modalVer', false)">Cerrar Expediente</button>
        </div>
    </div>
</div>

<!-- Modal 1: Ejecutar Proceso (Agendar Fecha) -->
<div class="modal-overlay" id="modalEjecutar">
    <div class="modal-content" style="max-width: 450px;">
        <form id="formEjecutar">
            <div class="modal-header">
                <h2>Ejecutar Proceso</h2>
                <button type="button" class="close-modal" onclick="toggleModal('modalEjecutar', false)"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Agende la diligencia de descargos para <strong><span id="txtNombreEjecutar"></span></strong>.
                </p>
                <input type="hidden" name="reporte_id" id="exec_reporte_id">
                <input type="hidden" name="colaborador_nombre" id="exec_nombre">
                <input type="hidden" name="colaborador_cedula" id="exec_cedula">
                <input type="hidden" name="accion" value="ejecutar_fecha">

                <div class="form-group">
                    <label class="form-label">Fecha de Citación a Descargos</label>
                    <input type="date" name="fecha_citacion" class="form-control" min="<?= $fecha_minima ?>" required>
                    <div style="display: flex; gap: 8px; margin-top: 8px; color: #0369a1; background: #f0f9ff; padding: 8px; border-radius: 6px;">
                        <i data-lucide="info" style="width: 16px; flex-shrink: 0;"></i>
                        <p style="font-size: 0.8rem; margin: 0;">Por ley, la fecha seleccionada no puede ser menor a 5 días a partir de hoy.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modalEjecutar', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary">Confirmar y Generar Plantilla</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Actualizar Estatus de Cierre -->
<div class="modal-overlay" id="modalEstatus">
    <div class="modal-content" style="max-width: 450px;">
        <form id="formEstatus">
            <div class="modal-header">
                <h2>Actualizar Estatus</h2>
                <button type="button" class="close-modal" onclick="toggleModal('modalEstatus', false)"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="reporte_id" id="estatus_reporte_id">
                <input type="hidden" name="accion" value="actualizar_estatus">

                <div class="form-group">
                    <label class="form-label">Decisión Final / Estatus</label>
                    <select name="nuevo_estatus" class="form-control" required>
                        <option value="">Seleccione el resultado...</option>
                        <option value="llamado_atencion_escrito">Llamado de atención escrito</option>
                        <option value="suspension_dias">Suspensión de días</option>
                        <option value="terminacion_contrato">Terminación de contrato</option>
                        <option value="archivado_sin_merito">Archivado (Sin mérito)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('modalEstatus', false)">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="background: #15803d;">Guardar Estatus</button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    // Motor del Contador en Tiempo Real (Hora Colombia)
    function actualizarContadores() {
        const contadores = document.querySelectorAll('.countdown-timer');
        const ahora = new Date().getTime(); // Hora local del dispositivo del usuario
        
        contadores.forEach(cont => {
            const fechaDestinoStr = cont.getAttribute('data-fecha');
            if(!fechaDestinoStr) return;
            
            const fechaDestino = new Date(fechaDestinoStr).getTime();
            const distancia = fechaDestino - ahora;
            
            if (distancia < 0) {
                cont.innerHTML = `<i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Diligencia en proceso/cumplida`;
                cont.classList.add('expired');
            } else {
                const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
                const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
                const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
                
                cont.innerHTML = `<i data-lucide="timer" style="width: 12px; height: 12px;"></i> Faltan: ${dias}d ${horas}h ${minutos}m ${segundos}s`;
            }
        });
        // Refrescar los íconos inyectados
        lucide.createIcons();
    }
    // Inicializar el reloj cada segundo
    setInterval(actualizarContadores, 1000);
    actualizarContadores();

    function abrirModalVer(data) {
        document.getElementById('ver_nombre').textContent = data.colaborador_nombre;
        document.getElementById('ver_cedula').textContent = data.colaborador_cedula;
        document.getElementById('ver_operacion').textContent = data.operacion_nombre || 'Global';
        document.getElementById('ver_infraccion').textContent = data.tipo_infraccion.replace('_', ' ');
        document.getElementById('ver_descripcion').textContent = data.descripcion;

        const bReincidente = document.getElementById('badge_reincidente');
        if(data.reincidente == 1) {
            bReincidente.textContent = 'Reincidente';
            bReincidente.style.background = '#fef3c7'; bReincidente.style.color = '#92400e';
        } else {
            bReincidente.textContent = '1ra Vez';
            bReincidente.style.background = '#f3f4f6'; bReincidente.style.color = '#4b5563';
        }

        const bGravedad = document.getElementById('badge_gravedad');
        if(data.falta_grave == 1) {
            bGravedad.textContent = 'Falta Grave';
            bGravedad.style.background = '#fee2e2'; bGravedad.style.color = '#b91c1c';
        } else {
            bGravedad.textContent = 'Falta Leve/Moderada';
            bGravedad.style.background = '#f3f4f6'; bGravedad.style.color = '#4b5563';
        }

        const contEvidencia = document.getElementById('contenedor_evidencia');
        const linkEvidencia = document.getElementById('ver_evidencia');
        if(data.evidencia) {
            linkEvidencia.href = 'uploads/' + data.evidencia;
            contEvidencia.style.display = 'flex';
        } else {
            contEvidencia.style.display = 'none';
        }

        toggleModal('modalVer', true);
    }

    function abrirModalEjecutar(id, nombre, cedula) {
        document.getElementById('exec_reporte_id').value = id;
        document.getElementById('exec_nombre').value = nombre;
        document.getElementById('exec_cedula').value = cedula;
        document.getElementById('txtNombreEjecutar').textContent = nombre;
        toggleModal('modalEjecutar', true);
    }

    function abrirModalEstatus(id) {
        document.getElementById('estatus_reporte_id').value = id;
        toggleModal('modalEstatus', true);
    }

    document.getElementById('formEjecutar').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('controllers/procesar_juridico.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if(data.success) {
                toggleModal('modalEjecutar', false);
                showToast('Éxito', data.mensaje, 'success');
                
                const urlWord = `plantilla_juridica.php?nombre=${encodeURIComponent(data.nombre)}&cedula=${data.cedula}&fecha=${data.fecha_citacion}`;
                setTimeout(() => { window.location.href = urlWord; }, 1000);
                setTimeout(() => { window.location.reload(); }, 2500);
            } else {
                showToast('Error', data.mensaje, 'error');
            }
        } catch (error) { showToast('Error', 'Fallo de conexión', 'error'); }
    });

    document.getElementById('formEstatus').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('controllers/procesar_juridico.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if(data.success) {
                toggleModal('modalEstatus', false);
                showToast('Actualizado', data.mensaje, 'success');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showToast('Error', data.mensaje, 'error');
            }
        } catch (error) { showToast('Error', 'Fallo de conexión', 'error'); }
    });
</script>
</body>
</html> 