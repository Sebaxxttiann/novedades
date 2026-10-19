<style>
    .detalle-encabezado {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 1.1rem;
    }

    .detalle-encabezado h3 {
        font-size: 1.05rem;
        font-weight: 600;
    }

    .detalle-encabezado p {
        font-size: 0.82rem;
        color: var(--text-muted);
    }

    .detalle-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 1.1rem;
    }

    .detalle-cargando {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .detalle-evidencia {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 0.85rem 1rem;
        border-radius: 10px;
        color: #15803d;
        font-size: 0.88rem;
        font-weight: 500;
    }
</style>

<div class="modal-overlay" id="modalDetalle">
    <div class="modal-content" style="max-width: 680px;">
        <div class="modal-header">
            <h2>Detalle de la novedad <span class="id-badge" id="detalleId"></span></h2>
            <button type="button" class="close-modal" onclick="toggleModal('modalDetalle', false)" aria-label="Cerrar"><i data-lucide="x" style="width: 20px;"></i></button>
        </div>
        <div class="modal-body" id="detalleCuerpo"></div>
        <div class="modal-footer" id="detallePie">
            <button type="button" class="btn btn-ghost" onclick="toggleModal('modalDetalle', false)">Cerrar</button>
        </div>
    </div>
</div>

<script>
    async function verDetalleNovedad(id) {
        const cuerpo = document.getElementById('detalleCuerpo');
        const pie = document.getElementById('detallePie');
        document.getElementById('detalleId').textContent = '#' + String(id).padStart(4, '0');
        cuerpo.innerHTML = '<div class="detalle-cargando">Cargando información...</div>';
        pie.querySelectorAll('[data-dinamico]').forEach(el => el.remove());
        toggleModal('modalDetalle', true);

        let data;
        try {
            const res = await fetch('api/detalle_novedad.php?id=' + encodeURIComponent(id));
            data = await res.json();
        } catch (error) {
            data = { success: false, mensaje: 'No se pudo conectar con el servidor.' };
        }

        if (!data.success) {
            cuerpo.innerHTML = `<div class="detalle-cargando">${escapeHtml(data.mensaje)}</div>`;
            return;
        }

        const r = data.reporte;
        const item = (etiqueta, valor, clase = '') => `
            <div class="detail-item ${clase}">
                <div class="detail-label">${escapeHtml(etiqueta)}</div>
                <div class="detail-value">${escapeHtml(valor)}</div>
            </div>`;

        const esProceso = r.decision === 'proceso_disciplinario';
        const iniciales = r.colaborador_nombre.trim().substring(0, 2).toUpperCase();

        cuerpo.innerHTML = `
            <div class="detalle-encabezado">
                <div class="avatar avatar-lg avatar-primary">${escapeHtml(iniciales)}</div>
                <div>
                    <h3>${escapeHtml(r.colaborador_nombre)}</h3>
                    <p>CC ${escapeHtml(r.colaborador_cedula)} · ${escapeHtml(r.colaborador_cargo)}</p>
                </div>
            </div>
            <div class="detalle-badges">
                <span class="badge tone-${escapeHtml(r.estado_tone)}"><i data-lucide="${escapeHtml(r.estado_icon)}"></i>${escapeHtml(r.estado_label)}</span>
                <span class="badge ${esProceso ? 'tone-red' : 'tone-orange'}"><i data-lucide="${esProceso ? 'gavel' : 'file-warning'}"></i>${escapeHtml(r.decision_label)}</span>
                ${r.reincidente ? '<span class="badge tone-amber"><i data-lucide="repeat"></i>Reincidente</span>' : ''}
                ${r.falta_grave ? '<span class="badge tone-red"><i data-lucide="alert-triangle"></i>Falta grave</span>' : ''}
            </div>
            <div class="detail-grid">
                ${item('Operación', r.operacion)}
                ${item('Tipo de infracción', r.tipo_infraccion)}
                ${item('Reportado por', r.creador)}
                ${item('Fecha de registro', r.fecha_creacion)}
                ${r.causal ? item('Causal del llamado', r.causal) : ''}
                ${esProceso ? item('Fecha de descargos', r.fecha_citacion || 'Sin agendar') : ''}
                <div class="detail-item full">
                    <div class="detail-label">Descripción de los hechos</div>
                    <div class="detail-value text">${escapeHtml(r.descripcion)}</div>
                </div>
            </div>
            ${r.tiene_evidencia ? `
                <div class="detalle-evidencia">
                    <span style="display:flex; align-items:center; gap:8px;"><i data-lucide="paperclip" style="width:18px;"></i>Evidencia adjunta</span>
                    <a href="${escapeHtml(r.evidencia_url)}" target="_blank" rel="noopener" class="btn btn-sm" style="background:#166534; color:white;">Ver archivo</a>
                </div>` : ''}
        `;

        if (r.plantilla_url) {
            const enlace = document.createElement('a');
            enlace.href = r.plantilla_url;
            enlace.className = 'btn btn-primary';
            enlace.dataset.dinamico = '1';
            enlace.innerHTML = '<i data-lucide="file-down" style="width:16px;"></i> Descargar llamado';
            pie.appendChild(enlace);
        }

        lucide.createIcons();
    }
</script>
