@php
    $tenant = \Filament\Facades\Filament::getTenant()?->id ?? 1;
@endphp

<a href="/whatsapp/{{ $tenant }}"
   target="_blank"
   rel="noopener noreferrer"
   class="block px-3 py-2 mx-2 rounded-lg text-sm font-medium transition-colors
          text-gray-700 hover:bg-gray-100 hover:text-gray-900
          dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
    💬 WhatsApp Center
</a>
