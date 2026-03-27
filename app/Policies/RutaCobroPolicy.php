<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RutaCobro;
use Illuminate\Auth\Access\HandlesAuthorization;

class RutaCobroPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RutaCobro');
    }

    public function view(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('View:RutaCobro');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RutaCobro');
    }

    public function update(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('Update:RutaCobro');
    }

    public function delete(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('Delete:RutaCobro');
    }

    public function restore(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('Restore:RutaCobro');
    }

    public function forceDelete(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('ForceDelete:RutaCobro');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RutaCobro');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RutaCobro');
    }

    public function replicate(AuthUser $authUser, RutaCobro $rutaCobro): bool
    {
        return $authUser->can('Replicate:RutaCobro');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RutaCobro');
    }

}