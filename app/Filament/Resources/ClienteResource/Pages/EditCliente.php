<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\Cliente;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver ClienteResource::table)
            // -- si no se repite aquí, el botón "Borrar" de esta página lo
            // esquiva por completo.
            Actions\DeleteAction::make()
                ->before(function (Cliente $record, Actions\DeleteAction $action) {
                    if (ClienteResource::tieneHistorialBloqueante($record->id)) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este cliente ya tiene ventas, pagos, visitas, pagarés, garantías o preventas registradas -- ocultarlo rompería esos recibos/reportes. Usa "Clientes por Ruta" para eliminarlo con toda su gestión, o desactívalo aquí.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
