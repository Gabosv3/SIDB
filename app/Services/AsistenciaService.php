<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\EmployeeProfile;
use Illuminate\Support\Carbon;

/**
 * Resumen de asistencia por periodo: para cada empleado, un renglón por día
 * con su primera entrada, última salida, horas trabajadas, y si llegó tarde
 * según la hora esperada configurada en su perfil.
 */
class AsistenciaService
{
    public static function rango(string $tipo, string $fechaReferencia): array
    {
        $ref = Carbon::parse($fechaReferencia);

        return match ($tipo) {
            'semana' => [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)],
            'quincena' => $ref->day <= 15
                ? [$ref->copy()->startOfMonth(), $ref->copy()->startOfMonth()->addDays(14)]
                : [$ref->copy()->startOfMonth()->addDays(15), $ref->copy()->endOfMonth()],
            'mes' => [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()],
            default => [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)],
        };
    }

    /**
     * Una fila por (empleado, día) dentro del rango, para los empleados que
     * tengan código de asistencia vinculado al equipo.
     *
     * @param  int[]  $empleadoIds  IDs de EmployeeProfile. Vacío = todos.
     */
    public static function resumenPorEmpleadoYDia(Carbon $inicio, Carbon $fin, array $empleadoIds = []): array
    {
        $perfiles = EmployeeProfile::whereNotNull('codigo_asistencia')
            ->where('estado_laboral', 'activo')
            ->with('user:id,name')
            ->when(! empty($empleadoIds), fn ($q) => $q->whereIn('id', $empleadoIds))
            ->get();

        $userIds = $perfiles->pluck('user_id')->filter();

        $marcajes = Asistencia::whereIn('user_id', $userIds)
            ->whereBetween('fecha_hora', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy(fn (Asistencia $a) => $a->user_id.'_'.$a->fecha_hora->toDateString());

        $filas = collect();

        foreach ($perfiles as $perfil) {
            for ($dia = $inicio->copy(); $dia->lte($fin); $dia->addDay()) {
                $clave = $perfil->user_id.'_'.$dia->toDateString();
                $delDia = $marcajes->get($clave, collect());

                if ($delDia->isEmpty()) {
                    continue;
                }

                $entradas = $delDia->where('tipo', 'entrada');
                $salidas = $delDia->where('tipo', 'salida');

                $primeraEntrada = $entradas->first()?->fecha_hora ?? $delDia->first()->fecha_hora;
                $ultimaSalida = $salidas->last()?->fecha_hora;

                $horasTrabajadas = $ultimaSalida
                    ? round($primeraEntrada->diffInMinutes($ultimaSalida) / 60, 2)
                    : null;

                $llegoTarde = false;
                if ($perfil->hora_entrada_esperada) {
                    $esperada = Carbon::parse($dia->toDateString().' '.$perfil->hora_entrada_esperada);
                    $llegoTarde = $primeraEntrada->gt($esperada);
                }

                $filas->push([
                    'empleado' => $perfil->user?->name ?? $perfil->codigo_empleado,
                    'fecha' => $dia->copy(),
                    'primera_entrada' => $primeraEntrada,
                    'ultima_salida' => $ultimaSalida,
                    'horas_trabajadas' => $horasTrabajadas,
                    'llego_tarde' => $llegoTarde,
                    'marcajes' => $delDia->values(),
                ]);
            }
        }

        return $filas->sortBy([['fecha', 'desc'], ['empleado', 'asc']])->values()->all();
    }

    public static function totales(array $filas): array
    {
        $col = collect($filas);

        return [
            'dias_con_marcaje' => $col->count(),
            'total_tardanzas' => $col->where('llego_tarde', true)->count(),
            'total_horas' => round((float) $col->sum('horas_trabajadas'), 2),
        ];
    }
}
