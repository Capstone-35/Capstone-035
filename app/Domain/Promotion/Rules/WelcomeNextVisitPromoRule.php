<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Order;
use App\Models\Promotion;
use Carbon\Carbon;

class WelcomeNextVisitPromoRule implements PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        $conditions = $promotion->conditions ?? [];
        $mode = $conditions['mode'] ?? 'both'; // 'welcome' (0 orders), 'next_visit' (>=1 recent order), or 'both'
        $validDays = (int) ($conditions['valid_days'] ?? 14);

        $orders = Order::query()
            ->successful()
            ->where('member_id', $member->id)
            ->orderByDesc('created_at')
            ->get(['id', 'created_at']);

        $count = $orders->count();

        if ($mode === 'welcome') {
            return $count === 0;
        }

        if ($mode === 'next_visit') {
            if ($count < 1) {
                return false;
            }
            $lastOrderAt = Carbon::parse($orders->first()->created_at);

            return $lastOrderAt->diffInDays($now) <= $validDays;
        }

        // 'both': either welcome (0 orders) or next visit within valid_days of a prior order
        if ($count === 0) {
            return true;
        }

        $lastOrderAt = Carbon::parse($orders->first()->created_at);

        return $lastOrderAt->diffInDays($now) <= $validDays;
    }
}
