<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Promotion;
use Carbon\Carbon;

class PromoHariBesarRule implements PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        $conditions = $promotion->conditions ?? [];

        // Check promotion master starts_at / ends_at first
        if ($promotion->starts_at && $now->lt($promotion->starts_at)) {
            return false;
        }
        if ($promotion->ends_at && $now->gt($promotion->ends_at)) {
            return false;
        }

        // Optional specific holiday dates array in conditions['holiday_dates']
        if (! empty($conditions['holiday_dates']) && is_array($conditions['holiday_dates'])) {
            return in_array($now->toDateString(), $conditions['holiday_dates'], true);
        }

        return true;
    }
}
