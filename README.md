# 🏋️ AccessGYM - Sistema de Control de Gimnasio

Sistema completo de gestión de gimnasios con control de acceso IoT mediante dispositivos Shelly Cloud, gestión de membresías, pagos, reportes y comunicación automática vía WhatsApp Business.

## ✨ Características Principales

### 🚪 Control de Acceso IoT (Shelly Cloud)
- Integración con dispositivos Shelly para control de puertas magnéticas
- Activación remota de puertas con tiempo configurable
- Monitoreo en tiempo real del estado de dispositivos
- Registro completo de eventos de acceso
- Apertura manual desde panel de administrador
- Integración con ChatBot de WhatsApp

### 👥 Gestión de Socios y Membresías
- CRUD completo de socios con fotografía
- Múltiples tipos de membresía configurables
- Validación automática de vigencia
- Códigos QR únicos por socio
- Control de horarios por tipo de membresía
- Estados: Activo, Inactivo, Suspendido, Vencido

### 📊 Reportes y Bitácora
- Dashboard con estadísticas en tiempo real
- Reportes de accesos por fecha, usuario, dispositivo
- Gráficas con Chart.js (accesos, ingresos)
- Bitácora completa de eventos del sistema

### 💬 ChatBot de WhatsApp
- Integración con WhatsApp Business API (Meta Cloud)
- Comandos para usuarios: "Abrir puerta", "Mi membresía", "Renovar"
- Registro de interacciones

### 💰 Gestión Financiera
- Registro de pagos de membresías
- Integración con pasarelas de pago (Stripe, MercadoPago, Conekta)
- Control de gastos operativos
- Reportes financieros

### 👔 Administración Multinivel
- **Superadmin**: Control total del sistema
- **Admin**: Gestión de sucursal
- **Recepcionista**: Altas, renovaciones y consultas

## 🛠️ Tecnologías

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL 5.7+
- **Frontend**: Tailwind CSS, Alpine.js, Chart.js, Font Awesome
- **Arquitectura**: MVC (Model-View-Controller)
- **Seguridad**: Sesiones, bcrypt, CSRF, validación de entradas
- **APIs**: Shelly Cloud, WhatsApp Business (Meta), Stripe/MercadoPago/Conekta

## 📦 Requisitos del Servidor

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache con mod_rewrite habilitado
- Extensiones PHP requeridas:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - session
  - gd (para procesamiento de imágenes)
  - curl (para APIs)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/danjohn007/AccesoGym.git
cd AccesoGym
```

### 2. Configurar la base de datos

```bash
# Crear la base de datos
mysql -u root -p < database/schema.sql
```

O importar manualmente el archivo `database/schema.sql` desde phpMyAdmin.

### 3. Configurar la aplicación

```bash
# Copiar archivo de configuración
cp config/config.example.php config/config.php

# Editar config/config.php con tus credenciales de base de datos
nano config/config.php
```

### 4. Configurar permisos

```bash
chmod -R 755 uploads/
chmod -R 755 logs/
```

### 5. Configurar Apache

Asegúrate de que el directorio apunte a la carpeta `public/`:

```apache
<VirtualHost *:80>
    ServerName accessgym.local
    DocumentRoot /path/to/AccesoGym/public
    
    <Directory /path/to/AccesoGym/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Acceder al sistema

Abrir en el navegador: `http://localhost/` o `http://accessgym.local/`

**Credenciales por defecto:**
- Email: `admin@accessgym.com`
- Contraseña: `admin123`

## ⚙️ Configuración

### 1. Configuración General del Sistema

Acceder a **Configuración** (solo Superadmin) para configurar:
- Nombre del sitio y logo
- Zona horaria
- Datos de contacto

### 2. Integración Shelly Cloud

1. Obtener API Key desde la consola de Shelly Cloud
2. Configurar en `config/config.php`:
   - SHELLY_ENABLED = true
   - SHELLY_API_URL
   - SHELLY_API_KEY
3. Registrar dispositivos con sus Device IDs

### 3. Integración WhatsApp Business

1. Crear cuenta en Meta for Developers
2. Configurar WhatsApp Business API
3. Configurar en `config/config.php`:
   - WHATSAPP_ENABLED = true
   - WHATSAPP_PHONE_ID
   - WHATSAPP_TOKEN
   - WHATSAPP_VERIFY_TOKEN

### 4. Pasarelas de Pago

Configurar en `config/config.php`:

```php
// Stripe
define('STRIPE_ENABLED', true);
define('STRIPE_PUBLIC_KEY', 'pk_live_...');
define('STRIPE_SECRET_KEY', 'sk_live_...');

// MercadoPago
define('MERCADOPAGO_ENABLED', true);
define('MERCADOPAGO_PUBLIC_KEY', 'APP_USR...');
define('MERCADOPAGO_ACCESS_TOKEN', 'APP_USR...');
```

## 👥 Roles y Permisos

### Superadmin
- Acceso total al sistema
- Gestión de sucursales
- Gestión de usuarios staff
- Configuración global

### Admin
- Gestión de su sucursal
- Socios y membresías
- Dispositivos de su sucursal
- Reportes de su sucursal

### Recepcionista
- Consulta de socios
- Altas y renovaciones
- Registro de pagos
- Acceso manual a puertas

## 📱 Uso del ChatBot de WhatsApp

### Comandos para Socios

- **"Hola"** - Mensaje de bienvenida
- **"Abrir puerta"** - Solicita acceso (valida membresía y horario)
- **"Mi membresía"** - Consulta estado de membresía
- **"Renovar"** - Recibe enlace de pago
- **"Ayuda"** - Información de contacto y horarios

## 🔒 Seguridad

- Passwords hasheados con bcrypt (cost 12)
- Protección contra SQL Injection (PDO prepared statements)
- Protección CSRF en formularios
- Validación y sanitización de entradas
- Sesiones seguras con timeout configurable
- Restricción de acceso por roles
- Logs de actividad completos
- Protección de archivos sensibles vía .htaccess

## 📂 Estructura del Proyecto

```
AccesoGym/
├── app/
│   ├── controllers/      # Controladores (futuro)
│   ├── models/          # Modelos de datos
│   ├── views/           # Vistas y plantillas
│   ├── helpers/         # Funciones auxiliares
│   └── services/        # Servicios (Shelly, WhatsApp)
├── assets/
│   ├── css/            # Estilos personalizados
│   ├── js/             # JavaScript personalizado
│   └── images/         # Imágenes del sistema
├── config/             # Configuración
├── database/           # Scripts SQL
├── logs/               # Logs del sistema
├── public/             # Archivos públicos (punto de entrada)
├── uploads/            # Archivos subidos (fotos, documentos)
└── .htaccess          # Configuración Apache
```

## 📝 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o pull request para sugerencias y mejoras.

## 📧 Soporte

Para soporte técnico o consultas, contacta a: admin@accessgym.com

---

**AccessGYM** - Sistema profesional de control de gimnasios 🏋️💪
