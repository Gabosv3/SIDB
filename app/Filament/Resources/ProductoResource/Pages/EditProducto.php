<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Producto;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProducto extends EditRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver ProductoResource::table)
            Actions\DeleteAction::make()
                ->before(function (Producto $record, Actions\DeleteAction $action) {
                    if ($record->detallesVenta()->exists() || $record->detallesCompra()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este producto ya tiene ventas o compras registradas -- ocultarlo rompería esos recibos/reportes viejos. Desactívalo (Activo = No) en vez de eliminarlo.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
