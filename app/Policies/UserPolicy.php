<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->can('users.view');
    }

    public function view(User $auth, User $model): bool
    {
        return $auth->can('users.view');
    }

    public function create(User $auth): bool
    {
        return $auth->can('users.manage');
    }

    public function update(User $auth, User $model): bool
    {
        return $auth->can('users.manage');
    }

    public function delete(User $auth, User $model): bool
    {
        if (! $auth->can('users.manage')) {
            return false;
        }
        if ((int) $auth->id === (int) $model->id) {
            return false;
        }

        return true;
    }
}
