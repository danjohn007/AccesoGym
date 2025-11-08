# Actualización del Sistema - Noviembre 2024

## Resumen de Cambios

Esta actualización implementa mejoras significativas al sistema AccessGYM, incluyendo mejoras en la interfaz móvil, búsqueda global, personalización de estilos, corrección del dispositivo Shelly y permisos de SuperAdmin.

## 🔧 Cambios Implementados

### 1. Menú Sidebar Móvil Mejorado ✅
- **Problema**: El sidebar móvil solo mostraba 4 ítems del menú principal
- **Solución**: Ahora muestra todos los ítems del menú desktop más los 4 ítems de la parte superior derecha:
  - Dashboard
  - Socios
  - Accesos
  - Pagos
  - Dispositivos (Admin/SuperAdmin)
  - Reportes (Admin/SuperAdmin)
  - Membresías (Admin/SuperAdmin)
  - Módulo Financiero (Admin/SuperAdmin)
  - Usuarios (Admin/SuperAdmin)
  - Importar Datos (Admin/SuperAdmin)
  - Auditoría (Admin/SuperAdmin)
  - Mi Perfil
  - Configuración (SuperAdmin)
  - Sucursales (SuperAdmin)
  - Cerrar Sesión

### 2. Fotografía de Perfil para Usuarios ✅
- **Problema**: No se podía agregar foto de perfil para usuarios staff
- **Solución**: 
  - Agregada funcionalidad de carga de foto en `perfil.php`
  - Soporte para JPG, PNG y JPEG (máximo 5MB)
  - Nueva columna `foto` en tabla `usuarios_staff`
  - Almacenamiento en `/uploads/staff/`
  - Preview de foto en el perfil

### 3. Diseño Responsivo Mejorado ✅
- **Problema**: El diseño no era totalmente responsivo, el top bar no siempre visible
- **Solución**:
  - Navbar ahora es fijo (`position: fixed`) y siempre visible en la parte superior
  - Agregado `padding-top` al body para compensar el navbar fijo
  - Sidebar desktop ajustado para considerar el navbar fijo
  - Todo el contenido ahora tiene margen apropiado

### 4. Buscador Principal de Socios ✅
- **Problema**: No había búsqueda global en el sistema
- **Solución**:
  - Buscador global en el navbar (visible en desktop)
  - Búsqueda en tiempo real con debounce de 500ms
  - Indexa socios por:
    - Nombre
    - Apellido
    - Código de socio
    - Email
    - Teléfono
  - Resultados en dropdown con preview
  - Muestra estado del socio (activo, vencido, etc.)
  - Link directo al detalle del socio

### 5. Personalización de Estilos Mejorada ✅
- **Problema**: El sistema no respondía a la personalización de estilos
- **Solución**:
  - Configuración de colores funcional:
    - Color primario (botones, enlaces)
    - Color secundario
    - Color de acento
  - Selección de fuentes:
    - System (predeterminada)
    - Inter
    - Roboto
    - Open Sans
    - Poppins
  - Opciones de bordes redondeados:
    - Sin redondeo
    - Pequeño
    - Mediano
    - Grande
  - Archivo CSS dinámico (`custom_styles.php`) que aplica la configuración
  - Las configuraciones se guardan en la tabla `configuracion`

### 6. Corrección del Dispositivo Shelly ✅
- **Problema**: Error 'Error al abrir puerta: Error al abrir puerta'
- **Solución**:
  - Actualizado `ShellyService.php` con la API correcta de Shelly Cloud
  - Configuradas las credenciales correctas:
    ```
    Device ID: 8813BFD94E20
    Auth Token: MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74
    Server: https://shelly-208-eu.shelly.cloud
    ```
  - Actualizado el método `openDoor()` para usar los endpoints correctos
  - Actualizado el método `getDeviceStatus()` para el servidor correcto
  - Mensajes de error más descriptivos

### 7. Acceso SuperAdmin a Todos los Módulos ✅
- **Problema**: SuperAdmin veía 'Acceso no autorizado' en varios módulos
- **Solución**: Actualizado `Auth::requireRole()` en:
  - ✅ `membresias.php` - Ahora acepta `['superadmin', 'admin']`
  - ✅ `modulo_financiero.php` - Con filtro de sucursales ya implementado
  - ✅ `usuarios.php` - Ahora acepta `['superadmin', 'admin']`
  - ✅ `importar_datos.php` - Con filtro de sucursales ya implementado
  - ✅ `auditoria.php` - Con filtro de sucursales ya implementado

## 📁 Archivos Nuevos

1. **`public/buscar_socios.php`** - Endpoint para búsqueda global de socios
2. **`public/custom_styles.php`** - Genera CSS dinámico desde configuración
3. **`database/update_nov_2024.sql`** - Script SQL de actualización completo

## 📝 Archivos Modificados

1. **`app/views/partials/navbar.php`**
   - Sidebar móvil completo
   - Buscador global integrado
   - Navbar fijo
   - Link a custom_styles.php

2. **`public/perfil.php`**
   - Carga de foto de perfil
   - Preview de foto actual
   - Validación de archivos

3. **`app/services/ShellyService.php`**
   - Configuración correcta del servidor Shelly
   - Uso de SHELLY_SERVER_URL y SHELLY_AUTH_TOKEN
   - Endpoints actualizados

4. **`app/models/Socio.php`**
   - Método `search()` para búsqueda global

5. **`public/configuracion.php`**
   - Opciones de fuente agregadas
   - Opciones de border radius agregadas
   - Configuraciones guardadas en DB

6. **`config/config.php`**
   - SHELLY_ENABLED = true
   - SHELLY_SERVER_URL configurado
   - SHELLY_AUTH_TOKEN configurado

7. **Módulos con acceso SuperAdmin**:
   - `public/membresias.php`
   - `public/modulo_financiero.php`
   - `public/usuarios.php`
   - `public/importar_datos.php`
   - `public/auditoria.php`

## 🗄️ Cambios en Base de Datos

### Ejecutar el Script SQL

```bash
mysql -u usuario -p nombre_bd < database/update_nov_2024.sql
```

### Cambios Incluidos:

1. **Nueva columna en `usuarios_staff`**:
   ```sql
   ALTER TABLE usuarios_staff ADD COLUMN foto VARCHAR(255) NULL AFTER telefono;
   ```

2. **Tabla `configuracion`** (creada si no existe):
   - Almacena configuraciones del sistema
   - Soporte para estilos, integraciones, etc.

3. **Configuraciones por defecto**:
   - Colores del sistema
   - Fuentes
   - Border radius
   - Configuración Shelly

4. **Índices para mejor rendimiento**:
   - `socios`: código, email, teléfono, nombre/apellido, estado
   - `bitacora_eventos`: fecha_hora, tipo
   - `accesos`: fecha_hora, resultado

5. **Tabla `uploads_files`**:
   - Tracking de archivos subidos
   - Gestión de fotos y documentos

6. **Dispositivo Shelly actualizado**:
   - Device ID correcto
   - Credenciales actualizadas

## 📂 Estructura de Directorios

Asegúrate de que existan estos directorios con permisos 0755:

```bash
mkdir -p uploads/staff
mkdir -p uploads/logos
chmod -R 0755 uploads/
```

## ⚙️ Configuración Post-Instalación

### 1. Configuración de Shelly (Ya hecha en config.php)
```php
define('SHELLY_ENABLED', true);
define('SHELLY_SERVER_URL', 'https://shelly-208-eu.shelly.cloud');
define('SHELLY_AUTH_TOKEN', 'MzgwNjRhdWlk0574CFA7E6D9F34D8F306EB51648C8DA5D79A03333414C2FBF51CFA88A780F9867246CE317003A74');
```

### 2. Verificar Dispositivo Shelly
1. Ir a **Dispositivos**
2. Verificar que el Device ID sea: `8813BFD94E20`
3. Probar conexión del dispositivo
4. Probar apertura de puerta

### 3. Personalizar Estilos
1. Ir a **Configuración** (como SuperAdmin)
2. Seleccionar pestaña **Estilos**
3. Ajustar colores y fuentes según preferencia
4. Guardar cambios
5. Recargar página para ver cambios

### 4. Probar Búsqueda Global
1. Hacer clic en la barra de búsqueda en el navbar
2. Escribir nombre, código, email o teléfono de un socio
3. Verificar que aparezcan resultados
4. Hacer clic en un resultado para ir al detalle

### 5. Agregar Foto de Perfil
1. Ir a **Mi Perfil**
2. Seleccionar foto (JPG/PNG, max 5MB)
3. Guardar cambios
4. Verificar que la foto aparezca en el perfil y navbar

## 🧪 Testing

### Pruebas Manuales Recomendadas:

1. **Mobile Sidebar**:
   - Abrir en dispositivo móvil o modo responsive
   - Verificar que el menú hamburguesa muestre todos los ítems
   - Verificar navegación funciona correctamente

2. **Búsqueda Global**:
   - Buscar socio por nombre
   - Buscar por código
   - Buscar por email
   - Buscar por teléfono
   - Verificar que los resultados sean correctos

3. **Foto de Perfil**:
   - Subir foto válida (JPG, PNG)
   - Intentar subir archivo muy grande (debe fallar)
   - Intentar subir tipo incorrecto (debe fallar)
   - Verificar que la foto aparezca correctamente

4. **Personalización de Estilos**:
   - Cambiar color primario
   - Cambiar fuente
   - Cambiar border radius
   - Guardar y recargar
   - Verificar que los cambios se apliquen

5. **Dispositivo Shelly**:
   - Ir a Dispositivos
   - Probar conexión del dispositivo
   - Intentar abrir puerta
   - Verificar que no hay error

6. **Acceso SuperAdmin**:
   - Iniciar sesión como SuperAdmin
   - Verificar acceso a Membresías
   - Verificar acceso a Módulo Financiero
   - Verificar acceso a Usuarios
   - Verificar acceso a Importar Datos
   - Verificar acceso a Auditoría
   - Verificar filtros de sucursal funcionan

## 🔒 Seguridad

- Las credenciales de Shelly están en `config.php` (excluido de git)
- Las fotos se validan por tipo y tamaño
- Los tokens CSRF siguen activos en todos los formularios
- Las búsquedas usan prepared statements (protección SQL injection)
- Solo SuperAdmin puede cambiar configuración de estilos

## 📊 Rendimiento

- Agregados índices de base de datos para búsquedas más rápidas
- Búsqueda con debounce para reducir llamadas al servidor
- Límite de 20 resultados en búsqueda global
- CSS dinámico cacheado por el navegador

## 🐛 Troubleshooting

### Error al subir foto
- Verificar que el directorio `uploads/staff/` existe
- Verificar permisos 0755 en `uploads/`
- Verificar que PHP tiene extensión GD habilitada

### Búsqueda no funciona
- Verificar que `buscar_socios.php` es accesible
- Verificar logs de errores de PHP
- Verificar que Alpine.js se carga correctamente

### Estilos no se aplican
- Verificar que `custom_styles.php` es accesible
- Verificar que la tabla `configuracion` existe
- Ejecutar el script SQL de actualización
- Limpiar caché del navegador

### Shelly no abre puerta
- Verificar credenciales en `config.php`
- Verificar que SHELLY_ENABLED = true
- Verificar conectividad con shelly-208-eu.shelly.cloud
- Verificar Device ID en la base de datos

## 📞 Soporte

Para problemas o dudas:
- Revisar logs en `logs/php_errors.log`
- Verificar configuración en `config/config.php`
- Consultar documentación en README.md

---

**Versión**: November 2024 Update
**Fecha**: 2024-11-08
**Autor**: Sistema AccessGYM
