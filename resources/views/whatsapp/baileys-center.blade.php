<x-filament-panels::page>
    <x-slot name="heading">
        Centro WhatsApp - Baileys
    </x-slot>

    <x-slot name="description">
        Sistema de mensajería personal con WhatsApp (Sin costos Meta)
    </x-slot>

    {{-- Estado de Baileys --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Status de conexión (sesión propia de quien está viendo esta página) --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Mi sesión de WhatsApp</h3>

            <div id="status-container" class="space-y-3">
                <div class="flex items-center gap-2">
                    <div id="status-indicator" class="w-4 h-4 rounded-full bg-gray-400"></div>
                    <span id="status-text">Verificando...</span>
                </div>

                <div id="account-info" class="text-sm text-gray-600" style="display: none;">
                    <p><strong>Número:</strong> <span id="account-number">-</span></p>
                    <p><strong>Nombre:</strong> <span id="account-name">-</span></p>
                </div>

                <a href="{{ route('whatsapp-center.dashboard') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mt-4">
                    🔄 Conectar / gestionar mi WhatsApp
                </a>
            </div>
        </div>

        {{-- Información útil --}}
        <div class="bg-blue-50 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">ℹ️ Información</h3>

            <ul class="space-y-2 text-sm">
                <li>✅ <strong>Gratis:</strong> Sin costos de Meta</li>
                <li>✅ <strong>Personal:</strong> cada vendedor/cobrador usa su propio WhatsApp</li>
                <li>✅ <strong>Rápido:</strong> Mensajes instantáneos</li>
                <li>⚠️ <strong>Rate limit:</strong> Evita enviar muchos mensajes muy rápido</li>
            </ul>
        </div>
    </div>

    {{-- Sesiones de empleados --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">👥 Sesiones de WhatsApp por empleado</h3>

        @php $sesiones = $this->getSesiones(); @endphp

        @if($sesiones->isEmpty())
            <p class="text-sm text-gray-500">Todavía ningún vendedor/cobrador ha conectado su WhatsApp.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-4">Empleado</th>
                            <th class="py-2 pr-4">Estado</th>
                            <th class="py-2 pr-4">Número</th>
                            <th class="py-2 pr-4">Última actividad</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sesiones as $sesion)
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-4 font-medium">{{ $sesion->user?->name ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    @php
                                        $badge = match($sesion->estado) {
                                            'conectado' => 'bg-green-100 text-green-800',
                                            'esperando_qr' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $sesion->estado)) }}</span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600">{{ $sesion->numero_whatsapp ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $sesion->ultima_actividad_at?->diffForHumans() ?? '—' }}</td>
                                <td class="py-2 pr-4">
                                    <button
                                        type="button"
                                        wire:click="$set('verMensajesDeUserId', {{ $sesion->user_id }})"
                                        class="text-blue-600 hover:underline"
                                    >
                                        Ver mensajes
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($verMensajesDeUserId)
            <div class="mt-6 border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold">
                        Mensajes de {{ $sesiones->firstWhere('user_id', $verMensajesDeUserId)?->user?->name ?? 'empleado' }}
                    </h4>
                    <button type="button" wire:click="$set('verMensajesDeUserId', null)" class="text-sm text-gray-500 hover:underline">Cerrar</button>
                </div>

                @php $conversaciones = $this->getConversacionesDeUsuario(); @endphp

                @if($conversaciones->isEmpty())
                    <p class="text-sm text-gray-500">Este empleado aún no tiene conversaciones registradas.</p>
                @else
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach($conversaciones as $conversacion)
                            <div class="border rounded-lg p-3">
                                <p class="font-medium text-sm mb-2">
                                    {{ $conversacion->cliente?->nombre_completo ?? $conversacion->wa_id }}
                                </p>
                                <div class="space-y-1">
                                    @forelse($conversacion->mensajes as $mensaje)
                                        <div class="text-xs flex gap-2 {{ $mensaje->direction === 'out' ? 'justify-end' : '' }}">
                                            <span class="px-2 py-1 rounded {{ $mensaje->direction === 'out' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $mensaje->body }}
                                            </span>
                                            <span class="text-gray-400">{{ $mensaje->created_at->format('d/m H:i') }}</span>
                                        </div>
                                    @empty
                                        <p class="text-xs text-gray-400">Sin mensajes</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Enviar Recordatorio --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">📬 Enviar Recordatorio de Pago</h3>
        
        <div class="space-y-4">
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Buscar Cliente</span>
                <input 
                    type="text" 
                    id="cliente-busqueda"
                    placeholder="Escribe nombre o número..."
                    class="mt-1 w-full border border-gray-300 rounded px-3 py-2"
                    autocomplete="off"
                >
                <div id="cliente-resultados" class="mt-2 border border-gray-200 rounded bg-gray-50 max-h-48 overflow-y-auto" style="display: none;">
                </div>
            </label>

            <div id="cliente-seleccionado" style="display: none;" class="bg-blue-50 p-3 rounded">
                <p><strong>Cliente:</strong> <span id="cliente-nombre"></span></p>
                <p><strong>WhatsApp:</strong> <span id="cliente-whatsapp"></span></p>
                <p><strong>Cuota pendiente:</strong> $<span id="cliente-cuota"></span></p>
            </div>

            <button 
                onclick="enviarRecordatorio()"
                id="btn-recordatorio"
                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded disabled:opacity-50"
                disabled
            >
                📤 Enviar Recordatorio
            </button>

            <div id="recordatorio-respuesta" style="display: none;" class="mt-4 p-4 rounded">
            </div>
        </div>
    </div>

    {{-- Enviar Mensaje Personalizado --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">💬 Enviar Mensaje Personalizado</h3>
        
        <div class="space-y-4">
            <label class="block">
                <span class="text-sm font-medium text-gray-700">Cliente (Selecciona arriba primero)</span>
                <div class="mt-1 bg-gray-100 p-3 rounded text-gray-600" id="cliente-seleccionado-msg">
                    Selecciona un cliente primero
                </div>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-gray-700">Mensaje (máx. 4096 caracteres)</span>
                <textarea 
                    id="mensaje-texto"
                    placeholder="Escribe tu mensaje aquí..."
                    maxlength="4096"
                    rows="4"
                    class="mt-1 w-full border border-gray-300 rounded px-3 py-2"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1"><span id="contador-chars">0</span>/4096</p>
            </label>

            <button 
                onclick="enviarMensajePersonalizado()"
                id="btn-mensaje"
                class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded disabled:opacity-50"
                disabled
            >
                📤 Enviar Mensaje
            </button>

            <div id="mensaje-respuesta" style="display: none;" class="mt-4 p-4 rounded">
            </div>
        </div>
    </div>

    {{-- Spinner/Loader --}}
    <div id="loader" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
            <p>Procesando...</p>
        </div>
    </div>

    <script>
        let clienteSeleccionado = null;

        // Verificar estado cada 5 segundos
        function verificarEstado() {
            fetch('{{ route("baileys.cobro.status") }}')
                .then(r => r.json())
                .then(data => {
                    const indicator = document.getElementById('status-indicator');
                    const statusText = document.getElementById('status-text');
                    
                    if (data.connected) {
                        indicator.className = 'w-4 h-4 rounded-full bg-green-500';
                        statusText.textContent = '✅ Conectado';
                        
                        if (data.account) {
                            document.getElementById('account-number').textContent = data.account.jid || '-';
                            document.getElementById('account-name').textContent = data.account.name || '-';
                            document.getElementById('account-info').style.display = 'block';
                        }
                    } else if (data.pending_qr) {
                        indicator.className = 'w-4 h-4 rounded-full bg-yellow-500';
                        statusText.textContent = '⏳ Escaneando QR...';
                    } else {
                        indicator.className = 'w-4 h-4 rounded-full bg-red-500';
                        statusText.textContent = '❌ Desconectado';
                        document.getElementById('account-info').style.display = 'none';
                    }
                })
                .catch(err => {
                    document.getElementById('status-indicator').className = 'w-4 h-4 rounded-full bg-red-500';
                    document.getElementById('status-text').textContent = '❌ Error de conexión';
                });
        }

        // Buscar clientes
        document.getElementById('cliente-busqueda').addEventListener('input', function(e) {
            const query = e.target.value;
            if (query.length < 2) {
                document.getElementById('cliente-resultados').style.display = 'none';
                return;
            }

            fetch(`/api/clientes?search=${query}`)
                .then(r => r.json())
                .then(data => {
                    const resultados = document.getElementById('cliente-resultados');
                    if (data.data && data.data.length > 0) {
                        resultados.innerHTML = data.data.map(cliente => `
                            <div onclick="seleccionarCliente(${cliente.id}, '${cliente.nombre}', '${cliente.telefono_whatsapp}')"
                                 class="p-2 border-b cursor-pointer hover:bg-gray-100">
                                <strong>${cliente.nombre}</strong> - ${cliente.telefono_whatsapp || 'Sin WhatsApp'}
                            </div>
                        `).join('');
                        resultados.style.display = 'block';
                    }
                });
        });

        // Seleccionar cliente
        function seleccionarCliente(id, nombre, whatsapp) {
            clienteSeleccionado = { id, nombre, whatsapp };
            
            document.getElementById('cliente-busqueda').value = nombre;
            document.getElementById('cliente-resultados').style.display = 'none';
            
            // Cargar cuota pendiente
            fetch(`/api/clientes/${id}`)
                .then(r => r.json())
                .then(data => {
                    const cuota = data.data?.gestionesCobro?.[0] || {};
                    document.getElementById('cliente-nombre').textContent = nombre;
                    document.getElementById('cliente-whatsapp').textContent = whatsapp;
                    document.getElementById('cliente-cuota').textContent = cuota.monto_cuota || '0.00';
                    document.getElementById('cliente-seleccionado').style.display = 'block';
                    document.getElementById('cliente-seleccionado-msg').textContent = nombre;
                    document.getElementById('btn-recordatorio').disabled = false;
                    document.getElementById('btn-mensaje').disabled = false;
                });
        }

        // Enviar recordatorio
        function enviarRecordatorio() {
            if (!clienteSeleccionado) return;
            
            mostrarLoader();
            fetch(`{{ route("baileys.cobro.recordatorio", ":id") }}`.replace(':id', clienteSeleccionado.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                ocultarLoader();
                const respuesta = document.getElementById('recordatorio-respuesta');
                if (data.success) {
                    respuesta.className = 'mt-4 p-4 rounded bg-green-100 text-green-800';
                    respuesta.innerHTML = `✅ <strong>${data.message}</strong><br>ID: ${data.messageId}`;
                } else {
                    respuesta.className = 'mt-4 p-4 rounded bg-red-100 text-red-800';
                    respuesta.innerHTML = `❌ ${data.error}`;
                }
                respuesta.style.display = 'block';
            });
        }

        // Enviar mensaje personalizado
        function enviarMensajePersonalizado() {
            if (!clienteSeleccionado) return;
            
            const mensaje = document.getElementById('mensaje-texto').value;
            if (!mensaje) return alert('Escribe un mensaje');
            
            mostrarLoader();
            fetch(`{{ route("baileys.cobro.mensaje", ":id") }}`.replace(':id', clienteSeleccionado.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensaje })
            })
            .then(r => r.json())
            .then(data => {
                ocultarLoader();
                const respuesta = document.getElementById('mensaje-respuesta');
                if (data.success) {
                    respuesta.className = 'mt-4 p-4 rounded bg-green-100 text-green-800';
                    respuesta.innerHTML = `✅ <strong>${data.message}</strong><br>ID: ${data.messageId}`;
                    document.getElementById('mensaje-texto').value = '';
                    document.getElementById('contador-chars').textContent = '0';
                } else {
                    respuesta.className = 'mt-4 p-4 rounded bg-red-100 text-red-800';
                    respuesta.innerHTML = `❌ ${data.error}`;
                }
                respuesta.style.display = 'block';
            });
        }

        // Contador de caracteres
        document.getElementById('mensaje-texto').addEventListener('input', function(e) {
            document.getElementById('contador-chars').textContent = e.target.value.length;
        });

        // Funciones auxiliares
        function mostrarLoader() {
            document.getElementById('loader').style.display = 'flex';
        }

        function ocultarLoader() {
            document.getElementById('loader').style.display = 'none';
        }

        // Iniciar verificación
        verificarEstado();
        setInterval(verificarEstado, 5000);
    </script>

    <style>
        input, textarea {
            @apply focus:outline-none focus:ring-2 focus:ring-blue-500;
        }
    </style>
</x-filament-panels::page>
