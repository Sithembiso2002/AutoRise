<?php
/** @var array $user */
/** @var array $orders */

$productsUrl = e(url('products'));

if (empty($orders)) {
    $body = '<div class="alert alert-secondary">'
          . 'You have no orders yet. <a href="' . $productsUrl . '" class="text-danger">Start shopping</a>.'
          . '</div>';
} else {
    $rows = '';
    foreach ($orders as $o) {
        $oid     = (int) $o['order_id'];
        $date    = e(date('M j, Y g:ia', strtotime((string) $o['order_date'])));
        $sRaw    = (string) $o['order_status'];
        $status  = e(ucfirst($sRaw));
        $total   = number_format((float) $o['total_amount'], 2);
        $url     = e(url("orders/{$oid}"));
        $badge = match ($sRaw) {
            'pending'   => 'bg-warning text-dark',
            'paid'      => 'bg-primary',
            'shipped'   => 'bg-info text-dark',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-secondary',
            default     => 'bg-secondary',
        };
        $rows .= <<<HTML
        <tr>
          <td><a href="{$url}" class="text-danger fw-bold">#{$oid}</a></td>
          <td>{$date}</td>
          <td><span class="badge {$badge}">{$status}</span></td>
          <td class="text-end price-tag">M {$total}</td>
          <td class="text-end"><a href="{$url}" class="btn btn-sm btn-outline-light">View</a></td>
        </tr>
        HTML;
    }

    $body = <<<HTML
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle mb-0">
        <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
        <tbody>{$rows}</tbody>
      </table>
    </div>
    HTML;
}

$count = count($orders);

$inner = <<<HTML
<div class="profile-content-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="profile-section-title mb-0" style="border:none;padding:0">My Orders</h3>
    <span class="text-secondary small">{$count} total</span>
  </div>
  {$body}
</div>
HTML;

echo view('shop.profile._shell', [
    'activeTab' => 'orders',
    'user'      => $user,
    'inner'     => $inner,
    'title'     => $title,
]);