<?php
declare(strict_types=1);

namespace App\Repositories;

final class PaymentRepository
{
    public function create(array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO payments (%s) VALUES (%s)',
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        db()->execute($sql, $data);
        return (int) db()->pdo()->lastInsertId();
    }

    public function findByIdempotency(string $key): ?array
    {
        return db()->selectOne(
            'SELECT * FROM payments WHERE idempotency_key = :k LIMIT 1',
            ['k' => $key]
        );
    }

    public function findByProviderRef(string $provider, string $ref): ?array
    {
        return db()->selectOne(
            'SELECT * FROM payments WHERE provider = :p AND provider_ref = :r LIMIT 1',
            ['p' => $provider, 'r' => $ref]
        );
    }

    public function markPaid(int $paymentId, ?array $raw = null): bool
    {
        $rows = db()->execute(
            'UPDATE payments SET payment_status = "paid", updated_at = :now
             WHERE payment_id = :id AND payment_status <> "paid"',
            ['id' => $paymentId, 'now' => date('Y-m-d H:i:s')]
        );
        return $rows > 0;
    }

    public function markFailed(int $paymentId, string $reason): bool
    {
        $rows = db()->execute(
            'UPDATE payments SET payment_status = "failed", failure_reason = :r, updated_at = :now
             WHERE payment_id = :id AND payment_status NOT IN ("paid","failed")',
            ['id' => $paymentId, 'r' => $reason, 'now' => date('Y-m-d H:i:s')]
        );
        return $rows > 0;
    }

    public function forOrder(int $orderId): array
    {
        return db()->select(
            'SELECT * FROM payments WHERE order_id = :o ORDER BY payment_id DESC',
            ['o' => $orderId]
        );
    }
}