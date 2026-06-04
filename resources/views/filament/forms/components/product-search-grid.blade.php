<div>
    <div x-data="{
        search: '',
        category: null,
        productos: @json(\App\Models\Producto::where('activo', true)->orderBy('nombre')->get()),
        categorias: @json(\App\Models\Categoria::orderBy('nombre')->get()),
    }"
    @class(['fi-fo-field-wrapper rounded-lg border border-gray-200 bg-white p-6'])>

        <!-- Título -->
        <h3 class="mb-4 text-lg font-semibold text-gray-900">
            Seleccionar Productos
        </h3>

        <!-- Búsqueda -->
        <div class="mb-4">
            <input
                type="text"
                x-model="search"
                placeholder="Buscar por nombre o código..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none"
            >
        </div>

        <!-- Categorías -->
        <div class="mb-4">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="category = null"
                    :class="{'bg-blue-500 text-white': category === null, 'bg-gray-100 text-gray-900': category !== null}"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                >
                    Todos
                </button>
                <template x-for="cat in categorias" :key="cat.id">
                    <button
                        type="button"
                        @click="category = cat.id"
                        :class="{'bg-blue-500 text-white': category === cat.id, 'bg-gray-100 text-gray-900': category !== cat.id}"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        x-text="cat.nombre"
                    ></button>
                </template>
            </div>
        </div>

        <!-- Grid de productos -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            <template x-for="producto in productos.filter(p => {
                const matchSearch = !search ||
                    p.nombre.toLowerCase().includes(search.toLowerCase()) ||
                    p.codigo.toLowerCase().includes(search.toLowerCase());
                const matchCategory = !category || p.categoria_id === category;
                return matchSearch && matchCategory;
            })" :key="producto.id">
                <button
                    type="button"
                    @click="
                        const key = 'producto_' + producto.id;
                        const detalles = $wire.get('data.detalles') || [];
                        const existente = detalles.find(d => d.producto_id === producto.id);
                        if (existente) {
                            existente.cantidad_asignada++;
                        } else {
                            detalles.push({
                                producto_id: producto.id,
                                cantidad_asignada: 1,
                                precio_venta: producto.precio_venta
                            });
                        }
                        $wire.set('data.detalles', detalles);
                    "
                    class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-3 text-center transition hover:border-blue-500 hover:shadow-md"
                >
                    <div class="mb-2 h-16 w-full bg-gray-100 rounded flex items-center justify-center">
                        <template x-if="producto.imagen">
                            <img :src="'/storage/' + producto.imagen" :alt="producto.nombre" class="h-full w-full object-cover rounded">
                        </template>
                        <template x-if="!producto.imagen">
                            <span class="text-2xl">📦</span>
                        </template>
                    </div>
                    <p class="mb-1 line-clamp-2 text-xs font-semibold text-gray-900" x-text="producto.nombre"></p>
                    <p class="mb-1 text-xs text-gray-500" x-text="producto.codigo"></p>
                    <p class="mb-2 text-xs text-gray-500" x-text="'Stock: ' + producto.stock"></p>
                    <p class="mb-2 font-bold text-blue-600" x-text="'$' + producto.precio_venta.toFixed(2)"></p>
                    <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        + Agregar
                    </button>
                </button>
            </template>
        </div>

        <!-- Sin resultados -->
        <template x-if="productos.filter(p => {
            const matchSearch = !search ||
                p.nombre.toLowerCase().includes(search.toLowerCase()) ||
                p.codigo.toLowerCase().includes(search.toLowerCase());
            const matchCategory = !category || p.categoria_id === category;
            return matchSearch && matchCategory;
        }).length === 0">
            <div class="py-8 text-center text-gray-500">
                <p>No hay productos que coincidan con tu búsqueda</p>
            </div>
        </template>
    </div>
</div>
