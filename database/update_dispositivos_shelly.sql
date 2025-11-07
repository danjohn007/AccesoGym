-- SQL Update Script for dispositivos_shelly table
-- Adds new fields for enhanced Shelly device configuration
-- Date: November 2024

-- Add new columns to dispositivos_shelly table
ALTER TABLE dispositivos_shelly
ADD COLUMN auth_token VARCHAR(255) NULL COMMENT 'Token de autenticación de Shelly Cloud' AFTER device_id,
ADD COLUMN servidor_cloud VARCHAR(255) DEFAULT 'shelly-208-eu.shelly.cloud' COMMENT 'Servidor Cloud de Shelly' AFTER auth_token,
ADD COLUMN area VARCHAR(100) NULL COMMENT 'Área o zona del dispositivo' AFTER ubicacion,
ADD COLUMN canal_entrada INT DEFAULT 1 COMMENT 'Canal de entrada (apertura)' AFTER tiempo_apertura,
ADD COLUMN canal_salida INT DEFAULT 0 COMMENT 'Canal de salida (cierre)' AFTER canal_entrada,
ADD COLUMN duracion_pulso INT DEFAULT 4000 COMMENT 'Duración del pulso en milisegundos' AFTER canal_salida,
ADD COLUMN invertido TINYINT(1) DEFAULT 0 COMMENT 'Dispositivo invertido (off -> on)' AFTER activo,
ADD COLUMN simultaneo TINYINT(1) DEFAULT 0 COMMENT 'Dispositivo simultáneo' AFTER invertido;

-- Add index for auth_token for faster lookups
CREATE INDEX idx_auth_token ON dispositivos_shelly(auth_token);

-- Add index for area for filtering
CREATE INDEX idx_area ON dispositivos_shelly(area);
