@php
    $this->previousUrl ??= url()->previous();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-900">ℹ️ Información</h3>
            <p class="text-sm text-blue-700 mt-2">
                Aquí se muestran los clientes sin ruta de cobro asignada.
                Asigna una ruta a cada cliente para organizar el trabajo de cobranza.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
