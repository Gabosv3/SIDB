# 📱 Baileys WhatsApp Service para SIDB

Servidor Node.js que integra Baileys para conectar WhatsApp de forma personal (sin costos de Meta Cloud API).

## 🚀 Instalación

### 1. Instalar dependencias
```bash
cd baileys-service
npm install
```

### 2. Configurar variables de entorno
```bash
cp .env.example .env
```

Edita `.env`:
```env
BAILEYS_PORT=3333
LARAVEL_API_URL=http://localhost:8000
LARAVEL_TOKEN=tu_token_api
```

### 3. Ejecutar servidor
```bash
# Producción
npm start

# Desarrollo con reload automático
npm run dev
```

## 📱 Conectar WhatsApp

1. Inicia el servidor
2. Abre tu navegador: `http://localhost:3333/qrcode`
3. Escanea el código QR con tu WhatsApp personal
4. ¡Listo! El servidor estará conectado

## 🔌 API Endpoints

### `GET /status`
Estado de conexión actual
```json
{
  "connected": true,
  "qrCodePending": false,
  "timestamp": "2026-07-06T10:30:00Z"
}
```

### `GET /qrcode`
Obtiene el código QR actual (si está pendiente de conectar)
```json
{
  "qr": "..."
}
```

### `POST /send`
Enviar un mensaje de texto
```json
{
  "to": "573001234567",
  "message": "Hola, tu cuota vence mañana"
}
```

**Respuesta:**
```json
{
  "success": true,
  "messageId": "ABC123",
  "timestamp": "2026-07-06T10:30:00Z"
}
```

### `POST /send-template`
Enviar mensaje usando plantillas
```json
{
  "to": "573001234567",
  "template": "recordatorio_cuota",
  "params": {
    "nombre": "Juan",
    "monto": "$50.000",
    "fecha_vencimiento": "06/07/2026"
  }
}
```

### `GET /info`
Información de la cuenta WhatsApp conectada
```json
{
  "jid": "573001234567@s.whatsapp.net",
  "name": "Distribuidora Briancesco",
  "connected": true
}
```

### `POST /disconnect`
Desconectar WhatsApp
```json
{
  "success": true,
  "message": "Desconectado"
}
```

### `POST /reconnect`
Reconectar (generar nuevo QR)
```json
{
  "success": true,
  "message": "Reconectando..."
}
```

## 🔑 Integración con Laravel

Laravel hará peticiones HTTP a este servidor para enviar mensajes.

Ejemplo en un controlador de Laravel:
```php
use Illuminate\Support\Facades\Http;

$response = Http::post('http://localhost:3333/send', [
    'to' => $cliente->telefono_whatsapp,
    'message' => 'Hola, tu saldo es de $500.000'
]);

if ($response->ok()) {
    $messageId = $response['messageId'];
}
```

## 📁 Estructura de directorios

```
baileys-service/
├── server.js           # Servidor principal
├── package.json        # Dependencias
├── .env.example        # Variables de entorno
├── README.md           # Este archivo
└── auth_info/          # Credenciales de WhatsApp (auto-generado)
    ├── creds.json
    └── pre-key-*.json
```

## ⚙️ Configuración en Docker (opcional)

Si deseas correr en Docker:

```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3333
CMD ["node", "server.js"]
```

## 🛠️ Troubleshooting

### El QR no aparece
```bash
# Reinicia el servidor
npm run dev
```

### Conexión desconectada constantemente
- Asegúrate de tener una conexión a internet estable
- Verifica que WhatsApp Web no esté abierto en otro navegador

### Laravel no puede conectar
- Verifica que el servidor Baileys esté escuchando en `localhost:3333`
- Comprueba la variable `LARAVEL_API_URL` en `.env`

## 📝 Notas

- ✅ **Gratis**: No hay costos de Meta Cloud API
- ✅ **Personal**: Usa tu número de WhatsApp personal
- ✅ **Rápido**: Latencia mínima
- ⚠️ **Limitaciones de Meta**: Meta puede bloquear la cuenta si envías demasiados mensajes rápido
- ⚠️ **Monitoreo**: Se recomienda implementar rate limiting y logging

## 📞 Soporte

Para problemas con Baileys, consulta: https://github.com/WhiskeySockets/Baileys
