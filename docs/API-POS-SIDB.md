# API POS — SIDB

**Base URL:** `http://{host}/api`  
**Autenticación:** Bearer Token (Laravel Sanctum)  
**Content-Type:** `application/json`

---

## Índice

1. [Autenticación](#1-autenticación)
2. [Catálogos](#2-catálogos)
3. [Ventas (Solo Vendedores)](#3-ventas)
4. [Asignaciones (Solo Vendedores)](#4-asignaciones)
5. [Cobros (Solo Cobradores)](#5-cobros)
6. [Códigos de error](#6-códigos-de-error)
7. [Métodos de pago válidos](#7-métodos-de-pago-válidos)

---

## 1. Autenticación

### `POST /login`

**Acceso:** Público (sin token)

Inicia sesión y obtiene un token Bearer. Solo usuarios con perfil de vendedor o cobrador activo pueden autenticarse.

**Request:**
```json
{
  "email": "vendedor@sidb.local",
  "password": "secret"
}
```

**Response 200:**
```json
{
  "token": "1|abc123def456...",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "vendedor@sidb.local",
    "roles": ["vendedor"]
  }
}
```

**Errores:**
| Código | Descripción |
|--------|-------------|
| 401 | Credenciales incorrectas |
| 403 | Usuario no tiene perfil vendedor/cobrador activo |
| 422 | Campos email o password faltantes |

---

### `GET /me`

**Acceso:** Autenticado  
**Header:** `Authorization: Bearer {token}`

Retorna los datos completos del usuario autenticado, incluyendo perfil, roles, permisos y sucursales.

**Response 200:**
```json
{
  "id": 1,
  "name": "Juan Pérez",
  "email": "vendedor@sidb.local",
  "roles": ["vendedor"],
  "permisos": ["view_venta", "create_venta"],
  "sucursales": [
    { "id": 1, "nombre": "Sucursal Centro" }
  ],
  "perfil": {
    "es_vendedor": true,
    "es_cobrador": false,
    "vendedor": {
      "id": 3,
      "codigo": "V-001",
      "nombre": "Juan Pérez",
      "sucursal_id": 1,
      "es_cobrador": false
    },
    "cobrador": null
  }
}
```

---

### `POST /logout`

**Acceso:** Autenticado  
**Header:** `Authorization: Bearer {token}`

Revoca el token actual.

**Response 200:**
```json
{
  "message": "Sesión cerrada correctamente."
}
```

---

## 2. Catálogos

⚠️ **IMPORTANTE:** 
- **ProductoController (`/productos`, `/productos/{id}`, `/productos/{id}/metodos-pago`)** NO se usa en el flujo normal del POS de vendedores. Los vendedores obtienen sus productos desde `GET /asignacion/hoy` que devuelve solo los productos asignados para el día, con stock disponible y opciones de cuotas.
- **ProductoController** está disponible para cobradores puros (acceso de lectura) o consultas de catálogo general, pero no es el flujo principal.

### `GET /categorias`

Lista categorías activas.

**Parámetros query (opcionales):**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `sucursal_id` | integer | Filtrar por sucursal |

**Response 200:**
```json
[
  {
    "id": 1,
    "nombre": "Electrodomésticos",
    "descripcion": "Productos del hogar",
    "icono": "tv"
  }
]
```

---

### `GET /productos`

Lista productos activos. Si el usuario es vendedor, solo muestra los productos de su asignación del día con stock disponible.

**Parámetros query (opcionales):**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `q` | string | Buscar por nombre o código |
| `categoria_id` | integer | Filtrar por categoría |
| `sucursal_id` | integer | Filtrar por sucursal |
| `per_page` | integer | Resultados por página (default: 50) |
| `page` | integer | Número de página |

**Response 200 (vendedor):**
```json
{
  "data": [
    {
      "id": 5,
      "nombre": "Televisor 40\"",
      "codigo": "TV-40",
      "descripcion": "Televisor LED 40 pulgadas",
      "unidad_medida": "unidad",
      "precio_venta": 299.99,
      "precios_cuotas": [
        { "cuotas": 3, "precio_cuota": 105.00, "descripcion": "3 meses" },
        { "cuotas": 6, "precio_cuota": 55.00, "descripcion": "6 meses" }
      ],
      "stock_global": 100,
      "stock_asignado": 10,
      "stock_disponible": 8,
      "cantidad_vendida": 2,
      "activo": true,
      "categoria": "Electrodomésticos",
      "categoria_id": 1,
      "sucursal_id": 1,
      "imagen": "productos/tv-40.jpg"
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "per_page": 50,
  "total": 1
}
```

> **Nota:** Si el vendedor no tiene asignación activa hoy, retorna `{ "message": "No tienes productos asignados para hoy.", "data": [] }`

---

### `GET /productos/{id}`

Detalle de un producto específico. Vendedores solo pueden ver productos de su asignación.

**Response 200:** Objeto completo del producto con categoría y sucursal.

---

### `GET /productos/{id}/metodos-pago`

Obtiene las opciones de pago a cuotas configuradas para un producto.

**Response 200:**
```json
{
  "id": 5,
  "nombre": "Televisor 40\"",
  "codigo": "TV-40",
  "precio_venta": 299.99,
  "precios_cuotas": [
    { "cuotas": 3, "precio_cuota": 105.00, "descripcion": "3 meses" },
    { "cuotas": 6, "precio_cuota": 55.00, "descripcion": "6 meses" },
    { "cuotas": 12, "precio_cuota": 30.00, "descripcion": "12 meses" }
  ]
}
```

---

### `GET /clientes`

Lista clientes activos.

**Parámetros query (opcionales):**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `q` | string | Buscar por nombre, apellido, DUI o teléfono |
| `sucursal_id` | integer | Filtrar por sucursal |
| `per_page` | integer | Resultados por página (default: 50) |
| `page` | integer | Número de página |

**Response 200:** Lista paginada de clientes (campos: id, nombre, apellido, dui, telefono_normal, telefono_whatsapp, email, direccion, limite_credito, saldo, activo, sucursal_id).

---

### `GET /clientes/{id}`

Detalle de un cliente por ID.

**Response 200:**
```json
{
  "id": 1,
  "nombre": "María",
  "apellido": "González",
  "dui": "12345678-9",
  "nit": "0614-010190-001-0",
  "telefono_normal": "7654-3210",
  "telefono_whatsapp": "7654-3210",
  "email": "maria@email.com",
  "direccion": "Col. Escalón, calle 5",
  "departamento": "SAN SALVADOR",
  "municipio": "San Salvador Centro",
  "distrito": "San Salvador",
  "limite_credito": 500.00,
  "saldo": 120.00,
  "activo": true,
  "sucursal_id": 1,
  "ruta_cobro_id": 2
}
```

---

## 3. Ventas

**Acceso:** Solo vendedores (`solo.vendedor`)

### `GET /ventas`

Lista las ventas del vendedor autenticado.

**Parámetros query (opcionales):**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `sucursal_id` | integer | Filtrar por sucursal |
| `estado` | string | `completada`, `pendiente`, `anulada` |
| `per_page` | integer | Default: 20 |
| `page` | integer | Número de página |

**Response 200:** Lista paginada de ventas del usuario.

---

### `GET /ventas/{id}`

Detalle de una venta específica. Solo muestra ventas del usuario autenticado.

**Response 200:**
```json
{
  "id": 8,
  "numero_venta": "VNT-A1B2C3D4",
  "cliente": { "id": 1, "nombre": "María", "apellido": "González", "dui": "12345678-9", "telefono_normal": "7654-3210" },
  "fecha_venta": "2026-06-05T14:30:00",
  "estado": "pendiente",
  "tipo_pago": "credito",
  "total": 708.00,
  "monto_pagado": 118.00,
  "saldo_pendiente": 590.00,
  "detalles": [
    {
      "producto": { "id": 5, "nombre": "Televisor 40\"", "codigo": "TV-40", "unidad_medida": "unidad" },
      "cantidad": 1,
      "precio_unitario": 708.00,
      "descuento_porcentaje": 0,
      "subtotal": 708.00,
      "cuotas": 6,
      "precio_cuota": 118.00
    }
  ],
  "pagos": [
    { "id": 1, "monto": 118.00, "fecha_pago": "2026-06-05", "metodo_pago": "efectivo" }
  ]
}
```

---

### `POST /ventas`

Crea una venta con sus líneas de detalle. Si la venta tiene cuotas, genera automáticamente las gestiones de cobro.

**Request:**
```json
{
  "cliente_id": 1,
  "sucursal_id": 1,
  "tipo_pago": "credito",
  "dias_credito": 30,
  "descuento_porcentaje": 0,
  "observaciones": "",
  "detalles": [
    {
      "producto_id": 5,
      "cantidad": 1,
      "precio_unitario": 708.00,
      "descuento_porcentaje": 0,
      "cuotas": 6,
      "precio_cuota": 118.00
    }
  ]
}
```

**Campos de cada detalle:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `producto_id` | integer | Sí | ID del producto |
| `cantidad` | integer | Sí | Cantidad (min: 1) |
| `precio_unitario` | number | Sí | Precio unitario |
| `descuento_porcentaje` | number | No | Descuento (0-100) |
| `cuotas` | integer | No | Número de cuotas (min: 2). Si se envía, genera gestiones de cobro |
| `precio_cuota` | number | No | Precio por cuota. Si no se envía, se calcula automáticamente |

**Validaciones:**
- El vendedor debe tener una asignación activa para hoy.
- El producto debe estar en la asignación.
- La cantidad no puede exceder lo asignado menos lo ya vendido.

**Response 201:** Venta creada con detalles y productos.

**Gestiones de cobro generadas automáticamente:**
Cuando se incluyen cuotas, el sistema crea N gestiones de cobro:
- Cuota 1 → vence en 1 mes desde la fecha de venta
- Cuota 2 → vence en 2 meses
- Cuota N (última) → absorbe el residuo de redondeo

---

## 4. Asignaciones

**Acceso:** Solo vendedores (`solo.vendedor`)

### `GET /asignacion/hoy`

Obtiene la asignación activa de hoy para el vendedor autenticado, incluyendo opciones de pago a cuotas por producto.

**Response 200:**
```json
{
  "asignacion": {
    "id": 5,
    "fecha": "2026-06-05",
    "estado": "activa",
    "sucursal_id": 1,
    "observaciones": null,
    "productos": [
      {
        "id": 12,
        "producto_id": 5,
        "nombre": "Televisor 40\"",
        "codigo": "TV-40",
        "unidad_medida": "unidad",
        "precio_venta": 708.00,
        "precios_cuotas": [
          { "cuotas": 3, "precio_cuota": 236.00, "descripcion": "3 meses" },
          { "cuotas": 6, "precio_cuota": 118.00, "descripcion": "6 meses" }
        ],
        "imagen": "productos/tv-40.jpg",
        "cantidad_asignada": 10,
        "cantidad_vendida": 2,
        "cantidad_devuelta": 0
      }
    ]
  }
}
```

**Response 200 (sin asignación):**
```json
{
  "message": "No hay asignación activa para hoy.",
  "asignacion": null
}
```

---

### `POST /asignaciones/{id}/liquidar`

Liquida la asignación del día. Calcula totales vendidos y devueltos, cierra la jornada.

**Request (opcional):**
```json
{
  "observaciones": "Jornada normal, sin novedades"
}
```

**Response 200:**
```json
{
  "message": "Jornada liquidada correctamente.",
  "asignacion": {
    "id": 5,
    "fecha": "2026-06-05",
    "estado": "liquidada",
    "total_vendido": 1416.00,
    "total_devuelto_valor": 5664.00,
    "liquidada_at": "2026-06-05 18:30:00",
    "detalle": [
      {
        "producto": "Televisor 40\"",
        "cantidad_asignada": 10,
        "cantidad_vendida": 2,
        "cantidad_devuelta": 8,
        "valor_vendido": 1416.00,
        "valor_devuelto": 5664.00
      }
    ]
  }
}
```

**Errores:**
| Código | Descripción |
|--------|-------------|
| 422 | La asignación ya fue liquidada |

---

## 5. Cobros

**Acceso:** Solo cobradores (`solo.cobrador`)  
**Prefijo:** Todos los endpoints van bajo `/cobros/`

### Flujo de uso

```
1. GET /cobros/ruta-hoy
   ↓ (obtiene rutas del día)
2. GET /cobros/rutas/{ruta_id}/clientes
   ↓ (lista clientes de la ruta elegida)
3. GET /cobros/clientes/{id}
   ↓ (ve historial completo del cliente)
4. GET /cobros/clientes/{id}/gestiones-pendientes
   ↓ (ve cuotas a cobrar)
5. POST /cobros/gestiones/{id}/pagar
   ↓ (registra el pago)
```

---

### `GET /cobros/ruta-hoy`

Obtiene las rutas asignadas al cobrador para el día actual. Un cobrador puede tener múltiples rutas el mismo día.

**Response 200:**
```json
{
  "dia": "jueves",
  "rutas": [
    {
      "id": 2,
      "nombre": "Ruta Centro",
      "dia_semana": "jueves",
      "total_clientes": 15,
      "clientes": [
        {
          "id": 1,
          "nombre": "María González",
          "telefono": "7654-3210",
          "saldo": 120.00,
          "gestiones_pendientes": 3
        }
      ]
    },
    {
      "id": 7,
      "nombre": "Ruta Norte",
      "dia_semana": "jueves",
      "total_clientes": 8,
      "clientes": [...]
    }
  ]
}
```

**Response 404 (sin ruta hoy):**
```json
{
  "mensaje": "No hay ruta asignada para hoy (Jueves).",
  "dia": "jueves"
}
```

---

### `GET /cobros/rutas/{ruta_id}/clientes`

Lista los clientes activos de una ruta específica. Solo funciona si la ruta pertenece al cobrador.

**Response 200:**
```json
{
  "ruta": { "id": 2, "nombre": "Ruta Centro" },
  "total": 15,
  "clientes": [
    {
      "id": 1,
      "nombre": "María González",
      "dui": "12345678-9",
      "telefono": "7654-3210",
      "whatsapp": "7654-3210",
      "saldo": 120.00,
      "direccion": "Col. Escalón, calle 5, San Salvador Centro, SAN SALVADOR",
      "cuotas_pendientes": 3
    }
  ]
}
```

**Errores:**
| Código | Descripción |
|--------|-------------|
| 403 | La ruta no pertenece al cobrador |

---

### `GET /cobros/clientes/{id}`

Detalle completo del cliente con historial de todas sus gestiones de cobro (cobradas, pendientes, vencidas).

**Seguridad:** Solo muestra clientes que pertenecen a alguna ruta del cobrador.

**Response 200:**
```json
{
  "cliente": {
    "id": 1,
    "nombre": "María González",
    "dui": "12345678-9",
    "telefono": "7654-3210",
    "whatsapp": "7654-3210",
    "direccion": "Col. Escalón, calle 5",
    "saldo_total": 120.00,
    "ruta": "Ruta Centro"
  },
  "resumen": {
    "total_cuotas": 6,
    "pendientes": 3,
    "cobradas": 2,
    "vencidas": 1
  },
  "gestiones": [
    {
      "id": 10,
      "venta": "VNT-A1B2C3D4",
      "cuota": "1/6",
      "monto_cuota": 118.00,
      "monto_pagado": 118.00,
      "saldo_cuota": 0.00,
      "fecha_vencimiento": "05/07/2026",
      "estado": "cobrado",
      "vencida": false
    },
    {
      "id": 11,
      "venta": "VNT-A1B2C3D4",
      "cuota": "2/6",
      "monto_cuota": 118.00,
      "monto_pagado": 60.00,
      "saldo_cuota": 58.00,
      "fecha_vencimiento": "05/08/2026",
      "estado": "parcialmente_cobrado",
      "vencida": false
    },
    {
      "id": 12,
      "venta": "VNT-A1B2C3D4",
      "cuota": "3/6",
      "monto_cuota": 118.00,
      "monto_pagado": 0.00,
      "saldo_cuota": 118.00,
      "fecha_vencimiento": "05/09/2026",
      "estado": "pendiente",
      "vencida": false
    }
  ]
}
```

---

### `GET /cobros/clientes/{id}/gestiones-pendientes`

Solo las cuotas pendientes o parcialmente cobradas de un cliente. Útil para la pantalla de cobro.

**Response 200:**
```json
{
  "cliente_id": 1,
  "total_pendiente": 176.00,
  "gestiones": [
    {
      "id": 11,
      "venta": "VNT-A1B2C3D4",
      "cuota": "2/6",
      "monto_cuota": 118.00,
      "monto_pagado": 60.00,
      "saldo_pendiente": 58.00,
      "fecha_vencimiento": "05/08/2026",
      "estado": "parcialmente_cobrado",
      "vencida": false
    },
    {
      "id": 12,
      "venta": "VNT-A1B2C3D4",
      "cuota": "3/6",
      "monto_cuota": 118.00,
      "monto_pagado": 0.00,
      "saldo_pendiente": 118.00,
      "fecha_vencimiento": "05/09/2026",
      "estado": "pendiente",
      "vencida": false
    }
  ]
}
```

---

### `POST /cobros/gestiones/{id}/pagar`

Registra un pago (completo o parcial) sobre una cuota específica.

**Request:**
```json
{
  "monto": 58.00,
  "metodo_pago": "efectivo",
  "referencia": "REC-00456",
  "observaciones": "Cobro en domicilio"
}
```

**Campos:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `monto` | number | Sí | Monto a pagar (min: 0.01) |
| `metodo_pago` | string | Sí | `efectivo`, `transferencia`, `cheque`, `deposito` |
| `referencia` | string | No | Número de referencia o comprobante (max: 100) |
| `observaciones` | string | No | Notas del cobro (max: 500) |

**Validaciones:**
1. La gestión debe pertenecer a un cliente de las rutas del cobrador.
2. Si es cuota 2+, la cuota anterior debe estar completamente pagada.
3. El monto no puede exceder el saldo pendiente de la cuota.

**Response 200 (pago completo):**
```json
{
  "mensaje": "Cuota pagada completamente.",
  "gestion": {
    "id": 11,
    "cuota": "2/6",
    "monto_cuota": 118.00,
    "monto_pagado": 118.00,
    "saldo_pendiente": 0.00,
    "estado": "cobrado"
  },
  "venta_saldo": 472.00
}
```

**Response 200 (pago parcial):**
```json
{
  "mensaje": "Pago parcial registrado.",
  "gestion": {
    "id": 11,
    "cuota": "2/6",
    "monto_cuota": 118.00,
    "monto_pagado": 80.00,
    "saldo_pendiente": 38.00,
    "estado": "parcialmente_cobrado"
  },
  "venta_saldo": 510.00
}
```

**Errores:**
| Código | Descripción |
|--------|-------------|
| 403 | La gestión no pertenece a las rutas del cobrador |
| 404 | Gestión no encontrada |
| 422 | Cuota anterior tiene saldo pendiente |
| 422 | El monto excede el saldo pendiente de la cuota |

---

## 6. Códigos de Error

Todos los errores siguen el mismo formato:

```json
{
  "message": "Descripción del error."
}
```

Para errores de validación (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "campo": ["Mensaje de error específico"]
  }
}
```

| Código | Significado |
|--------|-------------|
| 200 | Éxito |
| 201 | Recurso creado exitosamente |
| 401 | No autenticado (token inválido o expirado) |
| 403 | Sin permisos (rol incorrecto o recurso ajeno) |
| 404 | Recurso no encontrado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## 7. Métodos de Pago Válidos

Todos los endpoints que aceptan `metodo_pago` usan los mismos valores:

| Valor | Descripción |
|-------|-------------|
| `efectivo` | Pago en efectivo |
| `transferencia` | Transferencia bancaria |
| `cheque` | Cheque |
| `deposito` | Depósito bancario |

---

## 8. Estados de Gestiones de Cobro

| Estado | Descripción |
|--------|-------------|
| `pendiente` | Cuota sin ningún pago registrado |
| `parcialmente_cobrado` | Cuota con pago parcial (queda saldo) |
| `cobrado` | Cuota pagada completamente |
| `vencido` | Cuota vencida (fecha pasada sin pago completo) |

---

## 9. Resumen de Endpoints

| Método | Endpoint | Acceso | Descripción |
|--------|----------|--------|-------------|
| `POST` | `/login` | Público | Autenticarse |
| `GET` | `/me` | Autenticado | Datos del usuario |
| `POST` | `/logout` | Autenticado | Cerrar sesión |
| `GET` | `/categorias` | POS | Listar categorías |
| `GET` | `/productos` | POS | Listar productos |
| `GET` | `/productos/{id}` | POS | Detalle producto |
| `GET` | `/productos/{id}/metodos-pago` | POS | Opciones de cuotas |
| `GET` | `/clientes` | POS | Listar clientes |
| `GET` | `/clientes/{id}` | POS | Detalle cliente |
| `GET` | `/ventas` | POS | Mis ventas |
| `GET` | `/ventas/{id}` | POS | Detalle de mi venta |
| `POST` | `/ventas` | Vendedor | Crear venta |
| `GET` | `/asignacion/hoy` | Vendedor | Asignación del día |
| `POST` | `/asignaciones/{id}/liquidar` | Vendedor | Liquidar jornada |
| `GET` | `/cobros/ruta-hoy` | Cobrador | Rutas de hoy |
| `GET` | `/cobros/rutas/{id}/clientes` | Cobrador | Clientes de una ruta |
| `GET` | `/cobros/clientes/{id}` | Cobrador | Historial del cliente |
| `GET` | `/cobros/clientes/{id}/gestiones-pendientes` | Cobrador | Cuotas pendientes |
| `POST` | `/cobros/gestiones/{id}/pagar` | Cobrador | Registrar pago |
