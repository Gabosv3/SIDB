# 📦 MÓDULO DE COMPRAS Y PROVEEDORES

## ✨ Características Principales

### 1. **Gestión de Proveedores**
- Crear y mantener base de datos completa de proveedores
- Información detallada: contacto, ubicación, condiciones de pago
- Asociación de productos con proveedores
- Precios especiales por proveedor
- Descuentos comerciales configurables
- Estado de proveedor (activo/inactivo)

### 2. **Sistema Completo de Compras**
- Generación automática de números de compra únicos
- Gestión de detalles de artículos (items)
- Cálculo automático de totales, impuestos y descuentos
- Múltiples estados de compra (pendiente, recibida, completada, cancelada, devuelta)
- Información de lotes y fechas de vencimiento
- Diferentes formas y condiciones de pago

### 3. **Control de Pagos**
- Registro de pagos parciales o completos
- Seguimiento de saldo pendiente
- Múltiples formas de pago (efectivo, transferencia, cheque, tarjeta)
- Historial de pagos por compra

### 4. **Automatizaciones**
- Cálculo automático de subtotales y totales
- Actualización de saldo pendiente con cada pago
- Registro automático de movimientos de stock
- Cambios automáticos de estado según condiciones

### 5. **Reportes e Información**
- Resumen de compras por proveedor
- Deuda total pendiente
- Compras vencidas
- Histórico de compras
- Análisis de costos promedios

---

## 🚀 Instalación y Setup

### 1. Ejecutar Migraciones
```bash
php artisan migrate
```

Las siguientes migraciones se ejecutarán:
- `2026_03_27_000001_add_campos_to_productos_table`
- `2026_03_27_000002_create_proveedores_table`
- `2026_03_27_000003_create_producto_proveedor_table`
- `2026_03_27_000004_create_compras_table`
- `2026_03_27_000005_create_detalle_compras_table`
- `2026_03_27_000006_create_pagos_compra_table`

### 2. Ejecutar Seeders (Opcional)
Para llenar la base de datos con datos de prueba:
```bash
php artisan db:seed --class=ProveedorSeeder
```

---

## 📊 Modelos Disponibles

### **Proveedor**
```php
$proveedor = Proveedor::find(1);
$proveedor->compras();           // Todas las compras al proveedor
$proveedor->productos();         // Productos que suministra
$proveedor->totalGastado();      // Total gastado
$proveedor->tieneDeuda();        // ¿Tiene deuda?
$proveedor->deudaPendiente();    // Monto de deuda
```

### **Compra**
```php
$compra = Compra::find(1);
$compra->proveedor;              // Proveedor
$compra->detalles;               // Artículos comprados
$compra->pagos;                  // Historial de pagos
$compra->estaPagada();          // ¿Pagada completamente?
$compra->porcentajePago();      // % pagado
```

### **DetalleCompra**
```php
$detalle = DetalleCompra::find(1);
$detalle->compra;                // Compra asociada
$detalle->producto;              // Producto
$detalle->calcularSubtotal();   // Subtotal del item
$detalle->ahorroTotal();        // Ahorro por descuento
```

### **PagoCompra**
```php
$pago = PagoCompra::find(1);
$pago->compra;                   // Compra
$pago->usuario;                  // Usuario que registró
```

---

## 🎯 Casos de Uso

### Crear una Compra Completa

#### Opción 1: Usando Filament Panel
1. Navegar a **Compras** → **Nueva Compra**
2. Seleccionar proveedor
3. Añadir artículos (productos)
4. El sistema calcula automáticamente totales
5. Guardar compra

#### Opción 2: Usando el Servicio

```php
use App\Services\CompraService;

$compraService = app(CompraService::class);

$compra = $compraService->crearCompra(
    data: [
        'proveedor_id' => 1,
        'fecha_compra' => now(),
        'forma_pago' => 'credito',
        'condicion_pago' => 'credito',
        'dias_credito' => 30,
        'impuesto_porcentaje' => 16,
    ],
    detalles: [
        [
            'producto_id' => 1,
            'cantidad' => 10,
            'precio_unitario' => 100.00,
            'descuento_unitario' => 5.00,
        ],
        [
            'producto_id' => 2,
            'cantidad' => 5,
            'precio_unitario' => 50.00,
            'descuento_unitario' => 0,
        ],
    ]
);
```

### Registrar un Pago

```php
$compraService->registrarPago(
    $compra,
    monto: 500.00,
    formaPago: 'transferencia',
    referencia: 'TRX-123456',
    observaciones: 'Pago parcial'
);
```

### Obtener Productos para Reabastecer

```php
$productosReabastecer = $compraService->obtenerProductosPorReabastecer();

foreach ($productosReabastecer as $producto) {
    echo "{$producto->nombre}: Stock actual {$producto->stock}, Mínimo {$producto->stock_minimo}";
}
```

### Generar Reporte

```php
$reporte = $compraService->generarReporte(
    fechaInicio: Carbon::now()->subMonth(),
    fechaFin: Carbon::now()
);

echo "Total de compras: {$reporte['total_compras']}";
echo "Monto total: {$reporte['monto_total']}";
echo "Deuda pendiente: {$reporte['deuda_pendiente']}";
```

---

## 🔐 Permisos y Policies

El sistema incluye Policies para control de acceso:
- `ProveedorPolicy`: Control de acceso a proveedores
- `CompraPolicy`: Control de acceso a compras

Los permisos disponibles son:
- `view_proveedor` / `view_compra`
- `view_any_proveedor` / `view_any_compra`
- `create_proveedor` / `create_compra`
- `update_proveedor` / `update_compra`
- `delete_proveedor` / `delete_compra`
- `delete_any_proveedor` / `delete_any_compra`

Estos se integran automáticamente con Filament Shield.

---

## 📱 Interfaz Filament

### Proveedores
- **Listado**: Vista completa con filtros por estado y condición de pago
- **Crear**: Formulario con tabs para información general, ubicación y términos
- **Ver**: Infolist con detalles formateados
- **Editar**: Mismo formulario que crear

### Compras
- **Listado**: Con totales visibles y filtros avanzados
- **Crear**: Formulario completo con:
  - Selección de proveedor
  - Gestión de artículos (repeater)
  - Cálculo automático de cálculos
  - Términos de pago
- **Ver**: Desglose visual de la compra
- **Editar**: Modificación de datos

---

## 🔄 Observers Automáticos

El sistema incluye automáticamente:

1. **CompraObserver**
   - Registra compras creadas
   - Actualiza stock al recibir compra
   - Crea movimientos de stock

2. **DetalleCompraObserver**
   - Calcula subtotales automáticamente
   - Actualiza totales de compra al cambiar detalles

3. **PagoCompraObserver**
   - Reduce saldo pendiente al registrar pago
   - Cambia estado a completado cuando se paga completamente
   - Registra en logs

---

## 📈 Flujo Estándar de una Compra

```
1. CREAR COMPRA PENDIENTE
   └─ Sistema genera número único
   └─ Se registra fecha y proveedor
   └─ Se establece saldo pendiente = total

2. AGREGAR ARTÍCULOS
   └─ Se especifica cantidad, precio y descuentos
   └─ Sistema calcula subtotales automáticamente
   └─ Se actualizan totales de compra

3. CAMBIAR ESTADO A "RECIBIDA"
   └─ Sistema actualiza stock de productos
   └─ Se crean movimientos de stock
   └─ Se registra fecha de entrega real

4. REGISTRAR PAGOS
   └─ Se registra monto, forma y fecha
   └─ Sistema reduce saldo pendiente
   └─ Si saldo = 0, estado cambia a "COMPLETADA"

5. COMPRA COMPLETADA
   └─ Se puede consultar en reportes
   └─ Histórico disponible para auditoría
```

---

## 🛠️ Funciones del Servicio CompraService

```php
// Crear compra
$compra = $service->crearCompra($data, $detalles);

// Gestionar detalles
$service->agregarDetalle($compra, $data);
$service->actualizarDetalle($detalle, $data);
$service->eliminarDetalle($detalle);

// Pagos
$service->registrarPago($compra, $monto, $formaPago);

// Consultas
$service->obtenerProductosPorReabastecer();
$service->obtenerCostoPromedio($producto, $periodos);
$service->obtenerResumenPorProveedor($periodos);
$service->obtenerDeudaTotalPendiente();
$service->obtenerComprasVencidas();

// Reportes
$service->generarReporte($fechaInicio, $fechaFin);
```

---

## 📝 Notas Importantes

1. **Actualización de Stock**: El stock se actualiza automáticamente cuando la compra pasa al estado "recibida"

2. **Números Únicos**: Se generan automáticamente con formato `COM-YYYYMMDD-XXXXXX`

3. **Cálculos Automáticos**: Todos los totales se calculan automáticamente basados en detalles

4. **Auditoría**: Todos los cambios importantes se registran en logs

5. **Relaciones**: Los registros están protegidos contra eliminación si tienen dependencias

---

## ✅ Checklist de Setup

- [ ] Ejecutar migraciones: `php artisan migrate`
- [ ] Ejecutar seeders (opcional): `php artisan db:seed --class=ProveedorSeeder`
- [ ] Verificar en **Filament Admin** que aparezcan las nuevas opciones
- [ ] Crear primeros proveedores
- [ ] Crear primera compra de prueba
- [ ] Verificar que se cálculos funcionan correctamente
- [ ] Registrar pagos de prueba
- [ ] Revisar reportes

---

## 🐛 Troubleshooting

**P: Las migraciones no se ejecutan**
- Verificar que la base de datos esté accesible
- Ejecutar: `php artisan migrate:refresh` (cuidado: borra datos)

**P: Filament no muestra los nuevos módulos**
- Limpiar cache: `php artisan cache:clear`
- Limpiar config: `php artisan config:clear`
- Reiniciar servidor

**P: Los Observers no funcionan**
- Verificar que AppServiceProvider tenga registros correctos
- El archivo se actualiza automáticamente

**P: Errores en cálculos**
- Los Observers calculan automáticamente
- Si hay inconsistencias, verificar DetalleCompraObserver

---

## 📞 Soporte

Para issues o preguntas específicas, revisar:
- Documentación de Laravel: https://laravel.com
- Documentación de Filament: https://filamentphp.com
- Logs en: `storage/logs/laravel.log`

---

**Última actualización**: Marzo 27, 2026  
**Versión**: 1.0.0  
**Estado**: ✅ Producción Ready
