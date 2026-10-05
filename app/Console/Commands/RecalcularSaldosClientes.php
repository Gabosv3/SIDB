<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Console\Command;

// El saldo de un cliente es la suma del saldo pendiente de sus ventas. Las
// ventas creadas desde la app del vendedor no lo actualizaban (ya corregido en
// Api\VentaController), así que quedaron clientes con "Saldo $0.00" aunque
// tienen una venta a crédito pendiente. Este comando los recalcula.
class RecalcularSaldosClientes extends Command
{
    protected $signature = 'clientes:recalcular-saldos {--dry-run : Solo mostrar cuántos cambiarían, sin guardar}';

    protected $description = 'Recalcula clientes.saldo como la suma del saldo pendiente de sus ventas (corrige saldos que quedaron en $0)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $saldos = Venta::selectRaw('cliente_id, ROUND(SUM(saldo_pendiente), 2) as saldo')
            ->whereNotNull('cliente_id')
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->groupBy('cliente_id')
            ->pluck('saldo', 'cliente_id');

        $cambios = 0;

        Cliente::withTrashed()->select('id', 'saldo')->orderBy('id')->chunkById(500, function ($clientes) use ($saldos, $dryRun, &$cambios) {
            foreach ($clientes as $cliente) {
                $correcto = round((float) ($saldos[$cliente->id] ?? 0), 2);

                if (round((float) $cliente->saldo, 2) === $correcto) {
                    continue;
                }

                $cambios++;

                if (! $dryRun) {
                    Cliente::withTrashed()->whereKey($cliente->id)->update(['saldo' => $correcto]);
                }
            }
        });

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Clientes con saldo corregido: {$cambios}");

        return self::SUCCESS;
    }
}
