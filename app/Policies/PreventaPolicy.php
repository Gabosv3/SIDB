<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Preventa;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreventaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Preventa');
    }

    public function view(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('View:Preventa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Preventa');
    }

    public function update(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('Update:Preventa');
    }

    public function delete(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('Delete:Preventa');
    }

    public function restore(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('Restore:Preventa');
    }

    public function forceDelete(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('ForceDelete:Preventa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Preventa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Preventa');
    }

    public function replicate(AuthUser $authUser, Preventa $preventa): bool
    {
        return $authUser->can('Replicate:Preventa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Preventa');
    }

}