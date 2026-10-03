<?php
declare(strict_types=1);

namespace App\Services\Payments;

use App\Core\Config;

final class MockGateway implements PaymentGateway
{
    public function name(): string { return 'mock'; }

    public function createIntent(int $orderId, float $amount, string $currency, string $idempotencyKey, array $meta = []): array
    {
        $ref = 'mock_' . bin2hex(random_bytes(8));

        return [
            'provider_ref' => $ref,
            'redirect_url' => null,
            'raw' => [
                'amount'          => $amount,
                'currency'        => $currency,
                'order_id'        => $orderId,
                'idempotency_key' => $idempotencyKey,
                'meta'            => $meta,
            ],
        ];
    }

    public function confirm(string $providerRef, array $options = []): PaymentResult
    {
        $outcome = (string) (Config::get('payment.mock.force_outcome') ?? '');

        // Allow UI to override
        if (isset($options['outcome'])) {
            $outcome = (string) $options['outcome'];
        }

        // Default: random 85% success (feels realistic)
        if ($outcome === '') {
            $outcome = random_int(1, 100) <= 85 ? 'success' : 'failure';
        }

        if ($outcome === 'success') {
            return PaymentResult::ok($providerRef, 'paid', null, ['mock' => true, 'outcome' => 'success']);
        }

        return PaymentResult::failed($providerRef, 'Card declined (mock)');
    }

    public function parseWebhook(string $payload, array $headers): array
    {
        // Mock webhook: JSON body with signature = sha256 of body + app key
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid mock webhook payload.');
        }

        $signature = $headers['X-Mock-Signature'] ?? ($headers['x-mock-signature'] ?? '');
        $expected  = hash_hmac('sha256', $payload, (string) env('APP_KEY'));

        if (!hash_equals($expected, (string) $signature)) {
            throw new \RuntimeException('Invalid mock webhook signature.');
        }

        return $data;
    }
}