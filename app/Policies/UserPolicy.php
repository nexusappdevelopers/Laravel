<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermissionTo('users.delete');
    }

    /**
     * Determine whether the user can manage roles.
     *
     * @param User $user
     * @return bool
     */
    public function manageRoles(User $user): bool
    {
        return $user->hasPermissionTo('users.manage-roles');
    }

    /**
     * Determine whether the user can manage permissions.
     *
     * @param User $user
     * @return bool
     */
    public function managePermissions(User $user): bool
    {
        return $user->hasPermissionTo('users.manage-permissions');
    }

    /**
     * Determine whether the user can impersonate other users.
     *
     * @param User $user
     * @return bool
     */
    public function impersonate(User $user): bool
    {
        return $user->hasPermissionTo('users.impersonate');
    }
}
