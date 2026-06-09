@extends('whatsapp._layout')

@section('page-title', 'Editar automatización')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar: {{ $automation->nombre }}</span>
            <a href="{{ route('whatsapp.automations.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.automations.update', [$tenant, $automation]) }}">
                @csrf @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control {{ $errors->has('nombre') ? 'error' : '' }}"
                           value="{{ old('nombre', $automation->nombre) }}" required>
                    @error('nombre') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo de automatización *</label>
                    <select name="tipo" class="form-control" id="tipoSelect" required onchange="onTipoChange()">
                        @foreach($tipos as $val => $label)
                            <option value="{{ $val }}" {{ old('tipo',$automation->tipo)===$val ? 'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row col-3">
                    <div class="form-group" id="diasAntesGroup">
                        <label class="form-label">Días <strong>antes</strong></label>
                        <input type="number" name="dias_antes" class="form-control"
                               value="{{ old('dias_antes', $automation->dias_antes) }}" min="0" max="30">
                    </div>
                    <div class="form-group" id="diasDespuesGroup">
                        <label class="form-label">Días <strong>después</strong></label>
                        <input type="number" name="dias_despues" class="form-control"
                               value="{{ old('dias_despues', $automation->dias_despues) }}" min="0" max="90">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora de envío *</label>
                        <input type="time" name="hora_envio" class="form-control"
                               value="{{ old('hora_envio', \Carbon\Carbon::parse($automation->hora_envio)->format('H:i')) }}" required>
                    </div>
                </div>

                <div class="form-row col-2">
                    <div class="form-group">
                        <label class="form-label">Cuenta de WhatsApp</label>
                        <select name="whatsapp_account_id" class="form-control">
                            <option value="">— Resolver automáticamente —</option>
                            @foreach($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}"
                                    {{ old('whatsapp_account_id', $automation->whatsapp_account_id)==$cuenta->id ? 'selected':'' }}>
                                    {{ $cuenta->nombre }}
                                    @if($cuenta->display_phone_number)({{ $cuenta->display_phone_number }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plantilla</label>
                        <select name="template_id" class="form-control">
                            <option value="">— Texto dinámico —</option>
                            @foreach($plantillas as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('template_id', $automation->template_id)==$p->id ? 'selected':'' }}>
                                    {{ $p->name }} ({{ $p->language }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="check-group">
                        <input type="checkbox" name="activo" value="1"
                               {{ old('activo', $automation->activo) ? 'checked':'' }}>
                        <span>Automatización activa</span>
                    </label>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.automations.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const tiposConDespues = ['recordatorio_mora', 'confirmacion_pago', 'seguimiento_promesa_pago'];

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

document.addEventListener('DOMContentLoaded', onTipoChange);
</script>
@endsection
