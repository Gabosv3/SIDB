<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * Vistas del panel que deben exigir su permiso de Shield. Un usuario sin permisos
 * no puede abrirlas ni verlas en el menu; con su permiso si; super_admin siempre.
 * (Dashboard, AccountWidget y FilamentInfoWidget quedan abiertos a proposito.)
 */
$paginas = [
    'AsignarRutasClientes', 'AsistenciaEmpleados', 'Backups', 'ClientesInactivos', 'CuadreCaja', 'FrasesAsistente',
    'HistorialPagosEliminados', 'LiquidacionSemanal', 'LiquidacionSemanalVentas', 'PalabrasAsistente',
    'PersonalizacionSistema', 'ReportesCartera', 'ReportesCobros', 'ReportesComisiones', 'ReportesCompras',
    'ReportesInventario', 'ReportesVentas', 'ResumenCobrosDia', 'ResumenEncuestasCliente', 'ResumenGarantias',
    'ResumenMensual', 'ResumenReintegros', 'ResumenVentasDia', 'Supervisiones',
];

$widgets = [
    'KpisModernosWidget', 'VentasMensualesChart', 'ComprasOverviewWidget', 'ComprasPorProveedorChart',
    'VentasRecientesWidget', 'ProductosBajoStockWidget', 'CuentasPorCobrarWidget', 'ComprasRecientesWidget',
];

beforeEach(function () {
    Filament::setCurrentPanel('administrativo');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('super_admin', 'web');
    $this->usuarioSin = User::factory()->create();
    $this->usuarioCon = User::factory()->create();
});

foreach ($paginas as $nombre) {
    $clase = "App\\Filament\\Pages\\{$nombre}";

    test("la pagina {$nombre} exige su permiso", function () use ($clase, $nombre) {
        $permiso = Permission::findOrCreate("View:{$nombre}", 'web');
        $this->usuarioCon->givePermissionTo($permiso);
        // Shield asigna cada permiso nuevo al rol super_admin al correr shield:generate.
        Role::findByName('super_admin', 'web')->givePermissionTo($permiso);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($this->usuarioSin);
        expect($clase::canAccess())->toBeFalse();

        $this->actingAs($this->usuarioCon);
        expect($clase::canAccess())->toBeTrue();

        $this->actingAs($superAdmin);
        expect($clase::canAccess())->toBeTrue();
    });
}

foreach ($widgets as $nombre) {
    $clase = "App\\Filament\\Widgets\\{$nombre}";

    test("el widget {$nombre} exige su permiso", function () use ($clase, $nombre) {
        $permiso = Permission::findOrCreate("View:{$nombre}", 'web');
        $this->usuarioCon->givePermissionTo($permiso);

        $this->actingAs($this->usuarioSin);
        expect($clase::canView())->toBeFalse();

        $this->actingAs($this->usuarioCon);
        expect($clase::canView())->toBeTrue();
    });
}

test('el recurso de comisiones exige su permiso (tiene politica)', function () {
    $this->usuarioCon->givePermissionTo(Permission::findOrCreate('ViewAny:ComisionTramo', 'web'));

    $this->actingAs($this->usuarioSin);
    expect(App\Filament\Resources\ComisionTramoResource::canViewAny())->toBeFalse();

    $this->actingAs($this->usuarioCon);
    expect(App\Filament\Resources\ComisionTramoResource::canViewAny())->toBeTrue();
});

test('los PDF de reportes sueltos no se abren sin permiso', function () {
    $this->actingAs($this->usuarioSin);

    $this->get('/reportes/resumen-mensual/1')->assertForbidden();
    $this->get('/reportes/clientes-inactivos/1')->assertForbidden();
    $this->get('/reportes/clientes-seleccionados/1')->assertForbidden();
    $this->get('/productos/1/conteo-inventario')->assertForbidden();
});

test('el Dashboard y los widgets de bienvenida siguen abiertos para cualquier usuario', function () {
    $this->actingAs($this->usuarioSin);

    expect(\Filament\Pages\Dashboard::canAccess())->toBeTrue()
        ->and(\Filament\Widgets\AccountWidget::canView())->toBeTrue();
});

test('Ventas del Dia: corregir, cancelar y confirmar prima exigen permiso de gestion', function () {
    $this->actingAs($this->usuarioSin);
    expect((new App\Filament\Pages\ResumenVentasDia)->puedeGestionar())->toBeFalse();

    $this->usuarioCon->givePermissionTo(Permission::findOrCreate('Gestionar:ResumenVentasDia', 'web'));
    $this->actingAs($this->usuarioCon);
    expect((new App\Filament\Pages\ResumenVentasDia)->puedeGestionar())->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
    expect((new App\Filament\Pages\ResumenVentasDia)->puedeGestionar())->toBeTrue();
});

test('el total vendido no cuenta ventas canceladas ni devueltas', function () {
    $mk = fn ($estado, $total) => (object) ['venta' => (object) ['estado' => $estado, 'total' => $total, 'cliente_id' => 1], 'es_cliente_nuevo' => false];
    $t = App\Services\ResumenVentasDiaService::totales(collect([$mk('completada', 100), $mk('devuelta', 50), $mk('cancelada', 70)]));

    expect($t['total_vendido'])->toBe(100.0)->and($t['total_ventas'])->toBe(1)->and($t['ventas_canceladas'])->toBe(2);
});

test('los tramos de comision se leen una sola vez y se refrescan al editarlos', function () {
    App\Models\ComisionTramo::query()->delete();
    App\Models\ComisionTramo::olvidarCache();
    $t = App\Models\ComisionTramo::create(['desde' => 0, 'hasta' => null, 'porcentaje' => 10]);
    expect(App\Models\ComisionTramo::porcentajePara(50))->toBe(10.0);

    Illuminate\Support\Facades\DB::enableQueryLog();
    App\Models\ComisionTramo::porcentajePara(80);
    App\Models\ComisionTramo::porcentajePara(120);
    expect(count(Illuminate\Support\Facades\DB::getQueryLog()))->toBe(0);

    $t->update(['porcentaje' => 15]);
    expect(App\Models\ComisionTramo::porcentajePara(50))->toBe(15.0);
});

test('la comision del vendedor se calcula sobre lo cobrado (total con descuento), por producto', function () {
    App\Models\ComisionTramo::query()->delete();
    App\Models\ComisionTramo::olvidarCache();
    App\Models\ComisionTramo::create(['desde' => 0, 'hasta' => 135, 'porcentaje' => 10]);
    App\Models\ComisionTramo::create(['desde' => 136, 'hasta' => null, 'porcentaje' => 5]);

    $venta = new App\Models\Venta(['total' => 130]);
    $venta->setRelation('detalles', collect([new App\Models\DetalleVenta(['subtotal' => 140])]));

    // Producto $140 cobrado en $130: cae en el tramo de $130 (10%) = 13.00, no en el de $140 (5%).
    expect($venta->comisionVendedor())->toBe(13.0);
});
