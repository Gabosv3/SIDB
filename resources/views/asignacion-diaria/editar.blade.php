<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Asignación Diaria</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #0f172a;
            color: #f1f5f9;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* ──────────── HEADER ──────────── */
        .header {
            background: #111827;
            border-bottom: 1px solid #1f2937;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .header-left h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .header-left .breadcrumb {
            font-size: 12px;
            color: #9ca3af;
        }

        .header-left .breadcrumb a {
            color: #9ca3af;
            text-decoration: none;
        }

        .header-right {
            display: flex;
            gap: 1.5rem;
            flex: 1;
            max-width: 800px;
        }

        .form-group {
            flex: 1;
            min-width: 150px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group select:hover,
        .form-group input:hover {
            border-color: #4b5563;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #f97316;
        }

        /* ──────────── MAIN CONTENT ──────────── */
        .content {
            display: flex;
            flex: 1;
            overflow: hidden;
            gap: 1rem;
            padding: 1.5rem;
        }

        /* LEFT PANEL */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .left-panel::-webkit-scrollbar {
            width: 8px;
        }

        .left-panel::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 10px;
        }

        .left-panel::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }

        .left-panel::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }

        .search-section {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .search-section h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .search-box {
            margin-bottom: 1rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 14px;
            margin-bottom: 0.75rem;
        }

        .search-box input::placeholder {
            color: #6b7280;
        }

        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .category-btn {
            padding: 0.5rem 0.75rem;
            background: #374151;
            border: none;
            border-radius: 0.375rem;
            color: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .category-btn:hover {
            background: #4b5563;
        }

        .category-btn.active {
            background: #f97316;
            color: white;
        }

        .products-section {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            padding: 1rem;
            flex: 1;
            overflow-y: auto;
        }

        .products-section::-webkit-scrollbar {
            width: 8px;
        }

        .products-section::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 10px;
        }

        .products-section::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }

        .products-section::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 12px;
        }

        .products-header .count {
            color: #9ca3af;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
        }

        .product-card {
            border: 1px solid #374151;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #1f2937;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            border-color: #f97316;
            transform: translateY(-2px);
        }

        .product-image {
            background: #374151;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 24px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 0.75rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            color: white;
            font-size: 12px;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-code {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .product-stock {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-weight: bold;
            color: #fb923c;
            font-size: 12px;
            margin-bottom: 0.5rem;
        }

        .add-btn {
            width: 100%;
            background: #f97316;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .add-btn:hover {
            background: #ea580c;
        }

        /* RIGHT PANEL */
        .right-panel {
            width: 320px;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .cart-header {
            padding: 1rem;
            border-bottom: 1px solid #1f2937;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header h2 {
            font-size: 14px;
            font-weight: 600;
        }

        .cart-badge {
            background: #f97316;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            min-width: 24px;
            text-align: center;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track {
            background: transparent;
        }

        .cart-items::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 3px;
        }

        .cart-item {
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.75rem;
            padding: 0.75rem;
        }

        .cart-item-header {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .cart-item-image {
            width: 40px;
            height: 40px;
            background: #374151;
            border-radius: 0.5rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 14px;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
            min-width: 0;
        }

        .cart-item-name {
            font-size: 12px;
            font-weight: 600;
            color: white;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cart-item-code {
            font-size: 11px;
            color: #9ca3af;
        }

        .cart-item-price {
            font-size: 12px;
            font-weight: bold;
            color: #fb923c;
        }

        .remove-btn {
            color: #f87171;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
        }

        .quantity-controls {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .qty-btn {
            flex: 1;
            background: #374151;
            border: none;
            color: white;
            padding: 0.25rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #4b5563;
        }

        .qty-display {
            flex: 1;
            text-align: center;
            font-weight: 600;
            color: white;
        }

        .cart-item-total {
            text-align: right;
            font-size: 12px;
            font-weight: 600;
            color: #fb923c;
        }

        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            text-align: center;
        }

        .empty-cart p {
            font-size: 12px;
            margin-bottom: 0.5rem;
        }

        .cart-footer {
            padding: 1rem;
            border-top: 1px solid #1f2937;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .summary-row .label {
            color: #9ca3af;
        }

        .summary-row .value {
            color: white;
            font-weight: 600;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 0.5rem;
            border-top: 1px solid #374151;
            font-size: 16px;
            font-weight: bold;
        }

        .summary-total .label {
            color: white;
        }

        .summary-total .value {
            color: #fb923c;
        }

        .save-btn {
            width: 100%;
            background: #f97316;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }

        .save-btn:hover {
            background: #ea580c;
        }

        .cancel-link {
            display: block;
            text-align: center;
            color: #9ca3af;
            text-decoration: none;
            font-size: 12px;
            padding: 0.5rem;
            transition: color 0.2s;
        }

        .cancel-link:hover {
            color: #d1d5db;
        }

        /* OBSERVATIONS */
        .observations {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .observations h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .observations textarea {
            width: 100%;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            padding: 0.75rem;
            font-size: 14px;
            resize: none;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .observations textarea:focus {
            outline: none;
            border-color: #f97316;
        }

        @media (max-width: 1200px) {
            .content {
                flex-direction: column;
            }

            .right-panel {
                width: 100%;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <div class="breadcrumb">
                    <a href="{{ route('filament.administrativo.resources.asignaciones-diarias.index', ['tenant' => auth()->user()->sucursales()->first()->id]) }}">
                        Asignaciones Diarias
                    </a>
                    <span>/</span>
                    <span>Editar</span>
                </div>
                <h1>Editar Asignación Diaria</h1>
            </div>

            <div class="header-right">
                <form id="mainForm" method="POST" action="{{ route('asignacion-diaria.actualizar', $asignacion) }}" style="display: flex; gap: 1.5rem; flex: 1; max-width: 800px;">
                    @csrf

                    <div class="form-group">
                        <label>Vendedor</label>
                        <select name="vendedor_id" id="vendedorSelect" required>
                            <option value="">Seleccionar vendedor...</option>
                            @foreach($vendedores as $v)
                                <option value="{{ $v->id }}" {{ $v->id == $asignacion->vendedor_id ? 'selected' : '' }}>
                                    {{ $v->nombre }} {{ $v->apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sucursal</label>
                        <select name="sucursal_id" id="sucursalSelect" required>
                            <option value="">Sucursal...</option>
                            @foreach($sucursales as $s)
                                <option value="{{ $s->id }}" {{ $s->id == $asignacion->sucursal_id ? 'selected' : '' }}>
                                    {{ $s->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fecha de jornada</label>
                        <input type="date" name="fecha" id="fechaInput" value="{{ $asignacion->fecha->format('Y-m-d') }}" required>
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="content">
            <!-- LEFT PANEL -->
            <div class="left-panel">
                <!-- SEARCH SECTION -->
                <div class="search-section">
                    <h3>Buscar productos</h3>

                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar por nombre, código o escanear...">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 0.75rem;">Categorías</label>
                        <div class="categories">
                            <button class="category-btn active" data-category="">Todos</button>
                            @foreach($categorias as $cat)
                                <button class="category-btn" data-category="{{ $cat->id }}">{{ $cat->nombre }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- PRODUCTS SECTION -->
                <div class="products-section">
                    <div class="products-header">
                        <span class="count">Mostrando <span id="productCount">{{ $productos->count() }}</span> productos</span>
                    </div>

                    <div class="products-grid" id="productsGrid">
                        @forelse($productos as $producto)
                            <div class="product-card" onclick="agregarProducto({{ $producto->id }})">
                                <div class="product-image">
                                    @if($producto->imagen)
                                        <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}">
                                    @else
                                        📦
                                    @endif
                                </div>
                                <div class="product-info">
                                    <p class="product-name">{{ $producto->nombre }}</p>
                                    <p class="product-code">{{ $producto->codigo }}</p>
                                    <p class="product-stock">Stock: {{ $producto->stock }}</p>
                                    <p class="product-price">${{ number_format($producto->precio_venta, 2) }}</p>
                                    <button type="button" class="add-btn">+ Agregar</button>
                                </div>
                            </div>
                        @empty
                            <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #6b7280;">
                                No hay productos disponibles
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- OBSERVATIONS -->
                <div class="observations">
                    <h3>Observaciones</h3>
                    <textarea id="observacionesInput" name="observaciones" placeholder="Escribe aquí alguna observación..." rows="2">{{ $asignacion->observaciones }}</textarea>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel">
                <div class="cart-header">
                    <h2>Productos asignados</h2>
                    <span class="cart-badge" id="cartCount">{{ $asignacion->detalles->count() }}</span>
                </div>

                <div class="cart-items" id="cartItems">
                    @if($asignacion->detalles->count() === 0)
                        <div class="empty-cart">
                            <p>Sin productos seleccionados</p>
                            <p style="font-size: 11px; color: #6b7280;">Agrega productos del catálogo</p>
                        </div>
                    @endif
                </div>

                <div class="cart-footer">
                    <div class="summary-row">
                        <span class="label">Productos:</span>
                        <span class="value" id="summaryProducts">{{ $asignacion->detalles->count() }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Unidades:</span>
                        <span class="value" id="summaryUnits">{{ $asignacion->detalles->sum('cantidad_asignada') }}</span>
                    </div>
                    <div class="summary-total">
                        <span class="label">Total</span>
                        <span class="value" id="summaryTotal">${{ number_format($asignacion->detalles->sum(fn($d) => $d->cantidad_asignada * $d->precio_venta), 2) }}</span>
                    </div>

                    <button type="button" class="save-btn" onclick="guardarAsignacion()">Guardar cambios</button>
                    <a href="{{ route('filament.administrativo.resources.asignaciones-diarias.index', ['tenant' => auth()->user()->sucursales()->first()->id]) }}" class="cancel-link">Cancelar</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // State management
        const state = {
            detalles: {},
            productos: @json($productos->items() ?? []),
        };

        // Map product data
        state.productosMap = {};
        state.productos.forEach(p => {
            state.productosMap[p.id] = p;
        });

        // Load existing detalles
        const existingDetalles = @json($asignacion->detalles);
        existingDetalles.forEach(d => {
            const key = `p_${d.producto_id}`;
            state.detalles[key] = {
                producto_id: d.producto_id,
                nombre: d.producto.nombre,
                codigo: d.producto.codigo,
                imagen: d.producto.imagen,
                cantidad_asignada: d.cantidad_asignada,
                precio_venta: parseFloat(d.precio_venta),
            };
        });

        function agregarProducto(productoId) {
            const producto = state.productosMap[productoId];
            if (!producto) return;

            const key = `p_${productoId}`;
            if (state.detalles[key]) {
                state.detalles[key].cantidad_asignada++;
            } else {
                state.detalles[key] = {
                    producto_id: productoId,
                    nombre: producto.nombre,
                    codigo: producto.codigo,
                    imagen: producto.imagen,
                    cantidad_asignada: 1,
                    precio_venta: parseFloat(producto.precio_venta),
                };
            }

            actualizarCarrito();
        }

        function incrementar(key) {
            if (state.detalles[key]) {
                state.detalles[key].cantidad_asignada++;
                actualizarCarrito();
            }
        }

        function decrementar(key) {
            if (state.detalles[key]) {
                state.detalles[key].cantidad_asignada--;
                if (state.detalles[key].cantidad_asignada <= 0) {
                    delete state.detalles[key];
                }
                actualizarCarrito();
            }
        }

        function remover(key) {
            delete state.detalles[key];
            actualizarCarrito();
        }

        function actualizarCarrito() {
            const cartHtml = Object.keys(state.detalles).length === 0
                ? '<div class="empty-cart"><p>Sin productos seleccionados</p><p style="font-size: 11px; color: #6b7280;">Agrega productos del catálogo</p></div>'
                : Object.entries(state.detalles).map(([key, detalle]) => `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div class="cart-item-image">
                                ${detalle.imagen ? `<img src="{{ asset('storage') }}/${detalle.imagen}" alt="">` : '📦'}
                            </div>
                            <div class="cart-item-details">
                                <div class="cart-item-name">${detalle.nombre}</div>
                                <div class="cart-item-code">${detalle.codigo}</div>
                                <div class="cart-item-price">$${detalle.precio_venta.toFixed(2)}</div>
                            </div>
                            <button type="button" class="remove-btn" onclick="remover('${key}')">×</button>
                        </div>
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn" onclick="decrementar('${key}')">−</button>
                            <div class="qty-display">${detalle.cantidad_asignada}</div>
                            <button type="button" class="qty-btn" onclick="incrementar('${key}')">+</button>
                        </div>
                        <div class="cart-item-total">$${(detalle.cantidad_asignada * detalle.precio_venta).toFixed(2)}</div>
                    </div>
                `).join('');

            document.getElementById('cartItems').innerHTML = cartHtml;

            // Update summary
            const numProductos = Object.keys(state.detalles).length;
            const totalUnidades = Object.values(state.detalles).reduce((sum, d) => sum + d.cantidad_asignada, 0);
            const totalAsignacion = Object.values(state.detalles).reduce((sum, d) => sum + (d.cantidad_asignada * d.precio_venta), 0);

            document.getElementById('cartCount').textContent = numProductos;
            document.getElementById('summaryProducts').textContent = numProductos;
            document.getElementById('summaryUnits').textContent = totalUnidades;
            document.getElementById('summaryTotal').textContent = '$' + totalAsignacion.toFixed(2);
        }

        function guardarAsignacion() {
            if (!document.getElementById('vendedorSelect').value) {
                alert('Selecciona un vendedor');
                return;
            }
            if (!document.getElementById('sucursalSelect').value) {
                alert('Selecciona una sucursal');
                return;
            }
            if (Object.keys(state.detalles).length === 0) {
                alert('Agrega al menos un producto');
                return;
            }

            // Create hidden inputs for detalles
            const form = document.getElementById('mainForm');
            Object.entries(state.detalles).forEach(([key, detalle], index) => {
                form.innerHTML += `
                    <input type="hidden" name="detalles[${index}][producto_id]" value="${detalle.producto_id}">
                    <input type="hidden" name="detalles[${index}][cantidad_asignada]" value="${detalle.cantidad_asignada}">
                    <input type="hidden" name="detalles[${index}][precio_venta]" value="${detalle.precio_venta}">
                `;
            });

            form.submit();
        }

        // Initialize
        actualizarCarrito();
    </script>
</body>
</html>
