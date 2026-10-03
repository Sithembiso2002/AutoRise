<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

final class ReportController
{
    public function index(Request $request): Response
    {
        $range = $request->str('range', '30');
        $days  = in_array($range, ['7', '30', '90', '365'], true) ? (int) $range : 30;

        /* Compute "since" in PHP so this works on MySQL and SQLite alike. */
        $since = date('Y-m-d H:i:s', strtotime('-' . ($days - 1) . ' days'));

        /* ---------- Daily sales ---------- */
        $salesByDay = db()->select(
            "SELECT DATE(order_date) AS d,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(total_amount),0) AS rev
             FROM orders
             WHERE order_status IN ('paid','processing','shipped','delivered')
               AND order_date >= :since
             GROUP BY DATE(order_date)
             ORDER BY d ASC",
            ['since' => $since]
        );

        /* ---------- Summary totals ---------- */
        $salesTotal = db()->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) AS rev,
                    COUNT(*) AS cnt,
                    COALESCE(AVG(total_amount),0) AS avg
             FROM orders
             WHERE order_status IN ('paid','processing','shipped','delivered')
               AND order_date >= :since",
            ['since' => $since]
        );

        /* ---------- Orders by status ---------- */
        $byStatus = db()->select(
            "SELECT order_status, COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev
             FROM orders
             GROUP BY order_status
             ORDER BY cnt DESC"
        );

        /* ---------- Top products ---------- */
        $topProducts = db()->select(
            "SELECT p.product_id, p.name, p.image_path,
                    COALESCE(SUM(oi.quantity),0) AS units_sold,
                    COALESCE(SUM(oi.line_total),0) AS revenue
             FROM products p
             JOIN order_items oi ON oi.product_id = p.product_id
             JOIN orders o ON o.order_id = oi.order_id
             WHERE o.order_status IN ('paid','processing','shipped','delivered')
             GROUP BY p.product_id, p.name, p.image_path
             ORDER BY revenue DESC
             LIMIT 10"
        );

        /* ---------- Top customers ---------- */
        $topCustomers = db()->select(
            "SELECT u.user_id, u.name, u.email,
                    COUNT(o.order_id) AS order_count,
                    COALESCE(SUM(o.total_amount),0) AS lifetime_value
             FROM users u
             JOIN orders o ON o.user_id = u.user_id
             WHERE o.order_status IN ('paid','processing','shipped','delivered')
             GROUP BY u.user_id, u.name, u.email
             ORDER BY lifetime_value DESC
             LIMIT 10"
        );

        /* ---------- Sales by category ---------- */
        $byCategory = db()->select(
            "SELECT c.category_name,
                    COUNT(oi.order_item_id) AS orders,
                    COALESCE(SUM(oi.line_total),0) AS revenue
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.category_id
             LEFT JOIN order_items oi ON oi.product_id = p.product_id
             LEFT JOIN orders o ON o.order_id = oi.order_id
                  AND o.order_status IN ('paid','processing','shipped','delivered')
             GROUP BY c.category_id, c.category_name
             ORDER BY revenue DESC"
        );

        return Response::html(view('admin.reports.index', [
            'title'        => 'Reports',
            'active'       => 'reports',
            'range'        => (string) $days,
            'salesByDay'   => $salesByDay,
            'salesTotal'   => $salesTotal,
            'byStatus'     => $byStatus,
            'topProducts'  => $topProducts,
            'topCustomers' => $topCustomers,
            'byCategory'   => $byCategory,
        ]));
    }
}