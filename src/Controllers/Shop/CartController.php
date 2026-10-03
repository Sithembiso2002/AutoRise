<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Request;
use App\Core\Response;
use App\Services\Cart;

final class CartController
{
    public function __construct(private readonly Cart $cart = new Cart()) {}

    public function index(Request $request): Response
    {
        return Response::html(view('shop.cart.index', [
            'title'    => 'Your Cart | BuyCar',
            'items'    => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
        ]));
    }

    public function add(Request $request): Response
    {
        $id  = $request->int('product_id');
        $qty = max(1, $request->int('qty', 1));

        if ($id <= 0) {
            flash('error', 'Invalid product.');
            return Response::redirect(url('products'));
        }

        $this->cart->add($id, $qty);
        flash('success', 'Item added to cart.');

        $back = $request->str('back', url('cart'));
        return Response::redirect($back);
    }

    public function update(Request $request): Response
    {
        $id  = $request->int('product_id');
        $qty = $request->int('qty', 1);

        if ($id > 0) $this->cart->update($id, $qty);
        flash('success', 'Cart updated.');

        return Response::redirect(url('cart'));
    }

    public function remove(Request $request): Response
    {
        $id = $request->int('product_id');
        if ($id > 0) $this->cart->remove($id);
        flash('success', 'Item removed.');
        return Response::redirect(url('cart'));
    }
}