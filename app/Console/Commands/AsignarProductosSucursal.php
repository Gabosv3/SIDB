<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Console\Command;

class AsignarProductosSucursal extends Command
{
    protected $signature = 'productos:asignar-sucursal
                            {--sucursal= : ID de la sucursal a asignar (si no se indica, muestra listado)}';

    protected $description = 'Asigna sucursal_id a todos los productos que tienen sucursal_id = NULL';

    public function handle(): int
    {
        $huerfanos = Producto::whereNull('sucursal_id')->count();

        if ($huerfanos === 0) {
            $this->info('No hay productos sin sucursal asignada.');
            return self::SUCCESS;
        }

        $this->warn("Hay {$huerfanos} producto(s) sin sucursal_id asignado.");

        $sucursales = Sucursal::all(['id', 'nombre']);

        if ($sucursales->isEmpty()) {
            $this->error('No existe ninguna sucursal en la base de datos.');
            return self::FAILURE;
        }

        $this->table(['ID', 'Nombre'], $sucursales->map(fn ($s) => [$s->id, $s->nombre])->toArray());

        $sucursalId = $this->option('sucursal')
            ?? $this->ask('¿A qué sucursal asignarlos? (ingresa el ID)');

        if (! Sucursal::where('id', $sucursalId)->exists()) {
            $this->error("No existe una sucursal con ID {$sucursalId}.");
            return self::FAILURE;
        }

        $updated = Producto::whereNull('sucursal_id')
            ->update(['sucursal_id' => $sucursalId]);

        $this->info("✓ Se asignaron {$updated} producto(s) a la sucursal ID {$sucursalId}.");

        return self::SUCCESS;
    }
}
