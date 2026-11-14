<?php

namespace App\Domain\Payment\Drivers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MockQrisGateway implements PaymentGateway
{
    public function createQrisCharge(Order $order, int $expiryMinutes = 15): array
    {
        $reference = 'QRIS-774-'.$order->id.'-'.strtoupper(Str::random(8));
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Standard QRIS-like payload string labelled SIMULASI
        $payload = sprintf(
            '00020101021226580016ID.CO.774HUB.SIM0118%s52045812530336054%02d%d5802ID5907774 HUB6010YOGYAKARTA6304SIMU',
            $reference,
            strlen((string) $order->total_amount),
            $order->total_amount
        );

        return [
            'provider' => 'mock_qris',
            'provider_reference' => $reference,
            'qris_payload' => $payload,
            'expires_at' => $expiresAt,
        ];
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        $expected = self::signPayload($payload);

        return hash_equals($expected, $signature);
    }

    public static function signPayload(array $payload): string
    {
        $secret = (string) config('app.payment_webhook_secret', '774hub-webhook-secret-key');
        $canonical = implode('|', [
            (string) ($payload['event_id'] ?? ''),
            (string) ($payload['provider_reference'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['amount'] ?? ''),
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }

    public function fetchStatus(string $providerReference, array $webhookPayload = []): array
    {
        // Simulate UC-04 E-1 provider timeout if requested
        if (($webhookPayload['status'] ?? '') === 'timeout') {
            return [
                'status' => 'timeout',
                'amount' => (int) ($webhookPayload['amount'] ?? 0),
                'provider_available' => false,
            ];
        }

        $payment = Payment::query()->where('provider_reference', $providerReference)->first();
        $amount = (int) ($webhookPayload['amount'] ?? ($payment?->amount ?? 0));
        $status = (string) ($webhookPayload['status'] ?? 'paid');

        return [
            'status' => $status,
            'amount' => $amount,
            'provider_available' => true,
        ];
    }
}
