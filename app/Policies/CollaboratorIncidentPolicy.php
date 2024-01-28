<?php

namespace App\Policies;

use App\Models\CollaboratorIncident;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CollaboratorIncidentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list-collaborator-incidents');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CollaboratorIncident $collaboratorIncident): bool
    {
        return $user->hasPermissionTo('view-collaborator-incidents');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-collaborator-incidents');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CollaboratorIncident $collaboratorIncident): bool
    {
        return $user->hasPermissionTo('update-collaborator-incidents');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CollaboratorIncident $collaboratorIncident): bool
    {
        return $user->hasPermissionTo('delete-collaborator-incidents');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CollaboratorIncident $collaboratorIncident): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CollaboratorIncident $collaboratorIncident): bool
    {
        return false;
    }
}
