<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Vale;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vale');
    }

    public function view(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('View:Vale');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vale');
    }

    public function update(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('Update:Vale');
    }

    public function delete(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('Delete:Vale');
    }

    public function restore(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('Restore:Vale');
    }

    public function forceDelete(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('ForceDelete:Vale');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vale');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vale');
    }

    public function replicate(AuthUser $authUser, Vale $vale): bool
    {
        return $authUser->can('Replicate:Vale');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vale');
    }

}