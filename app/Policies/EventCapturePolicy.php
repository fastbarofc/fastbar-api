<?php

namespace App\Policies;

use App\Models\EventCapture;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventCapturePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-event-captures');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EventCapture $eventCapture): bool
    {
        return $user->hasPermissionTo('view-event-captures');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-event-captures');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EventCapture $eventCapture): bool
    {
        return $user->hasPermissionTo('update-event-captures');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventCapture $eventCapture): bool
    {
        return $user->hasPermissionTo('delete-event-captures');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EventCapture $eventCapture): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EventCapture $eventCapture): bool
    {
        return false;
    }
}
