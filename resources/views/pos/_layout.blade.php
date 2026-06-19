@php $config = \App\Models\ConfiguracionSistema::instance(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Monitor POS') — {{ $config->app_name }}</title>
    @if($config->favicon)
        <link rel="icon" href="{{ asset('storage/' . $config->favicon) }}" type="image/x-icon">
    @endif
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        html, body { min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f0f2f5; color:#1a1a2e; font-size:14px; }

        /* ── Topbar ── */
        .pm-topbar {
            height:60px; background:#fff; border-bottom:1px solid #e5e7eb;
            display:flex; align-items:center; justify-content:space-between;
            padding:0 1.5rem; gap:1rem; position:sticky; top:0; z-index:200;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        .pm-brand { display:flex; align-items:center; gap:.625rem; text-decoration:none; flex-shrink:0; }
        .pm-brand-icon {
            width:34px; height:34px; background:linear-gradient(135deg,#10b981,#059669);
            border-radius:.625rem; display:flex; align-items:center; justify-content:center;
        }
        .pm-brand-icon svg { width:18px; height:18px; }
        .pm-brand-name { font-size:15px; font-weight:700; color:#111827; }

        /* ── Live badge ── */
        .pm-live { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .6rem; background:#dcfce7; border-radius:9999px; font-size:.65rem; font-weight:700; color:#166534; }
        .pm-live-dot { width:6px; height:6px; background:#16a34a; border-radius:50%; animation:pm-pulse 1.4s infinite; }
        @keyframes pm-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.7)} }
        .pm-live.error { background:#fee2e2; color:#991b1b; }
        .pm-live.error .pm-live-dot { background:#dc2626; animation:none; }

        .pm-search-wrap { flex:1; max-width:420px; position:relative; }
        .pm-search { width:100%; padding:.5rem .875rem .5rem 2.25rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:.625rem; font-size:13px; color:#374151; outline:none; transition:all .15s; }
        .pm-search:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); background:#fff; }
        .pm-search-icon { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#9ca3af; pointer-events:none; }

        .pm-topbar-right { display:flex; align-items:center; gap:1rem; flex-shrink:0; }
        .pm-date { font-size:12px; color:#6b7280; font-weight:500; }
        .pm-bell { position:relative; cursor:pointer; color:#6b7280; }
        .pm-bell-badge {
            position:absolute; top:-4px; right:-4px; width:16px; height:16px;
            background:#ef4444; color:white; border-radius:50%; font-size:9px;
            font-weight:700; display:flex; align-items:center; justify-content:center;
            border:2px solid #fff;
        }
        .pm-user-pill { display:flex; align-items:center; gap:.5rem; padding:.35rem .75rem .35rem .4rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:9999px; cursor:pointer; transition:background .15s; text-decoration:none; }
        .pm-user-pill:hover { background:#f3f4f6; }
        .pm-avatar { width:28px; height:28px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:50%; color:white; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; }
        .pm-user-name { font-size:12px; font-weight:600; color:#374151; }

        /* ── Body ── */
        .pm-body { padding:1.5rem; max-width:1440px; margin:0 auto; }

        /* ── Page header ── */
        .pm-page-header { margin-bottom:1.25rem; }
        .pm-page-header h1 { font-size:1.375rem; font-weight:700; color:#111827; }
        .pm-page-header p  { font-size:.8rem; color:#6b7280; margin-top:.2rem; }

        /* ── Cards ── */
        .pm-card { background:#fff; border:1px solid #e5e7eb; border-radius:.875rem; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .pm-card-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:.75rem 1.1rem; border-bottom:1px solid #f3f4f6;
        }
        .pm-card-title { font-size:.8rem; font-weight:700; color:#111827; }
        .pm-card-link  { font-size:.72rem; font-weight:600; color:#10b981; text-decoration:none; }
        .pm-card-link:hover { text-decoration:underline; }

        /* ── Stat cards ── */
        .pm-stat { display:flex; align-items:center; gap:.875rem; padding:1.125rem; }
        .pm-stat-icon { width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .pm-stat-label { font-size:.72rem; font-weight:600; color:#6b7280; }
        .pm-stat-num   { font-size:1.75rem; font-weight:800; line-height:1.1; color:#111827; margin-top:.1rem; }
        .pm-stat-sub   { font-size:.7rem; color:#9ca3af; margin-top:.2rem; }

        /* ── Table ── */
        .pm-table-wrap { overflow-x:auto; }
        .pm-table { width:100%; border-collapse:collapse; }
        .pm-thead th { padding:.55rem .875rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; background:#f9fafb; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .pm-thead th:first-child { padding-left:1.1rem; }
        .pm-tr { border-bottom:1px solid #f3f4f6; transition:background .1s; }
        .pm-tr:hover { background:#fafafa; }
        .pm-td { padding:.6rem .875rem; font-size:.82rem; color:#374151; white-space:nowrap; vertical-align:middle; }
        .pm-td:first-child { padding-left:1.1rem; }

        /* ── Status badge ── */
        .pm-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .6rem; border-radius:9999px; font-size:.68rem; font-weight:700; }
        .pm-dot   { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

        /* ── Battery ── */
        .pm-bat-wrap { display:inline-flex; align-items:center; gap:.35rem; }
        .pm-bat-bar  { width:48px; height:6px; background:#e5e7eb; border-radius:3px; overflow:hidden; }
        .pm-bat-fill { height:100%; border-radius:3px; }
        .pm-bat-pct  { font-size:.7rem; color:#6b7280; min-width:24px; }

        /* ── Alert item ── */
        .pm-alert-item { display:flex; align-items:center; gap:.625rem; padding:.6rem 1.1rem; border-bottom:1px solid #f9fafb; }
        .pm-alert-icon { width:32px; height:32px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .pm-alert-name { font-size:.78rem; font-weight:600; color:#111827; }
        .pm-alert-desc { font-size:.68rem; color:#9ca3af; }
        .pm-alert-time { font-size:.65rem; color:#9ca3af; white-space:nowrap; margin-left:auto; padding-left:.5rem; }

        /* ── Metric card ── */
        .pm-metric { padding:1rem 1.1rem; }
        .pm-metric-label { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; }
        .pm-metric-num   { font-size:1.5rem; font-weight:800; color:#111827; margin:.2rem 0; }
        .pm-metric-delta { font-size:.72rem; font-weight:600; }
        .pm-delta-up    { color:#10b981; }
        .pm-delta-down  { color:#ef4444; }

        /* ── Loc link ── */
        .pm-loc { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; color:#6366f1; font-weight:600; text-decoration:none; }

        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:#e5e7eb; border-radius:3px; }
    </style>
    @yield('styles')
</head>
<body>

<div class="pm-topbar">
    <a href="{{ route('pos.monitor', $tenant) }}" class="pm-brand">
        <div class="pm-brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                <rect x="5" y="2" width="14" height="20" rx="2"/>
                <line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>
        </div>
        <span class="pm-brand-name">{{ $config->app_name }}</span>
    </a>

    <div class="pm-search-wrap">
        <svg class="pm-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="pm-search" placeholder="Buscar POS, usuario o ubicación..." id="pm-search-input">
    </div>

    <div class="pm-topbar-right">
        <span class="pm-date">{{ now()->isoFormat('D [de] MMMM, YYYY') }}</span>
        <span class="pm-live" id="pm-live-badge">
            <span class="pm-live-dot"></span>
            En vivo
        </span>
        <div class="pm-bell">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            @if(isset($alertasCount) && $alertasCount > 0)
                <span class="pm-bell-badge" id="pm-bell-badge">{{ $alertasCount }}</span>
            @else
                <span class="pm-bell-badge" id="pm-bell-badge" style="display:none;">0</span>
            @endif
        </div>
        <a href="/administrativo" class="pm-user-pill">
            <div class="pm-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <span class="pm-user-name">{{ auth()->user()->name }}</span>
        </a>
    </div>
</div>

<div class="pm-body">
    @yield('content')
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@yield('scripts')
<script>
    // Búsqueda en tabla
    document.getElementById('pm-search-input')?.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.pm-tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>
</body>
</html>
