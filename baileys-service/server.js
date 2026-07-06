import express from 'express';
import axios from 'axios';
import dotenv from 'dotenv';
import { makeWASocket, useMultiFileAuthState, DisconnectReason } from '@whiskeysockets/baileys';
import pino from 'pino';
import path from 'path';
import { fileURLToPath } from 'url';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.BAILEYS_PORT || 3333;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000';
const logger = pino({ level: 'silent' }); // Silenciar pino logs

let sock = null;
let qrCodeData = null;
let isConnected = false;
let connectionAttempts = 0;
const MAX_ATTEMPTS = 5;

app.use(express.json());

// ────────────────────────────────────────────────────────────────────────────────
// INICIALIZAR CONEXIÓN BAILEYS
// ────────────────────────────────────────────────────────────────────────────────

async function connectWhatsApp() {
    try {
        connectionAttempts++;
        if (connectionAttempts > MAX_ATTEMPTS && !isConnected) {
            console.log('⚠️ Múltiples intentos fallidos. Esperando...');
            setTimeout(connectWhatsApp, 10000);
            return;
        }

        const authPath = path.join(__dirname, './auth_info');
        const { state, saveCreds } = await useMultiFileAuthState(authPath);

        sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            logger: logger,
            browser: ['SIDB - Cobrador', 'Chrome', '1.0.0'],
            generateHighQualityLinkPreview: false,
            shouldSyncHistoryMessage: false,
        });

        // QR Code
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCodeData = qr;
                console.log('📱 Nuevo código QR generado');
            }

            if (connection === 'open') {
                isConnected = true;
                connectionAttempts = 0;
                console.log('✅ WhatsApp conectado exitosamente a las ' + new Date().toLocaleTimeString());
            } else if (connection === 'close') {
                isConnected = false;
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                
                if (statusCode === DisconnectReason.loggedOut) {
                    console.log('🚪 Cierre de sesión. Necesita nueva conexión.');
                    qrCodeData = null;
                } else if (statusCode === DisconnectReason.connectionClosed) {
                    console.log('⚠️ Conexión cerrada');
                } else {
                    console.log('⏳ Reconectando... (' + connectionAttempts + '/' + MAX_ATTEMPTS + ')');
                    setTimeout(connectWhatsApp, 5000);
                }
            }
        });

        // Guardar credenciales
        sock.ev.on('creds.update', saveCreds);

        // ────────────────────────────────────────────────────────────────────────────
        // RECIBIR MENSAJES Y ENVIAR A LARAVEL
        // ────────────────────────────────────────────────────────────────────────────

        sock.ev.on('messages.upsert', async (m) => {
            if (m.type !== 'notify') return;

            for (const msg of m.messages) {
                if (msg.key.fromMe) continue;

                const sender = msg.key.remoteJid;
                const text = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';

                if (!text || text.trim() === '') continue;

                console.log(`📨 Mensaje de ${sender}: ${text.substring(0, 50)}...`);

                try {
                    await axios.post(`${LARAVEL_API_URL}/api/whatsapp/baileys/webhook`, {
                        from: sender,
                        message: text,
                        timestamp: msg.messageTimestamp,
                        messageId: msg.key.id,
                    }, {
                        headers: { 'Authorization': `Bearer ${process.env.LARAVEL_TOKEN}` },
                        timeout: 5000,
                    });
                } catch (error) {
                    console.error('❌ Error enviando a Laravel:', error.message?.substring(0, 50));
                }
            }
        });

    } catch (error) {
        console.error('❌ Error conectando:', error.message?.substring(0, 50));
        setTimeout(connectWhatsApp, 5000);
    }
}

// ────────────────────────────────────────────────────────────────────────────────
// ENDPOINTS API
// ────────────────────────────────────────────────────────────────────────────────

app.get('/status', (req, res) => {
    res.json({
        connected: isConnected,
        qrCodePending: qrCodeData ? true : false,
        timestamp: new Date(),
    });
});

app.get('/qrcode', (req, res) => {
    if (!qrCodeData) {
        return res.status(404).json({ error: 'QR no disponible' });
    }
    res.json({ qr: qrCodeData });
});

app.post('/send', async (req, res) => {
    try {
        if (!isConnected) {
            return res.status(503).json({ error: 'WhatsApp no conectado' });
        }

        const { to, message } = req.body;

        if (!to || !message) {
            return res.status(400).json({ error: 'Parámetros requeridos: to, message' });
        }

        const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;

        const result = await sock.sendMessage(jid, { text: message });

        res.json({
            success: true,
            messageId: result.key.id,
            timestamp: new Date(),
        });

    } catch (error) {
        console.error('❌ Error enviando:', error.message?.substring(0, 50));
        res.status(500).json({ error: error.message });
    }
});

app.post('/send-template', async (req, res) => {
    try {
        if (!isConnected) {
            return res.status(503).json({ error: 'WhatsApp no conectado' });
        }

        const { to, template, params = {} } = req.body;

        if (!to || !template) {
            return res.status(400).json({ error: 'Parámetros requeridos: to, template' });
        }

        const templates = {
            recordatorio_cuota: `Hola {nombre}, le recordamos que tiene una cuota pendiente por ${params.monto}. Vencimiento: {fecha_vencimiento}. Distribuidora Briancesco Menjivar.`,
            saldo_pendiente: `Hola {nombre}, su saldo pendiente es ${params.saldo}. Distribuidora Briancesco Menjivar.`,
            confirmar_pago: `¡Hola {nombre}! Confirmamos recepción de su pago. Gracias por estar al día. Distribuidora Briancesco Menjivar.`,
        };

        let message = templates[template] || '';
        Object.keys(params).forEach(key => {
            message = message.replace(`{${key}}`, params[key]);
        });

        const jid = to.includes('@') ? to : `${to}@s.whatsapp.net`;
        const result = await sock.sendMessage(jid, { text: message });

        res.json({
            success: true,
            messageId: result.key.id,
            message: message,
            timestamp: new Date(),
        });

    } catch (error) {
        console.error('❌ Error enviando template:', error.message?.substring(0, 50));
        res.status(500).json({ error: error.message });
    }
});

app.get('/info', async (req, res) => {
    try {
        if (!isConnected || !sock) {
            return res.status(503).json({ error: 'WhatsApp no conectado' });
        }

        const user = sock.user;
        res.json({
            jid: user?.id,
            name: user?.name,
            connected: isConnected,
            timestamp: new Date(),
        });

    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.post('/disconnect', async (req, res) => {
    try {
        if (sock) {
            await sock.logout();
            isConnected = false;
            sock = null;
            qrCodeData = null;
            connectionAttempts = 0;
            res.json({ success: true, message: 'Desconectado' });
        } else {
            res.status(400).json({ error: 'No hay conexión activa' });
        }
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.post('/reconnect', async (req, res) => {
    try {
        if (sock) {
            await sock.logout();
        }
        qrCodeData = null;
        isConnected = false;
        connectionAttempts = 0;
        sock = null;
        
        setTimeout(connectWhatsApp, 1000);
        res.json({ success: true, message: 'Reconectando...' });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// ────────────────────────────────────────────────────────────────────────────────
// INICIAR SERVIDOR
// ────────────────────────────────────────────────────────────────────────────────

app.listen(PORT, () => {
    console.log(`🚀 Servidor Baileys escuchando en puerto ${PORT}`);
    console.log(`📡 API Laravel: ${LARAVEL_API_URL}`);
    connectWhatsApp();
});
