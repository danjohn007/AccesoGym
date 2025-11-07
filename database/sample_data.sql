-- ============================================================================
-- AccessGYM - SQL Sample Data
-- Datos de ejemplo para pruebas y demostración
-- ============================================================================

USE accessgym;

-- ============================================================================
-- ADVERTENCIA: Este script insertará datos de prueba en la base de datos
-- NO ejecutar en producción con datos reales
-- ============================================================================

-- Limpiar datos previos (opcional - comentar si no se desea)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE bitacora_eventos;
-- TRUNCATE TABLE mensajes_whatsapp;
-- TRUNCATE TABLE accesos;
-- TRUNCATE TABLE pagos;
-- TRUNCATE TABLE socios;
-- DELETE FROM usuarios_staff WHERE id > 1;
-- DELETE FROM tipos_membresia WHERE id > 4;
-- DELETE FROM dispositivos_shelly WHERE id > 0;
-- SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 1. SUCURSALES ADICIONALES
-- ============================================================================

INSERT INTO sucursales (nombre, direccion, telefono, email, activo) VALUES
('Sucursal Norte', 'Av. Revolución 1234, Col. Centro Norte', '555-111-2222', 'norte@accessgym.com', 1),
('Sucursal Sur', 'Calle Reforma 567, Col. Sur', '555-333-4444', 'sur@accessgym.com', 1),
('Sucursal Oriente', 'Blvd. Oriente 890, Col. Este', '555-555-6666', 'oriente@accessgym.com', 1);

-- ============================================================================
-- 2. USUARIOS DEL SISTEMA
-- ============================================================================

-- Admin para Sucursal Norte (password: admin123)
INSERT INTO usuarios_staff (nombre, email, telefono, password, rol, sucursal_id, activo) VALUES
('María García', 'maria.garcia@accessgym.com', '555-111-1111', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'admin', 2, 1),
('Carlos Rodríguez', 'carlos.rodriguez@accessgym.com', '555-222-2222', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'admin', 3, 1),
('Ana López', 'ana.lopez@accessgym.com', '555-333-3333', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'admin', 4, 1);

-- Recepcionistas
INSERT INTO usuarios_staff (nombre, email, telefono, password, rol, sucursal_id, activo) VALUES
('Laura Martínez', 'laura.martinez@accessgym.com', '555-444-4444', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'recepcionista', 1, 1),
('Pedro Sánchez', 'pedro.sanchez@accessgym.com', '555-555-5555', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'recepcionista', 2, 1),
('Isabel Fernández', 'isabel.fernandez@accessgym.com', '555-666-6666', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'recepcionista', 3, 1);

-- ============================================================================
-- 3. TIPOS DE MEMBRESÍA ADICIONALES
-- ============================================================================

INSERT INTO tipos_membresia (nombre, descripcion, duracion_dias, precio, acceso_horario_inicio, acceso_horario_fin, dias_semana, color, activo) VALUES
('Diaria', 'Acceso por un día', 1, 100.00, '06:00:00', '22:00:00', '1,2,3,4,5,6,7', '#EC4899', 1),
('Semanal', 'Acceso por 7 días', 7, 300.00, '06:00:00', '22:00:00', '1,2,3,4,5,6,7', '#06B6D4', 1),
('Quincenal', 'Acceso por 15 días', 15, 400.00, '06:00:00', '22:00:00', '1,2,3,4,5,6,7', '#14B8A6', 1),
('Matutino', 'Acceso matutino mensual', 30, 350.00, '06:00:00', '12:00:00', '1,2,3,4,5', '#F97316', 1),
('Nocturno', 'Acceso nocturno mensual', 30, 350.00, '18:00:00', '22:00:00', '1,2,3,4,5', '#6366F1', 1),
('Fin de Semana', 'Solo sábados y domingos', 30, 300.00, '08:00:00', '18:00:00', '6,7', '#EAB308', 1);

-- ============================================================================
-- 4. SOCIOS (100 SOCIOS DE EJEMPLO)
-- ============================================================================

INSERT INTO socios (codigo, nombre, apellido, email, telefono, direccion, fecha_nacimiento, sucursal_id, tipo_membresia_id, fecha_inicio, fecha_vencimiento, estado) VALUES
('SOC000001', 'Juan', 'Pérez García', 'juan.perez@email.com', '555-1001', 'Calle 1 #123', '1990-05-15', 1, 1, '2024-11-01', '2024-12-01', 'activo'),
('SOC000002', 'María', 'López Martínez', 'maria.lopez@email.com', '555-1002', 'Av. 2 #456', '1988-08-22', 1, 2, '2024-09-01', '2024-12-01', 'activo'),
('SOC000003', 'Carlos', 'González Ruiz', 'carlos.gonzalez@email.com', '555-1003', 'Blvd. 3 #789', '1995-03-10', 1, 3, '2024-05-01', '2024-11-01', 'activo'),
('SOC000004', 'Ana', 'Rodríguez Silva', 'ana.rodriguez@email.com', '555-1004', 'Calle 4 #321', '1992-11-30', 2, 1, '2024-11-05', '2024-12-05', 'activo'),
('SOC000005', 'Luis', 'Hernández Torres', 'luis.hernandez@email.com', '555-1005', 'Av. 5 #654', '1987-07-18', 2, 4, '2024-01-01', '2025-01-01', 'activo'),
('SOC000006', 'Laura', 'Martínez Cruz', 'laura.martinez@email.com', '555-1006', 'Calle 6 #987', '1993-12-05', 2, 1, '2024-10-15', '2024-11-15', 'activo'),
('SOC000007', 'Pedro', 'Sánchez Díaz', 'pedro.sanchez@email.com', '555-1007', 'Blvd. 7 #147', '1991-04-25', 3, 2, '2024-08-01', '2024-11-01', 'activo'),
('SOC000008', 'Isabel', 'Fernández Morales', 'isabel.fernandez@email.com', '555-1008', 'Av. 8 #258', '1989-09-14', 3, 3, '2024-05-15', '2024-11-15', 'activo'),
('SOC000009', 'Miguel', 'García López', 'miguel.garcia@email.com', '555-1009', 'Calle 9 #369', '1994-06-20', 3, 1, '2024-11-10', '2024-12-10', 'activo'),
('SOC000010', 'Carmen', 'Ruiz Vargas', 'carmen.ruiz@email.com', '555-1010', 'Blvd. 10 #741', '1986-02-28', 4, 4, '2024-02-01', '2025-02-01', 'activo'),
-- Más socios...
('SOC000011', 'Roberto', 'Torres Jiménez', 'roberto.torres@email.com', '555-1011', 'Av. 11 #852', '1990-01-12', 1, 5, '2024-11-01', '2024-12-01', 'activo'),
('SOC000012', 'Patricia', 'Ramírez Castro', 'patricia.ramirez@email.com', '555-1012', 'Calle 12 #963', '1991-10-08', 1, 6, '2024-11-01', '2024-12-01', 'activo'),
('SOC000013', 'Daniel', 'Flores Ortiz', 'daniel.flores@email.com', '555-1013', 'Blvd. 13 #159', '1988-05-17', 2, 7, '2024-11-01', '2024-12-01', 'activo'),
('SOC000014', 'Elena', 'Moreno Gutiérrez', 'elena.moreno@email.com', '555-1014', 'Av. 14 #357', '1992-08-29', 2, 8, '2024-11-01', '2024-12-01', 'activo'),
('SOC000015', 'Francisco', 'Vázquez Ramos', 'francisco.vazquez@email.com', '555-1015', 'Calle 15 #753', '1985-12-03', 3, 9, '2024-11-01', '2024-12-01', 'activo'),
-- Socios vencidos o inactivos
('SOC000016', 'Sofía', 'Castro Mendoza', 'sofia.castro@email.com', '555-1016', 'Blvd. 16 #951', '1993-03-22', 1, 1, '2024-09-01', '2024-10-01', 'vencido'),
('SOC000017', 'Jorge', 'Navarro Paredes', 'jorge.navarro@email.com', '555-1017', 'Av. 17 #159', '1987-07-14', 2, 1, '2024-08-15', '2024-09-15', 'vencido'),
('SOC000018', 'Mónica', 'Herrera Soto', 'monica.herrera@email.com', '555-1018', 'Calle 18 #357', '1990-11-27', 3, 1, '2024-09-20', '2024-10-20', 'vencido'),
('SOC000019', 'Ricardo', 'Domínguez Peña', 'ricardo.dominguez@email.com', '555-1019', 'Blvd. 19 #753', '1989-04-09', 4, NULL, NULL, NULL, 'inactivo'),
('SOC000020', 'Verónica', 'Rojas Aguilar', 'veronica.rojas@email.com', '555-1020', 'Av. 20 #951', '1991-09-16', 1, NULL, NULL, NULL, 'inactivo');

-- Agregar más socios (simplificado con datos similares)
INSERT INTO socios (codigo, nombre, apellido, email, telefono, sucursal_id, tipo_membresia_id, fecha_inicio, fecha_vencimiento, estado)
SELECT 
    CONCAT('SOC', LPAD(20 + n, 6, '0')),
    CONCAT('Socio', n),
    CONCAT('Apellido', n),
    CONCAT('socio', n, '@email.com'),
    CONCAT('555-', 1020 + n),
    ((n % 4) + 1),
    ((n % 10) + 1),
    DATE_SUB(CURDATE(), INTERVAL (n % 90) DAY),
    DATE_ADD(CURDATE(), INTERVAL ((n % 60) - 30) DAY),
    CASE 
        WHEN (n % 60) > 30 THEN 'activo'
        WHEN (n % 60) BETWEEN 20 AND 30 THEN 'vencido'
        ELSE 'inactivo'
    END
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 AS N UNION SELECT 1) c
) numbers
WHERE n BETWEEN 1 AND 80
LIMIT 80;

-- ============================================================================
-- 5. DISPOSITIVOS SHELLY
-- ============================================================================

INSERT INTO dispositivos_shelly (nombre, device_id, tipo, sucursal_id, ubicacion, tiempo_apertura, estado, activo) VALUES
('Puerta Principal Norte', 'shelly_001_norte', 'puerta_magnetica', 2, 'Entrada Principal', 5, 'online', 1),
('Puerta Gimnasio Norte', 'shelly_002_norte', 'puerta_magnetica', 2, 'Área de Ejercicios', 5, 'online', 1),
('Puerta Principal Sur', 'shelly_003_sur', 'puerta_magnetica', 3, 'Entrada Principal', 5, 'offline', 1),
('Puerta Emergencia Sur', 'shelly_004_sur', 'puerta_magnetica', 3, 'Salida de Emergencia', 3, 'online', 1),
('Puerta Principal Oriente', 'shelly_005_oriente', 'puerta_magnetica', 4, 'Entrada Principal', 5, 'online', 1),
('Puerta Vestidores Oriente', 'shelly_006_oriente', 'puerta_magnetica', 4, 'Vestidores', 5, 'online', 1);

-- ============================================================================
-- 6. PAGOS (200 PAGOS DE EJEMPLO)
-- ============================================================================

INSERT INTO pagos (socio_id, tipo_membresia_id, monto, metodo_pago, estado, sucursal_id, usuario_registro, fecha_pago) 
SELECT 
    ((n % 100) + 1),
    ((n % 10) + 1),
    CASE (n % 10) + 1
        WHEN 1 THEN 500.00
        WHEN 2 THEN 1350.00
        WHEN 3 THEN 2400.00
        WHEN 4 THEN 4500.00
        WHEN 5 THEN 100.00
        WHEN 6 THEN 300.00
        WHEN 7 THEN 400.00
        WHEN 8 THEN 350.00
        WHEN 9 THEN 350.00
        ELSE 300.00
    END,
    CASE (n % 6)
        WHEN 0 THEN 'efectivo'
        WHEN 1 THEN 'tarjeta'
        WHEN 2 THEN 'transferencia'
        WHEN 3 THEN 'stripe'
        WHEN 4 THEN 'mercadopago'
        ELSE 'efectivo'
    END,
    CASE 
        WHEN n % 20 = 0 THEN 'pendiente'
        WHEN n % 15 = 0 THEN 'cancelado'
        ELSE 'completado'
    END,
    ((n % 4) + 1),
    ((n % 6) + 1),
    DATE_SUB(CURDATE(), INTERVAL (n % 180) DAY)
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2) c
) numbers
WHERE n BETWEEN 1 AND 200
LIMIT 200;

-- ============================================================================
-- 7. ACCESOS (500 REGISTROS DE ACCESO)
-- ============================================================================

INSERT INTO accesos (socio_id, dispositivo_id, sucursal_id, tipo, estado, fecha_hora)
SELECT 
    ((n % 100) + 1),
    ((n % 6) + 1),
    ((n % 4) + 1),
    CASE (n % 4)
        WHEN 0 THEN 'qr'
        WHEN 1 THEN 'manual'
        WHEN 2 THEN 'whatsapp'
        ELSE 'qr'
    END,
    CASE 
        WHEN n % 25 = 0 THEN 'denegado'
        ELSE 'permitido'
    END,
    DATE_SUB(NOW(), INTERVAL (n * 15) MINUTE)
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) c
) numbers
WHERE n BETWEEN 1 AND 500
LIMIT 500;

-- ============================================================================
-- 8. GASTOS (100 GASTOS DE EJEMPLO)
-- ============================================================================

INSERT INTO gastos (concepto, categoria, monto, sucursal_id, fecha_gasto, usuario_registro) VALUES
('Pago de luz octubre', 'servicios', 3500.00, 1, '2024-10-05', 2),
('Mantenimiento aire acondicionado', 'mantenimiento', 2800.00, 1, '2024-10-10', 2),
('Salarios quincena 1', 'personal', 45000.00, 1, '2024-10-15', 2),
('Nuevas mancuernas', 'equipamiento', 12000.00, 1, '2024-10-18', 2),
('Publicidad Facebook', 'marketing', 2000.00, 1, '2024-10-20', 2),
('Productos de limpieza', 'otros', 800.00, 1, '2024-10-22', 2),
('Agua embotellada', 'otros', 600.00, 2, '2024-10-08', 3),
('Reparación caminadora', 'mantenimiento', 3500.00, 2, '2024-10-12', 3),
('Internet octubre', 'servicios', 1200.00, 2, '2024-10-05', 3),
('Salarios quincena 1', 'personal', 38000.00, 2, '2024-10-15', 3),
('Gas octubre', 'servicios', 1800.00, 3, '2024-10-06', 4),
('Pintura instalaciones', 'mantenimiento', 5500.00, 3, '2024-10-14', 4),
('Salarios quincena 1', 'personal', 42000.00, 3, '2024-10-15', 4),
('Toallas nuevas', 'equipamiento', 1500.00, 3, '2024-10-20', 4);

-- Agregar más gastos automáticamente
INSERT INTO gastos (concepto, categoria, monto, sucursal_id, fecha_gasto, usuario_registro)
SELECT 
    CASE (n % 7)
        WHEN 0 THEN CONCAT('Servicio tipo ', n)
        WHEN 1 THEN CONCAT('Mantenimiento ', n)
        WHEN 2 THEN CONCAT('Pago personal ', n)
        WHEN 3 THEN CONCAT('Equipo nuevo ', n)
        WHEN 4 THEN CONCAT('Campaña marketing ', n)
        WHEN 5 THEN CONCAT('Suministros ', n)
        ELSE CONCAT('Gasto varios ', n)
    END,
    CASE (n % 7)
        WHEN 0 THEN 'servicios'
        WHEN 1 THEN 'mantenimiento'
        WHEN 2 THEN 'personal'
        WHEN 3 THEN 'equipamiento'
        WHEN 4 THEN 'marketing'
        ELSE 'otros'
    END,
    (FLOOR(RAND() * 10000) + 500),
    ((n % 4) + 1),
    DATE_SUB(CURDATE(), INTERVAL (n % 180) DAY),
    ((n % 6) + 1)
FROM (
    SELECT a.N + b.N * 10 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8) b
) numbers
WHERE n BETWEEN 1 AND 86
LIMIT 86;

-- ============================================================================
-- 9. EVENTOS DE BITÁCORA (300 EVENTOS)
-- ============================================================================

INSERT INTO bitacora_eventos (tipo, descripcion, usuario_id, socio_id, sucursal_id, ip_address, fecha_hora)
SELECT 
    CASE (n % 7)
        WHEN 0 THEN 'acceso'
        WHEN 1 THEN 'pago'
        WHEN 2 THEN 'modificacion'
        WHEN 3 THEN 'sistema'
        WHEN 4 THEN 'dispositivo'
        WHEN 5 THEN 'whatsapp'
        ELSE 'sistema'
    END,
    CASE (n % 7)
        WHEN 0 THEN CONCAT('Acceso registrado socio #', (n % 100) + 1)
        WHEN 1 THEN CONCAT('Pago registrado $', (n * 100))
        WHEN 2 THEN CONCAT('Modificación de socio #', (n % 100) + 1)
        WHEN 3 THEN 'Inicio de sesión en el sistema'
        WHEN 4 THEN 'Dispositivo activado'
        WHEN 5 THEN 'Mensaje de WhatsApp enviado'
        ELSE 'Evento del sistema'
    END,
    ((n % 6) + 1),
    CASE WHEN n % 3 = 0 THEN ((n % 100) + 1) ELSE NULL END,
    ((n % 4) + 1),
    CONCAT('192.168.1.', (n % 255)),
    DATE_SUB(NOW(), INTERVAL (n * 30) MINUTE)
FROM (
    SELECT a.N + b.N * 10 + c.N * 100 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2) c
) numbers
WHERE n BETWEEN 1 AND 300
LIMIT 300;

-- ============================================================================
-- 10. MENSAJES DE WHATSAPP (50 MENSAJES)
-- ============================================================================

INSERT INTO mensajes_whatsapp (telefono, socio_id, tipo, mensaje, comando, estado)
SELECT 
    CONCAT('555-', 1000 + (n % 100)),
    ((n % 100) + 1),
    CASE n % 2
        WHEN 0 THEN 'entrante'
        ELSE 'saliente'
    END,
    CASE (n % 5)
        WHEN 0 THEN 'Hola, ¿cuál es mi estado de membresía?'
        WHEN 1 THEN 'Quiero renovar mi membresía'
        WHEN 2 THEN 'Abrir puerta'
        WHEN 3 THEN 'Tu membresía vence pronto'
        ELSE 'Gracias por tu mensaje'
    END,
    CASE (n % 5)
        WHEN 0 THEN 'status'
        WHEN 1 THEN 'renovar'
        WHEN 2 THEN 'abrir'
        ELSE NULL
    END,
    CASE (n % 4)
        WHEN 0 THEN 'enviado'
        WHEN 1 THEN 'entregado'
        WHEN 2 THEN 'leido'
        ELSE 'enviado'
    END
FROM (
    SELECT a.N + b.N * 10 as n
    FROM 
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b
) numbers
WHERE n BETWEEN 1 AND 50
LIMIT 50;

-- ============================================================================
-- FIN - RESUMEN DE DATOS INSERTADOS
-- ============================================================================

SELECT 
    'Datos de ejemplo insertados exitosamente' AS mensaje,
    NOW() AS fecha_ejecucion;

-- Mostrar resumen
SELECT 
    (SELECT COUNT(*) FROM sucursales) as total_sucursales,
    (SELECT COUNT(*) FROM usuarios_staff) as total_usuarios,
    (SELECT COUNT(*) FROM tipos_membresia) as total_tipos_membresia,
    (SELECT COUNT(*) FROM socios) as total_socios,
    (SELECT COUNT(*) FROM socios WHERE estado = 'activo') as socios_activos,
    (SELECT COUNT(*) FROM dispositivos_shelly) as total_dispositivos,
    (SELECT COUNT(*) FROM pagos) as total_pagos,
    (SELECT COUNT(*) FROM accesos) as total_accesos,
    (SELECT COUNT(*) FROM gastos) as total_gastos,
    (SELECT COUNT(*) FROM bitacora_eventos) as total_eventos_bitacora,
    (SELECT COUNT(*) FROM mensajes_whatsapp) as total_mensajes_whatsapp;

-- Mostrar algunos datos de ejemplo
SELECT 'Top 10 Socios Activos:' as info;
SELECT codigo, nombre, apellido, email, estado, fecha_vencimiento 
FROM socios 
WHERE estado = 'activo' 
ORDER BY fecha_vencimiento DESC 
LIMIT 10;

SELECT 'Resumen Financiero:' as info;
SELECT 
    SUM(monto) as total_ingresos,
    COUNT(*) as cantidad_pagos,
    AVG(monto) as ticket_promedio
FROM pagos 
WHERE estado = 'completado';
