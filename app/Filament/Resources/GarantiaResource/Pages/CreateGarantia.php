<?php

namespace App\Filament\Resources\GarantiaResource\Pages;

use App\Filament\Resources\GarantiaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGarantia extends CreateRecord
{
    protected static string $resource = GarantiaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reportado_por'] ??= auth()->id();
        $data['fecha_reporte'] ??= today()->toDateString();

        return $data;
    }
}
