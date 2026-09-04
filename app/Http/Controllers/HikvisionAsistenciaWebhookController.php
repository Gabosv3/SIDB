<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los marcajes de asistencia que el equipo Hikvision (terminal de
 * huella/rostro, ej. DS-K1T321MFWX) manda por internet cada vez que un
 * empleado marca — configurado en el equipo como "Notificar por HTTP" /
 * "Linkage Method: Notify Surveillance Center" apuntando a esta URL.
 *
 * El equipo vive en la red local de la oficina; el panel corre en la nube.
 * Por eso el equipo EMPUJA cada evento hacia acá en vez de que el panel lo
 * vaya a buscar — no requiere abrir puertos en la oficina.
 *
 * El formato exacto varía por firmware. La mayoría manda multipart/form-data
 * con una parte "event_log" en JSON; algunos mandan el JSON directo como
 * cuerpo de la petición. Se intentan ambos, y si no se puede identificar al
 * empleado o la fecha, el evento se registra en el log de todos modos para
 * poder ajustar el parseo con un caso real sin perder el marcaje de vista.
 */
class HikvisionAsistenciaWebhookController extends Controller
{
    public function recibir(Request $request, string $token): Response
    {
        if (! hash_equals((string) config('services.hikvision.token', ''), $token)) {
            Log::warning('Webhook Hikvision: token inválido', ['token_recibido' => $token]);

            return response('token inválido', 403);
        }

        $evento = $this->extraerEvento($request);

        if (! $evento) {
            Log::warning('Webhook Hikvision: no se pudo interpretar el payload', [
                'content_type' => $request->header('Content-Type'),
                'raw' => substr($request->getContent(), 0, 2000),
            ]);

            // 200 igual: si se responde error, el equipo reintenta indefinidamente
            // y satura el log con el mismo evento que no se va a poder leer solo.
            return response('recibido', 200);
        }

        Log::info('Webhook Hikvision: evento recibido', $evento);

        $codigoEmpleado = $evento['codigo_empleado'] ?? null;

        // El equipo manda por el mismo webhook otros eventos del control de
        // acceso que no son marcajes reales (ej. majorEventType 3 / "invalid":
        // un intento de verificación fallido o un evento de puerta), y esos
        // vienen sin employeeNoString. Antes se guardaban igual como un
        // "marcaje" vacío sin empleado, ensuciando la tabla — se descartan.
        if (! $codigoEmpleado) {
            Log::info('Webhook Hikvision: evento descartado por no traer código de empleado (no es un marcaje real)', $evento);

            return response('descartado, sin empleado', 200);
        }

        $userId = EmployeeProfile::where('codigo_asistencia', $codigoEmpleado)->value('user_id');

        if (! $userId) {
            Log::warning('Webhook Hikvision: código de empleado sin vincular a ningún perfil', [
                'codigo_empleado' => $codigoEmpleado,
            ]);
        }

        $fechaHora = $evento['fecha_hora'] ?? now();

        // El equipo a veces manda el mismo toque dos veces (reintento por
        // conexión lenta, o un doble disparo del sensor) -- si ya hay un
        // marcaje del mismo empleado a menos de un minuto, se ignora en vez
        // de guardarlo como si fuera un segundo evento real (que además el
        // heurístico de abajo etiquetaría mal como "salida").
        if ($userId) {
            $reciente = Asistencia::where('user_id', $userId)
                ->where('fecha_hora', '>=', Carbon::parse($fechaHora)->subMinute())
                ->exists();

            if ($reciente) {
                Log::info('Webhook Hikvision: marcaje ignorado por ser duplicado (menos de 1 minuto del anterior)', $evento);

                return response('duplicado, ignorado', 200);
            }
        }

        $tipo = $this->determinarTipo($evento['attendance_status'] ?? null, $userId, $fechaHora);

        Asistencia::create([
            'user_id' => $userId,
            'codigo_empleado_dispositivo' => $codigoEmpleado,
            'tipo' => $tipo,
            'fecha_hora' => $fechaHora,
            'metodo' => $evento['metodo'] ?? null,
            'dispositivo' => $evento['dispositivo'] ?? null,
            'payload_crudo' => $evento['crudo'] ?? null,
        ]);

        return response('ok', 200);
    }

    /**
     * Intenta leer el evento de las dos formas más comunes en que lo manda
     * un equipo Hikvision. Devuelve null si no reconoce ninguna.
     */
    private function extraerEvento(Request $request): ?array
    {
        $datos = null;

        // Forma más común: multipart/form-data con una parte de archivo o
        // campo de texto llamada "event_log" que trae el JSON del evento.
        if ($request->hasFile('event_log')) {
            $contenido = file_get_contents($request->file('event_log')->getRealPath());
            $datos = json_decode($contenido, true);
        } elseif ($request->filled('event_log')) {
            $datos = json_decode($request->input('event_log'), true);
        }

        // Algunos firmwares mandan el JSON directo como cuerpo, sin multipart.
        if (! $datos) {
            $datos = $request->json()->all();
        }

        if (empty($datos) || ! is_array($datos)) {
            return null;
        }

        $acceso = $datos['AccessControllerEvent'] ?? $datos['AcsEvent'] ?? [];

        $codigoEmpleado = $acceso['employeeNoString']
            ?? (isset($acceso['employeeNo']) ? (string) $acceso['employeeNo'] : null);

        $fechaHoraRaw = $datos['dateTime'] ?? null;
        $fechaHora = null;
        if ($fechaHoraRaw) {
            try {
                $fechaHora = Carbon::parse($fechaHoraRaw);
            } catch (\Throwable) {
                $fechaHora = null;
            }
        }

        if (! $codigoEmpleado && ! $fechaHora) {
            return null;
        }

        return [
            'codigo_empleado' => $codigoEmpleado,
            'fecha_hora' => $fechaHora,
            'attendance_status' => $acceso['attendanceStatus'] ?? null,
            'metodo' => $this->normalizarMetodo($acceso['currentVerifyMode'] ?? null),
            'dispositivo' => $acceso['deviceName'] ?? $datos['macAddress'] ?? $datos['ipAddress'] ?? null,
            'crudo' => $datos,
        ];
    }

    private function normalizarMetodo(?string $modo): ?string
    {
        if (! $modo) {
            return null;
        }

        return match (true) {
            str_contains($modo, 'face') => 'rostro',
            str_contains($modo, 'fp') || str_contains($modo, 'fingerPrint') => 'huella',
            str_contains($modo, 'card') => 'tarjeta',
            str_contains($modo, 'Pw') || str_contains($modo, 'password') => 'clave',
            default => $modo,
        };
    }

    /**
     * El equipo no siempre manda "attendanceStatus" (checkIn/checkOut) --
     * muchos terminales de control de acceso solo registran la marca sin
     * distinguir entrada de salida. Cuando falta, se infiere alternando con
     * el último marcaje del mismo empleado ese mismo día: si el anterior fue
     * entrada, este es salida, y viceversa.
     */
    private function determinarTipo(?string $attendanceStatus, ?int $userId, $fechaHora): string
    {
        // Comparación insensible a mayúsculas/espacios: el valor exacto que
        // manda cada firmware varía ("checkIn", "checkin", " CheckIn ", etc.).
        $normalizado = $attendanceStatus ? strtolower(trim($attendanceStatus)) : null;

        if (in_array($normalizado, ['checkin', 'check_in', 'check-in'], true)) {
            return 'entrada';
        }

        if (in_array($normalizado, ['checkout', 'check_out', 'check-out'], true)) {
            return 'salida';
        }

        if (! $userId) {
            return 'desconocido';
        }

        $fecha = Carbon::parse($fechaHora);
        $ultimo = Asistencia::where('user_id', $userId)
            ->whereDate('fecha_hora', $fecha->toDateString())
            ->orderByDesc('fecha_hora')
            ->first();

        if (! $ultimo || $ultimo->tipo === 'salida') {
            return 'entrada';
        }

        return 'salida';
    }
}
