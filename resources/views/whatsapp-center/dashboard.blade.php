<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Center - SIDB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .whatsapp-gradient {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .status-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .message-bubble {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header -->
            <div class="mb-8">
                <div class="glass-effect rounded-2xl p-8 border-t border-green-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2">
                                <span class="whatsapp-gradient bg-clip-text text-transparent">📱 WhatsApp Center</span>
                            </h1>
                            <p class="text-gray-300">Centro de control unificado para mensajes WhatsApp</p>
                        </div>
                        <div class="text-right">
                            <div class="inline-block bg-green-500/20 border border-green-400 rounded-lg px-6 py-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <div id="status-dot" class="w-3 h-3 rounded-full bg-yellow-500 status-pulse"></div>
                                    <span class="text-gray-300 text-sm">Estado</span>
                                </div>
                                <div id="status-text" class="text-white font-semibold">Verificando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                <!-- Status Card -->
                <div class="glass-effect rounded-2xl p-6 border-t border-blue-400">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <span class="text-xl">🔌</span>
                        </div>
                        <h2 class="text-xl font-semibold text-white">Conexión</h2>
                    </div>

                    <div id="account-info" class="space-y-3 hidden">
                        <div class="bg-green-500/10 rounded-lg p-3 border border-green-400/30">
                            <p class="text-xs text-gray-400">Número</p>
                            <p id="account-number" class="text-lg font-mono text-green-400">-</p>
                        </div>
                        <div class="bg-blue-500/10 rounded-lg p-3 border border-blue-400/30">
                            <p class="text-xs text-gray-400">Nombre</p>
                            <p id="account-name" class="text-lg text-blue-300">-</p>
                        </div>
                    </div>

                    <div id="qr-container" class="hidden">
                        <p class="text-sm text-gray-300 mb-4 text-center">Escanea el código QR:</p>
                        <div class="bg-white rounded-lg p-4 mx-auto w-fit">
                            <img id="qr-image" src="" alt="QR Code" class="w-48 h-48">
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button onclick="reconectar()" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            🔄 Reconectar
                        </button>
                        <button onclick="desconectar()" class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-300 px-4 py-2 rounded-lg font-medium transition border border-red-500/50">
                            🚫 Desconectar
                        </button>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="glass-effect rounded-2xl p-6 border-t border-purple-400">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <span class="text-xl">📊</span>
                        </div>
                        <h2 class="text-xl font-semibold text-white">Estadísticas</h2>
                    </div>

                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Mensajes enviados</span>
                            <span id="stat-sent" class="text-2xl font-bold text-green-400">0</span>
                        </div>
                        <div class="w-full bg-gray-700/30 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="space-y-3 mt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Mensajes recibidos</span>
                            <span id="stat-received" class="text-2xl font-bold text-blue-400">0</span>
                        </div>
                        <div class="w-full bg-gray-700/30 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="mt-6 p-3 bg-yellow-500/10 rounded-lg border border-yellow-500/30">
                        <p class="text-xs text-gray-400 mb-1">Rate Limit</p>
                        <p class="text-sm text-yellow-300">⚠️ Máx 100 mensajes/minuto</p>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="glass-effect rounded-2xl p-6 border-t border-cyan-400">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                            <span class="text-xl">ℹ️</span>
                        </div>
                        <h2 class="text-xl font-semibold text-white">Características</h2>
                    </div>

                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center gap-2 text-gray-300">
                            <span class="text-green-400">✓</span> Sin costos de Meta API
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <span class="text-green-400">✓</span> Tu número personal
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <span class="text-green-400">✓</span> Mensajes instantáneos
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <span class="text-green-400">✓</span> Sin intermediarios
                        </li>
                        <li class="flex items-center gap-2 text-gray-300">
                            <span class="text-green-400">✓</span> Disponible 24/7
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Send Message Section -->
            <div class="glass-effect rounded-2xl p-6 border-t border-green-400 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-xl">💬</span>
                    </div>
                    <h2 class="text-2xl font-semibold text-white">Enviar Mensaje</h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Número de teléfono</label>
                        <input type="text" id="numero-destino" placeholder="573001234567 o +57 300 123 4567" 
                               class="w-full bg-gray-800/50 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Archivo (opcional)</label>
                        <input type="file" id="archivo-mensaje" accept="image/*,video/*,audio/*,application/pdf"
                               class="w-full bg-gray-800/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Mensaje</label>
                    <textarea id="mensaje-texto" placeholder="Escribe tu mensaje aquí..." 
                              class="w-full bg-gray-800/50 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none transition h-32 resize-none"></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-xs text-gray-400">Máximo 4096 caracteres</span>
                        <span class="text-sm font-semibold">
                            <span id="contador-chars" class="text-green-400">0</span>/4096
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button onclick="limpiarFormulario()" class="flex-1 bg-gray-700/50 hover:bg-gray-700 text-gray-300 px-6 py-3 rounded-lg font-medium transition border border-gray-600">
                        🗑️ Limpiar
                    </button>
                    <button onclick="enviarMensaje()" class="flex-1 whatsapp-gradient text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg hover:shadow-green-500/50 transition">
                        ➤ Enviar Mensaje
                    </button>
                </div>
            </div>

            <!-- Chat History -->
            <div class="glass-effect rounded-2xl p-6 border-t border-orange-400">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center">
                        <span class="text-xl">📝</span>
                    </div>
                    <h2 class="text-2xl font-semibold text-white">Historial</h2>
                </div>

                <div id="chat-history" class="space-y-3 max-h-96 overflow-y-auto">
                    <div class="text-center py-8 text-gray-500">
                        📭 Sin mensajes aún...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loader -->
    <div id="loader" style="display: none;" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-slate-800/90 p-8 rounded-2xl shadow-xl text-center border border-green-400/30">
            <div class="inline-block">
                <div class="w-16 h-16 border-4 border-gray-600 border-t-green-500 rounded-full animate-spin mb-4 mx-auto"></div>
                <p class="text-white font-semibold">Enviando mensaje...</p>
                <p class="text-gray-400 text-sm mt-2">Por favor espera</p>
            </div>
        </div>
    </div>

    <script>
        const BAILEYS_URL = 'http://localhost:3333';
        const LARAVEL_URL = 'http://localhost:8000';
        let messageHistory = [];

        // Cargar estado inicial
        window.addEventListener('load', () => {
            verificarEstado();
            actualizarEstadisticas();
            setInterval(verificarEstado, 5000);
            setInterval(actualizarEstadisticas, 5000);
        });

        // Contador de caracteres
        document.getElementById('mensaje-texto').addEventListener('input', function(e) {
            document.getElementById('contador-chars').textContent = e.target.value.length;
        });

        // Verificar estado cada 5 segundos
        async function verificarEstado() {
            try {
                const res = await fetch(`${BAILEYS_URL}/status`);
                const data = await res.json();

                const dot = document.getElementById('status-dot');
                const statusText = document.getElementById('status-text');
                const accountInfo = document.getElementById('account-info');
                const qrContainer = document.getElementById('qr-container');

                if (data.connected) {
                    dot.className = 'w-3 h-3 rounded-full bg-green-500 status-pulse';
                    statusText.textContent = '✅ Conectado';
                    accountInfo.classList.remove('hidden');
                    qrContainer.classList.add('hidden');

                    try {
                        const infoRes = await fetch(`${BAILEYS_URL}/info`);
                        const infoData = await infoRes.json();
                        document.getElementById('account-number').textContent = infoData.jid || '-';
                        document.getElementById('account-name').textContent = infoData.name || '-';
                    } catch (e) {}
                } else if (data.qrCodePending) {
                    dot.className = 'w-3 h-3 rounded-full bg-yellow-500 status-pulse';
                    statusText.textContent = '⏳ Esperando QR';
                    accountInfo.classList.add('hidden');

                    try {
                        const qrRes = await fetch(`${BAILEYS_URL}/qrcode`);
                        const qrData = await qrRes.json();
                        if (qrData.qr) {
                            document.getElementById('qr-image').src = qrData.qr;
                            qrContainer.classList.remove('hidden');
                        }
                    } catch (e) {}
                } else {
                    dot.className = 'w-3 h-3 rounded-full bg-red-500';
                    statusText.textContent = '❌ Desconectado';
                    accountInfo.classList.add('hidden');
                    qrContainer.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error obteniendo estado:', error);
                document.getElementById('status-dot').className = 'w-3 h-3 rounded-full bg-red-500';
                document.getElementById('status-text').textContent = '❌ Error de conexión';
            }
        }

        // Enviar mensaje
        async function enviarMensaje() {
            const numero = document.getElementById('numero-destino').value.trim();
            const mensaje = document.getElementById('mensaje-texto').value.trim();

            if (!numero || !mensaje) {
                alert('⚠️ Por favor completa todos los campos requeridos');
                return;
            }

            mostrarLoader();

            try {
                const res = await fetch(`${LARAVEL_URL}/whatsapp-center/api/send`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ to: numero, message: mensaje })
                });

                const data = await res.json();
                ocultarLoader();

                if (data.success) {
                    agregarAlHistorial('enviado', numero, mensaje, data.data?.messageId || 'N/A');
                    limpiarFormulario();
                    actualizarEstadisticas();
                    alert('✅ Mensaje enviado correctamente');
                } else {
                    alert('❌ Error: ' + (data.error || 'No se pudo enviar el mensaje'));
                }
            } catch (error) {
                ocultarLoader();
                alert('❌ Error: ' + error.message);
            }
        }

        // Agregar al historial
        function agregarAlHistorial(tipo, numero, mensaje, id) {
            const item = {
                tipo,
                numero,
                mensaje: mensaje.substring(0, 50) + (mensaje.length > 50 ? '...' : ''),
                id,
                timestamp: new Date().toLocaleTimeString('es-ES')
            };

            messageHistory.unshift(item);

            const historyDiv = document.getElementById('chat-history');
            historyDiv.innerHTML = messageHistory.map((m, i) => `
                <div class="message-bubble ${m.tipo === 'enviado' ? 'bg-green-500/10 border-green-400' : 'bg-blue-500/10 border-blue-400'} rounded-lg p-4 border">
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-semibold text-white">${m.tipo === 'enviado' ? '📤' : '📥'} ${m.numero}</span>
                        <span class="text-xs text-gray-400">${m.timestamp}</span>
                    </div>
                    <p class="text-gray-300 text-sm">"${m.mensaje}"</p>
                    <span class="text-xs text-gray-500 mt-2 block">ID: ${m.id}</span>
                </div>
            `).join('');
        }

        // Reconectar
        async function reconectar() {
            try {
                const res = await fetch(`${BAILEYS_URL}/reconnect`, { method: 'POST' });
                const data = await res.json();
                alert('🔄 Reconectando... Escanea el nuevo QR');
                verificarEstado();
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }

        // Desconectar
        async function desconectar() {
            if (confirm('¿Estás seguro de que deseas desconectar?')) {
                try {
                    const res = await fetch(`${BAILEYS_URL}/disconnect`, { method: 'POST' });
                    const data = await res.json();
                    alert('✅ Desconectado');
                    verificarEstado();
                } catch (error) {
                    alert('❌ Error: ' + error.message);
                }
            }
        }

        // Limpiar formulario
        function limpiarFormulario() {
            document.getElementById('numero-destino').value = '';
            document.getElementById('mensaje-texto').value = '';
            document.getElementById('archivo-mensaje').value = '';
            document.getElementById('contador-chars').textContent = '0';
        }

        // Actualizar estadísticas
        async function actualizarEstadisticas() {
            try {
                const res = await fetch(`${BAILEYS_URL}/stats`);
                const data = await res.json();
                document.getElementById('stat-sent').textContent = data.sent;
                document.getElementById('stat-received').textContent = data.received;
            } catch (error) {
                console.error('Error obteniendo estadísticas:', error);
            }
        }

        // Mostrar loader
        function mostrarLoader() {
            document.getElementById('loader').style.display = 'flex';
        }

        // Ocultar loader
        function ocultarLoader() {
            document.getElementById('loader').style.display = 'none';
        }
    </script>
</body>
</html>
