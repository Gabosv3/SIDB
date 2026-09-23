<?php

namespace App\Filament\Resources\CobradorResource\Pages;

use App\Filament\Resources\CobradorResource;
use App\Models\Cobrador;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCobrador extends EditRecord
{
    protected static string $resource = CobradorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver CobradorResource::table)
            Actions\DeleteAction::make()
                ->before(function (Cobrador $record, Actions\DeleteAction $action) {
                    if (CobradorResource::tieneHistorialBloqueante($record->id)) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este cobrador ya tiene rutas, anticipos o encuestas de cliente registradas -- ocultarlo rompería esas pantallas. Desactívalo en vez de eliminarlo.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
