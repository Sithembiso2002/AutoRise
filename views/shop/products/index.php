<?php
/** @var array $products */
/** @var array $categories */
/** @var int $page, $total, $perPage, $catId */
/** @var string $search */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$lastPage    = max(1, (int) ceil($total / $perPage));
$productsUrl = e(url('products'));

// Build filter dropdown
$catOptions = '<option value="">All Categories</option>';
foreach ($categories as $c) {
    $sel = ((int) $c['category_id'] === $catId) ? ' selected' : '';
    $catOptions .= '<option value="' . (int) $c['category_id'] . '"' . $sel . '>'
                 . e($c['category_name']) . '</option>';
}

// Build product grid
$grid = '';
foreach ($products as $p) {
    $id      = (int) $p['product_id'];
    $name    = e($p['name']);
    $desc    = e(mb_strimwidth((string) ($p['description'] ?? ''), 0, 90, '...'));
    $price   = number_format((float) $p['price'], 2);
    $stock   = (int) $p['stock'];
    $urlShow = e(url("products/{$id}"));
    $img     = product_image($p['image_path'], (string) $p['name']);

    $stockBadge = $stock > 0
        ? '<span class="badge bg-success">' . $stock . ' in stock</span>'
        : '<span class="badge bg-secondary">Out of stock</span>';

    $grid .= <<<HTML
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card h-100">
        <a href="{$urlShow}"><img src="{$img}" class="card-img-top" alt="{$name}"></a>
        <div class="card-body d-flex flex-column">
          <h5 class="card-title">{$name}</h5>
          <p class="card-text small text-secondary flex-grow-1">{$desc}</p>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="price-tag">M {$price}</span>
            {$stockBadge}
          </div>
          <a href="{$urlShow}" class="btn btn-red btn-sm">View Details</a>
        </div>
      </div>
    </div>
    HTML;
}

if ($grid === '') {
    $grid = '<div class="col-12"><div class="alert alert-secondary">No products found.</div></div>';
}

// Pagination
$pager = '';
if ($lastPage > 1) {
    $pager = '<nav class="mt-4"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $lastPage; $i++) {
        $active = $i === $page ? ' active' : '';
        $q = http_build_query(['page' => $i, 'q' => $search ?: null, 'category' => $catId ?: null]);
        $pager .= '<li class="page-item' . $active . '"><a class="page-link bg-dark text-light border-secondary" href="' . $productsUrl . '?' . e($q) . '">' . $i . '</a></li>';
    }
    $pager .= '</ul></nav>';
}

$searchVal  = e($search);
$totalCount = (int) $total;

$body = $flashHtml . <<<HTML
<div class="container my-4">
  <div class="row align-items-center mb-4">
    <div class="col-md-6"><h2 class="mb-0">Browse Cars</h2>
      <p class="text-secondary mb-0">{$totalCount} product(s) found</p>
    </div>
    <div class="col-md-6">
      <form method="GET" action="{$productsUrl}" class="row g-2">
        <div class="col-7">
          <input type="text" name="q" value="{$searchVal}" class="form-control bg-dark text-light border-secondary" placeholder="Search...">
        </div>
        <div class="col-3">
          <select name="category" class="form-select bg-dark text-light border-secondary">
            {$catOptions}
          </select>
        </div>
        <div class="col-2">
          <button class="btn btn-red w-100">Filter</button>
        </div>
      </form>
    </div>
  </div>
  <div class="row">{$grid}</div>
  {$pager}
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);