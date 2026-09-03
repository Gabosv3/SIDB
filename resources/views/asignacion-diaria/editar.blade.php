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
            min-height: 100vh;
            overflow: visible;
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
            flex-wrap: wrap;
        }

        .header-left {
            min-width: 250px;
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
            min-width: 600px;
        }

        .form-group {
            flex: 1;
            min-width: 140px;
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
            font-family: inherit;
        }

        .form-group select:hover,
        .form-group input:hover {
            border-color: #4b5563;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* ──────────── MAIN CONTENT ──────────── */
        .content {
            display: flex;
            flex: 1;
            overflow: visible;
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
            overflow-x: hidden;
            padding-right: 0.5rem;
        }

        .left-panel::-webkit-scrollbar {
            width: 8px;
        }

        .left-panel::-webkit-scrollbar-track {
            background: transparent;
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
            padding: 1.5rem;
            flex-shrink: 0;
        }

        .search-section h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .search-box {
            margin-bottom: 1.5rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            font-size: 14px;
            font-family: inherit;
        }

        .search-box input::placeholder {
            color: #6b7280;
        }

        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .category-btn {
            padding: 0.625rem 1rem;
            background: #374151;
            border: none;
            border-radius: 0.375rem;
            color: white;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
            white-space: nowrap;
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
            padding: 1.5rem;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .products-section::-webkit-scrollbar {
            width: 8px;
        }

        .products-section::-webkit-scrollbar-track {
            background: transparent;
        }

        .products-section::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }

        .products-section::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }

        .products-header {
            margin-bottom: 1.5rem;
            font-size: 13px;
            color: #9ca3af;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1.5rem;
            grid-auto-rows: max-content;
            align-content: start;
            flex: 1;
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
            min-height: 260px;
        }

        .product-card:hover {
            border-color: #f97316;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(249, 115, 22, 0.2);
        }

        .product-image {
            background: #374151;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 28px;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .product-name {
            font-weight: 600;
            color: white;
            font-size: 13px;
            line-height: 1.4;
            overflow-wrap: break-word;
        }

        .product-code {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }

        .product-stock {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }

        .product-price {
            font-weight: bold;
            color: #fb923c;
            font-size: 14px;
            margin-top: 0.5rem;
        }

        .add-btn {
            width: 100%;
            background: #f97316;
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s;
            font-family: inherit;
            margin-top: auto;
        }

        .add-btn:hover {
            background: #ea580c;
        }

        .add-btn:active {
            transform: scale(0.98);
        }

        /* OBSERVATIONS */
        .observations {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            padding: 1.5rem;
            flex-shrink: 0;
        }

        .observations h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .observations textarea {
            width: 100%;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 0.5rem;
            color: white;
            padding: 0.875rem;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            max-height: 120px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .observations textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        /* RIGHT PANEL */
        .right-panel {
            width: 380px;
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-shrink: 0;
        }

        .cart-header {
            padding: 1rem;
            border-bottom: 1px solid #1f2937;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
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
            overflow-x: hidden;
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
            width: 48px;
            height: 48px;
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
            font-size: 13px;
            font-weight: 600;
            color: white;
            overflow-wrap: break-word;
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
            flex-shrink: 0;
            transition: color 0.2s;
        }

        .remove-btn:hover {
            color: #fca5a5;
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
            font-family: inherit;
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
            padding: 0.25rem 0;
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
            padding: 2rem 1rem;
        }

        .empty-cart p {
            font-size: 12px;
        }

        .cart-footer {
            padding: 1rem;
            border-top: 1px solid #1f2937;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-shrink: 0;
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
            font-family: inherit;
        }

        .save-btn:hover {
            background: #ea580c;
        }

        .save-btn:disabled {
            background: #6b7280;
            cursor: not-allowed;
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

        @media (max-width: 1300px) {
            .content {
                flex-direction: column;
            }

            .right-panel {
                width: 100%;
                max-height: 320px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-right {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($errors->any() || session('error'))
            <div style="background: #dc2626; color: white; padding: 1rem; margin: 1rem; border-radius: 0.5rem; font-size: 14px;">
                @if($errors->any())
                    <strong>Error de validación:</strong>
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                @if(session('error'))
                    <strong>{{ session('error') }}</strong>
                @endif
            </div>
        @endif

        <!-- HEADER -->
        <div class="header">
            <div class="header-left">
                <div class="breadcrumb">
                    <a href="{{ route('filament.administrativo.resources.asignaciones-diarias.index', ['tenant' => $tenant]) }}" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #9ca3af; text-decoration: none; font-size: 14px;">
                        ← Volver a Asignaciones
                    </a>
                </div>
                <h1>Asignación Diaria</h1>
            </div>

            <div class="header-right">
                <div class="form-group">
                    <label>Vendedor</label>
                    <select id="vendedorSelect" required>
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
                    <select id="sucursalSelect" required>
                        <option value="">Seleccionar sucursal...</option>
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id }}" {{ $s->id == $asignacion->sucursal_id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Fecha de jornada</label>
                    <input type="date" id="fechaInput" value="{{ $asignacion->fecha->format('Y-m-d') }}" required>
                </div>
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
                        <input type="text" id="searchInput" placeholder="Buscar por nombre, código...">
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 0.75rem;">Categorías</label>
                        <div class="categories" id="categoriesContainer">
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
                        <span>Mostrando <strong id="productCount">{{ count($productos) }}</strong> productos</span>
                    </div>
                    <div class="products-grid" id="productsGrid">
                        @forelse($productos as $producto)
                            <div class="product-card" data-category-id="{{ $producto->categoria_id }}" data-product-name="{{ strtolower($producto->nombre) }}" data-product-code="{{ strtolower($producto->codigo) }}" data-stock="{{ $producto->stock }}">
                                <div class="product-image">
                                    @if($producto->imagen)
                                        <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}">
                                    @else
                                        📦
                                    @endif
                                </div>
                                <div class="product-info">
                                    <div>
                                        <p class="product-name">{{ $producto->nombre }}</p>
                                        <p class="product-code">{{ $producto->codigo }}</p>
                                        <p class="product-stock">Stock: {{ $producto->stock }}</p>
                                        <p class="product-price">${{ number_format($producto->precio_venta, 2) }}</p>
                                    </div>
                                    <button type="button" class="add-btn" data-product-id="{{ $producto->id }}" data-product-name="{{ $producto->nombre }}" data-product-code="{{ $producto->codigo }}" data-product-image="{{ $producto->imagen }}" data-product-price="{{ $producto->precio_venta }}" data-product-stock="{{ $producto->stock }}" data-category-id="{{ $producto->categoria_id }}" onclick="agregarProducto(this)" {{ $producto->stock <= 0 ? 'disabled style=&quot;opacity: 0.5; cursor: not-allowed;&quot;' : '' }}>{{ $producto->stock <= 0 ? 'Sin stock' : '+ Agregar' }}</button>
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
                    <textarea id="observacionesInput" placeholder="Escribe aquí alguna observación..." rows="2">{{ $asignacion->observaciones ?? "" }}</textarea>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="right-panel">
                <div class="cart-header">
                    <h2>Productos asignados</h2>
                    <span class="cart-badge" id="cartCount">0</span>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        <p>Sin productos seleccionados</p>
                        <p style="font-size: 11px; color: #6b7280;">Agrega productos del catálogo</p>
                    </div>
                </div>

                <div class="cart-footer">
                    <div class="summary-row">
                        <span class="label">Productos:</span>
                        <span class="value" id="summaryProducts">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Unidades:</span>
                        <span class="value" id="summaryUnits">0</span>
                    </div>
                    <div class="summary-total">
                        <span class="label">Total</span>
                        <span class="value" id="summaryTotal">$0.00</span>
                    </div>

                    <button type="button" class="save-btn" onclick="guardarAsignacion()">Guardar asignación</button>
                    <a href="{{ route('filament.administrativo.resources.asignaciones-diarias.index', ['tenant' => auth()->user()->sucursales()->first()->id ?? 1]) }}" class="cancel-link">Cancelar</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const state = {
            detalles: {},
            allProducts: {}
        };

        const assetPath = '{{ asset("storage") }}';
        const productosBuscarUrl = '{{ route('asignacion-diaria.productos-buscar', $tenant) }}';

        // Load existing detalles for edit
        @if(isset($detallesJson))
        try {
            const existingDetalles = {!! $detallesJson !!};
            existingDetalles.forEach(detalle => {
                const key = `p_${detalle.producto_id}`;
                state.detalles[key] = detalle;
            });
        } catch (e) {
            console.error('Error cargando detalles:', e);
        }
        @endif

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatMoney(amount) {
            return '$' + parseFloat(amount).toFixed(2);
        }

        function showNotification(message, type = 'info') {
            // Simple notification using alert for now
            // TODO: Replace with toast system
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function agregarProducto(btn) {
            const productoId = btn.dataset.productId;
            const nombre = btn.dataset.productName;
            const codigo = btn.dataset.productCode;
            const imagen = btn.dataset.productImage;
            const precioVenta = btn.dataset.productPrice;
            const stock = parseInt(btn.dataset.productStock);
            const key = `p_${productoId}`;

            // Validar stock
            if (stock <= 0) {
                showNotification('Este producto no tiene stock disponible', 'warning');
                return;
            }

            // Validar cantidad máxima
            const cantidadActual = state.detalles[key] ? state.detalles[key].cantidad_asignada : 0;
            if (cantidadActual >= stock) {
                showNotification(`No puedes agregar más de ${stock} unidades de este producto`, 'warning');
                return;
            }

            if (state.detalles[key]) {
                state.detalles[key].cantidad_asignada++;
            } else {
                state.detalles[key] = {
                    producto_id: productoId,
                    nombre: nombre,
                    codigo: codigo,
                    imagen: imagen,
                    cantidad_asignada: 1,
                    precio_venta: parseFloat(precioVenta),
                    stock_disponible: stock
                };
            }

            actualizarCarrito();
        }

        function incrementar(key) {
            if (state.detalles[key]) {
                if (state.detalles[key].cantidad_asignada >= state.detalles[key].stock_disponible) {
                    showNotification('Stock limitado', 'warning');
                    return;
                }
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

        function actualizarPrecio(key, nuevoPrecio) {
            if (state.detalles[key]) {
                state.detalles[key].precio_venta_actual = parseFloat(nuevoPrecio) || state.detalles[key].precio_venta;
                actualizarCarrito();
            }
        }

        function actualizarCarrito() {
            const container = document.getElementById('cartItems');
            const detallesArray = Object.entries(state.detalles);

            if (detallesArray.length === 0) {
                container.innerHTML = '<div class="empty-cart"><p>Sin productos seleccionados</p><p style="font-size: 11px; color: #6b7280;">Agrega productos del catálogo</p></div>';
            } else {
                container.innerHTML = detallesArray.map(([key, detalle]) => {
                    const precioActual = detalle.precio_venta_actual || detalle.precio_venta;
                    const diferencia = parseFloat(precioActual) - parseFloat(detalle.precio_venta);
                    const mostrarDiferencia = Math.abs(diferencia) > 0.01;
                    return `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div class="cart-item-image">
                                ${detalle.imagen ? `<img src="${assetPath}/${detalle.imagen}" alt="" onerror="this.textContent='📦'">` : '📦'}
                            </div>
                            <div class="cart-item-details">
                                <div class="cart-item-name">${escapeHtml(detalle.nombre)}</div>
                                <div class="cart-item-code">${escapeHtml(detalle.codigo)}</div>
                                <div class="cart-item-price" style="display: flex; gap: 10px; align-items: center;">
                                    <span>Asignado: <strong>${formatMoney(detalle.precio_venta)}</strong></span>
                                    <input type="number" step="0.01" value="${precioActual}" style="width: 70px; padding: 3px; font-size: 11px;"
                                        onchange="actualizarPrecio('${key}', this.value)" placeholder="Precio venta">
                                    ${mostrarDiferencia ? `<span style="color: ${diferencia > 0 ? '#10b981' : '#f97316'}; font-weight: bold; font-size: 10px;">${diferencia > 0 ? '+' : ''}${formatMoney(diferencia)}</span>` : ''}
                                </div>
                            </div>
                            <button type="button" class="remove-btn" onclick="remover('${key}')">×</button>
                        </div>
                        <div class="quantity-controls">
                            <button type="button" class="qty-btn" onclick="decrementar('${key}')">−</button>
                            <div class="qty-display">${detalle.cantidad_asignada}</div>
                            <button type="button" class="qty-btn" onclick="incrementar('${key}')">+</button>
                        </div>
                        <div class="cart-item-total">${formatMoney(detalle.cantidad_asignada * parseFloat(precioActual))}</div>
                    </div>
                `}).join('');
            }

            // Update summary
            const numProductos = detallesArray.length;
            const totalUnidades = detallesArray.reduce((sum, [_, d]) => sum + d.cantidad_asignada, 0);
            const totalAsignacion = detallesArray.reduce((sum, [_, d]) => {
                const precio = d.precio_venta_actual || d.precio_venta;
                return sum + (d.cantidad_asignada * parseFloat(precio));
            }, 0);

            document.getElementById('cartCount').textContent = numProductos;
            document.getElementById('summaryProducts').textContent = numProductos;
            document.getElementById('summaryUnits').textContent = totalUnidades;
            document.getElementById('summaryTotal').textContent = formatMoney(totalAsignacion);
        }

        function guardarAsignacion() {
            const vendedorId = document.getElementById('vendedorSelect').value;
            const sucursalId = document.getElementById('sucursalSelect').value;
            const fecha = document.getElementById('fechaInput').value;
            const hoy = new Date().toISOString().split('T')[0];
            const numProductos = Object.keys(state.detalles).length;

            // Validación 1: Vendedor
            if (!vendedorId || vendedorId === '') {
                alert('⚠️ VENDEDOR REQUERIDO\n\nDebes seleccionar un vendedor antes de guardar la asignación.');
                document.getElementById('vendedorSelect').focus();
                return;
            }

            // Validación 2: Sucursal
            if (!sucursalId || sucursalId === '') {
                alert('⚠️ SUCURSAL REQUERIDA\n\nDebes seleccionar una sucursal antes de guardar la asignación.');
                document.getElementById('sucursalSelect').focus();
                return;
            }

            // Validación 3: Fecha
            if (!fecha) {
                alert('⚠️ FECHA REQUERIDA\n\nDebes seleccionar una fecha antes de guardar la asignación.');
                document.getElementById('fechaInput').focus();
                return;
            }

            if (fecha < hoy) {
                alert('⚠️ FECHA INVÁLIDA\n\nLa fecha no puede ser anterior a hoy.');
                document.getElementById('fechaInput').focus();
                return;
            }

            // Validación 4: Productos
            if (numProductos === 0) {
                alert('❌ SIN PRODUCTOS\n\nDebes agregar al menos 1 producto a la asignación antes de guardar.\n\nBúscalos en el catálogo de la izquierda.');
                document.getElementById('searchInput').focus();
                return;
            }

            // Confirmación final
            const vendedor = document.getElementById('vendedorSelect').options[document.getElementById('vendedorSelect').selectedIndex].text;
            const confirmMsg = `✓ Confirmar asignación:\n\nVendedor: ${vendedor}\nProductos: ${numProductos}\nUnidades: ${Object.values(state.detalles).reduce((sum, d) => sum + d.cantidad_asignada, 0)}\n\n¿Guardar esta asignación?`;

            if (!confirm(confirmMsg)) {
                return;
            }

            // Crear y enviar form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("asignacion-diaria.guardar", ["tenant" => $tenant]) }}';

            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            form.innerHTML += `<input type="hidden" name="vendedor_id" value="${vendedorId}">`;
            form.innerHTML += `<input type="hidden" name="sucursal_id" value="${sucursalId}">`;
            form.innerHTML += `<input type="hidden" name="fecha" value="${fecha}">`;
            form.innerHTML += `<input type="hidden" name="observaciones" value="${escapeHtml(document.getElementById('observacionesInput').value)}">`;

            let index = 0;
            Object.entries(state.detalles).forEach(([key, detalle]) => {
                form.innerHTML += `<input type="hidden" name="detalles[${index}][producto_id]" value="${detalle.producto_id}">`;
                form.innerHTML += `<input type="hidden" name="detalles[${index}][cantidad_asignada]" value="${detalle.cantidad_asignada}">`;
                const precioFinal = detalle.precio_venta_actual || detalle.precio_venta;
                form.innerHTML += `<input type="hidden" name="detalles[${index}][precio_venta]" value="${parseFloat(precioFinal)}">`;
                index++;
            });

            document.body.appendChild(form);
            form.submit();
        }

        // Construye el HTML de una tarjeta de producto, igual a la que arma
        // el servidor en la carga inicial de la página.
        function tarjetaProductoHtml(p) {
            const imagenHtml = p.imagen
                ? `<img src="${assetPath}/${p.imagen}" alt="${escapeHtml(p.nombre)}">`
                : '📦';

            return `
                <div class="product-card" data-category-id="${p.categoria_id ?? ''}" data-product-name="${escapeHtml(p.nombre.toLowerCase())}" data-product-code="${escapeHtml(p.codigo.toLowerCase())}" data-stock="${p.stock}">
                    <div class="product-image">${imagenHtml}</div>
                    <div class="product-info">
                        <div>
                            <p class="product-name">${escapeHtml(p.nombre)}</p>
                            <p class="product-code">${escapeHtml(p.codigo)}</p>
                            <p class="product-stock">Stock: ${p.stock}</p>
                            <p class="product-price">$${parseFloat(p.precio_venta).toFixed(2)}</p>
                        </div>
                        <button type="button" class="add-btn" data-product-id="${p.id}" data-product-name="${escapeHtml(p.nombre)}" data-product-code="${escapeHtml(p.codigo)}" data-product-image="${p.imagen ?? ''}" data-product-price="${p.precio_venta}" data-product-stock="${p.stock}" data-category-id="${p.categoria_id ?? ''}" onclick="agregarProducto(this)">+ Agregar</button>
                    </div>
                </div>
            `;
        }

        // Búsqueda real contra el servidor: antes esto solo filtraba entre
        // los primeros 12 productos que ya venían cargados en la página.
        let buscarProductosTimeout = null;
        function filtrarProductos() {
            clearTimeout(buscarProductosTimeout);
            buscarProductosTimeout = setTimeout(ejecutarBusquedaProductos, 250);
        }

        function ejecutarBusquedaProductos() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            const categoryId = document.querySelector('.category-btn.active')?.dataset.category || '';
            const grid = document.getElementById('productsGrid');

            const url = new URL(productosBuscarUrl, window.location.origin);
            if (searchTerm) url.searchParams.set('q', searchTerm);
            if (categoryId) url.searchParams.set('categoria_id', categoryId);

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('productCount').textContent = data.total;

                    if (data.productos.length === 0) {
                        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #6b7280;">No hay productos disponibles</div>';
                        return;
                    }

                    grid.innerHTML = data.productos.map(tarjetaProductoHtml).join('');
                })
                .catch(() => {
                    showNotification('No se pudo buscar productos, intenta de nuevo', 'warning');
                });
        }

        // Event listener para búsqueda
        document.getElementById('searchInput').addEventListener('input', filtrarProductos);

        // Category filter
        document.getElementById('categoriesContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('category-btn')) {
                document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                ejecutarBusquedaProductos();
            }
        });

        // Set min date on fecha input
        document.getElementById('fechaInput').min = new Date().toISOString().split('T')[0];

        // Initialize - Se ejecuta directamente porque el script está al final del body
        actualizarCarrito();
    </script>
</body>
</html>
