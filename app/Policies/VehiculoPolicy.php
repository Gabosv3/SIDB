<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Vehiculo;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiculoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Vehiculo');
    }

    public function view(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('View:Vehiculo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Vehiculo');
    }

    public function update(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('Update:Vehiculo');
    }

    public function delete(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('Delete:Vehiculo');
    }

    public function restore(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('Restore:Vehiculo');
    }

    public function forceDelete(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('ForceDelete:Vehiculo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Vehiculo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Vehiculo');
    }

    public function replicate(AuthUser $authUser, Vehiculo $vehiculo): bool
    {
        return $authUser->can('Replicate:Vehiculo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Vehiculo');
    }

}