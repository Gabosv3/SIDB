<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MovimientoStock;
use Illuminate\Auth\Access\HandlesAuthorization;

class MovimientoStockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MovimientoStock');
    }

    public function view(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('View:MovimientoStock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MovimientoStock');
    }

    public function update(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('Update:MovimientoStock');
    }

    public function delete(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('Delete:MovimientoStock');
    }

    public function restore(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('Restore:MovimientoStock');
    }

    public function forceDelete(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('ForceDelete:MovimientoStock');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MovimientoStock');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MovimientoStock');
    }

    public function replicate(AuthUser $authUser, MovimientoStock $movimientoStock): bool
    {
        return $authUser->can('Replicate:MovimientoStock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MovimientoStock');
    }

}