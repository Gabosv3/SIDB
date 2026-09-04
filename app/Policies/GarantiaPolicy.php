<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Garantia;
use Illuminate\Auth\Access\HandlesAuthorization;

class GarantiaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Garantia');
    }

    public function view(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('View:Garantia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Garantia');
    }

    public function update(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('Update:Garantia');
    }

    public function delete(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('Delete:Garantia');
    }

    public function restore(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('Restore:Garantia');
    }

    public function forceDelete(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('ForceDelete:Garantia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Garantia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Garantia');
    }

    public function replicate(AuthUser $authUser, Garantia $garantia): bool
    {
        return $authUser->can('Replicate:Garantia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Garantia');
    }

}