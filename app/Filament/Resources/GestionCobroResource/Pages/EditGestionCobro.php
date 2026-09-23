<?php

namespace App\Filament\Resources\GestionCobroResource\Pages;

use App\Filament\Resources\GestionCobroResource;
use App\Models\VisitaCobro;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGestionCobro extends EditRecord
{
    protected static string $resource = GestionCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver GestionCobroResource::table)
            Actions\DeleteAction::make()
                ->before(function ($record, Actions\DeleteAction $action) {
                    if (VisitaCobro::where('gestion_cobro_id', $record->id)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta cuota ya tiene una visita de cobro registrada.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
