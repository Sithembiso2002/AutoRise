<?php
/** @var string $title */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$productsUrl = e(url('products'));
$contactUrl  = e(url('contact'));
$registerUrl = e(url('register'));

$aboutHero = e(url('assets/img/about-car.jpg'));
$imgStory  = e(url('assets/img/car-hero.jpg'));

// ---------- Values ----------
$values = [
    ['fa-handshake-o', 'Integrity',      'We deal fairly and transparently with every customer. No hidden fees, no surprises.'],
    ['fa-star',        'Quality',        'Every vehicle is mechanically certified and inspected before sale.'],
    ['fa-rocket',      'Innovation',     'We use modern technology to make buying a car simple, fast, and secure.'],
    ['fa-heart',       'Customer Care',  'Real humans, available 24/7. Your satisfaction is the only metric that matters.'],
];
$valuesHtml = '';
foreach ($values as $v) {
    $valuesHtml .= <<<HTML
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="value-card p-4 h-100 text-center">
        <i class="fa {$v[0]} value-icon"></i>
        <h5 class="mt-3 mb-2">{$v[1]}</h5>
        <p class="text-secondary mb-0 small">{$v[2]}</p>
      </div>
    </div>
    HTML;
}

// ---------- Services ----------
$services = [
    ['fa-car',        'Vehicle Sales',      'New and pre-owned cars, SUVs, and sedans from trusted brands - all verified and warrantied.'],
    ['fa-exchange',   'Trade-In',           'Bring your current car and trade it in toward a new one - fair valuations, instant offers.'],
    ['fa-truck',      'Nationwide Delivery','Fast, insured delivery to every district in Lesotho, plus cross-border shipping.'],
    ['fa-credit-card','Secure Payments',    'Card, bank transfer, or mobile money. Every transaction is encrypted and protected.'],
    ['fa-undo',       'Easy Returns',       '7-day hassle-free returns on every vehicle in its original condition.'],
    ['fa-wrench',     'After-Sales Support','Warranty servicing, spare parts, and a dedicated support line for every customer.'],
];
$servicesHtml = '';
foreach ($services as $s) {
    $servicesHtml .= <<<HTML
    <div class="col-md-6 col-lg-4 mb-4">
      <div class="service-card p-4 h-100">
        <div class="d-flex align-items-start">
          <i class="fa {$s[0]} service-icon me-3"></i>
          <div>
            <h6 class="mb-2">{$s[1]}</h6>
            <p class="text-secondary small mb-0">{$s[2]}</p>
          </div>
        </div>
      </div>
    </div>
    HTML;
}

// ---------- Timeline ----------
$milestones = [
    ['2022', 'Founded',          'Auto Rise launched with a single goal: make quality cars accessible to every household in Lesotho.'],
    ['2023', 'Expanded Inventory','Grew our catalog with sedans, SUVs, sports cars, and pickups from trusted brands.'],
    ['2024', 'Cross-Border',     'Began shipping to South Africa, Botswana, Eswatini, and Namibia.'],
    ['2026', 'Going Digital',    'Launched our new online platform with secure payments, real-time inventory, and a full customer dashboard.'],
];
$timelineHtml = '';
foreach ($milestones as $i => $m) {
    $side = $i % 2 === 0 ? 'left' : 'right';
    $timelineHtml .= <<<HTML
    <div class="timeline-item {$side}">
      <div class="timeline-content">
        <span class="badge bg-danger mb-2">{$m[0]}</span>
        <h5>{$m[1]}</h5>
        <p class="text-secondary mb-0 small">{$m[2]}</p>
      </div>
    </div>
    HTML;
}

// ---------- Team ----------
$team = [
    ['Sethembiso S.', 'Founder',  'fa-user-circle-o', 'Visionary behind Auto Rise with a passion for connecting people with the right car.'],
    ['Thato M.',      'Manager',  'fa-user-circle-o', 'Oversees daily operations, fulfillment, and our customer support desk.'],
    ['Limpho M.',     'Operator', 'fa-user-circle-o', 'Handles order processing, deliveries, and keeps the wheels turning smoothly.'],
];
$teamHtml = '';
foreach ($team as $t) {
    $initial = e(substr($t[0], 0, 1));
    $teamHtml .= <<<HTML
    <div class="col-md-4 mb-4">
      <div class="team-card text-center p-4 h-100">
        <div class="team-avatar mx-auto mb-3">{$initial}</div>
        <h5 class="mb-1">{$t[0]}</h5>
        <p class="text-danger small mb-2">{$t[1]}</p>
        <p class="text-secondary small mb-0">{$t[3]}</p>
      </div>
    </div>
    HTML;
}

// ---------- Contact info ----------
$contactBlocks = [
    ['fa-map-marker', 'Visit Us',   'Kingsway Road, Maseru 100, Lesotho'],
    ['fa-phone',      'Call Us',    '+266 5800 0000'],
    ['fa-envelope',   'Email Us',   'hello@autorisÃÂµ.co.ls'],
    ['fa-clock-o',    'Hours',      'Mon-Sat: 8am - 6pm'],
];
$contactHtml = '';
foreach ($contactBlocks as $c) {
    $contactHtml .= <<<HTML
    <div class="col-md-6 col-lg-3 mb-3">
      <div class="contact-card p-4 h-100 text-center">
        <i class="fa {$c[0]} contact-icon"></i>
        <h6 class="mt-3 mb-1">{$c[1]}</h6>
        <p class="text-secondary small mb-0">{$c[2]}</p>
      </div>
    </div>
    HTML;
}

$body = $flashHtml . <<<HTML
<style>
.page-hero {
  position: relative;
  height: 46vh;
  min-height: 320px;
  background: linear-gradient(rgba(0,0,0,.65), rgba(0,0,0,.75)),
              url('{$aboutHero}') center/cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #fff;
}
.page-hero h1 {
  font-size: clamp(2rem, 4vw, 3.2rem);
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 2px;
}
.page-hero p {
  max-width: 640px;
  margin: 0 auto;
  color: #ccc;
}

/* Mission / Vision cards */
.mv-card {
  background: linear-gradient(160deg, #1c1c1c 0%, #111 100%);
  border: 1px solid #232323;
  border-radius: 18px;
  padding: 40px 32px;
  height: 100%;
  position: relative;
  overflow: hidden;
  transition: transform .3s, border-color .3s, box-shadow .3s;
}
.mv-card:hover {
  transform: translateY(-6px);
  border-color: #e60000;
  box-shadow: 0 20px 50px rgba(230,0,0,.15);
}
.mv-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 4px;
  background: linear-gradient(90deg, #e60000, #800000);
}
.mv-icon {
  width: 64px; height: 64px;
  border-radius: 16px;
  background: rgba(230,0,0,.12);
  color: #e60000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin-bottom: 24px;
}
.mv-card h3 {
  font-weight: 700;
  font-size: 1.4rem;
  margin-bottom: 16px;
  color: #fff;
}
.mv-card p {
  color: #b0b0b0;
  line-height: 1.75;
  margin-bottom: 0;
}
.mv-card p + p { margin-top: 14px; }

.value-card {
  background: linear-gradient(160deg, #1a1a1a 0%, #111 100%);
  border: 1px solid #232323;
  border-radius: 14px;
  transition: transform .25s, border-color .25s;
}
.value-card:hover { transform: translateY(-6px); border-color: #e60000; }
.value-icon { font-size: 36px; color: #e60000; }

.service-card {
  background: #161616;
  border: 1px solid #232323;
  border-radius: 14px;
  transition: transform .25s, border-color .25s;
}
.service-card:hover { transform: translateY(-4px); border-color: #e60000; }
.service-icon { font-size: 28px; color: #e60000; flex-shrink: 0; margin-top: 4px; }

.timeline {
  position: relative;
  max-width: 860px;
  margin: 0 auto;
  padding: 20px 0;
}
.timeline::before {
  content: '';
  position: absolute;
  top: 0; bottom: 0; left: 50%;
  width: 2px;
  background: #333;
  transform: translateX(-50%);
}
.timeline-item {
  position: relative;
  width: 50%;
  padding: 0 32px;
  margin-bottom: 32px;
}
.timeline-item.left  { left: 0; text-align: right; }
.timeline-item.right { left: 50%; text-align: left; }
.timeline-item::before {
  content: '';
  position: absolute;
  top: 12px;
  width: 14px; height: 14px;
  background: #e60000;
  border: 3px solid #111;
  border-radius: 50%;
}
.timeline-item.left::before  { right: -7px; }
.timeline-item.right::before { left: -7px; }
.timeline-content {
  background: #161616;
  border: 1px solid #232323;
  border-radius: 12px;
  padding: 20px;
}
@media (max-width: 768px) {
  .timeline::before { left: 16px; }
  .timeline-item { width: 100%; left: 0 !important; padding-left: 44px; padding-right: 0; text-align: left !important; }
  .timeline-item::before { left: 10px !important; right: auto !important; }
}

.team-card {
  background: #161616;
  border: 1px solid #232323;
  border-radius: 14px;
  transition: transform .25s, border-color .25s;
}
.team-card:hover { transform: translateY(-4px); border-color: #e60000; }
.team-avatar {
  width: 80px; height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #e60000 0%, #800000 100%);
  color: #fff;
  font-size: 32px;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}

.contact-card {
  background: #161616;
  border: 1px solid #232323;
  border-radius: 14px;
  transition: transform .25s, border-color .25s;
}
.contact-card:hover { transform: translateY(-4px); border-color: #e60000; }
.contact-icon { font-size: 28px; color: #e60000; }

.section-title {
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  position: relative;
  padding-bottom: 12px;
  margin-bottom: 8px;
}
.section-title::after {
  content: '';
  position: absolute;
  left: 50%; bottom: 0;
  width: 60px; height: 3px;
  background: #e60000;
  transform: translateX(-50%);
  border-radius: 2px;
}
.section-title.text-start::after { left: 0; transform: none; }
.section-sub { color: #999; margin-bottom: 40px; }
</style>

<!-- ============ PAGE HERO ============ -->
<section class="page-hero">
  <div class="container">
    <h1>About Auto Rise</h1>
    <p>Lesotho's modern marketplace for premium new and used cars.</p>
  </div>
</section>

<!-- ============ MISSION & VISION ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Mission &amp; Vision</h2>
      <p class="section-sub">What drives us every day</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa fa-bullseye"></i></div>
          <h3>Our Mission</h3>
          <p>
            To make quality new and used cars accessible to every household in
            Lesotho and the wider Southern African region - at fair prices, with
            modern service, and without the friction of traditional dealerships.
          </p>
          <p>
            We are committed to transparency, honesty, and putting the customer
            first at every step of the buying journey - from browsing to delivery
            to after-sales support.
          </p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa fa-eye"></i></div>
          <h3>Our Vision</h3>
          <p>
            To become the most trusted and preferred online vehicle marketplace
            in Southern Africa - a place where anyone, anywhere, can find and buy
            their perfect car with complete confidence.
          </p>
          <p>
            We envision a future where buying a car is as simple as ordering
            anything else online: transparent, secure, fast, and backed by real
            human support.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ OUR STORY ============ -->
<section class="py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-md-6">
        <h2 class="section-title text-start" style="display:inline-block">Our Story</h2>
        <p class="text-secondary mt-3">
          Auto Rise was founded in 2022 with a simple belief: buying a car should be
          exciting, not exhausting. Traditional dealerships were slow, opaque, and
          full of hidden fees. We wanted to build something better.
        </p>
        <p class="text-secondary">
          What started as a small local operation has grown into a regional
          marketplace serving thousands of customers. Every car we sell is
          inspected, certified, and backed by a warranty. Every customer gets a
          real human to talk to. Every transaction is secured with modern
          encryption. That is the Auto Rise way.
        </p>
        <a href="{$productsUrl}" class="btn btn-red mt-2">Explore Our Inventory</a>
      </div>
      <div class="col-md-6">
        <img src="{$imgStory}" class="img-fluid rounded shadow" alt="Auto Rise story">
      </div>
    </div>
  </div>
</section>

<!-- ============ VALUES ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Our Values</h2>
      <p class="section-sub">The principles behind everything we do</p>
    </div>
    <div class="row">{$valuesHtml}</div>
  </div>
</section>

<!-- ============ TIMELINE ============ -->
<section class="py-5">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Our Journey</h2>
      <p class="section-sub">From a small local shop to a regional marketplace</p>
    </div>
    <div class="timeline">{$timelineHtml}</div>
  </div>
</section>

<!-- ============ SERVICES ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">What We Offer</h2>
      <p class="section-sub">A complete buying experience, end to end</p>
    </div>
    <div class="row">{$servicesHtml}</div>
  </div>
</section>

<!-- ============ TEAM ============ -->
<section class="py-5">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Meet the Team</h2>
      <p class="section-sub">The people behind Auto Rise</p>
    </div>
    <div class="row justify-content-center">{$teamHtml}</div>
  </div>
</section>

<!-- ============ CONTACT INFO ============ -->
<section class="py-5" style="background:#0d0d0d">
  <div class="container">
    <div class="text-center">
      <h2 class="section-title">Get in Touch</h2>
      <p class="section-sub">We are always happy to hear from you</p>
    </div>
    <div class="row">{$contactHtml}</div>
    <div class="text-center mt-3">
      <a href="{$contactUrl}" class="btn btn-outline-light px-4">Contact Us <i class="fa fa-arrow-right ms-1"></i></a>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="py-5">
  <div class="container">
    <div class="text-center">
      <h3 class="mb-3">Ready to get started?</h3>
      <p class="text-secondary mb-4">Create a free account and start shopping in minutes.</p>
      <a href="{$registerUrl}" class="btn btn-red btn-lg px-5">Create Account</a>
    </div>
  </div>
</section>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);