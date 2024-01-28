<?php

namespace App\Policies;

use App\Models\EventStatus;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventStatusPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-event-statuses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EventStatus $eventStatus): bool
    {
        return $user->hasPermissionTo('view-event-statuses');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-event-statuses');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EventStatus $eventStatus): bool
    {
        return $user->hasPermissionTo('update-event-statuses');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventStatus $eventStatus): bool
    {
        return $user->hasPermissionTo('delete-event-statuses');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EventStatus $eventStatus): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EventStatus $eventStatus): bool
    {
        return false;
    }
}
