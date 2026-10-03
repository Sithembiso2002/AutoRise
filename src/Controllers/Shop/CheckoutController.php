<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\DomainException;
use App\Services\Cart;
use App\Services\OrderService;

final class CheckoutController
{
    public function __construct(
        private readonly Cart $cart = new Cart(),
        private readonly OrderService $orders = new OrderService(),
    ) {}

    public function show(Request $request): Response
    {
        if ($this->cart->isEmpty()) {
            flash('error', 'Your cart is empty.');
            return Response::redirect(url('products'));
        }

        return Response::html(view('shop.checkout.show', [
            'title'    => 'Checkout | BuyCar',
            'items'    => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'user'     => Auth::user(),
        ]));
    }

    public function store(Request $request): Response
    {
        if ($this->cart->isEmpty()) {
            flash('error', 'Your cart is empty.');
            return Response::redirect(url('products'));
        }

        try {
            $order = $this->orders->place((int) Auth::id());
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            return Response::redirect(url('cart'));
        }

        $this->cart->clear();

        // Send user to the payment page for this order
        return Response::redirect(url('orders/' . $order['order_id'] . '/pay'));
    }
}