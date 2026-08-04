# Crear venta — API móvil (POS, vendedores)

`POST /ventas`

Registra una venta con sus líneas de detalle. Solo lo puede usar un usuario
con perfil de **vendedor** que tenga una **asignación diaria activa para
hoy** — es de ahí de donde salen los productos y precios disponibles para
vender, y contra eso se valida que no se venda más de lo asignado.

Base URL: `{APP_URL}/api`
Auth: `Authorization: Bearer {token}` (Sanctum)

---

## Request

```
POST /ventas
Authorization: Bearer 1|abcdef123456...
Content-Type: application/json
```

```json
{
  "cliente_id": 1,
  "sucursal_id": 1,
  "prima": 20.00,
  "dias_credito": 30,
  "descuento_porcentaje": 0,
  "observaciones": "Venta en casa del cliente",
  "detalles": [
    {
      "producto_id": 5,
      "cantidad": 1,
      "precio_unitario": 300.00,
      "descuento_porcentaje": 0,
      "tipo_pago": "credito",
      "cuotas": 10
    },
    {
      "producto_id": 8,
      "cantidad": 2,
      "precio_unitario": 15.00,
      "tipo_pago": "contado"
    }
  ]
}
```

### Campos (nivel venta)

| Campo                   | Tipo    | Requerido | Descripción                                                                 |
|--------------------------|---------|-----------|-------------------------------------------------------------------------------|
| `cliente_id`              | int     | Sí        | Debe existir en `clientes`                                                     |
| `sucursal_id`             | int     | Sí        | Debe existir en `sucursales`                                                   |
| `prima`                   | float   | No        | Solo aplica a las líneas a crédito; se limita automáticamente al total a crédito (no puede ser mayor) |
| `dias_credito`            | int     | No        | Default 30. Se usa para calcular `fecha_pago_limite` si hay líneas a crédito   |
| `descuento_porcentaje`    | float   | No        | 0–100, aplicado sobre el subtotal total de la venta                            |
| `observaciones`           | string  | No        | Máx. 500 caracteres                                                             |
| `detalles`                | array   | Sí        | Mínimo 1 línea                                                                  |

### Campos por línea (`detalles[]`)

| Campo                   | Tipo    | Requerido | Descripción                                                                 |
|--------------------------|---------|-----------|-------------------------------------------------------------------------------|
| `producto_id`             | int     | Sí        | Debe existir en `productos` **y** estar incluido en la asignación diaria del vendedor |
| `cantidad`                | int     | Sí        | Mínimo 1. La suma de todas las líneas del mismo producto (en esta venta + lo ya vendido hoy) no puede superar la cantidad asignada |
| `precio_unitario`         | float   | Sí        | Se usa solo si el producto no tiene precio propio en la asignación diaria (si lo tiene, ese precio manda sobre el enviado) |
| `descuento_porcentaje`    | float   | No        | 0–100                                                                          |
| `tipo_pago`                | string  | No        | `contado` \| `credito`. Ver nota abajo sobre cómo se calcula el tipo de venta  |
| `cuotas`                   | int     | No        | Mínimo 2. Solo tiene efecto en líneas a crédito                               |
| `precio_cuota`             | float   | No        | Si se omite y hay `cuotas`, se calcula automáticamente: `subtotal_de_la_línea / cuotas` |

### ⚠️ Cómo se determina el tipo de pago de la venta

**No se envía un `tipo_pago` a nivel de venta.** Se calcula solo, mirando el
`tipo_pago` de cada línea:

- Si **todas** las líneas que lo especifican son iguales → la venta es de ese tipo (`contado` o `credito`).
- Si hay líneas **contado y crédito mezcladas** → la venta queda `mixta`.
- Si **ninguna línea** especifica `tipo_pago` → la venta es `contado` por defecto.

---

## Respuesta 201

```json
{
  "id": 145,
  "cliente_id": 1,
  "sucursal_id": 1,
  "user_id": 9,
  "vendedor_id": 3,
  "tipo_pago": "mixta",
  "prima": 20.0,
  "dias_credito": 30,
  "fecha_pago_limite": "2026-09-02",
  "subtotal": 330.0,
  "descuento_porcentaje": 0,
  "descuento_monto": 0,
  "impuesto_porcentaje": 0,
  "impuesto_monto": 0,
  "total": 330.0,
  "monto_pagado": 50.0,
  "saldo_pendiente": 280.0,
  "estado": "pendiente",
  "observaciones": "Venta en casa del cliente",
  "detalles": [
    {
      "producto_id": 5,
      "producto": { "id": 5, "nombre": "Refrigeradora 12p", "codigo": "REF-12" },
      "cantidad": 1,
      "precio_unitario": 300.0,
      "descuento_porcentaje": 0,
      "subtotal": 300.0,
      "tipo_pago": "credito",
      "cuotas": 10,
      "precio_cuota": 28.0
    },
    {
      "producto_id": 8,
      "producto": { "id": 8, "nombre": "Licuadora", "codigo": "LIC-01" },
      "cantidad": 2,
      "precio_unitario": 15.0,
      "descuento_porcentaje": 0,
      "subtotal": 30.0,
      "tipo_pago": "contado",
      "cuotas": null,
      "precio_cuota": null
    }
  ]
}
```

### Notas de comportamiento

- **`estado`** es `completada` si `saldo_pendiente <= 0` (todo contado, o crédito ya cubierto con prima), si no queda `pendiente`.
- Si hay líneas a crédito con saldo pendiente > 0, se crean automáticamente las **cuotas** (`gestiones_cobro`), repartiendo `saldo_pendiente` entre el número de cuotas (`cuotas` de la primera línea a crédito que lo especifique, o 1 si ninguna lo hizo). El residuo de centavos se agrega a la última cuota.
- **Límite de crédito del cliente**: si el cliente tiene `limite_credito > 0` configurado, la venta se rechaza (422) si `saldo_actual_del_cliente + saldo_pendiente_de_esta_venta` supera ese límite.
- El vendedor **no puede vender lo que no le fue asignado** — cada `producto_id` debe estar en su `AsignacionDiaria` de hoy con `estado = activa`, y la cantidad acumulada vendida hoy (esta venta incluida) no puede pasarse de `cantidad_asignada`.

---

## Errores

| Código | Motivo                                                                       |
|--------|--------------------------------------------------------------------------------|
| 401    | No autenticado                                                                   |
| 403    | El usuario autenticado no tiene perfil de vendedor                              |
| 422    | Validación de campos fallida, sin asignación diaria activa hoy, producto fuera de la asignación, cantidad solicitada supera lo asignado, o cliente supera su límite de crédito |

Ejemplo de 422 por falta de asignación:
```json
{
  "message": "No tienes una asignacion activa para vender hoy.",
  "errors": { "detalles": ["No tienes una asignacion activa para vender hoy."] }
}
```

---

## Uso típico en la app

1. El vendedor abre su asignación diaria (productos y cantidades que le tocan hoy).
2. Arma el carrito, elige cliente y método de pago por línea (contado/crédito, cuotas).
3. Envía `POST /ventas`.
4. Si la venta queda con saldo pendiente, las cuotas generadas quedan disponibles para el cobrador vía `GET /cobros/clientes/{id}/gestiones-pendientes`.
