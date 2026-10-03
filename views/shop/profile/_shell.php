<?php
/** @var string $activeTab */
/** @var array $user */
/** @var string $inner */
/** @var string $title */

$navItems = [
    'overview' => ['fa-dashboard',     'Overview',  e(url('profile'))],
    'orders'   => ['fa-shopping-bag',  'My Orders', e(url('profile/orders'))],
    'settings' => ['fa-cog',           'Settings',  e(url('profile/settings'))],
];

$initials  = e(initials_of($user['name'] ?? 'U'));
$avatarUrl = user_avatar($user);

$name   = e($user['name'] ?? 'User');
$email  = e($user['email'] ?? '');
$role   = e(ucfirst((string) ($user['role'] ?? 'customer')));
$joined = !empty($user['created_at'])
    ? e(date('M Y', strtotime((string) $user['created_at'])))
    : '—';

$avatarHtml = $avatarUrl
    ? '<img src="' . e($avatarUrl) . '" alt="Avatar" class="profile-sidebar-avatar-img">'
    : '<div class="profile-sidebar-avatar">' . $initials . '</div>';

ob_start();
?>
<style>
  .profile-sidebar {
    background: linear-gradient(160deg, #171717 0%, #111 100%);
    border: 1px solid #232323;
    border-radius: 18px;
    padding: 28px 20px;
    position: sticky;
    top: 100px;
  }
  .profile-sidebar-avatar {
    width: 96px; height: 96px; border-radius: 50%;
    background: linear-gradient(135deg, #e60000, #7a0000);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 2rem;
    margin: 0 auto 16px;
    box-shadow: 0 8px 32px rgba(230,0,0,.4);
  }
  .profile-sidebar-avatar-img {
    width: 96px; height: 96px; border-radius: 50%;
    object-fit: cover;
    display: block;
    margin: 0 auto 16px;
    border: 3px solid rgba(230,0,0,.5);
    box-shadow: 0 8px 32px rgba(230,0,0,.4);
  }
  .profile-sidebar h5 { color: #fff; text-align: center; margin-bottom: 4px; font-weight: 700; font-size: 1.05rem; }
  .profile-sidebar-email { text-align: center; color: #777; font-size: .82rem; margin-bottom: 12px; word-break: break-all; }
  .profile-role-badge {
    display: block; text-align: center; margin: 0 auto 20px; width: fit-content;
    font-size: .68rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .8px; padding: 4px 10px; border-radius: 4px;
    background: rgba(230,0,0,.15); color: #ff4444;
    border: 1px solid rgba(230,0,0,.3);
  }
  .profile-sidebar hr { border-color: #232323; margin: 20px 0; }
  .profile-nav { list-style: none; padding: 0; margin: 0; }
  .profile-nav li { margin-bottom: 4px; }
  .profile-nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-radius: 10px;
    color: #c8c8c8; text-decoration: none; font-size: .9rem;
    font-weight: 500; transition: all .25s cubic-bezier(.4,0,.2,1);
  }
  .profile-nav a i { width: 18px; text-align: center; color: #666; transition: color .2s; }
  .profile-nav a:hover { background: #1c1c1c; color: #fff; padding-left: 20px; }
  .profile-nav a:hover i { color: #e60000; }
  .profile-nav a.active {
    background: linear-gradient(90deg, rgba(230,0,0,.15), transparent);
    color: #fff; border-left: 3px solid #e60000; padding-left: 13px;
  }
  .profile-nav a.active i { color: #e60000; }
  .profile-meta-small {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px; font-size: .78rem; color: #666;
  }
  .profile-meta-small i { width: 14px; text-align: center; }
  .profile-content-card {
    background: #161616; border: 1px solid #232323;
    border-radius: 18px; padding: 28px;
  }
  .profile-section-title {
    font-size: 1.2rem; font-weight: 700;
    margin-bottom: 20px; color: #fff;
    padding-bottom: 12px; border-bottom: 1px solid #232323;
  }
  .stat-card {
    background: linear-gradient(160deg, #1c1c1c 0%, #131313 100%);
    border: 1px solid #232323; border-radius: 14px; padding: 22px;
    transition: transform .25s, border-color .25s;
  }
  .stat-card:hover { transform: translateY(-4px); border-color: #e60000; }
  .stat-card .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(230,0,0,.12); color: #e60000;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 14px;
  }
  .stat-card .stat-value { font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 6px; }
  .stat-card .stat-label { font-size: .82rem; color: #888; text-transform: uppercase; letter-spacing: .8px; }
  .btn-red-outline {
    background: transparent; border: 1px solid #e60000; color: #e60000;
    padding: 8px 20px; border-radius: 24px; transition: all .25s;
  }
  .btn-red-outline:hover { background: #e60000; color: #fff; }
</style>

<div class="container my-5">
  <div class="row g-4">
    <div class="col-lg-3">
      <aside class="profile-sidebar">
        <?= $avatarHtml ?>
        <h5><?= $name ?></h5>
        <div class="profile-sidebar-email"><?= $email ?></div>
        <div class="profile-role-badge"><?= $role ?></div>

        <hr>

        <ul class="profile-nav">
          <?php foreach ($navItems as $key => $item): ?>
            <li>
              <a href="<?= $item[2] ?>" class="<?= $key === $activeTab ? 'active' : '' ?>">
                <i class="fa <?= $item[0] ?>"></i><span><?= $item[1] ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>

        <hr>

        <div class="profile-meta-small">
          <i class="fa fa-calendar"></i><span>Member since <?= $joined ?></span>
        </div>
      </aside>
    </div>

    <div class="col-lg-9">
      <?= $inner ?>
    </div>
  </div>
</div>
<?php
$wrapper = ob_get_clean();
echo view('layouts.app', ['title' => $title, 'content' => $wrapper]);