<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Reintegro;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReintegroPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reintegro');
    }

    public function view(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('View:Reintegro');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reintegro');
    }

    public function update(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('Update:Reintegro');
    }

    public function delete(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('Delete:Reintegro');
    }

    public function restore(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('Restore:Reintegro');
    }

    public function forceDelete(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('ForceDelete:Reintegro');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Reintegro');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Reintegro');
    }

    public function replicate(AuthUser $authUser, Reintegro $reintegro): bool
    {
        return $authUser->can('Replicate:Reintegro');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Reintegro');
    }

}