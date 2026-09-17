<?php

namespace App\Filament\Pages;

use App\Models\AnticipoVendedor;
use App\Models\ComisionTramo;
use App\Models\Vale;
use App\Models\Vendedor;
use App\Models\Venta;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class LiquidacionSemanalVentas extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Liquidación Semanal';
    protected static ?string $title           = 'Liquidación Semanal de Ventas';
    protected string $view = 'filament.pages.liquidacion-semanal-ventas';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $semana_inicio = '';
    public ?int   $vendedor_id   = null;

    // Formulario anticipo
    public ?int    $anticipo_vendedor_id  = null;
    public ?float  $anticipo_monto        = null;
    public ?string $anticipo_descripcion  = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Ventas';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public function mount(): void
    {
        $this->semana_inicio = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function getSemanaFin(): Carbon
    {
        return Carbon::parse($this->semana_inicio)->endOfWeek(Carbon::SUNDAY);
    }

    public function getVendedores(): \Illuminate\Support\Collection
    {
        return Vendedor::where('activo', true)
            ->whereNotNull('user_id')
            ->orderBy('nombre')
            ->get();
    }

    public function getLiquidacion(): array
    {
        $inicio = Carbon::parse($this->semana_inicio)->startOfDay();
        $fin    = $this->getSemanaFin()->endOfDay();

        $vendedores = Vendedor::with('employeeProfile')
            ->where('activo', true)
            ->whereNotNull('user_id')
            ->when($this->vendedor_id, fn ($q) => $q->where('id', $this->vendedor_id))
            ->get();

        return $vendedores->map(function (Vendedor $vendedor) use ($inicio, $fin) {
            // Ventas canceladas/devueltas no cuentan ni para el total ni para
            // la comisión. El % de tramo se aplica POR VENTA individual (a
            // mayor monto de esa venta, menor %), no sobre el total acumulado
            // de la semana -- igual que la prima diaria de "Ventas del Día".
            $ventasSemana = Venta::where('vendedor_id', $vendedor->id)
                ->whereBetween('fecha_venta', [$inicio, $fin])
                ->whereNotIn('estado', ['cancelada', 'devuelta'])
                ->get(['id', 'total']);

            $totalVendido = (float) $ventasSemana->sum('total');
            $comision = round($ventasSemana->sum(
                fn (Venta $v) => (float) $v->total * ComisionTramo::porcentajePara((float) $v->total) / 100
            ), 2);

            $porDia = Venta::where('vendedor_id', $vendedor->id)
                ->whereBetween('fecha_venta', [$inicio, $fin])
                ->whereNotIn('estado', ['cancelada', 'devuelta'])
                ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total, COUNT(*) as ventas')
                ->groupBy('dia')
                ->orderBy('dia')
                ->get();

            // Anticipos de la semana
            $anticipos = AnticipoVendedor::where('vendedor_id', $vendedor->id)
                ->where('semana_inicio', $inicio->toDateString())
                ->get();
            $totalAnticipos = (float) $anticipos->sum('monto');

            // Vales de consumo (personales) ya aprobados de la semana -- mismo
            // criterio que en la Liquidación Semanal de Cobradores.
            $totalValesConsumo = (float) Vale::where('user_id', $vendedor->user_id)
                ->where('tipo', 'consumo')
                ->where('estado', 'aprobado')
                ->whereBetween('fecha_gasto', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            $perfil = $vendedor->employeeProfile;
            $modalidad = $perfil?->modalidad_pago ?? 'comision';
            $salarioBase = (float) ($perfil?->salario_base ?? 0);

            // % efectivo solo para mostrar en pantalla (comisión / vendido);
            // cada venta ya pagó su propio tramo por separado arriba.
            $pct = $totalVendido > 0 ? round($comision / $totalVendido * 100, 2) : 0.0;

            $aPagar = match ($modalidad) {
                'salario_fijo' => $salarioBase,
                'mixto' => $salarioBase + $comision,
                default => $comision, // 'comision'
            };

            $neto = round($aPagar - $totalAnticipos - $totalValesConsumo, 2);

            return [
                'vendedor'            => $vendedor,
                'modalidad'           => $modalidad,
                'porcentaje_comision' => $pct,
                'total_vendido'       => $totalVendido,
                'salario_base'        => $salarioBase,
                'comision'            => $comision,
                'a_pagar'             => $aPagar,
                'total_anticipos'     => $totalAnticipos,
                'total_vales_consumo' => $totalValesConsumo,
                'neto'                => $neto,
                'por_dia'             => $porDia,
                'anticipos'           => $anticipos,
            ];
        })->filter(fn ($r) => $r['total_vendido'] > 0 || $r['total_anticipos'] > 0 || ! $this->vendedor_id)
          ->values()->all();
    }

    public function registrarAnticipo(): void
    {
        $this->validate([
            'anticipo_vendedor_id' => 'required|exists:vendedores,id',
            'anticipo_monto'       => 'required|numeric|min:0.01',
        ]);

        $inicio = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY);

        AnticipoVendedor::create([
            'vendedor_id'    => $this->anticipo_vendedor_id,
            'autorizado_por' => auth()->id(),
            'monto'          => $this->anticipo_monto,
            'fecha'          => today(),
            'semana_inicio'  => $inicio->toDateString(),
            'semana_fin'     => $inicio->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            'descripcion'    => $this->anticipo_descripcion,
            'estado'         => 'pendiente',
        ]);

        $this->anticipo_vendedor_id = null;
        $this->anticipo_monto       = null;
        $this->anticipo_descripcion = null;

        Notification::make()->title('Anticipo registrado')->success()->send();
    }

    public function liquidarSemana(int $vendedorId): void
    {
        $inicio = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY)->toDateString();

        AnticipoVendedor::where('vendedor_id', $vendedorId)
            ->where('semana_inicio', $inicio)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'descontado']);

        Notification::make()->title('Semana liquidada')->success()->send();
    }
}
