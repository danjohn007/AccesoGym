-- ============================================================================
-- AccessGYM - SQL Update Script for Permission and Module Changes
-- Date: 2024-11-07
-- Description: Updates for phone validation, permissions, and SuperAdmin features
-- ============================================================================

USE accessgym;

-- ============================================================================
-- 1. No schema changes needed for phone validation (handled in PHP)
-- ============================================================================
-- The 10-digit phone validation is implemented in the frontend and backend PHP code
-- No database schema changes are required for this feature

-- ============================================================================
-- 2. Ensure sucursales table has proper structure
-- ============================================================================
-- The sucursales table already exists in schema.sql
-- Verify it has all necessary fields

ALTER TABLE sucursales 
    MODIFY COLUMN nombre VARCHAR(100) NOT NULL,
    MODIFY COLUMN activo TINYINT(1) DEFAULT 1;

-- ============================================================================
-- 3. Add index for improved query performance on permission checks
-- ============================================================================

-- Add index on usuarios_staff for role and branch queries
ALTER TABLE usuarios_staff 
    ADD INDEX idx_rol_sucursal (rol, sucursal_id);

-- Add index on pagos for financial module queries
ALTER TABLE pagos
    ADD INDEX idx_sucursal_fecha (sucursal_id, fecha_pago);

-- Add index on gastos for financial module queries
ALTER TABLE gastos
    ADD INDEX idx_sucursal_fecha_gasto (sucursal_id, fecha_gasto);

-- Add index on bitacora_eventos for audit queries
ALTER TABLE bitacora_eventos
    ADD INDEX idx_sucursal_fecha (sucursal_id, fecha_hora);

-- ============================================================================
-- 4. Update configuration for new modules
-- ============================================================================

INSERT IGNORE INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('modulo_sucursales_enabled', '1', 'boolean', 'modulos', 'Habilitar módulo de sucursales para SuperAdmin'),
('filtro_sucursales_financiero', '1', 'boolean', 'modulos', 'Habilitar filtro de sucursales en módulo financiero'),
('filtro_sucursales_auditoria', '1', 'boolean', 'modulos', 'Habilitar filtro de sucursales en auditoría'),
('filtro_sucursales_importar', '1', 'boolean', 'modulos', 'Habilitar filtro de sucursales en importar datos'),
('validacion_telefono_digitos', '10', 'numero', 'validacion', 'Número de dígitos requeridos para teléfonos');

-- ============================================================================
-- 5. Create view for SuperAdmin global statistics
-- ============================================================================

-- Vista de estadísticas globales por sucursal para SuperAdmin
CREATE OR REPLACE VIEW vista_estadisticas_sucursales AS
SELECT 
    s.id as sucursal_id,
    s.nombre as sucursal_nombre,
    s.activo,
    (SELECT COUNT(*) FROM socios WHERE sucursal_id = s.id) as total_socios,
    (SELECT COUNT(*) FROM socios WHERE sucursal_id = s.id AND estado = 'activo') as socios_activos,
    (SELECT COUNT(*) FROM usuarios_staff WHERE sucursal_id = s.id AND activo = 1) as total_staff,
    (SELECT COUNT(*) FROM dispositivos_shelly WHERE sucursal_id = s.id AND activo = 1) as total_dispositivos,
    (SELECT SUM(monto) FROM pagos WHERE sucursal_id = s.id AND estado = 'completado' AND DATE(fecha_pago) = CURDATE()) as ingresos_hoy,
    (SELECT SUM(monto) FROM pagos WHERE sucursal_id = s.id AND estado = 'completado' AND YEAR(fecha_pago) = YEAR(CURDATE()) AND MONTH(fecha_pago) = MONTH(CURDATE())) as ingresos_mes,
    s.created_at,
    s.updated_at
FROM sucursales s
ORDER BY s.nombre;

-- Vista de membresías globales para SuperAdmin
CREATE OR REPLACE VIEW vista_membresias_globales AS
SELECT 
    tm.id as tipo_membresia_id,
    tm.nombre as tipo_membresia,
    tm.duracion_dias,
    tm.precio,
    tm.activo,
    COUNT(DISTINCT s.id) as total_socios,
    COUNT(DISTINCT s.sucursal_id) as sucursales_con_socios,
    SUM(CASE WHEN s.estado = 'activo' AND s.fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END) as socios_activos,
    SUM(CASE WHEN s.estado = 'vencido' OR s.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END) as socios_vencidos
FROM tipos_membresia tm
LEFT JOIN socios s ON tm.id = s.tipo_membresia_id
GROUP BY tm.id, tm.nombre, tm.duracion_dias, tm.precio, tm.activo
ORDER BY tm.nombre;

-- ============================================================================
-- 6. Add stored procedure for SuperAdmin financial summary
-- ============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS obtener_resumen_financiero_global$$

CREATE PROCEDURE obtener_resumen_financiero_global(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_sucursal_id INT
)
BEGIN
    -- Return financial summary for SuperAdmin
    -- If p_sucursal_id is NULL, return global summary
    -- Otherwise, return summary for specific branch
    
    SELECT 
        COALESCE(s.id, 0) as sucursal_id,
        COALESCE(s.nombre, 'Todas las sucursales') as sucursal_nombre,
        COALESCE(SUM(p.monto), 0) as total_ingresos,
        COUNT(p.id) as cantidad_pagos,
        COALESCE(AVG(p.monto), 0) as promedio_pago,
        COALESCE(SUM(g.monto), 0) as total_gastos,
        COUNT(g.id) as cantidad_gastos,
        COALESCE(SUM(p.monto), 0) - COALESCE(SUM(g.monto), 0) as balance
    FROM sucursales s
    LEFT JOIN pagos p ON s.id = p.sucursal_id 
        AND p.fecha_pago BETWEEN p_fecha_inicio AND p_fecha_fin 
        AND p.estado = 'completado'
    LEFT JOIN gastos g ON s.id = g.sucursal_id 
        AND g.fecha_gasto BETWEEN p_fecha_inicio AND p_fecha_fin
    WHERE (p_sucursal_id IS NULL OR s.id = p_sucursal_id)
        AND s.activo = 1
    GROUP BY s.id, s.nombre
    ORDER BY s.nombre;
END$$

DELIMITER ;

-- ============================================================================
-- 7. Update sample data to ensure at least 2 sucursales exist for testing
-- ============================================================================

-- Add a second branch if it doesn't exist (for testing SuperAdmin features)
INSERT IGNORE INTO sucursales (id, nombre, direccion, telefono, email, activo) VALUES
(2, 'Sucursal Norte', 'Av. Norte #456, Col. Norte', '5559876543', 'norte@accessgym.com', 1);

-- ============================================================================
-- 8. Validation and integrity checks
-- ============================================================================

-- Ensure all socios have a valid sucursal_id
UPDATE socios SET sucursal_id = 1 WHERE sucursal_id IS NULL OR sucursal_id NOT IN (SELECT id FROM sucursales);

-- Ensure all usuarios_staff have a valid sucursal_id (except superadmin)
UPDATE usuarios_staff 
SET sucursal_id = 1 
WHERE (sucursal_id IS NULL OR sucursal_id NOT IN (SELECT id FROM sucursales))
AND rol != 'superadmin';

-- Ensure all pagos have a valid sucursal_id
UPDATE pagos p
INNER JOIN socios s ON p.socio_id = s.id
SET p.sucursal_id = s.sucursal_id
WHERE p.sucursal_id IS NULL OR p.sucursal_id NOT IN (SELECT id FROM sucursales);

-- ============================================================================
-- 9. Create audit trigger for permission changes
-- ============================================================================

DELIMITER $$

-- Trigger to log changes to user permissions
DROP TRIGGER IF EXISTS log_usuario_rol_change$$

CREATE TRIGGER log_usuario_rol_change
AFTER UPDATE ON usuarios_staff
FOR EACH ROW
BEGIN
    IF OLD.rol != NEW.rol OR OLD.sucursal_id != NEW.sucursal_id OR OLD.activo != NEW.activo THEN
        INSERT INTO bitacora_eventos (tipo, descripcion, usuario_id, sucursal_id, datos_adicionales)
        VALUES (
            'sistema',
            CONCAT('Cambios en usuario: ', NEW.nombre, 
                   IF(OLD.rol != NEW.rol, CONCAT(' - Rol: ', OLD.rol, ' -> ', NEW.rol), ''),
                   IF(OLD.sucursal_id != NEW.sucursal_id, CONCAT(' - Sucursal: ', OLD.sucursal_id, ' -> ', NEW.sucursal_id), ''),
                   IF(OLD.activo != NEW.activo, CONCAT(' - Estado: ', IF(NEW.activo, 'Activo', 'Inactivo')), '')),
            NEW.id,
            NEW.sucursal_id,
            JSON_OBJECT('old_rol', OLD.rol, 'new_rol', NEW.rol, 'old_sucursal', OLD.sucursal_id, 'new_sucursal', NEW.sucursal_id)
        );
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- 10. Performance optimization for multi-branch queries
-- ============================================================================

-- Optimize table for better performance with large datasets
OPTIMIZE TABLE socios;
OPTIMIZE TABLE pagos;
OPTIMIZE TABLE gastos;
OPTIMIZE TABLE bitacora_eventos;
OPTIMIZE TABLE usuarios_staff;

-- ============================================================================
-- 11. Permission verification query for testing
-- ============================================================================

-- Query to verify permission setup (for manual testing)
-- SuperAdmin should see all branches
-- Admin should see only their branch
-- Run this manually to verify: 
-- SELECT * FROM vista_estadisticas_sucursales;

-- ============================================================================
-- Verification and Completion
-- ============================================================================

SELECT 'Script de actualización ejecutado exitosamente' AS mensaje,
       NOW() AS fecha_ejecucion,
       DATABASE() AS base_datos;

-- Display summary
SELECT 
    'Sucursales' as tabla,
    COUNT(*) as total_registros,
    SUM(activo) as activos
FROM sucursales
UNION ALL
SELECT 
    'Usuarios Staff' as tabla,
    COUNT(*) as total_registros,
    SUM(activo) as activos
FROM usuarios_staff
UNION ALL
SELECT 
    'Socios' as tabla,
    COUNT(*) as total_registros,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos
FROM socios;

-- Display configuration
SELECT clave, valor, descripcion 
FROM configuracion 
WHERE grupo = 'modulos' OR grupo = 'validacion'
ORDER BY grupo, clave;

-- ============================================================================
-- END OF UPDATE SCRIPT
-- ============================================================================
