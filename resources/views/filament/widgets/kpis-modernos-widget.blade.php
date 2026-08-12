<x-filament-widgets::widget>
    <style>
        .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; }
        @media (max-width:1280px) { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:640px) { .kpi-grid { grid-template-columns:1fr; } }

        .kpi-card {
            position:relative; overflow:hidden; border-radius:0.9rem; padding:1.1rem 1.2rem;
            background:#fff; border:1px solid rgba(0,0,0,.06); box-shadow:0 1px 3px rgba(0,0,0,.04);
            transition:transform .15s, box-shadow .15s;
        }
        .kpi-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,.08); }
        html.dark .kpi-card { background:#1e2333; border-color:rgba(255,255,255,.06); box-shadow:none; }

        .kpi-icon-wrap {
            width:2.6rem; height:2.6rem; border-radius:0.7rem; display:flex; align-items:center;
            justify-content:center; margin-bottom:.75rem;
        }
        .kpi-icon-wrap svg { width:1.4rem; height:1.4rem; }

        .kpi-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
        html.dark .kpi-label { color:#9ca3af; }
        .kpi-valor { font-size:1.5rem; font-weight:800; color:#111827; margin-top:.15rem; line-height:1.15; }
        html.dark .kpi-valor { color:#f3f4f6; }
        .kpi-sub { font-size:.74rem; color:#9ca3af; margin-top:.35rem; display:flex; align-items:center; gap:.25rem; }

        .kpi-tendencia-up { color:#16a34a; }
        .kpi-tendencia-down { color:#dc2626; }

        .kpi-actualizado { text-align:right; font-size:.68rem; color:#9ca3af; margin-top:.5rem; }
    </style>

    @php
        $paletas = [
            'emerald' => ['bg' => '#d1fae5', 'fg' => '#059669'],
            'indigo'  => ['bg' => '#e0e7ff', 'fg' => '#4338ca'],
            'sky'     => ['bg' => '#e0f2fe', 'fg' => '#0369a1'],
            'amber'   => ['bg' => '#fef3c7', 'fg' => '#b45309'],
            'rose'    => ['bg' => '#ffe4e6', 'fg' => '#be123c'],
            'violet'  => ['bg' => '#ede9fe', 'fg' => '#6d28d9'],
            'orange'  => ['bg' => '#ffedd5', 'fg' => '#c2410c'],
            'red'     => ['bg' => '#fee2e2', 'fg' => '#b91c1c'],
        ];
    @endphp

    <div class="kpi-grid">
        @foreach($kpis as $kpi)
            @php $paleta = $paletas[$kpi['color']] ?? $paletas['indigo']; @endphp
            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background:{{ $paleta['bg'] }}; color:{{ $paleta['fg'] }};">
                    <x-filament::icon :icon="$kpi['icono']" />
                </div>
                <div class="kpi-label">{{ $kpi['label'] }}</div>
                <div class="kpi-valor">{{ $kpi['valor'] }}</div>
                <div class="kpi-sub">
                    @if($kpi['tendencia'] === 'up')
                        <span class="kpi-tendencia-up">▲</span>
                    @elseif($kpi['tendencia'] === 'down')
                        <span class="kpi-tendencia-down">▼</span>
                    @endif
                    {{ $kpi['sub'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="kpi-actualizado">Actualizado {{ $actualizadoEn }} · se refresca solo cada 30s</div>
</x-filament-widgets::widget>
