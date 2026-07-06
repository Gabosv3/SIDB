import express from 'express';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import QRCode from 'qrcode';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.BAILEYS_PORT || 3333;
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000';

let isConnected = false;
let qrCodeData = 'DEMO_QR_' + Math.random().toString(36).substring(7);
let messageHistory = [];
let stats = {
    sent: 0,
    received: 0,
};

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

console.log('🧪 MODO DEMO - Servidor de Prueba para Desarrollo');
console.log('📡 Servidor demo escuchando en puerto ' + PORT);
console.log('⚠️  Este es un servidor mock para desarrollo - Cuando estés en Hostinger, cambia a server-real.js');

// Endpoints

app.get('/status', (req, res) => {
    res.json({
        connected: isConnected,
        qrCodePending: !isConnected,
        timestamp: new Date(),
        mode: 'DEMO',
    });
});

app.get('/qrcode', async (req, res) => {
    if (isConnected) {
        return res.status(404).json({ error: 'QR no disponible - Ya conectado' });
    }
    
    try {
        // Generar imagen QR PNG
        const qrImage = await QRCode.toDataURL(qrCodeData, {
            errorCorrectionLevel: 'H',
            type: 'image/png',
            quality: 0.95,
            margin: 1,
            width: 300,
        });
        
        console.log('📱 QR generado para datos: ' + qrCodeData);
        
        // Devolver como JSON con data URL para fácil integración
        res.json({ 
            qr: qrImage,
            data: qrCodeData,
            mode: 'DEMO - Modo simulado',
            type: 'data_url'
        });
    } catch (error) {
        console.error('❌ Error generando QR:', error);
        res.status(500).json({ error: 'Error generando código QR' });
    }
});

app.post('/send', (req, res) => {
    const { to, message } = req.body;

    if (!to || !message) {
        return res.status(400).json({ error: 'Parámetros requeridos' });
    }

    const messageId = 'DEMO_MSG_' + Date.now() + Math.random().toString(36).substring(7);
    
    console.log(`📤 [DEMO] Mensaje a ${to}: ${message.substring(0, 50)}`);
    
    // Guardar en historial
    messageHistory.push({
        id: messageId,
        type: 'sent',
        to: to,
        message: message,
        timestamp: new Date().toISOString(),
        status: 'delivered',
    });
    
    stats.sent++;

    res.json({
        success: true,
        messageId: messageId,
        timestamp: new Date(),
        mode: 'DEMO',
        note: 'Este es un mensaje simulado para desarrollo'
    });
});

app.post('/send-template', (req, res) => {
    const { to, template, params } = req.body;

    console.log(`📤 [DEMO] Template ${template} a ${to}`);

    res.json({
        success: true,
        messageId: 'DEMO_TMPL_' + Date.now(),
        message: `Template ${template} simulado`,
        timestamp: new Date(),
        mode: 'DEMO',
    });
});

app.get('/info', (req, res) => {
    res.json({
        jid: 'demo@s.whatsapp.net',
        name: 'DEMO - Modo Desarrollo',
        connected: isConnected,
        timestamp: new Date(),
        mode: 'DEMO',
    });
});

app.post('/disconnect', (req, res) => {
    isConnected = false;
    res.json({ 
        success: true, 
        message: 'Desconectado (demo)',
        mode: 'DEMO'
    });
});

app.post('/reconnect', (req, res) => {
    isConnected = false;
    qrCodeData = 'DEMO_QR_' + Math.random().toString(36).substring(7);
    
    console.log('🔄 Nuevo QR generado (demo)');
    
    res.json({ 
        success: true, 
        message: 'Reconectando... (demo)',
        mode: 'DEMO'
    });
});

// Endpoint para simular un mensaje entrante
app.post('/mock/receive', (req, res) => {
    const { from, message } = req.body;

    if (!from || !message) {
        return res.status(400).json({ error: 'Requerido: from, message' });
    }

    console.log(`📨 [DEMO] Recibido de ${from}: ${message}`);

    // Simular envío a Laravel
    const payload = {
        from: from,
        message: message,
        timestamp: Math.floor(Date.now() / 1000),
        messageId: 'DEMO_MSG_' + Date.now(),
    };

    console.log('📡 Enviando a Laravel:', LARAVEL_API_URL + '/api/whatsapp/baileys/webhook');

    res.json({
        success: true,
        payload: payload,
        note: 'Mensaje simulado enviado a Laravel'
    });
});

// ── ESTADÍSTICAS E HISTORIAL ──

app.get('/stats', (req, res) => {
    res.json({
        sent: stats.sent,
        received: stats.received,
        total: stats.sent + stats.received,
        mode: 'DEMO',
    });
});

app.get('/history', (req, res) => {
    res.json({
        messages: messageHistory.slice(-50), // Últimos 50 mensajes
        total: messageHistory.length,
        mode: 'DEMO',
    });
});

app.post('/clear-history', (req, res) => {
    messageHistory = [];
    stats.sent = 0;
    stats.received = 0;
    res.json({
        success: true,
        message: 'Historial limpiado',
        mode: 'DEMO',
    });
});

app.listen(PORT, () => {
    console.log('\n✅ Servidor DEMO ejecutándose');
    console.log('🔗 URL: http://localhost:' + PORT);
    console.log('\n📚 Endpoints disponibles:');
    console.log('  GET  /status         - Estado actual');
    console.log('  GET  /qrcode         - Código QR');
    console.log('  POST /send           - Enviar mensaje (simulado)');
    console.log('  POST /send-template  - Enviar plantilla (simulada)');
    console.log('  POST /mock/receive   - Simular mensaje entrante');
    console.log('  GET  /stats          - Estadísticas (enviados/recibidos)');
    console.log('  GET  /history        - Historial de mensajes');
    console.log('  POST /clear-history  - Limpiar historial');
    console.log('\n🧪 Ejemplo de uso:');
    console.log(`  curl -X POST http://localhost:${PORT}/mock/receive \\`);
    console.log(`    -H "Content-Type: application/json" \\`);
    console.log(`    -d '{`);
    console.log(`      "from":"573001234567@s.whatsapp.net",`);
    console.log(`      "message":"Hola, esto es una prueba"`);
    console.log(`    }'`);
    console.log('\n⚠️  ESTO ES SOLO PARA DESARROLLO. No conecta a WhatsApp real.');
    console.log('📋 Para producción en Hostinger, cambia a server-real.js\n');
});
