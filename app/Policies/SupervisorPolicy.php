<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Supervisor;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupervisorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Supervisor');
    }

    public function view(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('View:Supervisor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Supervisor');
    }

    public function update(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('Update:Supervisor');
    }

    public function delete(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('Delete:Supervisor');
    }

    public function restore(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('Restore:Supervisor');
    }

    public function forceDelete(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('ForceDelete:Supervisor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Supervisor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Supervisor');
    }

    public function replicate(AuthUser $authUser, Supervisor $supervisor): bool
    {
        return $authUser->can('Replicate:Supervisor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Supervisor');
    }

}