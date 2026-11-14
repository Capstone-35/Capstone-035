<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(?User $user, Order $order): bool
    {
        if ($user && $user->can('orders.view')) {
            return true;
        }

        if ($user && $user->member && $order->member_id === $user->member->id) {
            return true;
        }

        $trackedTokens = (array) session('tracked_order_tokens', []);

        return in_array($order->public_token, $trackedTokens, true);
    }

    public function createManual(User $user): bool
    {
        return $user->can('orders.create');
    }

    public function updateItems(User $user, Order $order): bool
    {
        return $user->can('orders.edit') && $order->status === OrderStatus::MenungguPembayaran;
    }

    public function confirmCash(User $user, Order $order): bool
    {
        return $user->can('orders.confirm_cash') && $order->status === OrderStatus::MenungguPembayaran;
    }

    public function cancel(?User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::MenungguPembayaran) {
            if ($user && $user->can('orders.cancel_unpaid')) {
                return true;
            }
            if ($user && $user->member && $order->member_id === $user->member->id) {
                return true;
            }
            $trackedTokens = (array) session('tracked_order_tokens', []);

            return in_array($order->public_token, $trackedTokens, true);
        }

        if (in_array($order->status, [OrderStatus::Dibayar, OrderStatus::Diproses], true)) {
            return $user !== null && $user->can('orders.cancel_paid');
        }

        return false;
    }

    public function handover(User $user, Order $order): bool
    {
        return $user->can('orders.handover') && $order->status === OrderStatus::Siap;
    }

    public function updateKdsStatus(User $user, Order $order): bool
    {
        return $user->can('kds.update_status')
            && in_array($order->status, [OrderStatus::Dibayar, OrderStatus::Diproses], true);
    }
}
