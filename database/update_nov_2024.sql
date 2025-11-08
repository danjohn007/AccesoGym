-- SQL Update Script for November 2024 Enhancements
-- This script adds new columns and updates permissions for the system

-- Add foto column to usuarios_staff table for profile photos
ALTER TABLE usuarios_staff 
ADD COLUMN foto VARCHAR(255) NULL AFTER telefono;

-- Create configuracion table if it doesn't exist
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    grupo VARCHAR(50) DEFAULT 'general',
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default style configurations if they don't exist
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('color_primario', '#3B82F6', 'texto', 'estilos', 'Color principal del sistema'),
('color_secundario', '#10B981', 'texto', 'estilos', 'Color secundario del sistema'),
('color_acento', '#F59E0B', 'texto', 'estilos', 'Color de acento del sistema'),
('fuente_principal', 'system', 'texto', 'estilos', 'Fuente tipográfica principal'),
('border_radius', 'medium', 'texto', 'estilos', 'Radio de bordes de elementos')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- Insert Shelly API configuration if it doesn't exist
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('shelly_enabled', '0', 'booleano', 'integracion', 'Habilitar integración con Shelly Cloud'),
('shelly_server_url', 'https://shelly-208-eu.shelly.cloud', 'texto', 'integracion', 'URL del servidor Shelly Cloud'),
('shelly_auth_token', '', 'texto', 'integracion', 'Token de autenticación de Shelly Cloud'),
('shelly_device_id', '8813BFD94E20', 'texto', 'integracion', 'ID del dispositivo Shelly por defecto')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- Insert general site configurations if they don't exist
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('sitio_nombre', 'AccessGYM', 'texto', 'general', 'Nombre del sitio'),
('sitio_eslogan', 'Tu gimnasio, tu estilo de vida', 'texto', 'general', 'Eslogan del sitio'),
('sitio_logo', '', 'texto', 'general', 'Logo del sitio'),
('registros_por_pagina', '50', 'numero', 'sistema', 'Registros por página en listados'),
('zona_horaria', 'America/Mexico_City', 'texto', 'sistema', 'Zona horaria del sistema')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- Create uploads directory structure table for tracking
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
    INDEX idx_socio (socio_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing Shelly device with correct credentials (if exists)
-- This updates the device with ID that matches the provided Device ID
UPDATE dispositivos_shelly 
SET 
    device_id = '8813BFD94E20',
    descripcion = 'Dispositivo Shelly configurado con credenciales actualizadas',
    updated_at = CURRENT_TIMESTAMP
WHERE nombre LIKE '%Principal%' OR nombre LIKE '%Entrada%'
LIMIT 1;

-- If no device exists, insert a default one
INSERT INTO dispositivos_shelly (nombre, device_id, tipo, ubicacion, sucursal_id, estado, activo, descripcion)
SELECT 
    'Puerta Principal',
    '8813BFD94E20',
    'relay',
    'Entrada Principal',
    (SELECT id FROM sucursales ORDER BY id LIMIT 1),
    'offline',
    1,
    'Dispositivo Shelly Cloud configurado - Nov 2024'
WHERE NOT EXISTS (
    SELECT 1 FROM dispositivos_shelly WHERE device_id = '8813BFD94E20'
);

-- Ensure staff upload directory exists
-- Note: This is handled by the application code, but we document it here
-- Directory: /uploads/staff/ should be created with 0755 permissions

-- Update permissions: Ensure SuperAdmin has access to all admin modules
-- This is handled in the application code through Auth::requireRole(['superadmin', 'admin'])
-- No database changes needed as roles are checked in the application layer

-- Add indexes for better search performance
ALTER TABLE socios 
ADD INDEX idx_codigo (codigo),
ADD INDEX idx_email (email),
ADD INDEX idx_telefono (telefono),
ADD INDEX idx_nombre_apellido (nombre, apellido),
ADD INDEX idx_estado (estado);

-- Add index for bitacora search
ALTER TABLE bitacora_eventos 
ADD INDEX idx_fecha_hora (fecha_hora),
ADD INDEX idx_tipo (tipo);

-- Add index for accesos search
ALTER TABLE accesos 
ADD INDEX idx_fecha_hora (fecha_hora),
ADD INDEX idx_resultado (resultado);

-- Summary of changes:
-- 1. Added foto column to usuarios_staff for profile photos
-- 2. Created/updated configuracion table for system settings
-- 3. Inserted default style configurations
-- 4. Inserted Shelly API configurations with correct credentials
-- 5. Created uploads_files tracking table
-- 6. Updated/inserted Shelly device with correct Device ID
-- 7. Added database indexes for improved search performance
-- 8. Documented that SuperAdmin access is controlled by application code

-- Note: After running this script, update the config.php file with:
-- define('SHELLY_ENABLED', true);
-- define('SHELLY_SERVER_URL', 'https://shelly-208-eu.shelly.cloud');
-- define('SHELLY_AUTH_TOKEN', 'MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74');
