<?php

use App\Models\Sucursal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * QA automatizado: cada formulario "Crear" del panel, enviado vacio, debe
 * responder con errores de validacion -- nunca con una excepcion (SQL, tipo, etc.).
 */
$paginas = [
    'AsignacionDiariaResource\Pages\CreateAsignacionDiaria', 'CategoriaResource\Pages\CreateCategoria',
    'ClienteResource\Pages\CreateCliente', 'CobradorResource\Pages\CreateCobrador',
    'ComisionTramoResource\Pages\CreateComisionTramo', 'CompraResource\Pages\CreateCompra',
    'GarantiaResource\Pages\CreateGarantia', 'ProductoResource\Pages\CreateProducto',
    'ProveedorResource\Pages\CreateProveedor', 'ReintegroResource\Pages\CreateReintegro',
    'RutaCobroResource\Pages\CreateRutaCobro', 'SucursalResource\Pages\CreateSucursal',
    'SupervisorResource\Pages\CreateSupervisor', 'UserResource\Pages\CreateUser',
    'ValeResource\Pages\CreateVale', 'VehiculoResource\Pages\CreateVehiculo',
    'VendedorResource\Pages\CreateVendedor', 'VentaResource\Pages\CreateVenta',
];

beforeEach(function () {
    $this->sucursal = Sucursal::create([
        'nombre' => 'Sucursal QA', 'codigo' => 'QA01', 'direccion' => 'Av. QA 1',
        'telefono' => '+50312345678', 'email' => 'qa@test.com', 'activo' => true,
    ]);
    $rol = Role::findOrCreate('super_admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole($rol);
    // En produccion super_admin tiene los permisos asignados; aqui se le da acceso total.
    \Illuminate\Support\Facades\Gate::before(fn () => true);
    $this->actingAs($this->admin);
    Filament::setCurrentPanel('administrativo');
    Filament::setTenant($this->sucursal);
});

foreach ($paginas as $ruta) {
    test("QA formulario vacio: {$ruta}", function () use ($ruta) {
        $clase = 'App\\Filament\\Resources\\' . $ruta;
        if (! class_exists($clase)) {
            $this->markTestSkipped("{$clase} no existe");
        }

        $componente = Livewire::test($clase);
        $componente->call('create');

        // Si hubiera una excepcion, Livewire ya la habria lanzado. Debe haber al menos
        // un error de validacion (todos estos formularios tienen campos obligatorios)
        // y no debe haberse creado nada.
        expect($componente->errors()->isEmpty())->toBeFalse();
    });
}
