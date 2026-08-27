<?php

namespace App\Filament\Pages;

use App\Models\EmployeeProfile;
use App\Services\AsistenciaService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class AsistenciaEmpleados extends Page
{
    protected static ?string $navigationLabel = 'Asistencia de Empleados';
    protected static ?string $title = 'Asistencia de Empleados';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.asistencia-empleados';
    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodoTipo = 'semana';
    public string $fechaReferencia = '';
    /** @var array<int> */
    public array $empleadoIds = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-finger-print';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Empleados';
    }

    public function mount(): void
    {
        $this->fechaReferencia = today()->toDateString();
    }

    public function getEmpleados(): \Illuminate\Support\Collection
    {
        return EmployeeProfile::whereNotNull('codigo_asistencia')
            ->where('estado_laboral', 'activo')
            ->with('user:id,name')
            ->get();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function getRango(): array
    {
        return AsistenciaService::rango($this->periodoTipo, $this->fechaReferencia);
    }

    public function getEtiquetaPeriodo(): string
    {
        [$inicio, $fin] = $this->getRango();

        return $inicio->format('d/m/Y').' — '.$fin->format('d/m/Y');
    }

    public function getResumen(): array
    {
        [$inicio, $fin] = $this->getRango();

        return AsistenciaService::resumenPorEmpleadoYDia($inicio, $fin, $this->empleadoIds);
    }

    public function getTotales(array $resumen): array
    {
        return AsistenciaService::totales($resumen);
    }

    public function irPeriodoAnterior(): void
    {
        $this->fechaReferencia = match ($this->periodoTipo) {
            'semana' => Carbon::parse($this->fechaReferencia)->subWeek()->toDateString(),
            'quincena' => Carbon::parse($this->fechaReferencia)->subDays(15)->toDateString(),
            'mes' => Carbon::parse($this->fechaReferencia)->subMonthNoOverflow()->toDateString(),
            default => $this->fechaReferencia,
        };
    }

    public function irPeriodoSiguiente(): void
    {
        $this->fechaReferencia = match ($this->periodoTipo) {
            'semana' => Carbon::parse($this->fechaReferencia)->addWeek()->toDateString(),
            'quincena' => Carbon::parse($this->fechaReferencia)->addDays(15)->toDateString(),
            'mes' => Carbon::parse($this->fechaReferencia)->addMonthNoOverflow()->toDateString(),
            default => $this->fechaReferencia,
        };
    }
}
