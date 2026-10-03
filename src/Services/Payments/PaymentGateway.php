<?php
declare(strict_types=1);

namespace App\Services\Payments;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Create a payment intent for the given order.
     * Should be idempotent: same order + idempotency key returns the same intent.
     *
     * @return array{provider_ref: string, redirect_url: ?string, raw: array}
     */
    public function createIntent(int $orderId, float $amount, string $currency, string $idempotencyKey, array $meta = []): array;

    /**
     * Confirm/charge a payment. For redirect flows this just returns the current state.
     */
    public function confirm(string $providerRef, array $options = []): PaymentResult;

    /**
     * Verify and parse an incoming webhook. Returns the parsed event or throws.
     */
    public function parseWebhook(string $payload, array $headers): array;
}