<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Utensil;
use Illuminate\Auth\Access\Response;

class UtensilPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-utensils');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Utensil $utensil): bool
    {
        return $user->hasPermissionTo('view-utensils');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-utensils');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Utensil $utensil): bool
    {
        return $user->hasPermissionTo('update-utensils');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Utensil $utensil): bool
    {
        return $user->hasPermissionTo('delete-utensils');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Utensil $utensil): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Utensil $utensil): bool
    {
        return false;
    }
}
