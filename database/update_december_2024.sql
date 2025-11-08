-- AccessGYM Database Update - December 2024
-- Actualización del sistema con nuevas funcionalidades
-- Compatible con MySQL 8.0+

USE accessgym;

-- ============================================================
-- 1. TABLA: dispositivos_hikvision
-- Dispositivos de control de acceso HikVision
-- ============================================================
CREATE TABLE IF NOT EXISTS dispositivos_hikvision (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del dispositivo',
    ip VARCHAR(45) NOT NULL COMMENT 'Dirección IP del dispositivo',
    puerto INT DEFAULT 80 COMMENT 'Puerto HTTP/HTTPS',
    usuario VARCHAR(50) NOT NULL COMMENT 'Usuario de administrador',
    password VARCHAR(255) NOT NULL COMMENT 'Contraseña del dispositivo',
    numero_puerta TINYINT DEFAULT 1 COMMENT 'Número de puerta (1-8)',
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(100) COMMENT 'Ubicación física del dispositivo',
    estado ENUM('online', 'offline', 'error') DEFAULT 'offline',
    ultima_conexion TIMESTAMP NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_sucursal (sucursal_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dispositivos de control de acceso HikVision con API ISAPI';

-- ============================================================
-- 2. TABLA: ingresos_extra
-- Registro de ingresos adicionales (no membresías)
-- ============================================================
CREATE TABLE IF NOT EXISTS ingresos_extra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto VARCHAR(200) NOT NULL COMMENT 'Descripción del ingreso',
    categoria ENUM('ventas', 'servicios', 'alquiler', 'entrenamiento', 'eventos', 'otros') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    sucursal_id INT NOT NULL,
    usuario_registro INT,
    comprobante VARCHAR(255) COMMENT 'Ruta del archivo de comprobante',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    INDEX idx_fecha_ingreso (fecha_ingreso),
    INDEX idx_sucursal (sucursal_id),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Ingresos adicionales fuera de membresías';

-- ============================================================
-- 3. TABLA: activos_inventario
-- Gestión de activos, equipos e inventario
-- ============================================================
CREATE TABLE IF NOT EXISTS activos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL COMMENT 'Nombre del activo',
    codigo VARCHAR(50) UNIQUE NOT NULL COMMENT 'Código único del activo',
    tipo ENUM('equipo', 'mobiliario', 'electronico', 'consumible', 'otro') NOT NULL,
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(100) COMMENT 'Ubicación dentro de la sucursal',
    estado ENUM('excelente', 'bueno', 'regular', 'malo', 'fuera_servicio') DEFAULT 'bueno',
    cantidad INT DEFAULT 1 COMMENT 'Cantidad disponible',
    valor_compra DECIMAL(10,2) COMMENT 'Valor de compra original',
    fecha_compra DATE COMMENT 'Fecha de adquisición',
    proveedor VARCHAR(200) COMMENT 'Proveedor del activo',
    descripcion TEXT COMMENT 'Descripción detallada',
    notas TEXT COMMENT 'Notas adicionales',
    foto VARCHAR(255) COMMENT 'Ruta de la fotografía',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_codigo (codigo),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado),
    INDEX idx_sucursal (sucursal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Inventario de activos y equipamiento por sucursal';

-- ============================================================
-- 4. TABLA: categorias_financieras
-- Categorías personalizables para movimientos financieros
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias_financieras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Color en formato hexadecimal',
    descripcion TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nombre_tipo (nombre, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Categorías personalizables para clasificar movimientos financieros';

-- ============================================================
-- 5. Actualizar tabla usuarios_staff para foto de perfil
-- ============================================================
ALTER TABLE usuarios_staff 
ADD COLUMN IF NOT EXISTS foto VARCHAR(255) COMMENT 'Ruta de la foto de perfil'
AFTER telefono;

-- ============================================================
-- 6. DATOS DE EJEMPLO - Dispositivos HikVision
-- ============================================================
-- Insertar dispositivos de ejemplo solo si no existen registros
INSERT INTO dispositivos_hikvision (nombre, ip, puerto, usuario, password, numero_puerta, sucursal_id, ubicacion, estado)
SELECT * FROM (
    SELECT 
        'Puerta Principal - HikVision' as nombre,
        '192.168.1.100' as ip,
        80 as puerto,
        'admin' as usuario,
        'Admin123' as password,
        1 as numero_puerta,
        1 as sucursal_id,
        'Entrada Principal' as ubicacion,
        'offline' as estado
) AS tmp
WHERE NOT EXISTS (
    SELECT id FROM dispositivos_hikvision WHERE ip = '192.168.1.100'
) LIMIT 1;

-- ============================================================
-- 7. DATOS DE EJEMPLO - Activos e Inventario
-- ============================================================
-- Insertar activos de ejemplo solo si la tabla está vacía
INSERT INTO activos_inventario (nombre, codigo, tipo, sucursal_id, ubicacion, estado, cantidad, valor_compra, fecha_compra, proveedor, descripcion)
SELECT * FROM (
    SELECT 
        'Caminadora Profesional NordicTrack' as nombre,
        'EQ-CAM-001' as codigo,
        'equipo' as tipo,
        1 as sucursal_id,
        'Área de Cardio' as ubicacion,
        'excelente' as estado,
        2 as cantidad,
        35000.00 as valor_compra,
        '2024-01-15' as fecha_compra,
        'Fitness Equipment Pro' as proveedor,
        'Caminadora profesional con pantalla táctil, velocidad máxima 20 km/h, inclinación automática' as descripcion
    UNION ALL
    SELECT 
        'Bicicleta Estática Spinning',
        'EQ-BIC-001',
        'equipo',
        1,
        'Área de Spinning',
        'bueno',
        10,
        8500.00,
        '2023-11-20',
        'Spinning Pro',
        'Bicicleta de spinning con ajuste de resistencia y monitor de frecuencia cardíaca'
    UNION ALL
    SELECT 
        'Rack de Mancuernas Completo',
        'EQ-MANC-001',
        'equipo',
        1,
        'Área de Pesas Libres',
        'excelente',
        1,
        15000.00,
        '2024-02-10',
        'Weight Equipment SA',
        'Rack con juego completo de mancuernas de 2.5 kg a 50 kg'
    UNION ALL
    SELECT 
        'Escritorio Recepción',
        'MOB-ESC-001',
        'mobiliario',
        1,
        'Recepción',
        'bueno',
        1,
        4500.00,
        '2023-10-05',
        'Muebles Oficina',
        'Escritorio modular para recepción con cajones'
    UNION ALL
    SELECT 
        'Locker Metálico 12 Puertas',
        'MOB-LOCK-001',
        'mobiliario',
        1,
        'Vestidores',
        'excelente',
        4,
        3200.00,
        '2024-03-01',
        'Lockers Industrial',
        'Locker metálico de 12 compartimentos con cerradura de combinación'
    UNION ALL
    SELECT 
        'Computadora All-in-One HP',
        'ELEC-PC-001',
        'electronico',
        1,
        'Recepción',
        'excelente',
        2,
        18000.00,
        '2024-01-20',
        'Tech Solutions',
        'PC All-in-One, Intel Core i5, 16GB RAM, 512GB SSD, Windows 11 Pro'
    UNION ALL
    SELECT 
        'Sistema de Sonido Profesional',
        'ELEC-AUD-001',
        'electronico',
        1,
        'Área de Clases',
        'bueno',
        1,
        12000.00,
        '2023-09-15',
        'Audio Pro Mexico',
        'Sistema de audio con amplificador, bocinas y micrófono inalámbrico'
    UNION ALL
    SELECT 
        'Toallas Gym - Pack 50 unidades',
        'CONS-TOAL-001',
        'consumible',
        1,
        'Almacén',
        'excelente',
        100,
        2500.00,
        '2024-06-01',
        'Textiles Gym',
        'Toallas de microfibra para gimnasio, 40x80cm'
    UNION ALL
    SELECT 
        'Productos de Limpieza',
        'CONS-LIMP-001',
        'consumible',
        1,
        'Almacén',
        'bueno',
        50,
        1200.00,
        '2024-06-15',
        'Limpieza Total',
        'Kit de productos de limpieza: desinfectante, detergente, aromatizante'
    UNION ALL
    SELECT 
        'Banco de Pesas Ajustable',
        'EQ-BANC-001',
        'equipo',
        1,
        'Área de Pesas',
        'excelente',
        5,
        4500.00,
        '2024-04-10',
        'Fitness Equipment Pro',
        'Banco ajustable para ejercicios de pesas con respaldo reclinable'
) AS tmp
WHERE NOT EXISTS (
    SELECT id FROM activos_inventario LIMIT 1
);

-- ============================================================
-- 8. DATOS DE EJEMPLO - Categorías Financieras
-- ============================================================
INSERT INTO categorias_financieras (nombre, tipo, color, descripcion)
SELECT * FROM (
    SELECT 'Ventas de Suplementos' as nombre, 'ingreso' as tipo, '#10B981' as color, 'Ingresos por venta de suplementos y productos' as descripcion
    UNION ALL
    SELECT 'Entrenamiento Personal', 'ingreso', '#3B82F6', 'Ingresos por sesiones de entrenamiento personalizado'
    UNION ALL
    SELECT 'Alquiler de Espacios', 'ingreso', '#8B5CF6', 'Ingresos por alquiler de espacios para eventos'
    UNION ALL
    SELECT 'Servicios Adicionales', 'ingreso', '#F59E0B', 'Otros servicios complementarios'
    UNION ALL
    SELECT 'Servicios Básicos', 'egreso', '#EF4444', 'Luz, agua, internet, teléfono'
    UNION ALL
    SELECT 'Mantenimiento Preventivo', 'egreso', '#F59E0B', 'Mantenimiento regular de equipos'
    UNION ALL
    SELECT 'Reparaciones', 'egreso', '#DC2626', 'Reparaciones de equipos y edificio'
    UNION ALL
    SELECT 'Nómina', 'egreso', '#7C3AED', 'Pagos de sueldos y salarios'
    UNION ALL
    SELECT 'Marketing Digital', 'egreso', '#06B6D4', 'Publicidad en redes sociales y Google'
    UNION ALL
    SELECT 'Material Promocional', 'egreso', '#EC4899', 'Flyers, carteles, y material publicitario'
) AS tmp
WHERE NOT EXISTS (
    SELECT id FROM categorias_financieras LIMIT 1
);

-- ============================================================
-- 9. DATOS DE EJEMPLO - Ingresos Extra
-- ============================================================
INSERT INTO ingresos_extra (concepto, categoria, monto, fecha_ingreso, sucursal_id, notas)
SELECT * FROM (
    SELECT 
        'Venta de Proteínas y Suplementos - Junio' as concepto,
        'ventas' as categoria,
        15850.00 as monto,
        '2024-06-30' as fecha_ingreso,
        1 as sucursal_id,
        'Ventas del mes de junio: proteínas, creatina, aminoácidos' as notas
    UNION ALL
    SELECT 
        'Sesiones de Entrenamiento Personal - Julio',
        'entrenamiento',
        28500.00,
        '2024-07-31',
        1,
        '15 sesiones de entrenamiento personal durante julio'
    UNION ALL
    SELECT 
        'Alquiler Sala para Evento Corporativo',
        'alquiler',
        12000.00,
        '2024-08-15',
        1,
        'Alquiler de sala para evento de empresa, 4 horas'
    UNION ALL
    SELECT 
        'Venta de Accesorios Deportivos',
        'ventas',
        8750.00,
        '2024-08-20',
        1,
        'Venta de guantes, correas, rodilleras y muñequeras'
    UNION ALL
    SELECT 
        'Clase Especial de Yoga',
        'servicios',
        4500.00,
        '2024-09-10',
        1,
        'Clase especial de yoga con instructor invitado'
) AS tmp
WHERE NOT EXISTS (
    SELECT id FROM ingresos_extra LIMIT 1
);

-- ============================================================
-- 10. Crear directorios de uploads si no existen
-- ============================================================
-- NOTA: Estos comandos deben ejecutarse desde el sistema operativo
-- mkdir -p ../uploads/activos
-- mkdir -p ../uploads/staff
-- chmod 755 ../uploads/activos
-- chmod 755 ../uploads/staff

-- ============================================================
-- 11. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================

-- Optimizar búsquedas de bitácora de eventos
ALTER TABLE bitacora_eventos 
ADD INDEX IF NOT EXISTS idx_tipo_fecha (tipo, fecha_hora),
ADD INDEX IF NOT EXISTS idx_sucursal_fecha (sucursal_id, fecha_hora);

-- Optimizar búsquedas en pagos
ALTER TABLE pagos
ADD INDEX IF NOT EXISTS idx_estado_fecha (estado, fecha_pago),
ADD INDEX IF NOT EXISTS idx_metodo_pago (metodo_pago);

-- Optimizar búsquedas en gastos
ALTER TABLE gastos
ADD INDEX IF NOT EXISTS idx_categoria_fecha (categoria, fecha_gasto);

-- ============================================================
-- 12. VISTAS ÚTILES PARA REPORTES
-- ============================================================

-- Vista consolidada de movimientos financieros
CREATE OR REPLACE VIEW v_movimientos_financieros AS
SELECT 
    'ingreso' as tipo,
    'membresia' as subtipo,
    p.id,
    CONCAT('Pago membresía - ', s.nombre, ' ', s.apellido) as concepto,
    p.monto,
    DATE(p.fecha_pago) as fecha,
    p.sucursal_id,
    su.nombre as sucursal_nombre,
    p.metodo_pago as categoria,
    p.estado,
    p.created_at
FROM pagos p
INNER JOIN socios s ON p.socio_id = s.id
INNER JOIN sucursales su ON p.sucursal_id = su.id
WHERE p.estado = 'completado'

UNION ALL

SELECT 
    'ingreso' as tipo,
    'extra' as subtipo,
    ie.id,
    ie.concepto,
    ie.monto,
    ie.fecha_ingreso as fecha,
    ie.sucursal_id,
    su.nombre as sucursal_nombre,
    ie.categoria,
    'completado' as estado,
    ie.created_at
FROM ingresos_extra ie
INNER JOIN sucursales su ON ie.sucursal_id = su.id

UNION ALL

SELECT 
    'egreso' as tipo,
    'gasto' as subtipo,
    g.id,
    g.concepto,
    -g.monto as monto,
    g.fecha_gasto as fecha,
    g.sucursal_id,
    su.nombre as sucursal_nombre,
    g.categoria,
    'completado' as estado,
    g.created_at
FROM gastos g
INNER JOIN sucursales su ON g.sucursal_id = su.id;

-- Vista de activos por sucursal y estado
CREATE OR REPLACE VIEW v_activos_resumen AS
SELECT 
    s.id as sucursal_id,
    s.nombre as sucursal_nombre,
    a.tipo,
    a.estado,
    COUNT(*) as cantidad_activos,
    SUM(a.cantidad) as unidades_totales,
    SUM(a.valor_compra * a.cantidad) as valor_total
FROM activos_inventario a
INNER JOIN sucursales s ON a.sucursal_id = s.id
GROUP BY s.id, s.nombre, a.tipo, a.estado;

-- ============================================================
-- FIN DE LA ACTUALIZACIÓN
-- ============================================================

-- Verificación de tablas creadas
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros Aprox.',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'Tamaño (MB)',
    TABLE_COMMENT as 'Comentario'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'accessgym'
  AND TABLE_NAME IN (
    'dispositivos_hikvision',
    'ingresos_extra',
    'activos_inventario',
    'categorias_financieras'
  )
ORDER BY TABLE_NAME;

-- Mensaje de finalización
SELECT 
    '✓ Actualización completada exitosamente' as Estado,
    NOW() as Fecha,
    'Diciembre 2024' as Version;
