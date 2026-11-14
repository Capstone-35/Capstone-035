<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Promotion;
use Carbon\Carbon;

class PromoReturnTrackingRule implements PromotionRule
{
    /**
     * Promo Return Tracking is passive (Section 6 Module J):
     * It is not a checkout discount, so it never appears as a selectable checkout discount card.
     * Instead, PromotionEngine::evaluateReturnTracking() records member revisit metrics.
     */
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        return false;
    }
}
