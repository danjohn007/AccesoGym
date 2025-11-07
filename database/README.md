# Database Scripts - AccessGYM

Este directorio contiene los scripts SQL para la instalación, actualización y datos de ejemplo del sistema AccessGYM.

## Archivos

### 1. schema.sql
**Propósito:** Instalación inicial de la base de datos

**Uso:**
```bash
mysql -u root -p < database/schema.sql
```

**Contenido:**
- Creación de todas las tablas
- Datos iniciales básicos (tipos de membresía por defecto, usuario admin, sucursal principal)
- Estructura completa del sistema

**Cuándo usar:** Primera instalación del sistema

---

### 2. update.sql
**Propósito:** Actualización de base de datos existente con nuevas funcionalidades

**Uso:**
```bash
mysql -u root -p accessgym < database/update.sql
```

**Contenido:**
- Nuevas configuraciones del sistema
- Índices para mejorar rendimiento
- Vistas para reportes financieros
- Funciones almacenadas útiles
- Triggers para automatización
- Tabla de configuración de notificaciones

**Características agregadas:**
- Configuraciones de sitio (nombre, logo, eslogan)
- Configuraciones de email
- Configuraciones de contacto y horarios
- Configuraciones de estilos (colores)
- Integración con PayPal
- API de QR personalizable
- Configuraciones de sistema

**Cuándo usar:** Actualizar una instalación existente

**⚠️ IMPORTANTE:** Este script es seguro para ejecutar en bases de datos existentes. Usa `INSERT IGNORE` y `CREATE OR REPLACE` para evitar errores.

---

### 3. sample_data.sql
**Propósito:** Datos de ejemplo para pruebas y demostración

**Uso:**
```bash
mysql -u root -p accessgym < database/sample_data.sql
```

**Contenido:**
- 3 sucursales adicionales
- 6 usuarios del sistema (admins y recepcionistas)
- 10 tipos de membresía (incluyendo diaria, semanal, matutina, etc.)
- **100+ socios** con diferentes estados
- 6 dispositivos Shelly
- **200+ pagos** con diferentes métodos
- **500+ registros de acceso**
- 100 gastos de ejemplo
- 300 eventos de bitácora
- 50 mensajes de WhatsApp

**⚠️ ADVERTENCIA:** NO ejecutar en producción con datos reales. Este script está diseñado para entornos de desarrollo y pruebas.

**Cuándo usar:** Desarrollo, pruebas, demostraciones

---

## Orden de Ejecución Recomendado

### Instalación Nueva
```bash
# 1. Crear base de datos e importar esquema
mysql -u root -p < database/schema.sql

# 2. Aplicar actualizaciones
mysql -u root -p accessgym < database/update.sql

# 3. (Opcional) Cargar datos de ejemplo
mysql -u root -p accessgym < database/sample_data.sql
```

### Actualización de Sistema Existente
```bash
# Solo aplicar actualizaciones
mysql -u root -p accessgym < database/update.sql
```

### Entorno de Desarrollo/Pruebas
```bash
# Instalación completa con datos de ejemplo
mysql -u root -p < database/schema.sql
mysql -u root -p accessgym < database/update.sql
mysql -u root -p accessgym < database/sample_data.sql
```

---

## Notas Importantes

### Credenciales por Defecto
Después de ejecutar `schema.sql`, el sistema tiene un usuario superadmin:
- **Email:** admin@accessgym.com
- **Contraseña:** admin123

**🔒 SEGURIDAD:** Cambiar la contraseña inmediatamente después del primer inicio de sesión.

### Respaldos
Siempre crear un respaldo antes de ejecutar scripts de actualización:
```bash
mysqldump -u root -p accessgym > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar Respaldo
```bash
mysql -u root -p accessgym < backup_20241107_120000.sql
```

### Verificar Instalación
Después de ejecutar los scripts, puedes verificar la instalación:
```bash
mysql -u root -p accessgym -e "SELECT 
    (SELECT COUNT(*) FROM socios) as total_socios,
    (SELECT COUNT(*) FROM usuarios_staff) as total_usuarios,
    (SELECT COUNT(*) FROM configuracion) as total_configuraciones;"
```

---

## Funcionalidades de Base de Datos

### Vistas Creadas
- `vista_resumen_financiero_mensual` - Resumen de ingresos mensuales por sucursal
- `vista_socios_activos_membresia` - Socios activos agrupados por tipo de membresía

### Funciones Almacenadas
- `dias_hasta_vencimiento(fecha_venc)` - Calcula días hasta que vence una membresía
- `obtener_estado_socio(socio_id)` - Obtiene el estado real de un socio

### Triggers
- `actualizar_estado_socio_before_insert` - Actualiza automáticamente el estado al insertar
- `actualizar_estado_socio_before_update` - Actualiza automáticamente el estado al modificar

---

## Troubleshooting

### Error: "Table already exists"
**Solución:** Este es normal si ejecutas `schema.sql` en una base de datos existente. Usa `update.sql` en su lugar.

### Error: "Cannot add foreign key constraint"
**Solución:** Verifica que las tablas referenciadas existan y que los datos sean consistentes.

### Error: "Duplicate entry"
**Solución:** Si ejecutas `sample_data.sql` múltiples veces, puede haber duplicados. Limpia la base de datos primero o usa las secciones comentadas del script para truncar tablas.

### Error de codificación de caracteres
**Solución:** Asegúrate de usar UTF-8:
```bash
mysql -u root -p --default-character-set=utf8mb4 accessgym < database/script.sql
```

---

## Mantenimiento

### Limpieza de Datos de Prueba
```sql
-- Eliminar datos de ejemplo (CUIDADO: esto borrará datos)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE bitacora_eventos;
TRUNCATE TABLE mensajes_whatsapp;
TRUNCATE TABLE accesos;
TRUNCATE TABLE pagos;
TRUNCATE TABLE socios;
DELETE FROM usuarios_staff WHERE id > 1;
SET FOREIGN_KEY_CHECKS = 1;
```

### Optimización
```sql
-- Optimizar tablas periódicamente
OPTIMIZE TABLE socios, pagos, accesos, bitacora_eventos;

-- Analizar tablas para mejorar queries
ANALYZE TABLE socios, pagos, accesos;
```

---

## Contacto y Soporte

Para problemas o preguntas sobre los scripts de base de datos:
- GitHub Issues: https://github.com/danjohn007/AccesoGym/issues
- Email: admin@accessgym.com

---

**Última actualización:** 2024-11-07
**Versión de Base de Datos:** 1.1
