# 📐 DIAGRAMA DE RELACIONES - MÓDULO DE COMPRAS

## Diagrama de Entidades

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        SISTEMA DE COMPRAS                              │
└─────────────────────────────────────────────────────────────────────────┘

                              USUARIOS
                                 │
                                 │ 1:N
                         create compras / pagos
                                 │
                    ┌────────────┴────────────┐
                    │                         │
              ┌─────▼─────┐           ┌──────▼──────┐
              │  COMPRAS   │           │  PAGOS COMPRA
              └─────┬─────┘           └──────┬──────┘
                    │                         │
                    │ 1:N                    │ N:1
         ├──────────┴──────────┤             │
         │                     │             │
    ┌────▼──────┐      ┌──────▼────┐        │
    │ DETALLES  │      │PROVEEDOR◄─┼────────┘
    │COMPRAS    │      └───────┬────┘
    └────┬──────┘              │
         │                     │ N:M
    ┌────▼──────┐      ┌──────▼─────────┐
    │PRODUCTOS  ├─────►│PRODUCTO        │
    │(lectura)  │      │PROVEEDOR(pivot)│
    └───────────┘      └─────────────────┘
         │
         │ actualiza
         │ stock
         │
    ┌────▼────────────┐
    │MOVIMIENTOS STOCK│
    └──────────────────┘
```

---

## Relaciones Detalladas

### 1. **Proveedor → Compras** (1:N)
```
Un proveedor puede tener múltiples compras
═══════════════════════════════════════════════════

Proveedor {1} ═══════════════ {N} Compra
  ├─ id: 1                      ├─ id: 1
  ├─ nombre                     ├─ numero_compra
  └─ ...                        ├─ proveedor_id ← FK
                                ├─ fecha_compra
                                └─ ...
```

**En Laravel:**
```php
$proveedor->compras();           // N compras
$compra->proveedor;              // 1 proveedor
```

---

### 2. **Compra → Detalles de Compra** (1:N)
```
Una compra contiene múltiples detalles (artículos)
═════════════════════════════════════════════════

Compra {1} ═══════════════ {N} DetalleCompra
  ├─ id: 1                   ├─ id: 1
  ├─ numero_compra           ├─ compra_id ← FK
  └─ total                   ├─ producto_id ← FK
                             ├─ cantidad
                             ├─ precio_unitario
                             └─ subtotal
```

**En Laravel:**
```php
$compra->detalles();             // N detalles
$detalle->compra;                // 1 compra
```

---

### 3. **Compra → Pagos de Compra** (1:N)
```
Una compra puede tener múltiples pagos (parciales o completo)
══════════════════════════════════════════════════════════════

Compra {1} ═══════════════ {N} PagoCompra
  ├─ id: 1                    ├─ id: 1
  ├─ numero_compra            ├─ compra_id ← FK
  ├─ total                    ├─ monto
  ├─ saldo_pendiente          ├─ fecha_pago
  └─ ...                      └─ forma_pago
```

**En Laravel:**
```php
$compra->pagos();                // N pagos
$pago->compra;                   // 1 compra
$pago->usuario;                  // usuario que registró
```

---

### 4. **Proveedor ↔ Producto** (N:M)
```
Un proveedor suministra múltiples productos
Un producto puede ser suministrado por múltiples proveedores
═════════════════════════════════════════════════════════════

                    producto_proveedor (PIVOT)
                            ↑↑↑↑↑
        ┌──────────────────────╫──────────────────────┐
        │                      ║                      │
    {N} Proveedor            {N} Producto {M}
      ├─ id: 1                  ├─ id: 1
      ├─ nombre                 ├─ nombre
      └─ código                 └─ código
      
      PIVOT:
        ├─ proveedor_id
        ├─ producto_id
        ├─ codigo_proveedor
        ├─ precio_unitario
        ├─ cantidad_minima
        ├─ tiempo_entrega_dias
        └─ timestamps
```

**En Laravel:**
```php
$proveedor->productos();        // todos los productos
$producto->proveedores();       // todos los proveedores
$proveedor->productos()->pivot; // datos del pivote
```

---

### 5. **DetalleCompra → Producto** (N:1)
```
Múltiples detalles hacen referencia a un producto
(Para auditoría y trazabilidad)
═════════════════════════════════════════

DetalleCompra {N} ═════════════ {1} Producto
  ├─ id: 1                        ├─ id: 1
  ├─ compra_id                    ├─ nombre
  ├─ producto_id ← FK             ├─ stock
  ├─ cantidad                     └─ precio_actual
  └─ precio_unitario (snapshot)
```

**En Laravel:**
```php
$detalle->producto;              // 1 producto
$producto->detallesCompra();     // N detalles
```

---

### 6. **Usuario → Compras** (1:N)
```
Un usuario puede crear múltiples compras
═════════════════════════════════════════

Usuario {1} ═════════════ {N} Compra
  ├─ id: 1                   ├─ usuario_id ← FK
  ├─ name                    ├─ numero_compra
  └─ email                   └─ ...
```

**En Laravel:**
```php
$usuario->compras();             // compras creadas
$compra->usuario;                // usuario que creó
```

---

### 7. **Usuario → Pagos de Compra** (1:N)
```
Un usuario puede registrar múltiples pagos
═════════════════════════════════════════════

Usuario {1} ═════════════ {N} PagoCompra
  ├─ id: 1                    ├─ usuario_id ← FK
  ├─ name                     ├─ monto
  └─ email                    └─ fecha_pago
```

**En Laravel:**
```php
$usuario->pagos();               // pagos registrados
$pago->usuario;                  // usuario que registró
```

---

## Flujo de Datos - Compra Completa

```
1. CREAR COMPRA
   ┌────────────────────────────────────────┐
   │ POST /admin/compras (CREATE)           │
   │ - Seleccionar Proveedor                │
   │ - Fecha de compra                      │
   │ - Usuario autenticado                  │
   └────────────────────┬────────────────────┘
                        │
                        ▼
            ┌───────────────────────┐
            │   INSERT compras      │
            │ numero_compra: AUTO   │
            │ proveedor_id: 5       │
            │ usuario_id: 1         │
            │ estado: 'pendiente'   │
            └───────────┬───────────┘
                        │
2. AGREGAR ARTÍCULOS
   ┌────────────────────────────────────────┐
   │ ADD detalle_compras (REPEATER)         │
   │ - Producto                             │
   │ - Cantidad                             │
   │ - Precio Unitario                      │
   │ - Descuento                            │
   └────────────────────┬────────────────────┘
                        │
                        ▼
      ┌────────────────────────────────────┐
      │ INSERT detalle_compras (múltiples) │
      │ compra_id: 1                       │
      │ producto_id: [N]                   │
      │ cantidad: [N]                      │
      │ precio_unitario: [N]               │
      └────────┬────────────────────────────┘
               │
               ▼
    ┌──────────────────────────────┐
    │ DetalleCompraObserver        │
    │ - Calcula subtotal           │
    │ - Actualiza totales compra   │
    └──────────────────────────────┘
                │
3. ACTUALIZAR TOTALES
   ┌────────────────────────────────────────┐
   │ UPDATE compras  (AUTOMÁTICO)           │
   │ - subtotal: sum(detalles.subtotal)     │
   │ - total: subtotal - desc + impuesto    │
   │ - saldo_pendiente: total               │
   └───────────────────────────────────────┘
                        │
4. REGISTRAR PAGO
   ┌────────────────────────────────────────┐
   │ INSERT pagos_compra                    │
   │ - monto: 5000                          │
   │ - forma_pago: 'transferencia'          │
   └────────────────────┬────────────────────┘
                        │
                        ▼
      ┌────────────────────────────────────┐
      │ PagoCompraObserver                  │
      │ - Reduce saldo_pendiente            │
      │ - Si saldo = 0: estado = 'completada'
      └────────────────────────────────────┘
                        │
5. RECIBIR COMPRA (Cambiar Estado)
   ┌────────────────────────────────────────┐
   │ UPDATE compras SET estado = 'recibida' │
   └────────────────────┬────────────────────┘
                        │
                        ▼
      ┌────────────────────────────────────┐
      │ CompraObserver                      │
      │ Para cada detalle:                  │
      │ - UPDATE productos: stock += cant  │
      │ - INSERT movimientos_stock         │
      └────────────────────────────────────┘
                        │
6. COMPRA COMPLETADA
   ┌────────────────────────────────────────┐
   │ Compra lista para auditoría            │
   │ - Totales finales                      │
   │ - Pagos registrados                    │
   │ - Stock actualizado                    │
   │ - Historial disponible                 │
   └────────────────────────────────────────┘
```

---

## Relaciones en LaravelORM

```php
// PROVEEDOR
$proveedor = Proveedor::with(['compras', 'productos'])->find(1);
$proveedor->compras;              // HasMany
$proveedor->productos;            // BelongsToMany
$proveedor->totalGastado();       // método helper
$proveedor->deudaPendiente();     // método helper

// COMPRA
$compra = Compra::with(['proveedor', 'detalles', 'pagos', 'usuario'])->find(1);
$compra->proveedor;               // BelongsTo
$compra->detalles;                // HasMany → DetalleCompra
$compra->pagos;                   // HasMany → PagoCompra
$compra->usuario;                 // BelongsTo → User
$compra->estaPagada();           // método helper
$compra->porcentajePago();       // método helper

// DETALLE COMPRA
$detalle = DetalleCompra::with(['compra', 'producto'])->find(1);
$detalle->compra;                 // BelongsTo
$detalle->producto;               // BelongsTo
$detalle->calcularSubtotal();    // método helper
$detalle->ahorroTotal();         // método helper

// PAGO COMPRA
$pago = PagoCompra::with(['compra', 'usuario'])->find(1);
$pago->compra;                    // BelongsTo
$pago->usuario;                   // BelongsTo

// USUARIO
$usuario = User::with(['compras', 'pagos'])->find(1);
$usuario->compras;                // HasMany → Compra
$usuario->pagos;                  // HasMany → PagoCompra

// PRODUCTO
$producto = Producto::with(['proveedores', 'detallesCompra'])->find(1);
$producto->proveedores;           // BelongsToMany
$producto->detallesCompra;        // HasMany
$producto->margenGanancia();      // método helper
$producto->necesitaReabastecimiento(); // método helper
```

---

## Almacenamiento de Datos - Snapshots

```
Razón: Capturar precios en el momento de compra
══════════════════════════════════════════════════

TABLA: detalle_compras
┌──────────────┬────────────────────────────────────┐
│ Campo        │ Propósito                          │
├──────────────┼────────────────────────────────────┤
│ producto_id  │ Referencia al producto (puede      │
│              │ cambiar precio más adelante)       │
│              │                                    │
│ precio_unit. │ Snapsh del precio en ese momento   │
│              │ Ejemplo: Producto costaba $100,    │
│              │ ahora cuesta $120, pero esta       │
│              │ compra quedó registrada en $100    │
│              │                                    │
│ descuento    │ Descuento específico de esa        │
│              │ transacción                        │
└──────────────┴────────────────────────────────────┘

Ventajas:
  ✓ Historial fiel de precios
  ✓ Auditoría precisa
  ✓ No afecta cambios futuros
  ✓ Análisis histórico exacto
```

---

## Índices Clave - Optimización

```
TABLA: proveedores
  INDEX: codigo
  INDEX: activo

TABLA: compras
  INDEX: numero_compra (unique)
  INDEX: proveedor_id
  INDEX: fecha_compra
  INDEX: estado

TABLA: detalle_compras
  INDEX: compra_id
  INDEX: producto_id

TABLA: pagos_compra
  INDEX: compra_id
  INDEX: fecha_pago

TABLA: producto_proveedor
  UNIQUE: (producto_id, proveedor_id)
  INDEX: proveedor_id

Beneficios:
  ✓ Búsquedas rápidas
  ✓ Reportes optimizados
  ✓ Consultas eficientes
```

---

## Transacciones y Consistencia

```
Operación: Crear Compra y Detalles
════════════════════════════════════

BEGIN TRANSACTION
  ├─ INSERT compras ✓
  ├─ INSERT detalle_compras (múltiple) ✓
  ├─ SELECT and SUM para totales ✓
  ├─ UPDATE compras con totales ✓
  └─ Si error: ROLLBACK
     Si éxito: COMMIT

Resultado: Compra consistente o sin cambios
```

---

## Conclusiones

✅ **Relaciones bien definidas** para data integrity  
✅ **Snapshots de precios** para auditoría  
✅ **Observers automáticos** para consistencia  
✅ **Índices optimizados** para performance  
✅ **Métodos helpers** para lógica reutilizable  

---

**Última actualización**: Marzo 27, 2026  
**Versión**: 1.0.0  
**Estado**: ✅ Producción Ready
