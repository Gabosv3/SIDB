import express from 'express';
import dotenv from 'dotenv';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import makeWASocket, { useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion } from '@whiskeysockets/baileys';
import pino from 'pino';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.BAILEYS_PORT || 3333;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000';
const DEFAULT_COUNTRY_CODE = process.env.BAILEYS_DEFAULT_COUNTRY_CODE || '503';
const AUTH_BASE_DIR = path.join(__dirname, 'auth_info_baileys');

// Logging
const logger = pino();

// Map<sessionId, { socket, qr, connected, accountInfo, starting }> — una entrada por
// vendedor/cobrador (sessionId = "user_{id}"), en vez de una sola sesión global.
const sessions = new Map();

function getSession(sessionId) {
    if (!sessions.has(sessionId)) {
        sessions.set(sessionId, { socket: null, qr: null, connected: false, accountInfo: null, starting: false });
    }
    return sessions.get(sessionId);
}

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

console.log('🚀 Iniciando Baileys WhatsApp Multi-Sesión...');
console.log('📱 Puerto: ' + PORT);

function normalizeJid(to) {
    let jid = to.replace(/\D/g, '');
    if (!jid.startsWith(DEFAULT_COUNTRY_CODE)) jid = DEFAULT_COUNTRY_CODE + jid;
    return jid + '@s.whatsapp.net';
}

// ── INICIALIZAR UNA SESIÓN DE BAILEYS ──
async function startSession(sessionId) {
    const session = getSession(sessionId);
    if (session.starting) return;
    session.starting = true;

    try {
        const authDir = path.join(AUTH_BASE_DIR, sessionId);
        const { state, saveCreds } = await useMultiFileAuthState(authDir);
        const { version } = await fetchLatestBaileysVersion();

        const socket = makeWASocket({
            auth: state,
            version,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }),
            syncFullHistory: false,
            browser: ['Ubuntu', 'Chrome', '120'],
        });

        session.socket = socket;

        // QR Code / estado de conexión
        socket.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                session.qr = qr;
                console.log(`📲 [${sessionId}] QR generado - Escanea con tu WhatsApp`);
            }

            if (connection === 'open') {
                session.connected = true;
                session.qr = null;
                session.accountInfo = socket.user;
                console.log(`✅ [${sessionId}] CONECTADO A WHATSAPP`);
                console.log(`📞 [${sessionId}] Número:`, socket.user?.id);
            }

            if (connection === 'close') {
                session.connected = false;
                console.log(`❌ [${sessionId}] Desconectado`, lastDisconnect?.error?.output?.statusCode, lastDisconnect?.error?.message);
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
                session.starting = false;
                if (shouldReconnect) {
                    console.log(`🔄 [${sessionId}] Reconectando...`);
                    setTimeout(() => startSession(sessionId), 3000);
                }
            }
        });

        // Guardar credenciales (en la carpeta propia de esta sesión)
        socket.ev.on('creds.update', saveCreds);

        // Procesar mensajes entrantes
        socket.ev.on('messages.upsert', async (m) => {
            if (m.type === 'notify') {
                for (const msg of m.messages) {
                    if (msg.key.fromMe) continue;

                    const message = msg.message?.conversation || msg.message?.extendedTextMessage?.text;
                    if (!message) continue;

                    console.log(`💬 [${sessionId}] Mensaje recibido de`, msg.key.remoteJid, ':', message);

                    try {
                        await fetch(`${LARAVEL_API_URL}/api/whatsapp/baileys/webhook`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                sessionId,
                                from: msg.key.remoteJid,
                                message: message,
                                timestamp: msg.messageTimestamp,
                                messageId: msg.key.id,
                            }),
                        });
                    } catch (e) {
                        console.error(`Error enviando a Laravel [${sessionId}]:`, e.message);
                    }
                }
            }
        });

        session.starting = false;
    } catch (error) {
        session.starting = false;
        console.error(`❌ [${sessionId}] Error inicializando Baileys:`, error);
        console.log('💡 Consejo: Si estás en Windows, intenta correr esto en WSL2, Docker o Linux');
        setTimeout(() => startSession(sessionId), 5000);
    }
}

// ── Reconectar automáticamente las sesiones ya vinculadas al arrancar el proceso ──
function reconectarSesionesExistentes() {
    if (!fs.existsSync(AUTH_BASE_DIR)) return;

    const carpetas = fs.readdirSync(AUTH_BASE_DIR, { withFileTypes: true })
        .filter((d) => d.isDirectory())
        .map((d) => d.name);

    carpetas.forEach((sessionId) => {
        console.log(`🔁 Reconectando sesión existente: ${sessionId}`);
        startSession(sessionId);
    });
}

// ── ENDPOINTS ──

app.post('/sessions/:sessionId/connect', (req, res) => {
    const { sessionId } = req.params;
    const session = getSession(sessionId);

    if (session.connected) {
        return res.json({ success: true, message: 'Ya conectado', connected: true });
    }

    startSession(sessionId);
    res.json({ success: true, message: 'Iniciando sesión...' });
});

app.get('/sessions/:sessionId/status', (req, res) => {
    const session = getSession(req.params.sessionId);
    res.json({
        connected: session.connected,
        qrCodePending: !session.connected && !!session.qr,
        qr: session.qr ? session.qr : null,
        timestamp: new Date(),
        mode: 'REAL',
    });
});

app.get('/sessions/:sessionId/qrcode', async (req, res) => {
    const session = getSession(req.params.sessionId);

    if (session.connected) {
        return res.status(404).json({ error: 'QR no disponible - Ya conectado' });
    }

    if (!session.qr) {
        return res.status(202).json({
            message: 'QR no disponible aún, esperando...',
            data: null
        });
    }

    try {
        const QRCode = (await import('qrcode')).default;
        const qrImage = await QRCode.toDataURL(session.qr, {
            errorCorrectionLevel: 'H',
            type: 'image/png',
            quality: 0.95,
            margin: 1,
            width: 300,
        });

        console.log(`📲 [${req.params.sessionId}] QR enviado al cliente`);

        res.json({
            qr: qrImage,
            data: session.qr,
            mode: 'REAL - Conexión auténtica',
            type: 'data_url',
            instructions: 'Escanea este código QR con tu teléfono para conectar tu WhatsApp',
        });
    } catch (error) {
        console.error('Error generando QR:', error);
        res.status(500).json({ error: 'Error generando código QR' });
    }
});

app.get('/sessions/:sessionId/info', (req, res) => {
    const session = getSession(req.params.sessionId);

    if (!session.connected) {
        return res.status(503).json({ error: 'No conectado' });
    }

    res.json({
        jid: session.socket?.user?.id,
        name: session.socket?.user?.name,
        connected: session.connected,
        timestamp: new Date(),
        mode: 'REAL',
    });
});

app.post('/sessions/:sessionId/send', async (req, res) => {
    const session = getSession(req.params.sessionId);

    if (!session.connected) {
        return res.status(503).json({ success: false, error: 'No conectado a WhatsApp' });
    }

    const { to, message } = req.body;

    if (!to || !message) {
        return res.status(400).json({ error: 'Parámetros requeridos: to, message' });
    }

    try {
        const jid = normalizeJid(to);
        const result = await session.socket.sendMessage(jid, { text: message });

        console.log(`✅ [${req.params.sessionId}] Mensaje enviado a ${to}: ${message.substring(0, 50)}`);

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

app.post('/sessions/:sessionId/send-template', async (req, res) => {
    const session = getSession(req.params.sessionId);

    if (!session.connected) {
        return res.status(503).json({ success: false, error: 'No conectado' });
    }

    const { to, template, params } = req.body;

    try {
        const message = template
            .replace(/{(\d+)}/g, (match, num) => params[num] || match);

        const jid = normalizeJid(to);
        const result = await session.socket.sendMessage(jid, { text: message });

        console.log(`✅ [${req.params.sessionId}] Template enviado a ${to}`);

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

app.post('/sessions/:sessionId/disconnect', (req, res) => {
    const session = getSession(req.params.sessionId);
    if (session.socket) {
        session.socket.end();
        session.connected = false;
        session.qr = null;
        console.log(`🚫 [${req.params.sessionId}] Desconectado manualmente`);
    }
    res.json({ success: true, message: 'Desconectado' });
});

app.post('/sessions/:sessionId/reconnect', (req, res) => {
    const { sessionId } = req.params;
    const session = getSession(sessionId);

    session.qr = null;
    session.connected = false;
    if (session.socket) {
        session.socket.end();
    }

    console.log(`🔄 [${sessionId}] Reconectando...`);

    session.starting = false;
    setTimeout(() => {
        startSession(sessionId);
    }, 1000);

    res.json({ success: true, message: 'Reconectando...' });
});

// ── INICIAR SERVIDOR ──
app.listen(PORT, () => {
    console.log(`\n🎉 Servidor Baileys (multi-sesión) corriendo en puerto ${PORT}`);
    console.log(`📱 URL: http://localhost:${PORT}\n`);
    reconectarSesionesExistentes();
});

process.on('uncaughtException', (error) => {
    console.error('❌ Error no capturado:', error);
});
