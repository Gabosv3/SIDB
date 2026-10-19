<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ComisionTramo;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComisionTramoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ComisionTramo');
    }

    public function view(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('View:ComisionTramo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ComisionTramo');
    }

    public function update(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('Update:ComisionTramo');
    }

    public function delete(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('Delete:ComisionTramo');
    }

    public function restore(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('Restore:ComisionTramo');
    }

    public function forceDelete(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('ForceDelete:ComisionTramo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ComisionTramo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ComisionTramo');
    }

    public function replicate(AuthUser $authUser, ComisionTramo $comisionTramo): bool
    {
        return $authUser->can('Replicate:ComisionTramo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ComisionTramo');
    }

}