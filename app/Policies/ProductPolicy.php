<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function manage(User $user): bool
    {
        return $user->can('products.manage');
    }

    public function toggleAvailability(User $user, Product $product): bool
    {
        return $user->can('products.toggle_availability');
    }
}
