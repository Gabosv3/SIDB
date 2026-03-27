# 🚀 PLAN DE EJECUCIÓN - MÓDULO DE COMPRAS

## ⏱️ Tiempo Estimado: 15-30 minutos

---

## FASE 1: VALIDACIÓN (5 minutos)

### Paso 1.1: Verificar Instalación
```bash
cd c:\laragon\www\SIDB
php validate-compras-module.php
```

**Resultado esperado:**
```
✓ Modelo Proveedor
✓ Modelo Compra
✓ Modelo DetalleCompra
✓ Modelo PagoCompra
... (más items)
✅ VALIDACIÓN EXITOSA
```

**Si hay errores**: Revisar la sección de troubleshooting

---

## FASE 2: MIGRACIONES (10 minutos)

### Paso 2.1: Ejecutar Migraciones
```bash
php artisan migrate
```

**Resultado esperado:**
```
Migrating: [2026_03_27_000001_add_campos_to_productos_table]
Migrated:  [2026_03_27_000001_add_campos_to_productos_table] (XXms)
Migrating: [2026_03_27_000002_create_proveedores_table]
Migrated:  [2026_03_27_000002_create_proveedores_table] (XXms)
... (más migraciones)
```

### Paso 2.2: Verificar Migraciones
```bash
php artisan migrate:status
```

Todas deberían tener checkmark ✓

### Paso 2.3: Generar Permisos (OPCIONAL pero recomendado)
```bash
php artisan shield:generate --all
```

**Nota**: Si usas Filament Shield, esto crea los permisos automáticamente

---

## FASE 3: DATOS DE PRUEBA (5 minutos - OPCIONAL)

### Paso 3.1: Ejecutar Seeders
```bash
php artisan db:seed --class=ProveedorSeeder
```

**Resultado esperado:**
```
Seeded: ProveedorSeeder
```

Esto crea:
- 15 proveedores aleatorios
- 3 proveedores de ejemplo
- Asociaciones con productos

---

## FASE 4: CONFIGURACIÓN (5 minutos)

### Paso 4.1: Limpiar Cache
```bash
php artisan cache:clear
php artisan config:clear
```

### Paso 4.2: Reiniciar Servidor
```bash
# Si usas artisan serve:
php artisan serve

# O con otro servidor:
# Reinicia normalmente
```

---

## FASE 5: VERIFICACIÓN EN FILAMENT (5 minutos)

### Paso 5.1: Acceder al Panel
```
URL: http://localhost:8000/admin
(o donde esté tu Filament)
```

### Paso 5.2: Buscar Nuevos Menús
En el panel lateral, deberías ver:

```
COMPRAS (grupo nuevo)
├─ Proveedores ← NUEVO
└─ Compras ← NUEVO

INVENTARIO
├─ Productos (actualizado con pestaña de proveedores)
└─ ...
```

### Paso 5.3: Revisar Dashboard
- Debería haber 4 widgets nuevos
- Gráfico de proveedores
- Tabla de compras recientes

---

## FASE 6: PRUEBA FUNCIONAL (10 minutos)

### Prueba 6.1: Crear Proveedores

1. Click: **Compras → Proveedores → Nuevo Proveedor**
2. Completa el formulario:
   ```
   Nombre: Prueba SA
   Código: PROV-001
   Contacto: Juan Test
   Email: juan@test.com
   Teléfono: 555-0123
   Dirección: Calle Test 123
   Ciudad: Guatemala
   Departamento: Guatemala
   País: Guatemala
   ```
3. **Guardar**

✅ **Éxito si:** El proveedor aparece en la lista

### Prueba 6.2: Crear Compra

1. Click: **Compras → Compras → Nueva Compra**
2. Completa:
   ```
   Proveedor: Prueba SA
   Fecha: Hoy
   Usuario: (tu usuario)
   ```
3. Agregar artículo:
   - Producto: (selecciona uno existente)
   - Cantidad: 10
   - Precio: 100.00
   - Descuento: 5.00
4. Verificar que calcula:
   - Subtotal: 950.00 (10 × (100-5))
   - Impuesto (16%): 152.00
   - Total: 1,102.00
5. **Guardar**

✅ **Éxito si:** La compra aparece con estado "pendiente"

### Prueba 6.3: Registrar Pago

1. Abre la compra recién creada
2. Click: **Registrar Pago**
3. Completa:
   ```
   Monto: 500.00
   Forma: Transferencia
   ```
4. **Guardar**
5. Verifica que:
   - Saldo pendiente se redujo a 602.00
   - El pago aparece en la lista

✅ **Éxito si:** El saldo se actualiza automáticamente

### Prueba 6.4: Cambiar Estado

1. Abre la compra
2. Cambia estado a **"Recibida"**
3. **Guardar**
4. Verifica que:
   - El stock del producto se actualizó (+10)
   - Se creó movimiento de stock

✅ **Éxito si:** El stock cambió en Productos

---

## FASE 7: VALIDACIÓN FINAL

### Checklist Final

```
□ Validación script pasada
□ Migraciones ejecutadas
□ Permisos generados
□ Nuevos menús visibles
□ Widgets en dashboard
□ Proveedor creado
□ Compra creada
□ Pago registrado
□ Stock actualizado
□ Sin errores en logs
```

### Revisar Logs (si hay dudas)
```bash
tail -f storage/logs/laravel.log
```

---

## ✅ COMPLETADO

Si llegaste aquí, todo está funcionando correctamente.

---

## 🎯 PRÓXIMOS USOS

### Crear Compra Real
```
1. Compras → Proveedores
2. Crear con datos reales
3. Compras → Compras
4. Crear compra del proveedor
5. Registrar artículos
6. Guardar
7. Al recibir: cambiar a "Recibida"
8. Stock automático
```

### Usar en Código
```php
use App\Services\CompraService;

$service = app(CompraService::class);

// Crear compra
$compra = $service->crearCompra($data, $detalles);

// Pagar
$service->registrarPago($compra, 1000);

// Reportes
$reporte = $service->generarReporte();
```

### Generar Reportes
```php
$vencidas = $service->obtenerComprasVencidas();
$deuda = $service->obtenerDeudaTotalPendiente();
$resumen = $service->obtenerResumenPorProveedor();
```

---

## 🆘 TROUBLESHOOTING RÁPIDO

### "No veo los nuevos menús"
```bash
php artisan cache:clear
# Reload browser (Ctrl+F5)
```

### "Error en migraciones"
```bash
php artisan migrate:status
# Verificar DB en .env
```

### "Los cálculos no funcionan"
```bash
# Revisar logs
tail -f storage/logs/laravel.log
# Verificar AppServiceProvider tiene Observers
```

### "Permisos denegados"
```bash
php artisan shield:generate --all
# Asignar roles en admin
```

---

## 📞 DOCUMENTACIÓN COMPLETA

Para información detallada, revisar:

- **RESUMEN_EJECUTIVO.md** - Overview del proyecto
- **COMPRAS_MODULO.md** - Manual técnico completo
- **SETUP_COMPRAS.md** - Guía de instalación
- **DIAGRAMA_RELACIONES.md** - Arquitectura de datos

---

## 🎊 ¡LISTO PARA USAR!

El módulo está completamente funcional y listo para producción.

Tiempo invertido: 15-30 minutos  
Resultado: Sistema profesional de compras operativo

---

**Última actualización**: Marzo 27, 2026  
**Estado**: ✅ Completado  
**Siguiente paso**: Crear primer proveedor real
