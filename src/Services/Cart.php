<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

final class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int>  productId => qty */
    public function raw(): array
    {
        $c = Session::get(self::KEY, []);
        return is_array($c) ? $c : [];
    }

    /** @return array<int, array{product_id:int, qty:int}> */
    public function lines(): array
    {
        $out = [];
        foreach ($this->raw() as $pid => $qty) {
            $out[] = ['product_id' => (int) $pid, 'qty' => (int) $qty];
        }
        return $out;
    }

    /**
     * @return array<int, array{product_id:int, name:string, price:float, qty:int, stock:int, image_path:?string, line_total:float}>
     */
    public function items(): array
    {
        $raw = $this->raw();
        if (!$raw) return [];

        $ids  = array_map('intval', array_keys($raw));
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $rows = db()->select(
            "SELECT product_id, name, price, stock, image_path
             FROM products
             WHERE product_id IN ({$in})",
            $ids
        );

        $byId = [];
        foreach ($rows as $r) $byId[(int) $r['product_id']] = $r;

        $items = [];
        foreach ($raw as $pid => $qty) {
            $pid = (int) $pid;
            $qty = (int) $qty;
            if (!isset($byId[$pid])) continue;

            $p = $byId[$pid];
            // Clamp quantity to available stock
            if ($qty > (int) $p['stock']) $qty = (int) $p['stock'];

            $price = (float) $p['price'];
            $items[] = [
                'product_id' => $pid,
                'name'       => (string) $p['name'],
                'price'      => $price,
                'qty'        => $qty,
                'stock'      => (int) $p['stock'],
                'image_path' => $p['image_path'],
                'line_total' => round($price * $qty, 2),
            ];
        }
        return $items;
    }

    public function add(int $productId, int $qty = 1): void
    {
        if ($qty < 1) $qty = 1;

        $product = db()->selectOne(
            'SELECT product_id, stock FROM products WHERE product_id = :id',
            ['id' => $productId]
        );
        if (!$product) return;

        $raw = $this->raw();
        $existing = (int) ($raw[$productId] ?? 0);
        $new = $existing + $qty;

        $stock = (int) $product['stock'];
        if ($new > $stock) $new = $stock;
        if ($new < 1)      unset($raw[$productId]);
        else               $raw[$productId] = $new;

        Session::put(self::KEY, $raw);
    }

    public function update(int $productId, int $qty): void
    {
        $raw = $this->raw();
        if (!isset($raw[$productId])) return;

        if ($qty < 1) {
            unset($raw[$productId]);
        } else {
            $stock = (int) (db()->scalar(
                'SELECT stock FROM products WHERE product_id = :id',
                ['id' => $productId]
            ) ?? 0);
            if ($qty > $stock) $qty = $stock;
            if ($qty < 1) unset($raw[$productId]);
            else          $raw[$productId] = $qty;
        }
        Session::put(self::KEY, $raw);
    }

    public function remove(int $productId): void
    {
        $raw = $this->raw();
        unset($raw[$productId]);
        Session::put(self::KEY, $raw);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return empty($this->raw());
    }

    public function subtotal(): float
    {
        $sum = 0.0;
        foreach ($this->items() as $i) $sum += (float) $i['line_total'];
        return round($sum, 2);
    }
}