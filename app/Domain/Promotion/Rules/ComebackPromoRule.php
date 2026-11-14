<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Order;
use App\Models\Promotion;
use Carbon\Carbon;

class ComebackPromoRule implements PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        $conditions = $promotion->conditions ?? [];
        // ASSUMPTION A4: Comeback inactivity threshold = 30 days (alt. 60)
        $inactiveDays = (int) ($conditions['inactive_days'] ?? 30);

        $lastSuccessfulOrder = Order::query()
            ->successful()
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->first(['id', 'created_at']);

        if (! $lastSuccessfulOrder) {
            return false; // Member must have at least 1 prior order to "come back"
        }

        $daysSinceLast = Carbon::parse($lastSuccessfulOrder->created_at)->startOfDay()
            ->diffInDays($now->copy()->startOfDay());

        if ($daysSinceLast >= $inactiveDays) {
            return true;
        }

        // Or member is in an explicitly targeted at-risk/inactive RFM segment if configured
        $allowSegmentFallback = (bool) ($conditions['match_target_segment'] ?? false);
        if ($allowSegmentFallback && ! empty($promotion->target_segments)) {
            $latestRfm = $member->latestRfmResult()->with('segment')->first();
            $slug = $latestRfm?->segment?->slug;

            return $slug && in_array($slug, (array) $promotion->target_segments, true);
        }

        return false;
    }
}
