<?php

namespace App\Filament\Resources\GestionCobroResource\Pages;

use App\Filament\Resources\GestionCobroResource;
use App\Models\PagoVenta;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditGestionCobro extends EditRecord
{
    protected static string $resource = GestionCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrarPago')
                ->label('Registrar Pago')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('monto')
                        ->label('Monto a Pagar')
                        ->prefix('$')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->default(fn ($record) => $record->monto_cuota)
                        ->helperText('Monto de la cuota: $' . $this->record->monto_cuota),

                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de Pago')
                        ->options([
                            'efectivo' => 'Efectivo',
                            'transferencia' => 'Transferencia',
                            'cheque' => 'Cheque',
                            'deposito' => 'Depósito',
                        ])
                        ->default('efectivo')
                        ->required(),

                    Forms\Components\TextInput::make('referencia')
                        ->label('Referencia / N° de transacción')
                        ->placeholder('Ej: TRF-00123')
                        ->maxLength(100),

                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $gestion = $this->record;
                    $monto = (float)$data['monto'];

                    // Validar que no haya saldo pendiente en cuota anterior
                    if ($gestion->numero_cuota > 1) {
                        $cuotaAnterior = $gestion->venta->gestionesCobro()
                            ->where('numero_cuota', $gestion->numero_cuota - 1)
                            ->first();

                        $saldoPendiente = $cuotaAnterior->monto_cuota - $cuotaAnterior->monto_pagado;
                        if ($saldoPendiente > 0) {
                            Notification::make()
                                ->title('Error')
                                ->body("Aún hay \${$saldoPendiente} pendiente en la cuota anterior")
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // Calcular saldo pendiente después del pago
                    $saldoPendiente = $gestion->monto_cuota - ($gestion->monto_pagado + $monto);

                    // Validar que no se pague más de lo debido
                    if ($saldoPendiente < 0) {
                        $pendiente = $gestion->monto_cuota - $gestion->monto_pagado;
                        Notification::make()
                            ->title('Error')
                            ->body("El monto no puede exceder \${$pendiente} pendiente")
                            ->danger()
                            ->send();
                        return;
                    }

                    // Crear registro en PagoVenta
                    PagoVenta::create([
                        'venta_id' => $gestion->venta_id,
                        'cliente_id' => $gestion->cliente_id,
                        'monto' => $monto,
                        'fecha_pago' => today(),
                        'metodo_pago' => $data['metodo_pago'],
                        'referencia' => $data['referencia'] ?? null,
                        'observaciones' => $data['observaciones'] ?? null,
                        'user_id' => auth()->id(),
                    ]);

                    // Actualizar monto_pagado
                    $nuevoMontoPagado = $gestion->monto_pagado + $monto;

                    // Determinar estado
                    if ($nuevoMontoPagado >= $gestion->monto_cuota) {
                        $estado = 'cobrado';
                        $mensaje = "Cuota pagada completamente";
                    } else {
                        $estado = 'parcialmente_cobrado';
                        $mensaje = "Pago parcial registrado (\${$monto} de \${$gestion->monto_cuota})";
                    }

                    // Actualizar GestionCobro
                    $gestion->update([
                        'monto_pagado' => $nuevoMontoPagado,
                        'estado' => $estado,
                    ]);

                    // Actualizar saldo pendiente de la venta
                    $venta = $gestion->venta;
                    $venta->monto_pagado += $monto;
                    $venta->saldo_pendiente = max(0, $venta->saldo_pendiente - $monto);
                    if ($venta->saldo_pendiente == 0) {
                        $venta->estado = 'completada';
                    }
                    $venta->save();

                    Notification::make()
                        ->title('Éxito')
                        ->body($mensaje)
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),
        ];
    }
}
