<?php

namespace App\Domain\Invoice\Contracts;

use App\Models\Invoice;

interface InvoiceNotifier
{
    /**
     * Sends the digital invoice to the specified recipient and returns a provider message ID.
     * Throws RuntimeException on failure.
     */
    public function send(Invoice $invoice, string $recipient): string;
}
