<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Connection;
use App\Core\Database;
use App\Exceptions\DomainException;
use App\Repositories\OrderRepository;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders = new OrderRepository(),
        private readonly Cart $cart = new Cart(),
    ) {}

    /**
     * Place an order for $userId. Returns the new order row.
     * Throws DomainException on any business-rule violation.
     */
    public function place(int $userId): array
    {
        $lines = $this->cart->lines();
        if (!$lines) {
            throw new DomainException('Your cart is empty.');
        }

        return Database::connection()->transaction(function (Connection $c) use ($userId, $lines) {
            $total    = 0.0;
            $prepared = [];

            foreach ($lines as $line) {
                $product = $this->lockProduct($c, (int) $line['product_id']);
                if (!$product) {
                    throw new DomainException('One of the products in your cart no longer exists.');
                }

                $stock = (int) $product['stock'];
                $qty   = (int) $line['qty'];

                if ($stock < $qty) {
                    throw new DomainException(
                        "Not enough stock for \"{$product['name']}\". Only {$stock} left."
                    );
                }

                $unitPrice = (float) $product['price'];
                $total    += $unitPrice * $qty;

                $prepared[] = [
                    'product_id' => (int) $product['product_id'],
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                ];
            }

            $orderId = $this->orders->create($c, [
                'user_id'      => $userId,
                'order_date'   => date('Y-m-d H:i:s'),
                'order_status' => 'pending',
                'total_amount' => round($total, 2),
            ]);

            foreach ($prepared as $row) {
                $this->orders->addItem($c, $orderId, $row);

                // Atomic stock decrement — two distinct placeholders because PDO
                // real-prepares cannot bind the same name twice
                $affected = $c->execute(
                    'UPDATE products SET stock = stock - :q1
                     WHERE product_id = :id AND stock >= :q2',
                    [
                        'q1' => $row['quantity'],
                        'id' => $row['product_id'],
                        'q2' => $row['quantity'],
                    ]
                );
                if ($affected === 0) {
                    throw new DomainException('One of the products just sold out.');
                }
            }

            return $this->orders->find($c, $orderId)
                ?? throw new DomainException('Order creation failed.');
        });
    }

    private function lockProduct(Connection $c, int $id): ?array
    {
        $lock = $c->isSqlite() ? '' : ' FOR UPDATE';
        return $c->selectOne(
            "SELECT product_id, name, price, stock FROM products WHERE product_id = :id{$lock}",
            ['id' => $id]
        );
    }
}