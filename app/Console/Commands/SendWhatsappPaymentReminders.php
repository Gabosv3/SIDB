<?php

namespace App\Console\Commands;

use App\Models\GestionCobro;
use App\Models\WhatsappAccount;
use App\Models\WhatsappAutomation;
use App\Models\WhatsappAutomationLog;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppCloudApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWhatsappPaymentReminders extends Command
{
    protected $signature   = 'whatsapp:send-reminders {--dry-run : Simular sin enviar}';
    protected $description = 'Envía recordatorios de pago automáticos por WhatsApp';

    public function __construct(protected WhatsAppCloudApiService $whatsapp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // ⚠️  DESHABILITADO: Envío de mensajes WhatsApp desactivado (costos en Meta Cloud API)
        // Este comando fue comentado el 2026-07-06 para cambiar a modo solo-lectura
        // 
        // El envío de mensajes incurre en costos significativos en la Meta Cloud API
        // Se mantiene solo la recepción de mensajes (webhook) que es gratuita
        
        $this->warn('⚠️  Comando deshabilitado: El envío automático de recordatorios por WhatsApp está desactivado');
        $this->line('Motivo: Evitar costos en Meta Cloud API');
        $this->line('Estado: Solo lectura de mensajes (webhook) disponible');
        
        return Command::SUCCESS;
        
        /*
        // CÓDIGO ANTERIOR (DESHABILITADO - Mantener para referencia histórica)
        $isDryRun = $this->option('dry-run');
        $this->info($isDryRun ? '🔍 Modo dry-run (sin envíos reales)' : '📤 Enviando recordatorios...');

        // 1. Recordatorio 1 día antes del vencimiento
        $this->procesarTipo('recordatorio_antes_vencimiento', function () {
            return GestionCobro::with(['cliente', 'venta.vendedor'])
                ->whereIn('estado', ['pendiente'])
                ->whereDate('fecha_vencimiento', Carbon::tomorrow())
                ->get();
        }, 'Hola {nombre}, le recordamos que mañana vence su cuota #{cuota} por ${monto}. Por favor esté atento para realizar el pago. Gracias. Distribuidora Briancesco Menjivar.', $isDryRun);

        // 2. Recordatorio el día de vencimiento
        $this->procesarTipo('recordatorio_dia_vencimiento', function () {
            return GestionCobro::with(['cliente', 'venta.vendedor'])
                ->whereIn('estado', ['pendiente'])
                ->whereDate('fecha_vencimiento', Carbon::today())
                ->get();
        }, 'Buenos días {nombre}, hoy vence su cuota #{cuota} por ${monto}. Por favor realice el pago a la brevedad. Gracias. Distribuidora Briancesco Menjivar.', $isDryRun);

        // 3. Recordatorio de mora (vencida hace 1+ días)
        $this->procesarTipo('recordatorio_mora', function () {
            return GestionCobro::with(['cliente', 'venta.vendedor'])
                ->whereIn('estado', ['pendiente', 'vencida'])
                ->where('fecha_vencimiento', '<', Carbon::today())
                ->get();
        }, 'Hola {nombre}, su cuota #{cuota} por ${monto} venció el {fecha_vencimiento} y se encuentra en mora. Por favor comuníquese con nosotros. Distribuidora Briancesco Menjivar.', $isDryRun);

        $this->info('✅ Proceso completado.');
        return Command::SUCCESS;
        */
    }

    protected function procesarTipo(string $tipo, \Closure $getCuotas, string $mensajePlantilla, bool $isDryRun): void
    {
        $automation = WhatsappAutomation::where('tipo', $tipo)->where('activo', true)->first();
        if (! $automation) {
            $this->line("  ↳ Sin automatización activa para: {$tipo}");
            return;
        }

        $cuotas = $getCuotas();
        $this->info("\n[{$tipo}] {$cuotas->count()} cuotas encontradas");

        foreach ($cuotas as $cuota) {
            $cliente = $cuota->cliente;

            if (! $cliente) {
                continue;
            }

            if (! $cliente->telefono_whatsapp) {
                $this->warn("  ↳ [{$cliente->nombre_completo}] Sin número WhatsApp");
                continue;
            }

            // Verificar duplicado del día
            if (WhatsappAutomationLog::yaEnviadoHoy($automation->id, $cliente->id, $cuota->id)) {
                $this->line("  ↳ [{$cliente->nombre_completo}] Ya enviado hoy, omitiendo");
                continue;
            }

            // Resolver cuenta de envío
            $cuenta = $automation->cuenta
                ?? WhatsappAccount::resolverParaCliente($cliente);

            // Construir mensaje
            $mensaje = str_replace(
                ['{nombre}', '{cuota}', '{monto}', '{fecha_vencimiento}'],
                [
                    $cliente->nombre,
                    $cuota->numero_cuota,
                    number_format($cuota->monto_cuota, 2),
                    $cuota->fecha_vencimiento->format('d/m/Y'),
                ],
                $mensajePlantilla
            );

            if ($isDryRun) {
                $this->line("  ↳ [DRY-RUN] {$cliente->nombre_completo} → {$cliente->telefono_whatsapp}: {$mensaje}");
                continue;
            }

            // Crear/abrir conversación
            $conversacion = WhatsappConversation::firstOrCreate(
                ['cliente_id' => $cliente->id],
                [
                    'whatsapp_account_id' => $cuenta?->id,
                    'wa_id'              => $cliente->telefono_whatsapp,
                    'estado'             => 'abierta',
                ]
            );

            // Enviar
            $resultado = $this->whatsapp->sendTextMessage($cuenta, $cliente->telefono_whatsapp, $mensaje);

            // Guardar mensaje
            $waMsg = WhatsappMessage::create([
                'conversation_id' => $conversacion->id,
                'wa_message_id'   => $resultado['message_id'] ?? null,
                'direction'       => 'out',
                'type'            => 'text',
                'body'            => $mensaje,
                'status'          => $resultado['success'] ? 'sent' : 'failed',
                'sent_at'         => now(),
            ]);

            $conversacion->update([
                'ultimo_mensaje'    => $mensaje,
                'ultimo_mensaje_at' => now(),
            ]);

            // Log
            WhatsappAutomationLog::create([
                'automation_id'      => $automation->id,
                'cliente_id'         => $cliente->id,
                'cuota_id'           => $cuota->id,
                'whatsapp_message_id'=> $waMsg->id,
                'tipo'               => $tipo,
                'estado'             => $resultado['success'] ? 'sent' : 'failed',
                'error'              => $resultado['error'] ?? null,
            ]);

            if ($resultado['success']) {
                $this->info("  ✓ [{$cliente->nombre_completo}] Enviado" . ($resultado['simulated'] ? ' (simulado)' : ''));
            } else {
                $this->error("  ✗ [{$cliente->nombre_completo}] Error: " . ($resultado['error'] ?? 'desconocido'));
            }
        }
    }
}
