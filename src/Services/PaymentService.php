<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\Payments\GatewayFactory;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentResult;

final class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private ?PaymentGateway $gateway = null,
    ) {
        $this->gateway ??= GatewayFactory::make();
    }

    /**
     * Start (or resume) a payment for an order. Idempotent on order + attempt key.
     */
    public function startForOrder(array $order, string $attemptKey = 'initial'): array
    {
        $idempotencyKey = 'ord-' . (int) $order['order_id'] . '-' . preg_replace('/[^a-z0-9_-]/i', '', $attemptKey);

        // Existing payment for this key → reuse
        $existing = $this->payments->findByIdempotency($idempotencyKey);
        if ($existing) {
            return $existing;
        }

        $currency = (string) (config('payment.currency') ?? 'LSL');

        $intent = $this->gateway->createIntent(
            (int) $order['order_id'],
            (float) $order['total_amount'],
            $currency,
            $idempotencyKey,
            ['order_id' => (int) $order['order_id']]
        );

        $paymentId = $this->payments->create([
            'order_id'        => (int) $order['order_id'],
            'provider'        => $this->gateway->name(),
            'provider_ref'    => $intent['provider_ref'],
            'idempotency_key' => $idempotencyKey,
            'amount'          => (float) $order['total_amount'],
            'currency'        => $currency,
            'payment_method'  => 'credit_card',
            'payment_status'  => 'initiated',
            'payment_date'    => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return $this->payments->forOrder((int) $order['order_id'])[0]
            ?? ['payment_id' => $paymentId, 'provider_ref' => $intent['provider_ref']];
    }

    /**
     * For mock/no-redirect flows: process payment right now.
     * For real redirect flows: hand off to $returnUrl handled by the gateway.
     */
    public function process(array $payment, array $options = []): PaymentResult
    {
        return $this->gateway->confirm((string) $payment['provider_ref'], $options);
    }

    public function applyResult(array $payment, PaymentResult $result): void
    {
        if ($result->success) {
            $justPaid = $this->payments->markPaid((int) $payment['payment_id'], $result->raw);
            if ($justPaid) {
                $this->markOrderPaid((int) $payment['order_id']);
            }
        } else {
            $this->payments->markFailed((int) $payment['payment_id'], (string) $result->failureReason);
        }
    }

    public function markOrderPaid(int $orderId): void
    {
        // Guarded: only transitions from pending → paid
        db()->execute(
            'UPDATE orders SET order_status = "paid"
             WHERE order_id = :id AND order_status = "pending"',
            ['id' => $orderId]
        );
    }

    public function handleWebhookPayload(array $event): void
    {
        $type = (string) ($event['type'] ?? '');

        // Stripe-style event
        if (str_starts_with($type, 'payment_intent.')) {
            $intent = $event['data']['object'] ?? [];
            $ref    = (string) ($intent['id'] ?? '');
            if ($ref === '') return;

            $payment = $this->payments->findByProviderRef('stripe', $ref);
            if (!$payment) return;

            if ($type === 'payment_intent.succeeded') {
                if ($this->payments->markPaid((int) $payment['payment_id'], $intent)) {
                    $this->markOrderPaid((int) $payment['order_id']);
                }
            } elseif ($type === 'payment_intent.payment_failed') {
                $reason = (string) ($intent['last_payment_error']['message'] ?? 'Payment failed.');
                $this->payments->markFailed((int) $payment['payment_id'], $reason);
            }
            return;
        }

        // Mock-style event
        if (($event['provider'] ?? '') === 'mock') {
            $ref    = (string) ($event['provider_ref'] ?? '');
            $status = (string) ($event['status'] ?? '');
            if ($ref === '') return;

            $payment = $this->payments->findByProviderRef('mock', $ref);
            if (!$payment) return;

            if ($status === 'paid') {
                if ($this->payments->markPaid((int) $payment['payment_id'], $event)) {
                    $this->markOrderPaid((int) $payment['order_id']);
                }
            } elseif ($status === 'failed') {
                $this->payments->markFailed(
                    (int) $payment['payment_id'],
                    (string) ($event['reason'] ?? 'Payment failed.')
                );
            }
        }
    }
}