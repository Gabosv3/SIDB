<div class="fi-page-content-wrapper space-y-6" style="max-width: none;">
    <!-- Sección: Jornada -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900">
            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6 2a1 1 0 00-1 1v2H4a2 2 0 00-2 2v2h16V7a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v2H7V3a1 1 0 00-1-1zm0 5H4v9a2 2 0 002 2h12a2 2 0 002-2V7h-2v2a1 1 0 11-2 0V7H8v2a1 1 0 11-2 0V7z"/>
            </svg>
            Jornada
        </h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Vendedor *</label>
                <select wire:model.live="vendedor_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none">
                    <option value="">Seleccionar vendedor...</option>
                    @foreach ($vendedores as $v)
                        <option value="{{ $v->id }}">{{ $v->nombre }} {{ $v->apellido }}</option>
                    @endforeach
                </select>
                @error('vendedor_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Sucursal *</label>
                <select wire:model="sucursal_id" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-500" disabled>
                    <option value="">Se completa automáticamente</option>
                    @foreach ($sucursales as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Fecha de jornada *</label>
                <input type="date" wire:model="fecha" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none">
                @error('fecha') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <!-- Contenedor: Búsqueda + Productos -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Panel Izquierdo: Búsqueda y Productos -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Búsqueda -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Buscar productos</h3>

                <div class="space-y-4">
                    <!-- Input búsqueda -->
                    <input type="text" wire:model.live="search" placeholder="Buscar por nombre, código o escanear..."
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none" autofocus>

                    <!-- Filtros de categoría -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Categorías</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="$set('categoria_id', null)"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition {{ is_null($categoria_id) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                Todos
                            </button>
                            @foreach ($categorias as $cat)
                                <button type="button" wire:click="$set('categoria_id', {{ $cat->id }})"
                                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $categoria_id == $cat->id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                                    {{ $cat->nombre }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de Productos -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Productos disponibles ({{ count($productos) }})
                </h3>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @forelse ($productos as $producto)
                        <div class="rounded-lg border border-gray-200 bg-white transition hover:border-blue-500 hover:shadow-md">
                            <!-- Imagen -->
                            <div class="flex h-32 items-center justify-center bg-gray-100">
                                @if ($producto->imagen)
                                    <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover">
                                @else
                                    <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>

                            <!-- Info -->
                            <div class="space-y-2 p-3">
                                <p class="line-clamp-2 text-sm font-semibold text-gray-900">{{ $producto->nombre }}</p>
                                <p class="text-xs text-gray-500">CÓD: {{ $producto->codigo }}</p>
                                <p class="text-xs text-gray-500">Stock: {{ $producto->stock }}</p>
                                <p class="font-bold text-blue-600">${{ number_format($producto->precio_venta, 2) }}</p>

                                <button type="button"
                                    wire:click="agregarProducto({{ $producto->id }})"
                                    class="w-full rounded-lg bg-blue-600 py-2 font-semibold text-white transition hover:bg-blue-700">
                                    + Agregar
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center text-gray-500">
                            <p>No hay productos que coincidan</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Resumen -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:sticky lg:top-6 lg:h-fit">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Resumen ({{ count($detalles) }})</h3>

            <div class="space-y-3 max-h-96 overflow-y-auto">
                @forelse ($detalles as $key => $detalle)
                    <div class="space-y-2 rounded-lg border border-gray-200 p-3">
                        <!-- Producto -->
                        <div class="flex gap-2">
                            <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded bg-gray-100">
                                @if ($detalle['producto']->imagen)
                                    <img src="{{ asset('storage/' . $detalle['producto']->imagen) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center">📦</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $detalle['producto']->nombre }}</p>
                                <p class="text-xs text-gray-500">{{ $detalle['producto']->codigo }}</p>
                            </div>
                            <button type="button" wire:click="removerProducto('{{ $key }}')" class="text-red-600 hover:text-red-700">
                                ×
                            </button>
                        </div>

                        <!-- Cantidad y precio -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Cantidad:</span>
                                <span class="font-semibold text-gray-900">{{ $detalle['cantidad_asignada'] }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="decrementarProducto('{{ $key }}')" class="flex-1 rounded bg-gray-200 py-1 text-sm hover:bg-gray-300">−</button>
                                <button type="button" wire:click="incrementarProducto('{{ $key }}')" class="flex-1 rounded bg-gray-200 py-1 text-sm hover:bg-gray-300">+</button>
                            </div>
                            <div class="text-right text-sm">
                                <span class="font-semibold text-blue-600">${{ number_format($detalle['cantidad_asignada'] * $detalle['precio_venta'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500">
                        <p class="text-sm">Sin productos seleccionados</p>
                    </div>
                @endforelse
            </div>

            <!-- Totales -->
            <div class="mt-4 space-y-2 border-t pt-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Productos:</span>
                    <span class="font-semibold text-gray-900">{{ count($detalles) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Unidades:</span>
                    <span class="font-semibold text-gray-900">{{ $totalUnidades }}</span>
                </div>
                <div class="flex justify-between border-t pt-2 text-lg font-bold text-blue-600">
                    <span>Total:</span>
                    <span>${{ number_format($totalAsignacion, 2) }}</span>
                </div>
            </div>

            @if ($errors->has('detalles'))
                <p class="mt-2 text-sm text-red-600">{{ $errors->first('detalles') }}</p>
            @endif
        </div>
    </div>

    <!-- Sección: Observaciones -->
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900">
            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
            </svg>
            Observaciones
        </h2>

        <textarea wire:model="observaciones" placeholder="Indicaciones, rutas, condiciones especiales..."
            rows="3" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none"></textarea>
    </div>

    <!-- Botones -->
    <div class="flex gap-3">
        <button type="button" onclick="history.back()"
            class="flex-1 rounded-lg border border-gray-300 py-2 font-semibold text-gray-900 transition hover:bg-gray-50">
            Cancelar
        </button>
        <button type="button" wire:click="save"
            class="flex-1 rounded-lg bg-blue-600 py-2 font-semibold text-white transition hover:bg-blue-700">
            ✓ Guardar asignación
        </button>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif
</div>
