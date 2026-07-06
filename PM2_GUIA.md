# 🚀 Iniciar Baileys con PM2

## Desde la raíz del proyecto

### Opción 1: PM2 (Recomendado para Producción)

```bash
# Iniciar Baileys como servicio
pm2 start ecosystem.config.json

# Ver estado
pm2 status

# Ver logs en tiempo real
pm2 logs baileys

# Monitorear recursos
pm2 monit

# Detener
pm2 stop baileys

# Reiniciar
pm2 restart baileys

# Eliminar
pm2 delete baileys

# Guardar para que inicie con el SO
pm2 save
pm2 startup
```

### Opción 2: Desarrollo (Sin PM2)

```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Baileys
cd baileys-service
npm run dev
```

### Opción 3: Docker

```bash
cd baileys-service
docker build -t baileys .
docker run -p 3333:3333 \
  -e LARAVEL_API_URL=http://host.docker.internal:8000 \
  -v $(pwd)/auth_info:/app/auth_info \
  baileys
```

## ✅ Verificar instalación

```bash
# Verificar que el servidor está corriendo
curl http://localhost:3333/status

# Respuesta esperada:
# {"connected":false,"qrCodePending":true,"timestamp":"2026-07-06T..."}
```

## 📱 Conectar WhatsApp

1. Inicia Baileys: `pm2 start ecosystem.config.json`
2. Abre el QR: `http://localhost:3333/qrcode`
3. Escanea con tu teléfono

## 🎨 Acceder al Centro

Una vez conectado:

- **Filament Dashboard**: http://localhost:8000/admin
- **Centro Baileys**: http://localhost:8000/admin/baileys-center

O directamente:

- **Ruta web**: `/baileys-cobro/status` (verificar estado)
- **Enviar recordatorio**: POST `/baileys-cobro/recordatorio/{cliente_id}`
- **Enviar mensaje**: POST `/baileys-cobro/mensaje/{cliente_id}`

## 📊 Monitoreo

```bash
# Ver todos los procesos
pm2 list

# Ver detalles específico
pm2 info baileys

# Ver logs de error
pm2 logs baileys --err

# Guardar snapshot
pm2 save
```

## 🔧 Comandos útiles

```bash
# Recargar Baileys sin perder conexión
pm2 reload baileys

# Actualizar Node.js
nvm install node
nvm use node

# Verificar versión de Node
node --version
npm --version
pm2 --version
```

## ⚠️ Troubleshooting

### Error: "Cannot find module '@whiskeysockets/baileys'"

```bash
cd baileys-service
npm install
cd ..
```

### Error: "Port 3333 already in use"

```bash
# Windows
netstat -ano | findstr :3333
taskkill /PID <PID> /F

# macOS/Linux
lsof -i :3333
kill -9 <PID>
```

### Baileys se desconecta constantemente

```bash
# Eliminar credenciales y reconectar
rm -rf baileys-service/auth_info/*
pm2 restart baileys
```

### Laravel no recibe webhooks

1. Verifica que Laravel esté en: `LARAVEL_API_URL` del `.env`
2. Revisa logs: `tail -f storage/logs/laravel.log`
3. Prueba webhook manualmente:

```bash
curl -X POST http://localhost:8000/api/whatsapp/baileys/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "from":"573001234567@s.whatsapp.net",
    "message":"test",
    "timestamp":1234567890,
    "messageId":"ABC123"
  }'
```

## 📝 Archivos de configuración

- **PM2**: `ecosystem.config.json`
- **Baileys**: `baileys-service/.env`
- **Laravel**: `.env` (variable `BAILEYS_URL`)

---

**¿Necesitas ayuda?** Revisa:
- [BAILEYS_SETUP.md](BAILEYS_SETUP.md)
- [BAILEYS_INTEGRATION.md](BAILEYS_INTEGRATION.md)
- [baileys-service/README.md](baileys-service/README.md)
