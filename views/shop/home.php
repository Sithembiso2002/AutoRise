<?php
/** @var array $products */
/** @var array $categories */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

/* HERO SLIDES — inline background-image per slide */
$heroSlides = ['car-hero.jpg', 'sedan.jpg', 'suv.jpg', 'sports-car.jpg'];

$heroSlideHtml = '';
$heroDotHtml   = '';
foreach ($heroSlides as $i => $img) {
    $bg     = e(url('assets/img/' . $img));
    $active = $i === 0 ? ' active' : '';
    $heroSlideHtml .= '<div class="hero-slide' . $active . '" style="background-image: url(\'' . $bg . '\');"></div>';
    $heroDotHtml   .= '<button type="button" class="hero-dot' . $active . '" data-slide="' . $i . '" aria-label="Slide ' . ($i + 1) . '"></button>';
}

$aboutImg = e(url('assets/img/about-car.jpg'));

/* FEATURED PRODUCTS */
$featuredHtml = '';
foreach ($products as $p) {
    $id      = (int) $p['product_id'];
    $name    = e($p['name']);
    $desc    = e(mb_strimwidth((string) ($p['description'] ?? ''), 0, 80, '...'));
    $price   = number_format((float) $p['price'], 2);
    $stock   = (int) $p['stock'];
    $urlShow = e(url("products/{$id}"));
    $img     = product_image($p['image_path'], (string) $p['name']);

    $badge = $stock > 0
        ? '<span class="badge bg-success">' . $stock . ' in stock</span>'
        : '<span class="badge bg-secondary">Sold out</span>';

    $featuredHtml .= <<<HTML
    <div class="col-6 col-md-6 col-lg-3 mb-4">
      <div class="card h-100 product-card">
        <a href="{$urlShow}" class="position-relative d-block overflow-hidden">
          <img src="{$img}" class="card-img-top" alt="{$name}" loading="lazy">
          <span class="badge bg-danger position-absolute top-0 end-0 m-2">New</span>
        </a>
        <div class="card-body d-flex flex-column">
          <h6 class="card-title mb-1">{$name}</h6>
          <p class="card-text small text-secondary flex-grow-1 mb-2 d-none d-md-block">{$desc}</p>
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
            <span class="price-tag">M {$price}</span>
            {$badge}
          </div>
          <a href="{$urlShow}" class="btn btn-red btn-sm w-100">View Details</a>
        </div>
      </div>
    </div>
    HTML;
}
if ($featuredHtml === '') {
    $featuredHtml = '<div class="col-12"><div class="alert alert-secondary">No products yet.</div></div>';
}

/* CATEGORY CARDS */
$catIcons = [
    'Sedans'      => 'fa-car',
    'SUVs'        => 'fa-truck',
    'Sports Cars' => 'fa-rocket',
    'Pickups'     => 'fa-truck',
    'Hatchbacks'  => 'fa-car',
    'Electric'    => 'fa-bolt',
];
$catHtml = '';
foreach ($categories as $c) {
    $name = e($c['category_name']);
    $desc = e(mb_strimwidth((string) ($c['category_description'] ?? ''), 0, 60, '...'));
    $icon = $catIcons[$c['category_name']] ?? 'fa-tag';
    $url  = e(url('products?category=' . (int) $c['category_id']));
    $catHtml .= <<<HTML
    <div class="col-6 col-md-4 col-lg-2 mb-3">
      <a href="{$url}" class="text-decoration-none">
        <div class="card category-card h-100 text-center">
          <div class="card-body p-3">
            <i class="fa {$icon} category-icon"></i>
            <h6 class="mt-3 mb-1 small">{$name}</h6>
            <p class="small text-secondary mb-0 d-none d-md-block">{$desc}</p>
          </div>
        </div>
      </a>
    </div>
    HTML;
}

/* TRUST BADGES */
$trustBadges = [
    ['fa-truck',      'Free Delivery',   'On orders over M 200,000'],
    ['fa-shield',     'Secure Payment',  '256-bit encrypted'],
    ['fa-refresh',    '6-Day Returns',   'No questions asked'],
    ['fa-headphones', '24/6 Support',    'We are always here'],
];
$trustHtml = '';
foreach ($trustBadges as $b) {
    $trustHtml .= <<<HTML
    <div class="col-6 col-md-3 mb-3">
      <div class="trust-badge text-center">
        <i class="fa {$b[0]}"></i>
        <h6 class="mt-2 mb-1 small">{$b[1]}</h6>
        <p class="small text-secondary mb-0">{$b[2]}</p>
      </div>
    </div>
    HTML;
}

/* WHY CHOOSE US */
$whyFeatures = [
    ['fa-certificate', 'Certified Quality', 'Every vehicle inspected and guaranteed before delivery.'],
    ['fa-tags',        'Best Prices',       'Direct-from-dealer pricing with no hidden fees.'],
    ['fa-shield',      'Secure & Insured',  'Every order is encrypted and insured while in transit.'],
];
$whyHtml = '';
foreach ($whyFeatures as $f) {
    $whyHtml .= <<<HTML
    <div class="col-md-4 mb-4">
      <div class="why-card p-4">
        <i class="fa {$f[0]} why-icon"></i>
        <h5 class="mt-3">{$f[1]}</h5>
        <p class="text-secondary mb-0">{$f[2]}</p>
      </div>
    </div>
    HTML;
}

/* FAQ */
$faqs = [
    ['q' => 'How long does delivery take?',        'a' => 'Standard delivery takes 3-5 business days within Lesotho, 7-14 days for international orders.'],
    ['q' => 'What payment methods do you accept?', 'a' => 'We accept credit/debit cards, bank transfers, and mobile money. All transactions are encrypted and secure.'],
    ['q' => 'Can I return a vehicle?',             'a' => 'Yes - you have 7 days from delivery to return any vehicle in its original condition for a full refund.'],
    ['q' => 'Do you offer warranties?',            'a' => 'All our vehicles come with a 6-month mechanical warranty, extendable to 24 months.'],
    ['q' => 'Do you deliver outside Lesotho?',     'a' => 'Yes, we ship to South Africa, Botswana, Eswatini, and Namibia. Shipping fees apply based on destination.'],
];
$faqHtml = '';
foreach ($faqs as $i => $f) {
    $q = e($f['q']);
    $a = e($f['a']);
    $show      = $i === 0 ? 'show' : '';
    $collapsed = $i === 0 ? '' : 'collapsed';
    $expanded  = $i === 0 ? 'true' : 'false';
    $faqHtml .= <<<HTML
    <div class="accordion-item bg-dark border-secondary">
      <h2 class="accordion-header">
        <button class="accordion-button {$collapsed} bg-dark text-light" type="button"
                data-bs-toggle="collapse" data-bs-target="#faq{$i}" aria-expanded="{$expanded}">
          {$q}
        </button>
      </h2>
      <div id="faq{$i}" class="accordion-collapse collapse {$show}" data-bs-parent="#faqAccordion">
        <div class="accordion-body">{$a}</div>
      </div>
    </div>
    HTML;
}

/* RENDER */
$body = $flashHtml . <<<HTML
<!-- ============ HERO SLIDER ============ -->
<section class="hero-slider" id="heroSlider">
  {$heroSlideHtml}
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <span class="badge bg-danger mb-2 px-3 py-2">Premium new &amp; used cars</span>
    <h1>Your Dream Car Awaits</h1>
    <p class="lead">Premium new and used cars, delivered anywhere in Lesotho and beyond.</p>
    <div class="hero-cta mt-2">
      <a href="products" class="btn btn-red">Checkout Your Ride</a>
    </div>
  </div>
  <div class="hero-indicator" id="heroDots">
    {$heroDotHtml}
  </div>
</section>

<!-- ============ TRUST BADGES ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="row">{$trustHtml}</div>
  </div>
</section>

<!-- ============ BROWSE BY CATEGORY ============ -->
<section class="py-5">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Shop by Category</h2>
      <p class="section-sub">Find exactly what you need, faster</p>
    </div>
    <div class="row g-3 justify-content-center">{$catHtml}</div>
  </div>
</section>

<!-- ============ ABOUT US ============ -->
<section class="py-5" id="about" style="background:#0d0d0d">
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-md-6">
        <img src="{$aboutImg}" class="img-fluid rounded shadow" alt="About Auto Rise">
      </div>
      <div class="col-md-6">
        <h2 class="mb-3">About Auto Rise</h2>
        <p class="text-secondary">
          Auto Rise is Lesotho's fastest-growing online marketplace for premium new and used
          cars. From executive sedans to sports coupes, we connect you directly with
          trusted dealers - cutting out the middleman, cutting down the price.
        </p>
        <p class="text-secondary mb-0">
          Every vehicle is quality-checked, every order is insured in transit, and every
          customer gets a dedicated support agent. That is our promise.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="py-5">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Latest Arrivals</h2>
      <p class="section-sub">Fresh picks from our catalog</p>
    </div>
    <div class="row g-3 g-md-4">{$featuredHtml}</div>
    <div class="text-center mt-3">
      <a href="products" class="btn btn-outline-light px-4">View All Products <i class="fa fa-arrow-right ms-1"></i></a>
    </div>
  </div>
</section>

<!-- ============ WHY CHOOSE US ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Why Auto Rise</h2>
      <p class="section-sub">What sets us apart</p>
    </div>
    <div class="row">{$whyHtml}</div>
  </div>
</section>

<!-- ============ CTA BANNER ============ -->
<section class="py-5">
  <div class="container">
    <div class="cta-banner text-center text-white position-relative">
      <h2 class="mb-3 position-relative" style="z-index:1">Ready to find your perfect ride?</h2>
      <p class="lead mb-4 position-relative" style="z-index:1">Browse our full catalog and place your order in minutes.</p>
      <a href="products" class="btn btn-light btn-lg px-5 position-relative" style="z-index:1">Start Shopping</a>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container" style="max-width:820px">
    <div class="text-center">
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-sub">Everything you need to know</p>
    </div>
    <div class="accordion" id="faqAccordion">
      {$faqHtml}
    </div>
  </div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="py-5">
  <div class="container" style="max-width:720px">
    <div class="newsletter-box text-center">
      <i class="fa fa-envelope-o" style="font-size:40px;color:#e60000"></i>
      <h3 class="mt-3 mb-2">Stay in the loop</h3>
      <p class="text-secondary mb-4">Get exclusive deals, new arrivals, and insider offers - straight to your inbox.</p>
      <form class="row g-2 justify-content-center" onsubmit="this.reset(); this.querySelector('button').innerHTML='Subscribed!'; return false;">
        <div class="col-md-7 col-12">
          <input type="email" required placeholder="you@example.com" class="form-control">
        </div>
        <div class="col-md-3 col-12">
          <button type="submit" class="btn btn-red w-100">Subscribe</button>
        </div>
      </form>
      <small class="text-secondary d-block mt-2">No spam. Unsubscribe anytime.</small>
    </div>
  </div>
</section>

<script>
/* Hero slider — simple, reliable */
(function () {
  var slider = document.getElementById('heroSlider');
  if (!slider) return;
  var slides = slider.querySelectorAll('.hero-slide');
  var dots   = slider.querySelectorAll('.hero-dot');
  if (slides.length < 2) return;

  var idx = 0;
  var timer = null;
  var DELAY = 6000;

  function show(n) {
    slides[idx].classList.remove('active');
    if (dots[idx]) dots[idx].classList.remove('active');
    idx = (n + slides.length) % slides.length;
    slides[idx].classList.add('active');
    if (dots[idx]) dots[idx].classList.add('active');
  }

  function start() {
    stop();
    timer = setInterval(function () { show(idx + 1); }, DELAY);
  }
  function stop() { if (timer) { clearInterval(timer); timer = null; } }

  for (var i = 0; i < dots.length; i++) {
    (function (n) {
      dots[n].addEventListener('click', function () { show(n); start(); });
    })(i);
  }

  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', start);

  // Swipe support
  var x0 = null;
  slider.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 50) { show(idx + (dx < 0 ? 1 : -1)); start(); }
    x0 = null;
  }, { passive: true });

  start();
})();
</script>
HTML;

echo view('layouts.app', ['title' => 'Auto Rise - Premium Cars', 'content' => $body]);