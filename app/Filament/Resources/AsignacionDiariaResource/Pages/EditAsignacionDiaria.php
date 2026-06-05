<?php

namespace App\Filament\Resources\AsignacionDiariaResource\Pages;

use App\Filament\Resources\AsignacionDiariaResource;
use App\Models\AsignacionDiaria;
use Filament\Resources\Pages\EditRecord;

class EditAsignacionDiaria extends EditRecord
{
    protected static string $resource = AsignacionDiariaResource::class;

    public function mount(string|int $record): void
    {
        redirect()->route('asignacion-diaria.editar', $record);
    }
}
