## 🔌 Integración Baileys con Laravel

### 1. Configurar variables de entorno

Agrega al `.env` de Laravel:

```env
# Baileys WhatsApp Service
BAILEYS_URL=http://localhost:3333
BAILEYS_TOKEN=tu_token_opcional
```

### 2. Usar en controladores

```php
<?php

namespace App\Http\Controllers;

use App\Services\BaileysWhatsAppService;
use Illuminate\Http\Request;

class CobroController extends Controller
{
    public function __construct(protected BaileysWhatsAppService $baileys) {}

    /**
     * Enviar recordatorio de pago
     */
    public function enviarRecordatorio($clienteId)
    {
        $cliente = Cliente::findOrFail($clienteId);

        // Enviar mensaje simple
        $resultado = $this->baileys->sendMessage(
            $cliente->telefono_whatsapp,
            "Hola {$cliente->nombre}, le recordamos que tiene una cuota pendiente. Por favor comuníquese con nosotros."
        );

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'messageId' => $resultado['messageId'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $resultado['error'],
        ], 400);
    }

    /**
     * Enviar recordatorio usando plantilla
     */
    public function enviarRecordatorioPorTemplate($clienteId)
    {
        $cliente = Cliente::findOrFail($clienteId);
        $cuota = $cliente->gestionesCobro()
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->first();

        if (!$cuota) {
            return response()->json(['error' => 'Sin cuotas pendientes'], 404);
        }

        $resultado = $this->baileys->sendTemplate(
            $cliente->telefono_whatsapp,
            'recordatorio_cuota',
            [
                'nombre' => $cliente->nombre,
                'monto' => number_format($cuota->monto_cuota, 2),
                'fecha_vencimiento' => $cuota->fecha_vencimiento->format('d/m/Y'),
            ]
        );

        return response()->json($resultado);
    }
}
```

### 3. Usar en Jobs/Queue

```php
<?php

namespace App\Jobs;

use App\Models\GestionCobro;
use App\Services\BaileysWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarRecordatorioWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(protected GestionCobro $gestion) {}

    public function handle(BaileysWhatsAppService $baileys): void
    {
        $cliente = $this->gestion->cliente;
        
        if (!$cliente->telefono_whatsapp) {
            return;
        }

        $mensaje = "Hola {$cliente->nombre}, le recordamos que su cuota #{$this->gestion->numero_cuota} vence el {$this->gestion->fecha_vencimiento->format('d/m/Y')}. Por favor esté atento. Distribuidora Briancesco Menjivar.";

        $baileys->sendMessage($cliente->telefono_whatsapp, $mensaje);
    }
}
```

### 4. Endpoints disponibles

#### Enviar mensaje
```bash
POST /api/whatsapp/baileys/send
Content-Type: application/json

{
    "to": "573001234567",
    "message": "Hola, tu saldo es de $500.000"
}
```

#### Obtener estado
```bash
GET /api/whatsapp/baileys/status
```

#### Obtener código QR
```bash
GET /api/whatsapp/baileys/qrcode
```

#### Información de la cuenta
```bash
GET /api/whatsapp/baileys/info
```

### 5. Webhook - Recibir mensajes

El servidor Baileys enviará mensajes a:
```
POST /api/whatsapp/baileys/webhook
```

Que automáticamente:
- Busca/crea el cliente por número de WhatsApp
- Abre/crea la conversación
- Guarda el mensaje en la base de datos
- Actualiza el estado de la conversación

### ⚙️ Migración desde Meta Cloud API

Si vas a migrar de Meta Cloud API a Baileys:

1. **Desactiva las funciones de Meta**
   ```php
   // Ya desactivado en routes/web.php y controladores
   ```

2. **Usa Baileys en lugar de Meta**
   ```php
   // Antes (Meta)
   $resultado = $this->whatsapp->sendTextMessage($cuenta, $numero, $mensaje);

   // Ahora (Baileys)
   $resultado = $this->baileys->sendMessage($numero, $mensaje);
   ```

3. **Los datos se guardan igual**
   - Las conversaciones se almacenan en `whatsapp_conversations`
   - Los mensajes en `whatsapp_messages`
   - Compatible con toda la arquitectura existente

### 📊 Monitoreo

Para monitorear el estado de Baileys:

```php
use App\Services\BaileysWhatsAppService;

$baileys = app(BaileysWhatsAppService::class);

if (!$baileys->isAvailable()) {
    Log::alert('⚠️ Servidor Baileys no disponible');
}

$status = $baileys->getStatus();
if ($status['connected'] ?? false) {
    Log::info('✅ WhatsApp conectado');
} else {
    Log::warning('⚠️ WhatsApp desconectado');
}
```

### 🚀 Deploy

Para producción:

1. Ejecuta Baileys en un servidor separado (puede ser el mismo)
2. Configura supervisord/systemd para que siempre esté corriendo
3. Usa `BAILEYS_URL` apuntando a la URL pública del servidor

```bash
# supervisord
[program:baileys]
command=node /ruta/a/baileys-service/server.js
autostart=true
autorestart=true
stderr_logfile=/var/log/baileys.err.log
stdout_logfile=/var/log/baileys.out.log
```

### ⚠️ Limitaciones y advertencias

- **Rate limiting**: Meta puede bloquear cuentas que envíen demasiados mensajes muy rápido
- **Términos de servicio**: Asegúrate de cumplir con los TOS de WhatsApp
- **Estabilidad**: Baileys depende del protocolo de WhatsApp (que puede cambiar)
- **Números reales**: Usa un número de WhatsApp real, no simulado

### 🔐 Seguridad

- El webhook de Baileys recibe datos sin autenticación (configurable)
- Considera agregar validación IP o token Bearer
- Las credenciales de WhatsApp se guardan en `baileys-service/auth_info/`

```php
// En BaileysWebhookController@receive
// Valida que la petición viene del servidor Baileys
if ($request->getClientIp() !== env('BAILEYS_ALLOWED_IP')) {
    abort(403);
}
```

---

**¿Preguntas?** Revisa el README.md en `baileys-service/`
