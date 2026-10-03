<?php
/** @var array|null $product */
/** @var array $categories */

$isEdit = $product !== null;
$action = $isEdit ? e(url('admin/products/' . (int) $product['product_id'])) : e(url('admin/products'));
$token  = e(csrf_token());

$val = fn(string $k, $d = '') => e((string) (old($k, $isEdit ? ($product[$k] ?? $d) : $d)));

$catOptions = '<option value="">Select a category</option>';
$currentCat = (int) ($product['category_id'] ?? 0);
foreach ($categories as $c) {
    $sel = ((int) $c['category_id'] === $currentCat) ? ' selected' : '';
    $catOptions .= '<option value="' . (int) $c['category_id'] . '"' . $sel . '>' . e($c['category_name']) . '</option>';
}

$imageUrl = $isEdit ? product_image($product['image_path'] ?? null, (string) $product['name']) : null;
$imageBlock = $imageUrl
    ? '<img src="' . e($imageUrl) . '" alt="" class="img-fluid rounded mb-2" style="max-height:180px">'
    : '<div class="text-secondary small mb-2">No image uploaded yet.</div>';

$body = <<<HTML
<div class="mb-3">
  <a href="admin/products" class="text-secondary small">&larr; Back to products</a>
</div>

<form method="POST" action="{$action}" enctype="multipart/form-data" class="admin-form">
  <input type="hidden" name="_token" value="{$token}">

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="admin-card mb-4">
        <h3 class="admin-card-title">Basic Info</h3>

        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Product name *</label>
            <input type="text" name="name" value="{$val('name')}" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" value="{$val('sku')}" class="form-control" placeholder="Auto-generated if blank">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" rows="5" class="form-control">{$val('description')}</textarea>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h3 class="admin-card-title">Pricing &amp; Inventory</h3>

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Price (M) *</label>
            <input type="number" step="0.01" min="0" name="price" value="{$val('price', '0.00')}" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock *</label>
            <input type="number" min="0" name="stock" value="{$val('stock', '0')}" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Low Stock Alert</label>
            <input type="number" min="0" name="low_stock_threshold" value="{$val('low_stock_threshold', '5')}" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Category *</label>
            <select name="category_id" class="form-select" required>
              {$catOptions}
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-card">
        <h3 class="admin-card-title">Product Image</h3>
        {$imageBlock}
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control">
        <div class="form-text text-secondary mt-2">JPG, PNG, WebP or GIF. Max 4MB.</div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-red px-4">
      <i class="fa fa-save me-1"></i> {$saveLabel}
    </button>
    <a href="admin/products" class="btn btn-outline-light">Cancel</a>
  </div>
</form>
HTML;

$saveLabel = $isEdit ? 'Save Changes' : 'Create Product';
$body = str_replace('{$saveLabel}', $saveLabel, $body);

echo view('admin._layout', [
    'title'   => $isEdit ? 'Edit Product' : 'New Product',
    'active'  => 'products',
    'content' => $body,
]);