<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border-radius: 8px; padding: 24px; color: white;">
            <h1 style="font-size: 28px; font-weight: bold; margin-bottom: 8px;">Crear Asignación Diaria</h1>
            <p style="color: #dbeafe; font-size: 14px;">Selecciona vendedor, sucursal y productos para la jornada</p>
        </div>

        <form wire:submit.prevent="save" method="POST" action="#">
            @csrf

            <!-- Sección: Jornada -->
            <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">📅 Jornada</h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <!-- Vendedor -->
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Vendedor *</label>
                        <select wire:model="vendedor_id" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; color: #111827;">
                            <option value="">Seleccionar vendedor...</option>
                            @foreach ($vendedores as $v)
                                <option value="{{ $v->id }}">{{ $v->nombre }} {{ $v->apellido }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sucursal -->
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Sucursal *</label>
                        <select wire:model="sucursal_id" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; color: #111827; background-color: #f9fafb;" @if(!$vendedor_id) disabled @endif>
                            <option value="">Se completa automáticamente</option>
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id }}" @if($sucursal_id == $s->id) selected @endif>{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha -->
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">Fecha de jornada *</label>
                        <input type="date" wire:model="fecha" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; color: #111827;">
                    </div>
                </div>
            </div>

            <!-- Contenedor: Búsqueda + Productos -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                <!-- Panel Izquierdo -->
                <div>
                    <!-- Búsqueda -->
                    <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">🔍 Buscar productos</h3>

                        <input type="text" wire:model.live="search" placeholder="Buscar por nombre o código..."
                            style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; font-size: 14px; color: #111827; margin-bottom: 16px;">

                        <!-- Categorías -->
                        <div>
                            <label style="font-size: 14px; font-weight: 500; color: #374151; display: block; margin-bottom: 8px;">Categorías</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <button type="button" wire:click="$set('categoria_id', null)"
                                    style="padding: 8px 12px; border-radius: 6px; background-color: {{ is_null($this->categoria_id ?? null) ? '#2563eb' : '#e5e7eb' }}; color: {{ is_null($this->categoria_id ?? null) ? 'white' : '#111827' }}; border: none; cursor: pointer; font-size: 13px; font-weight: 500;">
                                    Todos
                                </button>
                                @foreach ($categorias as $cat)
                                    <button type="button" wire:click="$set('categoria_id', {{ $cat->id }})"
                                        style="padding: 8px 12px; border-radius: 6px; background-color: {{ ($categoria_id ?? null) == $cat->id ? '#2563eb' : '#e5e7eb' }}; color: {{ ($categoria_id ?? null) == $cat->id ? 'white' : '#111827' }}; border: none; cursor: pointer; font-size: 13px; font-weight: 500;">
                                        {{ $cat->nombre }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Grid de Productos -->
                    <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 24px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">
                            📦 Productos disponibles ({{ count($productos) }})
                        </h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px;">
                            @forelse ($productos as $producto)
                                <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: white; transition: all 0.2s;">
                                    <!-- Imagen -->
                                    <div style="background: #f3f4f6; height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        @if ($producto->imagen)
                                            <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <span style="font-size: 32px;">📦</span>
                                        @endif
                                    </div>

                                    <!-- Info -->
                                    <div style="padding: 12px;">
                                        <p style="font-weight: 600; color: #111827; font-size: 13px; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $producto->nombre }}</p>
                                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">{{ $producto->codigo }}</p>
                                        <p style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">Stock: {{ $producto->stock }}</p>
                                        <p style="font-weight: bold; color: #2563eb; font-size: 14px; margin-bottom: 8px;">${{ number_format($producto->precio_venta, 2) }}</p>

                                        <button type="button"
                                            wire:click="agregarProducto({{ $producto->id }})"
                                            style="width: 100%; background-color: #2563eb; color: white; font-weight: 600; padding: 6px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">
                                            + Agregar
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div style="grid-column: 1 / -1; text-align: center; padding: 32px; color: #6b7280;">
                                    <p>No hay productos que coincidan</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho: Resumen -->
                <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 24px; height: fit-content;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">Resumen ({{ count($detalles) }})</h3>

                    <div style="max-height: 360px; overflow-y: auto; margin-bottom: 16px;">
                        @forelse ($detalles as $key => $detalle)
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                    <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 4px; flex-shrink: 0; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                        @if ($detalle['producto']->imagen)
                                            <img src="{{ asset('storage/' . $detalle['producto']->imagen) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            📦
                                        @endif
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p style="font-weight: 600; color: #111827; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $detalle['producto']->nombre }}</p>
                                        <p style="font-size: 12px; color: #6b7280;">{{ $detalle['producto']->codigo }}</p>
                                    </div>
                                    <button type="button" wire:click="removerProducto('{{ $key }}')" style="color: #dc2626; cursor: pointer; background: none; border: none; padding: 0; font-size: 18px; line-height: 1;">×</button>
                                </div>

                                <div style="font-size: 13px; margin-bottom: 8px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <span style="color: #6b7280;">Cantidad:</span>
                                        <span style="font-weight: 600; color: #111827;">{{ $detalle['cantidad_asignada'] }}</span>
                                    </div>
                                    <div style="display: flex; gap: 4px;">
                                        <button type="button" wire:click="decrementarProducto('{{ $key }}')" style="flex: 1; background-color: #e5e7eb; padding: 4px; border-radius: 4px; border: none; cursor: pointer;">−</button>
                                        <button type="button" wire:click="incrementarProducto('{{ $key }}')" style="flex: 1; background-color: #e5e7eb; padding: 4px; border-radius: 4px; border: none; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <div style="text-align: right; font-size: 13px;">
                                    <span style="font-weight: 600; color: #2563eb;">${{ number_format($detalle['cantidad_asignada'] * $detalle['precio_venta'], 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 32px 0; color: #9ca3af; font-size: 14px;">
                                <p>Sin productos seleccionados</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Totales -->
                    <div style="border-top: 1px solid #e5e7eb; padding-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #6b7280;">Productos:</span>
                            <span style="font-weight: 600; color: #111827;">{{ count($detalles) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #6b7280;">Unidades:</span>
                            <span style="font-weight: 600; color: #111827;">{{ collect($detalles)->sum('cantidad_asignada') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 16px; font-weight: bold; color: #2563eb;">
                            <span>Total:</span>
                            <span>${{ number_format(collect($detalles)->sum(fn($d) => $d['cantidad_asignada'] * $d['precio_venta']), 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Observaciones -->
            <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px;">
                <h2 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">💬 Observaciones</h2>
                <textarea wire:model="observaciones" placeholder="Indicaciones, rutas, condiciones especiales..."
                    rows="3" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: #111827;"></textarea>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('filament.admin.resources.asignaciones-diarias.index') }}"
                    style="flex: 1; padding: 12px; background-color: #e5e7eb; color: #111827; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; text-align: center; text-decoration: none;">
                    Cancelar
                </a>
                <button type="submit" style="flex: 1; padding: 12px; background-color: #2563eb; color: white; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; font-size: 14px;">
                    ✓ Guardar asignación
                </button>
            </div>

            @if ($errors->any())
                <div style="background-color: #fee2e2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; color: #dc2626; margin-top: 16px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>
    </div>
</x-filament-panels::page>
