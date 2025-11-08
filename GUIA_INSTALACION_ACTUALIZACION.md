# Guía de Instalación de Actualización - Noviembre 2024

## ⚠️ Antes de Empezar

**IMPORTANTE**: Realiza un backup completo de la base de datos antes de proceder.

```bash
mysqldump -u usuario -p nombre_base_datos > backup_$(date +%Y%m%d).sql
```

## 📋 Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Acceso al servidor web (Apache/Nginx)
- Acceso SSH o panel de control del hosting
- Permisos de escritura en el directorio `uploads/`

## 🚀 Pasos de Instalación

### 1. Descargar los Cambios

Si usas Git:
```bash
cd /ruta/a/AccesoGym
git pull origin copilot/fix-sidebar-menu-responsiveness
```

O descarga los archivos modificados manualmente del repositorio.

### 2. Ejecutar Script SQL

```bash
# Conectarse a MySQL
mysql -u tu_usuario -p

# Seleccionar la base de datos
use nombre_de_tu_base_datos;

# Ejecutar el script
source /ruta/a/AccesoGym/database/update_nov_2024.sql;

# O desde línea de comandos:
mysql -u tu_usuario -p nombre_base_datos < database/update_nov_2024.sql
```

**Verificar que se ejecutó correctamente:**
```sql
-- Verificar nueva columna en usuarios_staff
DESCRIBE usuarios_staff;

-- Verificar tabla configuracion
SHOW TABLES LIKE 'configuracion';

-- Verificar configuraciones insertadas
SELECT * FROM configuracion WHERE grupo = 'estilos';

-- Verificar dispositivo Shelly
SELECT * FROM dispositivos_shelly WHERE device_id = '8813BFD94E20';
```

### 3. Crear Directorios de Upload

```bash
cd /ruta/a/AccesoGym

# Crear directorio para fotos de staff
mkdir -p uploads/staff

# Crear directorio para logos
mkdir -p uploads/logos

# Establecer permisos correctos
chmod -R 0755 uploads/
chown -R www-data:www-data uploads/  # En Ubuntu/Debian
# O
chown -R apache:apache uploads/      # En CentOS/RHEL
# O usar el usuario de tu servidor web
```

### 4. Verificar Configuración de Shelly

El archivo `config/config.php` ya debe estar actualizado con:

```php
define('SHELLY_ENABLED', true);
define('SHELLY_SERVER_URL', 'https://shelly-208-eu.shelly.cloud');
define('SHELLY_AUTH_TOKEN', 'MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74');
```

Si no está, actualízalo manualmente.

### 5. Verificar Archivos Nuevos

Asegúrate de que estos archivos existen:
```bash
ls -la public/buscar_socios.php
ls -la public/custom_styles.php
ls -la database/update_nov_2024.sql
```

### 6. Verificar Permisos de PHP

Asegúrate de que PHP tiene las extensiones necesarias:
```bash
php -m | grep -E "pdo|mysqli|gd|curl|json|mbstring"
```

Todas estas extensiones deben estar instaladas.

## ✅ Verificación Post-Instalación

### 1. Verificar Base de Datos

```sql
-- Login a MySQL
mysql -u usuario -p

use nombre_base_datos;

-- Verificar columna foto
SHOW COLUMNS FROM usuarios_staff LIKE 'foto';

-- Verificar tabla configuracion
SELECT COUNT(*) FROM configuracion;

-- Verificar device Shelly
SELECT device_id, nombre FROM dispositivos_shelly WHERE device_id = '8813BFD94E20';
```

### 2. Probar en el Navegador

1. **Acceder al Sistema**
   - Abre: `https://tu-dominio.com/login.php`
   - Ingresa con credenciales de SuperAdmin

2. **Verificar Navbar**
   - Debe estar fijo en la parte superior
   - Debe verse correctamente en móvil y desktop
   - El buscador debe estar visible (desktop)

3. **Verificar Mobile Sidebar**
   - Abre en modo responsive o móvil
   - Click en menú hamburguesa
   - Debe mostrar TODOS los ítems del menú

4. **Probar Búsqueda**
   - Click en barra de búsqueda
   - Escribe nombre de un socio
   - Deben aparecer resultados

5. **Probar Foto de Perfil**
   - Ir a "Mi Perfil"
   - Subir una foto
   - Verificar que se guarda y muestra

6. **Verificar Estilos**
   - Ir a "Configuración" → "Estilos"
   - Cambiar color primario
   - Guardar y recargar
   - El color debe cambiar

7. **Verificar Acceso SuperAdmin**
   - Como SuperAdmin, visitar:
     - `/membresias.php`
     - `/modulo_financiero.php`
     - `/usuarios.php`
     - `/importar_datos.php`
     - `/auditoria.php`
   - No debe aparecer "Acceso no autorizado"

8. **Probar Dispositivo Shelly**
   - Ir a "Dispositivos"
   - Verificar que el dispositivo aparece
   - Intentar probar conexión
   - Intentar abrir puerta

## 🔧 Solución de Problemas

### Error: "No such table: configuracion"
```sql
-- Crear tabla manualmente
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    grupo VARCHAR(50) DEFAULT 'general',
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Error: "Column 'foto' not found"
```sql
-- Agregar columna manualmente
ALTER TABLE usuarios_staff ADD COLUMN foto VARCHAR(255) NULL AFTER telefono;
```

### Error al subir fotos
```bash
# Verificar permisos
ls -la uploads/

# Debe mostrar: drwxr-xr-x (0755)
# Si no, corregir:
chmod -R 0755 uploads/
chown -R www-data:www-data uploads/
```

### Búsqueda no funciona
```bash
# Verificar que el archivo existe
ls -la public/buscar_socios.php

# Verificar sintaxis PHP
php -l public/buscar_socios.php

# Revisar logs
tail -f logs/php_errors.log
```

### Estilos no se aplican
```bash
# Verificar custom_styles.php
php -l public/custom_styles.php

# Probar acceso directo
curl http://tu-dominio.com/custom_styles.php

# Limpiar caché del navegador
# Chrome: Ctrl+Shift+R
# Firefox: Ctrl+F5
```

### Shelly no conecta
1. Verificar credenciales en `config/config.php`
2. Verificar que `SHELLY_ENABLED = true`
3. Probar conexión al servidor:
   ```bash
   curl -I https://shelly-208-eu.shelly.cloud
   ```
4. Verificar Device ID en base de datos:
   ```sql
   SELECT * FROM dispositivos_shelly WHERE device_id = '8813BFD94E20';
   ```

## 📊 Verificación de Logs

```bash
# Ver últimos errores de PHP
tail -f logs/php_errors.log

# Ver últimos errores de Apache
tail -f /var/log/apache2/error.log

# Ver últimos accesos
tail -f /var/log/apache2/access.log
```

## 🔄 Rollback (Si es necesario)

Si algo sale mal y necesitas revertir:

### 1. Restaurar Base de Datos
```bash
mysql -u usuario -p nombre_base_datos < backup_YYYYMMDD.sql
```

### 2. Revertir Código
```bash
cd /ruta/a/AccesoGym
git reset --hard HEAD~2  # Revertir últimos 2 commits
```

### 3. Eliminar Archivos Nuevos
```bash
rm public/buscar_socios.php
rm public/custom_styles.php
```

## 📞 Soporte

Si encuentras problemas:

1. **Revisar Logs**: Primero revisa los logs de errores
2. **Verificar SQL**: Asegúrate de que el script SQL se ejecutó completamente
3. **Verificar Permisos**: Revisa permisos de archivos y directorios
4. **Limpiar Caché**: Limpia caché del navegador
5. **Probar en Incógnito**: Abre el sitio en modo incógnito

## ✨ Después de la Instalación

1. **Cambiar Contraseñas**: Si es primera instalación
2. **Configurar Estilos**: Personaliza colores y fuentes
3. **Agregar Fotos**: Pide a los usuarios que agreguen sus fotos
4. **Probar Shelly**: Verifica que el dispositivo funciona
5. **Capacitar Usuarios**: Muestra las nuevas funciones al equipo

## 📝 Checklist de Instalación

- [ ] Backup de base de datos realizado
- [ ] Script SQL ejecutado sin errores
- [ ] Directorios uploads/ creados con permisos correctos
- [ ] Archivos nuevos verificados
- [ ] Configuración de Shelly actualizada
- [ ] Navbar se ve correctamente
- [ ] Mobile sidebar muestra todos los ítems
- [ ] Búsqueda funciona
- [ ] Se pueden subir fotos de perfil
- [ ] Estilos personalizados funcionan
- [ ] SuperAdmin tiene acceso a todos los módulos
- [ ] Dispositivo Shelly conecta correctamente
- [ ] No hay errores en logs de PHP

---

**Fecha de Actualización**: Noviembre 2024
**Versión**: 2.0
**Tiempo Estimado de Instalación**: 30-45 minutos
