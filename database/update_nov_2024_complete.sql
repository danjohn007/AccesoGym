-- AccessGYM Database Update Script (compatibilidad con MySQL < 8.0.16)
-- November 2024 - Complete System Enhancement
-- Description: Updates database schema to support new features and improvements

-- =============================================================================
-- Nota: algunas versiones de MySQL no soportan "ADD COLUMN IF NOT EXISTS" ni
-- "CREATE INDEX IF NOT EXISTS". Este script usa comprobaciones en
-- information_schema y sentencias preparadas para evitar errores si ya existen.
-- =============================================================================

-- =============================================================================
-- 1. Add telefono_emergencia and foto to usuarios_staff table (si no existen)
-- =============================================================================
-- telefono_emergencia
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_staff' AND COLUMN_NAME = 'telefono_emergencia';
SET @sql = IF(@cnt = 0, 'ALTER TABLE usuarios_staff ADD COLUMN telefono_emergencia VARCHAR(20) AFTER telefono', 'SELECT \"telefono_emergencia already exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- foto
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_staff' AND COLUMN_NAME = 'foto';
SET @sql = IF(@cnt = 0, 'ALTER TABLE usuarios_staff ADD COLUMN foto VARCHAR(255) AFTER telefono_emergencia', 'SELECT \"foto already exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2. Ensure socios table has telefono_emergencia (si no existe)
-- =============================================================================
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND COLUMN_NAME = 'telefono_emergencia';
SET @sql = IF(@cnt = 0, 'ALTER TABLE socios ADD COLUMN telefono_emergencia VARCHAR(20) AFTER telefono', 'SELECT \"telefono_emergencia already exists in socios\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 3. Add style configuration entries if they don't exist
-- =============================================================================
INSERT IGNORE INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('color_primario', '#3B82F6', 'texto', 'estilos', 'Color primario del sistema'),
('color_secundario', '#10B981', 'texto', 'estilos', 'Color secundario del sistema'),
('color_acento', '#F59E0B', 'texto', 'estilos', 'Color de acento del sistema'),
('fuente_principal', 'system', 'texto', 'estilos', 'Fuente principal del sistema'),
('border_radius', 'medium', 'texto', 'estilos', 'Radio de bordes del sistema');

-- =============================================================================
-- 4. Create movimientos_financieros table for financial module
-- =============================================================================
CREATE TABLE IF NOT EXISTS movimientos_financieros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
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
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha_movimiento),
    INDEX idx_tipo (tipo),
    INDEX idx_sucursal (sucursal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. Create categorias_financieras table for financial categories
-- =============================================================================
CREATE TABLE IF NOT EXISTS categorias_financieras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ingreso', 'egreso', 'ambos') NOT NULL,
    descripcion TEXT,
    color VARCHAR(7) DEFAULT '#6B7280',
    icono VARCHAR(50) DEFAULT 'fas fa-tag',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key for categoria_id after creating the table (si no existe)
-- Comprobamos si la constraint ya existe en information_schema.REFERENTIAL_CONSTRAINTS
SELECT COUNT(*) INTO @cnt FROM information_schema.TABLE_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'movimientos_financieros' AND CONSTRAINT_NAME = 'fk_movimientos_categoria';
SET @sql = IF(@cnt = 0, 'ALTER TABLE movimientos_financieros ADD CONSTRAINT fk_movimientos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_financieras(id) ON DELETE RESTRICT', 'SELECT \"fk_movimientos_categoria already exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 6. Insert default financial categories
-- =============================================================================
INSERT INTO categorias_financieras (nombre, tipo, descripcion, color, icono) VALUES
-- Income categories
('Membresías', 'ingreso', 'Ingresos por membresías y renovaciones', '#10B981', 'fas fa-id-card'),
('Clases Particulares', 'ingreso', 'Ingresos por clases personalizadas', '#3B82F6', 'fas fa-dumbbell'),
('Productos', 'ingreso', 'Venta de productos (suplementos, ropa, etc.)', '#F59E0B', 'fas fa-shopping-cart'),
('Servicios Adicionales', 'ingreso', 'Otros servicios (nutrición, fisioterapia, etc.)', '#8B5CF6', 'fas fa-plus-circle'),
('Otros Ingresos', 'ingreso', 'Ingresos diversos', '#6B7280', 'fas fa-coins'),

-- Expense categories
('Servicios', 'egreso', 'Luz, agua, internet, teléfono', '#EF4444', 'fas fa-bolt'),
('Mantenimiento', 'egreso', 'Reparaciones y mantenimiento de equipos', '#F97316', 'fas fa-tools'),
('Personal', 'egreso', 'Salarios y prestaciones', '#DC2626', 'fas fa-users'),
('Equipamiento', 'egreso', 'Compra de equipos y maquinaria', '#7C3AED', 'fas fa-shopping-bag'),
('Marketing', 'egreso', 'Publicidad y promoción', '#EC4899', 'fas fa-bullhorn'),
('Renta', 'egreso', 'Renta del local', '#991B1B', 'fas fa-building'),
('Otros Gastos', 'egreso', 'Gastos diversos', '#4B5563', 'fas fa-receipt')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- =============================================================================
-- 7. Create activos_inventario table for assets and inventory
-- =============================================================================
CREATE TABLE IF NOT EXISTS activos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('equipo', 'mobiliario', 'electronico', 'consumible', 'otro') NOT NULL,
    categoria VARCHAR(100),
    descripcion TEXT,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(200),
    estado ENUM('excelente', 'bueno', 'regular', 'malo', 'fuera_servicio') DEFAULT 'bueno',
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
-- 8. Create historial_mantenimiento table for maintenance history
-- =============================================================================
CREATE TABLE IF NOT EXISTS historial_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activo_id INT NOT NULL,
    tipo_mantenimiento ENUM('preventivo', 'correctivo', 'revision') NOT NULL,
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
-- 9. Create dispositivos_hikvision table for HikVision devices
-- =============================================================================
CREATE TABLE IF NOT EXISTS dispositivos_hikvision (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    direccion_ip VARCHAR(45) NOT NULL,
    puerto INT DEFAULT 80,
    usuario VARCHAR(100),
    password_hash VARCHAR(255),
    tipo ENUM('control_acceso', 'camara', 'nvr', 'videoportero') DEFAULT 'control_acceso',
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(100),
    descripcion TEXT,
    estado ENUM('online', 'offline', 'error', 'mantenimiento') DEFAULT 'offline',
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
-- 10. Update accesos table to support HikVision devices (si no existe la columna)
-- =============================================================================
-- Hacemos la comprobación para la columna dispositivo_tipo
SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'accesos' AND COLUMN_NAME = 'dispositivo_tipo';
SET @sql = IF(@cnt = 0, 'ALTER TABLE accesos MODIFY COLUMN dispositivo_id INT NULL, ADD COLUMN dispositivo_tipo ENUM(\'shelly\', \'hikvision\') DEFAULT \'shelly\' AFTER dispositivo_id', 'SELECT \"dispositivo_tipo already exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 11. Add indexes for better performance (comprobando si existen)
-- =============================================================================
-- idx_socios_estado
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_estado';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_estado ON socios(estado)', 'SELECT \"idx_socios_estado exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_socios_telefono
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_telefono';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_telefono ON socios(telefono)', 'SELECT \"idx_socios_telefono exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_socios_email
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'socios' AND INDEX_NAME = 'idx_socios_email';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_socios_email ON socios(email)', 'SELECT \"idx_socios_email exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_usuarios_telefono
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_staff' AND INDEX_NAME = 'idx_usuarios_telefono';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_usuarios_telefono ON usuarios_staff(telefono)', 'SELECT \"idx_usuarios_telefono exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- idx_configuracion_grupo
SELECT COUNT(*) INTO @cnt FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'configuracion' AND INDEX_NAME = 'idx_configuracion_grupo';
SET @sql = IF(@cnt = 0, 'CREATE INDEX idx_configuracion_grupo ON configuracion(grupo)', 'SELECT \"idx_configuracion_grupo exists\"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 12. Verify essential data exists
-- =============================================================================

-- Ensure default sucursal exists
INSERT IGNORE INTO sucursales (id, nombre, direccion, telefono, email, activo) 
VALUES (1, 'Sucursal Principal', 'Dirección de ejemplo', '1234567890', 'contacto@accessgym.com', 1);

-- Ensure default membership types exist
INSERT IGNORE INTO tipos_membresia (nombre, descripcion, duracion_dias, precio, color) VALUES
('Mensual', 'Acceso ilimitado durante 30 días', 30, 500.00, '#3B82F6'),
('Trimestral', 'Acceso ilimitado durante 90 días', 90, 1350.00, '#10B981'),
('Semestral', 'Acceso ilimitado durante 180 días', 180, 2400.00, '#F59E0B'),
('Anual', 'Acceso ilimitado durante 365 días', 365, 4500.00, '#8B5CF6')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- =============================================================================
-- Script completion
-- =============================================================================
-- Add log entry for update
INSERT INTO bitacora_eventos (tipo, descripcion, fecha_hora) 
VALUES ('sistema', 'Base de datos actualizada - Noviembre 2024 (Sistema completo)', NOW());

-- Show completion message
SELECT 'Database update completed successfully!' AS Status,
       NOW() AS UpdateTime;
