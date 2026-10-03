<?php
/** @var array $rows, $statuses */
/** @var string $q, $status */
/** @var int $page, $perPage, $total */

$lastPage = max(1, (int) ceil($total / $perPage));
$token = e(csrf_token());

$statusOptions = '<option value="">All Statuses</option>';
foreach ($statuses as $s) {
    $sel = ($status === $s) ? ' selected' : '';
    $statusOptions .= '<option value="' . e($s) . '"' . $sel . '>' . e(ucfirst($s)) . '</option>';
}

$tableRows = '';
if ($rows) {
    foreach ($rows as $o) {
        $oid  = (int) $o['order_id'];
        $ref  = e((string) ($o['order_ref'] ?? ('#' . $oid)));
        $cust = e((string) ($o['customer_name'] ?? 'Guest'));
        $mail = e((string) ($o['customer_email'] ?? ''));
        $tot  = 'M ' . number_format((float) $o['total_amount'], 2);
        $st   = (string) $o['order_status'];
        $date = e(date('M j, Y', strtotime((string) $o['order_date'])));
        $url  = e(url('admin/orders/' . $oid));

        $tableRows .= <<<HTML
        <tr>
          <td><a href="{$url}" class="text-danger fw-bold">{$ref}</a></td>
          <td>
            <div>{$cust}</div>
            <div class="small text-secondary">{$mail}</div>
          </td>
          <td class="text-end price-tag">{$tot}</td>
          <td><span class="badge-status {$st}">{$st}</span></td>
          <td class="text-end text-secondary small">{$date}</td>
          <td class="text-end">
            <a href="{$url}" class="btn btn-sm btn-outline-light">View</a>
          </td>
        </tr>
        HTML;
    }
} else {
    $tableRows = '<tr><td colspan="6" class="text-center text-secondary py-5">No orders found.</td></tr>';
}

$pager = '';
if ($lastPage > 1) {
    $pager = '<div class="admin-pagination">';
    $mk = fn($i, $l) => '<a href="?' . e(http_build_query(['page' => $i, 'q' => $q, 'status' => $status])) . '">' . $l . '</a>';
    if ($page > 1) $pager .= $mk($page - 1, '&laquo;');
    for ($i = 1; $i <= $lastPage; $i++) {
        $pager .= $i === $page ? '<span class="active">' . $i . '</span>' : $mk($i, (string) $i);
    }
    if ($page < $lastPage) $pager .= $mk($page + 1, '&raquo;');
    $pager .= '</div>';
}

$body = <<<HTML
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <h2 class="h5 mb-0">Orders <span class="text-secondary">({$total})</span></h2>
</div>

<form method="GET" action="admin/orders" class="filter-bar">
  <input type="text" name="q" value="{$q}" placeholder="Search order, customer, email..." class="form-control grow">
  <select name="status" class="form-select" style="max-width:180px">{$statusOptions}</select>
  <button class="btn btn-red">Filter</button>
  <a href="admin/orders" class="btn btn-outline-light">Reset</a>
</form>

<div class="admin-table-wrap">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th class="text-end">Total</th>
          <th>Status</th>
          <th class="text-end">Date</th>
          <th class="text-end"></th>
        </tr>
      </thead>
      <tbody>{$tableRows}</tbody>
    </table>
  </div>
</div>

{$pager}
HTML;

echo view('admin._layout', [
    'title'   => 'Orders',
    'active'  => 'orders',
    'content' => $body,
]);