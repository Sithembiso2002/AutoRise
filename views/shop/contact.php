<?php
/** @var string $title */

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$actionUrl = e(url('contact'));
$token     = e(csrf_token());

$body = $flashHtml . <<<HTML
<style>
  .contact-hero {
    background: linear-gradient(160deg, #1a1a1a 0%, #111 100%);
    border-bottom: 1px solid #232323;
    padding: 80px 20px 60px;
    text-align: center;
  }
  .contact-hero h1 {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
  }
  .contact-card {
    background: #161616;
    border: 1px solid #232323;
    border-radius: 14px;
    padding: 24px;
    transition: transform .25s, border-color .25s;
  }
  .contact-card:hover { transform: translateY(-4px); border-color: #e60000; }
  .contact-icon { font-size: 32px; color: #e60000; }
  .form-control:focus, .form-select:focus {
    background:#111; color:#eee; border-color:#e60000;
    box-shadow:0 0 0 .2rem rgba(230,0,0,.25);
  }
</style>

<section class="contact-hero">
  <div class="container">
    <h1>Contact Us</h1>
    <p class="text-secondary mb-0">Questions? Feedback? We'd love to hear from you.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-5">
        <div class="row g-3">
          <div class="col-6">
            <div class="contact-card h-100 text-center">
              <i class="fa fa-map-marker contact-icon"></i>
              <h6 class="mt-3 mb-1">Visit Us</h6>
              <p class="text-secondary small mb-0">Kingsway Road<br>Maseru 100, Lesotho</p>
            </div>
          </div>
          <div class="col-6">
            <div class="contact-card h-100 text-center">
              <i class="fa fa-phone contact-icon"></i>
              <h6 class="mt-3 mb-1">Call Us</h6>
              <p class="text-secondary small mb-0">+266 5800 0000<br>Mon-Sat, 8am-6pm</p>
            </div>
          </div>
          <div class="col-6">
            <div class="contact-card h-100 text-center">
              <i class="fa fa-envelope contact-icon"></i>
              <h6 class="mt-3 mb-1">Email Us</h6>
              <p class="text-secondary small mb-0">hello@autorise.co.ls<br>support@autorise.co.ls</p>
            </div>
          </div>
          <div class="col-6">
            <div class="contact-card h-100 text-center">
              <i class="fa fa-whatsapp contact-icon"></i>
              <h6 class="mt-3 mb-1">WhatsApp</h6>
              <p class="text-secondary small mb-0">+266 5800 0000<br>24/7 support</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-7">
        <div class="card p-4">
          <h3 class="mb-4">Send us a message</h3>
          <form method="POST" action="{$actionUrl}">
            <input type="hidden" name="_token" value="{$token}">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Your name</label>
                <input type="text" name="name" class="form-control bg-dark text-light border-secondary" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required>
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control bg-dark text-light border-secondary" required>
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" rows="6" class="form-control bg-dark text-light border-secondary" required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-red px-4">Send Message</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);