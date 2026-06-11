@php
    $config = \App\Models\ConfiguracionSistema::instance();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'WhatsApp Center') - {{ $config->app_name }}</title>
    @if($config->favicon)
        <link rel="icon" href="{{ asset('storage/' . $config->favicon) }}" type="image/x-icon">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:       {{ $config->primary_color ?? '#25d366' }};
            --green-dark:  #128c7e;
            --bg:          #f0f2f5;
            --white:       #ffffff;
            --border:      #e9ecef;
            --text:        #1a1a2e;
            --muted:       #8696a0;
            --radius:      12px;
            --shadow:      0 2px 8px rgba(0,0,0,.08);
        }

        html, body { min-height: 100vh; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background: var(--bg); color: var(--text); font-size: 14px; }

        /* TOPBAR */
        .topbar { height:62px; background:var(--white); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; padding:0 1.5rem; gap:1rem; box-shadow:var(--shadow); position:sticky; top:0; z-index:100; }
        .topbar-left { display:flex; align-items:center; gap:1rem; }
        .brand { display:flex; align-items:center; gap:.6rem; text-decoration:none; }
        .brand-icon { width:36px; height:36px; background:var(--green); border-radius:50%; display:flex; align-items:center; justify-content:center; }
        .brand-icon svg { width:20px; height:20px; fill:white; }
        .brand-name { font-size:17px; font-weight:700; color:var(--text); }
        .brand-badge { background:var(--green); color:white; font-size:9px; padding:2px 7px; border-radius:20px; font-weight:700; }
        .topbar-sep { color:var(--muted); font-size:18px; }
        .page-title-bar { font-size:15px; font-weight:600; color:var(--text); }
        .topbar-nav { display:flex; gap:.25rem; }
        .topbar-nav a { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .875rem; border-radius:8px; font-size:12px; font-weight:500; color:var(--muted); text-decoration:none; transition:all .2s; }
        .topbar-nav a:hover, .topbar-nav a.active { background:#f0fdf4; color:var(--green-dark); }
        .topbar-right { display:flex; align-items:center; gap:.75rem; }
        .user-pill { display:flex; align-items:center; gap:.6rem; padding:.4rem .875rem .4rem .5rem; background:var(--bg); border-radius:24px; border:1px solid var(--border); }
        .user-avatar { width:28px; height:28px; background:var(--green); border-radius:50%; color:white; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; }
        .user-name { font-size:12px; font-weight:600; color:var(--text); }

        /* PAGE */
        .page-body { padding:1.5rem; max-width:1100px; margin:0 auto; }

        /* CARD */
        .card { background:var(--white); border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow); margin-bottom:1.5rem; overflow:hidden; }
        .card-header { padding:1rem 1.25rem; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .card-title { font-size:15px; font-weight:700; color:var(--text); }
        .card-body { padding:1.25rem; }

        /* ALERTS */
        .alert { padding:.875rem 1rem; border-radius:8px; font-size:13px; margin-bottom:1rem; border:1px solid; }
        .alert-success { background:#f0fdf4; color:#166534; border-color:#bbf7d0; }
        .alert-error   { background:#fef2f2; color:#991b1b; border-color:#fecaca; }

        /* FORM */
        .form-group { margin-bottom:1.125rem; }
        .form-label { display:block; font-size:12px; font-weight:600; color:#555; margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.4px; }
        .form-control { width:100%; padding:.6rem .875rem; border:1px solid var(--border); border-radius:8px; font-size:13px; color:var(--text); background:white; font-family:inherit; outline:none; transition:all .2s; }
        .form-control:focus { border-color:var(--green); box-shadow:0 0 0 3px rgba(37,211,102,.1); }
        select.form-control { cursor:pointer; }
        textarea.form-control { resize:vertical; min-height:100px; }
        .form-hint  { font-size:11px; color:var(--muted); margin-top:4px; }
        .form-error { font-size:11px; color:#dc2626; margin-top:4px; }
        .form-row { display:grid; gap:1rem; }
        .form-row.col-2 { grid-template-columns:1fr 1fr; }
        .form-row.col-3 { grid-template-columns:1fr 1fr 1fr; }

        /* BUTTONS */
        .btn { display:inline-flex; align-items:center; gap:.4rem; padding:.6rem 1.25rem; border-radius:8px; border:none; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s; white-space:nowrap; }
        .btn-primary   { background:var(--green); color:white; }
        .btn-primary:hover   { background:var(--green-dark); }
        .btn-secondary { background:var(--bg); color:var(--text); border:1px solid var(--border); }
        .btn-secondary:hover { background:#e5e7eb; }
        .btn-danger    { background:#dc2626; color:white; }
        .btn-danger:hover    { background:#b91c1c; }
        .btn-warning   { background:#f59e0b; color:white; }
        .btn-warning:hover   { background:#d97706; }
        .btn-sm { padding:.4rem .875rem; font-size:12px; }
        .btn-icon { padding:.4rem .5rem; }

        /* TABLE */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        thead th { padding:.75rem 1rem; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); background:var(--bg); border-bottom:1px solid var(--border); text-align:left; white-space:nowrap; }
        tbody tr { border-bottom:1px solid #f5f5f5; transition:background .1s; }
        tbody tr:hover { background:#fafafa; }
        tbody td { padding:.875rem 1rem; font-size:13px; color:var(--text); vertical-align:middle; }

        /* BADGES */
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
        .badge-green  { background:#dcfce7; color:#166534; }
        .badge-gray   { background:#f3f4f6; color:#6b7280; }
        .badge-red    { background:#fee2e2; color:#991b1b; }
        .badge-yellow { background:#fef9c3; color:#854d0e; }
        .badge-blue   { background:#dbeafe; color:#1e40af; }
        .badge-purple { background:#ede9fe; color:#6d28d9; }

        /* TOGGLE */
        .toggle { position:relative; width:40px; height:22px; flex-shrink:0; }
        .toggle input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; inset:0; background:#ddd; border-radius:11px; cursor:pointer; transition:.3s; }
        .toggle-slider::before { content:''; position:absolute; width:16px; height:16px; background:white; border-radius:50%; left:3px; top:3px; transition:.3s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
        .toggle input:checked + .toggle-slider { background:var(--green); }
        .toggle input:checked + .toggle-slider::before { transform:translateX(18px); }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:#ddd; border-radius:3px; }

        /* CHECKBOX */
        .check-group { display:flex; align-items:center; gap:.5rem; cursor:pointer; font-size:13px; }
        .check-group input[type=checkbox] { width:16px; height:16px; accent-color:var(--green); cursor:pointer; }

        @media (max-width:768px) {
            .form-row.col-2, .form-row.col-3 { grid-template-columns:1fr; }
            .page-body { padding:1rem; }
            .topbar-nav { display:none; }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <a href="{{ route('whatsapp.index', $tenant) }}" class="brand">
                @if($config->logo)
                    <img src="{{ asset('storage/' . $config->logo) }}" alt="Logo" style="height:36px;width:auto;">
                @else
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378L.057 23.404l6.599-1.733a9.985 9.985 0 004.806 1.227h.004c5.539 0 10.064-4.525 10.064-10.064A10.064 10.064 0 0012.051 2.979z"/></svg>
                    </div>
                @endif
                <span class="brand-name">{{ $config->app_name ?? 'WhatsApp Center' }}</span>
                <span class="brand-badge">BETA</span>
            </a>
            @if(isset($pageTitle))
                <span class="topbar-sep">/</span>
                <span class="page-title-bar">@yield('page-title', $pageTitle)</span>
            @endif
        </div>
        <nav class="topbar-nav">
            <a href="{{ route('whatsapp.index', $tenant) }}">💬 Centro</a>
            <a href="{{ route('whatsapp.accounts.index', $tenant) }}"
               class="{{ request()->routeIs('whatsapp.accounts*') ? 'active' : '' }}">📱 Números</a>
            <a href="{{ route('whatsapp.templates.index', $tenant) }}"
               class="{{ request()->routeIs('whatsapp.templates*') ? 'active' : '' }}">📋 Plantillas</a>
            <a href="{{ route('whatsapp.automations.index', $tenant) }}"
               class="{{ request()->routeIs('whatsapp.automations*') ? 'active' : '' }}">⚙️ Automatizaciones</a>
        </nav>
        <div class="topbar-right">
            <div class="user-pill">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <span class="user-name">{{ auth()->user()->name }}</span>
            </div>
        </div>
    </div>

    <div class="page-body">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @yield('scripts')
</body>
</html>
