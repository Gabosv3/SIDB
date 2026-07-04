# API POS - SIDB Documentación Completa

**Base URL:** `http://localhost/SIDB/public/api`  
**Autenticación:** Laravel Sanctum (Bearer Token)  
**UI interactiva:** `http://localhost/SIDB/public/api/documentation`

---

## 📋 Índice de Endpoints

### 🔐 Autenticación
- `POST /api/login` - Iniciar sesión
- `GET /api/me` - Obtener usuario actual
- `POST /api/logout` - Cerrar sesión

### 📱 Aplicación Móvil
- `GET /api/version` - Última versión del APK disponible

### 👥 Clientes
- `GET /api/clientes` - Buscar clientes activos
- `POST /api/clientes` - Crear cliente nuevo
- `GET /api/clientes/{id}` - Obtener detalle de cliente
- `PATCH /api/clientes/{id}/ubicacion` - Actualizar GPS
- `PATCH /api/clientes/{id}/telefonos` - Actualizar teléfonos
- `PATCH /api/clientes/{id}/nombre` - **[NUEVO]** Actualizar nombre/apellido

### 📦 Catálogos
- `GET /api/categorias` - Listar categorías
- `GET /api/productos` - Buscar productos
- `GET /api/productos/{id}` - Detalle de producto
- `GET /api/productos/{id}/metodos-pago` - Métodos de pago disponibles

### 💰 Ventas
- `GET /api/ventas` - Listar ventas del usuario
- `GET /api/ventas/{id}` - Detalle de venta
- `POST /api/ventas` - Crear venta (solo vendedores)
- `GET /api/asignacion/hoy` - Asignación diaria
- `POST /api/asignaciones/{id}/liquidar` - Liquidar asignación

### 📊 Cobros
- `GET /api/cobros/ruta-hoy` - Rutas del día
- `GET /api/cobros/rutas/{ruta_id}/clientes` - Clientes de una ruta
- `GET /api/cobros/rutas/{ruta_id}/orden` - Orden guardado de clientes
- `POST /api/cobros/rutas/{ruta_id}/orden` - **[NUEVO]** Guardar orden de ruta
- `GET /api/cobros/clientes/{id}` - Detalle de cliente
- `GET /api/cobros/clientes/{id}/gestiones-pendientes` - Gestiones pendientes
- `POST /api/cobros/clientes/{id}/pagar` - Registrar pago
- `POST /api/cobros/clientes/{id}/visita` - Registrar visita
- `GET /api/cobros/resumen-dia` - Resumen diario

### 🔄 Reintegros
- `GET /api/reintegros/candidatos` - Productos candidatos a reintegro
- `GET /api/reintegros/vendedores` - Vendedores con devoluciones
- `GET /api/reintegros` - Listar reintegros
- `POST /api/reintegros` - Crear reintegro
- `PATCH /api/reintegros/{id}/estado` - Cambiar estado

---

## 🔐 Autenticación

### POST `/api/login`

Obtener token de acceso.

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@sidb.local",
  "password": "tu-password"
}
```

**Response 200:**
```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@sidb.local",
    "roles": ["super_admin"]
  }
}
```

---

### GET `/api/me`

Obtener información del usuario autenticado.

```http
GET /api/me
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "id": 1,
  "name": "Admin",
  "email": "admin@sidb.local",
  "roles": ["cajero"],
  "permisos": ["crear_ventas", "ver_productos"],
  "sucursales": [
    { "id": 1, "nombre": "Sucursal Central" }
  ]
}
```

---

### POST `/api/logout`

Cerrar sesión.

```http
POST /api/logout
Authorization: Bearer {token}
```

**Response 200:**
```json
{ "message": "Sesión cerrada correctamente." }
```

---

## 📱 Aplicación Móvil

### GET `/api/version`

Obtener última versión publicada del APK (sin autenticación).

```http
GET /api/version
```

**Response 200:**
```json
{
  "version": "1.0.1",
  "url": "https://panel.midominio.com/update/sidb.apk",
  "notas": "Correcciones de errores y nuevo sistema de orden de clientes"
}
```

---

## 👥 Clientes

### GET `/api/clientes` — Buscar clientes

```http
GET /api/clientes?q=7338
Authorization: Bearer {token}
```

**Query Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `q` | string | Busca en: nombre, apellido, DUI, código anterior, teléfono |
| `sucursal_id` | integer | Filtrar por sucursal |
| `per_page` | integer | Resultados por página (default: 50) |
| `page` | integer | Número de página (default: 1) |

**Response 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 78,
      "nombre": "Maria Del Carmen",
      "apellido": "Hernandez",
      "dui": "02741470-9",
      "codigo_anterior": "7338",
      "telefono_normal": "77856915",
      "telefono_whatsapp": "77856915",
      "email": null,
      "direccion": "Calle Principal",
      "limite_credito": "500.00",
      "saldo": "148.00",
      "activo": true,
      "sucursal_id": 1
    }
  ],
  "last_page": 1,
  "per_page": 50,
  "total": 1
}
```

---

### GET `/api/clientes/{id}` — Detalle de cliente

```http
GET /api/clientes/78
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "id": 78,
  "nombre": "Maria Del Carmen",
  "apellido": "Hernandez",
  "dui": "02741470-9",
  "nit": null,
  "telefono_normal": "77856915",
  "telefono_whatsapp": "77856915",
  "email": null,
  "direccion": "Calle Principal",
  "departamento": "Morazán",
  "municipio": "Gotera",
  "distrito": null,
  "limite_credito": "500.00",
  "saldo": "148.00",
  "activo": true,
  "sucursal_id": 1,
  "ruta_cobro_id": 3
}
```

---

### POST `/api/clientes` — Crear cliente

```http
POST /api/clientes
Authorization: Bearer {token}
Content-Type: multipart/form-data

nombre=Maria
apellido=Hernandez
dui=02741470-9
codigo_anterior=7338
telefono_normal=77856915
telefono_whatsapp=77856915
email=maria@email.com
direccion=Calle Principal
departamento=Morazán
municipio=Gotera
latitud=13.945
longitud=-87.87
ruta_cobro_id=3
foto_casa=<archivo>
```

**Response 201:**
```json
{
  "mensaje": "Cliente creado exitosamente.",
  "cliente": {
    "id": 170,
    "nombre": "Maria Hernandez",
    "dui": "02741470-9",
    "codigo_anterior": "7338",
    "telefono": "77856915",
    "foto_casa": "https://tudominio.com/storage/casas/foto.jpg"
  }
}
```

---

### PATCH `/api/clientes/{id}/ubicacion` — Actualizar GPS

```http
PATCH /api/clientes/78/ubicacion
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitud": 13.9450,
  "longitud": -87.8700
}
```

**Response 200:**
```json
{
  "mensaje": "Ubicación actualizada.",
  "cliente": {
    "id": 78,
    "nombre": "Maria Del Carmen Hernandez",
    "latitud": 13.945,
    "longitud": -87.87
  }
}
```

---

### PATCH `/api/clientes/{id}/telefonos` — Actualizar teléfonos

```http
PATCH /api/clientes/78/telefonos
Authorization: Bearer {token}
Content-Type: application/json

{
  "telefono_normal": "75123456",
  "telefono_whatsapp": "75123456"
}
```

**Response 200:**
```json
{
  "mensaje": "Teléfonos actualizados.",
  "cliente": {
    "id": 78,
    "nombre": "Maria Del Carmen Hernandez",
    "telefono_normal": "75123456",
    "telefono_whatsapp": "75123456"
  }
}
```

---

### PATCH `/api/clientes/{id}/nombre` — **[NUEVO]** Actualizar nombre/apellido

Actualiza el nombre y/o apellido de un cliente.

```http
PATCH /api/clientes/78/nombre
Authorization: Bearer {token}
Content-Type: application/json

{
  "nombre": "Maria",
  "apellido": "Hernandez Pérez"
}
```

**Parameters:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `nombre` | string | ❌ | Nuevo nombre (máx 100 caracteres) |
| `apellido` | string | ❌ | Nuevo apellido (máx 100 caracteres) |

> Al menos uno de los dos debe enviarse.

**Response 200:**
```json
{
  "mensaje": "Nombre actualizado.",
  "cliente": {
    "id": 78,
    "nombre": "Maria",
    "apellido": "Hernandez Pérez",
    "nombre_completo": "Maria Hernandez Pérez"
  }
}
```

**Response 422:**
```json
{
  "message": "Debe proporcionar al menos nombre o apellido."
}
```

---

## 📦 Catálogos

### GET `/api/categorias`

```http
GET /api/categorias
GET /api/categorias?sucursal_id=1
Authorization: Bearer {token}
```

**Response 200:**
```json
[
  { "id": 1, "nombre": "Electrónica", "descripcion": null, "icono": null },
  { "id": 2, "nombre": "Ropa", "descripcion": null, "icono": null }
]
```

---

### GET `/api/productos`

```http
GET /api/productos
GET /api/productos?q=televisor
GET /api/productos?categoria_id=1&sucursal_id=1
Authorization: Bearer {token}
```

**Query Parameters:**
| Parámetro | Descripción |
|-----------|-------------|
| `q` | Busca en nombre y código |
| `categoria_id` | Filtrar por categoría |
| `sucursal_id` | Filtrar por sucursal |
| `per_page` | Resultados por página (default: 50) |
| `page` | Número de página |

**Response 200:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 5,
      "nombre": "Televisor 40\"",
      "codigo": "TV-40-SAMSUNG",
      "precio_venta": "299.99",
      "precios_cuotas": { "12": 27.50, "24": 14.99 },
      "stock": 15,
      "categoria_id": 1
    }
  ],
  "total": 55,
  "per_page": 20,
  "last_page": 3
}
```

---

## 💰 Ventas

### GET `/api/ventas`

```http
GET /api/ventas
GET /api/ventas?sucursal_id=1&estado=completada
Authorization: Bearer {token}
```

**Query Parameters:**
| Parámetro | Descripción |
|-----------|-------------|
| `sucursal_id` | Filtrar por sucursal |
| `estado` | `completada`, `pendiente`, `anulada` |
| `per_page` | Resultados por página (default: 20) |
| `page` | Número de página |

---

### GET `/api/ventas/{id}`

```http
GET /api/ventas/123
Authorization: Bearer {token}
```

---

### POST `/api/ventas` — Crear venta (solo vendedores)

```http
POST /api/ventas
Authorization: Bearer {token}
Content-Type: application/json

{
  "cliente_id": 78,
  "sucursal_id": 1,
  "fecha_venta": "2026-07-04",
  "detalles": [
    {
      "producto_id": 5,
      "cantidad": 1,
      "precio_unitario": 299.99
    }
  ]
}
```

---

## 📊 Cobros

### GET `/api/cobros/ruta-hoy`

Obtener rutas y clientes del día.

```http
GET /api/cobros/ruta-hoy
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "dia": "jueves",
  "rutas": [
    {
      "id": 3,
      "nombre": "Zona 2 Gotera",
      "dia_semana": "jueves",
      "total_clientes": 86,
      "clientes": [
        {
          "id": 97,
          "nombre": "Florinda Perez",
          "orden": 1,
          "dui": "00040202-4",
          "codigo_anterior": "7274",
          "telefono": "26543086",
          "saldo_total": 249.0,
          "ventas": [
            {
              "id": 20,
              "numero_venta": "VNT-SLEOVUSF",
              "fecha_venta": "13/11/2025",
              "total": 160.0,
              "monto_pagado": 35.0,
              "saldo_pendiente": 125.0
            }
          ]
        }
      ]
    }
  ]
}
```

---

### GET `/api/cobros/rutas/{ruta_id}/clientes`

```http
GET /api/cobros/rutas/3/clientes
Authorization: Bearer {token}
```

---

### GET `/api/cobros/rutas/{ruta_id}/orden` — **[NUEVO]** Obtener orden guardado

Devuelve los IDs de clientes en el orden que guardó el cobrador la última vez.

```http
GET /api/cobros/rutas/3/orden
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "ids": [97, 45, 78, 102, 234]
}
```

---

### POST `/api/cobros/rutas/{ruta_id}/orden` — **[NUEVO]** Guardar orden de ruta

El cobrador reordena su ruta en la app y ese orden se guarda en el servidor.

```http
POST /api/cobros/rutas/3/orden
Authorization: Bearer {token}
Content-Type: application/json

{
  "ids": [97, 102, 78, 45, 234]
}
```

**Response 200:**
```json
{
  "mensaje": "Orden guardado."
}
```

**Response 422:**
```json
{
  "mensaje": "Alguno de los clientes no pertenece a esta ruta."
}
```

---

### POST `/api/cobros/clientes/{id}/pagar`

Registrar un pago directo a cliente.

```http
POST /api/cobros/clientes/78/pagar
Authorization: Bearer {token}
Content-Type: application/json

{
  "monto": 100.00,
  "referencia": "PG-123456",
  "metodo_pago": "efectivo"
}
```

---

### POST `/api/cobros/clientes/{id}/visita`

Registrar una visita sin pago (opcional con foto).

```http
POST /api/cobros/clientes/78/visita
Authorization: Bearer {token}
Content-Type: multipart/form-data

observaciones=Cliente no estaba en casa
foto_visita=<archivo>
```

---

## 🔄 Reintegros

### GET `/api/reintegros`

```http
GET /api/reintegros
Authorization: Bearer {token}
```

---

### POST `/api/reintegros`

```http
POST /api/reintegros
Authorization: Bearer {token}
Content-Type: application/json

{
  "venta_id": 123,
  "cantidad": 1,
  "motivo": "Producto dañado"
}
```

---

## ⚠️ Códigos de Error

| Código | Descripción |
|--------|-------------|
| **200** | OK |
| **201** | Creado exitosamente |
| **400** | Solicitud inválida (parámetros faltantes) |
| **401** | No autenticado (token faltante o inválido) |
| **403** | Acceso denegado (permisos insuficientes) |
| **404** | Recurso no encontrado |
| **422** | Error de validación |
| **500** | Error del servidor |

---

## 📝 Notas Importantes

1. **Token**: Incluir siempre en header: `Authorization: Bearer {token}`
2. **Base URL**: En emulador Android usar `http://10.0.2.2/SIDB/public/api`
3. **Content-Type**: 
   - JSON: `application/json`
   - Multipart: `multipart/form-data`
4. **Paginación**: Por defecto retorna 20-50 items, usar `per_page` y `page` para controlar
5. **Búsqueda**: El parámetro `q` busca en múltiples campos automáticamente

---

## 🧪 Ejemplos de Uso

### Flujo completo de cobrador

```
1. Login
   POST /api/login
   
2. Ver rutas del día
   GET /api/cobros/ruta-hoy
   
3. Guardar nuevo orden de clientes si reordenó
   POST /api/cobros/rutas/3/orden
   
4. Para cada cliente:
   - GET /api/cobros/clientes/{id}
   - POST /api/cobros/clientes/{id}/pagar
   
5. Cerrar sesión
   POST /api/logout
```

### Flujo completo de vendedor

```
1. Login
   POST /api/login
   
2. Buscar cliente
   GET /api/clientes?q=7338
   
3. Ver asignación del día
   GET /api/asignacion/hoy
   
4. Crear venta
   POST /api/ventas
   
5. Liquidar asignación
   POST /api/asignaciones/{id}/liquidar
```

---

## 📚 Documentación Adicional

- **Swagger UI:** `http://localhost/SIDB/public/api/documentation`
- **Archivo:** [docs/API_CLIENTES.md](API_CLIENTES.md)
- **Archivo:** [docs/API_COBROS.md](API_COBROS.md)
- **Archivo:** [docs/API_VENTAS.md](API_VENTAS.md)
- **Archivo:** [docs/API_REINTEGROS.md](API_REINTEGROS.md)

