<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Generar permisos de Shield ────────────────────────────────────────
        $this->call(ShieldSeeder::class);

        // ── Sucursal principal ────────────────────────────────────────────────
        $sucursal = Sucursal::firstOrCreate(
            ['codigo' => 'CENTRAL'],
            [
                'nombre'    => 'Distribuidora Birancesco Menijvar - Central',
                'direccion' => 'Dirección principal',
                'activo'    => true,
            ]
        );

        // ── Rol super admin ───────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // ── Usuario administrador ─────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@sidb.com'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('password'),
            ]
        );

        $admin->assignRole($superAdmin);
        $admin->sucursales()->syncWithoutDetaching([$sucursal->id]);
    }
}
