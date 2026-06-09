@extends('whatsapp._layout')

@section('page-title', 'Nueva plantilla')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Nueva plantilla de mensaje</span>
            <a href="{{ route('whatsapp.templates.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.templates.store', $tenant) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Cuenta de WhatsApp</label>
                    <select name="whatsapp_account_id" class="form-control">
                        <option value="">— Global (sin cuenta específica) —</option>
                        @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" {{ old('whatsapp_account_id')==$cuenta->id ? 'selected':'' }}>
                                {{ $cuenta->nombre }}
                                @if($cuenta->display_phone_number) ({{ $cuenta->display_phone_number }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <span class="form-hint">Asocia la plantilla a una cuenta específica o déjala global.</span>
                </div>

                <div class="form-row col-2">
                    <div class="form-group">
                        <label class="form-label">Nombre de la plantilla (ID Meta) *</label>
                        <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'error' : '' }}"
                               value="{{ old('name') }}" placeholder="recordatorio_pago" required>
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                        <span class="form-hint">Solo minúsculas, números y guión bajo. Sin espacios. Ej: <code>recordatorio_cuota_vencimiento</code></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Idioma *</label>
                        <select name="language" class="form-control" required>
                            <option value="es" {{ old('language','es')==='es' ? 'selected':'' }}>es — Español</option>
                            <option value="es_MX" {{ old('language')==='es_MX' ? 'selected':'' }}>es_MX — Español (México)</option>
                            <option value="es_AR" {{ old('language')==='es_AR' ? 'selected':'' }}>es_AR — Español (Argentina)</option>
                            <option value="en_US" {{ old('language')==='en_US' ? 'selected':'' }}>en_US — Inglés (US)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Categoría *</label>
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                        @foreach(['UTILITY' => ['🔧','Utilidad','Recordatorios de pago, confirmaciones, notificaciones transaccionales'], 'MARKETING' => ['📢','Marketing','Promociones, ofertas, contenido de marketing'], 'AUTHENTICATION' => ['🔐','Autenticación','Códigos de verificación, OTP']] as $val => [$icon,$label,$desc])
                        <label style="flex:1;min-width:180px;cursor:pointer;">
                            <input type="radio" name="category" value="{{ $val }}" style="display:none;"
                                   {{ old('category','UTILITY')===$val ? 'checked':'' }}
                                   onchange="selectCat(this)">
                            <div class="cat-card {{ old('category','UTILITY')===$val ? 'cat-active':'' }}"
                                 style="padding:.875rem;border:2px solid var(--border);border-radius:8px;transition:all .2s;">
                                <div style="font-size:22px;margin-bottom:.25rem;">{{ $icon }}</div>
                                <div style="font-weight:700;font-size:13px;">{{ $label }}</div>
                                <div style="font-size:11px;color:var(--muted);margin-top:3px;">{{ $desc }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('category') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Cuerpo del mensaje *</label>
                    <textarea name="body" class="form-control {{ $errors->has('body') ? 'error' : '' }}"
                              rows="5" placeholder="Hola {{nombre}}, le recordamos que tiene una cuota de ${{monto}} con vencimiento el {{fecha_vencimiento}}. Por favor comuníquese con nosotros. Distribuidora BM."
                              required>{{ old('body') }}</textarea>
                    @error('body') <span class="form-error">{{ $message }}</span> @enderror
                    <span class="form-hint">
                        Variables disponibles:
                        <code>{{'{{'}}nombre{{'}}'}}</code>
                        <code>{{'{{'}}monto{{'}}'}}</code>
                        <code>{{'{{'}}fecha_vencimiento{{'}}'}}</code>
                        <code>{{'{{'}}numero_cuota{{'}}'}}</code>
                        <code>{{'{{'}}saldo{{'}}'}}</code>
                    </span>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.templates.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar plantilla</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.cat-active { border-color: var(--green) !important; background: #f0fdf4; }
</style>
@endsection

@section('scripts')
<script>
function selectCat(radio) {
    document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('cat-active'));
    radio.closest('label').querySelector('.cat-card').classList.add('cat-active');
}
</script>
@endsection
