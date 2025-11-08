-- AccessGYM Database Update Script (compatibilidad MySQL 5.7+ / 8)
-- November 2024 - Regenerado para evitar errores con la DB actual
-- Ejecutar con: USE accessgym; (o ejecútalo conectado a la base de datos correcta)
-- Este script usa comprobaciones en information_schema antes de ALTER / CREATE INDEX / FK
-- para evitar errores si ya existen columnas/índices/constraints (compatible con MySQL 5.7).

SET @schema := DATABASE();

-- Safety: ensure we are using a database
SELECT IF(@schema IS NULL, CONCAT('ERROR: No database selected. Use USE <db>;'), CONCAT('Database: ', @schema)) AS info;

-- =============================================================================
-- 0. Recomiendo ejecutar dentro de una transacción de mantenimiento si procede.
-- =============================================================================

-- =============================================================================
-- 1. Añadir columnas a usuarios_staff (telefono_emergencia, foto) si no existen
-- =============================================================================
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'usuarios_staff' AND COLUMN_NAME = 'telefono_emergencia';
SET @sql = IF(@cnt = 0,
    'ALTER TABLE usuarios_staff ADD COLUMN telefono_emergencia VARCHAR(20)',
    'SELECT ''telefono_emergencia already exists in usuarios_staff'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'usuarios_staff' AND COLUMN_NAME = 'foto';
-- Añadimos la columna foto sin usar AFTER para evitar fallos si la columna de referencia no existe.
SET @sql = IF(@cnt = 0,
    'ALTER TABLE usuarios_staff ADD COLUMN foto VARCHAR(255) COMMENT ''Ruta de la foto de perfil''',
    'SELECT ''foto already exists in usuarios_staff'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2. Añadir telefono_emergencia a socios (si no existe)
-- =============================================================================
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'socios' AND COLUMN_NAME = 'telefono_emergencia';
SET @sql = IF(@cnt = 0,
    'ALTER TABLE socios ADD COLUMN telefono_emergencia VARCHAR(20)',
    'SELECT ''telefono_emergencia already exists in socios'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 3. Registrar entradas de configuración de estilos (safe insert)
-- =============================================================================
INSERT IGNORE INTO configuracion (`clave`, `valor`, `tipo`, `grupo`, `descripcion`) VALUES
('color_primario', '#3B82F6', 'texto', 'estilos', 'Color primario del sistema'),
('color_secundario', '#10B981', 'texto', 'estilos', 'Color secundario del sistema'),
('color_acento', '#F59E0B', 'texto', 'estilos', 'Color de acento del sistema'),
('fuente_principal', 'system', 'texto', 'estilos', 'Fuente principal del sistema'),
('border_radius', 'medium', 'texto', 'estilos', 'Radio de bordes del sistema');

-- =============================================================================
-- 4. Crear tabla movimientos_financieros si no existe
-- =============================================================================
CREATE TABLE IF NOT EXISTS movimientos_financieros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso','egreso') NOT NULL,
    categoria_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_movimiento DATE NOT NULL,
    metodo_pago VARCHAR(50),
    referencia VARCHAR(100),
    comprobante VARCHAR(255),
    sucursal_id INT NOT NULL,
    usuario_registro INT,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha_movimiento),
    INDEX idx_tipo (tipo),
    INDEX idx_sucursal (sucursal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. Crear tabla categorias_financieras si no existe
-- =============================================================================
CREATE TABLE IF NOT EXISTS categorias_financieras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ingreso','egreso','ambos') NOT NULL,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#6B7280',
    icono VARCHAR(50) DEFAULT 'fas fa-tag',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir FK movimientos_financieros.categoria_id -> categorias_financieras(id) si no existe
SELECT COUNT(*) INTO @cnt FROM information_schema.TABLE_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = @schema AND TABLE_NAME = 'movimientos_financieros' AND CONSTRAINT_NAME = 'fk_movimientos_categoria';
SET @sql = IF(@cnt = 0,
    'ALTER TABLE movimientos_financieros ADD CONSTRAINT fk_movimientos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_financieras(id) ON DELETE RESTRICT',
    'SELECT ''fk_movimientos_categoria already exists'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 6. Insertar categorías financieras por defecto (safe)
-- =============================================================================
INSERT INTO categorias_financieras (nombre, tipo, descripcion, color, icono)
VALUES
('Membresías','ingreso','Ingresos por membresías y renovaciones','#10B981','fas fa-id-card'),
('Clases Particulares','ingreso','Ingresos por clases personalizadas','#3B82F6','fas fa-dumbbell'),
('Productos','ingreso','Venta de productos (suplementos, ropa, etc.)','#F59E0B','fas fa-shopping-cart'),
('Servicios Adicionales','ingreso','Otros servicios (nutrición, fisioterapia, etc.)','#8B5CF6','fas fa-plus-circle'),
('Otros Ingresos','ingreso','Ingresos diversos','#6B7280','fas fa-coins'),
('Servicios','egreso','Luz, agua, internet, teléfono','#EF4444','fas fa-bolt'),
('Mantenimiento','egreso','Reparaciones y mantenimiento de equipos','#F97316','fas fa-tools'),
('Personal','egreso','Salarios y prestaciones','#DC2626','fas fa-users'),
('Equipamiento','egreso','Compra de equipos y maquinaria','#7C3AED','fas fa-shopping-bag'),
('Marketing','egreso','Publicidad y promoción','#EC4899','fas fa-bullhorn'),
('Renta','egreso','Renta del local','#991B1B','fas fa-building'),
('Otros Gastos','egreso','Gastos diversos','#4B5563','fas fa-receipt')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- =============================================================================
-- 7. Crear activos_inventario (si no existe)
-- =============================================================================
CREATE TABLE IF NOT EXISTS activos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('equipo','mobiliario','electronico','consumible','otro') NOT NULL,
    categoria VARCHAR(100),
    descripcion TEXT,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(200),
    estado ENUM('excelente','bueno','regular','malo','fuera_servicio') DEFAULT 'bueno',
    fecha_adquisicion DATE,
    costo_adquisicion DECIMAL(10,2),
    proveedor VARCHAR(200),
    cantidad INT DEFAULT 1,
    cantidad_minima INT DEFAULT 1,
    garantia_hasta DATE,
    ultima_mantenimiento DATE,
    proxima_mantenimiento DATE,
    foto VARCHAR(255),
    notas TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_codigo (codigo),
    INDEX idx_tipo (tipo),
    INDEX idx_sucursal (sucursal_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. Crear historial_mantenimiento (si no existe)
-- =============================================================================
CREATE TABLE IF NOT EXISTS historial_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activo_id INT NOT NULL,
    tipo_mantenimiento ENUM('preventivo','correctivo','revision') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_mantenimiento DATE NOT NULL,
    costo DECIMAL(10,2),
    realizado_por VARCHAR(200),
    proveedor VARCHAR(200),
    usuario_registro INT,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (activo_id) REFERENCES activos_inventario(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    INDEX idx_activo (activo_id),
    INDEX idx_fecha (fecha_mantenimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. Crear dispositivos_hikvision (si no existe)
-- =============================================================================
CREATE TABLE IF NOT EXISTS dispositivos_hikvision (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    direccion_ip VARCHAR(45) NOT NULL,
    puerto INT DEFAULT 80,
    usuario VARCHAR(100),
    password_hash VARCHAR(255),
    tipo ENUM('control_acceso','camara','nvr','videoportero') DEFAULT 'control_acceso',
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(100),
    descripcion TEXT,
    estado ENUM('online','offline','error','mantenimiento') DEFAULT 'offline',
    ultima_conexion TIMESTAMP NULL,
    activo TINYINT(1) DEFAULT 1,
    configuracion JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_device_id (device_id),
    INDEX idx_sucursal (sucursal_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. Actualizar tabla accesos: añadir columna dispositivo_tipo si no existe y permitir NULL en dispositivo_id si aplica
-- =============================================================================
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'accesos' AND COLUMN_NAME = 'dispositivo_tipo';
SET @sql = IF(@cnt = 0,
    'ALTER TABLE accesos ADD COLUMN dispositivo_tipo ENUM(''shelly'',''hikvision'') DEFAULT ''shelly''',
    'SELECT ''dispositivo_tipo already exists in accesos'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Hacer dispositivo_id NULLABLE si existe y no es nullable
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'accesos' AND COLUMN_NAME = 'dispositivo_id' AND IS_NULLABLE = 'YES';
-- Si columna existe y no es nullable, modificamos; si no existe, no hacemos nada.
SELECT COUNT(*) INTO @exists_col FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'accesos' AND COLUMN_NAME = 'dispositivo_id';
SET @sql = IF(@exists_col = 1 AND @cnt = 0,
    'ALTER TABLE accesos MODIFY COLUMN dispositivo_id INT NULL',
    'SELECT ''no change to dispositivo_id (either missing or already nullable)'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 11. Crear índices adicionales (comprobando existencia)
-- =============================================================================
-- lista de índices a añadir: tabla,name,definition
-- idx_socios_estado ON socios(estado)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_estado';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_estado ON socios(estado)', 'SELECT ''idx_socios_estado exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_socios_telefono ON socios(telefono)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_telefono';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_telefono ON socios(telefono)', 'SELECT ''idx_socios_telefono exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_socios_email ON socios(email)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_email';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_email ON socios(email)', 'SELECT ''idx_socios_email exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_usuarios_telefono ON usuarios_staff(telefono)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'usuarios_staff' AND INDEX_NAME = 'idx_usuarios_telefono';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_usuarios_telefono ON usuarios_staff(telefono)', 'SELECT ''idx_usuarios_telefono exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_configuracion_grupo ON configuracion(grupo)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'configuracion' AND INDEX_NAME = 'idx_configuracion_grupo';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_configuracion_grupo ON configuracion(grupo)', 'SELECT ''idx_configuracion_grupo exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ejemplo: índices adicionales para tablas mencionadas en el repo original (bitacora_eventos, pagos, gastos)
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'bitacora_eventos' AND INDEX_NAME = 'idx_tipo_fecha';
SET @sql = IF(@cnt = 0, 'ALTER TABLE bitacora_eventos ADD INDEX idx_tipo_fecha (tipo, fecha_hora)', 'SELECT ''idx_tipo_fecha exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'bitacora_eventos' AND INDEX_NAME = 'idx_sucursal_fecha';
SET @sql = IF(@cnt = 0, 'ALTER TABLE bitacora_eventos ADD INDEX idx_sucursal_fecha (sucursal_id, fecha_hora)', 'SELECT ''idx_sucursal_fecha exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_estado_fecha';
SET @sql = IF(@cnt = 0, 'ALTER TABLE pagos ADD INDEX idx_estado_fecha (estado, fecha_pago)', 'SELECT ''idx_estado_fecha exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'pagos' AND INDEX_NAME = 'idx_metodo_pago';
SET @sql = IF(@cnt = 0, 'ALTER TABLE pagos ADD INDEX idx_metodo_pago (metodo_pago)', 'SELECT ''idx_metodo_pago exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'gastos' AND INDEX_NAME = 'idx_categoria_fecha';
SET @sql = IF(@cnt = 0, 'ALTER TABLE gastos ADD INDEX idx_categoria_fecha (categoria, fecha_gasto)', 'SELECT ''idx_categoria_fecha exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 12. Verificaciones de datos esenciales / inserts seguros
-- =============================================================================
INSERT IGNORE INTO sucursales (id, nombre, direccion, telefono, email, activo) 
VALUES (1, 'Sucursal Principal', 'Dirección de ejemplo', '1234567890', 'contacto@accessgym.com', 1);

INSERT IGNORE INTO tipos_membresia (nombre, descripcion, duracion_dias, precio, color) VALUES
('Mensual', 'Acceso ilimitado durante 30 días', 30, 500.00, '#3B82F6'),
('Trimestral', 'Acceso ilimitado durante 90 días', 90, 1350.00, '#10B981'),
('Semestral', 'Acceso ilimitado durante 180 días', 180, 2400.00, '#F59E0B'),
('Anual', 'Acceso ilimitado durante 365 días', 365, 4500.00, '#8B5CF6')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- =============================================================================
-- 13. Log final de actualización de esquema
-- =============================================================================
INSERT INTO bitacora_eventos (tipo, descripcion, fecha_hora) 
VALUES ('sistema', 'Base de datos actualizada - Noviembre 2024 (script regenerado y seguro)', NOW());

SELECT '✓ Actualización completada (script regenerado) - revisa mensajes previos para detalles' AS Estado, NOW() AS Fecha;

-- FIN del script
