import express from 'express';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import makeWASocket, { useMultiFileAuthState, DisconnectReason } from '@whiskeysockets/baileys';
import pino from 'pino';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.BAILEYS_PORT || 3333;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000';

// Logging
const logger = pino();

// Variables globales
let socket = null;
let qrCode = null;
let isConnected = false;
let accountInfo = null;

app.use(express.json());

// CORS Middleware
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

console.log('🚀 Iniciando Baileys WhatsApp Real Connection...');
console.log('📱 Puerto: ' + PORT);

// ── INICIALIZAR BAILEYS ──
async function startBaileys() {
    try {
        const { state, saveCreds } = await useMultiFileAuthState('auth_info_baileys');

        socket = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }),
            syncFullHistory: false,
            browser: ['Ubuntu', 'Chrome', '120'],
        });

        // QR Code
        socket.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCode = qr;
                console.log('📲 QR Generado - Escanea con tu WhatsApp');
            }

            if (connection === 'open') {
                isConnected = true;
                accountInfo = socket.user;
                console.log('✅ CONECTADO A WHATSAPP');
                console.log('📞 Número:', socket.user.id);
            }

            if (connection === 'close') {
                isConnected = false;
                console.log('❌ Desconectado');
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
                if (shouldReconnect) {
                    console.log('🔄 Reconectando...');
                    setTimeout(() => startBaileys(), 3000);
                }
            }
        });

        // Guardar credenciales
        socket.ev.on('creds.update', saveCreds);

        // Procesar mensajes entrantes
        socket.ev.on('messages.upsert', async (m) => {
            if (m.type === 'notify') {
                for (const msg of m.messages) {
                    const message = msg.message?.conversation || msg.message?.extendedTextMessage?.text;
                    console.log('💬 Mensaje recibido de', msg.key.remoteJid, ':', message);
                    
                    // Aquí puedes enviar a Laravel si quieres
                    try {
                        await fetch(`${LARAVEL_API_URL}/api/whatsapp/baileys/webhook`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                from: msg.key.remoteJid,
                                message: message,
                                timestamp: msg.messageTimestamp,
                                messageId: msg.key.id,
                            }),
                        });
                    } catch (e) {
                        console.error('Error enviando a Laravel:', e.message);
                    }
                }
            }
        });

    } catch (error) {
        console.error('❌ Error inicializando Baileys:', error);
        console.log('💡 Consejo: Si estás en Windows, intenta correr esto en WSL2, Docker o Linux');
        setTimeout(() => startBaileys(), 5000);
    }
}

// ── ENDPOINTS ──

app.get('/status', (req, res) => {
    res.json({
        connected: isConnected,
        qrCodePending: !isConnected && qrCode,
        qr: qrCode ? qrCode : null,
        timestamp: new Date(),
        mode: 'REAL',
    });
});

app.get('/qrcode', async (req, res) => {
    if (isConnected) {
        return res.status(404).json({ error: 'QR no disponible - Ya conectado' });
    }
    
    if (!qrCode) {
        return res.status(202).json({ 
            message: 'QR no disponible aún, esperando...',
            data: null 
        });
    }

    try {
        const QRCode = (await import('qrcode')).default;
        const qrImage = await QRCode.toDataURL(qrCode, {
            errorCorrectionLevel: 'H',
            type: 'image/png',
            quality: 0.95,
            margin: 1,
            width: 300,
        });

        console.log('📲 QR enviado al cliente');

        res.json({
            qr: qrImage,
            data: qrCode,
            mode: 'REAL - Conexión auténtica',
            type: 'data_url',
            instructions: 'Escanea este código QR con tu teléfono para conectar tu WhatsApp',
        });
    } catch (error) {
        console.error('Error generando QR:', error);
        res.status(500).json({ error: 'Error generando código QR' });
    }
});

app.get('/info', (req, res) => {
    if (!isConnected) {
        return res.status(503).json({ error: 'No conectado' });
    }

    res.json({
        jid: socket.user?.id,
        name: socket.user?.name,
        connected: isConnected,
        timestamp: new Date(),
        mode: 'REAL',
    });
});

app.post('/send', async (req, res) => {
    if (!isConnected) {
        return res.status(503).json({ success: false, error: 'No conectado a WhatsApp' });
    }

    const { to, message } = req.body;

    if (!to || !message) {
        return res.status(400).json({ error: 'Parámetros requeridos: to, message' });
    }

    try {
        // Formatear número
        let jid = to.replace(/\D/g, '');
        if (!jid.startsWith('57')) jid = '57' + jid;
        jid = jid + '@s.whatsapp.net';

        const result = await socket.sendMessage(jid, { text: message });

        console.log(`✅ Mensaje enviado a ${to}: ${message.substring(0, 50)}`);

        res.json({
            success: true,
            messageId: result.key.id,
            timestamp: new Date(),
            mode: 'REAL',
            to: jid,
        });
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        res.status(400).json({
            success: false,
            error: error.message,
        });
    }
});

app.post('/send-template', async (req, res) => {
    if (!isConnected) {
        return res.status(503).json({ success: false, error: 'No conectado' });
    }

    const { to, template, params } = req.body;

    try {
        const message = template
            .replace(/{(\d+)}/g, (match, num) => params[num] || match);

        let jid = to.replace(/\D/g, '');
        if (!jid.startsWith('57')) jid = '57' + jid;
        jid = jid + '@s.whatsapp.net';

        const result = await socket.sendMessage(jid, { text: message });

        console.log(`✅ Template enviado a ${to}`);

        res.json({
            success: true,
            messageId: result.key.id,
            timestamp: new Date(),
            mode: 'REAL',
        });
    } catch (error) {
        console.error('Error enviando template:', error);
        res.status(400).json({ success: false, error: error.message });
    }
});

app.post('/disconnect', (req, res) => {
    if (socket) {
        socket.end();
        isConnected = false;
        qrCode = null;
        console.log('🚫 Desconectado manualmente');
    }
    res.json({ success: true, message: 'Desconectado' });
});

app.post('/reconnect', (req, res) => {
    qrCode = null;
    isConnected = false;
    if (socket) {
        socket.end();
    }
    
    console.log('🔄 Reconectando...');
    
    setTimeout(() => {
        startBaileys();
    }, 1000);

    res.json({ success: true, message: 'Reconectando...' });
});

// ── INICIAR SERVIDOR ──
app.listen(PORT, () => {
    console.log(`\n🎉 Servidor Baileys corriendo en puerto ${PORT}`);
    console.log(`📱 URL: http://localhost:${PORT}\n`);
    startBaileys();
});

process.on('uncaughtException', (error) => {
    console.error('❌ Error no capturado:', error);
});
