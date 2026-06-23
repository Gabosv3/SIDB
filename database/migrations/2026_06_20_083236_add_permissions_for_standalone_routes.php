<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'View:MonitorPos',
        'View:ResumenDia',
        'View:WhatsappCenter',
        'Manage:WhatsappSettings',
        'View:PagosUbicacion',
        'View:AsignacionesDiarias',
        'View:Reportes',
        'Export:Clientes',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_merge($this->permissions, ['View:RutaCobro']));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->get()->each(function (Permission $permission) {
            $permission->roles()->detach();
            $permission->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
