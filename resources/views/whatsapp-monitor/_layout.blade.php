@php $config = \App\Models\ConfiguracionSistema::instance(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'WhatsApp Monitor') — {{ $config->app_name }}</title>
    @if($config->favicon)
        <link rel="icon" href="{{ asset('storage/' . $config->favicon) }}" type="image/x-icon">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

        :root {
            --wm-bg:#F6F8FB; --wm-sidebar:#18212F; --wm-green:#22C55E; --wm-blue:#2563EB;
            --wm-text:#1F2937; --wm-gray:#6B7280; --wm-card:#FFFFFF; --wm-border:#E5E7EB;
        }

        html, body { min-height:100vh; font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background:var(--wm-bg); color:var(--wm-text); font-size:14px; }

        .wm-shell { display:flex; min-height:100vh; }

        /* ── Sidebar ── */
        .wm-sidebar { width:234px; flex-shrink:0; background:var(--wm-sidebar); display:flex; flex-direction:column; position:sticky; top:0; height:100vh; }
        .wm-sidebar-brand { display:flex; align-items:center; gap:.65rem; padding:1.25rem 1.1rem; }
        .wm-sidebar-icon { width:38px; height:38px; background:var(--wm-green); border-radius:.7rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.15rem; }
        .wm-sidebar-title { font-size:.95rem; font-weight:700; color:#fff; line-height:1.2; }
        .wm-sidebar-sub { font-size:.68rem; font-weight:600; color:var(--wm-green); }

        .wm-nav { flex:1; padding:.5rem .7rem; display:flex; flex-direction:column; gap:.15rem; }
        .wm-nav-item {
            display:flex; align-items:center; gap:.7rem; padding:.65rem .8rem; border-radius:.6rem;
            color:#9CA3AF; text-decoration:none; font-size:.84rem; font-weight:500; transition:background .15s,color .15s;
        }
        .wm-nav-item:hover { background:rgba(255,255,255,.06); color:#fff; }
        .wm-nav-item.active { background:var(--wm-green); color:#fff; font-weight:600; }
        .wm-nav-item.disabled { cursor:default; opacity:.45; }
        .wm-nav-item.disabled:hover { background:none; color:#9CA3AF; }
        .wm-nav-icon { width:18px; text-align:center; flex-shrink:0; }
        .wm-nav-soon { margin-left:auto; font-size:.6rem; font-weight:700; background:rgba(255,255,255,.08); color:#9CA3AF; padding:.1rem .4rem; border-radius:9999px; }

        .wm-sidebar-footer { padding:.9rem 1.1rem; border-top:1px solid rgba(255,255,255,.08); display:flex; align-items:center; gap:.6rem; }
        .wm-avatar { width:34px; height:34px; border-radius:50%; background:var(--wm-blue); color:#fff; font-size:.8rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .wm-footer-name { font-size:.8rem; font-weight:600; color:#fff; line-height:1.2; }
        .wm-footer-role { font-size:.68rem; color:#9CA3AF; }

        /* ── Main ── */
        .wm-main { flex:1; min-width:0; display:flex; flex-direction:column; }
        .wm-topbar {
            height:60px; background:var(--wm-card); border-bottom:1px solid var(--wm-border);
            display:flex; align-items:center; justify-content:space-between; padding:0 1.5rem;
            position:sticky; top:0; z-index:50;
        }
        .wm-topbar-title { font-size:1.05rem; font-weight:700; color:var(--wm-text); }
        .wm-topbar-right { display:flex; align-items:center; gap:1.1rem; font-size:.8rem; color:var(--wm-gray); }
        .wm-live { display:inline-flex; align-items:center; gap:.35rem; color:#16a34a; font-weight:600; }
        .wm-live-dot { width:7px; height:7px; background:#22C55E; border-radius:50%; animation:wm-pulse 1.4s infinite; }
        @keyframes wm-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        .wm-body { padding:1.5rem; max-width:1600px; width:100%; margin:0 auto; flex:1; }

        /* ── Cards / stats ── */
        .wm-stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
        @media (max-width:1100px) { .wm-stat-grid { grid-template-columns:repeat(2,1fr); } }
        .wm-card { background:var(--wm-card); border:1px solid var(--wm-border); border-radius:.9rem; }
        .wm-stat-card { display:flex; align-items:center; gap:.9rem; padding:1.1rem 1.25rem; }
        .wm-stat-icon { width:48px; height:48px; border-radius:.75rem; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .wm-stat-label { font-size:.78rem; color:var(--wm-gray); font-weight:500; }
        .wm-stat-num { font-size:1.5rem; font-weight:800; color:var(--wm-text); margin-top:.1rem; }
        .wm-stat-sub { font-size:.7rem; color:var(--wm-gray); margin-top:.15rem; }

        .wm-empty { text-align:center; padding:2.5rem 1.25rem; color:var(--wm-gray); font-size:.84rem; }

        ::-webkit-scrollbar { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:3px; }
    </style>
    @yield('styles')
</head>
<body>

<div class="wm-shell">
    <aside class="wm-sidebar">
        <div class="wm-sidebar-brand">
            <div class="wm-sidebar-icon">💬</div>
            <div>
                <div class="wm-sidebar-title">WhatsApp Monitor</div>
                <div class="wm-sidebar-sub">Solo lectura</div>
            </div>
        </div>

        <nav class="wm-nav">
            <a href="{{ route('whatsapp-center.index', $tenant) }}" class="wm-nav-item {{ ($activo ?? '') === 'dashboard' ? 'active' : '' }}">
                <span class="wm-nav-icon">🏠</span> Dashboard
            </a>
            <a href="{{ route('whatsapp-center.conversaciones', $tenant) }}" class="wm-nav-item {{ ($activo ?? '') === 'conversaciones' ? 'active' : '' }}">
                <span class="wm-nav-icon">💬</span> Conversaciones
            </a>
            <span class="wm-nav-item disabled"><span class="wm-nav-icon">📱</span> Dispositivos <span class="wm-nav-soon">Pronto</span></span>
            <span class="wm-nav-item disabled"><span class="wm-nav-icon">👥</span> Cobradores <span class="wm-nav-soon">Pronto</span></span>
            <span class="wm-nav-item disabled"><span class="wm-nav-icon">📊</span> Reportes <span class="wm-nav-soon">Pronto</span></span>
            <span class="wm-nav-item disabled"><span class="wm-nav-icon">⚙️</span> Configuración <span class="wm-nav-soon">Pronto</span></span>
        </nav>

        <div class="wm-sidebar-footer">
            <div class="wm-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div>
                <div class="wm-footer-name">{{ auth()->user()->name }}</div>
                <div class="wm-footer-role">Supervisor</div>
            </div>
        </div>
    </aside>

    <div class="wm-main">
        <div class="wm-topbar">
            <span class="wm-topbar-title">@yield('page-title', 'Dashboard')</span>
            <div class="wm-topbar-right">
                <span class="wm-live"><span class="wm-live-dot"></span> En línea</span>
                <span>{{ now()->isoFormat('D [de] MMMM, YYYY') }}</span>
                <span id="wm-clock"></span>
            </div>
        </div>

        <div class="wm-body">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@yield('scripts')
<script>
    function wmActualizarReloj() {
        var el = document.getElementById('wm-clock');
        if (el) el.textContent = new Date().toLocaleTimeString('es-SV', { hour12: false });
    }
    wmActualizarReloj();
    setInterval(wmActualizarReloj, 1000);
</script>
</body>
</html>
