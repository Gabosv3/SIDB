<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Categoria;
use App\Models\Producto;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('conteoInventario')
                ->label('Conteo de inventario (PDF)')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->modalHeading('Conteo de inventario')
                ->modalDescription('Elegí las categorías a incluir. Si no marcás ninguna, se incluyen todas.')
                ->modalSubmitActionLabel('Generar PDF')
                ->schema([
                    Forms\Components\CheckboxList::make('categorias')
                        ->label('Categorías')
                        ->options(fn () => Categoria::where('activo', true)->orderBy('nombre')->get()->mapWithKeys(fn (Categoria $c) => [(string) $c->id => $c->nombre]))
                        ->columns(2)
                        ->bulkToggleable(),
                ])
                ->action(fn (array $data) => redirect(route('productos.conteo-inventario', [
                    'tenant' => \Filament\Facades\Filament::getTenant()?->id ?? 1,
                    'categoria_id' => $data['categorias'] ?? [],
                ]))),
            // Combos quedan afuera: su stock se calcula solo a partir de sus
            // componentes, ponerlo a mano ahí se pisaría solo en el siguiente
            // cambio de stock de cualquier componente.
            Actions\Action::make('stockManualA50')
                ->label('Poner stock en 50 (manuales)')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Poner stock en 50')
                ->modalDescription(fn () => sprintf(
                    'Esto va a dejar el stock en 50 unidades para los %d productos ingresados manualmente (no afecta los importados de Excel ni los combos). ¿Confirmás?',
                    Producto::where('origen', 'manual')->where('es_combo', false)->count()
                ))
                ->modalSubmitActionLabel('Sí, poner en 50')
                ->action(function (): void {
                    $ids = Producto::where('origen', 'manual')->where('es_combo', false)->pluck('id');

                    // update() masivo no dispara eventos de modelo -- si alguno de
                    // estos productos es componente de un combo, ese combo quedaría
                    // con el stock calculado viejo. Se recalculan aparte.
                    $actualizados = Producto::whereIn('id', $ids)->update(['stock' => 50]);

                    \App\Models\ComboComponente::whereIn('producto_componente_id', $ids)
                        ->pluck('producto_combo_id')
                        ->unique()
                        ->each(fn (int $comboId) => Producto::recalcularStockCombo($comboId));

                    Notification::make()
                        ->title("Stock actualizado en {$actualizados} producto(s)")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
