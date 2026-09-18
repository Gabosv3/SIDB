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
use Illuminate\Support\Facades\Hash;

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
    public ?int    $anticipo_editando_id  = null;
    public ?string $anticipo_password     = null;
    public bool    $anticipo_requiere_password = false;

    // Forzar eliminar un anticipo ya "descontado"
    public ?int    $forzar_eliminar_id       = null;
    public ?string $forzar_eliminar_password = null;

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
            ['total_vendido' => $totalVendido, 'comision' => $comision, 'a_pagar' => $aPagar, 'modalidad' => $modalidad, 'salario_base' => $salarioBase]
                = $this->calcularAPagar($vendedor, $inicio, $fin);

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

            // % efectivo solo para mostrar en pantalla (comisión / vendido);
            // cada venta ya pagó su propio tramo por separado arriba.
            $pct = $totalVendido > 0 ? round($comision / $totalVendido * 100, 2) : 0.0;

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

    /**
     * Cuánto le corresponde pagar a un vendedor por su semana, sin contar
     * anticipos ni vales -- reutilizado tanto por getLiquidacion() como por
     * el chequeo de "el anticipo supera lo ganado" al registrar uno.
     *
     * @return array{total_vendido: float, comision: float, a_pagar: float, modalidad: string, salario_base: float}
     */
    private function calcularAPagar(Vendedor $vendedor, Carbon $inicio, Carbon $fin): array
    {
        // Ventas canceladas/devueltas no cuentan ni para el total ni para la
        // comisión. Las ventas AL CONTADO tampoco entran aquí: para esas el
        // vendedor ya se queda con su margen (precio_venta menos "se le
        // recibe al vendedor" de cada producto) y entrega ese monto fijo +
        // el ajuste de su prima el mismo día -- meterlas otra vez en la
        // semanal sería pagarle dos veces. El % de tramo se aplica POR
        // PRODUCTO (el subtotal de cada línea de la venta), no por el total
        // de la venta ni por el total de la semana -- una venta de $320 con
        // dos productos de $200 y $120 paga el tramo de $200 y el de $120
        // por separado.
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

        $perfil = $vendedor->employeeProfile;
        $modalidad = $perfil?->modalidad_pago ?? 'comision';
        $salarioBase = (float) ($perfil?->salario_base ?? 0);

        $aPagar = match ($modalidad) {
            'salario_fijo' => $salarioBase,
            'mixto' => $salarioBase + $comision,
            default => $comision, // 'comision'
        };

        return [
            'total_vendido' => $totalVendido,
            'comision' => $comision,
            'a_pagar' => $aPagar,
            'modalidad' => $modalidad,
            'salario_base' => $salarioBase,
        ];
    }

    public function registrarAnticipo(): void
    {
        $this->validate([
            'anticipo_vendedor_id' => 'required|exists:vendedores,id',
            'anticipo_monto'       => 'required|numeric|min:0.01',
        ]);

        // Semana a la que pertenece este anticipo: si se está editando uno
        // ya existente, se respeta SU semana original (no la que esté
        // seleccionada en el calendario en este momento).
        if ($this->anticipo_editando_id) {
            $anticipoActual = AnticipoVendedor::find($this->anticipo_editando_id);
            $inicioSemana = $anticipoActual
                ? Carbon::parse($anticipoActual->semana_inicio)
                : Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY);
        } else {
            $inicioSemana = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY);
        }
        $finSemana = $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY);

        // Si con este anticipo (sumado a los demás ya registrados esa
        // semana para el mismo vendedor) se supera lo que realmente ganó,
        // se exige la contraseña del usuario que lo está autorizando --
        // evita que cualquiera dé anticipos por encima de lo ganado sin
        // que quede claro quién lo aprobó.
        $vendedor = Vendedor::with('employeeProfile')->find($this->anticipo_vendedor_id);
        if ($vendedor) {
            $aPagar = $this->calcularAPagar($vendedor, $inicioSemana->copy()->startOfDay(), $finSemana->copy()->endOfDay())['a_pagar'];

            $otrosAnticipos = (float) AnticipoVendedor::where('vendedor_id', $this->anticipo_vendedor_id)
                ->where('semana_inicio', $inicioSemana->toDateString())
                ->when($this->anticipo_editando_id, fn ($q) => $q->where('id', '!=', $this->anticipo_editando_id))
                ->sum('monto');

            if (($otrosAnticipos + (float) $this->anticipo_monto) > $aPagar) {
                if (! $this->anticipo_requiere_password) {
                    $this->anticipo_requiere_password = true;

                    Notification::make()
                        ->title('Este anticipo supera lo que el vendedor lleva ganado esa semana')
                        ->body('Ingresa tu contraseña para confirmarlo.')
                        ->warning()
                        ->send();

                    return;
                }

                if (! $this->anticipo_password || ! Hash::check($this->anticipo_password, auth()->user()->password)) {
                    Notification::make()->title('Contraseña incorrecta')->danger()->send();

                    return;
                }
            }
        }

        if ($this->anticipo_editando_id) {
            $anticipo = AnticipoVendedor::find($this->anticipo_editando_id);

            if ($anticipo) {
                $anticipo->update([
                    'vendedor_id' => $this->anticipo_vendedor_id,
                    'monto'       => $this->anticipo_monto,
                    'descripcion' => $this->anticipo_descripcion,
                ]);

                Notification::make()->title('Anticipo actualizado')->success()->send();
            }

            $this->cancelarEdicionAnticipo();

            return;
        }

        AnticipoVendedor::create([
            'vendedor_id'    => $this->anticipo_vendedor_id,
            'autorizado_por' => auth()->id(),
            'monto'          => $this->anticipo_monto,
            'fecha'          => today(),
            'semana_inicio'  => $inicioSemana->toDateString(),
            'semana_fin'     => $finSemana->toDateString(),
            'descripcion'    => $this->anticipo_descripcion,
            'estado'         => 'pendiente',
        ]);

        $this->anticipo_vendedor_id       = null;
        $this->anticipo_monto             = null;
        $this->anticipo_descripcion       = null;
        $this->anticipo_password          = null;
        $this->anticipo_requiere_password = false;

        Notification::make()->title('Anticipo registrado')->success()->send();
    }

    /** Precarga el formulario de arriba con los datos de un anticipo para editarlo. */
    public function editarAnticipo(int $anticipoId): void
    {
        $anticipo = AnticipoVendedor::find($anticipoId);

        if (! $anticipo) {
            return;
        }

        $this->anticipo_editando_id  = $anticipo->id;
        $this->anticipo_vendedor_id  = $anticipo->vendedor_id;
        $this->anticipo_monto        = (float) $anticipo->monto;
        $this->anticipo_descripcion  = $anticipo->descripcion;
        $this->anticipo_password           = null;
        $this->anticipo_requiere_password  = false;
    }

    public function cancelarEdicionAnticipo(): void
    {
        $this->anticipo_editando_id       = null;
        $this->anticipo_vendedor_id       = null;
        $this->anticipo_monto             = null;
        $this->anticipo_descripcion       = null;
        $this->anticipo_password          = null;
        $this->anticipo_requiere_password = false;
    }

    /**
     * Un anticipo "pendiente" se borra directo. Uno ya "descontado" es
     * porque la semana ya se liquidó con ese monto restado -- borrarlo
     * dejaría el neto ya pagado descuadrado, así que solo se permite
     * "forzándolo" con la contraseña del usuario (para corregir errores o
     * limpiar pruebas, no para el uso normal).
     */
    public function eliminarAnticipo(int $anticipoId): void
    {
        $anticipo = AnticipoVendedor::find($anticipoId);

        if (! $anticipo) {
            return;
        }

        if ($anticipo->estado !== 'pendiente') {
            if ($this->forzar_eliminar_id !== $anticipo->id) {
                $this->forzar_eliminar_id = $anticipo->id;
                $this->forzar_eliminar_password = null;

                Notification::make()
                    ->title('Este anticipo ya fue descontado')
                    ->body('Si de verdad quieres borrarlo (ej. era una prueba), confirma con tu contraseña abajo.')
                    ->warning()
                    ->send();

                return;
            }

            if (! $this->forzar_eliminar_password || ! Hash::check($this->forzar_eliminar_password, auth()->user()->password)) {
                Notification::make()->title('Contraseña incorrecta')->danger()->send();

                return;
            }

            $this->forzar_eliminar_id = null;
            $this->forzar_eliminar_password = null;
        }

        if ($this->anticipo_editando_id === $anticipo->id) {
            $this->cancelarEdicionAnticipo();
        }

        $anticipo->delete();

        Notification::make()->title('Anticipo eliminado')->success()->send();
    }

    public function cancelarForzarEliminar(): void
    {
        $this->forzar_eliminar_id = null;
        $this->forzar_eliminar_password = null;
    }

    public function liquidarSemana(int $vendedorId): void
    {
        $inicioSemana = Carbon::parse($this->semana_inicio)->startOfWeek(Carbon::MONDAY);
        $finSemana = $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY);
        $inicio = $inicioSemana->toDateString();

        AnticipoVendedor::where('vendedor_id', $vendedorId)
            ->where('semana_inicio', $inicio)
            ->where('estado', 'pendiente')
            ->update(['estado' => 'descontado']);

        // Si le dieron más de anticipo/vales de lo que ganó esa semana, el
        // faltante no desaparece: se arrastra como un anticipo pendiente de
        // la semana siguiente, para que se le siga descontando hasta
        // recuperarlo.
        $vendedor = Vendedor::with('employeeProfile')->find($vendedorId);
        if ($vendedor) {
            $aPagar = $this->calcularAPagar($vendedor, $inicioSemana->copy()->startOfDay(), $finSemana->copy()->endOfDay())['a_pagar'];

            $totalAnticipos = (float) AnticipoVendedor::where('vendedor_id', $vendedorId)
                ->where('semana_inicio', $inicio)
                ->whereDoesntHave('venta', fn ($q) => $q->where('tipo_pago', 'contado'))
                ->sum('monto');

            $totalValesConsumo = (float) Vale::where('user_id', $vendedor->user_id)
                ->where('tipo', 'consumo')
                ->where('estado', 'aprobado')
                ->whereBetween('fecha_gasto', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->sum('monto');

            $neto = round($aPagar - $totalAnticipos - $totalValesConsumo, 2);

            if ($neto < 0) {
                $inicioSiguiente = $inicioSemana->copy()->addWeek();

                AnticipoVendedor::create([
                    'vendedor_id'    => $vendedorId,
                    'autorizado_por' => auth()->id(),
                    'monto'          => abs($neto),
                    'fecha'          => today(),
                    'semana_inicio'  => $inicioSiguiente->toDateString(),
                    'semana_fin'     => $inicioSiguiente->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                    'descripcion'    => 'Saldo pendiente de la semana del '.$inicioSemana->format('d/m/Y'),
                    'estado'         => 'pendiente',
                ]);
            }
        }

        Notification::make()->title('Semana liquidada')->success()->send();
    }
}
