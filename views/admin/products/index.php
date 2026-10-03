<?php
/** @var array $rows, $categories */
/** @var string $q, $status */
/** @var int $catId, $page, $perPage, $total */

$lastPage = max(1, (int) ceil($total / $perPage));

$catOptions = '<option value="">All Categories</option>';
foreach ($categories as $c) {
    $sel = ((int) $c['category_id'] === $catId) ? ' selected' : '';
    $catOptions .= '<option value="' . (int) $c['category_id'] . '"' . $sel . '>' . e($c['category_name']) . '</option>';
}

$statusOptions = '';
foreach (['' => 'All Statuses', 'active' => 'Active', 'inactive' => 'Archived'] as $val => $lbl) {
    $sel = ($status === $val) ? ' selected' : '';
    $statusOptions .= '<option value="' . e($val) . '"' . $sel . '>' . e($lbl) . '</option>';
}

$token = e(csrf_token());
$tableRows = '';
if ($rows) {
    foreach ($rows as $p) {
        $pid     = (int) $p['product_id'];
        $name    = e($p['name']);
        $sku     = e((string) ($p['sku'] ?? '—'));
        $cat     = e((string) ($p['category_name'] ?? 'Uncategorized'));
        $price   = 'M ' . number_format((float) $p['price'], 2);
        $stock   = (int) $p['stock'];
        $active  = (int) $p['is_active'] === 1;
        $img     = product_image($p['image_path'], (string) $p['name']);
        $editUrl = e(url('admin/products/' . $pid . '/edit'));

        $stockBadge = $stock <= 0
            ? '<span class="badge-status refunded">Out</span>'
            : ($stock <= (int) $p['low_stock_threshold']
                ? '<span class="badge-status pending">' . $stock . '</span>'
                : '<span class="badge-status active">' . $stock . '</span>');

        $statusBadge = $active
            ? '<span class="badge-status active">Active</span>'
            : '<span class="badge-status inactive">Archived</span>';

        $actionBtn = $active
            ? '<form method="POST" action="' . e(url('admin/products/' . $pid . '/delete')) . '" class="d-inline m-0" onsubmit="return confirm(\'Archive this product?\');">'
              . '<input type="hidden" name="_token" value="' . $token . '">'
              . '<button class="btn btn-sm btn-outline-danger" title="Archive"><i class="fa fa-trash"></i></button></form>'
            : '<form method="POST" action="' . e(url('admin/products/' . $pid . '/restore')) . '" class="d-inline m-0">'
              . '<input type="hidden" name="_token" value="' . $token . '">'
              . '<button class="btn btn-sm btn-outline-success" title="Restore"><i class="fa fa-undo"></i></button></form>';

        $tableRows .= <<<HTML
        <tr>
          <td><img src="{$img}" class="thumb" alt=""></td>
          <td>
            <a href="{$editUrl}" class="text-danger fw-bold">{$name}</a>
            <div class="small text-secondary">{$sku}</div>
          </td>
          <td>{$cat}</td>
          <td class="text-end price-tag">{$price}</td>
          <td class="text-end">{$stockBadge}</td>
          <td>{$statusBadge}</td>
          <td class="text-end">
            <div class="actions">
              <a href="{$editUrl}" class="btn btn-sm btn-outline-light" title="Edit"><i class="fa fa-pencil"></i></a>
              {$actionBtn}
            </div>
          </td>
        </tr>
        HTML;
    }
} else {
    $tableRows = '<tr><td colspan="7" class="text-center text-secondary py-5">No products found.</td></tr>';
}

$pager = '';
if ($lastPage > 1) {
    $pager = '<div class="admin-pagination">';
    $mk = fn($i, $label, $cls = '') => '<a class="' . $cls . '" href="?' . e(http_build_query(['page' => $i, 'q' => $q, 'category' => $catId, 'status' => $status])) . '">' . $label . '</a>';
    if ($page > 1) $pager .= $mk($page - 1, '&laquo;');
    for ($i = 1; $i <= $lastPage; $i++) {
        $pager .= $i === $page
            ? '<span class="active">' . $i . '</span>'
            : $mk($i, (string) $i);
    }
    if ($page < $lastPage) $pager .= $mk($page + 1, '&raquo;');
    $pager .= '</div>';
}

$body = <<<HTML
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <h2 class="h5 mb-0">Products <span class="text-secondary">({$total})</span></h2>
  <a href="admin/products/create" class="btn btn-red"><i class="fa fa-plus me-1"></i> New Product</a>
</div>

<form method="GET" action="admin/products" class="filter-bar">
  <input type="text" name="q" value="{$q}" placeholder="Search name or SKU..." class="form-control grow">
  <select name="category" class="form-select" style="max-width:200px">{$catOptions}</select>
  <select name="status" class="form-select" style="max-width:160px">{$statusOptions}</select>
  <button class="btn btn-red">Filter</button>
  <a href="admin/products" class="btn btn-outline-light">Reset</a>
</form>

<div class="admin-table-wrap">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width:70px"></th>
          <th>Product</th>
          <th>Category</th>
          <th class="text-end">Price</th>
          <th class="text-end">Stock</th>
          <th>Status</th>
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
    'title'   => 'Products',
    'active'  => 'products',
    'content' => $body,
]);