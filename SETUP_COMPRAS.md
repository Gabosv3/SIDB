# 🚀 GUÍA DE SETUP DEL MÓDULO DE COMPRAS

## ¡Bienvenido! 👋

Esta guía te ayudará a poner en marcha el módulo de compras y proveedores paso a paso.

---

## ✅ Prerequisitos

- Laravel 12+
- Filament 4+
- Spatie Permission
- PHP 8.2+

---

## 📋 INSTALACIÓN PASO A PASO

### PASO 1: Validar Instalación

Primero, verifica que todo está en su lugar:

```bash
php validate-compras-module.php
```

Deberías ver algo como:
```
✓ Modelo Proveedor
✓ Modelo Compra
✓ Modelo DetalleCompra
✓ Modelo PagoCompra
... (más verificaciones)

✅ VALIDACIÓN EXITOSA - El módulo está listo para usar
```

### PASO 2: Ejecutar Migraciones

Crea las tablas en la base de datos:

```bash
php artisan migrate
```

**Tablas que se crearán:**
- `proveedores` - Base de datos de proveedores
- `compras` - Registro de compras
- `detalle_compras` - Artículos de cada compra
- `producto_proveedor` - Relación de productos con proveedores
- `pagos_compra` - Pagos realizados
- Actualización de `productos` con nuevos campos

### PASO 3: Generar Permisos (Filament Shield)

Si usas Filament Shield, genera los permisos:

```bash
php artisan shield:generate --all
```

Esto creará permisos para:
- Ver proveedores
- Crear proveedores
- Editar proveedores
- Eliminar proveedores
- Lo mismo para compras

### PASO 4: Llenar Datos de Prueba (Opcional)

Para desarrollo y testing, ejecuta los seeders:

```bash
php artisan db:seed --class=ProveedorSeeder
```

**Esto creará:**
- 15 proveedores aleatorios
- 3 proveedores con datos específicos
- Asociaciones entre productos y proveedores

### PASO 5: Limpiar Cache

Asegúrate de que Filament carga los nuevos recursos:

```bash
php artisan cache:clear
php artisan config:clear
```

### PASO 6: Reiniciar Servidor

Depending on how you're running Laravel:

```bash
# Si usas artisan serve:
php artisan serve

# Si usas otro servidor, reinicia normalmente
```

---

## 🎯 VERIFICACIÓN

Abre tu navegador y ve a tu panel Filament (`/admin`)

### Deberías ver:

1. **Menú lateral** con nueva sección "Compras":
   - 📋 Proveedores
   - 🛒 Compras

2. **En Inventario** deberías ver actualizado:
   - 📦 Productos (con pestaña de proveedores)

3. **Dashboard** con widgets:
   - Deuda Total
   - Compras Este Mes
   - Proveedores Activos
   - Compras Últimos 7 Días

---

## 🧪 PRUEBA RÁPIDA

### 1. Crear un Proveedor

1. Ve a **Compras** → **Proveedores**
2. Click en **"Nuevo Proveedor"**
3. Completa:
   - Nombre: "Prueba SA"
   - Código: "PROV-TEST-001"
   - Contacto: "Juan Pérez"
   - Email: "juan@prueba.com"
   - Teléfono: "555-0123"
   - Dirección: "Calle Principal 123"
   - Ciudad: "Guatemala"
   - Departamento: "Guatemala"
   - País: "Guatemala"
4. Click **Guardar**

### 2. Crear una Compra

1. Ve a **Compras** → **Compras**
2. Click en **"Nueva Compra"**
3. En la pestaña "Información de Compra":
   - Proveedor: (selecciona el que creaste)
   - Fecha: (hoy)
   - Usuario: (tu usuario)
4. En la pestaña "Artículos":
   - Click en **"Añadir Artículo"**
   - Producto: (selecciona uno que exista)
   - Cantidad: 10
   - Precio Unitario: 100.00
   - Descuento Unitario: 5.00
5. En la pestaña "Totales y Pago":
   - Forma de Pago: "Crédito"
   - Condición de Pago: "Crédito"
   - Días de Crédito: 30
   - Impuesto: 16
6. Click **Guardar**

### 3. Ver Dashboard

1. Ve al dashboard principal
2. Deberías ver:
   - Deuda Total actualizada
   - Compras de Este Mes
   - Widget con compra reciente

---

## 🔧 TROUBLESHOOTING

### Problema: "No veo los nuevos menús"

**Solución:**
```bash
php artisan cache:clear
# Recarga la página browser (Ctrl+F5)
```

### Problema: "Error en migraciones"

**Solución:**
```bash
# Verificar que la DB está accesible
php artisan migrate:status

# Si falla, revisar .env
cat .env | grep DB_
```

### Problema: "Los seeding no funcionan"

**Solución:**
```bash
# Asegúrate de que los modelos tienen factories
php artisan make:factory ProveedorFactory --model=Proveedor

# Luego intenta de nuevo
php artisan db:seed --class=ProveedorSeeder
```

### Problema: "Total de compra no se calcula"

**Solución:**
```bash
# Verifica que los Observers están registrados
# Abre: app/Providers/AppServiceProvider.php
# Debería tener: Compra::observe(CompraObserver::class);
```

### Problema: "Stock no se actualiza"

**Solución:**
```bash
# Cambia estado de compra a "recibida"
# Debería actualizar automáticamente el stock
# Si no funciona, revisar CompraObserver
```

---

## 📊 ESTRUCTURA DE DATOS

### Tabla: `proveedores`
```
id (PK)
nombre
codigo
contacto_principal
email
telefono
telefono_adicional
direccion
ciudad
departamento
pais
codigo_postal
rfc_o_nit
condiciones_pago
dias_credito
descuento_comercial
activo
notas
timestamps
```

### Tabla: `compras`
```
id (PK)
numero_compra (UNIQUE)
proveedor_id (FK)
fecha_compra
fecha_entrega_estimada
fecha_entrega_real
usuario_id (FK)
subtotal
impuesto_porcentaje
impuesto_monto
descuento_monto
total
saldo_pendiente
forma_pago
condicion_pago
dias_credito
fecha_vencimiento
estado
observaciones
timestamps
```

### Tabla: `detalle_compras`
```
id (PK)
compra_id (FK)
producto_id (FK)
cantidad
precio_unitario
descuento_unitario
subtotal
numero_lote
fecha_vencimiento
observaciones
timestamps
```

### Tabla: `pagos_compra`
```
id (PK)
compra_id (FK)
fecha_pago
monto
forma_pago
referencia_pago
usuario_id (FK)
observaciones
timestamps
```

### Tabla: `producto_proveedor`
```
id (PK)
producto_id (FK)
proveedor_id (FK)
codigo_proveedor
precio_unitario
cantidad_minima
tiempo_entrega_dias
timestamps
```

---

## 📱 CARACTERÍSTICAS PRINCIPALES

### ✨ Automatizaciones Incluidas

- **Generación Automática de Números**: `COM-YYYYMMDD-XXXXXX`
- **Cálculos Automáticos**: Subtotales, impuestos, descuentos
- **Actualización de Stock**: Cuando compra pasa a "recibida"
- **Gestión de Saldo**: Se reduce con cada pago
- **Cambio de Estado**: Automático cuando se completa pago
- **Movimientos de Stock**: Se registran automáticamente
- **Validaciones**: Cantidad, montos, relaciones

### 🎯 Control de Acceso

Permisos sincronizados con Filament Shield:
- view / view_any
- create / update / delete
- delete_any / restore / force_delete

### 📈 Reportes Disponibles

Accesibles desde `CompraService`:
- Compras por período
- Resumen por proveedor
- Deuda total pendiente
- Compras vencidas
- Costo promedio de productos
- Reporte general con estadísticas

---

## 💡 TIPS Y TRUCOS

### Crear Compra por API/Command

```php
use App\Services\CompraService;

$service = app(CompraService::class);
$compra = $service->crearCompra(
    data: [...],
    detalles: [...]
);
```

### Reportes Programados

```php
// En schedule (app/Console/Kernel.php)
$schedule->call(function () {
    $service = app(CompraService::class);
    $vencidas = $service->obtenerComprasVencidas();
    // Enviar email o notificación
})->daily();
```

### Exportar Compras a Excel

```php
// Usando Filament + Spatie Excel
use App\Exports\ComprasExport;

$export = new ComprasExport();
return Excel::download($export, 'compras.xlsx');
```

---

## 🆘 SOPORTE

### Documentación Técnica
Ver: `COMPRAS_MODULO.md`

### Errores Comunes
1. **"Class not found"** → ejecuta `composer dump-autoload`
2. **"SQLSTATE"** → verifica conexión DB en `.env`
3. **"Permisos denegados"** → ejecuta `php artisan shield:generate`

### Logs
Revisar problemas en:
```
storage/logs/laravel.log
```

---

## ✅ CHECKLIST FINAL

- [ ] ✓ Validación pasada
- [ ] ✓ Migraciones ejecutadas
- [ ] ✓ Permisos generados (Filament Shield)
- [ ] ✓ Seeders ejecutados (opcional)
- [ ] ✓ Cache limpiado
- [ ] ✓ Servidor reiniciado
- [ ] ✓ Nuevos menús visibles en Filament
- [ ] ✓ Primer proveedor creado
- [ ] ✓ Primera compra creada
- [ ] ✓ Dashboard con datos

---

## 🎉 ¡LISTO!

El módulo de compras y proveedores está completamente instalado y funcional.

**Para comenzar:**
1. Crea proveedores (con datos reales)
2. Asocia productos con proveedores
3. Crea compras
4. Registra pagos
5. Consulta reportes

---

**Última actualización**: Marzo 27, 2026  
**Estado**: ✅ Listo para Producción  
**Versión**: 1.0.0
