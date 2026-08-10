<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AsignacionDiaria;
use Illuminate\Auth\Access\HandlesAuthorization;

class AsignacionDiariaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AsignacionDiaria');
    }

    public function view(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('View:AsignacionDiaria');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AsignacionDiaria');
    }

    public function update(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('Update:AsignacionDiaria');
    }

    public function delete(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('Delete:AsignacionDiaria');
    }

    public function restore(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('Restore:AsignacionDiaria');
    }

    public function forceDelete(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('ForceDelete:AsignacionDiaria');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AsignacionDiaria');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AsignacionDiaria');
    }

    public function replicate(AuthUser $authUser, AsignacionDiaria $asignacionDiaria): bool
    {
        return $authUser->can('Replicate:AsignacionDiaria');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AsignacionDiaria');
    }

}