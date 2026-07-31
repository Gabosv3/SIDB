<?php

namespace App\Filament\Resources\ProductoResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VentasRelationManager extends RelationManager
{
    protected static string $relationship = 'detallesVenta';

    protected static ?string $title = 'Ventas donde salió este producto';

    // No hay Policy para DetalleVenta (no se gestiona directo, solo se lista
    // aquí de forma read-only) — sin esto, Filament oculta la pestaña entera
    // porque authorize('viewAny', DetalleVenta::class) no encuentra policy.
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['venta.cliente', 'venta.vendedor']))
            ->columns([
                Tables\Columns\TextColumn::make('venta.numero_venta')
                    ->label('N° Venta')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('venta.fecha_venta')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('venta.cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'venta.cliente',
                        fn ($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextColumn::make('venta.vendedor.nombre')
                    ->label('Vendedor')
                    ->formatStateUsing(fn ($record) => $record->venta?->vendedor ? trim($record->venta->vendedor->nombre.' '.$record->venta->vendedor->apellido) : '—'),

                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio_unitario')
                    ->label('Precio unitario')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('venta.estado')
                    ->label('Estado venta')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        'devuelta'   => 'info',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_venta')
                    ->label('Estado venta')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $estado) => $q->whereHas('venta', fn ($v) => $v->where('estado', $estado))
                    )),
            ])
            ->actions([
                Actions\Action::make('ver_venta')
                    ->label('Ver venta')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->venta_id
                        ? \App\Filament\Resources\VentaResource::getUrl('view', ['record' => $record->venta_id])
                        : null)
                    ->visible(fn ($record) => (bool) $record->venta_id),
            ])
            ->headerActions([])
            ->bulkActions([])
            ->defaultSort('venta.fecha_venta', 'desc');
    }
}
