<?php

namespace App\Domain\Invoice;

use App\Enums\InvoiceDeliveryStatus;
use App\Jobs\SendInvoiceDeliveryJob;
use App\Models\Invoice;
use App\Models\InvoiceDelivery;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InvoiceService
{
    /**
     * Generates a 1:1 digital invoice for a paid order (idempotent) and queues delivery.
     */
    public function generateForPaidOrder(Order $order, bool $dispatchDelivery = true): Invoice
    {
        $invoice = DB::transaction(function () use ($order) {
            $existing = Invoice::query()->where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $order->loadMissing(['items', 'table', 'member.user', 'promotion', 'paidPayment']);

            $datePart = ($order->paid_at ?? now())->format('Ymd');
            $seq = str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-774-{$datePart}-{$seq}";

            $snapshot = [
                'invoice_number' => $invoiceNumber,
                'order_number' => $order->order_number,
                'order_code' => $order->order_code,
                'issued_at' => ($order->paid_at ?? now())->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB',
                'outlet_name' => Setting::getValue('outlet.name', '774 HUB'),
                'outlet_address' => Setting::getValue('outlet.address', 'Jl. Bung Tarjo No. 12, Baciro, Yogyakarta'),
                'outlet_phone' => Setting::getValue('outlet.phone', '0274-555774'),
                'outlet_tax_id' => Setting::getValue('outlet.tax_id', ''),
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'is_member' => $order->member_id !== null,
                'member_code' => $order->member?->member_code,
                'table_label' => $order->table?->label ?? 'Takeaway / POS',
                'payment_method' => $order->payment_method?->label() ?? 'Tunai',
                'payment_status' => 'Dibayar',
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'promotion_name' => $order->promotion?->name,
                'tax_rate' => $order->tax_rate,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'note' => $item->note,
                ])->all(),
            ];

            return Invoice::query()->create([
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'issued_at' => $order->paid_at ?? now(),
                'snapshot' => $snapshot,
            ]);
        });

        if ($dispatchDelivery && $invoice->deliveries()->count() === 0) {
            $recipient = $order->customer_email ?: ($order->member?->user?->email ?? '');
            if (! blank($recipient)) {
                $this->dispatchDelivery($invoice, 'email', $recipient, 'system');
            } elseif (! blank($order->customer_phone)) {
                $this->dispatchDelivery($invoice, 'whatsapp', $order->customer_phone, 'system');
            }
        }

        return $invoice;
    }

    public function dispatchDelivery(
        Invoice $invoice,
        string $channel,
        string $recipient,
        string $triggeredBy = 'system'
    ): InvoiceDelivery {
        $delivery = InvoiceDelivery::query()->create([
            'invoice_id' => $invoice->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => InvoiceDeliveryStatus::Pending,
            'attempt_no' => 1,
            'triggered_by' => $triggeredBy,
        ]);

        SendInvoiceDeliveryJob::dispatch($delivery->id);

        return $delivery->fresh();
    }

    /**
     * Manual resend from POS / Manager ("Kirim Ulang Invoice", UC-07 S-1).
     * Never changes orders.status.
     */
    public function resend(
        Invoice $invoice,
        User $actor,
        string $channel = 'email',
        ?string $recipientOverride = null
    ): InvoiceDelivery {
        $order = $invoice->order;

        if ($recipientOverride && $channel === 'email' && $order) {
            $order->customer_email = $recipientOverride;
            $order->save();
        }

        $recipient = $recipientOverride ?: (
            $channel === 'whatsapp'
                ? ($order?->customer_phone ?: $order?->member?->user?->phone ?: '')
                : ($order?->customer_email ?: $order?->member?->user?->email ?: '')
        );

        return $this->dispatchDelivery($invoice, $channel, (string) $recipient, (string) $actor->id);
    }

    public function renderPdfBinary(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->get($invoice->pdf_path);
        }

        $pdf = Pdf::loadView('customer.invoice-pdf', [
            'invoice' => $invoice,
            'order' => $invoice->order->loadMissing(['items', 'table']),
            'snapshot' => $invoice->snapshot ?? [],
        ]);

        $output = $pdf->output();

        try {
            $path = "invoices/{$invoice->invoice_number}.pdf";
            Storage::disk('local')->put($path, $output);
            $invoice->pdf_path = $path;
            $invoice->save();
        } catch (Throwable) {
            // Stream directly even if local storage write is skipped
        }

        return $output;
    }
}
