<?php
/** @var array $orders */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$productsUrl = e(url('products'));

if (empty($orders)) {
    $body = $flashHtml . <<<HTML
    <div class="container my-5">
      <h2 class="mb-4">Your Orders</h2>
      <div class="alert alert-secondary">
        You have no orders yet. <a href="{$productsUrl}" class="text-danger">Start shopping</a>.
      </div>
    </div>
    HTML;
} else {
    $rows = '';
    foreach ($orders as $o) {
        $id      = (int) $o['order_id'];
        $date    = e(date('M j, Y g:ia', strtotime((string) $o['order_date'])));
        $status  = e(ucfirst((string) $o['order_status']));
        $total   = number_format((float) $o['total_amount'], 2);
        $showUrl = e(url("orders/{$id}"));
        $badgeClass = match ($o['order_status']) {
            'pending'   => 'bg-warning text-dark',
            'shipped'   => 'bg-info text-dark',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-secondary',
            default     => 'bg-secondary',
        };
        $rows .= <<<HTML
        <tr>
          <td><a href="{$showUrl}" class="text-danger">#{$id}</a></td>
          <td>{$date}</td>
          <td><span class="badge {$badgeClass}">{$status}</span></td>
          <td class="text-end price-tag">M {$total}</td>
          <td class="text-end"><a href="{$showUrl}" class="btn btn-sm btn-outline-light">View</a></td>
        </tr>
        HTML;
    }

    $body = $flashHtml . <<<HTML
    <div class="container my-5">
      <h2 class="mb-4">Your Orders</h2>
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
          <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
          <tbody>{$rows}</tbody>
        </table>
      </div>
    </div>
    HTML;
}

echo view('layouts.app', ['title' => $title, 'content' => $body]);