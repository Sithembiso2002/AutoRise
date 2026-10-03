<?php
declare(strict_types=1);

namespace App\Controllers\Shop;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\OrderRepository;
use App\Services\PaymentService;

final class PaymentController
{
    public function __construct(
        private readonly OrderRepository $orders = new OrderRepository(),
        private readonly PaymentService $payments = new PaymentService(),
    ) {}

    public function show(Request $request, string $orderId): Response
    {
        $order = $this->orders->findForUser((int) $orderId, (int) Auth::id());
        if (!$order) {
            return Response::notFound('Order not found.');
        }

        if ($order['order_status'] !== 'pending') {
            flash('info', 'This order is already ' . $order['order_status'] . '.');
            return Response::redirect(url('orders/' . $order['order_id']));
        }

        $payment = $this->payments->startForOrder($order);

        return Response::html(view('shop.checkout.payment', [
            'title'   => 'Pay for Order #' . $order['order_id'] . ' | BuyCar',
            'order'   => $order,
            'payment' => $payment,
        ]));
    }

    public function process(Request $request, string $orderId): Response
    {
        $order = $this->orders->findForUser((int) $orderId, (int) Auth::id());
        if (!$order) {
            return Response::notFound('Order not found.');
        }
        if ($order['order_status'] !== 'pending') {
            return Response::redirect(url('orders/' . $order['order_id']));
        }

        $payment = $this->payments->startForOrder($order);

        $outcome = $request->str('outcome'); // 'success'|'failure'|''
        $result  = $this->payments->process($payment, $outcome !== '' ? ['outcome' => $outcome] : []);

        $this->payments->applyResult($payment, $result);

        if ($result->success) {
            flash('success', 'Payment received. Thank you!');
            return Response::redirect(url('orders/' . $order['order_id']));
        }

        flash('error', 'Payment failed: ' . ($result->failureReason ?? 'Unknown error') . '. Please try again.');
        return Response::redirect(url('orders/' . $order['order_id'] . '/pay'));
    }
}