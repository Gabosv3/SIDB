# API de Vales (Gastos) y Vehículos — Guía para la app POS

**Base URL:** `http://{host}/api`
**Autenticación:** Bearer Token (Laravel Sanctum)
**Quién usa esto:** cobradores y vendedores (cualquier perfil con acceso POS), autenticados con su propio token.

---

## 1. Qué es un "vale"

Un vale es un **comprobante de gasto** que el cobrador o vendedor sube desde el celular cuando gasta dinero de lo que llevaba en la calle (de lo cobrado ese día, o de su bolsillo). Siempre requiere **foto del comprobante** — sin foto no se puede enviar.

Hay **dos tipos**:

| Tipo | Quién lo usa | Qué pide | Ejemplo |
|---|---|---|---|
| `consumo` | Cobrador o vendedor | Solo **monto** + foto | Almuerzo, refrigerio |
| `vehiculo` | Cobrador o vendedor con moto/carro asignado | Monto + foto + **vehículo** + **categoría** | Gasolina, llanta ponchada |

Para `tipo=vehiculo`, la app debe pedir además `categoria_vehiculo`, con solo **dos** opciones disponibles desde el móvil:

| Categoría | Cuándo usarla |
|---|---|
| `gasolina` | Cargar combustible |
| `imprevisto` | Algo chico que se arregla en el momento: llanta ponchada, cadena, foco fundido, etc. |

> ⚠️ **`mantenimiento` NO es una opción en la app.** Esa categoría es solo para reparaciones grandes que el administrador registra directo en el sistema (ej. cambio de motor, pintura). Si el servidor recibe `categoria_vehiculo=mantenimiento` desde el móvil, responde **422**. No la muestres como opción en el selector.

**Todo vale enviado desde el móvil se descuenta automáticamente del efectivo que el cobrador debe entregar ese día** (el servidor lo marca así solo, no hay que mandar nada extra para esto — ver sección 6).

---

## 2. Flujo recomendado en la app

```
1. El empleado toca "Registrar gasto" (o similar)
        ↓
2. Elige tipo: "Consumo" o "Vehículo"
        ↓
   ¿Es "Vehículo"?
        ↓ SÍ                                    ↓ NO (Consumo)
3a. GET /vehiculos/disponibles          3b. Solo pide monto + foto
    → selector de vehículo
    → selector de categoría
    (gasolina / imprevisto)
        ↓
4. Toma/selecciona la foto del comprobante (OBLIGATORIA)
        ↓
5. POST /vales (multipart/form-data)
        ↓
6. Muestra confirmación: "Vale enviado, pendiente de aprobación"
        ↓
7. El empleado puede ver su historial:
   - GET /vales → lista de SUS vales, con estado (pendiente/aprobado/rechazado)
   - GET /cobros/historial → mezclado con sus pagos y visitas del día (cobrador)
```

**Vehículo de reserva (avería grande):** si al empleado se le rompe la moto, no necesita ninguna acción especial para "cambiarse" de vehículo — `GET /vehiculos/disponibles` ya le muestra su vehículo asignado + los de reserva. Simplemente selecciona el de reserva al llenar el siguiente vale, y ya. El admin es quien más adelante, si quiere, formaliza la reasignación desde el panel.

---

## 3. `GET /vehiculos/disponibles`

Trae los vehículos que el empleado puede seleccionar: el suyo (asignado fijo) + los de reserva disponibles. Úsalo para llenar el selector de vehículo cuando `tipo=vehiculo`.

**Request:**
```bash
curl -H "Authorization: Bearer {TOKEN}" \
  http://localhost/api/vehiculos/disponibles
```

**Response 200** (array plano, sin envoltorio ni paginación):
```json
[
  {
    "id": 3,
    "placa": "P123-456",
    "tipo": "moto",
    "marca": "Honda",
    "modelo": "CG150",
    "estado": "activo",
    "es_mio": true
  },
  {
    "id": 7,
    "placa": "P789-012",
    "tipo": "moto",
    "marca": "Yamaha",
    "modelo": "YBR125",
    "estado": "reserva",
    "es_mio": false
  }
]
```

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | integer | Úsalo como `vehiculo_id` al enviar el vale |
| `placa` | string | Para mostrar en el selector |
| `tipo` | string | `moto` \| `carro` \| `pickup` \| `otro` |
| `marca`, `modelo` | string\|null | Pueden venir vacíos |
| `estado` | string | `activo` (el suyo funcionando), `reserva` (disponible para tomar), `mantenimiento` (en taller — puede venir si es el suyo, aunque no debería seleccionarse para gastos nuevos) |
| `es_mio` | boolean | `true` = es su vehículo asignado fijo. `false` = es de reserva. Úsalo para poner "Mi vehículo" vs "Reserva" en la UI |

**Sugerencia de UI:** ordena mostrando primero el/los que tengan `es_mio: true` (el servidor ya los devuelve en ese orden), y marca visualmente los de `estado: "reserva"` como "Disponible como reserva" para que el empleado entienda por qué le aparece un vehículo que no es suyo.

Si el array viene **vacío**, significa que el empleado no tiene vehículo asignado ni hay reservas disponibles — en ese caso, deshabilita la opción `tipo=vehiculo` o muestra un mensaje ("No tienes un vehículo asignado, contacta a tu supervisor").

---

## 4. `POST /vales` — enviar el vale

**Content-Type obligatorio:** `multipart/form-data` (por la foto). No lo mandes como JSON.

### Campos del formulario

| Campo | Tipo | Requerido | Reglas |
|---|---|---|---|
| `tipo` | string | ✅ siempre | `consumo` o `vehiculo` |
| `monto` | number | ✅ siempre | Mínimo `0.01`. Usa un input numérico con 2 decimales |
| `comprobante` | archivo (imagen) | ✅ siempre | jpg, jpeg, png o webp. **Máximo 5 MB**. Sin este campo el request falla con 422 |
| `descripcion` | string | ❌ opcional | Máx 500 caracteres. Úsalo para que el empleado explique el imprevisto |
| `fecha_gasto` | date (`YYYY-MM-DD`) | ❌ opcional | Si no lo mandas, el servidor usa la fecha de hoy. Normalmente **no lo mandes** — deja que el servidor la ponga, así siempre coincide con el día real |
| `vehiculo_id` | integer | Solo si `tipo=vehiculo` | Debe ser un `id` que salió de `GET /vehiculos/disponibles` |
| `categoria_vehiculo` | string | Solo si `tipo=vehiculo` | Únicamente `gasolina` o `imprevisto` (ver sección 1) |

### Ejemplo — vale de consumo (el más simple)

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  -F "tipo=consumo" \
  -F "monto=5.00" \
  -F "comprobante=@almuerzo.jpg" \
  http://localhost/api/vales
```

En la UI, el formulario de "Consumo" solo necesita: **monto** + **botón de cámara/galería**. Nada más.

### Ejemplo — vale de vehículo (gasolina)

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  -F "tipo=vehiculo" \
  -F "vehiculo_id=3" \
  -F "categoria_vehiculo=gasolina" \
  -F "monto=8.50" \
  -F "comprobante=@gasolina.jpg" \
  http://localhost/api/vales
```

### Ejemplo — vale de vehículo (imprevisto, con descripción)

```bash
curl -H "Authorization: Bearer {TOKEN}" \
  -F "tipo=vehiculo" \
  -F "vehiculo_id=3" \
  -F "categoria_vehiculo=imprevisto" \
  -F "monto=3.00" \
  -F "descripcion=Llanta ponchada en el camino" \
  -F "comprobante=@factura_llanta.jpg" \
  http://localhost/api/vales
```

### Respuesta exitosa — 201 Created

```json
{
  "mensaje": "Vale enviado, queda pendiente de aprobación.",
  "vale": {
    "id": 12,
    "tipo": "vehiculo",
    "monto": 8.50,
    "comprobante_url": "/storage/vales/5/xyz123.jpg",
    "estado": "pendiente"
  }
}
```

> Nota: esta respuesta trae **menos campos** que `GET /vales` (no incluye `vehiculo`, `categoria_vehiculo`, `descripcion`, `fecha_gasto`). Si tu app necesita mostrar el vale recién creado con todos sus datos justo después de guardarlo, simplemente vuelve a pedir `GET /vales` (o guarda localmente los datos que el usuario ya llenó en el formulario, ya que son los mismos que acabas de enviar).

`comprobante_url` es una ruta **relativa** al host — arma la URL completa como `{BASE_URL_SIN_/api}` + `comprobante_url` para mostrar la imagen (ej. `http://host.com/storage/vales/5/xyz123.jpg`).

### Errores posibles

| Código | Cuándo pasa | Body |
|---|---|---|
| **403** | El `vehiculo_id` enviado no es el suyo ni está en `estado=reserva` | `{"mensaje": "Este vehículo no te pertenece ni está disponible como reserva."}` |
| **422** | Falta `comprobante`, `monto` inválido, `categoria_vehiculo=mantenimiento`, falta `vehiculo_id`/`categoria_vehiculo` cuando `tipo=vehiculo`, etc. | `{"message": "...", "errors": {"campo": ["mensaje"]}}` (formato estándar de validación Laravel) |
| **401** | Token vencido o inválido | — |

**Manejo recomendado en la app:**
- Si es 403 → el vehículo que tenía guardado localmente ya no le pertenece (se lo reasignaron); vuelve a pedir `GET /vehiculos/disponibles` y que elija de nuevo.
- Si es 422 → muestra los mensajes de `errors` campo por campo debajo de cada input.
- Guarda el vale en cola local si no hay internet, y reintenta el `POST` cuando vuelva la conexión (el comprobante debe quedar cacheado localmente hasta que el envío sea exitoso).

---

## 5. `GET /vales` — historial propio de vales

Para que el empleado vea el estado de lo que ha enviado (pendiente / aprobado / rechazado).

**Query params opcionales:**

| Parámetro | Valores |
|---|---|
| `estado` | `pendiente` \| `aprobado` \| `rechazado` |
| `tipo` | `consumo` \| `vehiculo` |

```bash
curl -H "Authorization: Bearer {TOKEN}" "http://localhost/api/vales?estado=pendiente"
```

**Response 200** (array plano, del más reciente al más antiguo):
```json
[
  {
    "id": 12,
    "tipo": "vehiculo",
    "vehiculo": "P123-456",
    "categoria_vehiculo": "gasolina",
    "monto": 8.50,
    "comprobante_url": "/storage/vales/5/xyz123.jpg",
    "descripcion": null,
    "fecha_gasto": "2026-07-18",
    "estado": "pendiente",
    "observaciones_admin": null,
    "descuenta_cobro_diario": true,
    "creado": "18/07/2026 09:15"
  }
]
```

| Campo | Notas |
|---|---|
| `vehiculo` | Es solo el **string de la placa** (o `null` si es `tipo=consumo`), no un objeto |
| `estado` | Úsalo para el badge de color: `pendiente` (amarillo), `aprobado` (verde), `rechazado` (rojo) |
| `observaciones_admin` | Motivo que puso el administrador si rechazó el vale — muéstralo cuando `estado=rechazado` |
| `descuenta_cobro_diario` | Casi siempre `true` para lo que el propio empleado envió (todo lo del móvil se marca así). Puede venir `false` si es un vale grande que el administrador registró directamente por él — en ese caso puedes mostrar un texto tipo "Este gasto no se descuenta de tu entrega diaria" |
| `creado` | Ya viene formateado `d/m/Y H:i`, listo para mostrar |

**Sugerencia de pantalla:** una lista simple tipo "Mis vales" con filtros por estado arriba (chips: Todos / Pendientes / Aprobados / Rechazados), cada fila con: miniatura del comprobante, monto, tipo, fecha, badge de estado.

---

## 6. Cómo aparecen los vales en el historial del día (`GET /cobros/historial`)

Esto es **solo para cobradores** (el endpoint requiere perfil de cobrador). Además de sus pagos cobrados y visitas sin pago, el día también incluye los vales que envió ese día, mezclados en un solo array `items[]` ordenado por hora, cada uno con un campo `"tipo"` que dice qué es.

```bash
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" http://localhost/api/cobros/historial
# o con fecha específica del mes en curso:
curl -H "Authorization: Bearer {TOKEN_COBRADOR}" "http://localhost/api/cobros/historial?fecha=2026-07-18"
```

```json
{
  "fecha": "2026-07-18",
  "total_cobrado": 20.00,
  "total_gastado": 5.00,
  "total_pagos": 1,
  "total_visitas": 1,
  "total_gastos": 1,
  "items": [
    {
      "tipo": "gasto",
      "id": 12,
      "tipo_gasto": "vehiculo",
      "vehiculo": "P123-456",
      "categoria_vehiculo": "imprevisto",
      "monto": 5.00,
      "comprobante_url": "/storage/vales/5/xyz123.jpg",
      "descripcion": null,
      "estado": "pendiente",
      "descuenta_cobro_diario": true,
      "hora": "11:00"
    },
    {
      "tipo": "visita",
      "id": 15,
      "cliente": { "id": 88, "nombre": "Juan Carlos Pérez", "codigo_anterior": "4521", "whatsapp": "70000000" },
      "resultado": "sin_pago",
      "promesa_fecha": null,
      "observaciones": "El cliente dice que no tiene efectivo hoy",
      "foto_hogar_url": "/storage/visitas/88/foto_abc123.jpg",
      "hora": "10:10"
    },
    {
      "tipo": "pago",
      "id": 103,
      "cliente": { "id": 97, "nombre": "Florinda Perez", "codigo_anterior": "7274", "whatsapp": "26543086" },
      "numero_venta": "VNT-SLEOVUSF",
      "productos": [{ "nombre": "Televisor 55 pulgadas", "codigo": "TV-55", "cantidad": 1 }],
      "monto": 20.00,
      "metodo_pago": "efectivo",
      "referencia": null,
      "hora": "09:45"
    }
  ]
}
```

**Cómo procesarlo en la app (`normalizarItemServidor()` o equivalente):** revisa el campo `item.tipo` primero y arma un `switch`:

```js
switch (item.tipo) {
  case 'pago':
    // item.cliente, item.numero_venta, item.productos[], item.monto, item.metodo_pago
    break;
  case 'visita':
    // item.cliente, item.resultado, item.promesa_fecha, item.foto_hogar_url
    break;
  case 'gasto':
    // item.tipo_gasto, item.vehiculo, item.categoria_vehiculo, item.monto,
    // item.comprobante_url, item.estado, item.descuenta_cobro_diario
    // OJO: los items "gasto" NO traen "cliente" (no están ligados a un cliente)
    break;
}
```

- `total_cobrado` − `total_gastado` = lo que el cobrador debería tener en efectivo para entregar al final del día (mismo cálculo que usa el administrador en "Resumen del Día" del panel).
- `total_gastado` solo suma los gastos con `descuenta_cobro_diario=true` — si algún vale grande fue registrado por el admin con esa bandera apagada, no se resta acá, pero sí aparece en `items[]` con `tipo=gasto` para que quede visible en el historial.

---

## 7. Reglas de negocio — resumen para QA / pruebas

- ✅ Un vale **siempre** necesita foto (`comprobante`), sin excepción, para ambos tipos.
- ✅ `tipo=consumo` nunca pide `vehiculo_id` ni `categoria_vehiculo` — si los mandas, el servidor los ignora (no da error, pero no los guarda).
- ✅ `tipo=vehiculo` exige `vehiculo_id` + `categoria_vehiculo`, y la categoría solo puede ser `gasolina` o `imprevisto` desde el móvil.
- ✅ El `vehiculo_id` debe pertenecer al empleado (`asignado_a`) o estar en `estado=reserva` — cualquier otro vehículo da 403.
- ✅ Todo vale enviado por la app queda en `estado=pendiente` — nunca se auto-aprueba. El empleado no puede aprobar ni rechazar sus propios vales (eso solo lo hace un administrador en el panel).
- ✅ El vale no se puede editar ni eliminar desde la app una vez enviado (no hay `PUT`/`DELETE` para vales en la API móvil).
- ✅ La fecha del vale (`fecha_gasto`) determina en qué "historial del día" aparece — normalmente es hoy, así que no envíes ese campo salvo un caso especial.

---

## 8. Checklist de implementación para la app

- [ ] Pantalla "Registrar gasto" con selector Consumo / Vehículo
- [ ] Si Vehículo → llamar `GET /vehiculos/disponibles` y poblar selector + selector de categoría (gasolina/imprevisto)
- [ ] Captura de foto obligatoria antes de habilitar el botón de enviar
- [ ] `POST /vales` como `multipart/form-data`, con manejo de cola offline si no hay internet
- [ ] Manejo de errores 403/422 con mensajes claros
- [ ] Pantalla "Mis vales" consumiendo `GET /vales`, con filtro por estado
- [ ] Historial del día (cobrador) actualizado para distinguir `pago` / `visita` / `gasto` vía el campo `tipo`
- [ ] Mostrar `total_cobrado − total_gastado` como "efectivo a entregar" en la pantalla de resumen del día, si aplica
