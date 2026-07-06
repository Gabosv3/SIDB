# Cambios recientes para la app móvil (POS)

Cinco features nuevas: verificación/publicación de actualizaciones del APK, orden de clientes sincronizado con el servidor, edición de nombre/apellido de un cliente, resultado "abono previo" en visitas, y búsqueda de cliente por código entre todas las rutas del cobrador.

---

## 1. Verificación y publicación de actualizaciones del APK

### Endpoint para la app (mismo de siempre, sin cambios de contrato)

```http
GET /api/version
```

No requiere autenticación. Responde:

```json
{
  "version": "1.0.1",
  "url": "https://panel.distribuidorabriancescomenjivar.com/update/sidb.apk",
  "notas": "Correcciones de errores y nuevo sistema de orden de clientes"
}
```

**Comportamiento:**
- Lee primero un archivo estático publicado en el servidor (`/update/version.json`), que se actualiza cada vez que el administrador sube un nuevo APK desde el panel.
- Si todavía no se ha publicado nada por ese panel, cae de vuelta a los valores configurados en "Personalización del Sistema" (respaldo).
- La app **no necesita cambiar nada** — mismo endpoint, misma forma de respuesta.

El `.apk` también queda accesible directo como archivo estático en la URL que devuelve `url` (ej. `https://.../update/sidb.apk`), sin autenticación, sin pasar por Laravel.

### Panel administrativo nuevo (interno, no es parte de la API pública)

- Ruta: `/admin/update`, protegida con el permiso `Publicar:AppUpdate` (asignado a super_admin).
- Formulario: archivo `.apk` (máx. 100 MB), versión (formato `x.x.x`), notas opcionales.
- Al publicar: guarda `sidb.apk` (sobreescribe el anterior) y regenera `version.json` — eso es lo que `GET /api/version` devuelve de inmediato.

---

## 2. Orden de clientes sincronizado con el servidor

Antes el orden de visita del cobrador solo vivía en el dispositivo (`AsyncStorage`). Ahora se puede guardar en el servidor, asociado a la ruta (misma columna `orden` que ya usa el panel administrativo para "Clientes por Ruta"), y sobrevive reinstalaciones / cambios de teléfono.

### GET `/api/cobros/rutas/{ruta_id}/orden`

Requiere autenticación (Sanctum, cobrador). Devuelve los IDs de cliente en el orden guardado:

```http
GET /api/cobros/rutas/3/orden
Authorization: Bearer {token}
```

```json
{ "ids": [82, 81, 80, 79, 78, ...] }
```

### POST `/api/cobros/rutas/{ruta_id}/orden`

El cobrador reordena su ruta en la app y ese orden se guarda en el servidor:

```http
POST /api/cobros/rutas/3/orden
Authorization: Bearer {token}
Content-Type: application/json

{ "ids": [3, 1, 7, 2, ...] }
```

Respuesta:
```json
{ "mensaje": "Orden guardado." }
```

Valida que la ruta pertenezca al cobrador y que **todos** los IDs enviados pertenezcan a esa ruta (si alguno no coincide, responde `422`). Si la ruta no es del cobrador, responde `403`.

### Nota importante: el orden ya viene aplicado en el listado normal de clientes

`GET /api/cobros/ruta-hoy` y `GET /api/cobros/rutas/{ruta_id}/clientes` (los que la app ya usa para traer el listado completo) **ahora también respetan el orden guardado** — no hace falta llamar al endpoint de `/orden` solo para pintar la lista, ya viene ordenada. El endpoint de `/orden` es útil sobre todo para:
- Guardar (`POST`) cuando el cobrador reordena.
- Consultar (`GET`) rápido solo los IDs, sin traer todos los datos del cliente, si la app lo necesita en algún flujo específico.

Los clientes que **nunca han sido reordenados** (sin valor de `orden` todavía) aparecen al final de la lista, después de los que sí tienen un orden explícito — no se mezclan al azar entre los ya ordenados.

### Flujo sugerido para la app

1. Al iniciar sesión / entrar a la ruta del día: `GET /api/cobros/ruta-hoy` (ya viene en el orden correcto).
2. Si el cobrador reordena manualmente en la app: `POST /api/cobros/rutas/{ruta_id}/orden` con los IDs en el nuevo orden.
3. Si cambia de dispositivo o reinstala: al volver a pedir la ruta, el orden ya está ahí — no depende de `AsyncStorage`.

---

## 3. Actualizar nombre y/o apellido de un cliente

### PATCH `/api/clientes/{id}/nombre`

Requiere autenticación (Sanctum). Permite corregir el nombre y/o apellido de un cliente directamente desde la app (por ejemplo, si el cobrador nota un error al visitar).

```http
PATCH /api/clientes/45/nombre
Authorization: Bearer {token}
Content-Type: application/json

{
  "nombre": "Maria",
  "apellido": "Hernandez"
}
```

Ambos campos son **opcionales**, pero se debe enviar al menos uno (si no se envía ninguno, responde `422`).

Respuesta:
```json
{
  "mensaje": "Nombre actualizado.",
  "cliente": {
    "id": 45,
    "nombre": "Maria",
    "apellido": "Hernandez",
    "nombre_completo": "Maria Hernandez"
  }
}
```

Si el cliente no existe, responde `404`.

---

## 4. Nuevo resultado de visita: `abono_previo`

### POST `/api/cobros/clientes/{id}/visita`

Se agregó `abono_previo` a los valores permitidos de `resultado`, para cuando el cliente ya pagó su mensualidad por otro medio (transferencia, en oficina, etc.) y el cobrador solo necesita dejar constancia de la visita — **no genera ningún cobro nuevo**.

```http
POST /api/cobros/clientes/78/visita
Authorization: Bearer {token}
Content-Type: application/json

{
  "resultado": "abono_previo",
  "observaciones": "Pagó por transferencia el día de ayer"
}
```

**A diferencia de los otros resultados, con `abono_previo`:**
- **No requiere** `foto_hogar` ni coordenadas (`latitud`/`longitud`) — quedan sin guardar aunque se envíen.
- No aplica ningún pago a cuotas ni afecta el saldo del cliente.

Valores válidos de `resultado` ahora: `sin_pago`, `promesa_pago`, `no_encontrado`, `rechazo`, `abono_previo`.

---

## 5. Buscar cliente por código (entre todas las rutas del cobrador)

### GET `/api/cobros/clientes/buscar`

Busca por código (código anterior) entre **todos** los clientes de las rutas del cobrador, sin importar si la ruta es la de hoy o de otro día — útil cuando un cliente paga fuera de su día habitual de visita.

```http
GET /api/cobros/clientes/buscar?codigo=7304
Authorization: Bearer {token}
```

Respuesta (puede haber más de un resultado si el código es parcial):
```json
{
  "total": 1,
  "clientes": [
    {
      "id": 78,
      "nombre": "Maria Del Carmen Hernandez",
      "codigo_anterior": "7338",
      "telefono": "6033-0849",
      "direccion": "Col. Santa brizeida calle ppal",
      "saldo_total": 81.0,
      "ruta": { "id": 3, "nombre": "Zona 2 Gotera", "dia_semana": "lunes" }
    }
  ]
}
```

Solo devuelve clientes de rutas asignadas al cobrador autenticado (un cliente de otro cobrador nunca aparece). Una vez encontrado, usar `GET /api/cobros/clientes/{id}` para el detalle completo.
