<?php

namespace App\Policies;

use App\Models\OfficeEvent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OfficeEventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-office-events');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OfficeEvent $officeEvent): bool
    {
        return $user->hasPermissionTo('view-office-events');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-office-events');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OfficeEvent $officeEvent): bool
    {
        return $user->hasPermissionTo('update-office-events');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OfficeEvent $officeEvent): bool
    {
        return $user->hasPermissionTo('delete-office-events');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OfficeEvent $officeEvent): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OfficeEvent $officeEvent): bool
    {
        return false;
    }
}
