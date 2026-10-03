<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;

final class DashboardController
{
    public function index(Request $request): Response
    {
        /* ---------- KPI totals ---------- */
        $revenueRow = db()->selectOne(
            "SELECT COALESCE(SUM(total_amount),0) AS total, COUNT(*) AS cnt
             FROM orders
             WHERE order_status IN ('paid','processing','shipped','delivered')"
        );
        $revenue    = (float) ($revenueRow['total'] ?? 0);
        $paidOrders = (int)   ($revenueRow['cnt']   ?? 0);

        $allOrders     = (int) db()->scalar("SELECT COUNT(*) FROM orders");
        $pendingOrders = (int) db()->scalar("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
        $productCount  = (int) db()->scalar("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $customerCount = (int) db()->scalar("SELECT COUNT(*) FROM users WHERE role = 'customer'");

        /* ---------- Low stock ---------- */
        $lowStock = db()->select(
            "SELECT product_id, name, stock, low_stock_threshold
             FROM products
             WHERE is_active = 1 AND stock <= low_stock_threshold
             ORDER BY stock ASC
             LIMIT 5"
        );

        /* ---------- Recent orders ---------- */
        $recentOrders = db()->select(
            "SELECT o.order_id, o.order_ref, o.total_amount, o.order_status, o.order_date,
                    u.name AS customer_name, u.email AS customer_email
             FROM orders o
             LEFT JOIN users u ON u.user_id = o.user_id
             ORDER BY o.order_id DESC
             LIMIT 8"
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
             ORDER BY units_sold DESC
             LIMIT 5"
        );

        /* ---------- Sales last 7 days ----------
           Compute "since" in PHP so the same SQL runs on both MySQL and SQLite. */
        $since = date('Y-m-d H:i:s', strtotime('-6 days'));

        $salesLast7 = db()->select(
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

        return Response::html(view('admin.dashboard', [
            'title'         => 'Dashboard',
            'active'        => 'dashboard',
            'revenue'       => $revenue,
            'paidOrders'    => $paidOrders,
            'allOrders'     => $allOrders,
            'pendingOrders' => $pendingOrders,
            'productCount'  => $productCount,
            'customerCount' => $customerCount,
            'lowStock'      => $lowStock,
            'recentOrders'  => $recentOrders,
            'topProducts'   => $topProducts,
            'salesLast7'    => $salesLast7,
        ]));
    }
}