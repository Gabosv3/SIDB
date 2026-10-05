<?php

namespace App\Filament\Pages;

use App\Models\CuadreCaja as CuadreCajaModelo;
use App\Services\CuadreCajaService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CuadreCaja extends Page
{
    protected static ?string $navigationLabel = 'Cuadre de Caja';
    protected static ?string $title = 'Cuadre de Caja';
    protected string $view = 'filament.pages.cuadre-caja';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $fecha = '';

    /** user_id => efectivo realmente recibido (texto del input). */
    public array $recibido = [];

    /** user_id => nota. */
    public array $nota = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calculator';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Resúmenes';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public function mount(): void
    {
        $this->fecha = today()->toDateString();
        $this->cargarGuardados();
    }

    /** Al cambiar de día se vuelven a cargar los montos y notas ya guardados. */
    public function updatedFecha(): void
    {
        $this->cargarGuardados();
    }

    private function fechaCarbon(): Carbon
    {
        try {
            return Carbon::parse($this->fecha)->startOfDay();
        } catch (\Throwable) {
            return today();
        }
    }

    private function cargarGuardados(): void
    {
        $this->recibido = [];
        $this->nota = [];

        foreach (CuadreCajaModelo::whereDate('fecha', $this->fechaCarbon())->get() as $c) {
            $this->recibido[$c->user_id] = (string) $c->recibido;
            $this->nota[$c->user_id] = (string) $c->nota;
        }
    }

    public function getFilas(): Collection
    {
        return CuadreCajaService::filas($this->fechaCarbon(), Filament::getTenant()?->id);
    }

    public function guardar(int $userId): void
    {
        $fila = $this->getFilas()->firstWhere('user_id', $userId);

        if (! $fila) {
            Notification::make()->title('Esa persona ya no tiene movimientos en esta fecha')->warning()->send();

            return;
        }

        $texto = trim((string) ($this->recibido[$userId] ?? ''));

        if ($texto === '' || ! is_numeric($texto) || (float) $texto < 0) {
            Notification::make()->title('Escribe el efectivo recibido (un número, 0 o más)')->warning()->send();

            return;
        }

        $recibido = round((float) $texto, 2);

        CuadreCajaModelo::updateOrCreate(
            ['fecha' => $this->fechaCarbon()->toDateString(), 'user_id' => $userId],
            [
                'cuadrado_por' => auth()->id(),
                'cobros_efectivo' => $fila['cobros'],
                'ventas_contado' => $fila['ventas_contado'],
                'gastos' => $fila['gastos'],
                'esperado' => $fila['esperado'],
                'recibido' => $recibido,
                'diferencia' => round($recibido - $fila['esperado'], 2),
                'nota' => trim((string) ($this->nota[$userId] ?? '')) ?: null,
            ]
        );

        Notification::make()->title('Cuadre guardado: ' . $fila['persona'])->success()->send();
    }

    /** Quita el cuadre guardado (por si se registró a la persona equivocada). */
    public function borrar(int $userId): void
    {
        CuadreCajaModelo::whereDate('fecha', $this->fechaCarbon())->where('user_id', $userId)->delete();
        unset($this->recibido[$userId], $this->nota[$userId]);

        Notification::make()->title('Cuadre eliminado')->success()->send();
    }
}
