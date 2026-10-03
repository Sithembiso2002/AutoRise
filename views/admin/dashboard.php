<?php
/** @var float $revenue */
/** @var int $paidOrders, $allOrders, $pendingOrders, $productCount, $customerCount */
/** @var array $lowStock, $recentOrders, $topProducts, $salesLast7 */

$money = fn($n) => 'M ' . number_format((float) $n, 2);

/* Sales bar chart: last 7 days */
$maxRev = 0.0;
foreach ($salesLast7 as $d) $maxRev = max($maxRev, (float) $d['rev']);
$bars = '';
if ($salesLast7) {
    foreach ($salesLast7 as $d) {
        $h = $maxRev > 0 ? max(4, (int) round(((float) $d['rev'] / $maxRev) * 160)) : 4;
        $label = date('D', strtotime((string) $d['d']));
        $val   = number_format((float) $d['rev'], 0);
        $bars .= '<div class="bar" style="height:' . $h . 'px" title="' . e($label . ': M ' . $val) . '"><span>' . e($label) . '</span></div>';
    }
} else {
    $bars = '<div style="color:#666;padding:60px 0;text-align:center;width:100%">No sales in the last 7 days.</div>';
}

/* Recent orders rows */
$ordersHtml = '';
if ($recentOrders) {
    foreach ($recentOrders as $o) {
        $oid  = (int) $o['order_id'];
        $ref  = e((string) ($o['order_ref'] ?? ('#' . $oid)));
        $cust = e((string) ($o['customer_name'] ?? 'Guest'));
        $tot  = $money($o['total_amount']);
        $st   = (string) $o['order_status'];
        $url  = e(url('admin/orders/' . $oid));
        $date = e(date('M j', strtotime((string) $o['order_date'])));
        $ordersHtml .= <<<HTML
        <tr>
          <td><a href="{$url}" class="text-danger fw-bold">{$ref}</a></td>
          <td>{$cust}</td>
          <td class="text-end">{$tot}</td>
          <td><span class="badge-status {$st}">{$st}</span></td>
          <td class="text-end text-secondary small">{$date}</td>
        </tr>
        HTML;
    }
} else {
    $ordersHtml = '<tr><td colspan="5" class="text-center text-secondary py-4">No orders yet.</td></tr>';
}

/* Low stock rows */
$lowHtml = '';
if ($lowStock) {
    foreach ($lowStock as $p) {
        $n = e($p['name']);
        $s = (int) $p['stock'];
        $t = (int) $p['low_stock_threshold'];
        $u = e(url('admin/products/' . (int) $p['product_id'] . '/edit'));
        $cls = $s === 0 ? 'refunded' : 'pending';
        $lowHtml .= <<<HTML
        <tr>
          <td><a href="{$u}" class="text-danger">{$n}</a></td>
          <td class="text-end"><span class="badge-status {$cls}">{$s}</span></td>
          <td class="text-end text-secondary small">min {$t}</td>
        </tr>
        HTML;
    }
} else {
    $lowHtml = '<tr><td colspan="3" class="text-center text-secondary py-4">All stocked up.</td></tr>';
}

/* Top products rows */
$topHtml = '';
if ($topProducts) {
    foreach ($topProducts as $p) {
        $n = e($p['name']);
        $u = e(url('admin/products/' . (int) $p['product_id'] . '/edit'));
        $us= (int) $p['units_sold'];
        $rv= $money($p['revenue']);
        $topHtml .= <<<HTML
        <tr>
          <td><a href="{$u}" class="text-danger">{$n}</a></td>
          <td class="text-end">{$us}</td>
          <td class="text-end price-tag">{$rv}</td>
        </tr>
        HTML;
    }
} else {
    $topHtml = '<tr><td colspan="3" class="text-center text-secondary py-4">No sales yet.</td></tr>';
}

$body = <<<HTML
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-money"></i></div>
      <div class="kpi-value">{$money($revenue)}</div>
      <div class="kpi-label">Total Revenue</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-shopping-bag"></i></div>
      <div class="kpi-value">{$paidOrders}</div>
      <div class="kpi-label">Paid Orders</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-clock-o"></i></div>
      <div class="kpi-value">{$pendingOrders}</div>
      <div class="kpi-label">Pending</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-users"></i></div>
      <div class="kpi-value">{$customerCount}</div>
      <div class="kpi-label">Customers</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="admin-card">
      <h3 class="admin-card-title">Sales — Last 7 Days</h3>
      <div class="bar-chart">{$bars}</div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <h3 class="admin-card-title">Low Stock</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Min</th></tr></thead>
          <tbody>{$lowHtml}</tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="admin-card">
      <h3 class="admin-card-title">
        <span>Recent Orders</span>
        <a href="admin/orders" class="btn btn-red btn-sm">View All</a>
      </h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Order</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th class="text-end">Date</th></tr></thead>
          <tbody>{$ordersHtml}</tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="admin-card">
      <h3 class="admin-card-title">Top Selling Products</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Product</th><th class="text-end">Sold</th><th class="text-end">Revenue</th></tr></thead>
          <tbody>{$topHtml}</tbody>
        </table>
      </div>
    </div>
  </div>
</div>
HTML;

echo view('admin._layout', [
    'title'   => 'Dashboard',
    'active'  => 'dashboard',
    'content' => $body,
]);