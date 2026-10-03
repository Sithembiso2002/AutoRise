<?php
/** @var array $items */
/** @var float $subtotal */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$checkoutUrl = e(url('checkout'));
$productsUrl = e(url('products'));
$updateUrl   = e(url('cart/update'));
$removeUrl   = e(url('cart/remove'));
$token       = e(csrf_token());

if (empty($items)) {
    $rowsHtml = '<tr><td colspan="5" class="text-center py-5 text-secondary">'
              . 'Your cart is empty. <a href="' . $productsUrl . '" class="text-danger">Browse products</a>.'
              . '</td></tr>';
} else {
    $rowsHtml = '';
    foreach ($items as $it) {
        $pid   = (int) $it['product_id'];
        $name  = e($it['name']);
        $price = number_format((float) $it['price'], 2);
        $qty   = (int) $it['qty'];
        $stock = (int) $it['stock'];
        $total = number_format((float) $it['line_total'], 2);
        $img   = product_image($it['image_path'], (string) $it['name']);

        $rowsHtml .= <<<HTML
        <tr>
          <td><img src="{$img}" style="width:80px;height:60px;object-fit:cover;border-radius:6px"></td>
          <td>{$name}</td>
          <td>M {$price}</td>
          <td>
            <form method="POST" action="{$updateUrl}" class="d-flex" style="max-width:140px">
              <input type="hidden" name="_token" value="{$token}">
              <input type="hidden" name="product_id" value="{$pid}">
              <input type="number" name="qty" value="{$qty}" min="1" max="{$stock}" class="form-control form-control-sm bg-dark text-light border-secondary me-2">
              <button class="btn btn-sm btn-outline-light">Update</button>
            </form>
          </td>
          <td class="text-end">
            <div class="price-tag mb-1">M {$total}</div>
            <form method="POST" action="{$removeUrl}" class="d-inline">
              <input type="hidden" name="_token" value="{$token}">
              <input type="hidden" name="product_id" value="{$pid}">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
        HTML;
    }
}

$subtotalFmt = number_format($subtotal, 2);

$body = $flashHtml . <<<HTML
<div class="container my-5">
  <h2 class="mb-4">Your Cart</h2>
  <div class="table-responsive">
    <table class="table table-dark table-hover align-middle">
      <thead>
        <tr>
          <th></th>
          <th>Product</th>
          <th>Price</th>
          <th>Qty</th>
          <th class="text-end">Line Total</th>
        </tr>
      </thead>
      <tbody>{$rowsHtml}</tbody>
    </table>
  </div>

  <div class="row mt-4">
    <div class="col-md-6 offset-md-6">
      <div class="card p-4">
        <div class="d-flex justify-content-between mb-3">
          <span>Subtotal</span>
          <strong class="price-tag">M {$subtotalFmt}</strong>
        </div>
        <a href="{$checkoutUrl}" class="btn btn-red w-100">Proceed to Checkout</a>
        <a href="{$productsUrl}" class="btn btn-link text-secondary w-100 mt-2">Continue shopping</a>
      </div>
    </div>
  </div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);