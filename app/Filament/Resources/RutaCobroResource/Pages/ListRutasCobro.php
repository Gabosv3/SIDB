<?php

namespace App\Filament\Resources\RutaCobroResource\Pages;

use App\Filament\Resources\RutaCobroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRutasCobro extends ListRecords
{
    protected static string $resource = RutaCobroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['sucursal_id'] = $this->tenant->id;
                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('sucursal_id', $this->tenant->id ?? null);
    }
}
