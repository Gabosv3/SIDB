<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Corrige ventas cuya fecha quedó mal digitada (día y mes invertidos, año con
 * error) y que por eso aparecen "en el futuro", y los pagos que arrastran una fecha
 * imposible. Detecta las ventas cuya fecha_venta es POSTERIOR a la fecha en que se
 * registraron (algo imposible) y:
 *
 *  - Si invertir día y mes da una fecha válida, anterior al registro de la venta y
 *    no posterior a su primer pago, la propone y la aplica sola.
 *  - Las demás ("manuales") solo se corrigen si se indica la fecha correcta con
 *    --manual=ID:AAAA-MM-DD.
 *
 * Al cambiar la fecha de una venta, sus cuotas (gestion_cobros) se mueven los mismos
 * días, para que sigan a la misma distancia de la venta (+14, +28...).
 *
 * Pagos: un pago (no anulado) tiene fecha imposible si es FUTURA o anterior a la
 * fecha de su venta (ya corregida). Se le pone la fecha de su venta -- el caso típico
 * es el primer abono o la prima, que se registró junto con la venta. No cambia
 * montos, saldos ni cuotas. Se revisan los pagos con fecha futura de todo el sistema
 * y los de las ventas corregidas.
 *
 * Por defecto solo muestra qué haría; con --aplicar escribe y deja registro en el
 * historial de actividad (log "fechas_corregidas") con la fecha anterior.
 */
class CorregirFechasDeVentas extends Command
{
    protected $signature = 'ventas:corregir-fechas
        {--aplicar : Escribe los cambios (sin esta opción solo muestra la vista previa)}
        {--manual=* : Fecha correcta para una venta sin propuesta automática, formato ID:AAAA-MM-DD}
        {--sin-pagos : No corrige las fechas de los pagos (solo ventas y cuotas)}';

    protected $description = 'Corrige ventas con fecha posterior a su registro (día/mes invertidos), mueve sus cuotas y corrige pagos con fecha imposible';

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
        /** @var array<int, Carbon> fecha que tendrá cada venta tras la corrección */
        $fechaFinal = [];
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

            // Un pago con fecha ya imposible (futura o posterior a su registro) no cuenta como
            // límite: esos pagos se corrigen aparte.
            $primerPagoValido = DB::table('pago_ventas')->where('venta_id', $v->id)->whereNull('anulado_en')
                ->get(['fecha_pago', 'created_at'])
                ->filter(fn ($p) => ! $this->fechaDePagoImposible($p, null))
                ->min('fecha_pago');
            if ($propuesta && $primerPagoValido && $propuesta->gt(Carbon::parse($primerPagoValido))) {
                $propuesta = null; // chocaría con un pago ya registrado
            }

            $filas[] = [
                $v->id, $v->numero_venta, $actual->toDateString(), $propuesta?->toDateString() ?? '— (manual)',
                $origen, $cuotas, $primerPago ? substr($primerPago, 0, 10) : '—',
            ];

            if ($propuesta) {
                $aplicables[] = [$v, $actual, $propuesta];
                $fechaFinal[$v->id] = $propuesta;
            }
        }

        $manualesIds = array_diff(array_column($filas, 0), array_map(fn ($a) => $a[0]->id, $aplicables));
        $this->table(['id', 'venta', 'fecha actual', 'fecha corregida', 'origen', 'cuotas', '1er pago'], $filas);
        $this->line(sprintf(
            '%d venta(s) con fecha imposible: %d con corrección, %d sin propuesta (indica la fecha con --manual=ID:AAAA-MM-DD).',
            count($filas), count($aplicables), count($manualesIds)
        ));

        $pagosAplicables = $this->option('sin-pagos') ? [] : $this->planDePagos(array_column($filas, 0), $fechaFinal, $manualesIds);

        if (! $this->option('aplicar')) {
            $this->info('Vista previa: no se cambió nada. Agrega --aplicar para escribir los cambios.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($aplicables, $pagosAplicables) {
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

            foreach ($pagosAplicables as [$p, $nueva]) {
                DB::table('pago_ventas')->where('id', $p->id)->update(['fecha_pago' => $nueva->toDateString()]);

                activity('fechas_corregidas')
                    ->withProperties([
                        'pago_id' => $p->id,
                        'numero_recibo' => $p->numero_recibo,
                        'venta_id' => $p->venta_id,
                        'monto' => (float) $p->monto,
                        'fecha_anterior' => substr((string) $p->fecha_pago, 0, 10),
                        'fecha_nueva' => $nueva->toDateString(),
                    ])
                    ->log(sprintf(
                        'Fecha del pago %s (venta %s) corregida de %s a %s',
                        $p->numero_recibo ?? "#{$p->id}", $p->venta_id, substr((string) $p->fecha_pago, 0, 10), $nueva->toDateString()
                    ));
            }
        });

        $this->info(count($aplicables).' venta(s) y '.count($pagosAplicables).' pago(s) corregido(s).');

        return self::SUCCESS;
    }

    /**
     * Pagos con fecha imposible: futura, o anterior a la fecha (ya corregida) de su venta.
     * Se les pone la fecha de su venta. Se omiten si esa fecha tampoco es válida
     * (la venta sigue con fecha futura / sin corregir).
     *
     * @param  array<int>  $ventaIdsCorregidas
     * @param  array<int, Carbon>  $fechaFinal
     * @param  array<int>  $ventasSinCorregir
     * @return array<int, array{0: object, 1: Carbon}>
     */
    private function planDePagos(array $ventaIdsCorregidas, array $fechaFinal, array $ventasSinCorregir): array
    {
        $hoy = today();

        $pagos = DB::table('pago_ventas')
            ->whereNull('anulado_en')
            ->where(fn ($q) => $q->where('fecha_pago', '>', $hoy->toDateString())
                ->orWhereColumn('fecha_pago', '>', 'created_at')
                ->orWhereIn('venta_id', $ventaIdsCorregidas ?: [0]))
            ->orderBy('venta_id')->orderBy('id')
            ->get();

        $fechaVenta = DB::table('ventas')->whereIn('id', $pagos->pluck('venta_id')->unique())->pluck('fecha_venta', 'id');

        $filas = [];
        $aplicables = [];
        $sinSalida = 0;
        foreach ($pagos as $p) {
            $venta = $fechaFinal[$p->venta_id] ?? (isset($fechaVenta[$p->venta_id]) ? Carbon::parse($fechaVenta[$p->venta_id])->startOfDay() : null);
            $fechaPago = Carbon::parse($p->fecha_pago)->startOfDay();

            if (! $this->fechaDePagoImposible($p, $venta)) {
                continue;
            }

            if (! $venta || $venta->gt($hoy) || $venta->equalTo($fechaPago)) {
                $sinSalida++;
                $filas[] = [$p->id, $p->numero_recibo ?? '—', $p->venta_id, $fechaPago->toDateString(), '— (revisar a mano)', number_format((float) $p->monto, 2)];

                continue;
            }

            $filas[] = [$p->id, $p->numero_recibo ?? '—', $p->venta_id, $fechaPago->toDateString(), $venta->toDateString(), number_format((float) $p->monto, 2)];
            $aplicables[] = [$p, $venta];
        }

        $this->newLine();
        $this->line('Pagos con fecha imposible (futura o anterior a su venta):');
        $this->table(['pago', 'recibo', 'venta', 'fecha actual', 'fecha corregida', 'monto'], $filas);
        $this->line(sprintf('%d pago(s) con corrección, %d sin salida automática.', count($aplicables), $sinSalida));

        return $aplicables;
    }

    /**
     * Un pago tiene fecha imposible si es futura, si es posterior al día en que se registró
     * (con un día de margen por zona horaria) o si es anterior a la fecha de su venta.
     */
    private function fechaDePagoImposible(object $pago, ?Carbon $fechaVenta): bool
    {
        $fecha = Carbon::parse($pago->fecha_pago)->startOfDay();

        return $fecha->gt(today())
            || $fecha->gt(Carbon::parse($pago->created_at)->startOfDay()->addDay())
            || ($fechaVenta !== null && $fecha->lt($fechaVenta));
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
