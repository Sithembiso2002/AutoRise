<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        // Fetch products from MySQL through the new Connection layer
        $products = db()->select(
            'SELECT product_id, name, description, price, stock, image_path
             FROM products
             ORDER BY product_id DESC
             LIMIT 8'
        );

        // Fetch top-level categories
        $categories = db()->select(
            'SELECT category_id, category_name, category_description
             FROM categories
             ORDER BY category_name ASC'
        );

        $html = view('shop.home', [
            'products'   => $products,
            'categories' => $categories,
        ]);

        return Response::html($html);
    }
}