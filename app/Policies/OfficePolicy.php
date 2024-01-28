<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Office;

class OfficePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-offices');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Office $office): bool
    {
        return $user->hasPermissionTo('view-offices');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-offices');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Office $office): bool
    {
        return $user->hasPermissionTo('update-offices');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Office $office): bool
    {
        return $user->hasPermissionTo('delete-offices');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Office $office): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Office $office): bool
    {
        return false;
    }
}
