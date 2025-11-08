-- SQL Update Script (regenerado para el esquema actual - MySQL 5.7 compatible)
-- Objetivo: aplicar las mejoras documentadas en update_nov_2024.sql
-- Nota: Este script evita errores en bases de datos existentes comprobando la presencia
--       de tablas/columnas/índices antes de crear/alterar. Aun así, haz un BACKUP antes
--       de ejecutarlo en producción. Algunos ALTER TABLE implican commits implícitos.

SET autocommit = 0;
START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1) Añadir columna 'foto' a usuarios_staff si no existe
-- ---------------------------------------------------------------------
SELECT COUNT(*) INTO @has_usuarios_staff
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios_staff';

SELECT COUNT(*) INTO @has_col_foto
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios_staff'
  AND COLUMN_NAME = 'foto';

SET @stmt = IF(@has_usuarios_staff = 1 AND @has_col_foto = 0,
    'ALTER TABLE usuarios_staff ADD COLUMN foto VARCHAR(255) NULL AFTER telefono',
    'SELECT 0');

PREPARE prep_stmt FROM @stmt;
EXECUTE prep_stmt;
DEALLOCATE PREPARE prep_stmt;

-- ---------------------------------------------------------------------
-- 2) Crear tabla 'configuracion' si no existe (no forzamos cambios si ya existe)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    tipo ENUM('texto','numero','boolean','json') DEFAULT 'texto',
    grupo VARCHAR(50) DEFAULT 'general',
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert / Update seguro de claves de configuracion (estilos)
UPDATE configuracion SET valor='#3B82F6', tipo='texto', grupo='estilos', descripcion='Color principal del sistema' WHERE clave='color_primario';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'color_primario', '#3B82F6', 'texto', 'estilos', 'Color principal del sistema'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='color_primario');

UPDATE configuracion SET valor='#10B981', tipo='texto', grupo='estilos', descripcion='Color secundario del sistema' WHERE clave='color_secundario';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'color_secundario', '#10B981', 'texto', 'estilos', 'Color secundario del sistema'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='color_secundario');

UPDATE configuracion SET valor='#F59E0B', tipo='texto', grupo='estilos', descripcion='Color de acento del sistema' WHERE clave='color_acento';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'color_acento', '#F59E0B', 'texto', 'estilos', 'Color de acento del sistema'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='color_acento');

UPDATE configuracion SET valor='system', tipo='texto', grupo='estilos', descripcion='Fuente tipográfica principal' WHERE clave='fuente_principal';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'fuente_principal', 'system', 'texto', 'estilos', 'Fuente tipográfica principal'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='fuente_principal');

UPDATE configuracion SET valor='medium', tipo='texto', grupo='estilos', descripcion='Radio de bordes de elementos' WHERE clave='border_radius';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'border_radius', 'medium', 'texto', 'estilos', 'Radio de bordes de elementos'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='border_radius');

-- Configuración Shelly (no poner tokens sensibles en VCS)
UPDATE configuracion SET valor='0', tipo='boolean', grupo='integracion', descripcion='Habilitar integración con Shelly Cloud' WHERE clave='shelly_enabled';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'shelly_enabled', '0', 'boolean', 'integracion', 'Habilitar integración con Shelly Cloud'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='shelly_enabled');

UPDATE configuracion SET valor='https://shelly-208-eu.shelly.cloud', tipo='texto', grupo='integracion', descripcion='URL del servidor Shelly Cloud' WHERE clave='shelly_server_url';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'shelly_server_url', 'https://shelly-208-eu.shelly.cloud', 'texto', 'integracion', 'URL del servidor Shelly Cloud'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='shelly_server_url');

UPDATE configuracion SET valor='8813BFD94E20', tipo='texto', grupo='integracion', descripcion='ID del dispositivo Shelly por defecto' WHERE clave='shelly_device_id';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'shelly_device_id', '8813BFD94E20', 'texto', 'integracion', 'ID del dispositivo Shelly por defecto'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='shelly_device_id');

-- Otras configuraciones generales
UPDATE configuracion SET valor='AccessGYM', tipo='texto', grupo='general', descripcion='Nombre del sitio' WHERE clave='sitio_nombre';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'sitio_nombre', 'AccessGYM', 'texto', 'general', 'Nombre del sitio'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='sitio_nombre');

UPDATE configuracion SET valor='Tu gimnasio, tu estilo de vida', tipo='texto', grupo='general', descripcion='Eslogan del sitio' WHERE clave='sitio_eslogan';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'sitio_eslogan', 'Tu gimnasio, tu estilo de vida', 'texto', 'general', 'Eslogan del sitio'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='sitio_eslogan');

UPDATE configuracion SET valor='50', tipo='numero', grupo='sistema', descripcion='Registros por página en listados' WHERE clave='registros_por_pagina';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'registros_por_pagina', '50', 'numero', 'sistema', 'Registros por página en listados'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='registros_por_pagina');

UPDATE configuracion SET valor='America/Mexico_City', tipo='texto', grupo='sistema', descripcion='Zona horaria del sistema' WHERE clave='zona_horaria';
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion)
SELECT 'zona_horaria', 'America/Mexico_City', 'texto', 'sistema', 'Zona horaria del sistema'
WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE clave='zona_horaria');

-- ---------------------------------------------------------------------
-- 3) Crear uploads_files si no existe
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS uploads_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('foto_socio', 'foto_staff', 'documento', 'logo') NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255),
    tamano INT,
    mime_type VARCHAR(100),
    usuario_id INT,
    socio_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_usuario (usuario_id),
    INDEX idx_socio (socio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) Actualizar/insertar dispositivo Shelly sin generar duplicados
--    - Si ya existe un registro con device_id = '8813BFD94E20' actualizamos sus metadatos.
--    - Si no existe, actualizamos la primera fila coincidente por nombre y asignamos el device_id.
--    - De este modo evitamos el error 1062 y el error 1093.
-- ---------------------------------------------------------------------

-- 4a) Si existe dispositivo con ese device_id, actualizamos sus campos relevantes
UPDATE dispositivos_shelly
SET
    servidor_cloud = 'shelly-208-eu.shelly.cloud',
    ubicacion = COALESCE(NULLIF(ubicacion, ''), 'Entrada Principal'),
    updated_at = CURRENT_TIMESTAMP
WHERE device_id = '8813BFD94E20';

-- 4b) Si no existe ninguna fila con ese device_id, entonces asignarlo a la primera fila que coincida por nombre
-- Nota: envolvemos la subconsulta en una tabla derivada para evitar el error 1093 de MySQL
UPDATE dispositivos_shelly
SET
    device_id = '8813BFD94E20',
    servidor_cloud = 'shelly-208-eu.shelly.cloud',
    ubicacion = COALESCE(NULLIF(ubicacion, ''), 'Entrada Principal'),
    updated_at = CURRENT_TIMESTAMP
WHERE (nombre LIKE '%Principal%' OR nombre LIKE '%Entrada%')
  AND NOT EXISTS (
      SELECT 1 FROM (
          SELECT device_id FROM dispositivos_shelly
      ) AS t WHERE t.device_id = '8813BFD94E20'
  )
LIMIT 1;

-- 4c) Si aún no existe (por ejemplo no hay filas con ese nombre), insertar una nueva fila por defecto
INSERT INTO dispositivos_shelly (nombre, device_id, auth_token, servidor_cloud, tipo, sucursal_id, ubicacion, estado, activo, created_at)
SELECT
    'Puerta Principal',
    '8813BFD94E20',
    NULL,
    'shelly-208-eu.shelly.cloud',
    'puerta_magnetica',
    (SELECT id FROM sucursales ORDER BY id LIMIT 1),
    'Entrada Principal',
    'offline',
    1,
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM dispositivos_shelly WHERE device_id = '8813BFD94E20'
);

-- ---------------------------------------------------------------------
-- 5) Añadir índices si faltan (socios, bitacora_eventos, accesos)
-- ---------------------------------------------------------------------
-- Índices para tabla 'socios'
SELECT COUNT(*) INTO @has_idx_codigo FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_codigo';
SET @sql = IF(@has_idx_codigo = 0, 'ALTER TABLE socios ADD INDEX idx_codigo (codigo)', 'SELECT 0');
PREPARE p1 FROM @sql; EXECUTE p1; DEALLOCATE PREPARE p1;

SELECT COUNT(*) INTO @has_idx_email FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_email';
SET @sql = IF(@has_idx_email = 0, 'ALTER TABLE socios ADD INDEX idx_email (email)', 'SELECT 0');
PREPARE p2 FROM @sql; EXECUTE p2; DEALLOCATE PREPARE p2;

SELECT COUNT(*) INTO @has_idx_telefono FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_telefono';
SET @sql = IF(@has_idx_telefono = 0, 'ALTER TABLE socios ADD INDEX idx_telefono (telefono)', 'SELECT 0');
PREPARE p3 FROM @sql; EXECUTE p3; DEALLOCATE PREPARE p3;

SELECT COUNT(*) INTO @has_idx_nombre_apellido FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_nombre_apellido';
SET @sql = IF(@has_idx_nombre_apellido = 0, 'ALTER TABLE socios ADD INDEX idx_nombre_apellido (nombre, apellido)', 'SELECT 0');
PREPARE p4 FROM @sql; EXECUTE p4; DEALLOCATE PREPARE p4;

SELECT COUNT(*) INTO @has_idx_estado FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_estado';
SET @sql = IF(@has_idx_estado = 0, 'ALTER TABLE socios ADD INDEX idx_estado (estado)', 'SELECT 0');
PREPARE p5 FROM @sql; EXECUTE p5; DEALLOCATE PREPARE p5;

-- Índices para tabla 'bitacora_eventos'
SELECT COUNT(*) INTO @has_idx_be_fecha FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_eventos' AND INDEX_NAME = 'idx_fecha_hora';
SET @sql = IF(@has_idx_be_fecha = 0, 'ALTER TABLE bitacora_eventos ADD INDEX idx_fecha_hora (fecha_hora)', 'SELECT 0');
PREPARE p6 FROM @sql; EXECUTE p6; DEALLOCATE PREPARE p6;

SELECT COUNT(*) INTO @has_idx_be_tipo FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bitacora_eventos' AND INDEX_NAME = 'idx_tipo';
SET @sql = IF(@has_idx_be_tipo = 0, 'ALTER TABLE bitacora_eventos ADD INDEX idx_tipo (tipo)', 'SELECT 0');
PREPARE p7 FROM @sql; EXECUTE p7; DEALLOCATE PREPARE p7;

-- Índices para tabla 'accesos'
SELECT COUNT(*) INTO @has_idx_ac_fecha FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesos' AND INDEX_NAME = 'idx_fecha_hora';
SET @sql = IF(@has_idx_ac_fecha = 0, 'ALTER TABLE accesos ADD INDEX idx_fecha_hora (fecha_hora)', 'SELECT 0');
PREPARE p8 FROM @sql; EXECUTE p8; DEALLOCATE PREPARE p8;

SELECT COUNT(*) INTO @has_idx_ac_resultado FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesos' AND INDEX_NAME = 'idx_resultado';
SET @sql = IF(@has_idx_ac_resultado = 0, 'ALTER TABLE accesos ADD INDEX idx_resultado (estado)', 'SELECT 0');
PREPARE p9 FROM @sql; EXECUTE p9; DEALLOCATE PREPARE p9;

-- ---------------------------------------------------------------------
-- 6) Resumen final y commit
-- ---------------------------------------------------------------------
COMMIT;
SET autocommit = 1;

-- FIN del script
