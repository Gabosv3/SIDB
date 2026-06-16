<?php

namespace App\Filament\Resources\ReintegroResource\Pages;

use App\Filament\Resources\ReintegroResource;
use App\Models\GestionCobro;
use Filament\Resources\Pages\CreateRecord;

class CreateReintegro extends CreateRecord
{
    protected static string $resource = ReintegroResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcular monto_adeudado y cuotas_vencidas al momento de crear
        if (! empty($data['venta_id'])) {
            $stats = GestionCobro::where('venta_id', $data['venta_id'])
                ->whereIn('estado', ['pendiente', 'parcialmente_cobrado'])
                ->whereDate('fecha_vencimiento', '<=', today())
                ->selectRaw('COUNT(*) as cuotas_vencidas, COALESCE(SUM(monto_cuota - monto_pagado), 0) as monto_adeudado')
                ->first();

            $data['monto_adeudado'] = $stats?->monto_adeudado ?? 0;
            $data['cuotas_vencidas'] = $stats?->cuotas_vencidas ?? 0;
        }

        $data['asignado_por'] = auth()->id();

        return $data;
    }
}
