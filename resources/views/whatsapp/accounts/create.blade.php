@extends('whatsapp._layout')

@section('page-title', 'Agregar número')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">📱 Nuevo número de WhatsApp Business</span>
            <a href="{{ route('whatsapp.accounts.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.accounts.store', $tenant) }}">
                @csrf

                {{-- Info básica --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);">
                        Información básica
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">Nombre interno *</label>
                            <input type="text" name="nombre" class="form-control {{ $errors->has('nombre') ? 'error' : '' }}"
                                   value="{{ old('nombre') }}" placeholder="Ej: Principal empresa" required>
                            @error('nombre') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-control {{ $errors->has('tipo') ? 'error' : '' }}" required>
                                <option value="">Seleccionar...</option>
                                <option value="empresa"  {{ old('tipo')==='empresa'  ? 'selected':'' }}>Empresa (global)</option>
                                <option value="vendedor" {{ old('tipo')==='vendedor' ? 'selected':'' }}>Vendedor</option>
                                <option value="cobrador" {{ old('tipo')==='cobrador' ? 'selected':'' }}>Cobrador</option>
                            </select>
                            @error('tipo') <span class="form-error">{{ $message }}</span> @enderror
                            <span class="form-hint">«Empresa» es el número por defecto cuando el vendedor/cobrador no tiene uno propio.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Usuario asignado</label>
                        <select name="user_id" class="form-control">
                            <option value="">— Sin asignar (global) —</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}" {{ old('user_id')==$u->id ? 'selected':'' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint">Si es tipo Vendedor o Cobrador, asigna el usuario correspondiente para que el sistema use su número automáticamente.</span>
                    </div>
                </div>

                {{-- Meta API --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);">
                        Configuración Meta WhatsApp Cloud API
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">Phone Number ID</label>
                            <input type="text" name="phone_number_id" class="form-control"
                                   value="{{ old('phone_number_id') }}" placeholder="123456789012345">
                            <span class="form-hint">Del Portal de Desarrolladores de Meta</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número mostrado</label>
                            <input type="text" name="display_phone_number" class="form-control"
                                   value="{{ old('display_phone_number') }}" placeholder="+50312345678">
                        </div>
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">WABA ID</label>
                            <input type="text" name="waba_id" class="form-control"
                                   value="{{ old('waba_id') }}" placeholder="WhatsApp Business Account ID">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Business ID</label>
                            <input type="text" name="meta_business_id" class="form-control"
                                   value="{{ old('meta_business_id') }}" placeholder="Business Manager ID">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre verificado</label>
                        <input type="text" name="verified_name" class="form-control"
                               value="{{ old('verified_name') }}" placeholder="Distribuidora BM">
                        <span class="form-hint">El nombre que aparece en los chats de tus clientes.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Token (API)</label>
                        <input type="password" name="access_token" class="form-control" autocomplete="off"
                               placeholder="EAAxxxxxxxxxx...">
                        <span class="form-hint">🔒 Se guarda cifrado en la base de datos. Genera un token permanente de System User en Meta Business Manager.</span>
                    </div>
                </div>

                {{-- Opciones --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);">
                        Opciones
                    </div>
                    <label class="check-group">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                        <span>Establecer como número <strong>principal</strong> (se usa cuando ningún vendedor/cobrador tiene número propio)</span>
                    </label>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.accounts.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Guardar número</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
