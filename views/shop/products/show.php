<?php
/** @var array $product */
/** @var array $related */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$id       = (int) $product['product_id'];
$name     = e($product['name']);
$desc     = e((string) ($product['description'] ?? ''));
$cat      = e((string) ($product['category_name'] ?? 'Uncategorized'));
$price    = number_format((float) $product['price'], 2);
$stock    = (int) $product['stock'];
$action   = e(url('cart/add'));
$token    = e(csrf_token());
$backUrl  = e(url("products/{$id}"));
$browseUrl = e(url('products'));
$img      = product_image($product['image_path'], (string) $product['name']);

$stockBadge = $stock > 0
    ? '<span class="badge bg-success">' . $stock . ' in stock</span>'
    : '<span class="badge bg-secondary">Out of stock</span>';

// Related products
$relatedHtml = '';
foreach ($related as $r) {
    $rid    = (int) $r['product_id'];
    $rname  = e($r['name']);
    $rprice = number_format((float) $r['price'], 2);
    $rimg   = product_image($r['image_path'], (string) $r['name']);
    $rurl   = e(url("products/{$rid}"));
    $relatedHtml .= <<<HTML
    <div class="col-6 col-md-3 mb-3">
      <a href="{$rurl}" class="text-decoration-none">
        <div class="card h-100">
          <img src="{$rimg}" class="card-img-top" style="height:120px">
          <div class="card-body p-2 text-center">
            <div class="small">{$rname}</div>
            <div class="price-tag">M {$rprice}</div>
          </div>
        </div>
      </a>
    </div>
    HTML;
}
if ($relatedHtml === '') $relatedHtml = '<p class="text-secondary">No related products.</p>';

$cartButton = $stock > 0
    ? <<<HTML
      <form method="POST" action="{$action}" class="row g-2 mt-3">
        <input type="hidden" name="_token" value="{$token}">
        <input type="hidden" name="product_id" value="{$id}">
        <input type="hidden" name="back" value="{$backUrl}">
        <div class="col-4 col-md-3">
          <input type="number" name="qty" value="1" min="1" max="{$stock}" class="form-control bg-dark text-light border-secondary">
        </div>
        <div class="col-8 col-md-4">
          <button class="btn btn-red w-100"><i class="fa fa-cart-plus"></i> Add to Cart</button>
        </div>
      </form>
      HTML
    : '<p class="text-danger mt-3 mb-0">This item is out of stock.</p>';

$body = $flashHtml . <<<HTML
<div class="container my-5">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{$browseUrl}" class="text-danger">Products</a></li>
      <li class="breadcrumb-item active text-secondary">{$name}</li>
    </ol>
  </nav>

  <div class="row g-4">
    <div class="col-md-6">
      <img src="{$img}" class="img-fluid rounded" alt="{$name}">
    </div>
    <div class="col-md-6">
      <div class="text-secondary small mb-1">{$cat}</div>
      <h1 class="mb-3">{$name}</h1>
      <div class="mb-3">
        <span class="price-tag" style="font-size:2rem">M {$price}</span>
        <span class="ms-3">{$stockBadge}</span>
      </div>
      <p>{$desc}</p>
      {$cartButton}
    </div>
  </div>

  <hr class="my-5 border-secondary">

  <h3 class="mb-4">You Might Also Like</h3>
  <div class="row">{$relatedHtml}</div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);