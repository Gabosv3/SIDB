<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Cobrador;
use Illuminate\Auth\Access\HandlesAuthorization;

class CobradorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Cobrador');
    }

    public function view(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('View:Cobrador');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Cobrador');
    }

    public function update(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('Update:Cobrador');
    }

    public function delete(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('Delete:Cobrador');
    }

    public function restore(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('Restore:Cobrador');
    }

    public function forceDelete(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('ForceDelete:Cobrador');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Cobrador');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Cobrador');
    }

    public function replicate(AuthUser $authUser, Cobrador $cobrador): bool
    {
        return $authUser->can('Replicate:Cobrador');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Cobrador');
    }

}