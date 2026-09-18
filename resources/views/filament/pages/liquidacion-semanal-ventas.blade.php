<x-filament-panels::page>
<style>
    .lqv-input {
        border:1px solid #d1d5db; border-radius:0.5rem;
        padding:0.5rem 0.75rem; font-size:0.875rem;
        color:#111827; background:#fff; outline:none; transition:border-color .15s; width:100%;
    }
    .lqv-input:focus { border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.2); }
    .lqv-card { background:#fff; border:1px solid #e5e7eb; border-radius:0.75rem; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; margin-bottom:1.25rem; }
    .lqv-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .lqv-stat-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; margin-bottom:0.15rem; }
    .lqv-stat-val   { font-size:1.4rem; font-weight:800; color:#0f172a; }
    .lqv-thead th { padding:0.55rem 0.75rem; text-align:left; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; background:#f9fafb; border-bottom:1px solid #e5e7eb; }
    .lqv-thead th:last-child { text-align:right; padding-right:1.25rem; }
    .lqv-tr { border-bottom:1px solid #f3f4f6; }
    .lqv-tr:hover { background:#f9fafb; }
    .lqv-td { padding:0.55rem 0.75rem; font-size:0.82rem; color:#374151; }
    .lqv-td:last-child { text-align:right; font-weight:700; padding-right:1.25rem; }
    .lqv-btn { display:inline-flex; align-items:center; gap:0.4rem; font-size:0.75rem; font-weight:600; border-radius:0.5rem; padding:0.45rem 1rem; cursor:pointer; border:none; transition:all .15s; }
    .lqv-btn-green  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    .lqv-btn-green:hover { background:#bbf7d0; }
    .lqv-anticipo-form { padding:1rem 1.25rem; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; }

    .lqv-vendedor-name { font-size:1rem; font-weight:700; color:#0f172a; }
    .lqv-vendedor-sub  { font-size:0.78rem; color:#64748b; margin-top:0.1rem; }

    .lqv-val        { font-size:1.2rem; font-weight:800; color:#0f172a; }
    .lqv-val-lg     { font-size:1.4rem; font-weight:800; color:#0f172a; }
    .lqv-vendido    { color:#15803d; }
    .lqv-comision   { color:#1d4ed8; }
    .lqv-anticipo   { color:#b45309; }
    .lqv-neto-pos   { color:#15803d; }
    .lqv-neto-neg   { color:#dc2626; }
    .lqv-dia-name   { font-weight:600; color:#334155; }
    .lqv-dia-fecha  { color:#94a3b8; }

    .lqv-badge { display:inline-flex; align-items:center; padding:0.15rem 0.55rem; border-radius:9999px; font-size:0.68rem; font-weight:700; }
    .lqv-badge-salario_fijo { background:#e0f2fe; color:#0369a1; }
    .lqv-badge-comision     { background:#ede9fe; color:#5b21b6; }
    .lqv-badge-mixto        { background:#fef9c3; color:#854d0e; }

    .dark .lqv-vendedor-name { color:#f1f5f9; }
    .dark .lqv-vendedor-sub  { color:#4b5563; }
    .dark .lqv-stat-label  { color:#475569; }
    .dark .lqv-stat-val    { color:#f1f5f9; }
    .dark .lqv-val         { color:#e2e8f0; }
    .dark .lqv-val-lg      { color:#e2e8f0; }
    .dark .lqv-vendido     { color:#34d399; }
    .dark .lqv-comision    { color:#60a5fa; }
    .dark .lqv-anticipo    { color:#fbbf24; }
    .dark .lqv-neto-pos    { color:#4ade80; }
    .dark .lqv-neto-neg    { color:#f87171; }
    .dark .lqv-dia-name    { color:#cbd5e1; }
    .dark .lqv-dia-fecha   { color:#475569; }
    .dark .lqv-badge-salario_fijo { background:rgba(2,132,199,.18); color:#38bdf8; }
    .dark .lqv-badge-comision     { background:rgba(124,58,237,.18); color:#c4b5fd; }
    .dark .lqv-badge-mixto        { background:rgba(202,138,4,.18); color:#fde047; }

    .dark .lqv-input  { background:#2a2a35; border-color:#3f3f50; color:#f3f4f6; }
    .dark .lqv-card   { background:#1e1e24; border-color:#2e2e3a; box-shadow:none; }
    .dark .lqv-header { background:#252530; border-bottom-color:#2e2e3a; }
    .dark .lqv-thead th   { background:#252530; border-bottom-color:#2e2e3a; color:#475569; }
    .dark .lqv-tr  { border-bottom-color:#2a2a35; }
    .dark .lqv-tr:hover { background:#252530; }
    .dark .lqv-td  { color:#94a3b8; }
    .dark .lqv-anticipo-form { background:#1a1a22; border-top-color:#2e2e3a; }
    .dark .lqv-btn-green { background:rgba(21,128,61,.15); color:#4ade80; border-color:rgba(74,222,128,.2); }
    .dark .lqv-btn-green:hover { background:rgba(21,128,61,.3); }

    .lqv-anticipo-section { padding:0.75rem 1.25rem; background:#fffbeb; border-top:1px solid #fde68a; }
    .lqv-anticipo-title   { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#92400e; margin-bottom:0.5rem; }
    .lqv-anticipo-desc    { color:#78350f; }
    .lqv-descontado       { font-size:0.65rem; color:#16a34a; margin-left:6px; }
    .dark .lqv-anticipo-section { background:rgba(120,53,15,.1); border-top-color:rgba(253,230,138,.1); }
    .dark .lqv-anticipo-title   { color:#fbbf24; }
    .dark .lqv-anticipo-desc    { color:#94a3b8; }
    .dark .lqv-descontado       { color:#4ade80; }
</style>

@php
    $liquidacion = $this->getLiquidacion();
    $vendedores  = $this->getVendedores();
    $semanaFin   = $this->getSemanaFin();
    $diasEs = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];
    $modalidadLabels = ['salario_fijo' => 'Salario fijo', 'comision' => 'Comisión', 'mixto' => 'Mixto'];

    $totalSemana        = collect($liquidacion)->sum('total_vendido');
    $totalAPagar        = collect($liquidacion)->sum('a_pagar');
    $totalAnticipos     = collect($liquidacion)->sum('total_anticipos');
    $totalValesConsumo  = collect($liquidacion)->sum('total_vales_consumo');
    $totalNeto          = collect($liquidacion)->sum('neto');
@endphp

{{-- Filtros --}}
<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:1.5rem">
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Semana (selecciona el lunes)</label>
        <input type="date" wire:model.live="semana_inicio" class="lqv-input" style="width:180px" />
    </div>
    <div>
        <label style="display:block;font-size:0.75rem;font-weight:500;color:#6b7280;margin-bottom:0.25rem">Vendedor</label>
        <select wire:model.live="vendedor_id" class="lqv-input" style="min-width:200px">
            <option value="">Todos los vendedores</option>
            @foreach($vendedores as $v)
                <option value="{{ $v->id }}">{{ $v->nombre }} {{ $v->apellido }}</option>
            @endforeach
        </select>
    </div>
    <div style="font-size:0.8rem;color:#64748b;align-self:center;padding-bottom:0.15rem">
        {{ \Carbon\Carbon::parse($this->semana_inicio)->format('d/m/Y') }} — {{ $semanaFin->format('d/m/Y') }}
    </div>
</div>

{{-- Totales generales --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.75rem">
    <div class="lqv-card" style="padding:1.1rem">
        <p class="lqv-stat-label">Total vendido</p>
        <p class="lqv-stat-val lqv-vendido">${{ number_format($totalSemana, 2) }}</p>
    </div>
    <div class="lqv-card" style="padding:1.1rem">
        <p class="lqv-stat-label">Total a pagar</p>
        <p class="lqv-stat-val lqv-comision">${{ number_format($totalAPagar, 2) }}</p>
    </div>
    <div class="lqv-card" style="padding:1.1rem">
        <p class="lqv-stat-label">Total anticipos</p>
        <p class="lqv-stat-val lqv-anticipo">${{ number_format($totalAnticipos, 2) }}</p>
    </div>
    <div class="lqv-card" style="padding:1.1rem">
        <p class="lqv-stat-label">Vales de consumo (aprobados)</p>
        <p class="lqv-stat-val lqv-anticipo">${{ number_format($totalValesConsumo, 2) }}</p>
    </div>
    <div class="lqv-card" style="padding:1.1rem">
        <p class="lqv-stat-label">Neto a pagar</p>
        <p class="lqv-stat-val {{ $totalNeto >= 0 ? 'lqv-neto-pos' : 'lqv-neto-neg' }}">${{ number_format($totalNeto, 2) }}</p>
    </div>
</div>

{{-- Registrar anticipo --}}
<div class="lqv-card" style="margin-bottom:1.5rem">
    <div style="padding:0.875rem 1.25rem;font-size:0.82rem;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0">
        {{ $anticipo_editando_id ? 'Editando anticipo' : 'Registrar anticipo de sueldo' }}
    </div>
    <div class="lqv-anticipo-form">
        <div style="flex:1;min-width:160px">
            <label class="lqv-stat-label" style="display:block;margin-bottom:0.25rem">Vendedor</label>
            <select wire:model="anticipo_vendedor_id" class="lqv-input">
                <option value="">— Seleccionar —</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}">{{ $v->nombre }} {{ $v->apellido }}</option>
                @endforeach
            </select>
        </div>
        <div style="width:130px">
            <label class="lqv-stat-label" style="display:block;margin-bottom:0.25rem">Monto ($)</label>
            <input type="number" wire:model="anticipo_monto" class="lqv-input" placeholder="0.00" step="0.01" min="0" />
        </div>
        <div style="flex:2;min-width:180px">
            <label class="lqv-stat-label" style="display:block;margin-bottom:0.25rem">Descripción</label>
            <input type="text" wire:model="anticipo_descripcion" class="lqv-input" placeholder="Ej: Vale lunes" />
        </div>
        @if($anticipo_requiere_password)
            <div style="flex:1;min-width:160px">
                <label class="lqv-stat-label" style="display:block;margin-bottom:0.25rem;color:#dc2626">Tu contraseña (supera lo ganado)</label>
                <input type="password" wire:model="anticipo_password" class="lqv-input" placeholder="••••••••" style="border-color:#dc2626" />
            </div>
        @endif
        <button wire:click="registrarAnticipo" class="lqv-btn lqv-btn-green">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            {{ $anticipo_requiere_password ? 'Confirmar' : ($anticipo_editando_id ? 'Guardar cambios' : 'Agregar') }}
        </button>
        @if($anticipo_editando_id)
            <button wire:click="cancelarEdicionAnticipo" type="button" class="lqv-btn" style="background:#f1f5f9;color:#475569">
                Cancelar
            </button>
        @endif
    </div>
    @if($anticipo_requiere_password)
        <div style="padding:0 1.25rem 0.875rem;font-size:0.75rem;color:#dc2626">
            ⚠ Este anticipo es mayor a lo que el vendedor lleva ganado esa semana. Ingresa tu contraseña para confirmarlo.
        </div>
    @endif
</div>

{{-- Cards por vendedor --}}
@forelse($liquidacion as $r)
    @php $v = $r['vendedor']; @endphp
    <div class="lqv-card">
        <div class="lqv-header">
            <div>
                <p class="lqv-vendedor-name">
                    {{ $v->nombre }} {{ $v->apellido }}
                    <span class="lqv-badge lqv-badge-{{ $r['modalidad'] }}">{{ $modalidadLabels[$r['modalidad']] ?? $r['modalidad'] }}</span>
                </p>
                <p class="lqv-vendedor-sub">
                    {{ \Carbon\Carbon::parse($this->semana_inicio)->format('d/m') }} — {{ $semanaFin->format('d/m/Y') }}
                </p>
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center">
                <div style="text-align:right">
                    <p class="lqv-stat-label">Vendido</p>
                    <p class="lqv-val lqv-vendido">${{ number_format($r['total_vendido'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lqv-stat-label">
                        @if($r['modalidad'] === 'salario_fijo')
                            Salario base
                        @else
                            A pagar ({{ $r['porcentaje_comision'] }}%@if($r['modalidad'] === 'mixto') + salario @endif)
                        @endif
                    </p>
                    <p class="lqv-val lqv-comision">${{ number_format($r['a_pagar'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lqv-stat-label">Anticipos</p>
                    <p class="lqv-val lqv-anticipo">${{ number_format($r['total_anticipos'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lqv-stat-label">Vale consumo</p>
                    <p class="lqv-val lqv-anticipo">${{ number_format($r['total_vales_consumo'], 2) }}</p>
                </div>
                <div style="text-align:right">
                    <p class="lqv-stat-label">Neto</p>
                    <p class="lqv-val-lg {{ $r['neto'] >= 0 ? 'lqv-neto-pos' : 'lqv-neto-neg' }}">
                        ${{ number_format($r['neto'], 2) }}
                    </p>
                </div>
                @if($r['anticipos']->where('estado', 'pendiente')->isNotEmpty())
                    <button wire:click="liquidarSemana({{ $v->id }})"
                            wire:confirm="¿Marcar todos los anticipos de {{ $v->nombre }} como descontados?"
                            class="lqv-btn lqv-btn-green">
                        ✓ Liquidar
                    </button>
                @endif
            </div>
        </div>

        {{-- Ventas por día --}}
        @if($r['por_dia']->isNotEmpty())
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead class="lqv-thead">
                        <tr>
                            <th>Día</th>
                            <th>Fecha</th>
                            <th>Ventas</th>
                            <th>Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($r['por_dia'] as $dia)
                            @php
                                $fecha = \Carbon\Carbon::parse($dia->dia);
                                $diaLabel = $diasEs[$fecha->format('D')] ?? $fecha->format('D');
                            @endphp
                            <tr class="lqv-tr">
                                <td class="lqv-td lqv-dia-name">{{ $diaLabel }}</td>
                                <td class="lqv-td lqv-dia-fecha">{{ $fecha->format('d/m/Y') }}</td>
                                <td class="lqv-td">{{ $dia->ventas }}</td>
                                <td class="lqv-td lqv-vendido" style="font-weight:700">${{ number_format($dia->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Anticipos --}}
        @if($r['anticipos']->isNotEmpty())
            <div class="lqv-anticipo-section">
                <p class="lqv-anticipo-title">Anticipos esta semana</p>
                @foreach($r['anticipos'] as $ant)
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;padding:0.2rem 0">
                        <span class="lqv-anticipo-desc">
                            {{ $ant->fecha->format('d/m') }}
                            @if($ant->descripcion) — {{ $ant->descripcion }} @endif
                        </span>
                        <span style="display:flex;align-items:center;gap:0.5rem">
                            <span class="lqv-anticipo" style="font-weight:700">
                                ${{ number_format($ant->monto, 2) }}
                                @if($ant->estado === 'descontado')
                                    <span class="lqv-descontado">✓ descontado</span>
                                @endif
                            </span>
                            @if($ant->estado === 'pendiente')
                                <button
                                    type="button"
                                    wire:click="editarAnticipo({{ $ant->id }})"
                                    style="background:none;border:none;padding:0;cursor:pointer;font:inherit;font-size:0.72rem;color:#0369a1"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    wire:click="eliminarAnticipo({{ $ant->id }})"
                                    wire:confirm="¿Eliminar este anticipo de ${{ number_format($ant->monto, 2) }}?"
                                    style="background:none;border:none;padding:0;cursor:pointer;font:inherit;font-size:0.72rem;color:#dc2626"
                                >
                                    Eliminar
                                </button>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div style="text-align:center;padding:3rem;background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;color:#94a3b8">
        Sin ventas registradas para esta semana
    </div>
@endforelse

</x-filament-panels::page>
