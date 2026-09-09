<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use App\Models\Venta;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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
                Actions\Action::make('ver')
                    ->label('Ver PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => $record->pdf_url)
                    ->openUrlInNewTab(),

                // La app manda el pagaré en dos pasos (sube el PDF firmado antes de
                // confirmar la venta, y enlaza venta_id después con un segundo
                // request) — cuando ese segundo paso no llega, el pagaré queda
                // suelto y no aparece en ningún resumen de ventas. Este botón
                // permite enlazarlo a mano en vez de dejarlo huérfano.
                Actions\Action::make('enlazarVenta')
                    ->label('Enlazar venta')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(fn ($record) => $record->venta_id === null)
                    ->schema([
                        Forms\Components\Select::make('venta_id')
                            ->label('Venta de este cliente')
                            ->options(fn ($record) => Venta::where('cliente_id', $record->cliente_id)
                                ->orderByDesc('fecha_venta')
                                ->get()
                                ->mapWithKeys(fn (Venta $v) => [
                                    (string) $v->id => sprintf('%s — %s (%s)', $v->numero_venta, $v->fecha_venta->format('d/m/Y'), number_format((float) $v->total, 2)),
                                ]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update(['venta_id' => $data['venta_id']]);

                        Notification::make()
                            ->title('Pagaré enlazado a la venta')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
