-- ============================================================================
-- AccessGYM - SQL Update Script
-- Actualización de base de datos para nuevas funcionalidades
-- ============================================================================

USE accessgym;

-- ============================================================================
-- 1. Actualizar tabla de configuración con nuevos campos
-- ============================================================================

-- Asegurar que la tabla configuracion tenga todas las columnas necesarias
ALTER TABLE configuracion 
    MODIFY COLUMN tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto';

-- Insertar nuevas configuraciones si no existen
INSERT IGNORE INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
-- General
('sitio_eslogan', 'Tu gimnasio, tu estilo de vida', 'texto', 'general', 'Frase descriptiva del gimnasio'),
('sitio_logo', '', 'texto', 'general', 'Ruta del logotipo del sistema'),

-- Email
('email_principal', '', 'texto', 'email', 'Email principal para envío de mensajes del sistema'),
('email_respuesta', '', 'texto', 'email', 'Email para que los clientes respondan'),

-- Contacto y Horarios
('telefono_principal', '', 'texto', 'contacto', 'Teléfono principal de contacto'),
('telefono_secundario', '', 'texto', 'contacto', 'Teléfono secundario de contacto'),
('horario_apertura', '06:00', 'texto', 'contacto', 'Hora de apertura del gimnasio'),
('horario_cierre', '22:00', 'texto', 'contacto', 'Hora de cierre del gimnasio'),
('dias_operacion', '1,2,3,4,5,6,7', 'texto', 'contacto', 'Días de la semana que opera (1=Lun, 7=Dom)'),

-- Estilos
('color_primario', '#3B82F6', 'texto', 'estilos', 'Color primario del sistema'),
('color_secundario', '#10B981', 'texto', 'estilos', 'Color secundario del sistema'),
('color_acento', '#F59E0B', 'texto', 'estilos', 'Color de acento del sistema'),

-- PayPal
('paypal_enabled', '0', 'boolean', 'pagos', 'Habilitar pagos con PayPal'),
('paypal_client_id', '', 'texto', 'pagos', 'PayPal Client ID'),
('paypal_secret', '', 'texto', 'pagos', 'PayPal Secret Key'),

-- QR API
('qr_api_enabled', '1', 'boolean', 'integracion', 'Habilitar API de generación de QR'),
('qr_api_key', '', 'texto', 'integracion', 'API Key para generación de QR (opcional)'),

-- Sistema
('mantenimiento_modo', '0', 'boolean', 'sistema', 'Modo mantenimiento (deshabilia acceso excepto superadmin)'),
('registros_por_pagina', '25', 'numero', 'sistema', 'Cantidad de registros por página en listados'),
('zona_horaria', 'America/Mexico_City', 'texto', 'sistema', 'Zona horaria del sistema');

-- ============================================================================
-- 2. Agregar índices para mejorar rendimiento
-- ============================================================================

-- Índices en tabla de configuración
ALTER TABLE configuracion 
    ADD INDEX idx_grupo (grupo),
    ADD INDEX idx_tipo (tipo);

-- Índices en tabla de socios
ALTER TABLE socios
    ADD INDEX idx_estado (estado),
    ADD INDEX idx_fecha_vencimiento (fecha_vencimiento),
    ADD INDEX idx_email (email);

-- Índices en tabla de pagos
ALTER TABLE pagos
    ADD INDEX idx_estado (estado),
    ADD INDEX idx_metodo_pago (metodo_pago);

-- Índices en tabla de bitácora
ALTER TABLE bitacora_eventos
    ADD INDEX idx_usuario (usuario_id);

-- ============================================================================
-- 3. Actualizar tabla de tipos_membresia (si es necesario)
-- ============================================================================

-- Asegurar que color tenga un valor por defecto
ALTER TABLE tipos_membresia 
    MODIFY COLUMN color VARCHAR(7) DEFAULT '#3B82F6';

-- ============================================================================
-- 4. Asegurar que la estructura de gastos esté actualizada
-- ============================================================================

-- Verificar que la tabla gastos tenga las categorías correctas
ALTER TABLE gastos 
    MODIFY COLUMN categoria ENUM('servicios', 'mantenimiento', 'personal', 'equipamiento', 'marketing', 'otros', 'suministros', 'impuestos') NOT NULL;

-- ============================================================================
-- 5. Actualizar permisos y estructura de usuarios_staff
-- ============================================================================

-- Asegurar que los roles estén correctos
ALTER TABLE usuarios_staff 
    MODIFY COLUMN rol ENUM('superadmin', 'admin', 'recepcionista') NOT NULL;

-- ============================================================================
-- 6. Crear vista para reportes financieros (opcional pero útil)
-- ============================================================================

-- Vista de resumen financiero mensual
CREATE OR REPLACE VIEW vista_resumen_financiero_mensual AS
SELECT 
    DATE_FORMAT(p.fecha_pago, '%Y-%m') as mes,
    s.id as sucursal_id,
    s.nombre as sucursal_nombre,
    SUM(p.monto) as total_ingresos,
    COUNT(p.id) as cantidad_pagos,
    AVG(p.monto) as promedio_pago,
    GROUP_CONCAT(DISTINCT p.metodo_pago) as metodos_usados
FROM pagos p
INNER JOIN sucursales s ON p.sucursal_id = s.id
WHERE p.estado = 'completado'
GROUP BY DATE_FORMAT(p.fecha_pago, '%Y-%m'), s.id, s.nombre
ORDER BY mes DESC, sucursal_nombre;

-- Vista de socios activos por membresía
CREATE OR REPLACE VIEW vista_socios_activos_membresia AS
SELECT 
    tm.id as tipo_membresia_id,
    tm.nombre as tipo_membresia,
    tm.precio,
    COUNT(s.id) as cantidad_socios,
    SUM(tm.precio) as ingresos_potenciales,
    s.sucursal_id,
    suc.nombre as sucursal_nombre
FROM tipos_membresia tm
LEFT JOIN socios s ON tm.id = s.tipo_membresia_id AND s.estado = 'activo' AND s.fecha_vencimiento >= CURDATE()
LEFT JOIN sucursales suc ON s.sucursal_id = suc.id
WHERE tm.activo = 1
GROUP BY tm.id, tm.nombre, tm.precio, s.sucursal_id, suc.nombre;

-- ============================================================================
-- 7. Actualizar datos existentes (correcciones)
-- ============================================================================

-- Actualizar estados de socios vencidos
UPDATE socios 
SET estado = 'vencido' 
WHERE fecha_vencimiento < CURDATE() AND estado = 'activo';

-- Asegurar que todos los códigos sean únicos
UPDATE socios 
SET codigo = CONCAT('SOC', LPAD(id, 6, '0'))
WHERE codigo IS NULL OR codigo = '';

-- ============================================================================
-- 8. Crear tabla para almacenar configuraciones de notificaciones (nuevo)
-- ============================================================================

CREATE TABLE IF NOT EXISTS configuracion_notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL COMMENT 'Tipo de notificación',
    canal ENUM('email', 'whatsapp', 'sms', 'sistema') NOT NULL COMMENT 'Canal de envío',
    activo TINYINT(1) DEFAULT 1,
    plantilla TEXT COMMENT 'Plantilla del mensaje',
    variables JSON COMMENT 'Variables disponibles en la plantilla',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tipo_canal (tipo, canal),
    INDEX idx_tipo (tipo),
    INDEX idx_canal (canal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar notificaciones por defecto
INSERT IGNORE INTO configuracion_notificaciones (tipo, canal, activo, plantilla, variables) VALUES
('bienvenida', 'email', 1, 'Bienvenido {nombre} a {gimnasio}. Tu código es {codigo}.', '["nombre", "gimnasio", "codigo"]'),
('vencimiento_proximo', 'whatsapp', 1, 'Hola {nombre}, tu membresía vence el {fecha_vencimiento}. Renueva ahora!', '["nombre", "fecha_vencimiento"]'),
('pago_recibido', 'email', 1, 'Pago de ${monto} recibido. Membresía activa hasta {fecha_vencimiento}.', '["monto", "fecha_vencimiento"]'),
('membresia_vencida', 'whatsapp', 1, 'Tu membresía ha vencido. Visítanos para renovar.', '[]');

-- ============================================================================
-- 9. Agregar funciones almacenadas útiles
-- ============================================================================

DELIMITER $$

-- Función para calcular días hasta vencimiento
DROP FUNCTION IF EXISTS dias_hasta_vencimiento$$
CREATE FUNCTION dias_hasta_vencimiento(fecha_venc DATE)
RETURNS INT
DETERMINISTIC
BEGIN
    RETURN DATEDIFF(fecha_venc, CURDATE());
END$$

-- Función para obtener el estado real de un socio
DROP FUNCTION IF EXISTS obtener_estado_socio$$
CREATE FUNCTION obtener_estado_socio(socio_id INT)
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE estado_actual VARCHAR(20);
    DECLARE fecha_venc DATE;
    
    SELECT fecha_vencimiento INTO fecha_venc FROM socios WHERE id = socio_id;
    
    IF fecha_venc IS NULL THEN
        SET estado_actual = 'inactivo';
    ELSEIF fecha_venc >= CURDATE() THEN
        SET estado_actual = 'activo';
    ELSE
        SET estado_actual = 'vencido';
    END IF;
    
    RETURN estado_actual;
END$$

DELIMITER ;

-- ============================================================================
-- 10. Trigger para actualizar automáticamente el estado de socios
-- ============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS actualizar_estado_socio_before_insert$$
CREATE TRIGGER actualizar_estado_socio_before_insert
BEFORE INSERT ON socios
FOR EACH ROW
BEGIN
    IF NEW.fecha_vencimiento IS NOT NULL AND NEW.fecha_vencimiento >= CURDATE() THEN
        SET NEW.estado = 'activo';
    ELSEIF NEW.fecha_vencimiento IS NOT NULL AND NEW.fecha_vencimiento < CURDATE() THEN
        SET NEW.estado = 'vencido';
    ELSE
        SET NEW.estado = 'inactivo';
    END IF;
END$$

DROP TRIGGER IF EXISTS actualizar_estado_socio_before_update$$
CREATE TRIGGER actualizar_estado_socio_before_update
BEFORE UPDATE ON socios
FOR EACH ROW
BEGIN
    IF NEW.fecha_vencimiento IS NOT NULL AND NEW.fecha_vencimiento >= CURDATE() THEN
        SET NEW.estado = 'activo';
    ELSEIF NEW.fecha_vencimiento IS NOT NULL AND NEW.fecha_vencimiento < CURDATE() THEN
        SET NEW.estado = 'vencido';
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- FIN DEL SCRIPT DE ACTUALIZACIÓN
-- ============================================================================

-- Mensaje de confirmación
SELECT 'Script de actualización ejecutado exitosamente' AS mensaje,
       NOW() AS fecha_ejecucion;

-- Verificar configuraciones instaladas
SELECT COUNT(*) as total_configuraciones FROM configuracion;

-- Mostrar resumen de la base de datos
SELECT 
    (SELECT COUNT(*) FROM socios) as total_socios,
    (SELECT COUNT(*) FROM socios WHERE estado = 'activo') as socios_activos,
    (SELECT COUNT(*) FROM tipos_membresia WHERE activo = 1) as membresias_activas,
    (SELECT COUNT(*) FROM usuarios_staff WHERE activo = 1) as usuarios_activos,
    (SELECT COUNT(*) FROM configuracion) as configuraciones;
