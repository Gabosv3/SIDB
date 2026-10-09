<?php

namespace App\Filament\Pages;

use App\Models\Asistencia;
use App\Models\EmployeeProfile;
use App\Services\AsistenciaService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrarAsistenciaManual')
                ->label('Registrar asistencia manual')
                ->icon('heroicon-m-pencil-square')
                ->color('gray')
                ->modalHeading('Registrar asistencia manual')
                ->modalDescription('Para cuando a un empleado se le olvidó marcar en el equipo.')
                ->schema([
                    Forms\Components\Select::make('empleado_id')
                        ->label('Empleado')
                        ->placeholder('Elige un empleado')
                        ->options(fn () => EmployeeProfile::whereNotNull('codigo_asistencia')
                            ->where('estado_laboral', 'activo')
                            ->with('user:id,name')
                            ->get()
                            ->mapWithKeys(fn (EmployeeProfile $p) => [(string) $p->id => $p->user?->name ?? $p->codigo_empleado]))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de marca')
                        ->options(['entrada' => 'Entrada', 'salida' => 'Salida'])
                        ->required(),
                    Forms\Components\DateTimePicker::make('fecha_hora')
                        ->label('Fecha y hora')
                        ->seconds(false)
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $perfil = EmployeeProfile::findOrFail($data['empleado_id']);

                    if (! $perfil->user_id) {
                        Notification::make()->title('Este empleado no tiene usuario vinculado')->danger()->send();

                        return;
                    }

                    Asistencia::create([
                        'user_id' => $perfil->user_id,
                        'tipo' => $data['tipo'],
                        'fecha_hora' => $data['fecha_hora'],
                        'metodo' => 'manual',
                    ]);

                    Notification::make()
                        ->title('Asistencia registrada manualmente')
                        ->success()
                        ->send();
                }),
        ];
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

    public function getResumenPorEmpleado(array $resumen): array
    {
        return AsistenciaService::agruparPorEmpleado($resumen);
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
