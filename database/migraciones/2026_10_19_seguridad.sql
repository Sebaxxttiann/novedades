CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave CHAR(64) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_clave_fecha (clave, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE operaciones ADD UNIQUE KEY uk_operacion_nombre (nombre);
ALTER TABLE reportes_novedades ADD KEY idx_operacion_fecha (operacion_id, fecha_creacion);
ALTER TABLE reportes_novedades ADD KEY idx_cedula (colaborador_cedula);
