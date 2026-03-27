<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // Tablas que necesitan permisos
        $tables = ['clientes', 'cobradores', 'rutas_cobro', 'productos', 'movimientos_stock'];
        $permissions = ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];

        // Crear permisos para cada tabla
        foreach ($tables as $table) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission . '_' . $table, 'guard_name' => 'web']
                );
            }
        }

        // Crear roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Asignar todos los permisos a super_admin
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $superAdmin->syncPermissions($allPermissions);
        
        // Media permisos para admin (sin delete)
        $adminPerms = Permission::where('guard_name', 'web')
            ->where('name', 'not like', '%delete%')
            ->get();
        $admin->syncPermissions($adminPerms);
        
        // Solo view para user
        $viewPerms = Permission::where('guard_name', 'web')
            ->where('name', 'like', '%view%')
            ->get();
        $user->syncPermissions($viewPerms);
    }
}

