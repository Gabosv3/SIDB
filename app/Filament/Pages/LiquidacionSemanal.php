<?php

namespace App\Filament\Pages;

use App\Models\AnticipoCobrador;
use App\Models\Cobrador;
use App\Models\PagoVenta;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class LiquidacionSemanal extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Liquidación Semanal';
    protected static ?string $title           = 'Liquidación Semanal de Cobradores';
    protected static ?int    $navigationSort  = 6;
    protected string $view = 'filament.pages.liquidacion-semanal';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $semana_inicio = '';
    public ?int   $cobrador_id   = null;

    // Formulario anticipo
    public ?int    $anticipo_cobrador_id  = null;
    public ?float  $anticipo_monto        = null;
    public ?string $anticipo_descripcion  = null;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public function mount(): void
    {
        // Lunes de la semana actual
        $this->semana_inicio = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function getSemanaFin(): Carbon
    {
        return Carbon::parse($this->semana_inicio)->endOfWeek(Carbon::SUNDAY);
    }

    public function getCobradores(): \Illuminate\Support\Collection
    {
        return Cobrador::where('activo', true)
            ->where('excluir_reportes', false)
            ->orderBy('nombre')
            ->get();
    }

    public function getLiquidacion(): array
    {
        $inicio = Carbon::parse($this->semana_inicio)->startOfDay();
        $fin    = $this->getSemanaFin()->endOfDay();

        $cobradores = Cobrador::with('user')
            ->where('activo', true)
            ->where('excluir_reportes', false)
            ->when($this->cobrador_id, fn ($q) => $q->where('id', $this->cobrador_id))
            ->get();

        return $cobradores->map(function ($cobrador) use ($inicio, $fin) {
            // Total cobrado en la semana
            $totalCobrado = (float) PagoVenta::where('user_id', $cobrador->user_id)
                ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('monto');

            // Cobros por día
            $porDia = PagoVenta::where('user_id', $cobrador->user_id)
                ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
                ->selectRaw('DATE(fecha_pago) as dia, SUM(monto) as total, COUNT(DISTINCT cliente_id) as clientes')
                ->groupBy('dia')
                ->orderBy('dia')
                ->get();

            // Anticipos de la semana
            $anticipos = AnticipoCobrador::where('cobrador_id', $cobrador->id)
                ->where('semana_inicio', $inicio->toDateString())
                ->get();
            $totalAnticipos = (float) $anticipos->sum('monto');

            // Comisión 8%
            $comision = round($totalCobrado * 0.08, 2);
            $neto     = round($comision - $totalAnticipos, 2);

            return [
                'cobrador'       => $cobrador,
                'total_cobrado'  => $totalCobrado,
                'comision'       => $comision,
                'total_anticipos'=> $totalAnticipos,
                'neto'           => $neto,
                'por_dia'        => $porDia,
                'anticipos'      => $anticipos,
            ];
        })->filter(fn ($r) => $r['total_cobrado'] > 0 || $r['total_anticipos'] > 0 || ! $this->cobrador_id)
          ->values()->all();
    }

    public function registrarAnticipo(): void
    {
        $this->validate([
            'anticipo_cobrador_id' => 'required|exists:cobradores,id',
            'anticipo_monto'       => 'required|numeric|min:0.01',
        ]);

        $inicio = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY);

        AnticipoCobrador::create([
            'cobrador_id'    => $this->anticipo_cobrador_id,
            'autorizado_por' => auth()->id(),
            'monto'          => $this->anticipo_monto,
            'fecha'          => today(),
            'semana_inicio'  => $inicio->toDateString(),
            'semana_fin'     => $inicio->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            'descripcion'    => $this->anticipo_descripcion,
            'estado'         => 'pendiente',
        ]);

        $this->anticipo_cobrador_id = null;
        $this->anticipo_monto       = null;
        $this->anticipo_descripcion = null;

        Notification::make()->title('Anticipo registrado')->success()->send();
    }

    public function liquidarSemana(int $cobradorId): void
    {
        $inicio = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY)->toDateString();

        AnticipoCobrador::where('cobrador_id', $cobradorId)
            ->where('semana_inicio', $inicio)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'descontado']);

        Notification::make()->title('Semana liquidada')->success()->send();
    }
}
