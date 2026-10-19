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
