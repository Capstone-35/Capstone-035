<?php

namespace App\Domain\Loyalty;

use App\Domain\Promotion\PromotionEngine;
use App\Domain\Rfm\RfmService;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointLedger;
use App\Models\Promotion;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoyaltyService
{
    public function __construct(
        protected RfmService $rfmService,
        protected PromotionEngine $promotionEngine
    ) {}

    /**
     * Awards loyalty points when an order reaches the configured earn_on_status (D4: default Selesai, or Dibayar).
     * Never awards for Guest, unpaid, or cancelled orders (NFR-16).
     * Idempotent via unique(order_id, type) on point_ledger.
     */
    public function awardIfEligible(Order $order): ?PointLedger
    {
        if (! $order->member_id) {
            return null;
        }

        if ($order->status === OrderStatus::Dibatalkan || $order->status === OrderStatus::MenungguPembayaran) {
            return null;
        }

        $earnOnStatus = (string) Setting::getValue('loyalty.earn_on_status', OrderStatus::Selesai->value);
        if ($earnOnStatus === OrderStatus::Selesai->value && $order->status !== OrderStatus::Selesai) {
            return null;
        }

        if ($earnOnStatus === OrderStatus::Dibayar->value && ! $order->status->isPaidOrLater()) {
            return null;
        }

        $ledger = DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $existing = PointLedger::query()
                ->where('order_id', $lockedOrder->id)
                ->where('type', 'earn')
                ->first();

            if ($existing) {
                return $existing;
            }

            /** @var Member $member */
            $member = Member::query()->whereKey($lockedOrder->member_id)->lockForUpdate()->firstOrFail();

            // Check repeat order marker (FR-09): true if member has >= 1 earlier successful order
            $priorCount = Order::query()
                ->successful()
                ->where('member_id', $member->id)
                ->where('id', '!=', $lockedOrder->id)
                ->count();

            if ($priorCount >= 1 && ! $lockedOrder->is_repeat_order) {
                $lockedOrder->is_repeat_order = true;
                $lockedOrder->save();
            }

            // ASSUMPTION A2: 1 point per Rp10.000 of subtotal after discount (floor)
            $pointsPerAmount = max(1, (int) Setting::getValue('loyalty.points_per_amount', 10000));
            $netSubtotal = max(0, (int) $lockedOrder->subtotal - (int) $lockedOrder->discount_amount);
            $earnedPoints = (int) floor($netSubtotal / $pointsPerAmount);

            // Add bonus points if a bonus_points promotion was applied
            if ($lockedOrder->promotion_id) {
                $promo = Promotion::withTrashed()->find($lockedOrder->promotion_id);
                if ($promo && $promo->benefit_type === 'bonus_points') {
                    $earnedPoints += (int) $promo->benefit_value;
                }
            }

            $newBalance = max(0, (int) $member->points_balance + $earnedPoints);
            $member->points_balance = $newBalance;
            $member->save();

            return PointLedger::query()->create([
                'member_id' => $member->id,
                'order_id' => $lockedOrder->id,
                'type' => 'earn',
                'points' => $earnedPoints,
                'balance_after' => $newBalance,
                'note' => "Perolehan poin transaksi {$lockedOrder->order_number}",
                'created_at' => now(),
            ]);
        });

        // Update RFM and Promo Return Tracking for this Member
        $member = Member::find($order->member_id);
        if ($member) {
            $this->rfmService->recomputeForMember($member);
            $this->promotionEngine->evaluateReturnTrackingForOrder($order->fresh());
        }

        return $ledger;
    }

    /**
     * Reverses earned points and releases promo/voucher if a paid order is cancelled by Manager (D10).
     */
    public function reverseForCancelledOrder(Order $order): ?PointLedger
    {
        return DB::transaction(function () use ($order) {
            // Release reward voucher if it was attached
            if ($order->reward_redemption_id) {
                $redemption = RewardRedemption::query()
                    ->whereKey($order->reward_redemption_id)
                    ->lockForUpdate()
                    ->first();

                if ($redemption && in_array($redemption->status, ['active', 'used'], true)) {
                    $redemption->status = 'active';
                    $redemption->used_order_id = null;
                    $redemption->save();
                }
            }

            // Delete promotion usage if order was cancelled
            if ($order->promotionUsage) {
                $order->promotionUsage()->delete();
            }

            if (! $order->member_id) {
                return null;
            }

            $earnRow = PointLedger::query()
                ->where('order_id', $order->id)
                ->where('type', 'earn')
                ->first();

            if (! $earnRow) {
                return null;
            }

            $existingReversal = PointLedger::query()
                ->where('order_id', $order->id)
                ->where('type', 'reversal')
                ->first();

            if ($existingReversal) {
                return $existingReversal;
            }

            /** @var Member $member */
            $member = Member::query()->whereKey($order->member_id)->lockForUpdate()->firstOrFail();
            $pointsToReverse = (int) $earnRow->points;
            $newBalance = max(0, (int) $member->points_balance - $pointsToReverse);
            $member->points_balance = $newBalance;
            $member->save();

            return PointLedger::query()->create([
                'member_id' => $member->id,
                'order_id' => $order->id,
                'type' => 'reversal',
                'points' => -$pointsToReverse,
                'balance_after' => $newBalance,
                'note' => "Pembatalan poin transaksi {$order->order_number}",
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Redeems a reward for a Member using a row lock to prevent concurrent double-redeem.
     */
    public function redeemReward(Member $member, Reward $reward): RewardRedemption
    {
        if (! $reward->is_active) {
            throw ValidationException::withMessages([
                'reward' => 'Reward sedang tidak aktif.',
            ]);
        }

        return DB::transaction(function () use ($member, $reward) {
            /** @var Member $lockedMember */
            $lockedMember = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            $cost = (int) $reward->points_cost;
            if ($lockedMember->points_balance < $cost) {
                $shortfall = $cost - $lockedMember->points_balance;
                throw ValidationException::withMessages([
                    'points' => "Poin belum cukup (kurang {$shortfall} poin).",
                ]);
            }

            $newBalance = (int) $lockedMember->points_balance - $cost;
            $lockedMember->points_balance = $newBalance;
            $lockedMember->save();

            $redemption = RewardRedemption::query()->create([
                'member_id' => $lockedMember->id,
                'reward_id' => $reward->id,
                'voucher_code' => 'RWD-'.strtoupper(Str::random(8)),
                'points_spent' => $cost,
                'status' => 'active',
                'redeemed_at' => now(),
                'expires_at' => now()->addDays(max(1, (int) $reward->valid_days)),
            ]);

            PointLedger::query()->create([
                'member_id' => $lockedMember->id,
                'reward_redemption_id' => $redemption->id,
                'type' => 'redeem',
                'points' => -$cost,
                'balance_after' => $newBalance,
                'note' => "Penukaran reward: {$reward->name} ({$redemption->voucher_code})",
                'created_at' => now(),
            ]);

            return $redemption->load('reward');
        });
    }
}
