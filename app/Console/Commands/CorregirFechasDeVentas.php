<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Corrige ventas cuya fecha quedó mal digitada (día y mes invertidos, año con
 * error) y que por eso aparecen "en el futuro". Detecta las ventas cuya
 * fecha_venta es POSTERIOR a la fecha en que se registraron (algo imposible) y:
 *
 *  - Si invertir día y mes da una fecha válida, anterior al registro de la venta y
 *    no posterior a su primer pago, la propone y la aplica sola.
 *  - Las demás ("manuales") solo se corrigen si se indica la fecha correcta con
 *    --manual=ID:AAAA-MM-DD.
 *
 * Al cambiar la fecha de una venta, sus cuotas (gestion_cobros) se mueven los mismos
 * días, para que sigan a la misma distancia de la venta (+14, +28...). No toca pagos.
 * Por defecto solo muestra qué haría; con --aplicar escribe y deja registro en el
 * historial de actividad (log "fechas_corregidas") con la fecha anterior.
 */
class CorregirFechasDeVentas extends Command
{
    protected $signature = 'ventas:corregir-fechas
        {--aplicar : Escribe los cambios (sin esta opción solo muestra la vista previa)}
        {--manual=* : Fecha correcta para una venta sin propuesta automática, formato ID:AAAA-MM-DD}';

    protected $description = 'Corrige ventas con fecha posterior a su registro (día/mes invertidos) y mueve sus cuotas';

    public function handle(): int
    {
        $manuales = $this->leerManuales();
        if ($manuales === null) {
            return self::FAILURE;
        }

        $ventas = DB::table('ventas')
            ->whereNull('deleted_at')
            ->whereRaw('fecha_venta > created_at')
            ->orderBy('fecha_venta')
            ->get();

        $filas = [];
        $aplicables = [];
        foreach ($ventas as $v) {
            $actual = Carbon::parse($v->fecha_venta);
            $propuesta = $this->propuestaAutomatica($v);
            $origen = 'auto';

            if (isset($manuales[$v->id])) {
                $propuesta = $manuales[$v->id];
                $origen = 'manual';
            }

            $cuotas = DB::table('gestion_cobros')->where('venta_id', $v->id)->count();
            $primerPago = DB::table('pago_ventas')->where('venta_id', $v->id)->whereNull('anulado_en')->min('fecha_pago');

            if ($propuesta && $primerPago && $propuesta->gt(Carbon::parse($primerPago))) {
                $propuesta = null; // chocaría con un pago ya registrado
            }

            $filas[] = [
                $v->id, $v->numero_venta, $actual->toDateString(), $propuesta?->toDateString() ?? '— (manual)',
                $origen, $cuotas, $primerPago ? substr($primerPago, 0, 10) : '—',
            ];

            if ($propuesta) {
                $aplicables[] = [$v, $actual, $propuesta];
            }
        }

        $manualesIds = array_diff(array_column($filas, 0), array_map(fn ($a) => $a[0]->id, $aplicables));
        $this->table(['id', 'venta', 'fecha actual', 'fecha corregida', 'origen', 'cuotas', '1er pago'], $filas);
        $this->line(sprintf(
            '%d venta(s) con fecha imposible: %d con corrección, %d sin propuesta (indica la fecha con --manual=ID:AAAA-MM-DD).',
            count($filas), count($aplicables), count($manualesIds)
        ));

        $pagosFuturos = DB::table('pago_ventas')->where('fecha_pago', '>', today()->toDateString())
            ->whereIn('venta_id', collect($filas)->pluck(0))->count();
        if ($pagosFuturos > 0) {
            $this->warn("Ojo: {$pagosFuturos} pago(s) de estas ventas tienen fecha futura. Este comando no los toca; revísalos aparte.");
        }

        if (! $this->option('aplicar')) {
            $this->info('Vista previa: no se cambió nada. Agrega --aplicar para escribir los cambios.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($aplicables) {
            foreach ($aplicables as [$v, $actual, $nueva]) {
                $dias = (int) $actual->copy()->startOfDay()->diffInDays($nueva->copy()->startOfDay(), false);

                $cambios = ['fecha_venta' => $nueva->copy()->setTimeFrom($actual)];
                if ($v->fecha_pago_limite) {
                    $cambios['fecha_pago_limite'] = Carbon::parse($v->fecha_pago_limite)->addDays($dias)->toDateString();
                }
                DB::table('ventas')->where('id', $v->id)->update($cambios);

                foreach (DB::table('gestion_cobros')->where('venta_id', $v->id)->get(['id', 'fecha_vencimiento']) as $c) {
                    DB::table('gestion_cobros')->where('id', $c->id)->update([
                        'fecha_vencimiento' => Carbon::parse($c->fecha_vencimiento)->addDays($dias)->toDateString(),
                    ]);
                }

                activity('fechas_corregidas')
                    ->withProperties([
                        'venta_id' => $v->id,
                        'numero_venta' => $v->numero_venta,
                        'fecha_anterior' => $actual->toDateString(),
                        'fecha_nueva' => $nueva->toDateString(),
                        'dias_movidos' => $dias,
                    ])
                    ->log("Fecha de la venta {$v->numero_venta} corregida de {$actual->toDateString()} a {$nueva->toDateString()}");
            }
        });

        $this->info(count($aplicables).' venta(s) corregida(s).');

        return self::SUCCESS;
    }

    /** Día y mes invertidos, si da una fecha válida anterior al registro de la venta. */
    private function propuestaAutomatica(object $v): ?Carbon
    {
        $actual = Carbon::parse($v->fecha_venta);
        $registro = Carbon::parse($v->created_at);

        if ($actual->day > 12) {
            return null;
        }

        $invertida = Carbon::create($actual->year, $actual->day, $actual->month, 0, 0, 0);

        return $invertida->lte($registro) ? $invertida : null;
    }

    /** @return array<int, Carbon>|null null si algún valor es inválido */
    private function leerManuales(): ?array
    {
        $resultado = [];
        foreach ((array) $this->option('manual') as $item) {
            if (! preg_match('/^(\d+):(\d{4}-\d{2}-\d{2})$/', (string) $item, $m) || ! checkdate((int) substr($m[2], 5, 2), (int) substr($m[2], 8, 2), (int) substr($m[2], 0, 4))) {
                $this->error("Valor inválido en --manual: \"{$item}\" (usa ID:AAAA-MM-DD).");

                return null;
            }
            $resultado[(int) $m[1]] = Carbon::parse($m[2])->startOfDay();
        }

        return $resultado;
    }
}
