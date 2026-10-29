<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use App\Models\Vendedor;
use App\Services\ReplicarAsignacionService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListAsignacionesDiarias extends ListRecords
{
    protected static string $resource = AsignacionDiariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reporte')
                ->label('📊 Descargar Reporte PDF')
                ->button()
                ->color('info')
                ->icon('heroicon-m-document-text')
                ->url(fn () => route('reporte.diario', ['tenant' => \Filament\Facades\Filament::getTenant()->id]))
                ->openUrlInNewTab(),

            Actions\Action::make('replicar')
                ->label('Replicar del día anterior')
                ->icon('heroicon-m-document-duplicate')
                ->color('gray')
                ->modalHeading('Replicar asignación del día anterior')
                ->modalDescription('Crea la asignación de la fecha elegida con los mismos productos y cantidades de la última que tuvo el vendedor. Los precios son los de hoy y la cantidad se limita al stock disponible.')
                ->modalSubmitActionLabel('Replicar')
                ->schema([
                    Forms\Components\Select::make('vendedor_id')
                        ->label('Vendedor')
                        ->options(fn () => Vendedor::where('activo', true)->whereNotNull('user_id')->orderBy('nombre')->get()
                            ->mapWithKeys(fn (Vendedor $v) => [$v->id => trim("{$v->nombre} {$v->apellido}")]))
                        ->searchable()
                        ->required()
                        ->live(),
                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha de la nueva asignación')
                        ->default(today())
                        ->native(false)
                        ->required()
                        ->live(),
                    Forms\Components\Placeholder::make('origen')
                        ->label('Se copiará de')
                        ->content(function (Get $get): string {
                            if (! $get('vendedor_id') || ! $get('fecha')) {
                                return 'Elige vendedor y fecha.';
                            }

                            $origen = ReplicarAsignacionService::origen((int) $get('vendedor_id'), $get('fecha'));

                            if (! $origen) {
                                return 'Este vendedor no tiene una asignación anterior.';
                            }

                            $detalle = $origen->detalles
                                ->map(fn ($d) => "{$d->cantidad_asignada} × ".($d->producto?->nombre ?? 'Producto eliminado'))
                                ->implode(', ');

                            return $origen->fecha->format('d/m/Y').' — '.$detalle;
                        }),
                ])
                ->action(function (array $data): void {
                    try {
                        $r = ReplicarAsignacionService::replicar((int) $data['vendedor_id'], $data['fecha']);
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('No se pudo replicar')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title('Asignación replicada desde el '.$r['origen']->fecha->format('d/m/Y'))
                        ->body($r['ajustes'] === [] ? null : "Ajustado por stock:\n".implode("\n", $r['ajustes']))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('create')
                ->label('Nueva asignación diaria')
                ->button()
                ->url(fn () => route('asignacion-diaria.crear', ['tenant' => \Filament\Facades\Filament::getTenant()->id])),
        ];
    }
}
