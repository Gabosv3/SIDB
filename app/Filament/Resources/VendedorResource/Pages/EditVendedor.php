<?php

namespace App\Filament\Resources\VendedorResource\Pages;

use App\Filament\Resources\VendedorResource;
use App\Models\Vendedor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendedor extends EditRecord
{
    protected static string $resource = VendedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver VendedorResource::table)
            Actions\DeleteAction::make()
                ->before(function (Vendedor $record, Actions\DeleteAction $action) {
                    if (VendedorResource::tieneHistorialBloqueante($record->id)) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este vendedor ya tiene ventas, reintegros, anticipos o asignaciones diarias registradas -- ocultarlo rompería esos recibos/reportes. Desactívalo en vez de eliminarlo.')
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
