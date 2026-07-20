# Verificación pendiente — sesión 2026-07-17 / 2026-07-18

Todo lo de este documento está **escrito y con `php -l` limpio**, pero **nada se ha corrido contra una base de datos real** porque MySQL estuvo caído durante toda la sesión (`Test-NetConnection -ComputerName 127.0.0.1 -Port 3306` devolvió `False` en cada intento). Este es el runbook para verificarlo todo apenas la base de datos vuelva a estar disponible.

---

## 0. Antes de empezar

```bash
# 1. Confirmar que MySQL responde
powershell -Command "Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet"
# debe devolver True

# 2. Correr las 6 migraciones pendientes (en este orden, por dependencias de FK)
php artisan migrate

# 3. Confirmar que corrieron
php artisan migrate:status | tail -10

# 4. El disco "public" debe estar enlazado (para las fotos de comprobante)
php artisan storage:link
```

**Migraciones pendientes (8, ninguna se ha ejecutado):**

| # | Migración | Qué agrega |
|---|-----------|------------|
| 1 | `2026_07_17_095703_add_ruta_cobro_id_original_to_reintegros_table` | `reintegros.ruta_cobro_id_original` |
| 2 | `2026_07_18_094440_add_orden_original_to_reintegros_table` | `reintegros.orden_original` |
| 3 | `2026_07_18_115947_create_vehiculos_table` | tabla `vehiculos` |
| 4 | `2026_07_18_115955_create_vales_table` | tabla `vales` |
| 5 | `2026_07_18_201118_create_preventas_table` | tabla `preventas` |
| 6 | `2026_07_18_201123_create_detalle_preventas_table` | tabla `detalle_preventas` |
| 7 | `2026_07_19_211720_create_mantenimientos_vehiculo_table` | tabla `mantenimientos_vehiculo` |
| 8 | `2026_07_19_212956_add_descuenta_cobro_diario_to_vales_table` | `vales.descuenta_cobro_diario` (default `true`) |

> Nota aparte: la migración de WhatsApp/Baileys (`create_baileys_sessions_table`, commit `c748bec`) ya está en `main`, no en esta lista — su verificación end-to-end es la tarea #28, independiente de este documento.

---

## 1. Datos de prueba desechables

Todo con prefijo `TEST_` para poder limpiarlo al final sin tocar datos reales. Pega esto en `php artisan tinker`:

```php
use App\Models\{Sucursal, User, Cobrador, Vendedor, Cliente, Producto, Categoria, RutaCobro, AsignacionDiaria, DetalleAsignacion};

$sucursal = Sucursal::first(); // usa una sucursal real existente

// Cobrador de prueba
$userCobrador = User::create(['name' => 'TEST Cobrador', 'email' => 'test_cobrador@sidb.test', 'password' => bcrypt('password')]);
$cobrador = Cobrador::create(['sucursal_id' => $sucursal->id, 'nombre' => 'TEST', 'apellido' => 'Cobrador', 'user_id' => $userCobrador->id, 'activo' => true]);

// Vendedor de prueba
$userVendedor = User::create(['name' => 'TEST Vendedor', 'email' => 'test_vendedor@sidb.test', 'password' => bcrypt('password')]);
$vendedor = Vendedor::create(['nombre' => 'TEST', 'apellido' => 'Vendedor', 'sucursal_id' => $sucursal->id, 'user_id' => $userVendedor->id, 'activo' => true]);

// Ruta y cliente de prueba
$ruta = RutaCobro::create(['nombre' => 'TEST Ruta', 'cobrador_id' => $cobrador->id, 'dia_semana' => now()->locale('es')->dayName]);
$cliente = Cliente::create(['nombre' => 'TEST', 'apellido' => 'Cliente', 'ruta_cobro_id' => $ruta->id, 'orden' => 1, 'sucursal_id' => $sucursal->id]);

// Producto de prueba
$categoria = Categoria::first();
$producto = Producto::create(['nombre' => 'TEST Producto', 'codigo' => 'TEST-001', 'categoria_id' => $categoria->id, 'sucursal_id' => $sucursal->id, 'precio_venta' => 50, 'stock' => 100, 'activo' => true, 'unidad_medida' => 'unidad']);

// Asignación diaria activa para el vendedor de prueba (necesaria para crear ventas)
$asignacion = AsignacionDiaria::create(['vendedor_id' => $vendedor->id, 'sucursal_id' => $sucursal->id, 'fecha' => today(), 'estado' => 'activa']);
DetalleAsignacion::create(['asignacion_id' => $asignacion->id, 'producto_id' => $producto->id, 'cantidad_asignada' => 20, 'cantidad_vendida' => 0]);

// Tokens Sanctum para probar los endpoints con curl/Postman
echo "Token cobrador: " . $userCobrador->createToken('test')->plainTextToken . PHP_EOL;
echo "Token vendedor: " . $userVendedor->createToken('test')->plainTextToken . PHP_EOL;
echo "cliente_id={$cliente->id} producto_id={$producto->id} vendedor_id={$vendedor->id} cobrador_id={$cobrador->id} sucursal_id={$sucursal->id}" . PHP_EOL;
```

Guarda los dos tokens y los IDs que imprime — se usan en todos los `curl` de abajo. Reemplaza `{TOKEN_COBRADOR}`, `{TOKEN_VENDEDOR}` y los IDs en cada ejemplo.

---

## 2. Módulo: Reintegros y ventas canceladas (11 hallazgos de code review)

Esto es lo más delicado — toca `boot()` de `Reintegro` y `Venta`, con locks y transacciones.

### 2.1 Reintegro saca al cliente de la ruta solo si era su única cuenta activa

```php
// En tinker, crea una 2da venta a crédito con saldo para el mismo cliente de prueba
$venta1 = \App\Models\Venta::create(['cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'user_id' => $userVendedor->id, 'vendedor_id' => $vendedor->id, 'tipo_pago' => 'credito', 'total' => 100, 'saldo_pendiente' => 100, 'estado' => 'pendiente']);
$venta2 = \App\Models\Venta::create(['cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'user_id' => $userVendedor->id, 'vendedor_id' => $vendedor->id, 'tipo_pago' => 'credito', 'total' => 100, 'saldo_pendiente' => 100, 'estado' => 'pendiente']);

$reintegro = \App\Models\Reintegro::create(['venta_id' => $venta1->id, 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'asignado_por' => $userVendedor->id, 'estado' => 'pendiente', 'fecha_asignacion' => today()]);

$cliente->refresh();
echo $cliente->ruta_cobro_id; // ✅ debe SEGUIR con ruta (todavía tiene venta2 activa)
```

**Repite** enviando `$venta2` también a reintegro → ahora sí debe salir de la ruta (`$cliente->refresh()->ruta_cobro_id === null`), y `reintegro->ruta_cobro_id_original` debe tener guardado el id de `$ruta` (heredado del primer reintegro, no perdido).

### 2.2 Botón "Devolver a ruta" (Filament → Reintegros)

1. Marca el 2do reintegro como `recuperado` (edítalo en Filament o `$reintegro2->update(['estado' => 'recuperado', 'fecha_recuperacion' => today()])`).
2. En la tabla de Reintegros, la fila debe mostrar el botón **"Devolver a ruta"**.
3. Al hacerlo clic → `$cliente->ruta_cobro_id` y `orden` deben volver a los originales, y debe aparecer notificación de éxito.
4. Verifica que el filtro **"Solo activos (ocultar historial)"** (activo por defecto) deja de mostrar esta fila una vez devuelto — y que si NO se devuelve, la fila sigue visible (es la corrección del hallazgo #3).

### 2.3 Eliminar un reintegro no debe dejar al cliente varado

```php
$reintegro3 = \App\Models\Reintegro::create(['venta_id' => $venta1->id, 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id, 'asignado_por' => $userVendedor->id, 'estado' => 'en_proceso', 'fecha_asignacion' => today()]);
$cliente->refresh(); // debe estar sin ruta ahora
$reintegro3->delete();
$cliente->refresh();
echo $cliente->ruta_cobro_id; // ✅ debe haber vuelto, aunque el reintegro estaba "en_proceso" (no resuelto)
```

### 2.4 Cancelar una venta saca al cliente de la ruta solo si era su única cuenta

```php
$venta1->update(['estado' => 'cancelada']); // cliente sigue teniendo venta2 activa → NO debe salir de ruta
$cliente->refresh();
echo $cliente->ruta_cobro_id; // ✅ debe seguir con ruta

$venta2->update(['estado' => 'cancelada']); // ahora sí era la última → debe salir
$cliente->refresh();
echo $cliente->ruta_cobro_id; // ✅ debe ser null
```

### 2.5 Filament → Ventas → pestaña "Historial de canceladas"

Confirma que la venta cancelada aparece ahí y no en la lista normal por defecto.

---

## 3. Módulo: Auditoría de API (21 hallazgos) — smoke test rápido

No hace falta repetir los 21 uno por uno; con estos 6 checks cubres los críticos:

| Check | Cómo probarlo | Qué esperar |
|---|---|---|
| IDOR en `GET /clientes/{id}` | Con `{TOKEN_COBRADOR}`, pide un cliente que NO está en sus rutas | `404`, no el cliente de otro |
| `pagarCliente` no permite sobre-cobro con doble-tap | Dos `curl` casi simultáneos por el mismo monto que cubre el saldo exacto | Solo uno debe aplicar el pago completo; el otro debe fallar o aplicar 0 |
| `PosController::heartbeat` no deja secuestrar un dispositivo | Registra un `serial` con el usuario A, luego intenta con el usuario B | `403 "Este dispositivo ya está vinculado a otro usuario."` |
| Límite de crédito en `POST /ventas` | Ponle `limite_credito` bajo al cliente de prueba y crea una venta que lo supere | `422` con el mensaje de límite de crédito |
| `PATCH /reintegros/{id}/estado` respeta ownership | Con `{TOKEN_VENDEDOR}`, intenta cambiar un reintegro asignado a otro vendedor | `403 "Este reintegro no te pertenece."` |
| `POST /login` con rate limit | 11 intentos seguidos con password incorrecta | El intento 11 debe devolver `429` |

---

## 4. Módulo: Historial de cobro por día

### `GET /cobros/historial?fecha=YYYY-MM-DD`

```bash
# Hoy (sin parámetro)
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" http://localhost/api/cobros/historial

# Un día específico de este mes
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" "http://localhost/api/cobros/historial?fecha=2026-07-10"

# Fuera del mes en curso → debe fallar
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" "http://localhost/api/cobros/historial?fecha=2026-06-15"
```

**Esperado:**
- Sin `fecha` → `fecha` en la respuesta es hoy; `items` trae lo cobrado y las visitas sin pago de hoy, mezclado y ordenado por hora descendente (registra un pago con `POST /cobros/clientes/{id}/pagar` y una visita con `POST /cobros/clientes/{id}/visita` primero para tener datos de ambos tipos).
- Con `fecha` dentro del mes → filtra correctamente por ese día (`whereDate('fecha_pago', ...)` para pagos, `whereDate('fecha_visita', ...)` para visitas).
- Con `fecha` de mes anterior o futura → **422** `"Solo puedes consultar días del mes en curso, hasta hoy."`
- Confirma el orden: un pago y una visita del mismo cobrador con horas distintas deben salir intercalados por hora, no agrupados por tipo.

**Forma exacta de la respuesta (200):**
```json
{
  "fecha": "2026-07-18",
  "total_cobrado": 20.00,
  "total_pagos": 1,
  "total_visitas": 1,
  "items": [
    {
      "tipo": "visita",
      "id": 15,
      "cliente": {
        "id": 88,
        "nombre": "Juan Carlos Pérez",
        "codigo_anterior": "4521",
        "whatsapp": "70000000"
      },
      "resultado": "sin_pago",
      "promesa_fecha": null,
      "observaciones": "El cliente dice que no tiene efectivo hoy",
      "foto_hogar_url": "/storage/visitas/88/foto_abc123.jpg",
      "hora": "10:10"
    },
    {
      "tipo": "pago",
      "id": 103,
      "cliente": {
        "id": 97,
        "nombre": "Florinda Perez",
        "codigo_anterior": "7274",
        "whatsapp": "26543086"
      },
      "numero_venta": "VNT-SLEOVUSF",
      "productos": [
        { "nombre": "Televisor 55 pulgadas", "codigo": "TV-55", "cantidad": 1 }
      ],
      "monto": 20.00,
      "metodo_pago": "efectivo",
      "referencia": null,
      "hora": "09:45"
    }
  ]
}
```
> Cada item de `items[]` trae `"tipo": "pago"` o `"tipo": "visita"` — los campos que siguen dependen del tipo (los de pago tienen `numero_venta`/`productos`/`monto`/`metodo_pago`; los de visita tienen `resultado`/`promesa_fecha`/`foto_hogar_url`). Ambos comparten `id`, `cliente`, `hora`. `productos` es **array** (una venta puede tener más de una línea). `cliente.whatsapp` puede ser `null`. `numero_recibo` **no existe** en ningún endpoint — esa numeración era parte de la feature revertida esta sesión (`f53660a`), no está en el código actual.

---

## 5. Módulo: Vehículos y Vales

### 5.1 Setup — un vehículo asignado y uno de reserva

```php
$vehiculoAsignado = \App\Models\Vehiculo::create(['placa' => 'TEST-001', 'tipo' => 'moto', 'estado' => 'activo', 'asignado_a' => $userCobrador->id, 'sucursal_id' => $sucursal->id]);
$vehiculoReserva  = \App\Models\Vehiculo::create(['placa' => 'TEST-002', 'tipo' => 'moto', 'estado' => 'reserva', 'sucursal_id' => $sucursal->id]);
```

### 5.2 `GET /vehiculos/disponibles`

```bash
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" http://localhost/api/vehiculos/disponibles
```
Esperado: devuelve **2** vehículos — `TEST-001` con `"es_mio": true` primero, y `TEST-002` (reserva) después. Un vehículo `inactivo` de prueba NO debe aparecer.

### 5.3 `POST /vales` — vale de consumo (solo monto + foto)

```bash
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" \
  -F "tipo=consumo" \
  -F "monto=5.00" \
  -F "comprobante=@/ruta/a/foto_prueba.jpg" \
  http://localhost/api/vales
```
Esperado: `201`, `estado: "pendiente"`, el archivo debe existir en `storage/app/public/vales/{user_id}/`.

### 5.4 `POST /vales` — vale de vehículo (gasolina)

```bash
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" \
  -F "tipo=vehiculo" \
  -F "categoria_vehiculo=gasolina" \
  -F "vehiculo_id={ID_TEST-001}" \
  -F "monto=8.50" \
  -F "comprobante=@/ruta/a/foto_prueba.jpg" \
  http://localhost/api/vales
```
Esperado: `201`. **Repite** con el vehículo de otro cobrador (no asignado ni de reserva) → debe dar `403`.

**Prueba negativa importante:** intenta enviar `categoria_vehiculo=mantenimiento` desde este endpoint → debe fallar con `422` (esa categoría es solo para Filament/admin).

### 5.5 Filament → Vales → Aprobar / Rechazar

1. El vale creado arriba debe aparecer en la lista con badge "Pendiente" y el contador en el sidebar.
2. Clic en **"Aprobar"** → `estado` pasa a `aprobado`, se llena `aprobado_por` y `fecha_aprobado`.
3. Crea otro vale y usa **"Rechazar"** con un motivo → `estado` pasa a `rechazado`, `observaciones_admin` guarda el motivo.
4. Confirma que la foto se ve en la columna de comprobante (`ImageColumn`).

### 5.6 Filament → Vehículos

Crea/edita un vehículo, cambia `estado` a `reserva` y confirma que `asignado_a` se puede dejar vacío. Asigna un vehículo a un usuario y confirma que solo aparecen usuarios con perfil de vendedor o cobrador en el selector.

### 5.7 Filament → Vehículos → Historial de mantenimiento (tab dentro del vehículo)

1. Edita el vehículo `TEST-001` (o el que tengas de prueba) — debe aparecer una pestaña **"Historial de mantenimiento"** junto al formulario.
2. Click en **"Registrar mantenimiento"**: fecha, kilometraje (ej. 15000), tipo (ej. "Cambio de aceite"), taller, costo, "Próximo cambio sugerido" (ej. 18000), detalle, foto de factura opcional.
3. Guarda y confirma que la fila aparece en la tabla, ordenada por kilometraje descendente si agregas un segundo registro con km distinto.
4. Vuelve a la lista principal de Vehículos: las columnas **"Último km"** y **"Próximo cambio"** (ocultas por defecto, actívalas desde el ícono de columnas si aplica) deben reflejar el registro de mayor kilometraje que acabas de crear.
5. Borra el registro de prueba y confirma que las columnas vuelven a mostrar `—` si no queda ningún mantenimiento.

### 5.8 Renombrado a "Gastos" + cuadre de caja diario + descuento en Liquidación Semanal

El recurso Filament que antes se llamaba "Vales" ahora se llama **"Gastos"** en el menú (grupo "Vehículos y Gastos") — el modelo/tabla/endpoints siguen llamándose `Vale`/`vales` internamente, no cambia nada para la app móvil. Esto es para no confundirse con el "anticipo de sueldo" que ya existía en Liquidación Semanal (`AnticipoCobrador`, sistema completamente aparte, sin tocar).

**Setup — dos vales del mismo cobrador el mismo día:**
```bash
# 1) Vale chico desde el móvil (imprevisto de calle) — SIEMPRE descuenta_cobro_diario=true
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" \
  -F "tipo=vehiculo" -F "vehiculo_id={ID_VEHICULO}" -F "categoria_vehiculo=imprevisto" \
  -F "monto=5.00" -F "comprobante=@foto.jpg" \
  http://localhost/api/vales
```
```php
// 2) Vale grande registrado por el admin directo en Filament (o vía tinker para la prueba)
\App\Models\Vale::create([
    'user_id' => $userCobrador->id, 'sucursal_id' => $sucursal->id,
    'tipo' => 'vehiculo', 'vehiculo_id' => $vehiculoAsignado->id,
    'categoria_vehiculo' => 'mantenimiento', 'monto' => 150.00,
    'comprobante' => 'vales/fake.jpg', 'fecha_gasto' => today(),
    'estado' => 'pendiente', 'descuenta_cobro_diario' => false, // el toggle en Filament debe quedar apagado por defecto
]);
```

**Esperado en Filament → Resumen del Día → tabla "Gastos del día":**
- La columna **"Total gastado"** de este cobrador debe incluir **solo los $5.00** (el vale chico), no los $150.00 del vale grande.
- **"Neto a entregar"** = cobrado − 5.00 (no −155.00).
- En el recurso **Gastos**, la columna "Descuenta hoy" debe mostrar el ícono en verde para el vale de $5 y en gris/apagado para el de $150.

**Esperado en Filament → Liquidación Semanal** (con el vale chico marcado `estado=aprobado`):
- Tarjeta "Vales de consumo (aprobados)" — **solo cuenta si `tipo=consumo`**; el ejemplo de arriba es `tipo=vehiculo`, así que NO debe aparecer ahí (verifica por separado con un vale `tipo=consumo, estado=aprobado` de esa semana).
- `neto = comisión − anticipos − vales_consumo_aprobados` — confirma la resta con un cálculo a mano.

---

## 6. Módulo: Anular venta

```bash
# Crear una venta de prueba (contado, sin pagos) con {TOKEN_VENDEDOR}
curl -H "Authorization: Bearer {TOKEN_VENDEDOR}" -H "Content-Type: application/json" \
  -d '{"cliente_id": {ID_CLIENTE}, "sucursal_id": {ID_SUCURSAL}, "detalles": [{"producto_id": {ID_PRODUCTO}, "cantidad": 2, "precio_unitario": 50, "tipo_pago": "contado"}]}' \
  http://localhost/api/ventas
# guarda el "id" de la respuesta como {ID_VENTA}

curl -H "Authorization: Bearer {TOKEN_VENDEDOR}" -H "Content-Type: application/json" \
  -d '{"motivo": "El cliente se arrepintió"}' \
  http://localhost/api/ventas/{ID_VENTA}/anular
```

**Esperado:**
- `200`, `estado: "cancelada"`.
- En BD: `DetalleAsignacion` del producto vendido debe tener `cantidad_vendida` reducida en 2 (se revirtió el stock del día porque la asignación seguía activa).
- El cliente debe haber salido de su ruta si esta era su única cuenta activa (mismo efecto que 2.4).

**Pruebas negativas:**
- Anular la misma venta otra vez → `422 "Esta venta ya está anulada."`
- Crear una venta, registrarle un pago (`POST /ventas/{venta}/pagos`), luego intentar anularla → `422` (no debe permitir anular con pagos ya aplicados).
- Intentar anular una venta de otro vendedor → `404` (scope por `user_id`).

---

## 7. Módulo: Preventas

### 7.1 Cobrador registra una preventa

```bash
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" -H "Content-Type: application/json" \
  -d '{"cliente_id": {ID_CLIENTE}, "observaciones": "Quiere una tele", "detalles": [{"producto_id": {ID_PRODUCTO}, "cantidad": 1}]}' \
  http://localhost/api/preventas
```
Esperado: `201`, `estado: "pendiente"`, `monto_estimado` = precio de catálogo × cantidad.

**Prueba negativa:** el mismo `curl` con `{TOKEN_VENDEDOR}` → debe fallar (`solo.cobrador` la bloquea; un vendedor puro sin perfil de cobrador no debería poder crear preventas).

### 7.2 Filament → Preventas

1. La preventa debe aparecer con "— Sin asignar" en rojo y el badge del sidebar debe contarla.
2. Edítala y asigna el vendedor de prueba en **"Vendedor asignado"** — el selector solo debe mostrar vendedores de la misma sucursal del cobrador.
3. Guarda. En la tabla debe aparecer ahora el vendedor asignado y el botón **"Convertir en venta"** debe volverse visible (antes, sin vendedor, no lo era).

### 7.3 Convertir en venta

1. Clic en **"Convertir en venta"** → debe abrir el wizard normal de "Nueva Venta" con: cliente, vendedor y el producto/cantidad **ya prellenados**.
2. Completa el wizard (tipo de pago, totales, estado) y guarda.
3. Verifica en BD: la preventa debe quedar `estado: "convertida"` con `venta_id` apuntando a la venta recién creada.
4. Verifica que ya NO aparece el botón "Convertir en venta" ni "Rechazar" en esa fila (solo pendientes los tienen visibles).

### 7.4 Rechazar

Crea otra preventa de prueba y usa **"Rechazar"** con un motivo → `estado: "rechazada"`, motivo guardado en `observaciones`.

### 7.5 Vendedor ve sus preventas asignadas

```bash
curl -H "Authorization: Bearer {TOKEN_VENDEDOR}" http://localhost/api/preventas
```
Esperado: ve la preventa que le asignaste en 7.2 (por `vendedor_id`), no las de otros vendedores.

---

## 8. Limpieza de datos de prueba

```php
// En tinker, en orden (por FKs)
\App\Models\DetallePreventa::whereHas('preventa.cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\Preventa::whereHas('cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\Vale::whereHas('user', fn($q) => $q->where('email', 'like', 'test_%@sidb.test'))->delete();
\App\Models\Vehiculo::where('placa', 'like', 'TEST-%')->delete();
\App\Models\Reintegro::whereHas('cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\DetalleVenta::whereHas('venta.cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\GestionCobro::whereHas('venta.cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\Venta::whereHas('cliente', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\DetalleAsignacion::whereHas('asignacion.vendedor', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\AsignacionDiaria::whereHas('vendedor', fn($q) => $q->where('nombre', 'TEST'))->delete();
\App\Models\Cliente::where('nombre', 'TEST')->delete();
\App\Models\RutaCobro::where('nombre', 'TEST Ruta')->delete();
\App\Models\Producto::where('codigo', 'TEST-001')->delete();
\App\Models\Cobrador::where('nombre', 'TEST')->delete();
\App\Models\Vendedor::where('nombre', 'TEST')->delete();
\App\Models\MantenimientoVehiculo::whereHas('vehiculo', fn($q) => $q->where('placa', 'like', 'TEST-%'))->delete();
\App\Models\User::where('email', 'like', 'test_%@sidb.test')->delete();

// Borra también los archivos subidos de prueba
\Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('vales');
\Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('mantenimientos');
```

---

## 9. Referencia rápida — todo lo tocado esta sesión (sin commitear)

**Migraciones (8):** ver tabla en la sección 0.

**Modelos nuevos:** `Vehiculo`, `Vale`, `Preventa`, `DetallePreventa`, `MantenimientoVehiculo`
**Modelos editados:** `Reintegro`, `Venta`, `Cliente`, `User`, `Vale` (campo `descuenta_cobro_diario`), `Vehiculo` (relación `mantenimientos`)

**Controladores API nuevos:** `VehiculoController`, `ValeController`, `PreventaController`
**Controladores API editados:** `ClienteController`, `CobroController` (incluye `historial`), `PagoVentaController`, `PosController`, `ProductoController`, `ReintegroController`, `VentaController` (incluye `anular`), `AsignacionController`, `ValeController` (`descuenta_cobro_diario`)

**Endpoints nuevos:**
- `GET /cobros/historial` (reemplaza `historial-hoy`; acepta `?fecha=`, mezcla pagos y visitas del día con campo `tipo`, trae `productos[]` y `numero_venta` por pago, `cliente.whatsapp`)
- `GET /vehiculos/disponibles`
- `GET /vales`, `POST /vales`
- `POST /ventas/{id}/anular`
- `GET /preventas`, `POST /preventas`

**Servicios editados:** `ResumenCobrosDiaService` (nuevo método `valesPorCobrador()`)

**Filament — recursos/páginas nuevos:** `VehiculoResource` (+ RelationManager `MantenimientosRelationManager`), `ValeResource` (nav. "Gastos"), `PreventaResource`
**Filament — editados:** `ReintegroResource`, `VentaResource/Pages/ListVentas`, `VentaResource/Pages/CreateVenta` (prefill desde preventa), `ResumenCobrosDia` (tabla "Gastos del día"), `LiquidacionSemanal` (descuenta vale de consumo aprobado)

**Aún sin decidir (fuera de este documento):** los 5 endpoints "faltantes" de la auditoría (subir foto de cliente post-creación, detalle de asignación pasada) — no bloquean esta verificación.
