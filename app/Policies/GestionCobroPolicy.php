<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GestionCobro;
use Illuminate\Auth\Access\HandlesAuthorization;

class GestionCobroPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GestionCobro');
    }

    public function view(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('View:GestionCobro');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GestionCobro');
    }

    public function update(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('Update:GestionCobro');
    }

    public function delete(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('Delete:GestionCobro');
    }

    public function restore(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('Restore:GestionCobro');
    }

    public function forceDelete(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('ForceDelete:GestionCobro');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GestionCobro');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GestionCobro');
    }

    public function replicate(AuthUser $authUser, GestionCobro $gestionCobro): bool
    {
        return $authUser->can('Replicate:GestionCobro');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GestionCobro');
    }

}