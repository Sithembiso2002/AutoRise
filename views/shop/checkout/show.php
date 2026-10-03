<?php
/** @var array $items */
/** @var float $subtotal */
/** @var array $user */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$action       = e(url('checkout'));
$cartUrl      = e(url('cart'));
$token        = e(csrf_token());
$name         = e($user['name'] ?? '');
$email        = e($user['email'] ?? '');

$rowsHtml = '';
foreach ($items as $it) {
    $n = e($it['name']);
    $q = (int) $it['qty'];
    $l = number_format((float) $it['line_total'], 2);
    $rowsHtml .= "<tr><td>{$n}</td><td class=\"text-center\">{$q}</td><td class=\"text-end\">M {$l}</td></tr>";
}
$subtotalFmt = number_format($subtotal, 2);

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:820px">
  <h2 class="mb-4">Checkout</h2>

  <div class="card p-4 mb-4">
    <h5 class="mb-3">Delivering to</h5>
    <p class="mb-1"><strong>{$name}</strong></p>
    <p class="text-secondary mb-0">{$email}</p>
  </div>

  <div class="card p-4 mb-4">
    <h5 class="mb-3">Order Summary</h5>
    <table class="table table-dark mb-0">
      <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead>
      <tbody>{$rowsHtml}</tbody>
      <tfoot>
        <tr>
          <th colspan="2">Subtotal</th>
          <th class="text-end price-tag">M {$subtotalFmt}</th>
        </tr>
      </tfoot>
    </table>
  </div>

  <form method="POST" action="{$action}">
    <input type="hidden" name="_token" value="{$token}">
    <div class="d-flex gap-3">
      <a href="{$cartUrl}" class="btn btn-outline-light">Back to Cart</a>
      <button type="submit" class="btn btn-red flex-grow-1">Place Order</button>
    </div>
  </form>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);