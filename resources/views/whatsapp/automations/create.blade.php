@extends('whatsapp._layout')

@section('page-title', 'Nueva automatización')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">⚙️ Nueva automatización de WhatsApp</span>
            <a href="{{ route('whatsapp.automations.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.automations.store', $tenant) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Nombre de la automatización *</label>
                    <input type="text" name="nombre" class="form-control {{ $errors->has('nombre') ? 'error' : '' }}"
                           value="{{ old('nombre') }}" placeholder="Ej: Recordatorio 1 día antes del vencimiento" required>
                    @error('nombre') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo de automatización *</label>
                    <select name="tipo" class="form-control {{ $errors->has('tipo') ? 'error' : '' }}"
                            id="tipoSelect" required onchange="onTipoChange()">
                        <option value="">Seleccionar tipo...</option>
                        @foreach($tipos as $val => $label)
                            <option value="{{ $val }}" {{ old('tipo')===$val ? 'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('tipo') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-row col-3">
                    {{-- días antes --}}
                    <div class="form-group" id="diasAntesGroup">
                        <label class="form-label">Días <strong>antes</strong> del vencimiento</label>
                        <input type="number" name="dias_antes" class="form-control"
                               value="{{ old('dias_antes', 1) }}" min="0" max="30">
                        <span class="form-hint">0 = el mismo día</span>
                    </div>
                    {{-- días después --}}
                    <div class="form-group" id="diasDespuesGroup" style="display:none;">
                        <label class="form-label">Días <strong>después</strong> del vencimiento</label>
                        <input type="number" name="dias_despues" class="form-control"
                               value="{{ old('dias_despues', 1) }}" min="0" max="90">
                    </div>
                    {{-- hora --}}
                    <div class="form-group">
                        <label class="form-label">Hora de envío *</label>
                        <input type="time" name="hora_envio" class="form-control"
                               value="{{ old('hora_envio', '08:00') }}" required>
                        <span class="form-hint">Hora local del servidor</span>
                    </div>
                </div>

                <div class="form-row col-2">
                    <div class="form-group">
                        <label class="form-label">Cuenta de WhatsApp</label>
                        <select name="whatsapp_account_id" class="form-control">
                            <option value="">— Usar cuenta del cobrador/vendedor —</option>
                            @foreach($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}" {{ old('whatsapp_account_id')==$cuenta->id ? 'selected':'' }}>
                                    {{ $cuenta->nombre }}
                                    @if($cuenta->display_phone_number)({{ $cuenta->display_phone_number }})@endif
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint">Si está vacío, se resuelve automáticamente: cobrador → vendedor → empresa.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plantilla (opcional)</label>
                        <select name="template_id" class="form-control">
                            <option value="">— Mensaje de texto dinámico —</option>
                            @foreach($plantillas as $p)
                                <option value="{{ $p->id }}" {{ old('template_id')==$p->id ? 'selected':'' }}>
                                    {{ $p->name }} ({{ $p->language }})
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint">Si no hay plantilla aprobada, se usa texto generado automáticamente.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="check-group">
                        <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked':'' }}>
                        <span>Activar inmediatamente</span>
                    </label>
                </div>

                {{-- Info box --}}
                <div style="padding:.875rem 1rem;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;margin-bottom:1.25rem;">
                    <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:.4rem;">💡 ¿Cómo funciona?</div>
                    <div style="font-size:12px;color:#166534;line-height:1.6;">
                        El comando <code>php artisan whatsapp:send-reminders</code> debe ejecutarse diariamente (cron).
                        Buscará todas las cuotas que coincidan con la configuración y enviará el mensaje si no se ha enviado ya hoy para esa cuota (evita duplicados automáticamente).
                    </div>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.automations.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar automatización</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const tiposConAntes    = ['recordatorio_antes_vencimiento', 'recordatorio_dia_vencimiento'];
const tiposConDespues  = ['recordatorio_mora', 'confirmacion_pago', 'seguimiento_promesa_pago'];

function onTipoChange() {
    const tipo = document.getElementById('tipoSelect').value;
    const ga = document.getElementById('diasAntesGroup');
    const gd = document.getElementById('diasDespuesGroup');

    if (tiposConDespues.includes(tipo)) {
        ga.style.display = 'none';
        gd.style.display = '';
    } else if (tipo === 'recordatorio_dia_vencimiento') {
        ga.style.display = 'none';
        gd.style.display = 'none';
    } else {
        ga.style.display = '';
        gd.style.display = 'none';
    }
}

// inicializar con valor previo si hay old()
document.addEventListener('DOMContentLoaded', onTipoChange);
</script>
@endsection
