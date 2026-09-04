<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EncuestaCliente;
use Illuminate\Auth\Access\HandlesAuthorization;

class EncuestaClientePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EncuestaCliente');
    }

    public function view(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('View:EncuestaCliente');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EncuestaCliente');
    }

    public function update(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('Update:EncuestaCliente');
    }

    public function delete(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('Delete:EncuestaCliente');
    }

    public function restore(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('Restore:EncuestaCliente');
    }

    public function forceDelete(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('ForceDelete:EncuestaCliente');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EncuestaCliente');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EncuestaCliente');
    }

    public function replicate(AuthUser $authUser, EncuestaCliente $encuestaCliente): bool
    {
        return $authUser->can('Replicate:EncuestaCliente');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EncuestaCliente');
    }

}