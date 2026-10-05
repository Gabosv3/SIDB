<x-filament-panels::page>
<style>
    .rm-h { font-size:1.05rem; font-weight:700; margin:1.4rem 0 .5rem; padding-bottom:.25rem; border-bottom:2px solid #c0394b; color:#111827; }
    .dark .rm-h { color:#f3f4f6; }
    .rm-table { width:100%; border-collapse:collapse; margin-bottom:.8rem; font-size:.85rem; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; }
    .dark .rm-table { background:#1e1e24; border-color:#2e2e3a; }
    .rm-table th { text-align:left; padding:.45rem .7rem; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid #e5e7eb; background:#f9fafb; }
    .dark .rm-table th { background:#252530; border-color:#2e2e3a; color:#9ca3af; }
    .rm-table td { padding:.45rem .7rem; border-bottom:1px solid #f3f4f6; color:#374151; }
    .dark .rm-table td { border-color:#2a2a35; color:#d1d5db; }
    .rm-table .n { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .rm-table .vacio { color:#9ca3af; text-align:center; }
    .rm-table tr.total td { font-weight:700; background:#f9fafb; }
    .dark .rm-table tr.total td { background:#252530; }
    .rm-kpis td { text-align:center; padding:.8rem .5rem; border-bottom:none; }
    .rm-kpis td span { display:block; font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
    .rm-kpis td b { display:block; font-size:1.2rem; color:#16a34a; margin-top:.15rem; }
    .rm-nota { font-size:.75rem; color:#6b7280; margin-top:.4rem; }
    .rm-input { border:1px solid #d1d5db; border-radius:.5rem; padding:.5rem .75rem; font-size:.875rem; background:#fff; color:#111827; }
    .dark .rm-input { background:#2a2a35; border-color:#3f3f50; color:#f3f4f6; }
    .rm-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .9rem; border-radius:.5rem; background:#c0394b; color:#fff; font-size:.85rem; font-weight:600; text-decoration:none; }
</style>

@php($r = $this->getResumen())

<div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-bottom:.5rem">
    <div>
        <label style="display:block;font-size:.75rem;font-weight:500;color:#6b7280;margin-bottom:.25rem">Mes</label>
        <input type="month" wire:model.live="mes" class="rm-input">
    </div>
    <a href="{{ $this->getPdfUrl() }}" target="_blank" class="rm-btn">Descargar PDF</a>
    <span style="font-size:.85rem;color:#6b7280">{{ ucfirst($r['mes']->locale('es')->translatedFormat('F Y')) }}</span>
</div>

@include('partials.resumen-mensual-tablas', ['r' => $r])
</x-filament-panels::page>
