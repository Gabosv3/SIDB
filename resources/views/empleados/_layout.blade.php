@php $config = \App\Models\ConfiguracionSistema::instance(); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Empleados') — {{ $config->app_name }}</title>
    @if($config->favicon)
        <link rel="icon" href="{{ asset('storage/' . $config->favicon) }}" type="image/x-icon">
    @endif
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script>
        (function () {
            if (localStorage.getItem('sidb-theme') === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

        :root {
            --bg:#f3f4f6; --card:#fff; --subtle:#f9fafb; --border:#e5e7eb; --border-2:#f3f4f6;
            --text:#1a1a2e; --text-2:#374151; --muted:#6b7280; --muted-2:#9ca3af;
        }
        html.dark {
            --bg:#15151a; --card:#1e1e24; --subtle:#252530; --border:#2e2e3a; --border-2:#2a2a35;
            --text:#f1f5f9; --text-2:#cbd5e1; --muted:#94a3b8; --muted-2:#64748b;
        }
        html, body { min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); font-size:14px; }
        [x-cloak] { display:none !important; }

        /* ── Topbar ── */
        .pe-topbar {
            height:60px; background:var(--card); border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            padding:0 1.5rem; gap:1rem; position:sticky; top:0; z-index:200;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        html.dark .pe-topbar { box-shadow:none; }
        .pe-topbar-left { display:flex; align-items:center; gap:.9rem; min-width:0; }
        .pe-hamburger { color:var(--muted); cursor:pointer; flex-shrink:0; background:none; border:none; display:flex; }
        .pe-breadcrumb { font-size:.82rem; color:var(--muted-2); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .pe-breadcrumb b { color:var(--text-2); font-weight:600; }
        .pe-breadcrumb a { color:var(--muted-2); text-decoration:none; }
        .pe-breadcrumb a:hover { color:var(--text-2); }

        .pe-search-wrap { flex:1; max-width:380px; position:relative; }
        .pe-search { width:100%; padding:.5rem .875rem .5rem 2.25rem; background:var(--subtle); border:1px solid var(--border); border-radius:.625rem; font-size:13px; color:var(--text-2); outline:none; transition:all .15s; }
        .pe-search:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); background:var(--card); }
        .pe-search-icon { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:var(--muted-2); pointer-events:none; }

        .pe-topbar-right { display:flex; align-items:center; gap:1rem; flex-shrink:0; }
        .pe-bell { position:relative; cursor:pointer; color:var(--muted); }
        .pe-bell-badge {
            position:absolute; top:-4px; right:-4px; width:16px; height:16px;
            background:#ef4444; color:white; border-radius:50%; font-size:9px;
            font-weight:700; display:flex; align-items:center; justify-content:center;
            border:2px solid var(--card);
        }
        .pe-user-pill { display:flex; align-items:center; gap:.5rem; padding:.35rem .75rem .35rem .4rem; background:var(--subtle); border:1px solid var(--border); border-radius:9999px; cursor:pointer; transition:background .15s; text-decoration:none; }
        .pe-user-pill:hover { background:var(--border); }
        .pe-avatar { width:28px; height:28px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:50%; color:white; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .pe-user-name { font-size:12px; font-weight:600; color:var(--text-2); }

        .theme-toggle { width:36px; height:36px; border-radius:50%; border:1px solid var(--border); background:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--muted); flex-shrink:0; }
        .theme-toggle:hover { background:var(--subtle); }
        .theme-toggle svg { width:18px; height:18px; }
        .theme-toggle .icon-sun { display:none; }
        html.dark .theme-toggle .icon-moon { display:none; }
        html.dark .theme-toggle .icon-sun { display:block; }

        /* ── Body ── */
        .pe-body { padding:1.5rem; max-width:1440px; margin:0 auto; }

        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }

        html.dark .leaflet-popup-content-wrapper, html.dark .leaflet-popup-tip { background:var(--card); color:var(--text); }
        html.dark .leaflet-bar a { background:var(--card); border-color:var(--border); color:var(--text); }
    </style>
    @yield('styles')
</head>
<body>

<div class="pe-topbar">
    <div class="pe-topbar-left">
        <button type="button" class="pe-hamburger" onclick="history.back()" title="Volver">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="pe-breadcrumb">
            <a href="/administrativo">Usuarios</a> / <a href="/administrativo">Empleados</a> / <b>@yield('breadcrumb-current', 'Detalle del empleado')</b>
        </div>
    </div>

    <div class="pe-search-wrap">
        <svg class="pe-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="pe-search" placeholder="Buscar empleados, clientes, rutas...">
    </div>

    <div class="pe-topbar-right">
        <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
            <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>
        <div class="pe-bell">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <a href="/administrativo" class="pe-user-pill">
            <div class="pe-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <span class="pe-user-name">{{ auth()->user()->name }}</span>
        </a>
    </div>
</div>

<div class="pe-body">
    @yield('content')
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function toggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('sidb-theme', isDark ? 'dark' : 'light');
    }

    // Intercepta cualquier <form data-confirm="..."> y muestra un SweetAlert2 en vez del confirm() nativo del navegador.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        e.preventDefault();
        Swal.fire({
            title: form.dataset.confirmTitle || '¿Estás seguro?',
            text: form.dataset.confirm,
            icon: form.dataset.confirmIcon || 'warning',
            showCancelButton: true,
            confirmButtonText: form.dataset.confirmButton || 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: form.dataset.confirmColor || '#dc2626',
            reverseButtons: true,
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
</script>
@yield('scripts')
</body>
</html>
