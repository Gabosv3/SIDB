<x-filament-panels::page>
<style>
    .lq-input {
        border:1px solid #d1d5db; border-radius:0.5rem;
        padding:0.5rem 0.75rem; font-size:0.875rem;
        color:#111827; background:#fff; outline:none; transition:border-color .15s; width:100%;
    }
    .lq-input:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.2); }
    .lq-card { background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; margin-bottom:1.25rem; }
    .lq-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .lq-stat-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:0.15rem; }
    .lq-stat-val   { font-size:1.4rem; font-weight:800; color:#0f172a; }
    .lq-thead th { padding:0.55rem 0.75rem; text-align:left; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .lq-thead th:last-child { text-align:right; padding-right:1.25rem; }
    .lq-tr { border-bottom:1px solid #f3f4f6; }
    .lq-tr:hover { background:#f9fafb; }
    .lq-td { padding:0.55rem 0.75rem; font-size:0.82rem; color:#374151; }
    .lq-td:last-child { text-align:right; font-weight:700; padding-right:1.25rem; }
    .lq-btn { display:inline-flex; align-items:center; gap:0.4rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; padding:0.45rem 1rem; cursor:pointer; border:none; transition:all .15s; }
    .lq-btn-green  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    .lq-btn-green:hover { background:#bbf7d0; }
    .lq-anticipo-form { padding:1rem 1.25rem; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; }

    /* Nombre cobrador */
    .lq-cobrador-name { font-size:1rem; font-weight:700; color:#0f172a; }
    .lq-cobrador-sub  { font-size:0.78rem; color:#64748b; margin-top:0.1rem; }

    /* Colores semánticos con dark */
    .lq-val        { font-size:1.2rem; font-weight:800; color:#0f172a; }
    .lq-val-lg     { font-size:1.4rem; font-weight:800; color:#0f172a; }
    .lq-cobrado    { color:#15803d; }
    .lq-comision   { color:#1d4ed8; }
    .lq-anticipo   { color:#b45309; }
    .lq-neto-pos   { color:#15803d; }
    .lq-neto-neg   { color:#dc2626; }
    .lq-dia-name   { font-weight:600; color:#334155; }
    .lq-dia-fecha  { color:#94a3b8; }

    /* Dark semánticos */
    .dark .lq-cobrador-name { color:#f1f5f9; }
    .dark .lq-cobrador-sub  { color:#4b5563; }
    .dark .lq-stat-label  { color:#475569; }
    .dark .lq-stat-val    { color:#f1f5f9; }
    .dark .lq-val         { color:#e2e8f0; }
    .dark .lq-val-lg      { color:#e2e8f0; }
    .dark .lq-cobrado     { color:#34d399; }
    .dark .lq-comision    { color:#60a5fa; }
    .dark .lq-anticipo    { color:#fbbf24; }
    .dark .lq-neto-pos    { color:#4ade80; }
    .dark .lq-neto-neg    { color:#f87171; }
    .dark .lq-dia-name    { color:#cbd5e1; }
    .dark .lq-dia-fecha   { color:#475569; }

    /* Dark estructural */
    .dark .lq-input  { background:#2a2a35; border-color:#3f3f50; color:#f3f4f6; }
    .dark .lq-card   { background:#1e1e24; border-color:#2e2e3a; box-shadow:none; }
    .dark .lq-header { background:#252530; border-bottom-color:#2e2e3a; }
    .dark .lq-thead th   { background:#252530; border-bottom-color:#2e2e3a; color:#475569; }
    .dark .lq-tr  { border-bottom-color:#2a2a35; }
    .dark .lq-tr:hover { background:#252530; }
    .dark .lq-td  { color:#94a3b8; }
    .dark .lq-anticipo-form { background:#1a1a22; border-top-color:#2e2e3a; }
    .dark .lq-btn-green { background:rgba(21,128,61,.15); color:#4ade80; border-color:rgba(74,222,128,.2); }
    .dark .lq-btn-green:hover { background:rgba(21,128,61,.3); }

    /* Anticipos section */
    .lq-anticipo-section { padding:0.75rem 1.25rem; background:#fffbeb; border-top:1px solid #fde68a; }
    .lq-anticipo-title   { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#92400e; margin-bottom:0.5rem; }
    .lq-anticipo-desc    { color:#78350f; }
    .lq-descontado       { font-size:0.65rem; color:#16a34a; margin-left:6px; }
    .dark .lq-anticipo-section { background:rgba(120,53,15,.1); border-top-color:rgba(253,230,138,.1); }
    .dark .lq-anticipo-title   { color:#fbbf24; }
    .dark .lq-anticipo-desc    { color:#94a3b8; }
    .dark .lq-descontado       { color:#4ade80; }
</style>

@php
    $liquidacion = $this->getLiquidacion();
    $cobradores  = $this->getCobradores();
    $semanaFin   = $this->getSemanaFin();
    $diasEs = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];

    $totalSemana    = collect($liquidacion)->sum('total_cobrado');
    $totalComision  = collect($liquidacion)->sum('comision');
    $totalAnticipos = collect($liquidacion)->sum('total_anticipos');
    $totalNeto      = collect($liquidacion)->sum('neto');
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Semana (selecciona el lunes)</label>
        <input type="date" wire:model.live="semana_inicio" class="lq-input" style="width:180px" />
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Cobrador</label>
        <select wire:model.live="cobrador_id" class="lq-input" style="min-width:200px">
            <option value="">Todos los cobradores</option>
            @foreach($cobradores as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
            @endforeach
        </select>
    </div>
    <div style="font-size:0.8rem;color:#64748b;align-self:center;padding-bottom:0.15rem">
        {{ \Carbon\Carbon::parse($this->semana_inicio)->format('d/m/Y') }} — {{ $semanaFin->format('d/m/Y') }}
    </div>
</div>

{{-- Totales generales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="lq-card" style="padding:1.1rem">
        <p class="lq-stat-label">Total cobrado</p>
        <p class="lq-stat-val lq-cobrado">${{ number_format($totalSemana, 2) }}</p>
    </div>
    <div class="lq-card" style="padding:1.1rem">
        <p class="lq-stat-label">Comisión total (8%)</p>
        <p class="lq-stat-val lq-comision">${{ number_format($totalComision, 2) }}</p>
    </div>
    <div class="lq-card" style="padding:1.1rem">
        <p class="lq-stat-label">Total anticipos</p>
        <p class="lq-stat-val lq-anticipo">${{ number_format($totalAnticipos, 2) }}</p>
    </div>
    <div class="lq-card" style="padding:1.1rem">
        <p class="lq-stat-label">Neto a pagar</p>
        <p class="lq-stat-val {{ $totalNeto >= 0 ? 'lq-neto-pos' : 'lq-neto-neg' }}">${{ number_format($totalNeto, 2) }}</p>
    </div>
</div>

{{-- Registrar anticipo --}}
<div class="lq-card" style="margin-bottom:1.5rem">
    <div style="padding:0.875rem 1.25rem;font-size:0.82rem;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0">
        Registrar anticipo de sueldo
    </div>
    <div class="lq-anticipo-form">
        <div style="flex:1;min-width:160px">
            <label class="lq-stat-label" style="display:block;margin-bottom:0.25rem">Cobrador</label>
            <select wire:model="anticipo_cobrador_id" class="lq-input">
                <option value="">— Seleccionar —</option>
                @foreach($cobradores as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }} {{ $c->apellido }}</option>
                @endforeach
            </select>
        </div>
        <div style="width:130px">
            <label class="lq-stat-label" style="display:block;margin-bottom:0.25rem">Monto ($)</label>
            <input type="number" wire:model="anticipo_monto" class="lq-input" placeholder="0.00" step="0.01" min="0" />
        </div>
        <div style="flex:2;min-width:180px">
            <label class="lq-stat-label" style="display:block;margin-bottom:0.25rem">Descripción</label>
            <input type="text" wire:model="anticipo_descripcion" class="lq-input" placeholder="Ej: Vale lunes" />
        </div>
        <button wire:click="registrarAnticipo" class="lq-btn lq-btn-green">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Agregar
        </button>
    </div>
</div>

{{-- Cards por cobrador --}}
@forelse($liquidacion as $r)
    @php $c = $r['cobrador']; @endphp
    <div class="lq-card">
        {{-- Header cobrador --}}
        <div class="lq-header">
            <div>
                <p class="lq-cobrador-name">{{ $c->nombre }} {{ $c->apellido }}</p>
                <p class="lq-cobrador-sub">
                    {{ \Carbon\Carbon::parse($this->semana_inicio)->format('d/m') }} — {{ $semanaFin->format('d/m/Y') }}
                </p>
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                <div style="text-align:right">
                    <p class="lq-stat-label">Cobrado</p>
                    <p class="lq-val lq-cobrado">${{ number_format($r['total_cobrado'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lq-stat-label">Comisión 8%</p>
                    <p class="lq-val lq-comision">${{ number_format($r['comision'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lq-stat-label">Anticipos</p>
                    <p class="lq-val lq-anticipo">${{ number_format($r['total_anticipos'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lq-stat-label">Neto</p>
                    <p class="lq-val-lg {{ $r['neto'] >= 0 ? 'lq-neto-pos' : 'lq-neto-neg' }}">
                        ${{ number_format($r['neto'], 2) }}
                    </p>
                </div>
                @if($r['anticipos']->where('estado', 'pendiente')->isNotEmpty())
                    <button wire:click="liquidarSemana({{ $c->id }})"
                            wire:confirm="¿Marcar todos los anticipos de {{ $c->nombre }} como descontados?"
                            class="lq-btn lq-btn-green">
                        ✓ Liquidar
                    </button>
                @endif
            </div>
        </div>

        {{-- Cobros por día --}}
        @if($r['por_dia']->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead class="lq-thead">
                        <tr>
                            <th>Día</th>
                            <th>Fecha</th>
                            <th>Clientes</th>
                            <th>Cobrado</th>
                            <th>Comisión (8%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($r['por_dia'] as $dia)
                            @php
                                $fecha = \Carbon\Carbon::parse($dia->dia);
                                $diaLabel = $diasEs[$fecha->format('D')] ?? $fecha->format('D');
                            @endphp
                            <tr class="lq-tr">
                                <td class="lq-td lq-dia-name">{{ $diaLabel }}</td>
                                <td class="lq-td lq-dia-fecha">{{ $fecha->format('d/m/Y') }}</td>
                                <td class="lq-td">{{ $dia->clientes }}</td>
                                <td class="lq-td lq-cobrado" style="font-weight:700">${{ number_format($dia->total, 2) }}</td>
                                <td class="lq-td lq-comision" style="font-weight:700">${{ number_format($dia->total * 0.08, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Anticipos --}}
        @if($r['anticipos']->isNotEmpty())
            <div class="lq-anticipo-section">
                <p class="lq-anticipo-title">Anticipos esta semana</p>
                @foreach($r['anticipos'] as $ant)
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;padding:0.2rem 0">
                        <span class="lq-anticipo-desc">
                            {{ $ant->fecha->format('d/m') }}
                            @if($ant->descripcion) — {{ $ant->descripcion }} @endif
                        </span>
                        <span class="lq-anticipo" style="font-weight:700">
                            ${{ number_format($ant->monto, 2) }}
                            @if($ant->estado === 'descontado')
                                <span class="lq-descontado">✓ descontado</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div style="text-align:center;padding:3rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;color:#94a3b8">
        Sin cobros registrados para esta semana
    </div>
@endforelse

</x-filament-panels::page>
