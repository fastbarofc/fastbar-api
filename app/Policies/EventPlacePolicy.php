<?php

namespace App\Policies;

use App\Models\EventPlace;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPlacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-event-places');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EventPlace $eventPlace): bool
    {
        return $user->hasPermissionTo('view-event-places');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-event-places');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EventPlace $eventPlace): bool
    {
        return $user->hasPermissionTo('update-event-places');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventPlace $eventPlace): bool
    {
        return $user->hasPermissionTo('delete-event-places');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EventPlace $eventPlace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EventPlace $eventPlace): bool
    {
        return false;
    }
}
