<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLogger;

final class OrderController
{
    private const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    public function index(Request $request): Response
    {
        $q      = $request->str('q');
        $status = $request->str('status');
        $page   = max(1, $request->int('page', 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(o.order_ref LIKE :q1 OR u.name LIKE :q2 OR u.email LIKE :q3)';
            $params['q1'] = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
            $params['q3'] = '%' . $q . '%';
        }
        if (in_array($status, self::STATUSES, true)) {
            $where[] = 'o.order_status = :st';
            $params['st'] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) db()->scalar(
            "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.user_id = o.user_id WHERE {$whereSql}",
            $params
        );

        $rows = db()->select(
            "SELECT o.order_id, o.order_ref, o.user_id, o.total_amount, o.order_status,
                    o.order_date, o.currency,
                    u.name AS customer_name, u.email AS customer_email
             FROM orders o
             LEFT JOIN users u ON u.user_id = o.user_id
             WHERE {$whereSql}
             ORDER BY o.order_id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return Response::html(view('admin.orders.index', [
            'title'   => 'Orders | Admin',
            'active'  => 'orders',
            'rows'    => $rows,
            'q'       => $q,
            'status'  => $status,
            'page'    => $page,
            'perPage' => $perPage,
            'total'   => $total,
            'statuses'=> self::STATUSES,
        ]));
    }

    public function show(Request $request, string $id): Response
    {
        $id = (int) $id;
        $order = db()->selectOne(
            "SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                    u.phone AS customer_phone, u.address AS customer_address,
                    u.city AS customer_city, u.country AS customer_country
             FROM orders o
             LEFT JOIN users u ON u.user_id = o.user_id
             WHERE o.order_id = :id",
            ['id' => $id]
        );
        if (!$order) return Response::notFound('Order not found.');

        $items = db()->select(
            "SELECT oi.*, p.image_path
             FROM order_items oi
             LEFT JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = :oid",
            ['oid' => $id]
        );

        $payments = db()->select(
            "SELECT * FROM payments WHERE order_id = :oid ORDER BY payment_id DESC",
            ['oid' => $id]
        );

        $history = db()->select(
            "SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY id DESC LIMIT 20",
            ['oid' => $id]
        );

        return Response::html(view('admin.orders.show', [
            'title'    => 'Order #' . $id . ' | Admin',
            'active'   => 'orders',
            'order'    => $order,
            'items'    => $items,
            'payments' => $payments,
            'history'  => $history,
            'statuses' => self::STATUSES,
        ]));
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $id     = (int) $id;
        $new    = $request->str('status');
        $note   = $request->str('note');

        if (!in_array($new, self::STATUSES, true)) {
            flash('error', 'Invalid status.');
            return Response::redirect(url('admin/orders/' . $id));
        }

        $order = db()->selectOne('SELECT * FROM orders WHERE order_id = :id', ['id' => $id]);
        if (!$order) return Response::notFound('Order not found.');

        $old = (string) $order['order_status'];
        if ($old === $new) {
            flash('info', 'Status unchanged.');
            return Response::redirect(url('admin/orders/' . $id));
        }

        db()->execute(
            'UPDATE orders SET order_status = :s, updated_at = :now WHERE order_id = :id',
            ['s' => $new, 'now' => date('Y-m-d H:i:s'), 'id' => $id]
        );

        try {
            db()->insert('order_status_history', [
                'order_id'    => $id,
                'from_status' => $old,
                'to_status'   => $new,
                'changed_by'  => auth()['id'] ?? null,
                'note'        => $note ?: null,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) { /* history table optional */ }

        AuditLogger::log('order.status', 'order', (string) $id, ['status' => $old], ['status' => $new]);
        flash('success', 'Order status updated to ' . ucfirst($new) . '.');
        return Response::redirect(url('admin/orders/' . $id));
    }
}