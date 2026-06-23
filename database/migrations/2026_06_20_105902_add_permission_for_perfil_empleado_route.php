<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'View:PerfilEmpleado', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo('View:PerfilEmpleado');
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'View:PerfilEmpleado')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
