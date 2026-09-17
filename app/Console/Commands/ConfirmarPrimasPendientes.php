<?php

namespace App\Console\Commands;

use App\Models\AnticipoVendedor;
use App\Models\ComisionTramo;
use App\Models\DetalleVenta;
use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

// Corre una vez al día (ver routes/console.php) y confirma automáticamente
// la prima de todas las ventas del día que aún no se hayan confirmado a
// mano desde "Resumen de Ventas del Día" -- para que nadie tenga que entrar
// venta por venta a darle "Confirmar" si nadie lo hizo durante el día.
class ConfirmarPrimasPendientes extends Command
{
    protected $signature = 'app:confirmar-primas-pendientes';

    protected $description = 'Confirma automáticamente las primas de las ventas del día que quedaron sin confirmar';

    public function handle(): int
    {
        $ventas = Venta::whereDate('fecha_venta', today())
            ->where('prima', '>', 0)
            ->whereNotNull('vendedor_id')
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->whereDoesntHave('anticipoVendedor')
            ->with('detalles')
            ->get();

        $confirmadas = 0;

        foreach ($ventas as $venta) {
            $tope = round($venta->detalles->sum(
                fn (DetalleVenta $d) => (float) $d->subtotal * ComisionTramo::porcentajePara((float) $d->subtotal) / 100
            ), 2);
            $monto = min((float) $venta->prima, $tope);

            $inicioSemana = Carbon::parse($venta->fecha_venta)->startOfWeek(Carbon::MONDAY);

            AnticipoVendedor::create([
                'vendedor_id'    => $venta->vendedor_id,
                'autorizado_por' => 1,
                'monto'          => $monto,
                'fecha'          => $venta->fecha_venta->toDateString(),
                'semana_inicio'  => $inicioSemana->toDateString(),
                'semana_fin'     => $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'descripcion'    => "Prima venta #{$venta->numero_venta} (confirmada automáticamente)",
                'estado'         => 'pendiente',
                'venta_id'       => $venta->id,
            ]);

            $confirmadas++;
        }

        $this->info("Primas confirmadas automáticamente: {$confirmadas}.");

        return self::SUCCESS;
    }
}
