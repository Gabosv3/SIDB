<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PagaresRelationManager extends RelationManager
{
    protected static string $relationship = 'pagares';

    protected static ?string $title = 'Pagarés';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre_deudor')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Firmado')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre_deudor')
                    ->label('Deudor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dui')
                    ->label('DUI'),

                Tables\Columns\TextColumn::make('monto_financiado')
                    ->label('Monto financiado')
                    ->money('USD')
                    ->weight('semibold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('venta.numero_venta')
                    ->label('Venta')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Sin enlazar'),

                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('1ra cuota')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => $record->pdf_url)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
