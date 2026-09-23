<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use App\Models\MantenimientoVehiculo;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehiculo extends EditRecord
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver VehiculoResource::table)
            Actions\DeleteAction::make()
                ->before(function ($record, Actions\DeleteAction $action) {
                    if (MantenimientoVehiculo::where('vehiculo_id', $record->id)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este vehículo ya tiene mantenimientos registrados -- ocultarlo rompería ese historial.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
