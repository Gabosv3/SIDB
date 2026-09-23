<?php

namespace App\Filament\Resources\RutaCobroResource\Pages;

use App\Filament\Resources\RutaCobroResource;
use App\Models\RutaCobro;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRutaCobro extends EditRecord
{
    protected static string $resource = RutaCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ver-mapa')
                ->label('Ver Mapa')
                ->icon('heroicon-m-map')
                ->url(fn($record) => route('ruta.mapa', $record))
                ->openUrlInNewTab(),
            // Mismo guard que la tabla del listado (ver RutaCobroResource::table)
            Actions\DeleteAction::make()
                ->before(function (RutaCobro $record, Actions\DeleteAction $action) {
                    $clientes = $record->clientes()->count();

                    if ($clientes > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body("Esta ruta tiene {$clientes} cliente(s) asignado(s) -- ocultarla los dejaría sin ruta visible. Ve a \"Clientes por Ruta\" y usa \"Borrar esta ruta completa\", o cambia esos clientes de ruta primero.")
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
