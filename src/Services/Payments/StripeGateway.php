<?php
declare(strict_types=1);

namespace App\Services\Payments;

use App\Core\Config;

final class StripeGateway implements PaymentGateway
{
    public function name(): string { return 'stripe'; }

    public function createIntent(int $orderId, float $amount, string $currency, string $idempotencyKey, array $meta = []): array
    {
        $secret = (string) Config::get('payment.stripe.secret_key');
        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key not configured.');
        }

        $params = [
            'amount'   => (int) round($amount * 100),
            'currency' => strtolower($currency),
            'metadata' => array_merge($meta, ['order_id' => $orderId]),
            'automatic_payment_methods' => ['enabled' => 'true'],
        ];

        $ch = curl_init('https://api.stripe.com/v1/payment_intents');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $secret . ':',
            CURLOPT_HTTPHEADER     => ['Idempotency-Key: ' . $idempotencyKey],
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_TIMEOUT        => 20,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $body, true);
        if ($code >= 400 || !is_array($data) || empty($data['id'])) {
            throw new \RuntimeException('Stripe error: ' . (string) $body);
        }

        return [
            'provider_ref' => (string) $data['id'],
            'redirect_url' => null,
            'raw'          => $data,
        ];
    }

    public function confirm(string $providerRef, array $options = []): PaymentResult
    {
        $secret = (string) Config::get('payment.stripe.secret_key');

        $ch = curl_init('https://api.stripe.com/v1/payment_intents/' . urlencode($providerRef));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secret . ':',
            CURLOPT_TIMEOUT        => 20,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            return PaymentResult::failed($providerRef, 'Invalid response from Stripe.');
        }

        $status = (string) ($data['status'] ?? 'failed');
        if (in_array($status, ['succeeded', 'requires_capture', 'processing'], true)) {
            return PaymentResult::ok($providerRef, $status === 'succeeded' ? 'paid' : 'authorized', null, $data);
        }
        return PaymentResult::failed($providerRef, (string) ($data['last_payment_error']['message'] ?? 'Payment failed.'), $data);
    }

    public function parseWebhook(string $payload, array $headers): array
    {
        $secret    = (string) Config::get('payment.stripe.webhook_secret');
        $sigHeader = $headers['Stripe-Signature'] ?? ($headers['stripe-signature'] ?? '');

        if ($secret === '' || $sigHeader === '') {
            throw new \RuntimeException('Stripe webhook not configured.');
        }

        $parts = [];
        foreach (explode(',', (string) $sigHeader) as $kv) {
            [$k, $v] = array_pad(explode('=', $kv, 2), 2, '');
            $parts[$k][] = $v;
        }
        $timestamp = $parts['t'][0] ?? '';
        $sig       = $parts['v1'][0] ?? '';

        if (!$timestamp || !$sig) throw new \RuntimeException('Malformed Stripe signature.');

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        if (!hash_equals($expected, $sig)) throw new \RuntimeException('Stripe signature mismatch.');

        $data = json_decode($payload, true);
        if (!is_array($data)) throw new \RuntimeException('Invalid JSON body.');

        return $data;
    }
}