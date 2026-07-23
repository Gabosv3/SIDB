<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\RutaCobro;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AsignarRutasClientes extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.asignar-rutas-clientes';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-link';
    }

    public static function getNavigationLabel(): string
    {
        return 'Asignar Rutas';
    }

    public function getTitle(): string
    {
        return 'Asignar Rutas a Clientes';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Cobros';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Cliente::whereNull('ruta_cobro_id')->orderBy('nombre'))
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Cliente')
                    ->searchable(['nombre', 'apellido'])
                    ->sortable(['nombre'])
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('dui')
                    ->label('DUI')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('telefono_normal')
                    ->label('Teléfono')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('asignarRuta')
                    ->label('Asignar Ruta')
                    ->icon('heroicon-m-link')
                    ->form([
                        Forms\Components\Select::make('ruta_cobro_id')
                            ->label('Selecciona una ruta')
                            ->placeholder('Elige una ruta de cobro')
                            ->options(fn () => RutaCobro::where('activa', true)
                                ->get()
                                ->pluck('nombre_con_dia', 'id')
                            )
                            ->required(),
                    ])
                    ->action(function (Cliente $record, array $data) {
                        $record->update(['ruta_cobro_id' => $data['ruta_cobro_id']]);

                        Notification::make()
                            ->title('Éxito')
                            ->body("Ruta asignada a {$record->nombre}")
                            ->success()
                            ->send();

                        $this->table->resetColumnOrder();
                    }),
            ])
            ->bulkActions([
                Actions\BulkAction::make('asignarRutaGrupal')
                    ->label('Asignar ruta a seleccionados')
                    ->icon('heroicon-m-link')
                    ->form([
                        Forms\Components\Select::make('ruta_cobro_id')
                            ->label('Selecciona una ruta')
                            ->placeholder('Elige una ruta de cobro')
                            ->options(fn () => RutaCobro::where('activa', true)
                                ->get()
                                ->pluck('nombre_con_dia', 'id')
                            )
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $count = $records->count();
                        $records->each(fn ($record) => $record->update(['ruta_cobro_id' => $data['ruta_cobro_id']]));

                        Notification::make()
                            ->title('Éxito')
                            ->body("Ruta asignada a {$count} cliente(s)")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('nombre')
            ->paginated([25, 50, 100]);
    }
}
