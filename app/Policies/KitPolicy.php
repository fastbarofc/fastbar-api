<?php

namespace App\Policies;

use App\Models\Kit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-kits');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Kit $kit): bool
    {
        return $user->hasPermissionTo('view-kits');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-kits');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Kit $kit): bool
    {
        return $user->hasPermissionTo('update-kits');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Kit $kit): bool
    {
        return $user->hasPermissionTo('delete-kits');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Kit $kit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Kit $kit): bool
    {
        return false;
    }
}
