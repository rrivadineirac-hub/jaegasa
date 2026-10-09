-- ============================================================
-- crear_tabla_trimestre.sql — Estructura de la tabla Trimestre
-- Sistema Académico JAEGASA
-- ============================================================

USE jaegasa_db_fluter;

CREATE TABLE IF NOT EXISTS Trimestre (
    idTrimestre INT AUTO_INCREMENT PRIMARY KEY,
    idGestion INT NOT NULL,
    numero TINYINT NOT NULL COMMENT '1: Primer Trimestre, 2: Segundo Trimestre, 3: Tercer Trimestre',
    nombre VARCHAR(50) NOT NULL,
    fechaInicio DATE NULL,
    fechaFin DATE NULL,
    estadoTrimestre ENUM('Pendiente', 'En Curso', 'Cerrado') NOT NULL DEFAULT 'Pendiente',
    observacion VARCHAR(255) NULL,
    estado TINYINT NOT NULL DEFAULT 1,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trimestre_gestion (idGestion),
    CONSTRAINT fk_trimestre_gestion FOREIGN KEY (idGestion) REFERENCES Gestion(idGestion) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT unq_gestion_numero UNIQUE (idGestion, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
