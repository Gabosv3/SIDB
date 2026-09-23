<?php

namespace App\Filament\Resources\CategoriaResource\Pages;

use App\Filament\Resources\CategoriaResource;
use App\Models\Categoria;
use App\Models\Producto;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoria extends EditRecord
{
    protected static string $resource = CategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Mismo guard que la tabla del listado (ver CategoriaResource::table)
            Actions\DeleteAction::make()
                ->before(function (Categoria $record, Actions\DeleteAction $action) {
                    $productos = Producto::where('categoria_id', $record->id)->count();

                    if ($productos > 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('No se puede eliminar')
                            ->body("Esta categoría tiene {$productos} producto(s) asignado(s) -- borrarla los dejaría sin categoría. Cambia esos productos de categoría primero, o desactívala en vez de eliminarla.")
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}