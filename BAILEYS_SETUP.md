# 📋 Guía de Instalación y Ejecución - Baileys WhatsApp

## 🚀 Setup Rápido

### Paso 1: Instalar dependencias de Node.js

```bash
cd baileys-service
npm install
```

Esto instalará:
- `@whiskeysockets/baileys` - Cliente WhatsApp
- `express` - Servidor web
- `axios` - Cliente HTTP
- `qrcode-terminal` - Mostrar QR en terminal
- `dotenv` - Variables de entorno
- `pino` - Logger

### Paso 2: Configurar variables de entorno

```bash
# En la carpeta baileys-service/
cp .env.example .env
```

Edita `baileys-service/.env`:
```env
BAILEYS_PORT=3333
LARAVEL_API_URL=http://localhost:8000
LARAVEL_TOKEN=tu_token_opcional
```

### Paso 3: Conectar WhatsApp

**Opción A: Desarrollo (Ver QR en terminal)**

```bash
npm run dev
```

Verás algo como:
```
🚀 Servidor Baileys escuchando en puerto 3333
📱 Escanea este código QR con tu WhatsApp:
[QR CODE AQUÍ]
```

Abre tu WhatsApp en el teléfono y escanea el código.

**Opción B: Producción (Ver QR por web)**

```bash
npm start
```

Luego abre: `http://localhost:3333/qrcode`

### Paso 4: Verificar conexión

Una vez conectado, verás:
```
✅ WhatsApp conectado exitosamente
```

## 📱 Flujo de Mensajes

```
Cliente (WhatsApp)
    ↓ Envía mensaje
Servidor Baileys (Node.js)
    ↓ Webhook
Laravel API
    ↓ Procesa y guarda
Base de datos
    ↓ Muestra en conversación
Centro Cobrador
```

## 🔌 Endpoints para probar

### 1. Status de conexión
```bash
curl http://localhost:3333/status
```

Respuesta:
```json
{
  "connected": true,
  "qrCodePending": false,
  "timestamp": "2026-07-06T10:30:00Z"
}
```

### 2. Enviar mensaje
```bash
curl -X POST http://localhost:3333/send \
  -H "Content-Type: application/json" \
  -d '{
    "to": "573001234567",
    "message": "Hola de prueba"
  }'
```

### 3. Información de la cuenta
```bash
curl http://localhost:3333/info
```

## 🛠️ Troubleshooting

### ❌ "Connection refused"
El servidor Baileys no está corriendo.
```bash
npm start
```

### ❌ El QR no aparece
- Reinicia: `npm run dev`
- Cierra otros navegadores con WhatsApp Web abierto
- Abre `http://localhost:3333/qrcode`

### ❌ Mensajes no llegan a Laravel
1. Verifica que Laravel esté corriendo: `php artisan serve`
2. Comprueba la URL en `.env`: `LARAVEL_API_URL=http://localhost:8000`
3. Revisa los logs: `storage/logs/laravel.log`

### ❌ "DisconnectReason.loggedOut"
La sesión expiró o se cerró en otro navegador.
```bash
rm -rf auth_info/*  # Windows: rmdir auth_info
npm start  # Vuelve a conectar
```

### ❌ Demasiados mensajes muy rápido
Meta podría bloquear la cuenta. Implementa:
```js
// En server.js - Rate limiting
const sendQueue = [];
const DELAY_MS = 1000; // 1 segundo entre mensajes

async function sendWithDelay(jid, message) {
    await new Promise(r => setTimeout(r, DELAY_MS));
    return await sock.sendMessage(jid, { text: message });
}
```

## 📊 Logs y Debug

### Ver logs en tiempo real (Desarrollo)
```bash
npm run dev
```

### Guardar logs a archivo
Edita `server.js`:
```js
import fs from 'fs';

const logStream = fs.createWriteStream('baileys.log', { flags: 'a' });
```

### Debug con pino
```bash
DEBUG=baileys* npm start
```

## 🔄 Administración

### Desconectar WhatsApp
```bash
curl -X POST http://localhost:3333/disconnect
```

### Reconectar (generar nuevo QR)
```bash
curl -X POST http://localhost:3333/reconnect
```

### Ver credenciales guardadas
```bash
ls -la auth_info/
# En Windows:
dir auth_info/
```

## 🐳 Ejecutar en Docker (Opcional)

### Dockerfile
```dockerfile
FROM node:18-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .

EXPOSE 3333
CMD ["node", "server.js"]
```

### Build y run
```bash
docker build -t baileys-service .
docker run -p 3333:3333 \
  -e BAILEYS_PORT=3333 \
  -e LARAVEL_API_URL=http://host.docker.internal:8000 \
  -v $(pwd)/auth_info:/app/auth_info \
  baileys-service
```

## 🌐 Variables de entorno

| Variable | Default | Descripción |
|----------|---------|-------------|
| `BAILEYS_PORT` | 3333 | Puerto donde corre el servidor |
| `LARAVEL_API_URL` | http://localhost:8000 | URL de API Laravel |
| `LARAVEL_TOKEN` | - | Token para autenticar con Laravel |

## 📈 Performance

### Recomendaciones para producción

1. **Usa PM2** (Process Manager)
```bash
npm install -g pm2
pm2 start server.js --name "baileys"
pm2 save
pm2 startup
```

2. **Monitorea recursos**
```bash
pm2 monit
```

3. **Proxying reverso** (nginx)
```nginx
location /api/baileys/ {
    proxy_pass http://localhost:3333/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

## ✅ Checklist de Deploy

- [ ] `npm install` completado
- [ ] `.env` configurado
- [ ] WhatsApp conectado (QR escaneado)
- [ ] `npm start` sin errores
- [ ] Laravel está corriendo en `LARAVEL_API_URL`
- [ ] Webhook recibe mensajes (`storage/logs/laravel.log`)
- [ ] PM2/supervisord configurado para autostart
- [ ] Firewall permite puerto 3333

## 📞 Soporte

- **Baileys GitHub**: https://github.com/WhiskeySockets/Baileys
- **Problemas comunes**: https://github.com/WhiskeySockets/Baileys/discussions
- **Issues de SIDB**: Ver proyecto en GitHub

---

**¿Todo listo?** Prueba enviar tu primer mensaje desde Laravel:

```bash
php artisan tinker
> app(\App\Services\BaileysWhatsAppService::class)->sendMessage('573001234567', 'Hola de prueba')
```
