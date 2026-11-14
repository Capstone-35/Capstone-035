<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('promotions.view');
    }

    public function manage(User $user, ?Promotion $promotion = null): bool
    {
        return $user->can('promotions.manage');
    }
}
