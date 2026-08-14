<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FusionarProductos extends Command
{
    protected $signature = 'productos:fusionar
                            {mantener : ID del producto que se conserva}
                            {eliminar* : IDs de los productos duplicados a fusionar dentro del anterior}
                            {--apply : Aplica los cambios. Sin este flag solo se muestra un reporte, sin tocar nada (dry-run)}';

    protected $description = 'Fusiona uno o más productos duplicados dentro de un producto "canónico": reasigna'
        .' todas sus referencias (ventas, compras, movimientos de stock, asignaciones diarias, preventas,'
        .' proveedores), suma el stock, y desactiva los duplicados (no se borran, para no perder historial).'
        .' Corre en modo de prueba por defecto; agrega --apply para modificar datos.';

    /** Tablas simples: solo hay que reapuntar producto_id, sin riesgo de duplicar una fila única. */
    private const TABLAS_SIMPLES = ['movimientos_stock', 'detalle_ventas', 'detalle_compras', 'detalle_preventas'];

    public function handle(): int
    {
        $aplicar = (bool) $this->option('apply');

        $mantenerId = (int) $this->argument('mantener');
        $eliminarIds = array_map('intval', $this->argument('eliminar'));

        if (in_array($mantenerId, $eliminarIds, true)) {
            $this->error('El producto a mantener no puede estar también en la lista de a eliminar.');

            return self::FAILURE;
        }

        $mantener = Producto::find($mantenerId);
        if (! $mantener) {
            $this->error("No existe el producto #{$mantenerId} a mantener.");

            return self::FAILURE;
        }

        $duplicados = Producto::whereIn('id', $eliminarIds)->get();
        if ($duplicados->count() !== count($eliminarIds)) {
            $this->error('Alguno de los IDs a eliminar no existe.');

            return self::FAILURE;
        }

        $this->info("Se mantendrá: #{$mantener->id} \"{$mantener->nombre}\" ({$mantener->codigo})");
        $this->table(
            ['ID', 'Código', 'Nombre', 'Stock', 'Ventas', 'Compras', 'Movs. stock', 'Preventas'],
            $duplicados->map(fn (Producto $p) => [
                $p->id, $p->codigo, $p->nombre, $p->stock,
                DB::table('detalle_ventas')->where('producto_id', $p->id)->count(),
                DB::table('detalle_compras')->where('producto_id', $p->id)->count(),
                DB::table('movimientos_stock')->where('producto_id', $p->id)->count(),
                DB::table('detalle_preventas')->where('producto_id', $p->id)->count(),
            ])
        );

        $stockASumar = $duplicados->sum('stock');
        $this->info('Stock que se sumará a #'.$mantenerId.': '.$stockASumar.' (quedaría en '.($mantener->stock + $stockASumar).')');

        if (! $aplicar) {
            $this->warn('Modo de prueba (dry-run) — no se guardó nada. Volvé a correr con --apply para aplicar.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($mantener, $duplicados, $eliminarIds) {
            foreach (self::TABLAS_SIMPLES as $tabla) {
                DB::table($tabla)->whereIn('producto_id', $eliminarIds)->update(['producto_id' => $mantener->id]);
            }

            // producto_proveedor y detalle_asignaciones tienen UNIQUE(producto_id, otra_columna) —
            // si el producto a mantener ya tiene una fila para ese mismo proveedor/asignación,
            // reapuntar generaría un duplicado. Se borran esas filas conflictivas del duplicado
            // (la información ya existe del lado del producto que se conserva) y el resto se reapunta.
            $this->reapuntarEvitandoDuplicados('producto_proveedor', 'proveedor_id', $mantener->id, $eliminarIds);
            $this->reapuntarEvitandoDuplicados('detalle_asignaciones', 'asignacion_id', $mantener->id, $eliminarIds);

            $mantener->increment('stock', $duplicados->sum('stock'));

            Producto::whereIn('id', $eliminarIds)->update(['activo' => false]);
        });

        $this->info('Fusión completada. Los productos duplicados quedaron desactivados (no se borraron).');

        return self::SUCCESS;
    }

    private function reapuntarEvitandoDuplicados(string $tabla, string $columnaPar, int $mantenerId, array $eliminarIds): void
    {
        $filas = DB::table($tabla)->whereIn('producto_id', $eliminarIds)->get();

        foreach ($filas as $fila) {
            $yaExiste = DB::table($tabla)
                ->where('producto_id', $mantenerId)
                ->where($columnaPar, $fila->{$columnaPar})
                ->exists();

            if ($yaExiste) {
                DB::table($tabla)->where('id', $fila->id)->delete();
            } else {
                DB::table($tabla)->where('id', $fila->id)->update(['producto_id' => $mantenerId]);
            }
        }
    }
}
