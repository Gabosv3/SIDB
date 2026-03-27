<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GeneratePermissions extends Command
{
    protected $signature = 'permissions:generate';
    protected $description = 'Generate permissions for all resources';

    public function handle()
    {
        $resources = [
            'App\Filament\Resources\ClienteResource',
            'App\Filament\Resources\CobradorResource',
            'App\Filament\Resources\RutaCobroResource',
        ];

        foreach ($resources as $resource) {
            $model = $resource::getModel();
            $userModel = new $model();
            $table = $userModel->getTable();

            $this->info("Generando permisos para: $table");

            foreach (['view', 'view_any', 'create', 'update', 'delete', 'delete_any'] as $permission) {
                Permission::firstOrCreate(
                    ['name' => "{$permission}_{$table}", 'guard_name' => 'web']
                );
            }
        }

        // Asignar todos los permisos al rol super_admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permissions = Permission::where('guard_name', 'web')->get();
        $superAdmin->syncPermissions($permissions);

        $this->info('✅ Permisos generados exitosamente');
    }
}
