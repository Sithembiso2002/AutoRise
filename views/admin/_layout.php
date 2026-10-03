<?php
/** @var string $content */
/** @var string $active */
/** @var string $title */

use App\Services\Cart;

$user     = auth() ?? [];
$name     = e($user['name'] ?? 'Admin');
$email    = e($user['email'] ?? '');
$role     = (string) ($user['role'] ?? 'staff');
$initials = e(initials_of($user['name'] ?? 'A'));
$avatar   = user_avatar($user);
$csrf     = e(csrf_token());

$avatarHtml = $avatar
    ? '<img src="' . e($avatar) . '" alt="Avatar" class="avatar-img">'
    : '<span class="avatar">' . $initials . '</span>';

$menu = [
    'dashboard' => ['fa-dashboard',    'Dashboard', url('admin')],
    'products'  => ['fa-car',          'Products',  url('admin/products')],
    'orders'    => ['fa-shopping-bag', 'Orders',    url('admin/orders')],
    'users'     => ['fa-users',        'Users',     url('admin/users')],
    'reports'   => ['fa-line-chart',   'Reports',   url('admin/reports')],
];

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Admin | ' . brand_name()) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin-body">

<!-- ============ SIDEBAR ============ -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-header">
    <?php if (brand_logo()): ?>
      <img src="<?= e(brand_logo()) ?>" alt="<?= e(brand_name()) ?>">
    <?php endif; ?>
    <div>
      <div class="brand-text"><?= e(brand_name()) ?></div>
      <div class="brand-sub">Admin Panel</div>
    </div>
  </div>

  <div class="admin-sidebar-body">
    <div class="admin-nav-section">Management</div>
    <ul class="admin-nav">
      <?php foreach ($menu as $key => $item): ?>
        <li class="admin-nav-item">
          <a href="<?= e($item[2]) ?>" class="admin-nav-link <?= $key === $active ? 'active' : '' ?>">
            <i class="fa <?= $item[0] ?>"></i>
            <span><?= $item[1] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="admin-nav-section">Site</div>
    <ul class="admin-nav">
      <li class="admin-nav-item">
        <a href="<?= e(url('/')) ?>" class="admin-nav-link" target="_blank" rel="noopener">
          <i class="fa fa-external-link"></i>
          <span>View Storefront</span>
        </a>
      </li>
      <li class="admin-nav-item">
        <a href="<?= e(url('profile')) ?>" class="admin-nav-link">
          <i class="fa fa-user-circle-o"></i>
          <span>My Profile</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="admin-sidebar-footer">
    <?= $avatarHtml ?>
    <div class="user-meta">
      <div class="user-name"><?= $name ?></div>
      <div class="user-role"><?= e(ucfirst($role)) ?></div>
    </div>
    <form method="POST" action="<?= e(url('logout')) ?>" class="m-0">
      <input type="hidden" name="_token" value="<?= $csrf ?>">
      <button type="submit" class="btn btn-sm btn-outline-danger" title="Logout">
        <i class="fa fa-sign-out"></i>
      </button>
    </form>
  </div>
</aside>

<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============ MAIN ============ -->
<main class="admin-main">
  <header class="admin-topbar">
    <button class="admin-burger" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="fa fa-bars"></i>
    </button>
    <h1><?= e($title ?? 'Admin') ?></h1>
    <div class="topbar-actions">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light d-none d-md-inline-flex">
        <i class="fa fa-external-link me-1"></i> Storefront
      </a>
    </div>
  </header>

  <div class="admin-content">
    <div class="admin-flash"><?= $flashHtml ?></div>
    <?= $content ?>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var sidebar = document.getElementById('adminSidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var toggle  = document.getElementById('sidebarToggle');

  function open()  { sidebar.classList.add('open'); overlay.classList.add('show'); }
  function close() { sidebar.classList.remove('open'); overlay.classList.remove('show'); }

  if (toggle)  toggle.addEventListener('click', function () {
    sidebar.classList.contains('open') ? close() : open();
  });
  if (overlay) overlay.addEventListener('click', close);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
  });
})();
</script>
</body>
</html>