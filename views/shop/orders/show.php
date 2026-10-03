<?php
/** @var array $order */
/** @var array $items */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$oid       = (int) $order['order_id'];
$date      = e(date('F j, Y g:i A', strtotime((string) $order['order_date'])));
$statusRaw = (string) $order['order_status'];
$status    = e(ucfirst($statusRaw));
$total     = number_format((float) $order['total_amount'], 2);
$ordersUrl = e(url('orders'));
$payUrl    = e(url("orders/{$oid}/pay"));

$badgeClass = match ($statusRaw) {
    'pending'   => 'bg-warning text-dark',
    'paid'      => 'bg-primary',
    'shipped'   => 'bg-info text-dark',
    'delivered' => 'bg-success',
    'cancelled' => 'bg-secondary',
    default     => 'bg-secondary',
};

$rows = '';
foreach ($items as $it) {
    $n = e($it['product_name'] ?? 'Product #' . $it['product_id']);
    $q = (int) $it['quantity'];
    $p = number_format((float) $it['unit_price'], 2);
    $l = number_format((float) ($it['unit_price'] * $it['quantity']), 2);
    $rows .= "<tr><td>{$n}</td><td class=\"text-center\">{$q}</td><td class=\"text-end\">M {$p}</td><td class=\"text-end\">M {$l}</td></tr>";
}

$payBanner = $statusRaw === 'pending'
    ? <<<HTML
      <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span>This order is awaiting payment.</span>
        <a href="{$payUrl}" class="btn btn-red">Pay Now</a>
      </div>
      HTML
    : '';

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:820px">
  <a href="{$ordersUrl}" class="text-secondary text-decoration-none small">&larr; Back to orders</a>
  <h2 class="mt-2 mb-3">Order #{$oid}</h2>
  <p class="text-secondary">Placed on {$date} &middot; <span class="badge {$badgeClass}">{$status}</span></p>

  {$payBanner}

  <div class="card p-4 mb-4">
    <table class="table table-dark mb-0">
      <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr></thead>
      <tbody>{$rows}</tbody>
      <tfoot>
        <tr><th colspan="3" class="text-end">Grand Total</th><th class="text-end price-tag">M {$total}</th></tr>
      </tfoot>
    </table>
  </div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);