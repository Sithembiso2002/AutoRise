<?php
/** @var array $order */
/** @var array $payment */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$oid        = (int) $order['order_id'];
$total      = number_format((float) $order['total_amount'], 2);
$provider   = e((string) $payment['provider']);
$ref        = e((string) $payment['provider_ref']);
$status     = e((string) $payment['payment_status']);
$actionUrl  = e(url("orders/{$oid}/pay"));
$cancelUrl  = e(url("orders/{$oid}"));
$token      = e(csrf_token());

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:640px">
  <h2 class="mb-1">Payment</h2>
  <p class="text-secondary">Order #{$oid}</p>

  <div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <span>Amount due</span>
      <strong class="price-tag" style="font-size:2rem">M {$total}</strong>
    </div>
    <hr class="border-secondary">
    <div class="small text-secondary">
      <div>Gateway: <code>{$provider}</code></div>
      <div>Reference: <code>{$ref}</code></div>
      <div>Status: <code>{$status}</code></div>
    </div>
  </div>

  <form method="POST" action="{$actionUrl}" class="d-grid gap-2">
    <input type="hidden" name="_token" value="{$token}">
    <button type="submit" class="btn btn-red btn-lg">Pay Now</button>
  </form>

  <div class="d-grid gap-2 mt-2">
    <a href="{$cancelUrl}" class="btn btn-outline-light">Cancel and view order</a>
  </div>

  <p class="text-secondary small mt-4">
    Running in <strong>mock mode</strong>. The gateway will approve ~85% of
    attempts at random so you can test both success and failure paths.
    Set <code>MOCK_PAYMENT_OUTCOME=success</code> or <code>failure</code>
    in <code>.env</code> to force a specific outcome.
  </p>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);