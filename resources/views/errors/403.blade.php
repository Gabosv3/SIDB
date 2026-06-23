@php
    $config = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('configuracion_sistema')) {
            $config = \App\Models\ConfiguracionSistema::instance();
        }
    } catch (\Throwable) {
    }

    $appName  = $config?->app_name ?: config('app.name', 'SIDB');
    $favicon  = $config?->favicon  ? asset('storage/'.$config->favicon) : null;
    $horario  = $config?->horario  ?: 'Lunes a viernes, 9:00 a.m. – 6:00 p.m.';

    $soporteUrl = match (true) {
        filled($config?->whatsapp_number) => 'https://wa.me/'.preg_replace('/\D/', '', $config->whatsapp_number),
        filled($config?->correo_contacto) => 'mailto:'.$config->correo_contacto,
        filled($config?->telefono)        => 'tel:'.$config->telefono,
        default                           => null,
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Acción no autorizada @if($appName) · {{ $appName }} @endif</title>
    @if($favicon)
        <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    @endif
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        html, body {
            min-height:100vh; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
            background:#f3f4f6; color:#0f172a;
        }
        body {
            display:flex; align-items:center; justify-content:center; padding:1.5rem;
            background-image:
                radial-gradient(circle at 8% 12%, rgba(30,41,59,.05) 0, transparent 45%),
                radial-gradient(circle at 92% 88%, rgba(30,41,59,.05) 0, transparent 45%);
        }
        .topbar { position:fixed; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,#0f172a,#1e293b,#334155); }

        .card {
            position:relative; width:100%; max-width:420px; background:#fff;
            border-radius:1.25rem; box-shadow:0 20px 45px -10px rgba(15,23,42,.15), 0 0 0 1px rgba(15,23,42,.04);
            padding:2.5rem 2rem 2rem; text-align:center; overflow:hidden;
        }
        .card-dots {
            position:absolute; top:-10px; left:-10px; width:90px; height:90px; opacity:.5;
            background-image: radial-gradient(circle, #cbd5e1 1.5px, transparent 1.5px);
            background-size:12px 12px; pointer-events:none;
        }
        .icon-wrap {
            width:76px; height:76px; margin:0 auto 1.5rem; border-radius:50%;
            background:linear-gradient(135deg,#1e293b,#334155);
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 10px 25px -5px rgba(30,41,59,.45);
            position:relative; z-index:1;
        }
        .icon-wrap svg { width:34px; height:34px; }

        .code { font-size:3rem; font-weight:800; color:#0f172a; line-height:1; letter-spacing:-.02em; position:relative; z-index:1; }
        .title { font-size:1.25rem; font-weight:700; color:#0f172a; margin-top:.5rem; position:relative; z-index:1; }
        .subtitle { font-size:.9rem; color:#64748b; margin-top:.5rem; line-height:1.5; position:relative; z-index:1; }

        .actions { display:flex; gap:.75rem; margin-top:1.75rem; position:relative; z-index:1; }
        .btn {
            flex:1; display:inline-flex; align-items:center; justify-content:center; gap:.45rem;
            padding:.65rem 1rem; border-radius:.65rem; font-size:.84rem; font-weight:600;
            text-decoration:none; border:1px solid #e2e8f0; color:#1e293b; background:#fff;
            transition:all .15s;
        }
        .btn:hover { background:#f8fafc; border-color:#cbd5e1; }
        .btn svg { width:16px; height:16px; flex-shrink:0; }

        .footer {
            margin-top:1.75rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;
            font-size:.78rem; color:#94a3b8; position:relative; z-index:1;
        }
        .footer p { line-height:1.5; }
        .footer .ayuda { display:flex; align-items:center; justify-content:center; gap:.4rem; color:#64748b; font-weight:500; }
        .footer .ayuda svg { width:13px; height:13px; flex-shrink:0; }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <div class="card">
        <div class="card-dots"></div>

        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <rect x="9" y="11" width="6" height="5" rx="1" fill="#fff" stroke="none"/>
                <path d="M10.5 11v-1.5a1.5 1.5 0 0 1 3 0V11" stroke="#fff"/>
            </svg>
        </div>

        <div class="code">403</div>
        <div class="title">Acción no autorizada</div>
        <p class="subtitle">No tienes permisos para acceder a este recurso.</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/><path d="M9 21v-6h6v6"/></svg>
                Volver al inicio
            </a>
            @if($soporteUrl)
            <a href="{{ $soporteUrl }}" target="_blank" rel="noopener noreferrer" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                Contactar soporte
            </a>
            @endif
        </div>

        <div class="footer">
            <p class="ayuda">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 1 1 5.83 1c0 2-3 2-3 4"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                ¿Necesitas ayuda? Nuestro equipo está aquí para asistirte.
            </p>
            <p style="margin-top:.3rem;">Soporte disponible: {{ $horario }}</p>
        </div>
    </div>
</body>
</html>
