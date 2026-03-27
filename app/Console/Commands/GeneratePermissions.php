<?php

namespace App\Console\Commands;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GeneratePermissions extends Command
{
    protected $signature = 'permissions:generate';
    protected $description = 'Genera permisos Shield para todos los resources y asigna al rol super_admin';

    public function handle(): int
    {
        $this->info('Generando políticas y permisos Shield...');

        try {
            $this->call('shield:generate', [
                '--all'    => true,
                '--option' => 'policies_and_permissions',
                '--panel'  => 'administrativo',
            ]);
        } catch (\Exception $e) {
            $this->error('Error en shield:generate: ' . $e->getMessage());
            return 1;
        }

        // Asignar todos los permisos al rol super_admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permissions = Permission::where('guard_name', 'web')->get();
        $superAdmin->syncPermissions($permissions);

        $this->info("✅ {$permissions->count()} permisos asignados al rol super_admin.");

        return 0;
    }
}
