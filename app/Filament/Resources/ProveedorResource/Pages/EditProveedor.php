<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Models\Compra;
use App\Models\Proveedor;
use Filament\Resources\Pages\EditRecord;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected static ?string $title = 'Editar Proveedor';

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver ProveedorResource::table)
            \Filament\Actions\DeleteAction::make()
                ->before(function (Proveedor $record, \Filament\Actions\DeleteAction $action) {
                    if (Compra::where('proveedor_id', $record->id)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este proveedor ya tiene compras registradas -- ocultarlo rompería ese historial en los reportes.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
