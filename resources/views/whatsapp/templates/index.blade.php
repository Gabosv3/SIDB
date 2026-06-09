@extends('whatsapp._layout')

@section('page-title', 'Plantillas de mensajes')

@section('content')

<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Plantillas de mensajes ({{ $plantillas->count() }})</span>
        <a href="{{ route('whatsapp.templates.create', $tenant) }}" class="btn btn-primary btn-sm">
            + Nueva plantilla
        </a>
    </div>

    @if($plantillas->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--muted);">
            <p style="font-size:40px;margin-bottom:.75rem;">📋</p>
            <p style="font-size:15px;font-weight:600;margin-bottom:.5rem;">Sin plantillas creadas</p>
            <p style="font-size:13px;margin-bottom:1.25rem;">Las plantillas te permiten enviar mensajes pre-aprobados por Meta, como recordatorios de pago o confirmaciones.</p>
            <a href="{{ route('whatsapp.templates.create', $tenant) }}" class="btn btn-primary">+ Crear primera plantilla</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nombre (ID Meta)</th>
                        <th>Categoría</th>
                        <th>Idioma</th>
                        <th>Cuenta asociada</th>
                        <th>Estado Meta</th>
                        <th>Vista previa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plantillas as $plantilla)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-family:monospace;font-size:12px;">{{ $plantilla->name }}</div>
                        </td>
                        <td>
                            <span class="badge {{ match($plantilla->category) {
                                'UTILITY'        => 'badge-blue',
                                'MARKETING'      => 'badge-purple',
                                'AUTHENTICATION' => 'badge-yellow',
                                default          => 'badge-gray'
                            } }}">{{ $plantilla->category }}</span>
                        </td>
                        <td>{{ strtoupper($plantilla->language) }}</td>
                        <td>{{ $plantilla->cuenta?->nombre ?? '— Global —' }}</td>
                        <td>
                            <span class="badge {{ match($plantilla->status) {
                                'approved' => 'badge-green',
                                'rejected' => 'badge-red',
                                default    => 'badge-yellow'
                            } }}">{{ ucfirst($plantilla->status) }}</span>
                        </td>
                        <td>
                            <div style="max-width:240px;font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                 title="{{ $plantilla->body }}">
                                {{ $plantilla->body }}
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="{{ route('whatsapp.templates.edit', [$tenant, $plantilla]) }}"
                                   class="btn btn-secondary btn-sm">✏️ Editar</a>
                                <form method="POST"
                                      action="{{ route('whatsapp.templates.destroy', [$tenant, $plantilla]) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('¿Eliminar la plantilla «{{ addslashes($plantilla->name) }}»?')">
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

{{-- Info sobre plantillas --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">ℹ️ Variables disponibles en plantillas</span>
    </div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:1rem;">
            Usa estas variables en el cuerpo de tus plantillas. El sistema las reemplaza automáticamente con los datos reales del cliente.
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
            @foreach(['{{nombre}}','{{apellido}}','{{telefono}}','{{monto}}','{{fecha_vencimiento}}','{{numero_cuota}}','{{total_cuotas}}','{{saldo}}','{{empresa}}'] as $var)
                <code style="background:#f8f8f8;padding:.35rem .75rem;border-radius:6px;font-size:12px;border:1px solid var(--border);color:var(--green-dark);">{{ $var }}</code>
            @endforeach
        </div>
        <p style="font-size:12px;color:var(--muted);margin-top:.875rem;">
            <strong>Nota:</strong> Las plantillas deben ser aprobadas por Meta antes de poder usarse para envíos masivos o automatizados.
            Los mensajes directos (conversación abierta en las últimas 24h) no requieren plantilla.
        </p>
    </div>
</div>

@endsection
