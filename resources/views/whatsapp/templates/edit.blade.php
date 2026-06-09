@extends('whatsapp._layout')

@section('page-title', 'Editar plantilla')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar: <code style="font-size:13px;">{{ $template->name }}</code></span>
            <a href="{{ route('whatsapp.templates.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.templates.update', [$tenant, $template]) }}">
                @csrf @method('PUT')

                <div class="form-group">
                    <label class="form-label">Cuenta de WhatsApp</label>
                    <select name="whatsapp_account_id" class="form-control">
                        <option value="">— Global —</option>
                        @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}"
                                {{ old('whatsapp_account_id', $template->whatsapp_account_id)==$cuenta->id ? 'selected':'' }}>
                                {{ $cuenta->nombre }}
                                @if($cuenta->display_phone_number)({{ $cuenta->display_phone_number }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row col-2">
                    <div class="form-group">
                        <label class="form-label">Nombre (ID Meta) *</label>
                        <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'error' : '' }}"
                               value="{{ old('name', $template->name) }}" required>
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                        <span class="form-hint">Solo minúsculas, números y guión bajo.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Idioma *</label>
                        <select name="language" class="form-control" required>
                            <option value="es"    {{ old('language',$template->language)==='es'    ? 'selected':'' }}>es — Español</option>
                            <option value="es_MX" {{ old('language',$template->language)==='es_MX' ? 'selected':'' }}>es_MX — Español (México)</option>
                            <option value="es_AR" {{ old('language',$template->language)==='es_AR' ? 'selected':'' }}>es_AR — Español (Argentina)</option>
                            <option value="en_US" {{ old('language',$template->language)==='en_US' ? 'selected':'' }}>en_US — Inglés (US)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row col-2">
                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select name="category" class="form-control" required>
                            <option value="UTILITY"        {{ old('category',$template->category)==='UTILITY'        ? 'selected':'' }}>🔧 Utilidad</option>
                            <option value="MARKETING"      {{ old('category',$template->category)==='MARKETING'      ? 'selected':'' }}>📢 Marketing</option>
                            <option value="AUTHENTICATION" {{ old('category',$template->category)==='AUTHENTICATION' ? 'selected':'' }}>🔐 Autenticación</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado de aprobación Meta</label>
                        <select name="status" class="form-control">
                            <option value="pending"  {{ old('status',$template->status)==='pending'  ? 'selected':'' }}>⏳ Pendiente</option>
                            <option value="approved" {{ old('status',$template->status)==='approved' ? 'selected':'' }}>✅ Aprobada</option>
                            <option value="rejected" {{ old('status',$template->status)==='rejected' ? 'selected':'' }}>❌ Rechazada</option>
                        </select>
                        <span class="form-hint">El estado lo actualiza Meta. Cámbialo manualmente si ya fue aprobada.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cuerpo del mensaje *</label>
                    <textarea name="body" class="form-control {{ $errors->has('body') ? 'error' : '' }}"
                              rows="6" required>{{ old('body', $template->body) }}</textarea>
                    @error('body') <span class="form-error">{{ $message }}</span> @enderror
                    <span class="form-hint">
                        Variables:
                        <code>{{'{{'}}nombre{{'}}'}}</code>
                        <code>{{'{{'}}monto{{'}}'}}</code>
                        <code>{{'{{'}}fecha_vencimiento{{'}}'}}</code>
                        <code>{{'{{'}}numero_cuota{{'}}'}}</code>
                        <code>{{'{{'}}saldo{{'}}'}}</code>
                    </span>
                </div>

                {{-- Vista previa en tiempo real --}}
                <div class="form-group" style="margin-top:.5rem;">
                    <label class="form-label">Vista previa</label>
                    <div id="preview"
                         style="background:#dcf8c6;border-radius:12px;padding:.875rem 1rem;font-size:13px;line-height:1.6;color:#1a1a1a;max-width:360px;box-shadow:0 1px 3px rgba(0,0,0,.1);white-space:pre-wrap;">
                    </div>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.templates.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
const bodyTA  = document.querySelector('textarea[name="body"]');
const preview = document.getElementById('preview');

const vars = {
    nombre: 'Juan Pérez', apellido: 'Pérez', monto: '250.00',
    fecha_vencimiento: '15/06/2026', numero_cuota: '3',
    total_cuotas: '12', saldo: '750.00', empresa: 'Distribuidora BM'
};

function updatePreview() {
    let text = bodyTA.value;
    Object.entries(vars).forEach(([k,v]) => {
        text = text.replace(new RegExp('{{'+k+'}}', 'g'), v);
    });
    preview.textContent = text || '— sin contenido —';
}

bodyTA.addEventListener('input', updatePreview);
updatePreview();
</script>
@endsection
