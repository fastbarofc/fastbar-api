<?php

namespace App\Policies;

use App\Models\EventGift;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventGiftPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-event-gifts');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EventGift $eventGift): bool
    {
        return $user->hasPermissionTo('view-event-gifts');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-event-gifts');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EventGift $eventGift): bool
    {
        return $user->hasPermissionTo('update-event-gifts');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventGift $eventGift): bool
    {
        return $user->hasPermissionTo('delete-event-gifts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EventGift $eventGift): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EventGift $eventGift): bool
    {
        return false;
    }
}
