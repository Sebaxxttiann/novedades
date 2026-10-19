CREATE DATABASE IF NOT EXISTS si_disciplinario_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE si_disciplinario_v2;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `operaciones`;
CREATE TABLE `operaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_operacion_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','normal','juridico') NOT NULL,
  `operacion_id` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 0,
  `password_actualizado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `operacion_id` (`operacion_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `headcount`;
CREATE TABLE `headcount` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `cargo` varchar(150) NOT NULL,
  `operacion_id` int(11) NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cedula_operacion` (`cedula`,`operacion_id`),
  KEY `fk_headcount_operacion` (`operacion_id`),
  KEY `fk_headcount_creador` (`creado_por`),
  CONSTRAINT `fk_headcount_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_headcount_operacion` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `reportes_novedades`;
CREATE TABLE `reportes_novedades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creador_id` int(11) NOT NULL,
  `operacion_id` int(11) NOT NULL,
  `colaborador_nombre` varchar(150) NOT NULL,
  `colaborador_cedula` varchar(20) NOT NULL,
  `colaborador_cargo` varchar(150) DEFAULT NULL,
  `tipo_infraccion` enum('operativa','seguridad','gestion_humana') NOT NULL,
  `descripcion` text NOT NULL,
  `evidencia` varchar(255) DEFAULT NULL,
  `decision` enum('llamado_atencion','proceso_disciplinario') NOT NULL,
  `causal_llamado` varchar(100) DEFAULT NULL,
  `reincidente` tinyint(1) DEFAULT 0,
  `falta_grave` tinyint(1) DEFAULT 0,
  `estado` varchar(50) DEFAULT 'registrado',
  `fecha_citacion` date DEFAULT NULL,
  `citado_en` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `creador_id` (`creador_id`),
  KEY `idx_operacion_fecha` (`operacion_id`,`fecha_creacion`),
  KEY `idx_cedula` (`colaborador_cedula`),
  CONSTRAINT `reportes_novedades_ibfk_1` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `reportes_novedades_ibfk_2` FOREIGN KEY (`operacion_id`) REFERENCES `operaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clave` char(64) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_clave_fecha` (`clave`,`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO operaciones (nombre) VALUES ('Operación Principal');

INSERT INTO usuarios (nombre, email, password, rol, operacion_id, estado, debe_cambiar_password) VALUES
('Administrador', 'admin@empresa.com', '$2y$10$siWtMaur/na4amXltI3IDubeq17eXhAX786YJrhovPx6H29AQuw1K', 'admin', NULL, 1, 1);
