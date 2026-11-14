<?php

namespace App\Domain\Promotion;

use App\Domain\Promotion\Contracts\PromotionRule;
use App\Domain\Promotion\Rules\ComebackPromoRule;
use App\Domain\Promotion\Rules\PromoHariBesarRule;
use App\Domain\Promotion\Rules\PromoReturnTrackingRule;
use App\Domain\Promotion\Rules\RepeatOrderRewardRule;
use App\Domain\Promotion\Rules\SlowHourPromoRule;
use App\Domain\Promotion\Rules\WelcomeNextVisitPromoRule;
use App\Enums\PromotionRuleType;
use App\Models\Member;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionReturn;
use App\Models\PromotionUsage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionEngine
{
    /**
     * @return array<string, PromotionRule>
     */
    protected function rules(): array
    {
        return [
            PromotionRuleType::Comeback->value => new ComebackPromoRule,
            PromotionRuleType::RepeatOrder->value => new RepeatOrderRewardRule,
            PromotionRuleType::HariBesar->value => new PromoHariBesarRule,
            PromotionRuleType::WelcomeNextVisit->value => new WelcomeNextVisitPromoRule,
            PromotionRuleType::SlowHour->value => new SlowHourPromoRule,
            PromotionRuleType::ReturnTracking->value => new PromoReturnTrackingRule,
        ];
    }

    /**
     * Returns all promotions eligible for the given Member, cart subtotal, and timestamp.
     *
     * @return Collection<int, Promotion>
     */
    public function eligiblePromotions(?Member $member, int $subtotal = 0, ?Carbon $now = null): Collection
    {
        if (! $member) {
            return collect();
        }

        $now = $now ? $now->copy() : now();

        $candidates = Promotion::query()
            ->where('is_active', true)
            ->where('rule_type', '!=', PromotionRuleType::ReturnTracking->value)
            ->get();

        return $candidates->filter(fn (Promotion $promo) => $this->isEligible($promo, $member, $subtotal, $now))->values();
    }

    public function isEligible(Promotion $promotion, Member $member, int $subtotal, ?Carbon $now = null): bool
    {
        $now = $now ? $now->copy() : now();

        if (! $promotion->is_active) {
            return false;
        }

        if ($promotion->rule_type === PromotionRuleType::ReturnTracking) {
            return false;
        }

        if ($promotion->starts_at && $now->lt($promotion->starts_at)) {
            return false;
        }

        if ($promotion->ends_at && $now->gt($promotion->ends_at)) {
            return false;
        }

        if ($promotion->min_transaction > 0 && $subtotal < $promotion->min_transaction) {
            return false;
        }

        // Check total usage limit
        if ($promotion->usage_limit !== null && $promotion->usage_limit > 0) {
            $totalUsed = PromotionUsage::query()->where('promotion_id', $promotion->id)->count();
            if ($totalUsed >= $promotion->usage_limit) {
                return false;
            }
        }

        // Check per-member usage limit
        if ($promotion->per_member_limit !== null && $promotion->per_member_limit > 0) {
            $memberUsed = PromotionUsage::query()
                ->where('promotion_id', $promotion->id)
                ->where('member_id', $member->id)
                ->count();
            if ($memberUsed >= $promotion->per_member_limit) {
                return false;
            }
        }

        // Check target RFM segments (unless target_segments contains 'all' or is empty)
        $targets = array_filter((array) ($promotion->target_segments ?? []));
        if (! empty($targets) && ! in_array('all', $targets, true)) {
            // For Comeback promo, if inactive_days passes, allow even if RFM hasn't been re-run yet, OR check segment
            $latestRfm = $member->latestRfmResult()->with('segment')->first();
            $memberSegmentSlug = $latestRfm?->segment?->slug ?? 'belum-aktif';
            $memberSegmentName = $latestRfm?->segment_name ?? 'Belum Aktif';

            $segmentMatched = in_array($memberSegmentSlug, $targets, true)
                || in_array($memberSegmentName, $targets, true);

            if (! $segmentMatched && $promotion->rule_type !== PromotionRuleType::Comeback) {
                return false;
            }
        }

        $ruleEvaluator = $this->rules()[$promotion->rule_type->value] ?? null;
        if (! $ruleEvaluator) {
            return false;
        }

        return $ruleEvaluator->evaluate($promotion, $member, $subtotal, $now);
    }

    /**
     * Records promotion usage ONLY when payment is confirmed (OrderPaid).
     * Idempotent via unique(order_id) on promotion_usages.
     */
    public function recordUsageForPaidOrder(Order $order): ?PromotionUsage
    {
        if (! $order->promotion_id || ! $order->member_id) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $existing = PromotionUsage::query()->where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $promotion = Promotion::withTrashed()->find($order->promotion_id);
            $bonusPoints = ($promotion && $promotion->benefit_type === 'bonus_points')
                ? (int) $promotion->benefit_value
                : 0;

            $usage = PromotionUsage::query()->create([
                'promotion_id' => $order->promotion_id,
                'order_id' => $order->id,
                'member_id' => $order->member_id,
                'discount_amount' => $order->discount_amount,
                'bonus_points' => $bonusPoints,
                'used_at' => $order->paid_at ?? now(),
            ]);

            // Create initial Promo Return Tracking record (Rule 6)
            PromotionReturn::query()->firstOrCreate(
                ['promotion_usage_id' => $usage->id],
                [
                    'member_id' => $order->member_id,
                    'evaluated_at' => now(),
                ]
            );

            return $usage;
        });
    }

    /**
     * Rule 6 — Promo Return Tracking:
     * When a Member completes a subsequent successful order after using a promo,
     * link the return visit (returned_order_id, days_to_return, return_order_value).
     */
    public function evaluateReturnTrackingForOrder(Order $order): void
    {
        if (! $order->member_id) {
            return;
        }

        $openReturns = PromotionReturn::query()
            ->with('usage')
            ->where('member_id', $order->member_id)
            ->whereNull('returned_order_id')
            ->get();

        foreach ($openReturns as $returnRow) {
            $usage = $returnRow->usage;
            if (! $usage || $usage->order_id === $order->id) {
                continue;
            }

            $orderTimestamp = $order->completed_at ?? $order->paid_at ?? $order->created_at;
            if ($orderTimestamp->lte($usage->used_at)) {
                continue;
            }

            $days = max(0, (int) $usage->used_at->copy()->startOfDay()->diffInDays($orderTimestamp->copy()->startOfDay()));

            $returnRow->update([
                'returned_order_id' => $order->id,
                'days_to_return' => $days,
                'return_order_value' => $order->total_amount,
                'evaluated_at' => now(),
            ]);
        }
    }
}
