<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Apartment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApartmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Apartment');
    }

    public function view(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('View:Apartment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Apartment');
    }

    public function update(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('Update:Apartment');
    }

    public function delete(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('Delete:Apartment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Apartment');
    }

    public function restore(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('Restore:Apartment');
    }

    public function forceDelete(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('ForceDelete:Apartment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Apartment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Apartment');
    }

    public function replicate(AuthUser $authUser, Apartment $apartment): bool
    {
        return $authUser->can('Replicate:Apartment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Apartment');
    }

}