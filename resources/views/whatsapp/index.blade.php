@php
    $config = \App\Models\ConfiguracionSistema::instance();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WhatsApp Center - {{ $config->app_name }}</title>
    @if($config->favicon)
        <link rel="icon" href="{{ asset('storage/' . $config->favicon) }}" type="image/x-icon">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:       {{ $config->primary_color ?? '#25d366' }};
            --green-dark:  #128c7e;
            --green-light: #dcf8c6;
            --bg:          #f0f2f5;
            --white:       #ffffff;
            --border:      #e9ecef;
            --text:        #1a1a2e;
            --muted:       #8696a0;
            --sidebar-bg:  #ffffff;
            --chat-bg:     #efeae2;
            --header-bg:   #ffffff;
            --radius:      12px;
            --shadow:      0 2px 8px rgba(0,0,0,.08);
        }

        html, body { height: 100vh; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background: var(--bg); color: var(--text); overflow: hidden; font-size: 14px; }

        /* ── TOP BAR ─────────────────────────────────────────────────────────── */
        .topbar {
            height: 62px; background: var(--white); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; gap: 1rem; box-shadow: var(--shadow); position: relative; z-index: 10;
        }

        .topbar-left { display: flex; align-items: center; gap: 1rem; }

        .brand { display: flex; align-items: center; gap: .6rem; }
        .brand-icon { width: 36px; height: 36px; background: var(--green); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .brand-icon svg { width: 20px; height: 20px; fill: white; }
        .brand h1 { font-size: 17px; font-weight: 700; color: var(--text); }
        .brand-badge { background: var(--green); color: white; font-size: 9px; padding: 2px 7px; border-radius: 20px; font-weight: 700; letter-spacing: .5px; }

        .topbar-search {
            flex: 1; max-width: 380px;
        }
        .topbar-search form { position: relative; }
        .topbar-search input {
            width: 100%; padding: .55rem 1rem .55rem 2.4rem;
            border: 1px solid var(--border); border-radius: 24px;
            background: var(--bg); font-size: 13px; color: var(--text);
            outline: none; transition: all .2s;
        }
        .topbar-search input:focus { border-color: var(--green); background: white; box-shadow: 0 0 0 3px rgba(37,211,102,.1); }
        .topbar-search .search-icon { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: var(--muted); }

        .topbar-right { display: flex; align-items: center; gap: 1rem; }

        .topbar-nav a {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .45rem .875rem; border-radius: 8px; font-size: 12px; font-weight: 500;
            color: var(--muted); text-decoration: none; transition: all .2s;
        }
        .topbar-nav a:hover, .topbar-nav a.active { background: #f0fdf4; color: var(--green-dark); }

        .user-pill {
            display: flex; align-items: center; gap: .6rem;
            padding: .4rem .875rem .4rem .5rem;
            background: var(--bg); border-radius: 24px; border: 1px solid var(--border);
        }
        .user-avatar { width: 28px; height: 28px; background: var(--green); border-radius: 50%; color: white; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .user-info { line-height: 1.2; }
        .user-name { font-size: 12px; font-weight: 600; color: var(--text); }
        .user-role { font-size: 10px; color: var(--muted); }

        /* ── STATS ───────────────────────────────────────────────────────────── */
        .stats-bar {
            background: var(--white); border-bottom: 1px solid var(--border);
            padding: .875rem 1.5rem; display: flex; gap: 1rem; align-items: stretch;
        }

        .stat-item {
            flex: 1; display: flex; align-items: center; gap: .875rem;
            padding: .875rem 1rem; background: var(--bg); border-radius: var(--radius);
            border: 1px solid var(--border); transition: all .2s; cursor: default;
        }
        .stat-item:hover { border-color: var(--green); background: #f0fdf4; }

        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .stat-icon.green  { background: #dcfce7; }
        .stat-icon.blue   { background: #dbeafe; }
        .stat-icon.orange { background: #ffedd5; }
        .stat-icon.red    { background: #fee2e2; }

        .stat-body {}
        .stat-value { font-size: 22px; font-weight: 700; color: var(--text); line-height: 1; }
        .stat-label { font-size: 11px; color: var(--muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }

        /* ── MAIN LAYOUT ─────────────────────────────────────────────────────── */
        .workspace {
            display: flex;
            height: calc(100vh - 62px - 76px);
            overflow: hidden;
        }

        /* ── PANEL IZQUIERDO ─────────────────────────────────────────────────── */
        .panel-left {
            width: 340px; flex-shrink: 0;
            background: var(--sidebar-bg); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; overflow: hidden;
        }

        .panel-left-header {
            padding: .875rem 1rem .5rem;
            border-bottom: 1px solid var(--border);
        }

        .panel-left-title {
            font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: .625rem;
        }

        .filters {
            display: flex; gap: .375rem; flex-wrap: wrap;
        }

        .filter-chip {
            padding: .35rem .75rem; border-radius: 20px; border: 1px solid var(--border);
            background: white; color: var(--muted); font-size: 11px; font-weight: 500;
            cursor: pointer; transition: all .15s; white-space: nowrap;
        }
        .filter-chip:hover { border-color: var(--green); color: var(--green); }
        .filter-chip.active { background: var(--green); border-color: var(--green); color: white; }

        .conv-list { flex: 1; overflow-y: auto; }

        .conv-item {
            display: flex; align-items: flex-start; gap: .75rem;
            padding: .875rem 1rem; border-bottom: 1px solid #f5f5f5;
            cursor: pointer; transition: background .12s; position: relative;
        }
        .conv-item:hover { background: #f7f7f7; }
        .conv-item.active { background: #f0fdf4; border-left: 3px solid var(--green); padding-left: calc(1rem - 3px); }

        .conv-avatar {
            width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
            background: var(--green); color: white; font-weight: 700; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
        }

        .conv-body { flex: 1; min-width: 0; }
        .conv-row1 { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; margin-bottom: 2px; }
        .conv-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-time { font-size: 11px; color: var(--muted); flex-shrink: 0; }
        .conv-preview { font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-phone { font-size: 11px; color: var(--muted); margin-top: 1px; }

        .status-badge {
            display: inline-block; font-size: 10px; font-weight: 600;
            padding: 2px 7px; border-radius: 10px; margin-top: 4px;
        }
        .badge-vencida       { background: #fee2e2; color: #dc2626; }
        .badge-pendiente     { background: #ffedd5; color: #ea580c; }
        .badge-promesa       { background: #fef9c3; color: #ca8a04; }
        .badge-pagado        { background: #dcfce7; color: #16a34a; }
        .badge-sin_respuesta { background: #e0e7ff; color: #4f46e5; }
        .badge-cerrada       { background: #f3f4f6; color: #6b7280; }

        .conv-empty { padding: 2rem 1rem; text-align: center; color: var(--muted); }

        /* ── PANEL CENTRAL (CHAT) ────────────────────────────────────────────── */
        .panel-center {
            flex: 1; display: flex; flex-direction: column;
            background: var(--chat-bg); overflow: hidden; min-width: 0;
        }

        .chat-empty-state {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 1rem; color: var(--muted);
        }
        .chat-empty-state .big-icon { font-size: 72px; opacity: .25; }

        /* Chat header */
        .chat-topbar {
            background: var(--white); border-bottom: 1px solid var(--border);
            padding: .75rem 1.25rem; display: flex; align-items: center;
            justify-content: space-between; flex-shrink: 0;
        }
        .chat-client-info { display: flex; align-items: center; gap: .875rem; }
        .chat-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--green); color: white; font-weight: 700; font-size: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .chat-client-name { font-size: 15px; font-weight: 700; color: var(--text); }
        .chat-client-meta { font-size: 12px; color: var(--muted); }

        .chat-topbar-actions { display: flex; gap: .5rem; }
        .icon-btn {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border);
            background: white; cursor: pointer; display: flex; align-items: center;
            justify-content: center; color: var(--muted); transition: all .2s; font-size: 14px;
        }
        .icon-btn:hover { background: var(--bg); color: var(--text); border-color: #aaa; }

        /* Messages */
        .chat-messages {
            flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem;
            display: flex; flex-direction: column; gap: .5rem;
        }

        .msg-row { display: flex; align-items: flex-end; gap: .5rem; }
        .msg-row.out { justify-content: flex-end; }
        .msg-row.in  { justify-content: flex-start; }

        .msg-bubble {
            max-width: 60%; padding: .625rem .875rem;
            border-radius: 12px; font-size: 13px; line-height: 1.55;
            word-break: break-word; position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,.1);
        }
        .msg-row.out .msg-bubble { background: var(--green-light); color: #1a1a1a; border-bottom-right-radius: 4px; }
        .msg-row.in  .msg-bubble { background: white; color: var(--text); border-bottom-left-radius: 4px; }

        .msg-meta { font-size: 10px; color: var(--muted); margin-top: 3px; display: flex; align-items: center; gap: 3px; }
        .msg-row.out .msg-meta { justify-content: flex-end; }

        .msg-check { font-size: 12px; }
        .check-read { color: #53bdeb; }
        .check-sent { color: #aaa; }

        .day-divider { text-align: center; margin: .75rem 0; }
        .day-divider span { background: rgba(255,255,255,.75); color: var(--muted); font-size: 11px; padding: 3px 12px; border-radius: 12px; font-weight: 500; }

        /* Input area */
        .chat-input-area {
            background: var(--white); border-top: 1px solid var(--border);
            padding: .875rem 1.25rem; flex-shrink: 0;
        }
        .quick-btns { display: flex; gap: .5rem; margin-bottom: .75rem; flex-wrap: wrap; }
        .quick-btn {
            padding: .375rem .75rem; border-radius: 20px; border: 1px solid var(--border);
            background: white; color: #555; font-size: 11px; font-weight: 500;
            cursor: pointer; transition: all .2s; white-space: nowrap;
        }
        .quick-btn:hover { border-color: var(--green); color: var(--green); background: #f0fdf4; }

        .input-row { display: flex; gap: .625rem; align-items: flex-end; }
        .msg-textarea {
            flex: 1; border: 1px solid var(--border); border-radius: 24px;
            padding: .625rem 1.125rem; font-size: 13px; resize: none;
            max-height: 120px; background: var(--bg); color: var(--text);
            font-family: inherit; outline: none; transition: all .2s; line-height: 1.5;
        }
        .msg-textarea:focus { border-color: var(--green); background: white; box-shadow: 0 0 0 3px rgba(37,211,102,.1); }

        .send-btn {
            width: 42px; height: 42px; border-radius: 50%; background: var(--green);
            border: none; color: white; cursor: pointer; display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            font-size: 16px; transition: all .2s; box-shadow: 0 2px 6px rgba(37,211,102,.35);
        }
        .send-btn:hover { background: var(--green-dark); }
        .send-btn:disabled { opacity: .5; cursor: not-allowed; }

        /* ── PANEL DERECHO (DETALLES) ─────────────────────────────────────────── */
        .panel-right {
            width: 300px; flex-shrink: 0; background: var(--white);
            border-left: 1px solid var(--border); overflow-y: auto;
            display: flex; flex-direction: column;
        }

        .detail-section { padding: 1rem; border-bottom: 1px solid var(--border); }
        .detail-section-title { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: .75rem; }

        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .5rem; font-size: 12px; }
        .detail-key { color: var(--muted); }
        .detail-val { color: var(--text); font-weight: 500; text-align: right; max-width: 55%; }
        .detail-val.danger { color: #dc2626; font-weight: 700; }
        .detail-val.success { color: #16a34a; font-weight: 700; }

        .cuota-card {
            padding: .625rem .75rem; background: var(--bg); border-radius: 8px;
            border-left: 3px solid var(--green); margin-bottom: .5rem; font-size: 12px;
        }
        .cuota-card.vencida { border-left-color: #dc2626; background: #fef2f2; }
        .cuota-num { font-weight: 600; color: var(--text); }
        .cuota-monto { font-weight: 700; font-size: 14px; margin: 2px 0; }
        .cuota-card.vencida .cuota-monto { color: #dc2626; }
        .cuota-card:not(.vencida) .cuota-monto { color: var(--green-dark); }
        .cuota-fecha { color: var(--muted); font-size: 11px; }

        .automation-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: .625rem 0; border-bottom: 1px solid #f5f5f5; font-size: 12px;
        }
        .automation-row:last-child { border-bottom: none; }
        .automation-label { color: var(--text); font-weight: 500; }

        .toggle { position: relative; width: 40px; height: 22px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0; background: #ddd; border-radius: 11px;
            cursor: pointer; transition: .3s;
        }
        .toggle-slider::before { content: ''; position: absolute; width: 16px; height: 16px; background: white; border-radius: 50%; left: 3px; top: 3px; transition: .3s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        .toggle input:checked + .toggle-slider { background: var(--green); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

        .detail-empty { padding: 2rem 1rem; text-align: center; color: var(--muted); font-size: 13px; }

        /* ── SCROLLBAR ───────────────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }

        /* ── LOADING ─────────────────────────────────────────────────────────── */
        .loading-dots { display: flex; gap: 4px; align-items: center; justify-content: center; padding: 2rem; }
        .loading-dots span { width: 8px; height: 8px; background: var(--green); border-radius: 50%; animation: bounce .8s infinite; }
        .loading-dots span:nth-child(2) { animation-delay: .1s; }
        .loading-dots span:nth-child(3) { animation-delay: .2s; }
        @keyframes bounce { 0%,80%,100% { transform: scale(.7); } 40% { transform: scale(1); } }
    </style>
</head>
<body>
    {{-- TOP BAR --}}
    <div class="topbar">
        <div class="topbar-left">
            <div class="brand">
                @if($config->logo)
                    <img src="{{ asset('storage/' . $config->logo) }}" alt="Logo" style="height:36px;width:auto;margin-right:.5rem;">
                @else
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378L.057 23.404l6.599-1.733a9.985 9.985 0 004.806 1.227h.004c5.539 0 10.064-4.525 10.064-10.064A10.064 10.064 0 0012.051 2.979z"/></svg>
                    </div>
                @endif
                <h1>{{ $config->app_name ?? 'WhatsApp Center' }}</h1>
                <span class="brand-badge">BETA</span>
            </div>

            <nav class="topbar-nav">
                <a href="{{ route('whatsapp.accounts.index', $tenant) }}">📱 Números</a>
                <a href="{{ route('whatsapp.templates.index', $tenant) }}">📋 Plantillas</a>
                <a href="{{ route('whatsapp.automations.index', $tenant) }}">⚙️ Automatizaciones</a>
            </nav>
        </div>

        <div class="topbar-search">
            <form method="GET">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Buscar clientes, conversaciones..." value="{{ $buscar ?? '' }}" autocomplete="off">
            </form>
        </div>

        <div class="topbar-right">
            @if(($cuentas ?? 0) === 0)
                <a href="{{ route('whatsapp.accounts.create', $tenant) }}" style="font-size:12px;color:#dc2626;text-decoration:none;">⚠️ Sin número configurado</a>
            @endif
            <div class="user-pill">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATS BAR --}}
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-icon green">💬</div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['conversaciones_activas'] ?? 0 }}</div>
                <div class="stat-label">Conversaciones activas</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon blue">📤</div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['recordatorios_enviados'] ?? 0 }}</div>
                <div class="stat-label">Recordatorios enviados</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon green">✅</div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['cubiertas_confirmadas'] ?? 0 }}</div>
                <div class="stat-label">Cobros confirmados</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon orange">⏳</div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['respuestas_pendientes'] ?? 0 }}</div>
                <div class="stat-label">Respuestas pendientes</div>
            </div>
        </div>
    </div>

    {{-- WORKSPACE --}}
    <div class="workspace">

        {{-- PANEL IZQUIERDO --}}
        <div class="panel-left">
            <div class="panel-left-header">
                <div class="panel-left-title">Clientes / Conversaciones</div>
                <div class="filters">
                    <button class="filter-chip {{ $filtro==='todos' ? 'active' : '' }}" onclick="setFiltro('todos')">Todos</button>
                    <button class="filter-chip {{ $filtro==='abiertas' ? 'active' : '' }}" onclick="setFiltro('abiertas')">Abiertas</button>
                    <button class="filter-chip {{ $filtro==='sin_respuesta' ? 'active' : '' }}" onclick="setFiltro('sin_respuesta')">Sin respuesta</button>
                    <button class="filter-chip {{ $filtro==='cerradas' ? 'active' : '' }}" onclick="setFiltro('cerradas')">Cerradas</button>
                </div>
            </div>

            <div class="conv-list" id="convList">
                @forelse($conversaciones as $conv)
                    @php
                        $estado = $conv->cliente?->gestionesCobro()
                            ->whereIn('estado',['pendiente','vencida'])
                            ->orderBy('fecha_vencimiento')
                            ->value('estado') ?? null;
                    @endphp
                    <div class="conv-item {{ $conversacionActiva?->id === $conv->id ? 'active' : '' }}"
                         data-id="{{ $conv->id }}"
                         onclick="abrirConversacion({{ $conv->id }})">
                        <div class="conv-avatar">{{ strtoupper(substr($conv->cliente?->nombre ?? '?', 0, 1)) }}</div>
                        <div class="conv-body">
                            <div class="conv-row1">
                                <span class="conv-name">{{ $conv->cliente?->nombre_completo ?? 'Sin nombre' }}</span>
                                <span class="conv-time">{{ $conv->ultimo_mensaje_at?->format('H:i') ?? '' }}</span>
                            </div>
                            <div class="conv-preview">{{ Str::limit($conv->ultimo_mensaje ?? 'Sin mensajes', 45) }}</div>
                            <div class="conv-phone">📱 {{ $conv->cliente?->telefono_whatsapp ?? '—' }}</div>
                            @if($estado)
                                <span class="status-badge badge-{{ $estado }}">
                                    {{ match($estado) { 'vencida'=>'Vencida','pendiente'=>'Pendiente',default=>ucfirst($estado) } }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="conv-empty">
                        <p style="font-size:32px;margin-bottom:.5rem;">💬</p>
                        <p>No hay conversaciones</p>
                        <p style="font-size:12px;margin-top:.25rem;">Abre una desde la lista de clientes</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- PANEL CENTRAL (CHAT) --}}
        <div class="panel-center" id="chatPanel">
            @if($conversacionActiva)
                @include('whatsapp._chat', ['conversation' => $conversacionActiva])
            @else
                <div class="chat-empty-state">
                    <div class="big-icon">💬</div>
                    <p style="font-size:16px;font-weight:700;color:#333;">WhatsApp Center</p>
                    <p style="font-size:13px;">Selecciona una conversación para comenzar</p>
                </div>
            @endif
        </div>

        {{-- PANEL DERECHO (DETALLES) --}}
        <div class="panel-right" id="detailPanel">
            @if($conversacionActiva)
                @include('whatsapp._detail', ['conversation' => $conversacionActiva])
            @else
                <div class="detail-empty">
                    <p style="font-size:28px;margin-bottom:.5rem;">👤</p>
                    <p>Selecciona una conversación para ver los detalles del cliente</p>
                </div>
            @endif
        </div>

    </div>{{-- /workspace --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const TENANT = '{{ $tenant }}';
    const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
    let activeConvId = {{ $conversacionActiva?->id ?? 'null' }};

    // ── Filtro ────────────────────────────────────────────────────────────────
    function setFiltro(f) {
        const u = new URL(window.location);
        u.searchParams.set('filtro', f);
        window.location = u;
    }

    // ── Abrir conversación vía AJAX ───────────────────────────────────────────
    function abrirConversacion(id) {
        document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
        document.querySelector(`.conv-item[data-id="${id}"]`)?.classList.add('active');
        activeConvId = id;

        document.getElementById('chatPanel').innerHTML = `
            <div class="loading-dots" style="flex:1;align-items:center;">
                <span></span><span></span><span></span>
            </div>`;
        document.getElementById('detailPanel').innerHTML = '<div class="detail-empty"><p>Cargando...</p></div>';

        fetch(`/whatsapp/${TENANT}/conversation/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        })
        .then(r => r.json())
        .then(data => {
            renderChat(data);
            renderDetail(data);
        })
        .catch(() => {
            document.getElementById('chatPanel').innerHTML = '<div class="chat-empty-state"><p>Error al cargar</p></div>';
        });
    }

    // ── Render chat ───────────────────────────────────────────────────────────
    function renderChat(data) {
        const conv    = data.conversation;
        const cliente = conv.cliente;
        const ruta    = cliente.ruta_cobro?.nombre ?? '—';

        let h = `
        <div class="chat-topbar">
            <div class="chat-client-info">
                <div class="chat-avatar">${(cliente.nombre||'?')[0].toUpperCase()}</div>
                <div>
                    <div class="chat-client-name">${esc(cliente.nombre+' '+(cliente.apellido||''))}</div>
                    <div class="chat-client-meta">📱 ${cliente.telefono_whatsapp||'—'}</div>
                </div>
            </div>
            <div class="chat-topbar-actions">
                <button class="icon-btn" onclick="enviarRecordatorio(${cliente.id})" title="Enviar recordatorio">🔔</button>
                <button class="icon-btn" title="Más opciones">⋮</button>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">`;

        if (!conv.mensajes?.length) {
            h += `<div style="text-align:center;color:var(--muted);padding:2rem;font-size:13px;">Sin mensajes aún</div>`;
        } else {
            let lastDate = '';
            conv.mensajes.forEach(msg => {
                const t   = msg.sent_at || msg.received_at || msg.created_at;
                const d   = t ? new Date(t).toLocaleDateString('es-SV',{day:'2-digit',month:'short'}) : '';
                const hr  = t ? new Date(t).toLocaleTimeString('es-SV',{hour:'2-digit',minute:'2-digit'}) : '';
                if (d !== lastDate) {
                    h += `<div class="day-divider"><span>${d}</span></div>`;
                    lastDate = d;
                }
                const check = msg.direction==='out' ? (msg.status==='read' ? '<span class="msg-check check-read">✓✓</span>' : '<span class="msg-check check-sent">✓</span>') : '';
                h += `
                <div class="msg-row ${msg.direction}">
                    <div>
                        <div class="msg-bubble">${esc(msg.body||'')}</div>
                        <div class="msg-meta">${hr} ${check}</div>
                    </div>
                </div>`;
            });
        }

        h += `</div>
        <div class="chat-input-area">
            <div class="quick-btns">
                <button class="quick-btn" onclick="quickMsg('recordar_cuota')">🔔 Recordar cuota</button>
                <button class="quick-btn" onclick="quickMsg('saldo_pendiente')">💰 Saldo pendiente</button>
                <button class="quick-btn" onclick="quickMsg('confirmar_pago')">✅ Confirmar pago</button>
                <button class="quick-btn" onclick="quickMsg('promesa_pago')">📅 Promesa de pago</button>
            </div>
            <div class="input-row">
                <textarea class="msg-textarea" id="msgInput" rows="1"
                    placeholder="Escribe un mensaje... (Enter para enviar)"
                    onkeydown="onEnter(event)" oninput="autoH(this)"></textarea>
                <button class="send-btn" id="sendBtn" onclick="enviarMensaje()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>`;

        document.getElementById('chatPanel').innerHTML = h;
        scrollBottom();
    }

    // ── Render detalle ────────────────────────────────────────────────────────
    function renderDetail(data) {
        const conv    = data.conversation;
        const cliente = conv.cliente;
        const cuotas  = data.cuotas_pendientes || [];

        let h = `
        <div class="detail-section">
            <div class="detail-section-title">👤 Detalle del cliente</div>
            <div class="detail-row"><span class="detail-key">Cliente</span><span class="detail-val">${esc(cliente.nombre_completo||'N/A')}</span></div>
            <div class="detail-row"><span class="detail-key">WhatsApp</span><span class="detail-val">${cliente.telefono_whatsapp||'—'}</span></div>
            <div class="detail-row"><span class="detail-key">Saldo</span><span class="detail-val danger">$${parseFloat(cliente.saldo||0).toFixed(2)}</span></div>
        </div>`;

        if (cuotas.length) {
            h += `<div class="detail-section"><div class="detail-section-title">📋 Cuotas pendientes</div>`;
            cuotas.forEach(c => {
                h += `
                <div class="cuota-card ${c.estado==='vencida'?'vencida':''}">
                    <div class="cuota-num">Cuota ${c.numero_cuota}/${c.total_cuotas}</div>
                    <div class="cuota-monto">$${parseFloat(c.monto_cuota).toFixed(2)}</div>
                    <div class="cuota-fecha">Vence: ${c.fecha_vencimiento}</div>
                </div>`;
            });
            h += `</div>`;
        }

        h += `
        <div class="detail-section">
            <div class="detail-section-title">⚙️ Automatizaciones</div>
            <div class="automation-row">
                <span class="automation-label">Recordatorio 1 día antes</span>
                <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
            </div>
            <div class="automation-row">
                <span class="automation-label">Día de vencimiento</span>
                <label class="toggle"><input type="checkbox" checked><span class="toggle-slider"></span></label>
            </div>
            <div class="automation-row">
                <span class="automation-label">Confirmación de pago</span>
                <label class="toggle"><input type="checkbox"><span class="toggle-slider"></span></label>
            </div>
        </div>`;

        document.getElementById('detailPanel').innerHTML = h;
    }

    // ── Enviar mensaje ────────────────────────────────────────────────────────
    function enviarMensaje() {
        const input = document.getElementById('msgInput');
        const text  = input?.value?.trim();
        if (!text || !activeConvId) return;

        const btn = document.getElementById('sendBtn');
        btn.disabled = true;

        fetch(`/whatsapp/${TENANT}/send`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF },
            body: JSON.stringify({ conversation_id: activeConvId, message: text }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const msgs = document.getElementById('chatMessages');
                if (msgs) {
                    const hr = new Date().toLocaleTimeString('es-SV',{hour:'2-digit',minute:'2-digit'});
                    msgs.insertAdjacentHTML('beforeend', `
                    <div class="msg-row out">
                        <div>
                            <div class="msg-bubble">${esc(text)}</div>
                            <div class="msg-meta">${hr} <span class="msg-check check-sent">✓</span></div>
                        </div>
                    </div>`);
                    scrollBottom();
                }
                input.value = '';
                autoH(input);
                if (data.simulated) {
                    Swal.fire({ toast:true, position:'top-end', icon:'info', title:'Modo simulado (sin API)', timer:2000, showConfirmButton:false });
                }
            } else {
                Swal.fire({ icon:'error', title:'Error', text: data.error || 'No se pudo enviar' });
            }
        })
        .finally(() => btn.disabled = false);
    }

    // ── Recordatorio rápido ───────────────────────────────────────────────────
    function enviarRecordatorio(clienteId) {
        Swal.fire({
            title: '¿Enviar recordatorio?',
            text: 'Se enviará el recordatorio de cuota pendiente al cliente.',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#25d366', confirmButtonText: 'Enviar', cancelButtonText: 'Cancelar',
        }).then(r => {
            if (!r.isConfirmed) return;
            fetch(`/whatsapp/${TENANT}/reminder/${clienteId}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'✓ Recordatorio enviado', timer:2000, showConfirmButton:false });
                    if (activeConvId) abrirConversacion(activeConvId);
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: data.error });
                }
            });
        });
    }

    // ── Mensaje rápido ────────────────────────────────────────────────────────
    function quickMsg(tipo) {
        if (!activeConvId) return;
        fetch(`/whatsapp/${TENANT}/quick-message`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF },
            body: JSON.stringify({ tipo, conversation_id: activeConvId }),
        })
        .then(r => r.json())
        .then(data => {
            const input = document.getElementById('msgInput');
            if (input && data.texto) {
                input.value = data.texto;
                input.focus();
                autoH(input);
            }
        });
    }

    // ── Utils ─────────────────────────────────────────────────────────────────
    function onEnter(e) {
        if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); enviarMensaje(); }
    }

    function autoH(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function scrollBottom() {
        const c = document.getElementById('chatMessages');
        if (c) c.scrollTop = c.scrollHeight;
    }

    function esc(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    if (activeConvId) document.addEventListener('DOMContentLoaded', scrollBottom);
    </script>
</body>
</html>
