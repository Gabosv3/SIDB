<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Status de conexión --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Estado de Conexión</h3>
            
            <div id="status-container" class="space-y-3">
                <div class="flex items-center gap-2">
                    <div id="status-indicator" class="w-4 h-4 rounded-full bg-gray-400"></div>
                    <span id="status-text">Verificando...</span>
                </div>
                
                <div id="account-info" class="text-sm text-gray-600" style="display: none;">
                    <p><strong>Número:</strong> <span id="account-number">-</span></p>
                    <p><strong>Nombre:</strong> <span id="account-name">-</span></p>
                </div>

                {{-- Contenedor QR --}}
                <div id="qr-container" style="display: none;" class="mt-4 text-center">
                    <p class="text-sm text-gray-700 mb-2">📱 Escanea el código QR:</p>
                    <img id="qr-image" src="" alt="QR Code" class="w-32 h-32 mx-auto border-2 border-gray-300 rounded">
                </div>

                <button onclick="reconectar()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mt-4">
                    🔄 Reconectar
                </button>
            </div>
        </div>

        {{-- Información útil --}}
        <div class="bg-blue-50 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">ℹ️ Información</h3>
            
            <ul class="space-y-2 text-sm">
                <li>✅ <strong>Gratis:</strong> Sin costos de Meta</li>
                <li>✅ <strong>Personal:</strong> Tu número de WhatsApp</li>
                <li>✅ <strong>Rápido:</strong> Mensajes instantáneos</li>
                <li>⚠️ <strong>Rate limit:</strong> Evita enviar muchos mensajes muy rápido</li>
                <li>📱 <strong>Puerto:</strong> localhost:3333</li>
            </ul>
        </div>
    </div>

    {{-- Enviar Recordatorio --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">📬 Enviar Recordatorio de Pago</h3>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Buscar Cliente</label>
                <input type="text" id="buscar-cliente" placeholder="Escribe el nombre o teléfono..." class="w-full px-4 py-2 border rounded-lg">
                <ul id="lista-clientes" class="bg-gray-100 rounded mt-2 hidden max-h-48 overflow-y-auto">
                </ul>
            </div>

            <button onclick="enviarRecordatorio()" id="btn-recordatorio" disabled class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                ✉️ Enviar Recordatorio
            </button>
        </div>
    </div>

    {{-- Enviar Mensaje Personalizado --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">💬 Mensaje Personalizado</h3>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Número o Búsqueda</label>
                <input type="text" id="numero-mensaje" placeholder="573001234567 o nombre cliente..." class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Mensaje</label>
                <textarea id="mensaje-texto" placeholder="Escribe tu mensaje..." class="w-full px-4 py-2 border rounded-lg h-24"></textarea>
                <div class="text-xs text-gray-600 mt-1">
                    Caracteres: <span id="contador-chars">0</span>/160
                </div>
            </div>

            <button onclick="enviarMensaje()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                ➤ Enviar Mensaje
            </button>
        </div>
    </div>

    {{-- Loader --}}
    <div id="loader" style="display: none;" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 rounded">
        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-3"></div>
            <p class="text-sm font-medium">Procesando...</p>
        </div>
    </div>

    {{-- Respuesta --}}
    <div id="mensaje-respuesta" style="display: none;" class="mt-4"></div>

    <script>
        const BAILEYS_URL = 'http://localhost:3333';
        const LARAVEL_URL = '{{ config('app.url') }}';
        let clienteSeleccionado = null;

        // Verificar estado cada 5 segundos
        async function verificarEstado() {
            try {
                const res = await fetch(`${BAILEYS_URL}/status`);
                const data = await res.json();
                
                const indicator = document.getElementById('status-indicator');
                const statusText = document.getElementById('status-text');
                const accountInfo = document.getElementById('account-info');
                const qrContainer = document.getElementById('qr-container');
                
                if (data.connected) {
                    indicator.className = 'w-4 h-4 rounded-full bg-green-500';
                    statusText.textContent = '✅ Conectado';
                    accountInfo.style.display = 'block';
                    qrContainer.style.display = 'none';
                    document.getElementById('account-number').textContent = data.jid || '-';
                    document.getElementById('account-name').textContent = data.name || '-';
                } else if (data.qrCodePending) {
                    indicator.className = 'w-4 h-4 rounded-full bg-yellow-500';
                    statusText.textContent = '⏳ Esperando QR - Escanea abajo';
                    accountInfo.style.display = 'none';
                    
                    // Obtener imagen QR
                    try {
                        const qrRes = await fetch(`${BAILEYS_URL}/qrcode`);
                        const qrData = await qrRes.json();
                        if (qrData.qr) {
                            document.getElementById('qr-image').src = qrData.qr;
                            qrContainer.style.display = 'block';
                        }
                    } catch (qrErr) {
                        console.error('Error obteniendo QR:', qrErr);
                    }
                } else {
                    indicator.className = 'w-4 h-4 rounded-full bg-red-500';
                    statusText.textContent = '❌ Desconectado';
                    accountInfo.style.display = 'none';
                    qrContainer.style.display = 'none';
                }
            } catch (error) {
                document.getElementById('status-indicator').className = 'w-4 h-4 rounded-full bg-red-500';
                document.getElementById('status-text').textContent = '❌ Error: Servidor no disponible';
            }
        }

        // Búsqueda de clientes (mock)
        document.getElementById('buscar-cliente').addEventListener('input', async function(e) {
            const query = e.target.value;
            if (!query) {
                document.getElementById('lista-clientes').classList.add('hidden');
                return;
            }

            // En producción, aquí harías un fetch a tu API
            const lista = document.getElementById('lista-clientes');
            lista.innerHTML = `<li class="p-2 cursor-pointer hover:bg-gray-200" onclick="seleccionarCliente('573001234567')">📱 +57 300 123 4567</li>`;
            lista.classList.remove('hidden');
        });

        function seleccionarCliente(numero) {
            clienteSeleccionado = numero;
            document.getElementById('buscar-cliente').value = numero;
            document.getElementById('lista-clientes').classList.add('hidden');
            document.getElementById('btn-recordatorio').disabled = false;
        }

        // Enviar recordatorio
        async function enviarRecordatorio() {
            if (!clienteSeleccionado) {
                alert('Selecciona un cliente');
                return;
            }

            mostrarLoader();
            try {
                const res = await fetch(`${LARAVEL_URL}/baileys-cobro/recordatorio/${clienteSeleccionado}`, { method: 'POST' });
                const data = await res.json();

                ocultarLoader();
                const respuesta = document.getElementById('mensaje-respuesta');
                if (data.success) {
                    respuesta.className = 'mt-4 p-4 rounded bg-green-100 text-green-800';
                    respuesta.innerHTML = `✅ <strong>Recordatorio enviado</strong><br>Mensaje: ${data.message}`;
                } else {
                    respuesta.className = 'mt-4 p-4 rounded bg-red-100 text-red-800';
                    respuesta.innerHTML = `❌ ${data.error}`;
                }
                respuesta.style.display = 'block';
            } catch (error) {
                ocultarLoader();
                alert('Error: ' + error.message);
            }
        }

        // Enviar mensaje personalizado
        async function enviarMensaje() {
            const numero = document.getElementById('numero-mensaje').value;
            const mensaje = document.getElementById('mensaje-texto').value;

            if (!numero || !mensaje) {
                alert('Completa todos los campos');
                return;
            }

            mostrarLoader();
            try {
                const res = await fetch(`${LARAVEL_URL}/baileys-cobro/mensaje/${numero}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: mensaje })
                });
                const data = await res.json();

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
            } catch (error) {
                ocultarLoader();
                alert('Error: ' + error.message);
            }
        }

        // Reconectar
        async function reconectar() {
            mostrarLoader();
            try {
                const res = await fetch(`${BAILEYS_URL}/reconnect`, { method: 'POST' });
                const data = await res.json();
                ocultarLoader();
                alert('Reconectando... Escanea el QR en http://localhost:3333/qrcode');
                verificarEstado();
            } catch (error) {
                ocultarLoader();
                alert('Error: ' + error.message);
            }
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
</div>
