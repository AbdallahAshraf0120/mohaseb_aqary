<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('properties.manage');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->hasPermission('properties.manage');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->isAdmin();
    }
}
