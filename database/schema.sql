-- AccessGYM Database Schema
-- Sistema de Control de Gimnasio con Activación de Puertas Magnéticas

-- Database creation
CREATE DATABASE IF NOT EXISTS accessgym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE accessgym;

-- Table: sucursales (branches)
CREATE TABLE sucursales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: usuarios_staff (system users)
CREATE TABLE usuarios_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'recepcionista') NOT NULL,
    sucursal_id INT,
    activo TINYINT(1) DEFAULT 1,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: tipos_membresia (membership types)
CREATE TABLE tipos_membresia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    duracion_dias INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    acceso_horario_inicio TIME,
    acceso_horario_fin TIME,
    dias_semana VARCHAR(50) DEFAULT '1,2,3,4,5,6,7',
    color VARCHAR(7) DEFAULT '#3B82F6',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: socios (members)
CREATE TABLE socios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20) NOT NULL,
    telefono_emergencia VARCHAR(20),
    direccion TEXT,
    fecha_nacimiento DATE,
    foto VARCHAR(255),
    qr_code VARCHAR(255),
    sucursal_id INT NOT NULL,
    tipo_membresia_id INT,
    fecha_inicio DATE,
    fecha_vencimiento DATE,
    estado ENUM('activo', 'inactivo', 'suspendido', 'vencido') DEFAULT 'inactivo',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE RESTRICT,
    FOREIGN KEY (tipo_membresia_id) REFERENCES tipos_membresia(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: dispositivos_shelly (Shelly devices)
CREATE TABLE dispositivos_shelly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    tipo VARCHAR(50) DEFAULT 'puerta_magnetica',
    sucursal_id INT NOT NULL,
    ubicacion VARCHAR(100),
    tiempo_apertura INT DEFAULT 5,
    estado ENUM('online', 'offline', 'error') DEFAULT 'offline',
    ultima_conexion TIMESTAMP NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: accesos (access log)
CREATE TABLE accesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    dispositivo_id INT,
    sucursal_id INT NOT NULL,
    tipo ENUM('qr', 'manual', 'whatsapp', 'api') NOT NULL,
    estado ENUM('permitido', 'denegado') NOT NULL,
    motivo VARCHAR(255),
    usuario_autorizo INT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_shelly(id) ON DELETE SET NULL,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_autorizo) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    INDEX idx_fecha_hora (fecha_hora),
    INDEX idx_socio (socio_id),
    INDEX idx_sucursal (sucursal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pagos (payments)
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    tipo_membresia_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'stripe', 'mercadopago', 'conekta') NOT NULL,
    referencia VARCHAR(100),
    estado ENUM('pendiente', 'completado', 'cancelado', 'reembolsado') DEFAULT 'pendiente',
    comprobante VARCHAR(255),
    usuario_registro INT,
    sucursal_id INT NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notas TEXT,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_membresia_id) REFERENCES tipos_membresia(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_fecha_pago (fecha_pago),
    INDEX idx_socio (socio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: gastos (expenses)
CREATE TABLE gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto VARCHAR(200) NOT NULL,
    categoria ENUM('servicios', 'mantenimiento', 'personal', 'equipamiento', 'marketing', 'otros') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    sucursal_id INT NOT NULL,
    fecha_gasto DATE NOT NULL,
    comprobante VARCHAR(255),
    usuario_registro INT,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_registro) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    INDEX idx_fecha_gasto (fecha_gasto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: horarios_especiales (special schedules)
CREATE TABLE horarios_especiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('festivo', 'mantenimiento', 'cerrado', 'horario_especial') NOT NULL,
    hora_apertura TIME,
    hora_cierre TIME,
    sucursal_id INT,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: mensajes_whatsapp (WhatsApp messages log)
CREATE TABLE mensajes_whatsapp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telefono VARCHAR(20) NOT NULL,
    socio_id INT,
    tipo ENUM('entrante', 'saliente') NOT NULL,
    mensaje TEXT NOT NULL,
    comando VARCHAR(50),
    estado ENUM('enviado', 'entregado', 'leido', 'fallido') DEFAULT 'enviado',
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE SET NULL,
    INDEX idx_telefono (telefono),
    INDEX idx_fecha_hora (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: bitacora_eventos (event log)
CREATE TABLE bitacora_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('acceso', 'pago', 'modificacion', 'sistema', 'dispositivo', 'error', 'whatsapp') NOT NULL,
    descripcion TEXT NOT NULL,
    usuario_id INT,
    socio_id INT,
    sucursal_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_adicionales JSON,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_staff(id) ON DELETE SET NULL,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE SET NULL,
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_hora (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: configuracion (system configuration)
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto',
    grupo VARCHAR(50),
    descripcion TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration
INSERT INTO configuracion (clave, valor, tipo, grupo, descripcion) VALUES
('sitio_nombre', 'AccessGYM', 'texto', 'general', 'Nombre del sistema'),
('sitio_logo', '', 'texto', 'general', 'URL del logo'),
('sitio_timezone', 'America/Mexico_City', 'texto', 'general', 'Zona horaria'),
('smtp_host', '', 'texto', 'email', 'Servidor SMTP'),
('smtp_port', '587', 'numero', 'email', 'Puerto SMTP'),
('smtp_user', '', 'texto', 'email', 'Usuario SMTP'),
('smtp_pass', '', 'texto', 'email', 'Contraseña SMTP'),
('shelly_api_url', 'https://shelly-cloud-api.com/device/status', 'texto', 'shelly', 'URL API Shelly'),
('shelly_api_key', '', 'texto', 'shelly', 'API Key Shelly'),
('whatsapp_phone_id', '', 'texto', 'whatsapp', 'Phone Number ID'),
('whatsapp_token', '', 'texto', 'whatsapp', 'Access Token'),
('whatsapp_verify_token', '', 'texto', 'whatsapp', 'Verify Token'),
('stripe_public_key', '', 'texto', 'pagos', 'Stripe Public Key'),
('stripe_secret_key', '', 'texto', 'pagos', 'Stripe Secret Key'),
('mercadopago_public_key', '', 'texto', 'pagos', 'MercadoPago Public Key'),
('mercadopago_access_token', '', 'texto', 'pagos', 'MercadoPago Access Token'),
('session_timeout', '3600', 'numero', 'seguridad', 'Timeout de sesión (segundos)'),
('qr_api_url', 'https://api.qrserver.com/v1/create-qr-code/', 'texto', 'integracion', 'API de generación de QR');

-- Insert default branch
INSERT INTO sucursales (nombre, direccion, telefono, email) VALUES
('Sucursal Principal', 'Dirección de ejemplo', '1234567890', 'contacto@accessgym.com');

-- Insert default membership types
INSERT INTO tipos_membresia (nombre, descripcion, duracion_dias, precio, acceso_horario_inicio, acceso_horario_fin, color) VALUES
('Mensual', 'Acceso ilimitado durante 30 días', 30, 500.00, '06:00:00', '22:00:00', '#3B82F6'),
('Trimestral', 'Acceso ilimitado durante 90 días', 90, 1350.00, '06:00:00', '22:00:00', '#10B981'),
('Semestral', 'Acceso ilimitado durante 180 días', 180, 2400.00, '06:00:00', '22:00:00', '#F59E0B'),
('Anual', 'Acceso ilimitado durante 365 días', 365, 4500.00, '06:00:00', '22:00:00', '#8B5CF6');

-- Insert default superadmin user (password: admin123)
INSERT INTO usuarios_staff (nombre, email, password, rol, sucursal_id) VALUES
('Administrador', 'admin@accessgym.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5ztL.dYuxBvgu', 'superadmin', 1);
