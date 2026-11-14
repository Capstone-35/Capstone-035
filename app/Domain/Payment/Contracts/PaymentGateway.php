<?php

namespace App\Domain\Payment\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * Creates a QRIS charge for the given Order.
     *
     * @return array{
     *   provider: string,
     *   provider_reference: string,
     *   qris_payload: string,
     *   expires_at: \Carbon\Carbon
     * }
     */
    public function createQrisCharge(Order $order, int $expiryMinutes = 15): array;

    /**
     * Verifies HMAC signature on incoming webhook payload.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Re-queries the provider status for the given provider reference (UC-04 S-1 step 2).
     *
     * @return array{
     *   status: string,
     *   amount: int,
     *   provider_available: bool
     * }
     */
    public function fetchStatus(string $providerReference, array $webhookPayload = []): array;
}
