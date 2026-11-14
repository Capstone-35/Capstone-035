<?php

namespace App\Domain\Ordering;

use App\Models\Member;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\RewardRedemption;
use App\Models\Setting;
use App\Domain\Promotion\PromotionEngine;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrderPricingService
{
    public function __construct(
        protected PromotionEngine $promotionEngine
    ) {}

    /**
     * Computes authoritative server-side totals from database product prices.
     * Browser-sent prices/totals are completely ignored (Section 6 Module C / Section 18).
     *
     * @param  array<int, array{product_id: int, quantity: int, note?: ?string}>  $cartLines
     * @return array{
     *   items: array<int, array{product_id: int, product_name: string, unit_price: int, quantity: int, note: ?string, line_total: int}>,
     *   subtotal: int,
     *   discount_amount: int,
     *   tax_rate: float,
     *   tax_amount: int,
     *   total_amount: int,
     *   promotion_id: ?int,
     *   reward_redemption_id: ?int,
     *   applied_label: ?string,
     *   bonus_points: int
     * }
     */
    public function calculate(
        array $cartLines,
        ?Member $member = null,
        ?int $promotionId = null,
        ?int $rewardRedemptionId = null,
        bool $strictAvailability = true
    ): array {
        if (empty($cartLines)) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang pesanan kosong. Silakan pilih menu terlebih dahulu.',
            ]);
        }

        // D14 / ASSUMPTION A5: At most ONE promotion OR ONE reward redemption per order
        if ($promotionId && $rewardRedemptionId) {
            throw ValidationException::withMessages([
                'promotion_id' => 'Hanya satu promo atau satu voucher reward yang dapat digunakan per pesanan.',
            ]);
        }

        $productIds = collect($cartLines)->pluck('product_id')->unique()->values()->all();
        /** @var Collection<int, Product> $products */
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $validatedItems = [];
        $unavailableNames = [];
        $subtotal = 0;

        foreach ($cartLines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            $note = isset($line['note']) ? mb_substr(trim(strip_tags((string) $line['note'])), 0, 200) : null;

            if ($qty < 1 || $qty > 99) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah item harus antara 1 sampai 99.',
                ]);
            }

            $product = $products->get($productId);
            if (! $product || ! $product->is_active) {
                $unavailableNames[] = $product?->name ?? "Produk #{$productId}";
                continue;
            }

            if ($strictAvailability && ! $product->is_available) {
                $unavailableNames[] = $product->name;
                continue;
            }

            $unitPrice = (int) $product->price;
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $validatedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'note' => $note ?: null,
                'line_total' => $lineTotal,
            ];
        }

        if (! empty($unavailableNames)) {
            throw ValidationException::withMessages([
                'unavailable_items' => 'Menu berikut sedang habis atau tidak tersedia: '.implode(', ', $unavailableNames).'. Silakan hapus dari keranjang.',
            ]);
        }

        if (empty($validatedItems)) {
            throw ValidationException::withMessages([
                'cart' => 'Tidak ada item valid di keranjang.',
            ]);
        }

        $discountAmount = 0;
        $appliedPromotionId = null;
        $appliedRedemptionId = null;
        $appliedLabel = null;
        $bonusPoints = 0;

        // D13: Promotions and reward vouchers apply to Members only
        if ($promotionId) {
            if (! $member) {
                throw ValidationException::withMessages([
                    'promotion_id' => 'Promo hanya dapat digunakan oleh Member terdaftar.',
                ]);
            }

            $promotion = Promotion::query()->find($promotionId);
            if (! $promotion || ! $this->promotionEngine->isEligible($promotion, $member, $subtotal, now())) {
                throw ValidationException::withMessages([
                    'promotion_id' => 'Promo sudah tidak berlaku atau belum memenuhi syarat transaksi.',
                ]);
            }

            $discountAmount = $promotion->calculateDiscount($subtotal);
            if ($promotion->benefit_type === 'bonus_points') {
                $bonusPoints = (int) $promotion->benefit_value;
            }
            $appliedPromotionId = $promotion->id;
            $appliedLabel = $promotion->name;
        } elseif ($rewardRedemptionId) {
            if (! $member) {
                throw ValidationException::withMessages([
                    'reward_redemption_id' => 'Voucher reward hanya dapat digunakan oleh Member.',
                ]);
            }

            $redemption = RewardRedemption::query()
                ->with('reward')
                ->where('id', $rewardRedemptionId)
                ->where('member_id', $member->id)
                ->first();

            if (! $redemption || ! $redemption->isUsable() || ! $redemption->reward) {
                throw ValidationException::withMessages([
                    'reward_redemption_id' => 'Voucher reward tidak valid, sudah digunakan, atau kedaluwarsa.',
                ]);
            }

            $discountAmount = $redemption->reward->calculateDiscount($subtotal);
            $appliedRedemptionId = $redemption->id;
            $appliedLabel = 'Voucher: '.$redemption->reward->name;
        }

        $discountAmount = min($subtotal, max(0, $discountAmount));
        $netSubtotal = max(0, $subtotal - $discountAmount);

        // D2 / ASSUMPTION A7: Configurable tax_rate (percent, can be 0)
        $taxRate = (float) Setting::getValue('tax_rate', 0);
        $taxRate = max(0.0, $taxRate);
        $taxAmount = $taxRate > 0 ? (int) round($netSubtotal * ($taxRate / 100)) : 0;
        $totalAmount = $netSubtotal + $taxAmount;

        return [
            'items' => $validatedItems,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'promotion_id' => $appliedPromotionId,
            'reward_redemption_id' => $appliedRedemptionId,
            'applied_label' => $appliedLabel,
            'bonus_points' => $bonusPoints,
        ];
    }
}
