<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizaciones de la App — SIDB</title>
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

        .au-topbar {
            height:60px; background:var(--card); border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between;
            padding:0 1.5rem; gap:1rem; position:sticky; top:0; z-index:200;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        html.dark .au-topbar { box-shadow:none; }
        .au-back { display:inline-flex; align-items:center; gap:.4rem; color:var(--muted); background:none; border:none; cursor:pointer; font-size:.85rem; font-weight:600; padding:.4rem .6rem; border-radius:.5rem; }
        .au-back:hover { background:var(--subtle); color:var(--text-2); }
        .au-title { font-size:.92rem; font-weight:700; color:var(--text); }

        .theme-toggle { width:36px; height:36px; border-radius:50%; border:1px solid var(--border); background:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--muted); flex-shrink:0; }
        .theme-toggle:hover { background:var(--subtle); }
        .theme-toggle svg { width:18px; height:18px; }
        .theme-toggle .icon-sun { display:none; }
        html.dark .theme-toggle .icon-moon { display:none; }
        html.dark .theme-toggle .icon-sun { display:block; }

        .au-body { padding:1.5rem; max-width:720px; margin:0 auto; }

        .au-card { background:var(--card); border:1px solid var(--border); border-radius:.875rem; box-shadow:0 1px 3px rgba(0,0,0,.05); margin-bottom:1.25rem; overflow:hidden; }
        html.dark .au-card { box-shadow:none; }
        .au-card-header { padding:1rem 1.25rem; border-bottom:1px solid var(--border-2); }
        .au-card-header h3 { font-size:.85rem; font-weight:700; color:var(--text); }
        .au-card-body { padding:1.25rem; }

        .au-page-header h1 { font-size:1.375rem; font-weight:700; color:var(--text); margin-bottom:.2rem; }
        .au-page-header p { font-size:.85rem; color:var(--muted); }

        .au-current { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
        .au-current-icon { width:48px; height:48px; border-radius:.7rem; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        html.dark .au-current-icon { background:rgba(34,197,94,.18); }
        .au-current-version { font-size:1.1rem; font-weight:800; color:var(--text); }
        .au-current-notas { font-size:.82rem; color:var(--muted); margin-top:.15rem; }
        .au-current-url { font-size:.76rem; color:var(--muted-2); margin-top:.3rem; word-break:break-all; }
        .au-current-url a { color:#2563eb; text-decoration:none; }
        .au-current-url a:hover { text-decoration:underline; }

        .au-form-group { margin-bottom:1.1rem; }
        .au-form-group label { display:block; font-size:.78rem; font-weight:600; color:var(--text-2); margin-bottom:.4rem; }
        .au-input { width:100%; padding:.6rem .8rem; border:1px solid var(--border); border-radius:.55rem; font-size:.85rem; color:var(--text); background:var(--subtle); outline:none; font-family:inherit; }
        .au-input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); background:var(--card); }
        .au-hint { font-size:.72rem; color:var(--muted-2); margin-top:.35rem; }
        .au-error { font-size:.76rem; color:#dc2626; margin-top:.35rem; font-weight:600; }

        .au-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.25rem; border-radius:.6rem; border:none; font-size:.85rem; font-weight:700; cursor:pointer; background:#10b981; color:#fff; }
        .au-btn:hover { background:#059669; }
        .au-btn:disabled { opacity:.6; cursor:not-allowed; }

        .au-alert { padding:.85rem 1.1rem; border-radius:.65rem; font-size:.85rem; font-weight:600; margin-bottom:1.25rem; }
        .au-alert-success { background:#dcfce7; color:#166534; }
        .au-alert-error { background:#fef2f2; color:#991b1b; }
        html.dark .au-alert-success { background:rgba(34,197,94,.15); color:#86efac; }
        html.dark .au-alert-error { background:rgba(220,38,38,.15); color:#fca5a5; }

        .au-empty { padding:1.5rem; text-align:center; color:var(--muted-2); font-size:.85rem; }
    </style>
</head>
<body>

<div class="au-topbar">
    <div style="display:flex; align-items:center; gap:.9rem;">
        <button type="button" class="au-back" onclick="history.back()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Volver
        </button>
        <span class="au-title">Actualizaciones de la App</span>
    </div>
    <button type="button" class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
</div>

<div class="au-body">

    @if(session('success'))
        <div class="au-alert au-alert-success">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="au-alert au-alert-error">
            @foreach($errors->all() as $error)
                {{ $error }}@if(!$loop->last)<br>@endif
            @endforeach
        </div>
    @endif

    <div class="au-page-header" style="margin-bottom:1.25rem;">
        <h1>Actualizaciones de la App</h1>
        <p>Publica una nueva versión del APK para los cobradores y vendedores.</p>
    </div>

    <div class="au-card">
        <div class="au-card-header"><h3>Versión publicada actualmente</h3></div>
        <div class="au-card-body">
            @if($actual)
                <div class="au-current">
                    <div class="au-current-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    </div>
                    <div>
                        <div class="au-current-version">v{{ $actual['version'] ?? '—' }}</div>
                        @if(!empty($actual['notas']))
                            <div class="au-current-notas">{{ $actual['notas'] }}</div>
                        @endif
                        @if(!empty($actual['url']))
                            <div class="au-current-url"><a href="{{ $actual['url'] }}" target="_blank">{{ $actual['url'] }}</a></div>
                        @endif
                    </div>
                </div>
            @else
                <div class="au-empty">Todavía no se ha publicado ningún APK.</div>
            @endif
        </div>
    </div>

    <div class="au-card">
        <div class="au-card-header"><h3>Publicar nueva versión</h3></div>
        <div class="au-card-body">
            <form method="POST" action="{{ route('admin.update.upload') }}" enctype="multipart/form-data">
                @csrf

                <div class="au-form-group">
                    <label for="version">Versión</label>
                    <input type="text" id="version" name="version" class="au-input" placeholder="1.0.1" pattern="\d+\.\d+\.\d+" value="{{ old('version') }}" required>
                    <div class="au-hint">Formato x.x.x (ej: 1.0.1)</div>
                </div>

                <div class="au-form-group">
                    <label for="apk">Archivo APK</label>
                    <input type="file" id="apk" name="apk" class="au-input" accept=".apk" required>
                    <div class="au-hint">Máximo 250 MB. Sobreescribe el APK publicado anteriormente.</div>
                </div>

                <div class="au-form-group">
                    <label for="notas">Notas de la versión (opcional)</label>
                    <textarea id="notas" name="notas" class="au-input" rows="3" placeholder="Correcciones de errores y nuevo sistema de orden de clientes">{{ old('notas') }}</textarea>
                </div>

                <button type="submit" class="au-btn" id="au-submit-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Publicar actualización
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    function toggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('sidb-theme', isDark ? 'dark' : 'light');
    }

    document.querySelector('form').addEventListener('submit', function () {
        var btn = document.getElementById('au-submit-btn');
        btn.disabled = true;
        btn.textContent = 'Subiendo...';
    });
</script>
</body>
</html>
