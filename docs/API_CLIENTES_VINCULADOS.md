# API de Clientes Vinculados (Grupo Familiar) - Documentación

## Resumen General

Un mismo cliente real (persona o familia) puede tener varias cuentas de crédito registradas por separado — a veces a su nombre, a veces a nombre de un familiar. Estos endpoints permiten **vincular** esas cuentas entre sí para que el cobrador vea todas las cuentas de un hogar juntas y sepa cuánto debe la familia en total.

No existe una tabla de "grupos" separada: `clientes.grupo_id` es un valor compartido entre los clientes vinculados. Cuando se vinculan dos clientes que no tienen grupo todavía, se usa el `id` del primero (`{id}` de la URL) como `grupo_id` para ambos.

No hay endpoint de "abono grupal" — la app hace varias llamadas al endpoint de pago que ya existe (uno por cada `venta_id`) para repartir un abono entre las cuentas del grupo.

---

## 1. POST `/api/clientes/{id}/vincular` — Vincular dos clientes

Une al cliente `{id}` con otro cliente bajo el mismo grupo familiar.

### Request
```
POST /api/clientes/45/vincular
Authorization: Bearer {token}
Content-Type: application/json

{
  "cliente_id_vincular": 88
}
```

### Parámetros (Body)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `cliente_id_vincular` | integer | ✅ | ID del cliente a vincular con `{id}` |

### Reglas
- No se permite vincular un cliente consigo mismo (`422` si `cliente_id_vincular === {id}`).
- Si **ninguno** de los dos tiene grupo: se crea uno nuevo usando el `id` de `{id}` como `grupo_id` para ambos.
- Si **uno** ya tiene grupo y el otro no: el que no tiene grupo adopta el del que sí tiene.
- Si **ambos** tienen grupos distintos: se fusionan — todos los clientes que estaban en el grupo del otro pasan al grupo de `{id}`. Esto soporta fusionar grupos de 3 o más personas en cadena.
- Si ya están en el mismo grupo, no hace nada (idempotente).

### Response (200)
```json
{
  "mensaje": "Clientes vinculados correctamente.",
  "grupo_id": 45
}
```

### Errores
| Código | Motivo |
|--------|--------|
| `404` | `{id}` o `cliente_id_vincular` no existe |
| `422` | Falta `cliente_id_vincular`, no es un cliente válido, o es igual a `{id}` |

---

## 2. GET `/api/clientes/{id}/grupo` — Ver cuentas del grupo

Devuelve al cliente `{id}` y a todos sus vinculados, con el detalle y saldo de cada cuenta a crédito activa.

### Request
```
GET /api/clientes/45/grupo
Authorization: Bearer {token}
```

### Comportamiento
- Si el cliente no tiene grupo, devuelve solo su propia información (`clientes` con un único elemento).
- Un cliente sin ventas a crédito activas sigue apareciendo, con `cuentas: []` y `saldo_total: 0`.
- Solo se listan ventas con `tipo_pago = credito` y `saldo_pendiente > 0`.
- `cuota_mensual` es el monto de la próxima cuota pendiente (`gestion_cobros.monto_cuota`, la de `numero_cuota` más bajo con `estado = pendiente`).
- `producto` es el nombre del producto de la venta; si la venta tiene varios ítems, se concatenan separados por coma.

### Response (200)
```json
{
  "grupo_id": 45,
  "clientes": [
    {
      "id": 45,
      "nombre": "Briancarlo",
      "apellido": "Escobar",
      "dui": "05684790-5",
      "cuentas": [
        {
          "venta_id": 501,
          "producto": "Colchón Doble",
          "saldo": 120.00,
          "cuota_mensual": 15.00
        }
      ],
      "saldo_total": 120.00
    },
    {
      "id": 88,
      "nombre": "Ana",
      "apellido": "Escobar",
      "dui": "07123456-8",
      "cuentas": [
        {
          "venta_id": 502,
          "producto": "TV 32\"",
          "saldo": 80.00,
          "cuota_mensual": 12.00
        }
      ],
      "saldo_total": 80.00
    }
  ],
  "saldo_total_grupo": 200.00
}
```

### Errores
| Código | Motivo |
|--------|--------|
| `404` | `{id}` no existe |

---

## 3. POST `/api/clientes/{id}/desvincular` — Sacar a un cliente del grupo

Quita a `{id}` de su grupo familiar. No afecta las ventas, pagos ni saldos del cliente — solo la relación de grupo.

### Request
```
POST /api/clientes/88/desvincular
Authorization: Bearer {token}
```

### Reglas
- Pone `grupo_id = null` únicamente para `{id}`.
- Si, después de desvincularlo, el grupo queda con un solo integrante, a ese integrante también se le quita el grupo (un grupo de 1 no tiene sentido).
- Si el cliente no pertenecía a ningún grupo, no hace nada y responde igual (idempotente).

### Response (200)
```json
{
  "mensaje": "Cliente desvinculado correctamente."
}
```

### Errores
| Código | Motivo |
|--------|--------|
| `404` | `{id}` no existe |

---

## Base de datos

```sql
ALTER TABLE clientes ADD COLUMN grupo_id BIGINT UNSIGNED NULL;
ALTER TABLE clientes ADD INDEX idx_clientes_grupo_id (grupo_id);
```

`grupo_id` no tiene llave foránea: es una etiqueta compartida entre clientes vinculados, no una referencia estricta a una fila "dueña" del grupo.

## Archivos involucrados

- `database/migrations/2026_07_15_140541_add_grupo_id_to_clientes_table.php`
- `app/Models/Cliente.php` — campo `grupo_id` en `$fillable` + relación `vinculados()`
- `app/Http/Controllers/Api/ClienteController.php` — métodos `vincular()`, `grupo()`, `desvincular()`
- `routes/api.php` — rutas dentro del grupo `auth:sanctum` + `pos.acceso`
