@extends('whatsapp._layout')

@section('page-title', 'Automatizaciones')

@section('content')

{{-- Header stats --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    @php
        $activas    = $automatizaciones->where('activo', true)->count();
        $inactivas  = $automatizaciones->where('activo', false)->count();
        $logsHoy    = $logs->where('created_at', '>=', today())->count();
        $logsError  = $logs->where('estado', 'failed')->count();
    @endphp
    @foreach([
        ['⚙️','badge-green', $activas,   'Automatizaciones activas'],
        ['💤','badge-gray',  $inactivas, 'Inactivas'],
        ['📤','badge-blue',  $logsHoy,   'Envíos hoy'],
        ['❌','badge-red',   $logsError, 'Errores recientes'],
    ] as [$icon,$badge,$val,$label])
    <div class="card" style="margin:0;display:flex;align-items:center;gap:1rem;padding:1rem;">
        <div style="width:44px;height:44px;border-radius:10px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">{{ $icon }}</div>
        <div>
            <div style="font-size:24px;font-weight:700;line-height:1;">{{ $val }}</div>
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:3px;">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Automatizaciones --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">⚙️ Automatizaciones configuradas</span>
        <div style="display:flex;gap:.5rem;">
            <a href="{{ route('whatsapp.automations.create', $tenant) }}" class="btn btn-primary btn-sm">
                + Nueva automatización
            </a>
        </div>
    </div>

    @if($automatizaciones->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--muted);">
            <p style="font-size:40px;margin-bottom:.75rem;">⚙️</p>
            <p style="font-size:15px;font-weight:600;margin-bottom:.5rem;">Sin automatizaciones</p>
            <p style="font-size:13px;margin-bottom:1.25rem;">Las automatizaciones envían recordatorios de pago automáticamente según las fechas de vencimiento.</p>
            <a href="{{ route('whatsapp.automations.create', $tenant) }}" class="btn btn-primary">+ Crear primera automatización</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Configuración</th>
                        <th>Hora envío</th>
                        <th>Plantilla</th>
                        <th>Cuenta</th>
                        <th style="text-align:center;">Activa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($automatizaciones as $auto)
                    @php
                        $tipos = \App\Models\WhatsappAutomation::tiposLabels();
                        $tipoLabel = $tipos[$auto->tipo] ?? $auto->tipo;
                    @endphp
                    <tr id="row-{{ $auto->id }}">
                        <td style="font-weight:600;">{{ $auto->nombre }}</td>
                        <td>
                            <span class="badge {{ match(true) {
                                str_contains($auto->tipo,'mora')       => 'badge-red',
                                str_contains($auto->tipo,'vencimiento')=> 'badge-yellow',
                                str_contains($auto->tipo,'pago')       => 'badge-green',
                                default                                => 'badge-blue'
                            } }}">{{ $tipoLabel }}</span>
                        </td>
                        <td style="font-size:12px;color:var(--muted);">
                            @if($auto->dias_antes)
                                {{ $auto->dias_antes }} día(s) antes
                            @elseif($auto->dias_despues)
                                {{ $auto->dias_despues }} día(s) después
                            @else
                                El mismo día
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($auto->hora_envio)->format('H:i') }} hrs</td>
                        <td>
                            @if($auto->plantilla)
                                <span style="font-family:monospace;font-size:11px;background:var(--bg);padding:2px 7px;border-radius:4px;">{{ $auto->plantilla->name }}</span>
                            @else
                                <span style="color:var(--muted);font-size:12px;">Texto dinámico</span>
                            @endif
                        </td>
                        <td>{{ $auto->cuenta?->nombre ?? '— Default —' }}</td>
                        <td style="text-align:center;">
                            <label class="toggle" style="margin:auto;" title="{{ $auto->activo ? 'Activa — clic para desactivar' : 'Inactiva — clic para activar' }}">
                                <input type="checkbox" {{ $auto->activo ? 'checked':'' }}
                                       onchange="toggleAuto({{ $auto->id }}, this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="{{ route('whatsapp.automations.edit', [$tenant, $auto]) }}"
                                   class="btn btn-secondary btn-sm">✏️</a>
                                <form method="POST"
                                      action="{{ route('whatsapp.automations.destroy', [$tenant, $auto]) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('¿Eliminar «{{ addslashes($auto->nombre) }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Logs recientes --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">📜 Historial de envíos recientes</span>
        <span style="font-size:12px;color:var(--muted);">Últimos 50 registros</span>
    </div>

    @if($logs->isEmpty())
        <div style="padding:2rem;text-align:center;color:var(--muted);font-size:13px;">
            Sin registros de envíos aún. Los envíos automáticos aparecerán aquí.
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Automatización</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;white-space:nowrap;">{{ $log->created_at->format('d/m H:i') }}</td>
                        <td style="font-size:12px;">{{ $log->automatizacion?->nombre ?? '—' }}</td>
                        <td style="font-weight:500;">{{ $log->cliente?->nombre_completo ?? '—' }}</td>
                        <td>
                            <span class="badge badge-blue" style="font-size:10px;">{{ $log->tipo }}</span>
                        </td>
                        <td>
                            <span class="badge {{ match($log->estado) {
                                'sent'    => 'badge-green',
                                'failed'  => 'badge-red',
                                'pending' => 'badge-yellow',
                                'skipped' => 'badge-gray',
                                default   => 'badge-gray'
                            } }}">
                                {{ match($log->estado) { 'sent'=>'Enviado','failed'=>'Fallido','pending'=>'Pendiente','skipped'=>'Omitido',default=>ucfirst($log->estado) } }}
                            </span>
                        </td>
                        <td style="font-size:11px;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $log->error ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Artisan info --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">🖥️ Comando Artisan para envíos automáticos</span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:.875rem;">
            Programa este comando en el Cron del servidor para que se ejecute diariamente.
        </p>
        <div style="display:flex;flex-direction:column;gap:.625rem;">
            <div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:.25rem;">Ejecución real:</div>
                <code style="display:block;background:#1a1a2e;color:#25d366;padding:.75rem 1rem;border-radius:8px;font-size:13px;">php artisan whatsapp:send-reminders</code>
            </div>
            <div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:.25rem;">Modo prueba (sin envíos reales):</div>
                <code style="display:block;background:#1a1a2e;color:#f59e0b;padding:.75rem 1rem;border-radius:8px;font-size:13px;">php artisan whatsapp:send-reminders --dry-run</code>
            </div>
            <div>
                <div style="font-size:11px;color:var(--muted);margin-bottom:.25rem;">Entrada cron recomendada (diario a las 8am):</div>
                <code style="display:block;background:#1a1a2e;color:#e2e8f0;padding:.75rem 1rem;border-radius:8px;font-size:13px;">0 8 * * * cd /ruta/del/proyecto && php artisan whatsapp:send-reminders >> /dev/null 2>&1</code>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const TENANT = '{{ $tenant }}';
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;

function toggleAuto(id, checkbox) {
    const prev = !checkbox.checked;
    fetch(`/whatsapp/${TENANT}/automations/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: data.activo ? '✓ Automatización activada' : 'Automatización desactivada',
            timer: 1800, showConfirmButton: false
        });
    })
    .catch(() => {
        checkbox.checked = prev; // revertir
        Swal.fire({ icon: 'error', title: 'Error al cambiar estado' });
    });
}
</script>
@endsection
