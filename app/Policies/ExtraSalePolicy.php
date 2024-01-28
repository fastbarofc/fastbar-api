<?php

namespace App\Policies;

use App\Models\ExtraSale;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExtraSalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-extra-sales');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExtraSale $extraSale): bool
    {
        return $user->hasPermissionTo('view-extra-sales');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-extra-sales');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExtraSale $extraSale): bool
    {
        return $user->hasPermissionTo('update-extra-sales');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExtraSale $extraSale): bool
    {
        return $user->hasPermissionTo('delete-extra-sales');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExtraSale $extraSale): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExtraSale $extraSale): bool
    {
        return false;
    }
}
