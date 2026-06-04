<div style="padding: 2rem 0;">
    <!-- Header -->
    <div style="background: linear-gradient(to right, #f97316, #ea580c); border-radius: 0.5rem; padding: 2rem; color: white; margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">Crear Asignación Diaria</h1>
        <p style="color: #fed7aa; font-size: 1rem;">Selecciona vendedor, sucursal y productos para la jornada</p>
    </div>

    <form wire:submit.prevent="save" style="max-width: 1280px; margin: 0 auto;">
        <!-- Sección: Jornada -->
        <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">📅 Jornada</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <!-- Vendedor -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Vendedor *</label>
                    <select wire:model.live="vendedor_id" style="width: 100%; border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; color: #111827;">
                        <option value="">Seleccionar vendedor...</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->nombre }} {{ $v->apellido }}</option>
                        @endforeach
                    </select>
                    @error('vendedor_id') <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>

                <!-- Sucursal -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Sucursal *</label>
                    <select wire:model="sucursal_id" style="width: 100%; border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; color: #111827; background-color: #f9fafb;" disabled>
                        <option value="">Se llena automáticamente</option>
                        @foreach ($sucursales as $s)
                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Fecha -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Fecha de jornada *</label>
                    <input type="date" wire:model="fecha" style="width: 100%; border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; color: #111827;">
                    @error('fecha') <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Contenedor Principal: Búsqueda + Resumen -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <!-- Panel Izquierdo: Búsqueda y Productos -->
            <div>
                <!-- Búsqueda -->
                <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">🔍 Buscar productos</h3>

                    <div style="margin-bottom: 1.5rem;">
                        <input type="text" wire:model.live="search" placeholder="Buscar por nombre o código..."
                            style="width: 100%; border: 2px solid #e5e7eb; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; color: #111827;">
                    </div>

                    <!-- Filtros de categoría -->
                    <div>
                        <p style="font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.75rem;">Categorías:</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <button type="button" wire:click="$set('categoria_id', null)"
                                style="padding: 0.5rem 1rem; border-radius: 0.375rem; background-color: {{ is_null($this->categoria_id) ? '#f97316' : '#e5e7eb' }}; color: {{ is_null($this->categoria_id) ? 'white' : '#111827' }}; border: none; cursor: pointer; font-size: 0.875rem; font-weight: 500;">
                                Todos
                            </button>
                            @foreach ($categorias as $cat)
                                <button type="button" wire:click="$set('categoria_id', {{ $cat->id }})"
                                    style="padding: 0.5rem 1rem; border-radius: 0.375rem; background-color: {{ $categoria_id == $cat->id ? '#f97316' : '#e5e7eb' }}; color: {{ $categoria_id == $cat->id ? 'white' : '#111827' }}; border: none; cursor: pointer; font-size: 0.875rem; font-weight: 500;">
                                    {{ $cat->nombre }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Grid de Productos -->
                <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">
                        📦 Productos disponibles ({{ count($productos) }})
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                        @forelse ($productos as $producto)
                            <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; background: white; transition: all 0.2s;">
                                <div style="background: #e5e7eb; height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    @if ($producto['imagen'])
                                        <img src="{{ asset('storage/' . $producto['imagen']) }}" alt="{{ $producto['nombre'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6;">
                                            <span style="font-size: 2rem;">📷</span>
                                        </div>
                                    @endif
                                </div>

                                <div style="padding: 0.75rem;">
                                    <p style="font-weight: 600; color: #111827; font-size: 0.875rem; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $producto['nombre'] }}</p>
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.25rem;">{{ $producto['codigo'] }}</p>
                                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.5rem;">Stock: {{ $producto['stock'] }}</p>
                                    <p style="font-weight: bold; color: #f97316; font-size: 1rem; margin-bottom: 0.75rem;">${{ number_format($producto['precio_venta'], 2) }}</p>

                                    <button type="button"
                                        wire:click="agregarProducto({{ $producto['id'] }})"
                                        style="width: 100%; background-color: #f97316; color: white; font-weight: 600; padding: 0.5rem; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.75rem;">
                                        + Agregar
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #6b7280;">
                                <p style="font-size: 1rem;">No hay productos que coincidan</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Panel Derecho: Resumen -->
            <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; height: fit-content; position: sticky; top: 20px;">
                <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">📋 Resumen ({{ count($detalles) }})</h3>

                <div style="max-height: 400px; overflow-y: auto; margin-bottom: 1rem;">
                    @forelse ($detalles as $key => $detalle)
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <div style="width: 40px; height: 40px; background: #e5e7eb; border-radius: 0.375rem; flex-shrink: 0; overflow: hidden;">
                                    @if ($detalle['producto']['imagen'])
                                        <img src="{{ asset('storage/' . $detalle['producto']['imagen']) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">📷</div>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-weight: 600; color: #111827; font-size: 0.875rem; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $detalle['producto']['nombre'] }}</p>
                                    <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">{{ $detalle['producto']['codigo'] }}</p>
                                </div>
                                <button type="button" wire:click="removerProducto('{{ $key }}')" style="color: #dc2626; cursor: pointer; background: none; border: none; padding: 0; font-size: 1.25rem; line-height: 1;">×</button>
                            </div>

                            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                                <button type="button" wire:click="decrementarProducto('{{ $key }}')" style="flex: 1; background: #e5e7eb; border: none; padding: 0.25rem; border-radius: 0.25rem; cursor: pointer; font-weight: bold;">−</button>
                                <span style="flex: 1; text-align: center; font-weight: 600;">{{ $detalle['cantidad_asignada'] }}</span>
                                <button type="button" wire:click="incrementarProducto('{{ $key }}')" style="flex: 1; background: #e5e7eb; border: none; padding: 0.25rem; border-radius: 0.25rem; cursor: pointer; font-weight: bold;">+</button>
                            </div>

                            <div style="text-align: right; font-size: 0.875rem; font-weight: 600; color: #f97316;">
                                ${{ number_format($detalle['cantidad_asignada'] * $detalle['precio_venta'], 2) }}
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 2rem 0; color: #9ca3af;">
                            <p>Sin productos</p>
                        </div>
                    @endforelse
                </div>

                <!-- Totales -->
                <div style="border-top: 2px solid #e5e7eb; padding-top: 1rem; display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: #6b7280;">Prod. diferentes:</span>
                        <span style="font-weight: 600;">{{ count($detalles) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: #6b7280;">Total unidades:</span>
                        <span style="font-weight: 600;">{{ $totalUnidades }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: bold; color: #f97316;">
                        <span>Total:</span>
                        <span>${{ number_format($totalAsignacion, 2) }}</span>
                    </div>
                </div>

                @error('detalles') <p style="color: #dc2626; font-size: 0.75rem;">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Sección: Observaciones -->
        <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">💬 Observaciones</h2>
            <textarea wire:model="observaciones" placeholder="Indicaciones, rutas sugeridas, condiciones especiales..."
                rows="3" style="width: 100%; border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; font-family: inherit; color: #111827;"></textarea>
        </div>

        <!-- Botones de acción -->
        <div style="display: flex; gap: 1rem;">
            <button type="button" onclick="history.back()"
                style="flex: 1; padding: 0.75rem 1.5rem; background-color: #d1d5db; color: #111827; font-weight: 600; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem;">
                Cancelar
            </button>
            <button type="submit" style="flex: 1; padding: 0.75rem 1.5rem; background-color: #f97316; color: white; font-weight: 600; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem;">
                ✓ Guardar asignación
            </button>
        </div>
    </form>
</div>
