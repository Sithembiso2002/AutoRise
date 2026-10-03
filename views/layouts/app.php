<?php
/** @var string $content */

use App\Services\Cart;

$currentUser = auth();
$cartCount   = (new Cart())->count();

$logoutUrl = e(url('logout'));
$loginUrl  = e(url('login'));
$regUrl    = e(url('register'));
$csrf      = e(csrf_token());

if ($currentUser) {
    $name      = e($currentUser['name'] ?? $currentUser['email']);
    $email     = e($currentUser['email']);
    $roleLabel = ucfirst((string) ($currentUser['role'] ?? 'customer'));
    $initials  = e(initials_of($currentUser['name'] ?? 'U'));
    $avatarUrl = user_avatar($currentUser);

    $profileUrl  = e(url('profile'));
    $myOrdersUrl = e(url('orders'));
    $settingsUrl = e(url('profile/settings'));

    $navAvatarHtml = $avatarUrl
        ? '<img src="' . e($avatarUrl) . '" alt="Avatar" class="nav-avatar-img">'
        : '<span class="nav-avatar">' . $initials . '</span>';

    $ddAvatarHtml = $avatarUrl
        ? '<img src="' . e($avatarUrl) . '" alt="Avatar" class="avatar-lg-img">'
        : '<div class="avatar-lg">' . $initials . '</div>';

    $mobileNavRight = <<<HTML
      <li class="nav-item"><a class="nav-link" href="{$myOrdersUrl}">My Orders</a></li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2" href="{$profileUrl}">
          {$navAvatarHtml}
          <span>My Profile</span>
        </a>
      </li>
      <li class="nav-item"><a class="nav-link" href="{$settingsUrl}">Settings</a></li>
      <li class="nav-item">
        <form method="POST" action="{$logoutUrl}" class="m-0">
          <input type="hidden" name="_token" value="{$csrf}">
          <button type="submit" class="nav-link text-start w-100 border-0 bg-transparent" style="color:#ff6b6b !important;">
            <i class="fa fa-sign-out me-1"></i> Logout
          </button>
        </form>
      </li>
    HTML;

    $desktopNavRight = <<<HTML
      <li class="nav-item"><a class="nav-link" href="{$myOrdersUrl}">My Orders</a></li>
      <li class="nav-item dropdown profile-dropdown">
        <a class="nav-link dropdown-toggle profile-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          {$navAvatarHtml}
          <span class="nav-username d-none d-lg-inline">{$name}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end profile-menu">
          <li class="profile-menu-header">
            {$ddAvatarHtml}
            <div class="profile-meta">
              <strong>{$name}</strong>
              <small>{$email}</small>
              <span class="role-badge">{$roleLabel}</span>
            </div>
          </li>
          <li class="profile-menu-body">
            <a class="profile-menu-item" href="{$profileUrl}">
              <i class="fa fa-user-circle-o"></i><span>My Profile</span>
            </a>
            <a class="profile-menu-item" href="{$myOrdersUrl}">
              <i class="fa fa-shopping-bag"></i><span>My Orders</span>
            </a>
            <a class="profile-menu-item" href="{$settingsUrl}">
              <i class="fa fa-cog"></i><span>Settings</span>
            </a>
            <div class="profile-menu-divider"></div>
            <form method="POST" action="{$logoutUrl}" class="m-0">
              <input type="hidden" name="_token" value="{$csrf}">
              <button type="submit" class="profile-menu-item danger w-100 text-start border-0 bg-transparent">
                <i class="fa fa-sign-out"></i><span>Logout</span>
              </button>
            </form>
          </li>
        </ul>
      </li>
    HTML;
} else {
    $mobileNavRight = <<<HTML
      <li class="nav-item"><a class="nav-link" href="{$regUrl}">Sign Up</a></li>
      <li class="nav-item"><a class="nav-link nav-cta nav-cta-primary" href="{$loginUrl}">Login</a></li>
    HTML;

    $desktopNavRight = <<<HTML
      <li class="nav-item"><a class="nav-link nav-cta" href="{$regUrl}">Sign Up</a></li>
      <li class="nav-item"><a class="nav-link nav-cta nav-cta-primary" href="{$loginUrl}">Login</a></li>
    HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? brand_name()) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>

<!-- ============ NAVBAR (top bar only) ============ -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="siteNav">
  <div class="container">
    <a class="navbar-brand" href="<?= e(url('/')) ?>">
      <?php if (brand_logo()): ?>
        <img src="<?= e(brand_logo()) ?>" alt="<?= e(brand_name()) ?>">
      <?php endif; ?>
      <span><?= e(brand_name()) ?></span>
    </a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mobileNav"
            aria-controls="mobileNav" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Desktop-only inline nav (hidden on mobile via CSS) -->
    <div class="d-none d-lg-flex ms-auto">
      <ul class="navbar-nav align-items-lg-center flex-row">
        <li class="nav-item"><a class="nav-link" href="<?= e(url('/')) ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('products')) ?>">Browse</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('about')) ?>">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('contact')) ?>">Contact</a></li>
        <li class="nav-item">
          <a class="nav-link cart-link" href="<?= e(url('cart')) ?>">
            <i class="fa fa-shopping-cart"></i>
            <span>Cart</span>
            <?php if ($cartCount > 0): ?>
              <span class="cart-badge"><?= (int) $cartCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?= $desktopNavRight ?>
      </ul>
    </div>
  </div>
</nav>

<!-- ============ MOBILE OFF-CANVAS (direct child of body) ============ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileNavLabel">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" href="<?= e(url('/')) ?>">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(url('products')) ?>">Browse</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(url('about')) ?>">About</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e(url('contact')) ?>">Contact</a></li>
      <li class="nav-item">
        <a class="nav-link cart-link" href="<?= e(url('cart')) ?>">
          <i class="fa fa-shopping-cart"></i>
          <span>Cart</span>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= (int) $cartCount ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?= $mobileNavRight ?>
    </ul>
  </div>
</div>

<?= $content ?>

<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6 brand-col">
        <a href="<?= e(url('/')) ?>" class="footer-brand">
          <?php if (brand_logo()): ?>
            <img src="<?= e(brand_logo()) ?>" alt="<?= e(brand_name()) ?>" class="footer-brand-logo">
          <?php else: ?>
            <?= e(brand_name()) ?>
          <?php endif; ?>
        </a>
        <p>Lesotho's modern marketplace for premium new and used cars. Quality vehicles, secure payments, and nationwide delivery - all in one place.</p>
        <div class="footer-social">
          <a href="#" aria-label="Facebook"><i class="fa fa-facebook"></i></a>
          <a href="#" aria-label="Twitter"><i class="fa fa-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa fa-instagram"></i></a>
          <a href="#" aria-label="WhatsApp"><i class="fa fa-whatsapp"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa fa-linkedin"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h6>Quick Links</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('/')) ?>"><i class="fa fa-angle-right"></i>Home</a></li>
          <li><a href="<?= e(url('products')) ?>"><i class="fa fa-angle-right"></i>Shop All</a></li>
          <li><a href="<?= e(url('about')) ?>"><i class="fa fa-angle-right"></i>About Us</a></li>
          <li><a href="<?= e(url('contact')) ?>"><i class="fa fa-angle-right"></i>Contact</a></li>
          <li><a href="<?= e(url('register')) ?>"><i class="fa fa-angle-right"></i>Sign Up</a></li>
        </ul>
      </div>

      <div class="col-lg-2 col-md-6 col-6">
        <h6>Customer Service</h6>
        <ul class="footer-links">
          <li><a href="<?= e(url('orders')) ?>"><i class="fa fa-angle-right"></i>My Orders</a></li>
          <li><a href="#"><i class="fa fa-angle-right"></i>Shipping Info</a></li>
          <li><a href="#"><i class="fa fa-angle-right"></i>Returns</a></li>
          <li><a href="#"><i class="fa fa-angle-right"></i>Warranty</a></li>
          <li><a href="#"><i class="fa fa-angle-right"></i>FAQ</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-6">
        <h6>Get in Touch</h6>
        <div class="footer-contact-item"><i class="fa fa-map-marker"></i><span>Kingsway Road, Maseru 100, Lesotho</span></div>
        <div class="footer-contact-item"><i class="fa fa-phone"></i><span>+266 5800 0000</span></div>
        <div class="footer-contact-item"><i class="fa fa-envelope"></i><span>hello@autorise.co.ls</span></div>
        <div class="footer-newsletter mt-3">
          <small class="text-light d-block mb-2"><strong>Subscribe to our newsletter</strong></small>
          <form onsubmit="this.reset(); this.querySelector('button').innerHTML='Subscribed!'; return false;">
            <div class="input-group input-group-sm">
              <input type="email" required placeholder="you@example.com" class="form-control">
              <button class="btn btn-red" type="submit">Join</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
          &copy; <?= date('Y') ?> Auto Rise (Pty) Ltd. All rights reserved.
          <a href="#">Privacy</a> &middot; <a href="#">Terms</a>
        </div>
        <div class="col-md-6 text-center text-md-end payment-icons">
          <i class="fa fa-cc-visa" title="Visa"></i>
          <i class="fa fa-cc-mastercard" title="Mastercard"></i>
          <i class="fa fa-cc-paypal" title="PayPal"></i>
          <i class="fa fa-cc-stripe" title="Stripe"></i>
        </div>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var nav = document.getElementById('siteNav');
  if (nav) {
    function onScroll() {
      if (window.scrollY > 20) nav.classList.add('scrolled');
      else nav.classList.remove('scrolled');
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var offEl = document.getElementById('mobileNav');
  if (offEl) {
    offEl.addEventListener('click', function (e) {
      var t = e.target.closest('a.nav-link');
      if (!t) return;
      if (t.classList.contains('dropdown-toggle')) return;
      var oc = bootstrap.Offcanvas.getInstance(offEl);
      if (oc) oc.hide();
    });
  }
})();
</script>
</body>
</html>