<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Permission::whereIn('name', ['View:WhatsappCenter', 'Manage:WhatsappSettings'])
            ->get()
            ->each(function (Permission $permission) {
                $permission->roles()->detach();
                $permission->delete();
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::firstOrCreate(['name' => 'View:WhatsappCenter', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Manage:WhatsappSettings', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
