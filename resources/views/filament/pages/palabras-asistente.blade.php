<x-filament-panels::page>
    <style>
        .pa-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1rem 1.25rem; margin-bottom:1.25rem; }
        .dark .pa-card { background:#1e1e24; border-color:#2e2e3a; }
        .pa-card h3 { font-weight:700; font-size:1rem; margin-bottom:.4rem; }
        .pa-hint { font-size:.85rem; color:#6b7280; margin-bottom:.75rem; }
        .pa-table { width:100%; border-collapse:collapse; font-size:.85rem; }
        .pa-table th { text-align:left; padding:.5rem .6rem; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid #e5e7eb; }
        .pa-table td { padding:.5rem .6rem; border-bottom:1px solid #f3f4f6; vertical-align:top; }
        .dark .pa-table td { border-color:#2a2a35; }
        .pa-chip { display:inline-block; background:#eff6ff; color:#1d4ed8; border-radius:999px; padding:.1rem .55rem; margin:.1rem .15rem .1rem 0; font-size:.78rem; }
        .dark .pa-chip { background:rgba(37,99,235,.2); color:#93c5fd; }
        .pa-si { color:#16a34a; font-weight:600; }
    </style>

    <div class="pa-card">
        <h3>Cómo se usa</h3>
        <p class="pa-hint">Abre el asistente con el botón 💬 de abajo a la derecha y escribe una frase corta. Con <b>crear, nuevo, agregar, registrar</b> o <b>alta</b> te lleva al formulario de crear; sin verbo, te lleva al listado. No importan los acentos, las mayúsculas ni el plural.</p>
        <p class="pa-hint" style="margin-bottom:0">Ejemplos: «quiero crear un usuario», «nuevo cliente», «ventas de hoy», «hacer un respaldo», «cómo corrijo el precio de una venta».</p>
    </div>

    <div class="pa-card">
        <h3>Pantallas con listado y formulario</h3>
        <p class="pa-hint">Solo aparecen las que tu usuario puede ver.</p>
        <table class="pa-table">
            <thead><tr><th>Pantalla</th><th>Palabras que la activan</th><th>Se puede crear</th></tr></thead>
            <tbody>
                @foreach($modulos as $m)
                    <tr>
                        <td><b>{{ $m['pantalla'] }}</b></td>
                        <td>@foreach($m['palabras'] as $p)<span class="pa-chip">{{ $p }}</span>@endforeach</td>
                        <td>@if($m['crear'])<span class="pa-si">Sí</span>@else — @endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pa-card">
        <h3>Pantallas especiales (resúmenes, reportes y herramientas)</h3>
        <table class="pa-table">
            <thead><tr><th>Pantalla</th><th>Palabras que la activan</th></tr></thead>
            <tbody>
                @foreach($especiales as $m)
                    <tr>
                        <td><b>{{ $m['pantalla'] }}</b></td>
                        <td>@foreach($m['palabras'] as $p)<span class="pa-chip">{{ $p }}</span>@endforeach</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pa-card">
        <h3>Preguntas de «¿cómo hago…?»</h3>
        <p class="pa-hint">El asistente responde con una explicación corta y un enlace.</p>
        <table class="pa-table">
            <thead><tr><th>Respuesta</th><th>Frases que reconoce</th></tr></thead>
            <tbody>
                @foreach($faq as $f)
                    <tr>
                        <td style="max-width:360px">{{ \Illuminate\Support\Str::limit($f['r'], 110) }}</td>
                        <td>@foreach($f['k'] as $p)<span class="pa-chip">{{ $p }}</span>@endforeach</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
