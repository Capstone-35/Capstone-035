<?php

namespace App\Domain\Promotion\Contracts;

use App\Models\Member;
use App\Models\Promotion;
use Carbon\Carbon;

interface PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool;
}
