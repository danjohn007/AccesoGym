# 📘 Guía de Instalación - AccessGYM

Esta guía proporciona instrucciones paso a paso para instalar y configurar AccessGYM en tu servidor.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener:

- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Extensiones PHP** requeridas:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - session
  - gd
  - curl

## 🚀 Instalación Paso a Paso

### 1. Clonar el Repositorio

```bash
git clone https://github.com/danjohn007/AccesoGym.git
cd AccesoGym
```

### 2. Configurar Permisos

```bash
chmod -R 755 uploads/
chmod -R 755 logs/
chmod 644 config/config.php
```

### 3. Crear la Base de Datos

**Opción A: Usando MySQL CLI**
```bash
mysql -u root -p < database/schema.sql
```

**Opción B: Usando phpMyAdmin**
1. Accede a phpMyAdmin
2. Crea una nueva base de datos llamada `accessgym`
3. Importa el archivo `database/schema.sql`

### 4. Configurar la Aplicación

```bash
# Copiar archivo de configuración de ejemplo
cp config/config.example.php config/config.php

# Editar configuración
nano config/config.php
```

Actualiza los valores de conexión a la base de datos:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'accessgym');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 5. Configurar Apache

Crea un Virtual Host para AccessGYM:

```apache
<VirtualHost *:80>
    ServerName accessgym.local
    DocumentRoot /var/www/html/AccesoGym/public
    
    <Directory /var/www/html/AccesoGym/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/accessgym_error.log
    CustomLog ${APACHE_LOG_DIR}/accessgym_access.log combined
</VirtualHost>
```

Habilita mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 6. Acceder al Sistema

Abre tu navegador y navega a:
```
http://accessgym.local/
```

**Credenciales por defecto:**
- Email: `admin@accessgym.com`
- Contraseña: `admin123`

⚠️ **IMPORTANTE**: Cambia la contraseña por defecto inmediatamente después del primer inicio de sesión.

## ⚙️ Configuración Avanzada

### Configurar Shelly Cloud API

1. Obtén tu API Key desde [Shelly Cloud Console](https://control.shelly.cloud/)
2. Edita `config/config.php`:

```php
define('SHELLY_ENABLED', true);
define('SHELLY_API_KEY', 'tu_api_key_aqui');
```

3. Registra tus dispositivos desde el panel de administración

### Configurar WhatsApp Business API

1. Crea una cuenta en [Meta for Developers](https://developers.facebook.com/)
2. Configura WhatsApp Business API
3. Obtén:
   - Phone Number ID
   - Access Token
   - Verify Token (crea uno seguro)

4. Edita `config/config.php`:

```php
define('WHATSAPP_ENABLED', true);
define('WHATSAPP_PHONE_ID', 'tu_phone_number_id');
define('WHATSAPP_TOKEN', 'tu_access_token');
define('WHATSAPP_VERIFY_TOKEN', 'tu_verify_token');
```

5. Configura el webhook en Meta:
   - URL: `https://tudominio.com/webhook_whatsapp.php`
   - Verify Token: (el mismo que definiste arriba)
   - Suscripciones: `messages`

### Configurar Email (SMTP)

Para enviar notificaciones por email:

```php
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-email@gmail.com');
define('SMTP_PASS', 'tu-app-password');
```

Para Gmail, necesitas generar una [App Password](https://support.google.com/accounts/answer/185833).

### Configurar Pasarelas de Pago

**Stripe:**
```php
define('STRIPE_ENABLED', true);
define('STRIPE_PUBLIC_KEY', 'pk_live_...');
define('STRIPE_SECRET_KEY', 'sk_live_...');
```

**MercadoPago:**
```php
define('MERCADOPAGO_ENABLED', true);
define('MERCADOPAGO_PUBLIC_KEY', 'APP_USR...');
define('MERCADOPAGO_ACCESS_TOKEN', 'APP_USR...');
```

## 🔐 Seguridad

### Recomendaciones de Seguridad

1. **Cambiar contraseñas por defecto**
2. **Configurar HTTPS**: Usa Let's Encrypt para SSL gratuito
3. **Actualizar permisos de archivos**:
   ```bash
   chmod 600 config/config.php
   chmod 700 uploads/
   chmod 700 logs/
   ```
4. **Configurar firewall**: Permite solo puertos necesarios (80, 443, 22)
5. **Backups regulares**: Configura backups automáticos de la base de datos

### Configurar SSL con Let's Encrypt

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tudominio.com
```

## 🧪 Verificar la Instalación

1. **Inicio de sesión**: Verifica que puedes iniciar sesión
2. **Dashboard**: Comprueba que se muestran las estadísticas
3. **Crear socio**: Intenta crear un nuevo socio
4. **QR Code**: Verifica que se genera el código QR
5. **Dispositivos**: Si configuraste Shelly, prueba un dispositivo

## 🐛 Resolución de Problemas

### Error de conexión a la base de datos

**Problema**: "Error de conexión a la base de datos"

**Solución**:
- Verifica las credenciales en `config/config.php`
- Asegúrate de que MySQL esté corriendo: `sudo systemctl status mysql`
- Verifica que el usuario tenga permisos: `GRANT ALL ON accessgym.* TO 'usuario'@'localhost';`

### Página en blanco o error 500

**Problema**: Pantalla blanca o error 500

**Solución**:
- Revisa los logs: `tail -f /var/log/apache2/accessgym_error.log`
- Verifica permisos de archivos y directorios
- Asegúrate de que todas las extensiones PHP estén instaladas

### Las imágenes no se cargan

**Problema**: Las fotos de los socios no se muestran

**Solución**:
```bash
chmod -R 755 uploads/photos/
chown -R www-data:www-data uploads/
```

### Mod_rewrite no funciona

**Problema**: Error 404 en todas las páginas

**Solución**:
```bash
sudo a2enmod rewrite
sudo nano /etc/apache2/sites-available/000-default.conf
# Cambiar AllowOverride None a AllowOverride All
sudo systemctl restart apache2
```

## 📞 Soporte

Si encuentras problemas durante la instalación:

1. Revisa los [Issues](https://github.com/danjohn007/AccesoGym/issues) en GitHub
2. Abre un nuevo issue si tu problema no está resuelto
3. Contacta: admin@accessgym.com

## 📚 Recursos Adicionales

- [Documentación de PHP](https://www.php.net/docs.php)
- [Documentación de MySQL](https://dev.mysql.com/doc/)
- [Shelly Cloud API](https://shelly-api-docs.shelly.cloud/)
- [WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)

---

¡Felicidades! Tu instalación de AccessGYM está completa. 🎉
