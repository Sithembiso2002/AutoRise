<?php
/** @var string $title, $token, $email */
$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$action = e(url('reset-password/' . $token));
$csrf   = e(csrf_token());

$body = $flashHtml . <<<HTML
<div class="container my-5" style="max-width:480px">
  <div class="card p-4">
    <h2 class="mb-2 text-center">Set a new password</h2>
    <p class="text-secondary text-center small mb-4">
      For <strong>{$email}</strong>
    </p>

    <form method="POST" action="{$action}" novalidate>
      <input type="hidden" name="_token" value="{$csrf}">
      <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" name="password" required autofocus
               class="form-control bg-dark text-light border-secondary">
        <div class="form-text text-secondary">At least 8 characters.</div>
      </div>
      <div class="mb-4">
        <label class="form-label">Confirm new password</label>
        <input type="password" name="password_confirmation" required
               class="form-control bg-dark text-light border-secondary">
      </div>
      <button type="submit" class="btn btn-red w-100">Reset Password</button>
    </form>
  </div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);