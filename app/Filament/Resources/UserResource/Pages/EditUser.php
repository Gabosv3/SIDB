<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $estadoAnterior = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $this->estadoAnterior = $this->record->getOriginal('account_status');
    }

    // Si el estado pasó de "activa" a bloqueada/desactivada, se cierran todas
    // las sesiones y tokens del usuario — mismo comportamiento que el botón
    // "Bloquear acceso" del perfil de empleado.
    protected function afterSave(): void
    {
        if ($this->estadoAnterior === 'activa' && $this->record->account_status !== 'activa') {
            $this->record->cerrarTodasLasSesiones();
        }
    }
}
