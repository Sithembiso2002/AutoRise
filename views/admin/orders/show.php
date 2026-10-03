<?php
/** @var array $order, $items, $payments, $history, $statuses */

$oid  = (int) $order['order_id'];
$ref  = e((string) ($order['order_ref'] ?? ('#' . $oid)));
$st   = (string) $order['order_status'];
$date = e(date('F j, Y g:i A', strtotime((string) $order['order_date'])));
$money = fn($n) => 'M ' . number_format((float) $n, 2);
$token = e(csrf_token());

/* Items rows */
$itemsHtml = '';
foreach ($items as $it) {
    $name = e((string) ($it['product_name'] ?? ('Product #' . $it['product_id'])));
    $qty  = (int) $it['quantity'];
    $u    = $money($it['unit_price']);
    $l    = $money($it['line_total'] ?? ((float) $it['unit_price'] * $qty));
    $itemsHtml .= "<tr><td>{$name}</td><td class=\"text-end\">{$u}</td><td class=\"text-end\">{$qty}</td><td class=\"text-end price-tag\">{$l}</td></tr>";
}
if ($itemsHtml === '') $itemsHtml = '<tr><td colspan="4" class="text-center text-secondary py-4">No items.</td></tr>';

/* Status dropdown */
$statusOpts = '';
foreach ($statuses as $s) {
    $sel = ($st === $s) ? ' selected' : '';
    $statusOpts .= '<option value="' . e($s) . '"' . $sel . '>' . e(ucfirst($s)) . '</option>';
}

/* Customer block */
$custName  = e((string) ($order['customer_name'] ?? 'Guest'));
$custEmail = e((string) ($order['customer_email'] ?? ''));
$custPhone = e((string) ($order['customer_phone'] ?? ''));
$custAddr  = e((string) ($order['customer_address'] ?? ''));
$custCity  = e((string) ($order['customer_city'] ?? ''));
$custCtry  = e((string) ($order['customer_country'] ?? ''));

/* Payments */
$payHtml = '';
if ($payments) {
    foreach ($payments as $p) {
        $pst  = (string) ($p['payment_status'] ?? 'unknown');
        $prov = e((string) ($p['provider'] ?? 'n/a'));
        $pr   = e((string) ($p['provider_ref'] ?? ''));
        $amt  = $money($p['amount'] ?? 0);
        $when = e((string) ($p['payment_date'] ?? ''));
        $payHtml .= "<tr><td>{$prov}</td><td><code class=\"text-secondary small\">{$pr}</code></td><td>{$amt}</td><td><span class=\"badge-status {$pst}\">{$pst}</span></td><td class=\"text-secondary small text-end\">{$when}</td></tr>";
    }
} else {
    $payHtml = '<tr><td colspan="5" class="text-center text-secondary py-4">No payments recorded.</td></tr>';
}

/* History */
$histHtml = '';
if ($history) {
    foreach ($history as $h) {
        $from = e((string) ($h['from_status'] ?? '—'));
        $to   = e((string) ($h['to_status'] ?? ''));
        $note = e((string) ($h['note'] ?? ''));
        $when = e(date('M j, g:i A', strtotime((string) $h['created_at'])));
        $histHtml .= "<tr><td><span class=\"badge-status " . e((string)($h['from_status'] ?? 'inactive')) . "\">{$from}</span> &rarr; <span class=\"badge-status {$to}\">{$to}</span></td><td class=\"small text-secondary\">{$note}</td><td class=\"text-end small text-secondary\">{$when}</td></tr>";
    }
} else {
    $histHtml = '<tr><td colspan="3" class="text-center text-secondary py-4">No status changes yet.</td></tr>';
}

$body = <<<HTML
<div class="mb-3">
  <a href="admin/orders" class="text-secondary small">&larr; Back to orders</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="admin-card">
      <h3 class="admin-card-title">
        <span>Order {$ref}</span>
        <span class="badge-status {$st}">{$st}</span>
      </h3>
      <p class="text-secondary small mb-3">Placed {$date}</p>

      <div class="table-responsive mb-4">
        <table class="admin-table">
          <thead><tr><th>Item</th><th class="text-end">Unit</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
          <tbody>{$itemsHtml}</tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Subtotal</span><span>{$money($order['subtotal'] ?? 0)}</span></div>
      <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Shipping</span><span>{$money($order['shipping_fee'] ?? 0)}</span></div>
      <div class="d-flex justify-content-between mb-3"><span class="text-secondary">Tax</span><span>{$money($order['tax_amount'] ?? 0)}</span></div>
      <hr>
      <div class="d-flex justify-content-between"><strong>Total</strong><strong class="price-tag">{$money($order['total_amount'] ?? 0)}</strong></div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card mb-3">
      <h3 class="admin-card-title">Customer</h3>
      <div><strong>{$custName}</strong></div>
      <div class="text-secondary small">{$custEmail}</div>
      <div class="text-secondary small">{$custPhone}</div>
      <hr>
      <div class="text-secondary small">{$custAddr}</div>
      <div class="text-secondary small">{$custCity}, {$custCtry}</div>
    </div>

    <div class="admin-card">
      <h3 class="admin-card-title">Update Status</h3>
      <form method="POST" action="{$ref}/../{$oid}/status" class="admin-form" style="margin-top:4px">
        <input type="hidden" name="_token" value="{$token}">
        <label class="form-label">New status</label>
        <select name="status" class="form-select mb-3">{$statusOpts}</select>
        <label class="form-label">Note (optional)</label>
        <input type="text" name="note" class="form-control mb-3" placeholder="Reason / note for history">
        <button class="btn btn-red w-100" type="submit"><i class="fa fa-check me-1"></i> Update Status</button>
      </form>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="admin-card">
      <h3 class="admin-card-title">Payment Records</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Provider</th><th>Reference</th><th>Amount</th><th>Status</th><th class="text-end">When</th></tr></thead>
          <tbody>{$payHtml}</tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="admin-card">
      <h3 class="admin-card-title">Status History</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Transition</th><th>Note</th><th class="text-end">When</th></tr></thead>
          <tbody>{$histHtml}</tbody>
        </table>
      </div>
    </div>
  </div>
</div>
HTML;

$body = str_replace('{$ref}/../{$oid}/status', url('admin/orders/' . $oid . '/status'), $body);

echo view('admin._layout', [
    'title'   => 'Order ' . $ref,
    'active'  => 'orders',
    'content' => $body,
]);