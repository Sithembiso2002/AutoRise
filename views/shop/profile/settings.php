<?php
/** @var array $user */

$settingsAction = e(url('profile/settings'));
$passwordAction = e(url('profile/password'));
$avatarAction   = e(url('profile/avatar'));
$avatarRemove   = e(url('profile/avatar/remove'));
$token          = e(csrf_token());

$name    = e((string) ($user['name'] ?? ''));
$email   = e((string) ($user['email'] ?? ''));
$phone   = e((string) ($user['phone'] ?? ''));
$address = e((string) ($user['address'] ?? ''));
$city    = e((string) ($user['city'] ?? ''));
$zip     = e((string) ($user['zip_code'] ?? ''));
$country = e((string) ($user['country'] ?? 'Lesotho'));

$avatarUrl = user_avatar($user);
$initials  = e(initials_of($user['name'] ?? 'U'));

$avatarPreview = $avatarUrl
    ? '<img src="' . e($avatarUrl) . '" alt="Avatar" class="avatar-preview-img">'
    : '<div class="avatar-preview-initials">' . $initials . '</div>';

$removeButton = '';
if ($avatarUrl) {
    $removeButton = <<<HTML
      <form method="POST" action="{$avatarRemove}" onsubmit="return confirm('Remove your profile picture?');" class="d-inline">
        <input type="hidden" name="_token" value="{$token}">
        <button type="submit" class="btn btn-outline-danger btn-sm">
          <i class="fa fa-trash-o"></i> Remove picture
        </button>
      </form>
    HTML;
}

$flashes = flashes();
ob_start();
require base_path('views/partials/flash.php');
$flashHtml = ob_get_clean();

$inner = $flashHtml . <<<HTML
<style>
  .avatar-preview-wrapper {
    display: inline-block; padding: 4px; border-radius: 50%;
    background: linear-gradient(135deg, #e60000, #7a0000);
    box-shadow: 0 8px 32px rgba(230,0,0,.3);
  }
  .avatar-preview-img {
    width: 140px; height: 140px; border-radius: 50%;
    object-fit: cover; display: block;
  }
  .avatar-preview-initials {
    width: 140px; height: 140px; border-radius: 50%;
    background: #1a1a1a;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 2.8rem;
  }
</style>

<div class="profile-content-card mb-4">
  <h3 class="profile-section-title">Profile Picture</h3>
  <div class="row g-4 align-items-center">
    <div class="col-md-3 text-center">
      <div class="avatar-preview-wrapper">{$avatarPreview}</div>
    </div>
    <div class="col-md-9">
      <form method="POST" action="{$avatarAction}" enctype="multipart/form-data" class="mb-3">
        <input type="hidden" name="_token" value="{$token}">
        <label class="form-label">Upload a new picture</label>
        <div class="d-flex gap-2 flex-wrap">
          <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                 class="form-control bg-dark text-light border-secondary" style="max-width:340px" required>
          <button type="submit" class="btn btn-red px-4">Upload</button>
        </div>
        <div class="form-text text-secondary mt-2">JPG, PNG, or WebP. Max 2MB.</div>
      </form>
      {$removeButton}
    </div>
  </div>
</div>

<div class="profile-content-card mb-4">
  <h3 class="profile-section-title">Profile Information</h3>
  <form method="POST" action="{$settingsAction}">
    <input type="hidden" name="_token" value="{$token}">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full name</label>
        <input type="text" name="name" value="{$name}" class="form-control bg-dark text-light border-secondary" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{$email}" class="form-control bg-dark text-light border-secondary" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{$phone}" class="form-control bg-dark text-light border-secondary" placeholder="+266 ...">
      </div>
      <div class="col-md-6">
        <label class="form-label">Country</label>
        <input type="text" name="country" value="{$country}" class="form-control bg-dark text-light border-secondary">
      </div>
      <div class="col-md-8">
        <label class="form-label">Street address</label>
        <input type="text" name="address" value="{$address}" class="form-control bg-dark text-light border-secondary">
      </div>
      <div class="col-md-2">
        <label class="form-label">City</label>
        <input type="text" name="city" value="{$city}" class="form-control bg-dark text-light border-secondary">
      </div>
      <div class="col-md-2">
        <label class="form-label">ZIP</label>
        <input type="text" name="zip_code" value="{$zip}" class="form-control bg-dark text-light border-secondary">
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-red px-4">Save Changes</button>
      </div>
    </div>
  </form>
</div>

<div class="profile-content-card">
  <h3 class="profile-section-title">Change Password</h3>
  <form method="POST" action="{$passwordAction}">
    <input type="hidden" name="_token" value="{$token}">
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Current password</label>
        <input type="password" name="current_password" class="form-control bg-dark text-light border-secondary" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">New password</label>
        <input type="password" name="new_password" class="form-control bg-dark text-light border-secondary" required>
        <div class="form-text text-secondary">At least 8 characters.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Confirm new password</label>
        <input type="password" name="new_password_confirmation" class="form-control bg-dark text-light border-secondary" required>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-red-outline px-4">Update Password</button>
      </div>
    </div>
  </form>
</div>
HTML;

echo view('shop.profile._shell', [
    'activeTab' => 'settings',
    'user'      => $user,
    'inner'     => $inner,
    'title'     => $title,
]);