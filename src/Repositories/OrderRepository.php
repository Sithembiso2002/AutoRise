<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Connection;

final class OrderRepository
{
    public function create(Connection $c, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO orders (%s) VALUES (%s)',
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        $c->execute($sql, $data);
        return (int) $c->pdo()->lastInsertId();
    }

    public function addItem(Connection $c, int $orderId, array $row): void
    {
        $c->insert('order_items', [
            'order_id'   => $orderId,
            'product_id' => (int) $row['product_id'],
            'quantity'   => (int) $row['quantity'],
            'price'      => (float) $row['unit_price'],
            'unit_price' => (float) $row['unit_price'],
        ]);
    }

    public function find(Connection $c, int $orderId): ?array
    {
        return $c->selectOne('SELECT * FROM orders WHERE order_id = :id', ['id' => $orderId]);
    }

    public function findForUser(int $orderId, int $userId): ?array
    {
        return db()->selectOne(
            'SELECT * FROM orders WHERE order_id = :id AND user_id = :u',
            ['id' => $orderId, 'u' => $userId]
        );
    }

    public function itemsFor(int $orderId): array
    {
        return db()->select(
            'SELECT oi.*, p.name AS product_name, p.image_path
             FROM order_items oi
             LEFT JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = :id',
            ['id' => $orderId]
        );
    }

    public function forUser(int $userId): array
    {
        return db()->select(
            'SELECT * FROM orders WHERE user_id = :u ORDER BY order_date DESC',
            ['u' => $userId]
        );
    }
}