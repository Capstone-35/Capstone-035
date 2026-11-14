<?php

namespace App\Http\Controllers\Webhook;

use App\Domain\Payment\Drivers\MockQrisGateway;
use App\Domain\Payment\PaymentService;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * POST /webhooks/payments/{provider} (UC-04 S-1, NFR-06)
     * Verifies X-Webhook-Signature HMAC and processes idempotently.
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $signature = (string) ($request->header('X-Webhook-Signature') ?? $request->input('signature', ''));
        $payload = $request->only(['event_id', 'provider_reference', 'status', 'amount', 'order_number']);

        $outcome = $this->paymentService->handleQrisWebhook($provider, $payload, $signature);

        return response()->json([
            'result' => $outcome['result'],
            'message' => $outcome['message'],
            'order_number' => isset($outcome['order']) ? $outcome['order']->order_number : null,
            'order_status' => isset($outcome['order']) ? $outcome['order']->status->value : null,
        ], $outcome['http_status']);
    }

    /**
     * GET /sandbox/payments — Manager-only QRIS Webhook Simulator (Non-production only)
     */
    public function sandboxIndex(): View
    {
        if (app()->environment('production') || config('app.payment_driver') !== 'mock') {
            abort(404);
        }

        $this->paymentService->expireStaleQris();

        $qrisPayments = Payment::query()
            ->with(['order.table', 'order.member.user'])
            ->where('method', PaymentMethod::Qris->value)
            ->latest('id')
            ->limit(30)
            ->get();

        $webhookLogs = PaymentWebhookEvent::query()
            ->latest('id')
            ->limit(25)
            ->get();

        return view('manager.sandbox-payments', [
            'qrisPayments' => $qrisPayments,
            'webhookLogs' => $webhookLogs,
        ]);
    }

    /**
     * POST /sandbox/payments/trigger — Fires a real HMAC-signed webhook payload into handleQrisWebhook
     */
    public function sandboxTrigger(Request $request): RedirectResponse
    {
        if (app()->environment('production') || config('app.payment_driver') !== 'mock') {
            abort(404);
        }

        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'scenario' => ['required', 'in:paid,failed,expired,duplicate,amount_mismatch,timeout,invalid_signature'],
            'custom_event_id' => ['nullable', 'string', 'max:80'],
        ]);

        $payment = Payment::query()->with('order')->findOrFail($validated['payment_id']);
        $scenario = $validated['scenario'];

        $eventId = ($request->input('custom_event_id')) ?: ('EVT-SIM-'.strtoupper(Str::random(10)));
        if ($scenario === 'duplicate') {
            $lastEvent = PaymentWebhookEvent::query()->latest('id')->first();
            $eventId = $lastEvent?->event_id ?: 'EVT-SIM-DUPLICATE-001';
        }

        $status = match ($scenario) {
            'paid', 'duplicate', 'amount_mismatch', 'invalid_signature' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            'timeout' => 'timeout',
        };

        $amount = $scenario === 'amount_mismatch'
            ? max(1000, (int) $payment->amount - 5000)
            : (int) $payment->amount;

        $payload = [
            'event_id' => $eventId,
            'provider_reference' => (string) $payment->provider_reference,
            'status' => $status,
            'amount' => $amount,
            'order_number' => $payment->order?->order_number,
        ];

        $signature = $scenario === 'invalid_signature'
            ? 'invalid-hmac-signature-0000'
            : MockQrisGateway::signPayload($payload);

        $outcome = $this->paymentService->handleQrisWebhook('mock_qris', $payload, $signature);

        $toastType = in_array($outcome['result'], ['paid', 'duplicate_ignored', 'already_paid_ignored'], true)
            ? 'toast_success'
            : 'toast_info';

        return back()->with(
            $toastType,
            "[SIMULASI WEBHOOK: {$scenario}] Hasil: {$outcome['result']} — {$outcome['message']}"
        );
    }

    /**
     * Customer-side QRIS Simulation helper in Demo/Mock mode:
     * Fires a real signed webhook for the current pending QRIS payment.
     */
    public function simulateCustomerQris(Request $request, string $public_token): RedirectResponse
    {
        if (app()->environment('production') || config('app.payment_driver') !== 'mock') {
            abort(404);
        }

        $order = Order::query()->where('public_token', $public_token)->firstOrFail();
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('method', PaymentMethod::Qris->value)
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->first();

        if (! $payment) {
            return back()->with('toast_error', 'Tidak ada tagihan QRIS aktif untuk disimulasikan.');
        }

        $scenario = $request->input('scenario', 'paid');
        $status = in_array($scenario, ['paid', 'failed', 'expired'], true) ? $scenario : 'paid';

        $payload = [
            'event_id' => 'EVT-CUST-'.strtoupper(Str::random(10)),
            'provider_reference' => (string) $payment->provider_reference,
            'status' => $status,
            'amount' => (int) $payment->amount,
            'order_number' => $order->order_number,
        ];

        $signature = MockQrisGateway::signPayload($payload);
        $outcome = $this->paymentService->handleQrisWebhook('mock_qris', $payload, $signature);

        if ($outcome['result'] === 'paid') {
            return redirect()->route('order.track', ['public_token' => $order->public_token])
                ->with('toast_success', '[SIMULASI QRIS] Pembayaran QRIS terverifikasi otomatis melalui signed webhook!');
        }

        return back()->with('toast_info', "[SIMULASI QRIS: {$status}] {$outcome['message']}");
    }
}
