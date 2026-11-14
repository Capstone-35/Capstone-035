<?php

namespace App\Domain\Ordering;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStateMachine
{
    /**
     * Allowed transitions map (Section 7.2)
     *
     * @var array<string, list<string>>
     */
    protected const ALLOWED_TRANSITIONS = [
        OrderStatus::MenungguPembayaran->value => [
            OrderStatus::Dibayar->value,
            OrderStatus::Dibatalkan->value,
        ],
        OrderStatus::Dibayar->value => [
            OrderStatus::Diproses->value,
            OrderStatus::Dibatalkan->value,
        ],
        OrderStatus::Diproses->value => [
            OrderStatus::Siap->value,
            OrderStatus::Dibatalkan->value,
        ],
        OrderStatus::Siap->value => [
            OrderStatus::Selesai->value,
        ],
        OrderStatus::Selesai->value => [],
        OrderStatus::Dibatalkan->value => [],
    ];

    public function canTransition(Order $order, OrderStatus $to, ?User $actor = null, ?string $reason = null): bool
    {
        $from = $order->status;
        $allowedTargets = self::ALLOWED_TRANSITIONS[$from->value] ?? [];

        if (! in_array($to->value, $allowedTargets, true)) {
            return false;
        }

        // D10: Cancellation after Dibayar or Diproses is restricted to Manager only and requires a reason
        if ($to === OrderStatus::Dibatalkan && in_array($from, [OrderStatus::Dibayar, OrderStatus::Diproses], true)) {
            if (! $actor || ! $actor->can('orders.cancel_paid')) {
                return false;
            }
            if (blank($reason)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Executes a validated, row-locked status transition and writes OrderStatusHistory.
     */
    public function transition(
        Order $order,
        OrderStatus $to,
        ?User $actor = null,
        string $source = 'system',
        ?string $reason = null,
        ?OrderStatus $expectedFrom = null
    ): Order {
        return DB::transaction(function () use ($order, $to, $actor, $source, $reason, $expectedFrom) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($expectedFrom !== null && $locked->status !== $expectedFrom) {
                throw ValidationException::withMessages([
                    'status' => "Status pesanan tidak sesuai (saat ini: {$locked->status->value}). Silakan muat ulang halaman.",
                ]);
            }

            if (! $this->canTransition($locked, $to, $actor, $reason)) {
                throw ValidationException::withMessages([
                    'status' => "Perubahan status dari '{$locked->status->value}' ke '{$to->value}' tidak diizinkan.",
                ]);
            }

            $fromValue = $locked->status->value;
            $locked->status = $to;

            $now = now();
            if ($to === OrderStatus::Dibayar && ! $locked->paid_at) {
                $locked->paid_at = $now;
            } elseif ($to === OrderStatus::Diproses) {
                $locked->processing_at = $now;
            } elseif ($to === OrderStatus::Siap) {
                $locked->ready_at = $now;
            } elseif ($to === OrderStatus::Selesai) {
                $locked->completed_at = $now;
                if ($actor) {
                    $locked->handed_over_by = $actor->id;
                }
            } elseif ($to === OrderStatus::Dibatalkan) {
                $locked->cancelled_at = $now;
                $locked->cancel_reason = $reason;
            }

            $locked->save();

            OrderStatusHistory::query()->create([
                'order_id' => $locked->id,
                'from_status' => $fromValue,
                'to_status' => $to->value,
                'actor_user_id' => $actor?->id,
                'source' => $source,
                'reason' => $reason,
                'created_at' => $now,
            ]);

            return $locked->fresh(['items', 'table', 'member', 'payments', 'invoice']);
        });
    }
}
