<?php
declare(strict_types=1);

namespace App\Services\Payments;

final class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $providerRef,
        public readonly string $status,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $failureReason = null,
        public readonly array $raw = [],
    ) {}

    public static function ok(string $ref, string $status = 'paid', ?string $redirectUrl = null, array $raw = []): self
    {
        return new self(true, $ref, $status, $redirectUrl, null, $raw);
    }

    public static function failed(string $ref, string $reason, array $raw = []): self
    {
        return new self(false, $ref, 'failed', null, $reason, $raw);
    }
}