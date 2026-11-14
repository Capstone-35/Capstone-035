<?php

namespace App\Jobs;

use App\Domain\Invoice\Drivers\EmailInvoiceNotifier;
use App\Domain\Invoice\Drivers\WhatsAppInvoiceNotifier;
use App\Enums\InvoiceDeliveryStatus;
use App\Models\InvoiceDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInvoiceDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * NFR-07: Automatic retry max 3 times; delivery failure NEVER alters order status.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds (1 min, 5 min, 15 min)
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public int $invoiceDeliveryId
    ) {}

    public function handle(): void
    {
        $delivery = InvoiceDelivery::query()->with('invoice.order')->find($this->invoiceDeliveryId);
        if (! $delivery || ! $delivery->invoice) {
            return;
        }

        $notifier = $delivery->channel === 'whatsapp'
            ? app(WhatsAppInvoiceNotifier::class)
            : app(EmailInvoiceNotifier::class);

        $maxAttempts = $this->tries;
        $attempt = max(1, (int) $delivery->attempt_no);

        // When running on the 'sync' queue driver, simulate up to 3 attempts in-process if a failure occurs
        while ($attempt <= $maxAttempts) {
            try {
                $delivery->attempt_no = $attempt;
                $msgId = $notifier->send($delivery->invoice, $delivery->recipient);

                $delivery->status = InvoiceDeliveryStatus::Sent;
                $delivery->provider_message_id = $msgId;
                $delivery->error_message = null;
                $delivery->sent_at = now();
                $delivery->save();

                return;
            } catch (Throwable $e) {
                $delivery->attempt_no = $attempt;
                $delivery->status = InvoiceDeliveryStatus::Failed;
                $delivery->error_message = $e->getMessage();
                $delivery->save();

                Log::channel('invoices')->warning('Invoice delivery attempt failed', [
                    'invoice_id' => $delivery->invoice_id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if (config('queue.default') !== 'sync') {
                    // Let queue worker handle retry up to $tries
                    if ($this->attempts() < $this->tries) {
                        throw $e;
                    }
                    break;
                }

                $attempt++;
            }
        }
    }
}
