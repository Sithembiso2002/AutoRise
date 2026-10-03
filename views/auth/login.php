<?php
/** @var string $title */

$flashes = flashes();

ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

/* Pre-compute every value used inside the heredoc */
$action      = e(url('login'));
$registerUrl = e(url('register'));
$forgotUrl   = e(url('forgot-password'));
$token       = e(csrf_token());
$email       = e(old('email', ''));

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:480px">
  <div class="card p-4">
    <h2 class="mb-4 text-center">Welcome Back</h2>

    <form method="POST" action="{$action}" novalidate>
      <input type="hidden" name="_token" value="{$token}">

      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{$email}" required autofocus
               class="form-control bg-dark text-light border-secondary">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" required
               class="form-control bg-dark text-light border-secondary">
      </div>

      <div class="d-flex justify-content-end mb-3">
        <a href="{$forgotUrl}" class="text-secondary small">Forgot your password?</a>
      </div>

      <button type="submit" class="btn btn-red w-100">Sign In</button>
    </form>

    <hr class="border-secondary">

    <p class="text-center text-secondary mb-0">
      Don't have an account? <a href="{$registerUrl}" class="text-danger">Sign Up</a>
    </p>
  </div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);