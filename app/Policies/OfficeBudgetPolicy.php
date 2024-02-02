<?php

namespace App\Policies;

use App\Models\OfficeBudget;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OfficeBudgetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-office-budgets');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OfficeBudget $officeBudget): bool
    {
        return $user->hasPermissionTo('view-office-budgets');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-office-budgets');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OfficeBudget $officeBudget): bool
    {
        return $user->hasPermissionTo('update-office-budgets');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OfficeBudget $officeBudget): bool
    {
        return $user->hasPermissionTo('delete-office-budgets');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OfficeBudget $officeBudget): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OfficeBudget $officeBudget): bool
    {
        return false;
    }
}
