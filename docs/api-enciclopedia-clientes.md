# Enciclopedia de clientes — API móvil (POS)

Dos endpoints de solo lectura para que el cobrador pueda consultar cualquier
cliente de sus rutas, aunque no sea el de hoy.

Base URL: `{APP_URL}/api`
Auth: `Authorization: Bearer {token}` (Sanctum)

---

## 1. Directorio completo de clientes del cobrador (agrupado por ruta)

Lista **todos** los clientes de **todas las rutas activas** del cobrador
(no solo la ruta de hoy), agrupados por ruta y ordenados por el orden manual
de cada ruta. Pensado como directorio/consulta rápida — un "quién es quién"
de toda la cartera del cobrador.

```
GET /cobros/clientes
```

### Parámetros (query)

| Parámetro | Tipo   | Requerido | Descripción                                                          |
|-----------|--------|-----------|------------------------------------------------------------------------|
| `buscar`  | string | No        | Filtra dentro de cada ruta por `nombre`, `apellido`, `codigo_anterior` o `telefono` |

### Ejemplo de request

```
GET /cobros/clientes
Authorization: Bearer 1|abcdef123456...
```

```
GET /cobros/clientes?buscar=Maria
Authorization: Bearer 1|abcdef123456...
```

### Respuesta 200

```json
{
  "total_rutas": 3,
  "total_clientes": 92,
  "rutas": [
    {
      "ruta": { "id": 4, "nombre": "Ruta Centro", "dia_semana": "lunes" },
      "total_clientes": 26,
      "clientes": [
        {
          "id": 78,
          "nombre": "Juan Pérez",
          "codigo_anterior": "7304",
          "telefono": "7000-0000",
          "whatsapp": "7000-0000",
          "saldo_total": 120.5,
          "direccion": "Col. Las Flores, San Miguel"
        }
      ]
    },
    {
      "ruta": { "id": 5, "nombre": "Ruta Norte", "dia_semana": "miércoles" },
      "total_clientes": 63,
      "clientes": [ "..." ]
    }
  ]
}
```

### Errores

| Código | Motivo                                              |
|--------|---------------------------------------------------------|
| 403    | El usuario autenticado no tiene perfil de cobrador       |

### Uso típico en la app

1. El cobrador abre la sección "Directorio de clientes" (fuera del flujo normal de cobro del día).
2. Se llama a este endpoint (opcionalmente con `?buscar=` mientras escribe).
3. Al tocar un cliente, se navega al **perfil completo** usando su `id` (ver endpoint 2).

---

## 2. Perfil completo del cliente (enciclopedia)

Devuelve toda la información que ve el administrador en el panel web: datos
personales, referencias, ubicación, historial de ventas con pagos y visitas
mezclados en una sola línea de tiempo, y el historial de cambios de ruta.
Cubre **todas las rutas del cobrador** — pensado para consulta, no para
registrar cobros desde acá (eso se hace con `POST /cobros/clientes/{id}/pagar`).

```
GET /cobros/clientes/{id}/perfil
```

### Parámetros (path)

| Parámetro | Tipo | Requerido | Descripción      |
|-----------|------|-----------|-------------------|
| `id`      | int  | Sí        | ID del cliente     |

### Ejemplo de request

```
GET /cobros/clientes/78/perfil
Authorization: Bearer 1|abcdef123456...
```

### Respuesta 200

```json
{
  "cliente": {
    "id": 78,
    "codigo_anterior": "7304",
    "nombre": "Juan Pérez",
    "dui": "01234567-8",
    "nit": "0614-...",
    "email": "juan@example.com",
    "telefono": "7000-0000",
    "whatsapp": "7000-0000",
    "direccion": "Col. Las Flores, San Miguel",
    "departamento": "San Miguel",
    "municipio": "San Miguel",
    "latitud": 13.3483056,
    "longitud": -88.4078173,
    "saldo": 120.5,
    "limite_credito": 500.0,
    "ruta_nombre": "Ruta Centro",
    "ruta_dia": "lunes",
    "foto_casa": "https://panel.../storage/clientes/78/casa.jpg",
    "referencias_familiares": [
      { "nombre": "María Pérez", "telefono": "7000-1111", "parentesco": "Esposa" }
    ],
    "referencias_conocidas": [
      { "nombre": "Pedro López", "telefono": "7000-2222", "trabajo": "Tienda La Esquina" }
    ]
  },
  "ventas": [
    {
      "id": 1,
      "numero_venta": "V-000123",
      "fecha_venta": "01/07/2026",
      "tipo_pago": "credito",
      "estado": "activa",
      "total": 500.0,
      "monto_pagado": 200.0,
      "saldo_pendiente": 300.0,
      "dias_credito": 90,
      "vendedor_nombre": "Carlos Ramírez",
      "productos": [
        { "nombre": "Refrigeradora 12p", "cantidad": 1, "precio_unitario": 500.0, "subtotal": 500.0 }
      ],
      "observaciones": null,
      "eventos": [
        {
          "tipo": "pago",
          "sort_key": 1735689600,
          "numero_recibo": "REC-5-000391",
          "fecha": "01/07/2026",
          "monto": 50.0,
          "metodo_pago": "efectivo",
          "observaciones": null,
          "anulado": false,
          "anulado_en": null,
          "anulado_por": null,
          "motivo_anulacion": null
        },
        {
          "tipo": "visita",
          "sort_key": 1735776000,
          "fecha": "02/07/2026 09:15",
          "resultado": "no_encontrado",
          "usuario": "Nombre Cobrador",
          "observaciones": null,
          "foto_hogar_url": "https://panel.../storage/visitas/456.jpg"
        }
      ],
      "cuotas_resumen": { "total": 10, "cobradas": 4, "pendientes": 6, "vencidas": 1 },
      "proxima_cuota": {
        "numero_cuota": 5,
        "total_cuotas": 10,
        "monto_cuota": 50.0,
        "monto_pagado": 0.0,
        "fecha_vencimiento": "2026-08-05"
      }
    }
  ],
  "resumen": {
    "total_ventas": 1,
    "total_comprado": 500.0,
    "total_pagado": 200.0,
    "total_pendiente": 300.0
  },
  "historial_ruta": [
    {
      "fecha": "15/06/2026 10:00",
      "usuario": "Admin",
      "ruta_anterior": "Ruta Norte",
      "ruta_nueva": "Ruta Centro"
    }
  ],
  "visitas_sin_cobro": []
}
```

### Notas de uso

- **`eventos`** ya viene mezclado (pagos + visitas) y ordenado por
  `sort_key` de más reciente a más antiguo — no hace falta combinar dos
  listas en el frontend, solo pintarlo en orden.
- **`visitas_sin_cobro`** solo trae datos si el cliente **no tiene ninguna
  venta** todavía (visitas registradas antes de la primera venta a crédito).
- Un pago puede aparecer agrupado bajo un solo `numero_recibo` aunque haya
  cubierto varias cuotas — el `monto` del evento ya es la suma total de ese
  recibo.

### Errores

| Código | Motivo                                          |
|--------|---------------------------------------------------|
| 403    | Cliente no pertenece a ninguna ruta del cobrador   |

### Uso típico en la app

1. Desde el directorio (endpoint 1) o desde cualquier otra pantalla que ya
   tenga el `id` del cliente (búsqueda global, ruta del día, etc.), el
   usuario toca un cliente.
2. Se llama a este endpoint con el `id` del cliente.
3. Se pinta la pantalla de "Perfil de cliente" (igual que la vista web
   `perfil-cliente`), con ficha de datos, línea de tiempo de eventos y
   resumen de saldos — **sin** botón de cobrar (esta pantalla es solo
   lectura; el cobro se hace desde el flujo normal de ruta del día).
