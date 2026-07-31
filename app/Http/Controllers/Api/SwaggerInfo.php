<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * Definición central de la especificación OpenAPI para la API POS del SIDB.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'SIDB — API POS',
    description: 'API RESTful para el punto de venta (POS) del Sistema Integrado de Distribución y Bodega.<br><br>**Cómo autenticarse:**<br>1. Ejecuta **POST /login** con tu email y password.<br>2. Copia el `token` de la respuesta.<br>3. Haz clic en el botón **Authorize 🔒** (arriba a la derecha).<br>4. Escribe: `Bearer {tu-token}` y confirma.',
    contact: new OA\Contact(name: 'Soporte SIDB', email: 'soporte@sidb.local'),
)]
#[OA\Server(
    url: 'https://panel.distribuidorabriancescomenjivar.com/api',
    description: 'Producción',
)]
#[OA\Server(
    url: 'http://localhost/SIDB/public/api',
    description: 'Laragon local',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Pega aquí el token obtenido en POST /login (sin la palabra Bearer)',
)]

// ── Schemas reutilizables ────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'Paginacion',
    description: 'Envoltura de paginación estándar de Laravel',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 50),
        new OA\Property(property: 'total', type: 'integer', example: 230),
        new OA\Property(property: 'from', type: 'integer', example: 1),
        new OA\Property(property: 'to', type: 'integer', example: 50),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Producto',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Televisor 40"'),
        new OA\Property(property: 'codigo', type: 'string', example: 'TV-40-SAMSUNG'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true),
        new OA\Property(property: 'unidad_medida', type: 'string', example: 'unidad'),
        new OA\Property(property: 'precio_venta', type: 'number', format: 'float', example: 299.99),
        new OA\Property(property: 'precios_cuotas', type: 'object', nullable: true, description: 'Objeto JSON con planes de cuotas'),
        new OA\Property(property: 'stock', type: 'integer', example: 15),
        new OA\Property(property: 'categoria_id', type: 'integer', nullable: true),
        new OA\Property(property: 'imagen', type: 'string', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Cliente',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Pérez'),
        new OA\Property(property: 'dui', type: 'string', example: '01234567-8', nullable: true),
        new OA\Property(property: 'telefono_normal', type: 'string', example: '2200-0000', nullable: true),
        new OA\Property(property: 'telefono_whatsapp', type: 'string', example: '7000-0000', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'limite_credito', type: 'number', format: 'float', example: 500.00),
        new OA\Property(property: 'saldo', type: 'number', format: 'float', example: 150.00),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'DetalleVenta',
    properties: [
        new OA\Property(property: 'producto_id', type: 'integer', example: 5),
        new OA\Property(property: 'cantidad', type: 'integer', example: 2),
        new OA\Property(property: 'precio_unitario', type: 'number', format: 'float', example: 299.99),
        new OA\Property(property: 'descuento_porcentaje', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'cuotas', type: 'integer', nullable: true, example: 6),
        new OA\Property(property: 'precio_cuota', type: 'number', format: 'float', nullable: true, example: 99.99),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 599.98),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Venta',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'numero_venta', type: 'string', example: 'VNT-A1B2C3D4'),
        new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
        new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
        new OA\Property(property: 'tipo_pago', type: 'string', enum: ['contado', 'credito'], example: 'contado'),
        new OA\Property(property: 'estado', type: 'string', enum: ['completada', 'pendiente', 'anulada']),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 599.98),
        new OA\Property(property: 'descuento_porcentaje', type: 'number', format: 'float', example: 5),
        new OA\Property(property: 'descuento_monto', type: 'number', format: 'float', example: 30.00),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 569.98),
        new OA\Property(property: 'monto_pagado', type: 'number', format: 'float', example: 569.98),
        new OA\Property(property: 'saldo_pendiente', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'fecha_pago_limite', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
        new OA\Property(property: 'fecha_venta', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'PagoVenta',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'venta_id', type: 'integer', example: 1),
        new OA\Property(property: 'monto', type: 'number', format: 'float', example: 100.00),
        new OA\Property(property: 'fecha_pago', type: 'string', format: 'date', example: '2026-06-01'),
        new OA\Property(property: 'metodo_pago', type: 'string', enum: ['efectivo', 'transferencia', 'tarjeta', 'cheque']),
        new OA\Property(property: 'referencia', type: 'string', nullable: true),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Credenciales incorrectas.'),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Errores422',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
    type: 'object',
)]
class SwaggerInfo {}
