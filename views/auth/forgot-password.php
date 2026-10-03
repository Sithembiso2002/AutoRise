<?php
/** @var string $title */
$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$action = e(url('forgot-password'));
$loginUrl = e(url('login'));
$token = e(csrf_token());
$email = e(old('email', ''));

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:480px">
  <div class="card p-4">
    <h2 class="mb-2 text-center">Forgot your password?</h2>
    <p class="text-secondary text-center small mb-4">
      Enter the email you used to sign up. We'll send a reset link.
    </p>

    <form method="POST" action="{$action}" novalidate>
      <input type="hidden" name="_token" value="{$token}">
      <div class="mb-4">
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{$email}" required autofocus
               class="form-control bg-dark text-light border-secondary">
      </div>
      <button type="submit" class="btn btn-red w-100">Send Reset Link</button>
    </form>

    <hr class="border-secondary">
    <p class="text-center text-secondary small mb-0">
      Remembered it? <a href="{$loginUrl}" class="text-danger">Back to login</a>
    </p>
  </div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);