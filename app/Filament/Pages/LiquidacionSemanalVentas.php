<?php

namespace App\Filament\Pages;

use App\Models\AnticipoVendedor;
use App\Models\ComisionTramo;
use App\Models\DetalleVenta;
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
            // la comisión. Las ventas AL CONTADO tampoco entran aquí: para
            // esas el vendedor ya se queda con su margen (precio_venta menos
            // "se le recibe al vendedor" de cada producto) y entrega ese
            // monto fijo + el ajuste de su prima el mismo día -- meterlas
            // otra vez en la semanal sería pagarle dos veces. El % de tramo
            // se aplica POR PRODUCTO (el subtotal de cada línea de la
            // venta), no por el total de la venta ni por el total de la
            // semana -- una venta de $320 con dos productos de $200 y $120
            // paga el tramo de $200 y el de $120 por separado.
            $ventasSemana = Venta::where('vendedor_id', $vendedor->id)
                ->whereBetween('fecha_venta', [$inicio, $fin])
                ->whereNotIn('estado', ['cancelada', 'devuelta'])
                ->where('tipo_pago', '!=', 'contado')
                ->with('detalles')
                ->get();

            $totalVendido = (float) $ventasSemana->sum('total');
            $comision = round($ventasSemana->flatMap->detalles->sum(
                fn (DetalleVenta $d) => (float) $d->subtotal * ComisionTramo::porcentajePara((float) $d->subtotal) / 100
            ), 2);

            $porDia = Venta::where('vendedor_id', $vendedor->id)
                ->whereBetween('fecha_venta', [$inicio, $fin])
                ->whereNotIn('estado', ['cancelada', 'devuelta'])
                ->where('tipo_pago', '!=', 'contado')
                ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total, COUNT(*) as ventas')
                ->groupBy('dia')
                ->orderBy('dia')
                ->get();

            // Anticipos de la semana -- excluye los que vienen de "Confirmar
            // prima" de una venta al contado, porque esa prima ya no está
            // ligada a ninguna comisión de esta liquidación (se resolvió el
            // mismo día).
            $anticipos = AnticipoVendedor::where('vendedor_id', $vendedor->id)
                ->where('semana_inicio', $inicio->toDateString())
                ->whereDoesntHave('venta', fn ($q) => $q->where('tipo_pago', 'contado'))
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
