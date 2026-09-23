<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Compra;
use App\Models\PagoCompra;
use Filament\Resources\Pages\EditRecord;

class EditCompra extends EditRecord
{
    protected static string $resource = CompraResource::class;

    protected static ?string $title = 'Editar Compra';

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver CompraResource::table)
            \Filament\Actions\DeleteAction::make()
                ->before(function (Compra $record, \Filament\Actions\DeleteAction $action) {
                    if (PagoCompra::where('compra_id', $record->id)->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta compra ya tiene pagos registrados -- ocultarla rompería ese historial de pagos a proveedor en los reportes.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
