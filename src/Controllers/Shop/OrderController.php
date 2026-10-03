<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;

final class OrderController
{
    public function __construct(private readonly OrderRepository $orders = new OrderRepository()) {}

    public function index(Request $request): Response
    {
        $rows = $this->orders->forUser((int) Auth::id());

        return Response::html(view('shop.orders.index', [
            'title'  => 'Your Orders | BuyCar',
            'orders' => $rows,
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        $order = $this->orders->findForUser((int) $id, (int) Auth::id());
        if (!$order) {
            return Response::notFound('Order not found.');
        }

        return Response::html(view('shop.orders.show', [
            'title' => 'Order #' . $order['order_id'] . ' | BuyCar',
            'order' => $order,
            'items' => $this->orders->itemsFor((int) $order['order_id']),
        ]));
    }
}