# Actualización del Sistema - Noviembre 2024

## Cambios Implementados

### 1. Navegación Mejorada

#### Menú de Cuenta en Parte Superior Derecha
- Se agregó un menú dropdown en la esquina superior derecha con:
  - Mi Perfil
  - Configuración (solo para Superadmin)
  - Sucursales (solo para Superadmin)
  - Cerrar Sesión

#### Sidebar Siempre Visible en Desktop
- El menú lateral ahora está siempre visible en dispositivos desktop (pantallas grandes)
- En dispositivos móviles, el sidebar se muestra como overlay con solo 4 ítems principales:
  - Dashboard
  - Socios
  - Accesos
  - Pagos

#### Diseño Responsivo
- Desktop: Sidebar fijo a la izquierda, contenido principal con margen izquierdo
- Mobile: Sidebar overlay que se abre con botón hamburguesa

### 2. Corrección de Error 404 en Registro de Pagos

Se creó el archivo `pago_detalle.php` que faltaba, el cual:
- Muestra la información completa del pago registrado
- Incluye datos del socio asociado
- Permite editar el pago
- Proporciona opciones para registrar nuevo pago para el mismo socio
- Tiene funcionalidad de impresión

### 3. Módulo Mejorado de Dispositivos Shelly

Se agregaron los siguientes campos al formulario de dispositivos:

#### Campos Nuevos:
1. **Token de Autenticación** - Token de Shelly Cloud API (requerido)
2. **Device ID** - ID único del dispositivo (requerido)
3. **Servidor Cloud** - Servidor de Shelly Cloud (default: shelly-208-eu.shelly.cloud)
4. **Área** - Zona o área del dispositivo (ej: Entrada Puerta 1)
5. **Canal de Entrada (Apertura)** - Canal para pulso de entrada (Canal 0 o 1)
6. **Canal de Salida (Cierre)** - Canal para activación al salir (Canal 0 o 1)
7. **Duración Pulso** - Duración del pulso en milisegundos (1000-10000 ms, default: 4000)

#### Checkboxes:
- **Dispositivo habilitado** - Activa/desactiva el dispositivo
- **Invertido (off → on)** - Invierte la lógica del dispositivo
- **Dispositivo simultáneo** - Permite operación simultánea

### 4. Actualización de Base de Datos

Se creó el script SQL `database/update_dispositivos_shelly.sql` que:
- Agrega las nuevas columnas a la tabla `dispositivos_shelly`
- Crea índices para optimizar búsquedas
- Incluye valores por defecto apropiados
- Preserva la funcionalidad existente

## Instrucciones de Actualización

### 1. Actualizar Base de Datos

Ejecutar el siguiente script SQL en la base de datos:

```bash
mysql -u root -p accessgym < database/update_dispositivos_shelly.sql
```

O desde MySQL:

```sql
source /path/to/database/update_dispositivos_shelly.sql;
```

### 2. Verificar Archivos Actualizados

Los siguientes archivos han sido modificados/creados:
- `app/views/partials/navbar.php` (modificado)
- `public/pago_detalle.php` (nuevo)
- `public/dispositivo_form.php` (modificado)
- `database/update_dispositivos_shelly.sql` (nuevo)

### 3. Probar Funcionalidad

1. **Navegación:**
   - Verificar que el dropdown de cuenta funcione en desktop
   - Verificar que el sidebar esté siempre visible en desktop
   - Verificar que en móvil solo aparezcan 4 ítems

2. **Pagos:**
   - Registrar un nuevo pago
   - Verificar que redirija correctamente a `pago_detalle.php`
   - Verificar que no aparezca el error 404

3. **Dispositivos Shelly:**
   - Crear/editar un dispositivo
   - Verificar que todos los nuevos campos se guarden correctamente
   - Verificar que los checkboxes funcionen

## Características Técnicas

### Compatibilidad
- PHP 7.4+
- MySQL 5.7+
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

### Responsividad
- Breakpoint desktop: `lg` (1024px+)
- Breakpoint mobile: < 1024px
- Utiliza Tailwind CSS para clases responsivas

### Seguridad
- Tokens CSRF en todos los formularios
- Validación de permisos de usuario
- Sanitización de entradas
- Prepared statements en consultas SQL

## Notas Adicionales

- Los valores por defecto aseguran compatibilidad con dispositivos existentes
- La actualización de la base de datos es segura y no afecta datos existentes
- El diseño es consistente con el resto del sistema
- Se mantiene la compatibilidad con funcionalidad anterior

## Soporte

Para cualquier problema o pregunta sobre esta actualización, referirse a:
- Documentación del sistema en `README.md`
- Schema de base de datos en `database/schema.sql`
- Modelos en `app/models/`
