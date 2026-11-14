<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(?User $user, Invoice $invoice): bool
    {
        if ($user && $user->can('invoices.view')) {
            return true;
        }

        $order = $invoice->order;
        if (! $order) {
            return false;
        }

        if ($user && $user->member && $order->member_id === $user->member->id) {
            return true;
        }

        $trackedTokens = (array) session('tracked_order_tokens', []);

        return in_array($order->public_token, $trackedTokens, true);
    }

    public function resend(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.resend');
    }
}
