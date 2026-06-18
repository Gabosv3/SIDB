<?php

namespace App\Filament\Pages;

use App\Models\Cobrador;
use App\Models\GestionCobro;
use App\Models\PagoVenta;
use App\Models\VisitaCobro;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ResumenCobrosDia extends Page
{
    protected static ?string $navigationLabel = 'Resumen del Día';
    protected static ?string $title = 'Resumen de Cobros del Día';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.resumen-cobros-dia';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';
    public ?int $cobrador_id = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)->orderBy('nombre')->get();
    }

    public function getResumen(): array
    {
        $fecha = Carbon::parse($this->fecha);

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->when($this->cobrador_id, fn ($q) => $q->where('id', $this->cobrador_id))
            ->get();

        $resumen = $cobradores->map(function ($cobrador) use ($fecha) {
            $pagos = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('COUNT(DISTINCT cliente_id) AS total_pagos, COUNT(DISTINCT cliente_id) AS clientes_visitados, SUM(monto) AS total_cobrado')
                ->first();

            $porMetodo = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->selectRaw('metodo_pago, COUNT(*) AS cantidad, SUM(monto) AS monto')
                ->groupBy('metodo_pago')
                ->get()
                ->keyBy('metodo_pago');

            $detalle = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)
                ->with('cliente:id,nombre,apellido', 'venta:id,numero_venta')
                ->orderBy('created_at')
                ->get();

            // Visitas sin cobro del día
            $visitas = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fecha)
                ->with('cliente:id,nombre,apellido')
                ->orderBy('created_at')
                ->get();

            // Clientes NO visitados: en rutas del cobrador con cuotas pendientes,
            // sin pago ni visita registrada en la fecha seleccionada
            $diasEs = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
            $diaFecha = $diasEs[$fecha->dayOfWeekIso - 1];

            $rutasIds = $cobrador->rutasCobro()
                ->where('dia_semana', $diaFecha)
                ->pluck('id');

            $clientesConPago   = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereDate('fecha_pago', $fecha)->pluck('cliente_id');
            $clientesConVisita = VisitaCobro::where('user_id', $cobrador->user_id)
                ->whereDate('created_at', $fecha)->pluck('cliente_id');
            $clientesAtendidos = $clientesConPago->merge($clientesConVisita)->unique();

            $noVisitados = \App\Models\Cliente::whereIn('ruta_cobro_id', $rutasIds)
                ->whereNotIn('id', $clientesAtendidos)
                ->whereHas('gestionesCobro', fn ($q) => $q->whereIn('estado', ['pendiente', 'parcialmente_cobrado']))
                ->select('id', 'nombre', 'apellido', 'telefono_normal')
                ->orderBy('nombre')
                ->get();

            return [
                'cobrador'           => $cobrador,
                'total_cobrado'      => (float) ($pagos->total_cobrado ?? 0),
                'total_pagos'        => (int) ($pagos->total_pagos ?? 0),
                'clientes_visitados' => (int) ($pagos->clientes_visitados ?? 0),
                'por_metodo'         => $porMetodo,
                'detalle'            => $detalle,
                'visitas_sin_cobro'  => $visitas,
                'no_visitados'       => $noVisitados,
            ];
        })->filter(fn ($r) => $r['total_pagos'] > 0 || $r['visitas_sin_cobro']->isNotEmpty() || $r['no_visitados']->isNotEmpty() || ! $this->cobrador_id);

        return $resumen->values()->all();
    }

    public function getTotalesGenerales(array $resumen): array
    {
        $col = collect($resumen);
        return [
            'total_cobrado'      => round($col->sum('total_cobrado'), 2),
            'total_pagos'        => $col->sum('total_pagos'),
            'clientes_visitados' => $col->sum('clientes_visitados'),
            'total_sin_cobro'    => $col->sum(fn ($r) => $r['visitas_sin_cobro']->count()),
            'total_no_visitados' => $col->sum(fn ($r) => $r['no_visitados']->count()),
        ];
    }
}
