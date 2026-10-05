{{-- Asistente de navegación (sin IA): catálogo según los permisos del usuario actual --}}
<script>window.ASISTENTE = @json(\App\Services\AsistenteService::catalogo());</script>
<script src="{{ asset('js/asistente.js') }}?v={{ @filemtime(public_path('js/asistente.js')) }}"></script>
