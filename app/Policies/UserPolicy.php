<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.manage_staff') || $actor->can('users.manage_all');
    }

    public function manage(User $actor, ?User $target = null, ?string $requestedRole = null): bool
    {
        if ($actor->can('users.manage_all')) {
            return true;
        }

        if (! $actor->can('users.manage_staff')) {
            return false;
        }

        // Manager cannot create or modify an Owner account (Section 5.2)
        if ($target && $target->hasRole('owner')) {
            return false;
        }

        if ($requestedRole === 'owner') {
            return false;
        }

        return true;
    }
}
