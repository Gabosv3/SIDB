<?php

namespace App\Filament\Resources\SupervisorResource\Pages;

use App\Filament\Resources\SupervisorResource;
use App\Models\EncuestaCliente;
use App\Models\Supervision;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupervisor extends EditRecord
{
    protected static string $resource = SupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver SupervisorResource::table)
            Actions\DeleteAction::make()
                ->before(function ($record, Actions\DeleteAction $action) {
                    if (Supervision::where('supervisor_id', $record->id)->exists()
                        || EncuestaCliente::where('supervisor_id', $record->id)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este supervisor ya tiene supervisiones o encuestas de cliente registradas -- ocultarlo rompería esas pantallas. Desactívalo en vez de eliminarlo.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
