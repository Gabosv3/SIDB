# Backend: correlativo de recibo por cobrador — IMPLEMENTADO

## Estado
✅ Implementado en el backend (Laravel). Pendiente solo de correr la migración en el servidor (bloqueado por MySQL caído en el entorno de desarrollo al momento de escribir esto) y verificación end-to-end con datos reales.

## Qué se pidió
Cada cobro debe imprimir un recibo con número correlativo propio. Antes se generaba localmente en el teléfono, pero si el teléfono se rompe y se reemplaza, el contador reinicia y puede repetir números. Se necesitaba que fuera autoritativo en el servidor, ligado al `cobrador_id` (usuario autenticado), no al dispositivo.

## Qué se implementó

### Base de datos
```sql
CREATE TABLE cobrador_recibos_contador (
    cobrador_id BIGINT UNSIGNED PRIMARY KEY,
    ultimo_numero INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NULL
);
```
Migración: `database/migrations/2026_07_15_145035_create_cobrador_recibos_contador_table.php`

### Modelo
`app/Models/CobradorRecibosContador.php` — PK propia (`cobrador_id`, no autoincremental), sin `created_at`.

### Endpoint modificado (no nuevo)
`POST /api/cobros/clientes/{id}/pagar` → `App\Http\Controllers\Api\CobroController@pagarCliente`

Se agregó `numero_recibo` a la respuesta existente (no se cambió nada más del formato). El correlativo se genera de forma atómica dentro de la misma transacción de BD que ya envolvía el registro del pago, con `lockForUpdate()` para evitar condiciones de carrera si el mismo cobrador envía dos cobros casi al mismo tiempo:

```php
private function siguienteNumeroRecibo(int $cobradorId): string
{
    $contador = CobradorRecibosContador::where('cobrador_id', $cobradorId)->lockForUpdate()->first();
    if (! $contador) {
        CobradorRecibosContador::create(['cobrador_id' => $cobradorId, 'ultimo_numero' => 0]);
        $contador = CobradorRecibosContador::where('cobrador_id', $cobradorId)->lockForUpdate()->first();
    }

    $siguiente = $contador->ultimo_numero + 1;
    $contador->update(['ultimo_numero' => $siguiente]);

    return sprintf('REC-%d-%06d', $cobradorId, $siguiente);
}
```

`$cobradorId` = `auth()->id()`, el usuario autenticado que hace el cobro (mismo valor que ya se guarda en `PagoVenta.user_id` en este mismo endpoint).

### Response (200) — campos agregados en negrita
```json
{
  "mensaje": "Pago registrado y distribuido en 1 cuota(s).",
  "numero_recibo": "REC-12-000351",
  "monto_total": 25.00,
  "cuotas_pagadas": [ ... ],
  "proxima_cuota": { ... }
}
```

`numero_recibo` tiene el formato `REC-{cobrador_id}-{consecutivo de 6 dígitos}`, ej. `REC-12-000351`.

## Nota sobre offline (ya manejado del lado móvil, no requiere nada más del backend)
Sin conexión, la app genera un número temporal local solo para imprimir en el momento. Al sincronizar, la app reemplaza ese temporal por el `numero_recibo` real que devuelve este endpoint — no hace falta reimprimir ni ningún cambio adicional en el backend para ese flujo.

## Lo que NO se tocó
- `POST /api/cobros/gestiones/{id}/pagar` (el otro endpoint de pago, a una gestión específica) — no estaba en el alcance pedido.
- El resto de la respuesta de `pagarCliente` (mensaje, monto_total, cuotas_pagadas, proxima_cuota) — se mantiene igual, solo se agregó `numero_recibo`.

## Pendiente de verificación (bloqueado por infraestructura, no por código)
1. Correr `php artisan migrate` para crear la tabla `cobrador_recibos_contador`.
2. Probar `POST /api/cobros/clientes/{id}/pagar` dos veces con el mismo cobrador → confirmar `REC-{id}-000001`, luego `REC-{id}-000002`.
3. Confirmar que otro cobrador arranca su propio contador en `000001` (independiente por `cobrador_id`).
