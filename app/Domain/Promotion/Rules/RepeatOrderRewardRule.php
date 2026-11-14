<?php

namespace App\Domain\Promotion\Rules;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Models\Member;
use App\Models\Order;
use App\Models\Promotion;
use Carbon\Carbon;

class RepeatOrderRewardRule implements PromotionRule
{
    public function evaluate(Promotion $promotion, Member $member, int $subtotal, Carbon $now): bool
    {
        $conditions = $promotion->conditions ?? [];
        // ASSUMPTION A4: Repeat Order Reward = 5 successful transactions in 30 days
        $requiredOrders = (int) ($conditions['required_orders'] ?? 5);
        $windowDays = (int) ($conditions['window_days'] ?? 30);

        $windowStart = $now->copy()->subDays($windowDays)->startOfDay();

        $count = Order::query()
            ->successful()
            ->where('member_id', $member->id)
            ->whereBetween('created_at', [$windowStart, $now])
            ->count();

        return $count >= $requiredOrders;
    }
}
