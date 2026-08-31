<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;

class DetectarProductosDuplicados extends Command
{
    protected $signature = 'productos:duplicados-similares
                            {--umbral=68 : % mínimo de similitud entre nombres para considerarlos posible duplicado (0-100)}
                            {--origen= : Filtra solo productos "excel" o "manual". Sin este flag, revisa todos.}';

    protected $description = 'Solo reporta (no modifica nada) grupos de productos con nombres muy parecidos —'
        .' útil cuando el catálogo se importó varias veces desde Excel con nombres ligeramente distintos para'
        .' el mismo artículo (ej. "1 cama 1.40", "1 cama de 1.40", "1 Cama Junco 1.40 metros"). Para fusionar'
        .' un grupo confirmado, usar productos:fusionar.';

    public function handle(): int
    {
        $umbral = (float) $this->option('umbral');
        $origen = $this->option('origen');

        if ($origen && ! in_array($origen, ['excel', 'manual'], true)) {
            $this->error('--origen debe ser "excel" o "manual".');

            return self::FAILURE;
        }

        $productos = Producto::when($origen, fn ($q) => $q->where('origen', $origen))
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre', 'stock', 'precio_venta', 'activo']);

        $visitados = [];
        $grupos = [];

        foreach ($productos as $a) {
            if (in_array($a->id, $visitados, true)) {
                continue;
            }

            $grupo = collect([$a]);

            foreach ($productos as $b) {
                if ($a->id === $b->id || in_array($b->id, $visitados, true)) {
                    continue;
                }

                similar_text(
                    $this->normalizar($a->nombre),
                    $this->normalizar($b->nombre),
                    $pct
                );

                if ($pct >= $umbral) {
                    $grupo->push($b);
                }
            }

            if ($grupo->count() > 1) {
                $grupo->each(fn ($p) => $visitados[] = $p->id);
                $grupos[] = $grupo;
            }
        }

        // Dos productos "en el límite" del umbral pueden formar el mismo
        // grupo dos veces si el primero en aparecer no alcanza el umbral con
        // todos los demás pero el segundo sí — se deduplica por el conjunto
        // de IDs antes de mostrar.
        $vistos = [];
        $grupos = array_values(array_filter($grupos, function ($grupo) use (&$vistos) {
            $firma = $grupo->pluck('id')->sort()->implode(',');
            if (in_array($firma, $vistos, true)) {
                return false;
            }
            $vistos[] = $firma;

            return true;
        }));

        if (empty($grupos)) {
            $this->info('No se encontraron grupos de nombres parecidos con ese umbral.');

            return self::SUCCESS;
        }

        $this->info(count($grupos).' grupo(s) de posibles duplicados encontrados:');

        foreach ($grupos as $i => $grupo) {
            $this->line('');
            $this->line('<fg=yellow>Grupo '.($i + 1).'</>');
            $this->table(
                ['ID', 'Código', 'Nombre', 'Stock', 'Precio venta', 'Activo'],
                $grupo->map(fn (Producto $p) => [
                    $p->id, $p->codigo, $p->nombre, $p->stock,
                    '$'.number_format((float) $p->precio_venta, 2),
                    $p->activo ? 'sí' : 'no',
                ])
            );
        }

        $this->line('');
        $this->warn('Esto es solo un reporte — nada se modificó. Revisá cada grupo y, si de verdad son el mismo');
        $this->warn('producto, fusionalos con: php artisan productos:fusionar {id_a_mantener} {ids_a_eliminar...}');

        return self::SUCCESS;
    }

    private function normalizar(string $nombre): string
    {
        $n = mb_strtolower(trim($nombre));
        $n = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $n);
        $n = preg_replace('/[^a-z0-9. ]/', ' ', $n);

        return preg_replace('/\s+/', ' ', $n);
    }
}
