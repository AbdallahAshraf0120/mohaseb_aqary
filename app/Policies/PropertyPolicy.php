<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'accountant', 'sales', 'viewer'], true);
    }

    public function view(User $user, Property $property): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'sales'], true);
    }

    public function update(User $user, Property $property): bool
    {
        return in_array($user->role, ['admin', 'sales'], true);
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->role === 'admin';
    }
}
