<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Floor;
use Illuminate\Auth\Access\HandlesAuthorization;

class FloorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Floor');
    }

    public function view(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('View:Floor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Floor');
    }

    public function update(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('Update:Floor');
    }

    public function delete(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('Delete:Floor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Floor');
    }

    public function restore(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('Restore:Floor');
    }

    public function forceDelete(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('ForceDelete:Floor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Floor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Floor');
    }

    public function replicate(AuthUser $authUser, Floor $floor): bool
    {
        return $authUser->can('Replicate:Floor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Floor');
    }

}