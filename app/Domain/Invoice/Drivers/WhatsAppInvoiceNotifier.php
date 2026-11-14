<?php

namespace App\Domain\Invoice\Drivers;

use App\Domain\Invoice\Contracts\InvoiceNotifier;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsAppInvoiceNotifier implements InvoiceNotifier
{
    public function send(Invoice $invoice, string $recipient): string
    {
        if (blank($recipient)) {
            throw new RuntimeException('Nomor WhatsApp penerima tidak tersedia.');
        }

        if (str_contains(strtolower($recipient), '0000')) {
            throw new RuntimeException('Simulasi kegagalan pengiriman WhatsApp API.');
        }

        $msgId = 'WA-MOCK-'.strtoupper(Str::random(10));
        Log::channel('invoices')->info('Invoice sent via WhatsApp SIMULASI adapter', [
            'invoice_number' => $invoice->invoice_number,
            'message_id' => $msgId,
        ]);

        return $msgId;
    }
}
