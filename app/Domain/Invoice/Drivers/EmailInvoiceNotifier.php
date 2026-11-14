<?php

namespace App\Domain\Invoice\Drivers;

use App\Domain\Invoice\Contracts\InvoiceNotifier;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class EmailInvoiceNotifier implements InvoiceNotifier
{
    public function send(Invoice $invoice, string $recipient): string
    {
        if (blank($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Alamat email penerima tidak valid atau kosong.');
        }

        // Simulated failure trigger for testing UC-07 A-1 / NFR-07 retry logic
        if (str_contains(strtolower($recipient), 'fail@') || str_contains(strtolower($recipient), 'error@')) {
            throw new RuntimeException('Simulasi timeout/kegagalan layanan Email API eksternal.');
        }

        $order = $invoice->order;
        $subject = "Invoice Digital {$invoice->invoice_number} — 774 HUB";

        Mail::raw(
            "Terima kasih telah memesan di 774 HUB.\n".
            "Nomor Invoice: {$invoice->invoice_number}\n".
            "Nomor Pesanan: {$order?->order_number}\n".
            "Total Pembayaran: Rp".number_format((int) ($order?->total_amount ?? 0), 0, ',', '.')."\n".
            "Status Pembayaran: Dibayar\n",
            function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            }
        );

        $msgId = 'EMAIL-'.strtoupper(Str::random(12));
        Log::channel('invoices')->info('Invoice sent via Email adapter', [
            'invoice_number' => $invoice->invoice_number,
            'message_id' => $msgId,
        ]);

        return $msgId;
    }
}
