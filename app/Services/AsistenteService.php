<?php

namespace App\Services;

use App\Models\Sucursal;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Throwable;

/**
 * Catálogo del asistente de navegación: pantallas a las que el usuario actual
 * tiene acceso (respeta permisos de Filament), con palabras clave y sinónimos,
 * más respuestas rápidas a preguntas frecuentes. El emparejamiento de la
 * frase del usuario se hace en el navegador (public/js/asistente.js).
 */
class AsistenteService
{
    /** slug del recurso/página => sinónimos extra (en minúsculas, sin acentos). */
    private const SINONIMOS = [
        'users' => ['usuario', 'cuenta', 'acceso', 'contrasena', 'login'],
        'clientes' => ['cliente', 'comprador'],
        'ventas' => ['venta', 'vender', 'factura', 'ticket'],
        'productos' => ['producto', 'articulo', 'inventario', 'precio', 'stock', 'mercaderia'],
        'categorias' => ['categoria', 'tipo producto'],
        'compras' => ['compra', 'comprar', 'pedido proveedor'],
        'proveedors' => ['proveedor', 'proveedores', 'suplidor'],
        'vendedors' => ['vendedor', 'vendedores'],
        'cobradors' => ['cobrador', 'cobradores'],
        'supervisors' => ['supervisor', 'supervisores'],
        'sucursals' => ['sucursal', 'sede', 'tienda'],
        'vehiculos' => ['vehiculo', 'moto', 'carro', 'camion'],
        'vales' => ['gasto', 'vale', 'gastos', 'consumo'],
        'ruta-cobros' => ['ruta', 'rutas', 'ruta de cobro'],
        'comision-tramos' => ['comision', 'tramo', 'porcentaje'],
        'asignaciones-diarias' => ['asignacion', 'jornada', 'mercaderia vendedor', 'salida'],
        'reintegros' => ['reintegro', 'recuperar', 'cuenta atrasada'],
        'garantias' => ['garantia', 'reclamo', 'producto danado'],
        'preventas' => ['preventa', 'pedido'],
        'encuesta-clientes' => ['encuesta'],
        'gestion-cobros' => ['cuota', 'cuotas', 'cobro', 'cobros'],
        'resumen-ventas-dia' => ['vendido hoy', 'ventas de hoy', 'resumen ventas', 'ventas del dia'],
        'resumen-cobros-dia' => ['cobrado hoy', 'cobros de hoy', 'cierre de caja', 'cuadrar efectivo'],
        'liquidacion-semanal' => ['liquidar cobrador', 'pagar cobrador', 'comision cobrador', 'anticipo cobrador'],
        'liquidacion-semanal-ventas' => ['liquidar vendedor', 'pagar vendedor', 'comision vendedor', 'anticipo vendedor', 'remanente'],
        'clientes-inactivos' => ['inactivo', 'sin visita', 'sin abono', 'morosos'],
        'asignar-rutas-clientes' => ['asignar ruta', 'cliente sin ruta'],
        'reportes-ventas' => ['reporte ventas', 'analisis ventas'],
        'reportes-cobros' => ['reporte cobros', 'efectividad'],
        'reportes-cartera' => ['cartera', 'mora', 'morosidad', 'por cobrar'],
        'reportes-inventario' => ['reporte inventario', 'stock bajo', 'valor inventario'],
        'reportes-compras' => ['reporte compras'],
        'reportes-comisiones' => ['nomina', 'planilla', 'salarios', 'pago empleados'],
        'asistencia-empleados' => ['asistencia', 'marcaje', 'reloj', 'tardanza', 'horas'],
        'personalizacion-sistema' => ['personalizar', 'logo', 'colores', 'datos empresa', 'configuracion'],
        'backups' => ['respaldo', 'copia de seguridad', 'backup', 'respaldar'],
        'audit-logs' => ['auditoria', 'bitacora', 'historial cambios', 'quien modifico'],
        'historial-pagos-eliminados' => ['pagos eliminados', 'pago borrado'],
        'supervisiones' => ['supervision', 'evaluacion cobrador'],
    ];

    public static function catalogo(): array
    {
        try {
            return self::construir();
        } catch (Throwable $e) {
            report($e);

            return ['items' => [], 'faq' => self::faq([])];
        }
    }

    private static function construir(): array
    {
        $panel = Filament::getPanel('administrativo');
        $tenantId = Filament::getTenant()?->id ?? request()->route('tenant');
        $tenant = $tenantId ? Sucursal::find($tenantId) : null;
        $tenant ??= auth()->user()?->sucursales()->first() ?? Sucursal::first();

        $items = [];
        $slugs = [];

        foreach ($panel->getResources() as $recurso) {
            if (! $recurso::canViewAny()) {
                continue;
            }

            $slug = $recurso::getSlug();
            $titulo = $recurso::getPluralModelLabel();
            $slugs[$slug] = true;

            $items[] = self::item('Ver ' . $titulo, $recurso::getUrl('index', [], false, 'administrativo', $tenant), 'ver', $slug, $titulo);

            if (array_key_exists('create', $recurso::getPages()) && $recurso::canCreate()) {
                $items[] = self::item('Crear ' . $recurso::getModelLabel(), $recurso::getUrl('create', [], false, 'administrativo', $tenant), 'crear', $slug, $titulo);
            }
        }

        foreach ($panel->getPages() as $pagina) {
            if (! method_exists($pagina, 'canAccess') || ! $pagina::canAccess()) {
                continue;
            }

            $slug = $pagina::getSlug();
            if (isset($slugs[$slug]) || $slug === 'dashboard') {
                continue;
            }

            $items[] = self::item($pagina::getNavigationLabel(), $pagina::getUrl([], false, 'administrativo', $tenant), 'pagina', $slug, $pagina::getNavigationLabel());
        }

        $id = $tenant?->id ?? 1;
        $user = auth()->user();

        if ($user?->can('View:ClientesRuta')) {
            $items[] = self::item('Clientes por Ruta', "/clientes-ruta/{$id}", 'pagina', 'clientes-ruta', 'Clientes por Ruta', ['ruta', 'orden de visita', 'recordatorio', 'whatsapp', 'fusionar', 'perfil cliente']);
        }

        $items[] = self::item('Monitor POS', "/pos/{$id}/monitor", 'pagina', 'pos-monitor', 'Monitor POS', ['pos', 'dispositivos', 'cobradores en linea']);

        return ['items' => $items, 'faq' => self::faq($items)];
    }

    private static function item(string $titulo, string $url, string $tipo, string $slug, string $base, array $extra = []): array
    {
        $palabras = array_merge(
            [self::norm($base)],
            self::SINONIMOS[$slug] ?? [],
            array_map([self::class, 'norm'], $extra),
        );

        return ['t' => $titulo, 'u' => $url, 'a' => $tipo, 'k' => array_values(array_unique($palabras))];
    }

    private static function norm(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }

    /** Respuestas rápidas a preguntas frecuentes de "¿cómo hago...?". */
    private static function faq(array $items): array
    {
        $url = fn (string $titulo) => collect($items)->firstWhere('t', $titulo)['u'] ?? null;

        return [
            ['k' => ['corregir precio', 'cambiar precio', 'precio mal puesto', 'quitar descuento', 'descuento mal puesto', 'editar precio', 'corrijo precio', 'corrijo el precio', 'modificar precio', 'modifico el precio', 'arreglar precio', 'cambio de precio', 'cambiar el precio', 'cambio el precio', 'quito el descuento', 'quitar el descuento'],
             'r' => 'Abre Clientes por Ruta, entra al perfil del cliente (ícono del ojo) y pulsa el botón 💲 en la venta. Ahí cambias el precio de cada producto y el descuento. Solo lo ve el super administrador.',
             'u' => $url('Clientes por Ruta'), 'l' => 'Ir a Clientes por Ruta'],
            ['k' => ['corregir venta', 'cambiar cantidad venta', 'quitar producto venta', 'corregir prima', 'corrijo la venta', 'corrijo una venta', 'arreglar venta', 'modificar venta'],
             'r' => 'En Resumen de Ventas del Día pulsa Corregir en la venta: puedes cambiar cantidades, quitar un producto o ajustar la prima. Pide el motivo.',
             'u' => $url('Resumen de Ventas del Día') ?? $url('Ventas del Día'), 'l' => 'Ir a Ventas del Día'],
            ['k' => ['enviar recordatorio', 'recordatorio pago', 'recordar pago', 'avisar cliente saldo', 'whatsapp cliente'],
             'r' => 'En Clientes por Ruta elige la ruta y pulsa "Enviar recordatorios de pago". Cada botón Enviar abre WhatsApp Web con el mensaje listo.',
             'u' => $url('Clientes por Ruta'), 'l' => 'Ir a Clientes por Ruta'],
            ['k' => ['imprimir clientes nuevos', 'clientes nuevos', 'listado clientes nuevos'],
             'r' => 'En Resumen de Ventas del Día usa el filtro "Clientes nuevos", marca los clientes y pulsa "Imprimir seleccionados".',
             'u' => $url('Resumen de Ventas del Día') ?? $url('Ventas del Día'), 'l' => 'Ir a Ventas del Día'],
            ['k' => ['cierre del dia', 'cerrar dia', 'cuadrar caja', 'cuadrar efectivo'],
             'r' => 'Revisa Ventas del Día y Cobros del Día, confirma las primas, liquida las asignaciones diarias (las abiertas se cierran solas a las 11 pm) y atiende gastos, reintegros y garantías pendientes.',
             'u' => $url('Resumen de Cobros del Día') ?? $url('Cobros del Día'), 'l' => 'Ir a Cobros del Día'],
            ['k' => ['eliminar pago', 'borrar pago', 'anular pago', 'anular recibo'],
             'r' => 'Un pago se elimina desde Resumen de Cobros del Día (solo super administrador, pide tu contraseña y un motivo). Un recibo se anula desde el perfil del cliente. Los dos dejan constancia.',
             'u' => $url('Resumen de Cobros del Día') ?? $url('Cobros del Día'), 'l' => 'Ir a Cobros del Día'],
            ['k' => ['cliente sin ruta', 'asignar ruta cliente', 'poner ruta cliente'],
             'r' => 'Usa Asignar Rutas: lista a los clientes sin ruta, y puedes asignar una a uno o a varios seleccionados.',
             'u' => $url('Asignar Rutas'), 'l' => 'Ir a Asignar Rutas'],
            ['k' => ['respaldo', 'backup', 'copia de seguridad', 'hacer respaldo'],
             'r' => 'En Respaldos pulsa "Crear Backup Ahora" y descarga el archivo. Guárdalo fuera del servidor.',
             'u' => $url('Gestión de Backups') ?? $url('Backups'), 'l' => 'Ir a Respaldos'],
            ['k' => ['no puedo eliminar', 'no deja borrar', 'no se puede eliminar'],
             'r' => 'Los registros con historial (ventas, pagos, compras, rutas con clientes) no se eliminan: se desactivan o se cancelan para no perder información.',
             'u' => null, 'l' => null],
            ['k' => ['tutorial', 'ayuda pantalla', 'como se usa esta pantalla', 'recorrido'],
             'r' => 'Pulsa el botón rojo "?" de abajo a la derecha para ver el recorrido guiado de esta pantalla.',
             'u' => null, 'l' => null],
        ];
    }
}
