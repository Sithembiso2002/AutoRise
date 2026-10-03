<?php
/** @var array $user */
/** @var array $stats */
/** @var array $recentOrders */

$ordersUrl   = e(url('profile/orders'));
$productsUrl = e(url('products'));

$firstName = e(explode(' ', trim((string) ($user['name'] ?? 'there')))[0] ?? 'there');
$spent     = number_format((float) $stats['spent'], 2);

// Recent orders table rows
$rows = '';
if (empty($recentOrders)) {
    $rows = '<tr><td colspan="5" class="text-center py-5 text-secondary">'
          . 'No orders yet. <a href="' . $productsUrl . '" class="text-danger">Start shopping</a>.'
          . '</td></tr>';
} else {
    foreach ($recentOrders as $o) {
        $oid      = (int) $o['order_id'];
        $date     = e(date('M j, Y', strtotime((string) $o['order_date'])));
        $statusRaw= (string) $o['order_status'];
        $status   = e(ucfirst($statusRaw));
        $total    = number_format((float) $o['total_amount'], 2);
        $showUrl  = e(url("orders/{$oid}"));
        $badge = match ($statusRaw) {
            'pending'   => 'bg-warning text-dark',
            'paid'      => 'bg-primary',
            'shipped'   => 'bg-info text-dark',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-secondary',
            default     => 'bg-secondary',
        };
        $rows .= <<<HTML
        <tr>
          <td><a href="{$showUrl}" class="text-danger">#{$oid}</a></td>
          <td>{$date}</td>
          <td><span class="badge {$badge}">{$status}</span></td>
          <td class="text-end price-tag">M {$total}</td>
          <td class="text-end"><a href="{$showUrl}" class="btn btn-sm btn-outline-light">View</a></td>
        </tr>
        HTML;
    }
}

$inner = <<<HTML
<div class="profile-content-card mb-4">
  <h2 class="mb-1">Welcome back, {$firstName}!</h2>
  <p class="text-secondary mb-0">Here's a summary of your account activity.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="fa fa-shopping-bag"></i></div>
      <div class="stat-value">{$stats['orders']}</div>
      <div class="stat-label">Total Orders</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="fa fa-money"></i></div>
      <div class="stat-value">M {$spent}</div>
      <div class="stat-label">Total Spent</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
      <div class="stat-value">{$stats['pending']}</div>
      <div class="stat-label">Pending Orders</div>
    </div>
  </div>
</div>

<div class="profile-content-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="profile-section-title mb-0" style="border:none;padding:0">Recent Orders</h3>
    <a href="{$ordersUrl}" class="btn btn-red-outline btn-sm">View All</a>
  </div>
  <div class="table-responsive">
    <table class="table table-dark align-middle mb-0">
      <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</div>
HTML;

echo view('shop.profile._shell', [
    'activeTab' => 'overview',
    'user'      => $user,
    'inner'     => $inner,
    'title'     => $title,
]);