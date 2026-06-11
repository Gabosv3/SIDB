<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use App\Models\PagoVenta;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class VentasRelationManager extends RelationManager
{
    protected static string $relationship = 'ventas';

    protected static ?string $title = 'Historial de compras';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_venta')
            ->columns([
                Tables\Columns\TextColumn::make('numero_venta')
                    ->label('N° Venta')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_venta')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_pago')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        'credito' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contado' => 'Contado',
                        'credito' => 'Crédito',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Pagado')
                    ->money('USD')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_pendiente')
                    ->label('Saldo')
                    ->money('USD')
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado venta')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'  => 'warning',
                        'completada' => 'success',
                        'cancelada'  => 'danger',
                        'devuelta'   => 'info',
                        default      => 'gray',
                    }),

                // ── Estado de cuotas ──────────────────────────────────────────
                Tables\Columns\TextColumn::make('estado_cuotas_badge')
                    ->label('Cuotas')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        if ((float) $record->saldo_pendiente <= 0) return 'completado';
                        $info = $record->estadoCuotas();
                        return match ($info['estado']) {
                            'adelantado' => "adelantado_{$info['diferencia']}",
                            'atrasado'   => "atrasado_{$info['diferencia']}",
                            'al_dia'     => 'al_dia',
                            default      => 'completado',
                        };
                    })
                    ->formatStateUsing(function ($state, $record): string {
                        if ((float) $record->saldo_pendiente <= 0) return '✓ Completado';
                        $info = $record->estadoCuotas();
                        return match ($info['estado']) {
                            'adelantado' => "▲ Adelantado {$info['diferencia']}",
                            'atrasado'   => "▼ Atrasado {$info['diferencia']}",
                            'al_dia'     => '● Al día',
                            default      => '✓ Completado',
                        };
                    })
                    ->color(function ($state): string {
                        if (str_starts_with($state, 'atrasado'))   return 'danger';
                        if (str_starts_with($state, 'adelantado')) return 'success';
                        if ($state === 'al_dia')                    return 'info';
                        return 'gray';
                    })
                    ->tooltip(function ($record): string {
                        $info = $record->estadoCuotas();
                        $txt  = "Cobradas: {$info['cuotas_cobradas']} | ";
                        $txt .= "Esperadas hoy: {$info['cuotas_esperadas']} | ";
                        $txt .= "Pendientes: {$info['cuotas_pendientes']}";
                        if ($info['parcial_numero']) {
                            $txt .= " | Cuota #{$info['parcial_numero']} parcial: \${$info['parcial_pagado']} de \${$info['parcial_total']}";
                        }
                        if ($info['proxima_fecha']) {
                            $txt .= " | Próxima: {$info['proxima_fecha']}";
                        }
                        return $txt;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente'  => 'Pendiente',
                        'completada' => 'Completada',
                        'cancelada'  => 'Cancelada',
                        'devuelta'   => 'Devuelta',
                    ]),

                Tables\Filters\Filter::make('con_saldo')
                    ->label('Con saldo pendiente')
                    ->query(fn ($query) => $query->where('saldo_pendiente', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->label('Ver venta')
                    ->url(fn ($record) => \App\Filament\Resources\VentaResource::getUrl('view', ['record' => $record])),

                Actions\Action::make('ver_pagos')
                    ->label('Pagos')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->tipo_pago === 'credito')
                    ->modalHeading(fn ($record): string => 'Pagos — ' . $record->numero_venta)
                    ->modalIcon('heroicon-o-banknotes')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn ($record) => view(
                        'filament.modals.pagos-venta',
                        ['venta' => $record->load('pagos.user')]
                    )),
            ])
            ->bulkActions([])
            ->defaultSort('fecha_venta', 'desc');
    }
}
