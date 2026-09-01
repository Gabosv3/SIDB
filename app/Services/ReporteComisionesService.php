<?php

namespace App\Services;

use App\Models\Cobrador;
use App\Models\EmployeeProfile;
use App\Models\PagoVenta;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Support\Carbon;

/**
 * Resumen de comisiones y nómina por periodo: cuánto le corresponde a cada
 * empleado según su modalidad de pago (salario fijo, comisión, o mixto),
 * calculando la comisión real sobre lo que vendió/cobró en el periodo.
 */
class ReporteComisionesService
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
     * Una fila por empleado activo con lo que le corresponde pagar en el
     * periodo, según su modalidad_pago:
     * - salario_fijo: el salario_base tal cual.
     * - comision: porcentaje_comision % sobre lo que vendió/cobró.
     * - mixto: salario_base + comisión sobre lo vendido/cobrado.
     */
    public static function resumenPorEmpleado(Carbon $inicio, Carbon $fin): array
    {
        $inicioStr = $inicio->toDateString();
        $finStr = $fin->copy()->endOfDay()->toDateTimeString();

        $empleados = EmployeeProfile::where('estado_laboral', 'activo')
            ->whereNotNull('modalidad_pago')
            ->with('user')
            ->get();

        return $empleados->map(function (EmployeeProfile $perfil) use ($inicioStr, $finStr) {
            $tipos = $perfil->tipo_empleado ?? [];
            $pct = (float) ($perfil->porcentaje_comision ?? 0);

            $baseVendido = 0.0;
            $baseCobrado = 0.0;

            if (in_array('vendedor', $tipos, true)) {
                $vendedorId = Vendedor::where('user_id', $perfil->user_id)->value('id');
                if ($vendedorId) {
                    $baseVendido = (float) Venta::where('vendedor_id', $vendedorId)
                        ->whereBetween('fecha_venta', [$inicioStr, $finStr])
                        ->whereNotIn('estado', ['cancelada', 'devuelta'])
                        ->sum('total');
                }
            }

            if (in_array('cobrador', $tipos, true)) {
                $baseCobrado = (float) PagoVenta::where('user_id', $perfil->user_id)
                    ->whereBetween('fecha_pago', [$inicioStr, $finStr])
                    ->whereNull('anulado_en')
                    ->sum('monto');
            }

            $baseComision = $baseVendido + $baseCobrado;
            $comision = round($baseComision * $pct / 100, 2);
            $salarioBase = (float) ($perfil->salario_base ?? 0);

            $totalAPagar = match ($perfil->modalidad_pago) {
                'salario_fijo' => $salarioBase,
                'comision' => $comision,
                'mixto' => $salarioBase + $comision,
                default => 0.0,
            };

            return [
                'empleado' => trim(($perfil->user->name ?? $perfil->codigo_empleado ?? 'Sin nombre')),
                'cargo' => $perfil->cargo,
                'modalidad_pago' => $perfil->modalidad_pago,
                'porcentaje_comision' => $pct,
                'base_vendido' => round($baseVendido, 2),
                'base_cobrado' => round($baseCobrado, 2),
                'comision' => $comision,
                'salario_base' => round($salarioBase, 2),
                'total_a_pagar' => round($totalAPagar, 2),
            ];
        })
            ->sortByDesc('total_a_pagar')
            ->values()
            ->all();
    }

    public static function totales(array $resumen): array
    {
        $col = collect($resumen);

        return [
            'total_empleados' => $col->count(),
            'total_nomina' => round($col->sum('total_a_pagar'), 2),
            'total_comisiones' => round($col->sum('comision'), 2),
            'total_salarios_fijos' => round(
                $col->where('modalidad_pago', 'salario_fijo')->sum('salario_base')
                    + $col->where('modalidad_pago', 'mixto')->sum('salario_base'),
                2
            ),
        ];
    }
}
