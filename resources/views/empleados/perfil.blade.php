@extends('empleados._layout')

@php
    $nombreEmpleado = $empleado->vendedor?->nombre_completo ?? $empleado->cobrador?->nombre_completo ?? $empleado->name;
    $ruta = $empleado->cobrador?->rutasCobro?->first();
    $fotoUrl = $employeeProfile?->foto ? \Illuminate\Support\Facades\Storage::url($employeeProfile->foto) : null;

    $statusCfg = [
        'activa' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Activo'],
        'bloqueada' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Bloqueado'],
        'desactivada' => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Desactivado'],
    ][$empleado->account_status ?? 'activa'] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => '—'];

    $hayDocVerificado = $documentos->where('estado_verificacion', 'verificado')->isNotEmpty();

    $estadoLaboralCfg = [
        'activo' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Activo'],
        'suspendido' => ['bg' => '#fef9c3', 'color' => '#854d0e', 'label' => 'Suspendido'],
        'inactivo' => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Inactivo'],
        'despedido' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Despedido'],
        'renuncia' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Renuncia'],
    ][$employeeProfile?->estado_laboral ?? ''] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => '—'];

    $verifCfg = [
        'pendiente' => ['bg' => '#fef9c3', 'color' => '#854d0e', 'label' => 'Pendiente'],
        'verificado' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Verificado'],
        'rechazado' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Rechazado'],
    ];

    $tiposDocumento = [
        'dui_frente' => 'DUI — Frente', 'dui_reverso' => 'DUI — Reverso', 'contrato' => 'Contrato laboral',
        'comprobante_domicilio' => 'Comprobante de domicilio', 'licencia' => 'Licencia de conducir', 'otro' => 'Otro',
    ];
@endphp

@section('page-title', $nombreEmpleado)
@section('breadcrumb-current', $nombreEmpleado)

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container--default .select2-selection--multiple { border:1px solid #d1d5db; border-radius:.5rem; min-height:2.3rem; padding:.15rem .25rem; }
    .select2-container--default.select2-container--focus .select2-selection--multiple { border-color:#10b981; box-shadow:0 0 0 2px rgba(16,185,129,.2); }
    .select2-container--default .select2-selection--multiple .select2-selection__choice { background:#d1fae5; border-color:#a7f3d0; color:#065f46; font-size:.78rem; padding:.05rem .5rem; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color:#065f46; margin-right:.35rem; }
    .select2-dropdown { border-color:#d1d5db; }
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color:#10b981; }

    .pe-card { background:#fff; border:1px solid #e5e7eb; border-radius:.875rem; box-shadow:0 1px 3px rgba(0,0,0,.05); overflow:hidden; }
    .pe-card-header { display:flex; align-items:center; justify-content:space-between; padding:.85rem 1.1rem; border-bottom:1px solid #f3f4f6; }
    .pe-card-title { font-size:.85rem; font-weight:700; color:#111827; }
    .pe-card-link { font-size:.74rem; font-weight:600; color:#10b981; text-decoration:none; }
    .pe-card-link:hover { text-decoration:underline; }
    .pe-label { font-size:.68rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:.03em; }
    .pe-value { font-size:.86rem; font-weight:600; color:#111827; margin-top:.15rem; }
    .pe-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .65rem; border-radius:9999px; font-size:.7rem; font-weight:700; }
    .pe-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
    .pe-empty { padding:2.25rem; text-align:center; color:#9ca3af; font-size:.85rem; }
    .pe-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1rem; border-radius:.6rem; font-size:.8rem; font-weight:600; border:none; cursor:pointer; text-decoration:none; }
    .pe-btn-primary { background:#2563eb; color:#fff; }
    .pe-btn-primary:hover { background:#1d4ed8; }
    .pe-btn-warning { background:#f59e0b; color:#fff; }
    .pe-btn-warning:hover { background:#d97706; }
    .pe-btn-danger { background:#dc2626; color:#fff; }
    .pe-btn-danger:hover { background:#b91c1c; }
    .pe-btn-success { background:#16a34a; color:#fff; }
    .pe-btn-gray { background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }
    .pe-btn-gray:hover { background:#e5e7eb; }
    .pe-link-quick { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; border-radius:.5rem; padding:.45rem .8rem; cursor:pointer; text-decoration:none; }
    .pe-link-quick:hover { background:#f3f4f6; }

    .pe-tabs-nav { display:flex; flex-wrap:wrap; gap:.25rem; border-bottom:1px solid #e5e7eb; margin:1.25rem 0; }
    .pe-tab-btn { padding:.6rem 1rem; font-size:.82rem; font-weight:600; color:#6b7280; background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; margin-bottom:-1px; }
    .pe-tab-btn.active { color:#10b981; border-bottom-color:#10b981; }

    .pe-grid-main { display:grid; grid-template-columns:1.6fr 1fr; gap:1.25rem; align-items:start; }
    @media (max-width:1000px) { .pe-grid-main { grid-template-columns:1fr; } }

    .pe-table { width:100%; border-collapse:collapse; }
    .pe-table th { padding:.5rem .9rem; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; background:#f9fafb; border-bottom:1px solid #f3f4f6; text-align:left; }
    .pe-table td { padding:.55rem .9rem; font-size:.82rem; color:#374151; border-bottom:1px solid #f9fafb; }

    .pe-doc-row { display:flex; align-items:center; gap:.7rem; padding:.65rem 1.1rem; border-bottom:1px solid #f9fafb; }
    .pe-doc-icon { width:34px; height:34px; border-radius:.5rem; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .pe-doc-name { font-size:.8rem; font-weight:600; color:#111827; }
    .pe-doc-date { font-size:.68rem; color:#9ca3af; }

    .pe-perf-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1.25rem; padding:1.1rem; }
    @media (max-width:700px) { .pe-perf-grid { grid-template-columns:repeat(2,1fr); } }
    .pe-perf-label { font-size:.68rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.03em; }
    .pe-perf-num { font-size:1.2rem; font-weight:800; color:#111827; margin:.2rem 0; }
    .pe-perf-delta { font-size:.7rem; font-weight:700; color:#16a34a; }

    .pe-progress-bar { width:100%; height:6px; background:#e5e7eb; border-radius:3px; overflow:hidden; margin-top:.4rem; }
    .pe-progress-fill { height:100%; border-radius:3px; background:#10b981; }

    .pe-spark { width:100%; height:34px; display:block; margin-top:.4rem; }

    .pe-form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; padding:1.1rem; }
    @media (max-width:700px) { .pe-form-grid { grid-template-columns:1fr; } }
    .pe-form-grid label { display:block; font-size:.72rem; font-weight:600; color:#374151; margin-bottom:.3rem; }
    .pe-input { width:100%; padding:.5rem .7rem; border:1px solid #e5e7eb; border-radius:.5rem; font-size:.82rem; color:#111827; outline:none; }
    .pe-input:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
    .pe-form-actions { padding:0 1.1rem 1.1rem; }

    .pe-alert { padding:.75rem 1rem; border-radius:.6rem; font-size:.82rem; font-weight:600; margin-bottom:1rem; }
    .pe-alert-success { background:#dcfce7; color:#166534; }
    .pe-alert-warning { background:#fef9c3; color:#854d0e; }

    #pe-mapa-ubicacion { height:340px; }

    /* ── Modo oscuro ── */
    html.dark .pe-card { background:var(--card); border-color:var(--border); box-shadow:none; }
    html.dark .pe-card-header { border-bottom-color:var(--border-2); }
    html.dark .pe-card-title { color:var(--text); }
    html.dark .pe-label { color:var(--muted-2); }
    html.dark .pe-value { color:var(--text); }
    html.dark .pe-empty { color:var(--muted-2); }
    html.dark .pe-btn-gray { background:var(--subtle); color:var(--text-2); border-color:var(--border); }
    html.dark .pe-btn-gray:hover { background:var(--border); }
    html.dark .pe-link-quick { color:var(--text-2); background:var(--subtle); border-color:var(--border); }
    html.dark .pe-link-quick:hover { background:var(--border); }
    html.dark .pe-tabs-nav { border-bottom-color:var(--border); }
    html.dark .pe-tab-btn { color:var(--muted); }
    html.dark .pe-table th { color:var(--muted-2); background:var(--subtle); border-bottom-color:var(--border-2); }
    html.dark .pe-table td { color:var(--text-2); border-bottom-color:var(--border-2); }
    html.dark .pe-doc-row { border-bottom-color:var(--border-2); }
    html.dark .pe-doc-icon { background:rgba(220,38,38,.18); }
    html.dark .pe-doc-name { color:var(--text); }
    html.dark .pe-doc-date { color:var(--muted-2); }
    html.dark .pe-perf-label { color:var(--muted-2); }
    html.dark .pe-perf-num { color:var(--text); }
    html.dark .pe-progress-bar { background:var(--border); }
    html.dark .pe-form-grid label { color:var(--text-2); }
    html.dark .pe-input { background:var(--subtle); border-color:var(--border); color:var(--text); }
    html.dark .pe-input:focus { background:var(--card); }
    html.dark .pe-alert-success { background:rgba(34,197,94,.15); color:#86efac; }
    html.dark .pe-alert-warning { background:rgba(202,138,4,.15); color:#fde047; }
</style>
@endsection

@section('content')
<div x-data="{ tab: '{{ request('tab', 'resumen') }}' }">

    @if(session('password_temporal'))
        <div class="pe-alert pe-alert-warning">Nueva contraseña temporal generada: <strong>{{ session('password_temporal') }}</strong> — entrégasela al empleado y pídele que la cambie al iniciar sesión.</div>
    @endif

    {{-- ── Header ── --}}
    <div class="pe-card" style="padding:1.5rem;">
        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;align-items:flex-start;justify-content:space-between;">
            <div style="display:flex;gap:1.25rem;flex:1;min-width:280px;">
                @if($fotoUrl)
                    <img src="{{ $fotoUrl }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #f3f4f6;flex-shrink:0;">
                @else
                    <div style="width:72px;height:72px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:1.4rem;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($nombreEmpleado, 0, 1)) }}
                    </div>
                @endif
                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                        <h1 style="font-size:1.2rem;font-weight:700;color:var(--text);">{{ $nombreEmpleado }}</h1>
                        <span class="pe-badge" style="background:{{ $statusCfg['bg'] }};color:{{ $statusCfg['color'] }};"><span class="pe-dot"></span>{{ $statusCfg['label'] }}</span>
                        @if($hayDocVerificado)
                            <span class="pe-badge" style="background:#dbeafe;color:#1d4ed8;">Verificado</span>
                        @endif
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:1.25rem;margin-top:.6rem;">
                        <div><div class="pe-label">Sucursal</div><div class="pe-value" style="font-size:.78rem;">{{ $perfilLaboral?->sucursal?->nombre ?? '—' }}</div></div>
                        <div><div class="pe-label">Ruta</div><div class="pe-value" style="font-size:.78rem;">{{ $ruta?->nombre ?? '—' }}</div></div>
                        <div><div class="pe-label">Supervisor</div><div class="pe-value" style="font-size:.78rem;">{{ $supervisor?->name ?? '—' }}</div></div>
                        <div><div class="pe-label">Código</div><div class="pe-value" style="font-size:.78rem;">{{ $employeeProfile?->codigo_empleado ?? '—' }}</div></div>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;flex-shrink:0;">
                <button type="button" class="pe-btn pe-btn-primary" @click="tab='personal'">Editar</button>
                <form method="POST" action="{{ route('empleados.resetearPassword', [$tenant, $empleado->id]) }}" style="display:inline;"
                      data-confirm="Se generará una nueva contraseña aleatoria para este usuario." data-confirm-title="¿Resetear contraseña?"
                      data-confirm-icon="warning" data-confirm-color="#f59e0b" data-confirm-button="Sí, resetear">
                    @csrf
                    <button type="submit" class="pe-btn pe-btn-warning">Resetear contraseña</button>
                </form>
                <form method="POST" action="{{ route('empleados.toggleBloqueo', [$tenant, $empleado->id]) }}" style="display:inline;"
                      data-confirm="{{ $empleado->estaBloqueado() ? 'Se reactivará el acceso de este usuario al sistema y al POS móvil.' : 'Este usuario no podrá iniciar sesión en el panel ni usar el POS móvil.' }}"
                      data-confirm-title="{{ $empleado->estaBloqueado() ? '¿Reactivar acceso?' : '¿Bloquear acceso?' }}"
                      data-confirm-icon="{{ $empleado->estaBloqueado() ? 'question' : 'warning' }}"
                      data-confirm-color="{{ $empleado->estaBloqueado() ? '#16a34a' : '#dc2626' }}"
                      data-confirm-button="{{ $empleado->estaBloqueado() ? 'Sí, reactivar' : 'Sí, bloquear' }}">
                    @csrf
                    <button type="submit" class="pe-btn {{ $empleado->estaBloqueado() ? 'pe-btn-success' : 'pe-btn-danger' }}">{{ $empleado->estaBloqueado() ? 'Desbloquear acceso' : 'Bloquear acceso' }}</button>
                </form>
                <div style="position:relative;" x-data="{ open: false }">
                    <button type="button" class="pe-btn pe-btn-gray" @click="open = !open">Más acciones ▾</button>
                    <div x-show="open" @click.outside="open = false" style="position:absolute;right:0;top:110%;background:var(--card);border:1px solid var(--border);border-radius:.6rem;box-shadow:0 6px 18px rgba(0,0,0,.08);min-width:220px;z-index:50;overflow:hidden;" x-cloak>
                        <a href="{{ route('empleados.descargarExpediente', [$tenant, $empleado->id]) }}" style="display:block;padding:.6rem .9rem;font-size:.8rem;color:var(--text-2);text-decoration:none;">Descargar expediente</a>
                        <form method="POST" action="{{ route('empleados.cerrarSesiones', [$tenant, $empleado->id]) }}"
                              data-confirm="Se cerrará la sesión web y se revocarán todos los tokens del POS móvil de este usuario."
                              data-confirm-title="¿Cerrar todas las sesiones?" data-confirm-icon="warning" data-confirm-button="Sí, cerrar sesiones">
                            @csrf
                            <button type="submit" style="display:block;width:100%;text-align:left;padding:.6rem .9rem;font-size:.8rem;color:#dc2626;background:none;border:none;cursor:pointer;">Cerrar sesión en todos los dispositivos</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border-2);">
            <div>
                <div class="pe-label">Ventas del mes</div>
                <div class="pe-value" style="font-size:1.1rem;">${{ number_format($ventasMes, 2) }}</div>
                <canvas id="sparkVentas" class="pe-spark"></canvas>
            </div>
            <div>
                <div class="pe-label">Cobros del mes</div>
                <div class="pe-value" style="font-size:1.1rem;">${{ number_format($cobrosMes, 2) }}</div>
                <canvas id="sparkCobros" class="pe-spark"></canvas>
            </div>
            <div>
                <div class="pe-label">Clientes asignados</div>
                <div class="pe-value" style="font-size:1.1rem;">{{ $clientesAsignados }} <span style="font-size:.72rem;color:#9ca3af;font-weight:500;">({{ $clientesActivos }} activos)</span></div>
            </div>
            <div>
                <div class="pe-label">Última conexión</div>
                <div class="pe-value" style="font-size:.85rem;">{{ $posDevice?->ultimo_ping?->diffForHumans() ?? 'Sin registro' }}</div>
                <div style="font-size:.72rem;color:#9ca3af;">{{ $posDevice?->nombre ?? '—' }}</div>
            </div>
            <div>
                <div class="pe-label">Última ubicación</div>
                <div class="pe-value" style="font-size:.85rem;display:flex;align-items:center;gap:.3rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $employeeProfile?->municipio ?? ($posDevice?->latitud ? 'Ver en mapa' : 'Sin registro') }}
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.1rem;">
            <button type="button" class="pe-link-quick" @click="tab='ubicacion'">Ver ubicación</button>
            <button type="button" class="pe-link-quick" @click="tab='historial'">Ver historial</button>
            <button type="button" class="pe-link-quick" @click="tab='rutas'">Asignaciones</button>
            <a href="{{ route('empleados.descargarExpediente', [$tenant, $empleado->id]) }}" class="pe-link-quick">Descargar expediente</a>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="pe-tabs-nav">
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'resumen' }" @click="tab='resumen'">Resumen</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'personal' }" @click="tab='personal'">Información personal</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'laboral' }" @click="tab='laboral'">Información laboral</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'acceso' }" @click="tab='acceso'">Acceso y permisos</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'rutas' }" @click="tab='rutas'">Rutas y asignaciones</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'documentos' }" @click="tab='documentos'">Documentos</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'actividad' }" @click="tab='actividad'">Actividad</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'ubicacion' }" @click="tab='ubicacion'; setTimeout(() => window.initMapaPerfilEmpleado && window.initMapaPerfilEmpleado(), 60)">Ubicación</button>
        <button type="button" class="pe-tab-btn" :class="{ active: tab === 'historial' }" @click="tab='historial'">Historial</button>
    </div>

    {{-- ── RESUMEN ── --}}
    <div x-show="tab === 'resumen'" class="pe-grid-main">
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Información personal</span></div>
                <div class="pe-form-grid" style="grid-template-columns:1fr 1fr;">
                    <div>
                        <div class="pe-label">Nombre completo</div><div class="pe-value">{{ $nombreEmpleado }}</div>
                    </div>
                    <div><div class="pe-label">Departamento</div><div class="pe-value">{{ $employeeProfile?->departamento ?? '—' }}</div></div>
                    <div><div class="pe-label">DUI</div><div class="pe-value">{{ $employeeProfile?->dui ?? '—' }}</div></div>
                    <div><div class="pe-label">Municipio</div><div class="pe-value">{{ $employeeProfile?->municipio ?? '—' }}</div></div>
                    <div><div class="pe-label">Fecha de nacimiento</div><div class="pe-value">{{ $employeeProfile?->fecha_nacimiento?->format('d/m/Y') ?? '—' }} @if($employeeProfile?->edad) <span style="color:#9ca3af;font-weight:400;">({{ $employeeProfile->edad }} años)</span>@endif</div></div>
                    <div><div class="pe-label">Dirección</div><div class="pe-value">{{ $employeeProfile?->direccion ?? '—' }}</div></div>
                    <div><div class="pe-label">Género</div><div class="pe-value">{{ ucfirst($employeeProfile?->genero ?? '—') }}</div></div>
                    <div><div class="pe-label">Tipo de sangre</div><div class="pe-value">{{ $employeeProfile?->tipo_sangre ?? '—' }}</div></div>
                    <div><div class="pe-label">Estado civil</div><div class="pe-value">{{ ucfirst($employeeProfile?->estado_civil ?? '—') }}</div></div>
                    <div><div class="pe-label">Nacionalidad</div><div class="pe-value">{{ $employeeProfile?->nacionalidad ?? '—' }}</div></div>
                    <div><div class="pe-label">Teléfono principal</div><div class="pe-value">{{ $perfilLaboral?->telefono ?? '—' }}</div></div>
                    <div><div class="pe-label">Número de afiliación</div><div class="pe-value">{{ $employeeProfile?->numero_afiliacion ?? '—' }}</div></div>
                    <div><div class="pe-label">WhatsApp</div><div class="pe-value">{{ $employeeProfile?->telefono_whatsapp ?? '—' }}</div></div>
                    <div><div class="pe-label">Contacto de emergencia</div><div class="pe-value">{{ $employeeProfile?->contacto_emergencia_nombre ?? '—' }}</div></div>
                    <div><div class="pe-label">Correo electrónico</div><div class="pe-value">{{ $empleado->email }}</div></div>
                    <div><div class="pe-label"># emergencia</div><div class="pe-value">{{ $employeeProfile?->contacto_emergencia_telefono ?? '—' }}</div></div>
                </div>
                <div style="padding:0 1.1rem 1.1rem;">
                    <button type="button" class="pe-card-link" @click="tab='personal'" style="background:none;border:none;cursor:pointer;">Editar información personal →</button>
                </div>
            </div>

            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Resumen de rendimiento (este mes)</span></div>
                <div class="pe-perf-grid">
                    <div>
                        <div class="pe-perf-label">Ventas</div>
                        <div class="pe-perf-num">${{ number_format($ventasMes, 2) }}</div>
                    </div>
                    <div>
                        <div class="pe-perf-label">Cobros</div>
                        <div class="pe-perf-num">${{ number_format($cobrosMes, 2) }}</div>
                    </div>
                    <div>
                        <div class="pe-perf-label">Meta de ventas</div>
                        <div class="pe-perf-num">{{ $metaVentasPct !== null ? $metaVentasPct.'%' : '—' }}</div>
                        @if($metaVentasPct !== null)
                            <div class="pe-progress-bar"><div class="pe-progress-fill" style="width:{{ $metaVentasPct }}%;"></div></div>
                        @endif
                    </div>
                    <div>
                        <div class="pe-perf-label">Meta de cobros</div>
                        <div class="pe-perf-num">{{ $metaCobrosPct !== null ? $metaCobrosPct.'%' : '—' }}</div>
                        @if($metaCobrosPct !== null)
                            <div class="pe-progress-bar"><div class="pe-progress-fill" style="width:{{ $metaCobrosPct }}%;background:#2563eb;"></div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div class="pe-card">
                <div class="pe-card-header">
                    <span class="pe-card-title">Documentos</span>
                    <button type="button" class="pe-card-link" @click="tab='documentos'" style="background:none;border:none;cursor:pointer;">Ver todos →</button>
                </div>
                @if($documentos->isEmpty())
                    <div class="pe-empty">Sin documentos cargados.</div>
                @else
                    @foreach($documentos->take(5) as $doc)
                        @php $vc = $verifCfg[$doc->estado_verificacion] ?? $verifCfg['pendiente']; @endphp
                        <div class="pe-doc-row">
                            <div class="pe-doc-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="pe-doc-name">{{ $tiposDocumento[$doc->tipo] ?? $doc->tipo }}</div>
                                <div class="pe-doc-date">{{ $doc->created_at->format('d/m/Y') }}</div>
                            </div>
                            <span class="pe-badge" style="background:{{ $vc['bg'] }};color:{{ $vc['color'] }};">{{ $vc['label'] }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Dispositivo autorizado</span></div>
                @if($posDevice)
                    <div style="padding:1.1rem;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div class="pe-value">{{ $posDevice->nombre }}</div>
                            <span class="pe-badge" style="background:{{ $posDevice->estado_calc === 'activo' ? '#dcfce7' : '#fee2e2' }};color:{{ $posDevice->estado_calc === 'activo' ? '#166534' : '#991b1b' }};">{{ ucfirst($posDevice->estado_calc) }}</span>
                        </div>
                        <div class="pe-label" style="margin-top:.6rem;">Última conexión</div>
                        <div class="pe-value" style="font-size:.8rem;">{{ $posDevice->ultimo_ping?->diffForHumans() ?? '—' }}</div>
                    </div>
                @else
                    <div class="pe-empty">Sin dispositivo POS registrado.</div>
                @endif
            </div>

            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Estado de ubicación</span></div>
                @if($posDevice?->latitud)
                    <div style="padding:1.1rem;">
                        <span class="pe-badge" style="background:{{ $posDevice->estado_calc !== 'sin_conexion' ? '#dcfce7' : '#fee2e2' }};color:{{ $posDevice->estado_calc !== 'sin_conexion' ? '#166534' : '#991b1b' }};">{{ $posDevice->estado_calc !== 'sin_conexion' ? 'En línea' : 'Desconectado' }}</span>
                        <div class="pe-label" style="margin-top:.6rem;">Última ubicación</div>
                        <div class="pe-value" style="font-size:.8rem;">{{ $posDevice->ultimo_ping?->diffForHumans() ?? '—' }}</div>
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.15rem;">{{ $employeeProfile?->municipio }}{{ $employeeProfile?->municipio && $employeeProfile?->departamento ? ', ' : '' }}{{ $employeeProfile?->departamento }}</div>
                        <button type="button" class="pe-card-link" @click="tab='ubicacion'" style="background:none;border:none;cursor:pointer;margin-top:.5rem;display:inline-block;">Ver en mapa →</button>
                    </div>
                @else
                    <div class="pe-empty">Sin ubicación GPS registrada.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── INFORMACIÓN PERSONAL (editable) ── --}}
    <div x-show="tab === 'personal'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Editar información personal</span></div>
        <form method="POST" action="{{ route('empleados.actualizarPersonal', [$tenant, $empleado->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="pe-form-grid">
                <div>
                    <label>Foto de perfil</label>
                    <input type="file" name="foto" accept="image/*" class="pe-input">
                </div>
                <div></div>
                <div><label>DUI</label><input type="text" name="dui" value="{{ old('dui', $employeeProfile?->dui) }}" class="pe-input" placeholder="00000000-0"></div>
                <div><label>NIT</label><input type="text" name="nit" value="{{ old('nit', $employeeProfile?->nit) }}" class="pe-input"></div>
                <div>
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $employeeProfile?->fecha_nacimiento?->format('Y-m-d')) }}" class="pe-input">
                </div>
                <div>
                    <label>Tipo de sangre</label>
                    <input type="text" name="tipo_sangre" value="{{ old('tipo_sangre', $employeeProfile?->tipo_sangre) }}" class="pe-input" placeholder="O+">
                </div>
                <div>
                    <label>Género</label>
                    <select name="genero" class="pe-input">
                        <option value="">—</option>
                        @foreach(['masculino'=>'Masculino','femenino'=>'Femenino','otro'=>'Otro'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('genero', $employeeProfile?->genero) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Nacionalidad</label>
                    <input type="text" name="nacionalidad" value="{{ old('nacionalidad', $employeeProfile?->nacionalidad) }}" class="pe-input">
                </div>
                <div>
                    <label>Estado civil</label>
                    <select name="estado_civil" class="pe-input">
                        <option value="">—</option>
                        @foreach(['soltero'=>'Soltero(a)','casado'=>'Casado(a)','divorciado'=>'Divorciado(a)','viudo'=>'Viudo(a)','acompanado'=>'Acompañado(a)'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('estado_civil', $employeeProfile?->estado_civil) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Número de afiliación</label>
                    <input type="text" name="numero_afiliacion" value="{{ old('numero_afiliacion', $employeeProfile?->numero_afiliacion) }}" class="pe-input">
                </div>
                <div><label>Teléfono WhatsApp</label><input type="text" name="telefono_whatsapp" value="{{ old('telefono_whatsapp', $employeeProfile?->telefono_whatsapp) }}" class="pe-input"></div>
                <div><label>Contacto de emergencia</label><input type="text" name="contacto_emergencia_nombre" value="{{ old('contacto_emergencia_nombre', $employeeProfile?->contacto_emergencia_nombre) }}" class="pe-input"></div>
                <div><label>Departamento</label><input type="text" name="departamento" value="{{ old('departamento', $employeeProfile?->departamento) }}" class="pe-input"></div>
                <div><label>Teléfono de emergencia</label><input type="text" name="contacto_emergencia_telefono" value="{{ old('contacto_emergencia_telefono', $employeeProfile?->contacto_emergencia_telefono) }}" class="pe-input"></div>
                <div><label>Municipio</label><input type="text" name="municipio" value="{{ old('municipio', $employeeProfile?->municipio) }}" class="pe-input"></div>
                <div></div>
                <div style="grid-column:1/-1;"><label>Dirección</label><input type="text" name="direccion" value="{{ old('direccion', $employeeProfile?->direccion) }}" class="pe-input"></div>
            </div>
            <div class="pe-form-actions">
                <button type="submit" class="pe-btn pe-btn-primary">Guardar información personal</button>
            </div>
        </form>
    </div>

    {{-- ── INFORMACIÓN LABORAL (editable) ── --}}
    <div x-show="tab === 'laboral'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Editar información laboral</span></div>
        <form method="POST" action="{{ route('empleados.actualizarLaboral', [$tenant, $empleado->id]) }}">
            @csrf
            <div class="pe-form-grid">
                <div><label>Cargo / Puesto</label><input type="text" name="cargo" value="{{ old('cargo', $employeeProfile?->cargo) }}" class="pe-input"></div>
                <div style="grid-column:1/-1;">
                    <label>Tipo de empleado <span style="font-weight:400;color:var(--muted,#6b7280);">(puede marcar varios — ej. vendedor y cobrador a la vez)</span></label>
                    <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:.35rem;">
                        @php $tiposActuales = old('tipo_empleado', $employeeProfile?->tipo_empleado ?? []); @endphp
                        @foreach(['vendedor'=>'Vendedor','cobrador'=>'Cobrador','supervisor'=>'Supervisor'] as $val => $lbl)
                            <label style="display:flex; align-items:center; gap:.4rem; font-weight:400;">
                                <input type="checkbox" name="tipo_empleado[]" value="{{ $val }}" {{ in_array($val, $tiposActuales) ? 'checked' : '' }}>
                                {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div><label>Fecha de ingreso</label><input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', $employeeProfile?->fecha_ingreso?->format('Y-m-d')) }}" class="pe-input"></div>
                <div><label>Fecha de salida</label><input type="date" name="fecha_salida" value="{{ old('fecha_salida', $employeeProfile?->fecha_salida?->format('Y-m-d')) }}" class="pe-input"></div>
                <div><label>Salario base</label><input type="number" step="0.01" name="salario_base" value="{{ old('salario_base', $employeeProfile?->salario_base) }}" class="pe-input"></div>
                <div>
                    <label>Tipo de contrato</label>
                    <select name="tipo_contrato" class="pe-input">
                        <option value="">—</option>
                        @foreach(['indefinido'=>'Indefinido','temporal'=>'Temporal','por_obra'=>'Por obra','practica'=>'Práctica'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('tipo_contrato', $employeeProfile?->tipo_contrato) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Modalidad de pago</label>
                    <select name="modalidad_pago" class="pe-input" id="pe-modalidad-pago" onchange="document.getElementById('pe-comision-wrap').style.display = this.value === 'salario_fijo' ? 'none' : ''">
                        <option value="">—</option>
                        @foreach(['salario_fijo'=>'Salario fijo','comision'=>'Por comisión','mixto'=>'Mixto (salario + comisión)'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('modalidad_pago', $employeeProfile?->modalidad_pago) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="pe-comision-wrap" style="{{ old('modalidad_pago', $employeeProfile?->modalidad_pago) === 'salario_fijo' || !old('modalidad_pago', $employeeProfile?->modalidad_pago) ? 'display:none;' : '' }}">
                    <label>% de comisión</label>
                    <input type="number" step="0.01" min="0" max="100" name="porcentaje_comision" value="{{ old('porcentaje_comision', $employeeProfile?->porcentaje_comision) }}" class="pe-input" placeholder="Ej. 5.00">
                </div>
                <div><label>Meta de ventas mensual</label><input type="number" step="0.01" name="meta_ventas_mensual" value="{{ old('meta_ventas_mensual', $employeeProfile?->meta_ventas_mensual) }}" class="pe-input"></div>
                <div><label>Meta de cobros mensual</label><input type="number" step="0.01" name="meta_cobros_mensual" value="{{ old('meta_cobros_mensual', $employeeProfile?->meta_cobros_mensual) }}" class="pe-input"></div>
                <div style="grid-column:1/-1;"><label>Horario laboral</label><input type="text" name="horario_laboral" value="{{ old('horario_laboral', $employeeProfile?->horario_laboral) }}" class="pe-input"></div>
                <div>
                    <label>Hora de entrada esperada <span style="font-weight:400;color:var(--muted,#6b7280);">(para asistencia)</span></label>
                    <input type="time" name="hora_entrada_esperada" value="{{ old('hora_entrada_esperada', $employeeProfile?->hora_entrada_esperada ? substr($employeeProfile->hora_entrada_esperada, 0, 5) : '') }}" class="pe-input">
                </div>
                <div>
                    <label>Hora de salida esperada</label>
                    <input type="time" name="hora_salida_esperada" value="{{ old('hora_salida_esperada', $employeeProfile?->hora_salida_esperada ? substr($employeeProfile->hora_salida_esperada, 0, 5) : '') }}" class="pe-input">
                </div>
                <div>
                    <label>Código de asistencia <span style="font-weight:400;color:var(--muted,#6b7280);">(el número que le diste al inscribirlo en el equipo Hikvision)</span></label>
                    <input type="text" name="codigo_asistencia" value="{{ old('codigo_asistencia', $employeeProfile?->codigo_asistencia) }}" class="pe-input" placeholder="Ej. 1001">
                </div>
                <div>
                    <label>Estado laboral</label>
                    <select name="estado_laboral" class="pe-input" required>
                        @foreach(['activo'=>'Activo','suspendido'=>'Suspendido','inactivo'=>'Inactivo','despedido'=>'Despedido','renuncia'=>'Renuncia'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('estado_laboral', $employeeProfile?->estado_laboral ?? 'activo') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Jefe inmediato</label>
                    <select name="supervisor_id" class="pe-input">
                        <option value="">—</option>
                        @foreach(\App\Models\User::where('id','!=',$empleado->id)->orderBy('name')->get() as $u)
                            <option value="{{ $u->id }}" {{ (string) old('supervisor_id', $employeeProfile?->supervisor_id) === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;display:flex;align-items:center;gap:.5rem;">
                    <input type="checkbox" name="puede_usar_pos_movil" id="puede_usar_pos_movil" value="1" {{ old('puede_usar_pos_movil', $employeeProfile?->puede_usar_pos_movil ?? true) ? 'checked' : '' }}>
                    <label for="puede_usar_pos_movil" style="margin:0;">Permitir uso del POS móvil</label>
                </div>
                <div style="grid-column:1/-1;">
                    <label>Rutas supervisadas <span style="font-weight:400;color:var(--muted,#6b7280);">(solo aplica si el tipo de empleado es Supervisor)</span></label>
                    <select name="rutas_supervisadas[]" id="pe-rutas-supervisadas" class="pe-input" multiple>
                        @foreach($rutasCobro as $ruta)
                            <option value="{{ $ruta->id }}" {{ in_array($ruta->id, old('rutas_supervisadas', $rutasSupervisadasIds)) ? 'selected' : '' }}>{{ $ruta->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="pe-form-actions">
                <button type="submit" class="pe-btn pe-btn-primary">Guardar información laboral</button>
            </div>
        </form>
    </div>

    {{-- ── PAGOS AL EMPLEADO ── --}}
    <div x-show="tab === 'laboral'" class="pe-card">
        <div class="pe-card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span class="pe-card-title">Pagos registrados</span>
            <a href="{{ route('empleados.contrato', [$tenant, $empleado->id]) }}" target="_blank" class="pe-btn pe-btn-gray" style="padding:.4rem .8rem; font-size:.78rem;">📄 Generar contrato de trabajo</a>
        </div>

        <form method="POST" action="{{ route('empleados.registrarPago', [$tenant, $empleado->id]) }}" style="padding:0 1.25rem 1.25rem;">
            @csrf
            <div class="pe-form-grid">
                <div><label>Mes / Período</label><input type="text" name="mes_periodo" class="pe-input" placeholder="Ej. Agosto 2026" required></div>
                <div><label>Monto</label><input type="number" step="0.01" min="0.01" name="monto" class="pe-input" required></div>
                <div><label>Fecha de pago</label><input type="date" name="fecha_pago" class="pe-input" value="{{ today()->toDateString() }}" required></div>
                <div>
                    <label>Método de pago</label>
                    <select name="metodo_pago" class="pe-input" required>
                        @foreach(['efectivo'=>'Efectivo','transferencia'=>'Transferencia','cheque'=>'Cheque','deposito'=>'Depósito'] as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Referencia</label><input type="text" name="referencia" class="pe-input" placeholder="Opcional"></div>
                <div style="grid-column:1/-1;"><label>Observaciones</label><input type="text" name="observaciones" class="pe-input" placeholder="Opcional"></div>
            </div>
            <div class="pe-form-actions">
                <button type="submit" class="pe-btn pe-btn-primary">Registrar pago</button>
            </div>
        </form>

        @if($pagosEmpleado->isEmpty())
            <div class="pe-empty-hint" style="padding:0 1.25rem 1.25rem;">Todavía no hay pagos registrados.</div>
        @else
            <table class="pe-table">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pagosEmpleado as $pago)
                        <tr>
                            <td>{{ $pago->mes_periodo }}</td>
                            <td>${{ number_format((float) $pago->monto, 2) }}</td>
                            <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($pago->metodo_pago) }}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <a href="{{ route('empleados.constanciaPago', [$tenant, $empleado->id, $pago->id]) }}" target="_blank" class="pe-btn pe-btn-gray" style="padding:.25rem .6rem; font-size:.72rem;">Constancia</a>
                                <form method="POST" action="{{ route('empleados.eliminarPago', [$tenant, $empleado->id, $pago->id]) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este pago?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pe-btn pe-btn-danger" style="padding:.25rem .6rem; font-size:.72rem;">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── ACCESO Y PERMISOS ── --}}
    <div x-show="tab === 'acceso'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Cuenta del sistema</span></div>
        <div class="pe-form-grid" style="grid-template-columns:repeat(3,1fr);">
            <div><div class="pe-label">Usuario</div><div class="pe-value">{{ $empleado->name }}</div></div>
            <div><div class="pe-label">Email de acceso</div><div class="pe-value">{{ $empleado->email }}</div></div>
            <div><div class="pe-label">Roles del sistema</div><div class="pe-value">{{ $empleado->getRoleNames()->implode(', ') ?: '—' }}</div></div>
            <div><div class="pe-label">Estado de la cuenta</div><div><span class="pe-badge" style="background:{{ $statusCfg['bg'] }};color:{{ $statusCfg['color'] }};">{{ $statusCfg['label'] }}</span></div></div>
            <div><div class="pe-label">Último acceso</div><div class="pe-value" style="font-size:.78rem;">{{ $empleado->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</div></div>
            <div><div class="pe-label">Dirección IP</div><div class="pe-value">{{ $empleado->last_login_ip ?? '—' }}</div></div>
            <div style="grid-column:1/-1;"><div class="pe-label">Dispositivo</div><div class="pe-value" style="font-size:.76rem;font-weight:400;color:#6b7280;">{{ $empleado->last_login_device ?? '—' }}</div></div>
        </div>
    </div>

    {{-- ── RUTAS Y ASIGNACIONES ── --}}
    <div x-show="tab === 'rutas'" style="display:flex;flex-direction:column;gap:1.25rem;">
        @if($empleado->cobrador)
            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Rutas de cobro asignadas</span></div>
                @php $rutas = $empleado->cobrador->rutasCobro; @endphp
                @if($rutas->isEmpty())
                    <div class="pe-empty">Sin rutas asignadas.</div>
                @else
                    <table class="pe-table">
                        <thead><tr><th>Ruta</th><th>Día</th><th>Clientes</th><th>Estado</th></tr></thead>
                        <tbody>
                        @foreach($rutas as $r)
                            <tr>
                                <td>{{ $r->nombre }}</td>
                                <td>{{ ucfirst($r->dia_semana ?? '—') }}</td>
                                <td>{{ \App\Models\Cliente::where('ruta_cobro_id', $r->id)->count() }}</td>
                                <td>{{ $r->activa ? 'Activa' : 'Inactiva' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        @if($empleado->vendedor)
            <div class="pe-card">
                <div class="pe-card-header"><span class="pe-card-title">Asignación de hoy (ventas)</span></div>
                @if(!$asignacionHoy)
                    <div class="pe-empty">Sin asignación activa para hoy.</div>
                @else
                    <table class="pe-table">
                        <thead><tr><th>Producto</th><th>Asignado</th><th>Vendido</th><th>Devuelto</th><th>Disponible</th></tr></thead>
                        <tbody>
                        @foreach($asignacionHoy->detalles as $detalle)
                            <tr>
                                <td>{{ $detalle->producto?->nombre ?? '—' }}</td>
                                <td>{{ $detalle->cantidad_asignada }}</td>
                                <td>{{ $detalle->cantidad_vendida }}</td>
                                <td>{{ $detalle->cantidad_devuelta }}</td>
                                <td>{{ $detalle->disponible }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        @if(!$empleado->cobrador && !$empleado->vendedor)
            <div class="pe-card pe-empty">Este usuario no tiene perfil de vendedor ni de cobrador.</div>
        @endif
    </div>

    {{-- ── DOCUMENTOS ── --}}
    <div x-show="tab === 'documentos'" class="pe-card">
        <div class="pe-card-header">
            <span class="pe-card-title">Documentos del empleado</span>
        </div>

        <form method="POST" action="{{ route('empleados.subirDocumento', [$tenant, $empleado->id]) }}" enctype="multipart/form-data" style="padding:1.1rem;border-bottom:1px solid var(--border-2);display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div>
                <label style="display:block;font-size:.72rem;font-weight:600;color:var(--text-2);margin-bottom:.3rem;">Tipo de documento</label>
                <select name="tipo" class="pe-input" required>
                    @foreach($tiposDocumento as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.72rem;font-weight:600;color:var(--text-2);margin-bottom:.3rem;">Archivo</label>
                <input type="file" name="archivo" accept="image/*,application/pdf" class="pe-input" required>
            </div>
            <button type="submit" class="pe-btn pe-btn-primary">Subir documento</button>
        </form>

        @if($documentos->isEmpty())
            <div class="pe-empty">Sin documentos cargados.</div>
        @else
            <table class="pe-table">
                <thead><tr><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Cargado</th><th></th></tr></thead>
                <tbody>
                @foreach($documentos as $doc)
                    @php $vc = $verifCfg[$doc->estado_verificacion] ?? $verifCfg['pendiente']; @endphp
                    <tr>
                        <td>{{ $tiposDocumento[$doc->tipo] ?? $doc->tipo }}</td>
                        <td><a href="{{ \Illuminate\Support\Facades\Storage::url($doc->archivo) }}" target="_blank" style="color:#2563eb;text-decoration:none;">Ver archivo</a></td>
                        <td><span class="pe-badge" style="background:{{ $vc['bg'] }};color:{{ $vc['color'] }};">{{ $vc['label'] }}</span></td>
                        <td style="color:#9ca3af;font-size:.75rem;">{{ $doc->created_at->format('d/m/Y') }}</td>
                        <td style="white-space:nowrap;">
                            @if($doc->estado_verificacion !== 'verificado')
                                <form method="POST" action="{{ route('empleados.verificarDocumento', [$tenant, $empleado->id, $doc->id]) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="estado" value="verificado">
                                    <button type="submit" style="font-size:.72rem;color:#16a34a;background:none;border:none;cursor:pointer;font-weight:600;">Verificar</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('empleados.eliminarDocumento', [$tenant, $empleado->id, $doc->id]) }}" style="display:inline;margin-left:.5rem;"
                                  data-confirm="Esta acción no se puede deshacer." data-confirm-title="¿Eliminar este documento?" data-confirm-button="Sí, eliminar">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="font-size:.72rem;color:#dc2626;background:none;border:none;cursor:pointer;font-weight:600;">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── ACTIVIDAD ── --}}
    <div x-show="tab === 'actividad'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Actividad reciente</span></div>
        @if($actividad->isEmpty())
            <div class="pe-empty">Sin actividad registrada.</div>
        @else
            <table class="pe-table">
                <thead><tr><th>Evento</th><th>Descripción</th><th>Fecha</th></tr></thead>
                <tbody>
                @foreach($actividad as $log)
                    <tr>
                        <td style="text-transform:capitalize;">{{ $log->event ?? '—' }}</td>
                        <td>{{ $log->description }}</td>
                        <td style="color:#9ca3af;font-size:.75rem;">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── UBICACIÓN ── --}}
    <div x-show="tab === 'ubicacion'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Última ubicación registrada</span></div>
        @if($posDevice?->latitud && $posDevice?->longitud)
            <div id="pe-mapa-ubicacion"></div>
        @else
            <div class="pe-empty">Sin ubicación GPS registrada para este empleado.</div>
        @endif
    </div>

    {{-- ── HISTORIAL ── --}}
    <div x-show="tab === 'historial'" class="pe-card">
        <div class="pe-card-header"><span class="pe-card-title">Historial de cambios del perfil</span></div>
        @if($historial->isEmpty())
            <div class="pe-empty">Sin cambios registrados en el perfil.</div>
        @else
            <table class="pe-table">
                <thead><tr><th>Cambio</th><th>Fecha</th></tr></thead>
                <tbody>
                @foreach($historial as $log)
                    <tr>
                        <td>{{ $log->description }}</td>
                        <td style="color:#9ca3af;font-size:.75rem;">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<script>
$('#pe-rutas-supervisadas').select2({
    width: '100%',
    language: 'es',
    placeholder: 'Selecciona las rutas a supervisar...',
    closeOnSelect: false,
});

@if(session('success'))
Swal.fire({
    toast: true, position: 'top-end', icon: 'success', title: @json(session('success')),
    showConfirmButton: false, timer: 3000, timerProgressBar: true,
});
@endif

function mkSpark(id, color, data) {
    return new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: data.map((_, i) => i),
            datasets: [{ data: data, borderColor: color, borderWidth: 1.5, pointRadius: 0, fill: false, tension: 0.4 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, animation: false }
    });
}
mkSpark('sparkVentas', '#10b981', @json($ventasSemana));
mkSpark('sparkCobros', '#2563eb', @json($cobrosSemana));

@if($posDevice?->latitud && $posDevice?->longitud)
window.initMapaPerfilEmpleado = function () {
    if (window.__peMapaInit) { window.__peMapaLeaflet?.invalidateSize(); return; }
    var el = document.getElementById('pe-mapa-ubicacion');
    if (!el || typeof L === 'undefined') return;
    var map = L.map('pe-mapa-ubicacion').setView([{{ $posDevice->latitud }}, {{ $posDevice->longitud }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
    L.circleMarker([{{ $posDevice->latitud }}, {{ $posDevice->longitud }}], { radius: 9, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 0.9 })
        .addTo(map).bindPopup('Última posición: {{ $posDevice->ultimo_ping?->diffForHumans() }}');
    window.__peMapaInit = true;
    window.__peMapaLeaflet = map;
};
@endif
</script>
@endsection
