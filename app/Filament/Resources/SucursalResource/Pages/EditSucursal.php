<?php

namespace App\Filament\Resources\SucursalResource\Pages;

use App\Filament\Resources\SucursalResource;
use App\Models\Sucursal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSucursal extends EditRecord
{
    protected static string $resource = SucursalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver SucursalResource::table)
            Actions\DeleteAction::make()
                ->before(function (Sucursal $record, Actions\DeleteAction $action) {
                    if (SucursalResource::tieneDatosBloqueantes($record->id)) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta sucursal ya tiene cobradores, vendedores, rutas de cobro o clientes -- ocultarla rompería esas relaciones por todo el sistema. Desactívala en vez de eliminarla.')
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
