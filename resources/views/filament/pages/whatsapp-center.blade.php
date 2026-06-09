@php
    $tenant = auth()->user()?->sucursal_id ?? 1;
    $whatsappUrl = route('whatsapp.index', ['tenant' => $tenant]);
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">💬 WhatsApp Center</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Bandeja de conversaciones con clientes
            </p>
        </div>
        <a href="{{ $whatsappUrl }}" target="_blank"
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            ↗ Abrir en nueva pestaña
        </a>
    </div>

    <!-- IFRAME -->
    <div class="border rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow" style="height: 75vh;">
        <iframe src="{{ $whatsappUrl }}"
                style="width: 100%; height: 100%; border: none; display: block;"
                title="WhatsApp Center">
        </iframe>
    </div>

    <!-- NOTA -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <p class="text-sm text-blue-900 dark:text-blue-300">
            💡 <strong>Tip:</strong> Usa los botones 💬 WhatsApp en la lista de clientes para acceder rápidamente a las conversaciones.
        </p>
    </div>
</div>
