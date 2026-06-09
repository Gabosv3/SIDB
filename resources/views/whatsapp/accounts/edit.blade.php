@extends('whatsapp._layout')

@section('page-title', 'Editar número')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Editar: {{ $account->nombre }}</span>
            <a href="{{ route('whatsapp.accounts.index', $tenant) }}" class="btn btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.accounts.update', [$tenant, $account]) }}">
                @csrf @method('PUT')

                {{-- Info básica --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);">
                        Información básica
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">Nombre interno *</label>
                            <input type="text" name="nombre" class="form-control {{ $errors->has('nombre') ? 'error' : '' }}"
                                   value="{{ old('nombre', $account->nombre) }}" required>
                            @error('nombre') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-control" required>
                                <option value="empresa"  {{ old('tipo',$account->tipo)==='empresa'  ? 'selected':'' }}>Empresa (global)</option>
                                <option value="vendedor" {{ old('tipo',$account->tipo)==='vendedor' ? 'selected':'' }}>Vendedor</option>
                                <option value="cobrador" {{ old('tipo',$account->tipo)==='cobrador' ? 'selected':'' }}>Cobrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">Usuario asignado</label>
                            <select name="user_id" class="form-control">
                                <option value="">— Sin asignar —</option>
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}"
                                        {{ old('user_id', $account->user_id)==$u->id ? 'selected':'' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-control">
                                <option value="activo"     {{ old('estado',$account->estado)==='activo'     ? 'selected':'' }}>Activo</option>
                                <option value="inactivo"   {{ old('estado',$account->estado)==='inactivo'   ? 'selected':'' }}>Inactivo</option>
                                <option value="suspendido" {{ old('estado',$account->estado)==='suspendido' ? 'selected':'' }}>Suspendido</option>
                            </select>
                        </div>
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
                                   value="{{ old('phone_number_id', $account->phone_number_id) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número mostrado</label>
                            <input type="text" name="display_phone_number" class="form-control"
                                   value="{{ old('display_phone_number', $account->display_phone_number) }}">
                        </div>
                    </div>
                    <div class="form-row col-2">
                        <div class="form-group">
                            <label class="form-label">WABA ID</label>
                            <input type="text" name="waba_id" class="form-control"
                                   value="{{ old('waba_id', $account->waba_id) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta Business ID</label>
                            <input type="text" name="meta_business_id" class="form-control"
                                   value="{{ old('meta_business_id', $account->meta_business_id) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre verificado</label>
                        <input type="text" name="verified_name" class="form-control"
                               value="{{ old('verified_name', $account->verified_name) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Token (API)</label>
                        @if($account->estaConfigurado())
                            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                                <span style="font-family:monospace;font-size:13px;background:var(--bg);padding:.4rem .75rem;border-radius:6px;border:1px solid var(--border);">
                                    {{ $account->token_masked }}
                                </span>
                                <span class="badge badge-green">✓ Configurado</span>
                            </div>
                        @endif
                        <input type="password" name="access_token" class="form-control" autocomplete="off"
                               placeholder="{{ $account->estaConfigurado() ? 'Dejar vacío para no cambiar el token' : 'EAAxxxxxxxxxx...' }}">
                        <span class="form-hint">🔒 Se guarda cifrado. {{ $account->estaConfigurado() ? 'Deja vacío para conservar el token actual.' : 'Genera un token permanente de System User en Meta.' }}</span>
                    </div>
                </div>

                {{-- Opciones --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid var(--border);">
                        Opciones
                    </div>
                    <label class="check-group">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', $account->is_default) ? 'checked':'' }}>
                        <span>Número <strong>principal</strong> del sistema</span>
                    </label>
                </div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <a href="{{ route('whatsapp.accounts.index', $tenant) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
