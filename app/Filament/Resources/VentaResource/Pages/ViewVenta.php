<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\GestionCobro;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;

class ViewVenta extends ViewRecord
{
    protected static string $resource = VentaResource::class;

    protected static ?string $title = 'Detalle de Venta';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerarGestiones')
                ->label('Regenerar Gestiones de Cobro')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->action(function () {
                    $venta = $this->record;

                    // Si no tiene cuotas, no hacer nada
                    $cuotas = $venta->detalles
                        ->pluck('cuotas')
                        ->filter(fn ($c) => $c !== null)
                        ->unique()
                        ->values();

                    if ($cuotas->isEmpty()) {
                        Notification::make()
                            ->title('Sin cuotas')
                            ->body('Esta venta no tiene cuotas configuradas.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Eliminar gestiones existentes
                    GestionCobro::where('venta_id', $venta->id)->delete();

                    // Crear nuevas gestiones
                    $numeroCuotas = $cuotas->first();
                    $gestionesCobro = [];

                    for ($i = 1; $i <= $numeroCuotas; $i++) {
                        $gestionesCobro[] = [
                            'venta_id' => $venta->id,
                            'cliente_id' => $venta->cliente_id,
                            'numero_cuota' => $i,
                            'total_cuotas' => $numeroCuotas,
                            'monto_cuota' => round($venta->total / $numeroCuotas, 2),
                            'fecha_vencimiento' => $venta->fecha_venta->addMonths($i)->toDateString(),
                            'estado' => 'pendiente',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    GestionCobro::insert($gestionesCobro);

                    Notification::make()
                        ->title('Éxito')
                        ->body("Se crearon {$numeroCuotas} gestiones de cobro.")
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Venta')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Información General')
                        ->icon('heroicon-m-document-text')
                        ->components([
                            Section::make('Encabezado')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('numero_venta')
                                        ->label('N° Venta')
                                        ->badge()
                                        ->color('primary'),

                                    TextEntry::make('fecha_venta')
                                        ->label('Fecha')
                                        ->dateTime('d/m/Y H:i'),

                                    TextEntry::make('estado')
                                        ->label('Estado')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'pendiente'  => 'warning',
                                            'completada' => 'success',
                                            'cancelada'  => 'danger',
                                            'devuelta'   => 'info',
                                            default      => 'gray',
                                        }),

                                    TextEntry::make('tipo_pago')
                                        ->label('Tipo de Pago')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'contado' => 'success',
                                            'credito' => 'warning',
                                            default   => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'contado' => 'Contado',
                                            'credito' => 'Crédito (Fiado)',
                                            default   => $state,
                                        }),

                                    TextEntry::make('dias_credito')
                                        ->label('Cuotas')
                                        ->formatStateUsing(function ($record) {
                                            $cuotas = $record->detalles
                                                ->pluck('cuotas')
                                                ->filter(fn ($c) => $c !== null)
                                                ->unique()
                                                ->values();

                                            if ($cuotas->count() === 1) {
                                                return $cuotas->first() . ' cuotas';
                                            } elseif ($cuotas->count() > 1) {
                                                return 'Mixto (' . $cuotas->implode(', ') . ')';
                                            }

                                            return ($record->dias_credito ?? 0) . ' días';
                                        })
                                        ->placeholder('—'),

                                    TextEntry::make('fecha_pago_limite')
                                        ->label('Plazo')
                                        ->formatStateUsing(function ($record) {
                                            $cuotas = $record->detalles
                                                ->pluck('cuotas')
                                                ->filter(fn ($c) => $c !== null)
                                                ->unique()
                                                ->values();

                                            if ($cuotas->count() === 1) {
                                                $meses = $cuotas->first();
                                                return $meses . ' mes' . ($meses > 1 ? 'es' : '');
                                            } elseif ($cuotas->count() > 1) {
                                                return 'Mixto';
                                            }

                                            return $record->fecha_pago_limite?->format('d/m/Y') ?? '—';
                                        })
                                        ->placeholder('—')
                                        ->color(fn ($record): string =>
                                            $record->tipo_pago === 'credito' && $record->saldo_pendiente > 0 && $record->fecha_pago_limite?->isPast()
                                                ? 'danger' : 'warning'
                                        ),

                                    TextEntry::make('user.name')
                                        ->label('Vendedor'),

                                    TextEntry::make('sucursal.nombre')
                                        ->label('Sucursal')
                                        ->placeholder('—'),
                                ]),

                            Section::make('Cliente')
                                ->icon('heroicon-m-user')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('cliente.nombre')
                                        ->label('Nombre')
                                        ->formatStateUsing(fn ($record) =>
                                            ($record->cliente?->nombre ?? '') . ' ' . ($record->cliente?->apellido ?? '')
                                        ),

                                    TextEntry::make('cliente.dui')
                                        ->label('DUI')
                                        ->placeholder('—'),

                                    TextEntry::make('cliente.telefono_normal')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('cliente.email')
                                        ->label('Email')
                                        ->placeholder('—'),

                                    TextEntry::make('cliente.saldo')
                                        ->label('Saldo Total Cliente')
                                        ->money('USD')
                                        ->color(fn ($state): string => (float)$state > 0 ? 'danger' : 'success'),
                                ]),
                        ]),

                    Tabs\Tab::make('Productos')
                        ->icon('heroicon-m-shopping-cart')
                        ->components([
                            Section::make('Detalle de Artículos')
                                ->columnSpanFull()
                                ->components([
                                    RepeatableEntry::make('detalles')
                                        ->label('')
                                        ->columns(4)
                                        ->schema([
                                            TextEntry::make('producto.nombre')
                                                ->label('Producto')
                                                ->columnSpan(2),

                                            TextEntry::make('cantidad')
                                                ->label('Cantidad'),

                                            TextEntry::make('precio_unitario')
                                                ->label('Precio Unit.')
                                                ->money('USD'),

                                            TextEntry::make('descuento_porcentaje')
                                                ->label('Descuento')
                                                ->suffix('%')
                                                ->placeholder('0%'),

                                            TextEntry::make('subtotal')
                                                ->label('Subtotal')
                                                ->money('USD')
                                                ->weight('semibold'),
                                        ]),
                                ]),

                            Section::make('Totales')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('subtotal')
                                        ->label('Subtotal')
                                        ->money('USD'),

                                    TextEntry::make('descuento_monto')
                                        ->label('Descuento')
                                        ->money('USD'),

                                    TextEntry::make('impuesto_monto')
                                        ->label('Impuesto')
                                        ->money('USD'),

                                    TextEntry::make('total')
                                        ->label('Total')
                                        ->money('USD')
                                        ->weight('bold')
                                        ->size('lg'),

                                    TextEntry::make('monto_pagado')
                                        ->label('Total Pagado')
                                        ->money('USD')
                                        ->color('success'),

                                    TextEntry::make('saldo_pendiente')
                                        ->label('Saldo Pendiente')
                                        ->money('USD')
                                        ->color(fn ($state): string => (float)$state > 0 ? 'danger' : 'success')
                                        ->weight('bold'),
                                ]),
                        ]),

                    Tabs\Tab::make('Pagos Recibidos')
                        ->icon('heroicon-m-banknotes')
                        ->components([
                            Section::make('Historial de Pagos')
                                ->description('Registro de todos los pagos recibidos para esta venta')
                                ->columnSpanFull()
                                ->components([
                                    RepeatableEntry::make('pagos')
                                        ->label('')
                                        ->columns(4)
                                        ->schema([
                                            TextEntry::make('fecha_pago')
                                                ->label('Fecha')
                                                ->date('d/m/Y'),

                                            TextEntry::make('monto')
                                                ->label('Monto')
                                                ->money('USD')
                                                ->weight('semibold')
                                                ->color('success'),

                                            TextEntry::make('metodo_pago')
                                                ->label('Método')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'efectivo'      => 'success',
                                                    'transferencia' => 'info',
                                                    'cheque'        => 'warning',
                                                    'deposito'      => 'primary',
                                                    default         => 'gray',
                                                }),

                                            TextEntry::make('referencia')
                                                ->label('Referencia')
                                                ->placeholder('—'),

                                            TextEntry::make('user.name')
                                                ->label('Registró')
                                                ->placeholder('—'),

                                            TextEntry::make('observaciones')
                                                ->label('Notas')
                                                ->placeholder('—')
                                                ->columnSpan(3),
                                        ]),
                                ]),
                        ]),

                    Tabs\Tab::make('Gestiones de Cobro')
                        ->icon('heroicon-m-calendar')
                        ->components([
                            Section::make('Plan de Cuotas')
                                ->description('Cronograma de vencimiento de cuotas')
                                ->columnSpanFull()
                                ->components([
                                    RepeatableEntry::make('gestionesCobro')
                                        ->label('')
                                        ->columns(5)
                                        ->schema([
                                            TextEntry::make('numero_cuota')
                                                ->label('Cuota')
                                                ->formatStateUsing(fn ($state, $record) => "{$state}/{$record->total_cuotas}"),

                                            TextEntry::make('monto_cuota')
                                                ->label('Monto')
                                                ->money('USD')
                                                ->weight('semibold'),

                                            TextEntry::make('fecha_vencimiento')
                                                ->label('Vencimiento')
                                                ->date('d/m/Y'),

                                            TextEntry::make('estado')
                                                ->label('Estado')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'pendiente' => 'warning',
                                                    'cobrado' => 'success',
                                                    'vencido' => 'danger',
                                                    default => 'gray',
                                                })
                                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                                    'pendiente' => 'Pendiente',
                                                    'cobrado' => 'Cobrado',
                                                    'vencido' => 'Vencido',
                                                    default => $state,
                                                }),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}