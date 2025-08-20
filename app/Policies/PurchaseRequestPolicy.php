<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchaseRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-purchase-requests');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('view-purchase-requests');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-purchase-requests');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('update-purchase-requests');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasPermissionTo('delete-purchase-requests');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return false;
    }
} 