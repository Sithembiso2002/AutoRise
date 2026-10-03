<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Request;
use App\Core\Response;

final class ProductController
{
    private const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $page   = max(1, $request->int('page', 1));
        $search = $request->str('q');
        $catId  = $request->int('category', 0);
        $offset = ($page - 1) * self::PER_PAGE;

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            // Use distinct placeholders — PDO real prepares require this
            $where[] = '(name LIKE :q1 OR description LIKE :q2)';
            $params['q1'] = '%' . $search . '%';
            $params['q2'] = '%' . $search . '%';
        }
        if ($catId > 0) {
            $where[] = 'category_id = :cid';
            $params['cid'] = $catId;
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) db()->scalar(
            "SELECT COUNT(*) FROM products WHERE {$whereSql}",
            $params
        );

        $products = db()->select(
            "SELECT product_id, name, description, price, stock, image_path, category_id
             FROM products
             WHERE {$whereSql}
             ORDER BY product_id DESC
             LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );

        $categories = db()->select(
            'SELECT category_id, category_name FROM categories ORDER BY category_name'
        );

        return Response::html(view('shop.products.index', [
            'title'      => 'Products | BuyCar',
            'products'   => $products,
            'categories' => $categories,
            'page'       => $page,
            'total'      => $total,
            'perPage'    => self::PER_PAGE,
            'search'     => $search,
            'catId'      => $catId,
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        $product = db()->selectOne(
            'SELECT p.*, c.category_name
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             WHERE p.product_id = :id',
            ['id' => (int) $id]
        );

        if (!$product) {
            return Response::notFound('Product not found.');
        }

        $related = db()->select(
            'SELECT product_id, name, price, stock, image_path
             FROM products
             WHERE category_id = :cid AND product_id <> :id
             LIMIT 4',
            ['cid' => $product['category_id'], 'id' => (int) $id]
        );

        return Response::html(view('shop.products.show', [
            'title'   => $product['name'] . ' | BuyCar',
            'product' => $product,
            'related' => $related,
        ]));
    }
}