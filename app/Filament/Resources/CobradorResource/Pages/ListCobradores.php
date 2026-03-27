<?php

namespace App\Filament\Resources\CobradorResource\Pages;

use App\Filament\Resources\CobradorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\HasFilters;
use Illuminate\Database\Eloquent\Builder;

class ListCobradores extends ListRecords
{
    protected static string $resource = CobradorResource::class;

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
