@extends('whatsapp._layout')

@section('page-title', 'Números de WhatsApp')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title">📱 Números conectados ({{ $cuentas->count() }})</span>
        <a href="{{ route('whatsapp.accounts.create', $tenant) }}" class="btn btn-primary btn-sm">
            + Agregar número
        </a>
    </div>

    @if($cuentas->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--muted);">
            <p style="font-size:40px;margin-bottom:.75rem;">📱</p>
            <p style="font-size:15px;font-weight:600;margin-bottom:.5rem;">Sin números configurados</p>
            <p style="font-size:13px;margin-bottom:1.25rem;">Agrega tu número de WhatsApp Business para empezar a enviar mensajes.</p>
            <a href="{{ route('whatsapp.accounts.create', $tenant) }}" class="btn btn-primary">+ Agregar primer número</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Número / Nombre verificado</th>
                        <th>Nombre interno</th>
                        <th>Tipo</th>
                        <th>Usuario asignado</th>
                        <th>Estado</th>
                        <th>API configurada</th>
                        <th style="text-align:center;">Principal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cuentas as $cuenta)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $cuenta->display_phone_number ?: '—' }}</div>
                            @if($cuenta->verified_name)
                                <div style="font-size:11px;color:var(--muted);">{{ $cuenta->verified_name }}</div>
                            @endif
                        </td>
                        <td>{{ $cuenta->nombre }}</td>
                        <td>
                            <span class="badge {{ match($cuenta->tipo) {
                                'empresa'  => 'badge-blue',
                                'vendedor' => 'badge-green',
                                'cobrador' => 'badge-yellow',
                                default    => 'badge-gray'
                            } }}">{{ ucfirst($cuenta->tipo) }}</span>
                        </td>
                        <td>{{ $cuenta->user?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ match($cuenta->estado) {
                                'activo'    => 'badge-green',
                                'suspendido'=> 'badge-red',
                                default     => 'badge-gray'
                            } }}">{{ ucfirst($cuenta->estado) }}</span>
                        </td>
                        <td>
                            @if($cuenta->estaConfigurado())
                                <span class="badge badge-green">✓ Configurada</span>
                            @else
                                <span class="badge badge-yellow">⚠ Incompleta</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($cuenta->is_default)
                                <span style="color:var(--green);font-size:20px;" title="Cuenta principal">⭐</span>
                            @else
                                <form method="POST"
                                      action="{{ route('whatsapp.accounts.setDefault', [$tenant, $cuenta]) }}"
                                      style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm btn-icon" title="Establecer como principal">☆</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;align-items:center;">
                                <a href="{{ route('whatsapp.accounts.edit', [$tenant, $cuenta]) }}"
                                   class="btn btn-secondary btn-sm" title="Editar">✏️ Editar</a>
                                @if($cuenta->estaConfigurado())
                                    <button class="btn btn-warning btn-sm"
                                            onclick="testSend({{ $cuenta->id }}, '{{ e($cuenta->nombre) }}')"
                                            title="Enviar mensaje de prueba">🧪 Test</button>
                                @endif
                                <form method="POST"
                                      action="{{ route('whatsapp.accounts.destroy', [$tenant, $cuenta]) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('¿Eliminar «{{ addslashes($cuenta->nombre) }}»? Esta acción no se puede deshacer.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Eliminar">🗑</button>
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

{{-- Guía de configuración --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">ℹ️ ¿Cómo conectar un número de WhatsApp Business?</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            @foreach([
                ['1️⃣','Meta Business Suite','Ve a business.facebook.com → WhatsApp Manager y registra tu número de teléfono.'],
                ['2️⃣','Phone Number ID','En Meta Developer Portal → WhatsApp → Getting Started copia el Phone Number ID y WABA ID.'],
                ['3️⃣','Access Token','Crea un System User en tu Business Manager y genera un token permanente con permisos de WhatsApp.'],
                ['4️⃣','Agregar aquí','Registra el número en este panel. El token se guarda cifrado. Puedes probar el envío con el botón 🧪 Test.'],
            ] as [$num,$title,$desc])
            <div style="padding:1rem;background:var(--bg);border-radius:8px;">
                <div style="font-size:22px;margin-bottom:.4rem;">{{ $num }}</div>
                <div style="font-weight:700;margin-bottom:.3rem;font-size:13px;">{{ $title }}</div>
                <div style="font-size:12px;color:var(--muted);line-height:1.55;">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const TENANT = '{{ $tenant }}';
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;

function testSend(accountId, nombre) {
    Swal.fire({
        title: `🧪 Prueba de envío`,
        html: `<p style="margin-bottom:.75rem;font-size:13px;">Cuenta: <strong>${nombre}</strong></p>
               <input id="testPhone" type="tel" class="swal2-input"
                      placeholder="+50312345678" style="font-size:14px;">
               <p style="font-size:11px;color:#888;margin-top:.5rem;">Ingresa el número destino con código de país</p>`,
        confirmButtonText: 'Enviar prueba',
        confirmButtonColor: '#25d366',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const phone = document.getElementById('testPhone').value.trim();
            if (!phone) { Swal.showValidationMessage('Ingresa un número de destino'); return false; }
            return phone;
        }
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Enviando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch(`/whatsapp/${TENANT}/accounts/${accountId}/test`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF },
            body: JSON.stringify({ numero: r.value }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success', title: '✓ Enviado',
                    text: data.simulated
                        ? 'Modo simulado (sin API real configurada). El sistema funciona correctamente.'
                        : 'Mensaje enviado correctamente al número destino.'
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error al enviar', text: data.error || 'No se pudo enviar' });
            }
        })
        .catch(() => Swal.fire({ icon:'error', title:'Error de red' }));
    });
}
</script>
@endsection
