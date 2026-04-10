@php
    /** @var \App\Models\Venta $venta */
    $pagos        = $venta->pagos;
    $totalPagado  = $pagos->sum('monto');
    $saldo        = (float) $venta->saldo_pendiente;
    $totalVenta   = (float) $venta->total;
    $pct          = $totalVenta > 0 ? round(($totalPagado / $totalVenta) * 100) : 0;

    $metodoBadge = [
        'efectivo'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'transferencia' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'cheque'        => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'deposito'      => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    ];
@endphp

<div class="space-y-4 px-1 pb-2">

    {{-- Resumen --}}
    <div class="grid grid-cols-3 gap-3 text-center">
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Total venta</p>
            <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">${{ number_format($totalVenta, 2) }}</p>
        </div>
        <div class="rounded-lg bg-green-50 dark:bg-green-900/30 p-3">
            <p class="text-xs text-green-600 dark:text-green-400">Pagado</p>
            <p class="mt-1 text-lg font-bold text-green-700 dark:text-green-300">${{ number_format($totalPagado, 2) }}</p>
        </div>
        <div class="rounded-lg {{ $saldo > 0 ? 'bg-red-50 dark:bg-red-900/30' : 'bg-green-50 dark:bg-green-900/30' }} p-3">
            <p class="text-xs {{ $saldo > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">Saldo pendiente</p>
            <p class="mt-1 text-lg font-bold {{ $saldo > 0 ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300' }}">${{ number_format($saldo, 2) }}</p>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
            <span>Progreso de pago</span>
            <span>{{ $pct }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
            <div
                class="h-2 rounded-full {{ $saldo <= 0 ? 'bg-green-500' : 'bg-blue-500' }} transition-all"
                style="width: {{ min($pct, 100) }}%"
            ></div>
        </div>
    </div>

    {{-- Tabla de pagos --}}
    @if ($pagos->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 py-8 text-center text-sm text-gray-400">
            Esta venta aún no tiene pagos registrados.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Método</th>
                        <th class="px-4 py-2 text-right">Monto</th>
                        <th class="px-4 py-2 text-left">Referencia</th>
                        <th class="px-4 py-2 text-left">Registró</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                    @foreach ($pagos->sortBy('fecha_pago') as $i => $pago)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-200">
                                {{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $metodoBadge[$pago->metodo_pago] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($pago->metodo_pago) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-green-600 dark:text-green-400">
                                ${{ number_format($pago->monto, 2) }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                {{ $pago->referencia ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                                {{ $pago->user?->name ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
