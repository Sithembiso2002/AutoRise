<?php
/** @var string $title */

$flashes = flashes();

ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

// Pre-compute every value the form needs (heredocs do NOT run PHP tags)
$action        = e(url('register'));
$loginUrl      = e(url('login'));
$token         = e(csrf_token());
$firstName     = e(old('first_name'));
$lastName      = e(old('last_name'));
$username      = e(old('username'));
$email         = e(old('email'));

$body = '<div class="container my-5" style="max-width:560px">' . $flashHtml . <<<HTML
<div class="card p-4">
  <h2 class="mb-4 text-center">Create Your Account</h2>
  <form method="POST" action="{$action}" novalidate>
    <input type="hidden" name="_token" value="{$token}">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">First name</label>
        <input type="text" name="first_name" class="form-control bg-dark text-light border-secondary"
               value="{$firstName}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Last name</label>
        <input type="text" name="last_name" class="form-control bg-dark text-light border-secondary"
               value="{$lastName}" required>
      </div>
    </div>

    <div class="mb-3 mt-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control bg-dark text-light border-secondary"
             value="{$username}" required>
      <div class="form-text text-secondary">Letters, numbers, dashes and underscores only.</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control bg-dark text-light border-secondary"
             value="{$email}" required>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control bg-dark text-light border-secondary" required>
        <div class="form-text text-secondary">At least 8 characters.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirmation" class="form-control bg-dark text-light border-secondary" required>
      </div>
    </div>

    <button type="submit" class="btn btn-red w-100 mt-4">Create Account</button>
  </form>
  <hr class="border-secondary">
  <p class="text-center text-secondary mb-0">
    Already have an account? <a href="{$loginUrl}" class="text-danger">Log in</a>
  </p>
</div>
</div>
HTML;

echo view('layouts.app', ['title' => $title, 'content' => $body]);