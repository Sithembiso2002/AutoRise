<?php
/** @var array $rows, $roles */
/** @var string $q, $role */
/** @var int $page, $perPage, $total */

$lastPage = max(1, (int) ceil($total / $perPage));
$token = e(csrf_token());

$roleOptions = '<option value="">All Roles</option>';
foreach ($roles as $r) {
    $sel = ($role === $r) ? ' selected' : '';
    $roleOptions .= '<option value="' . e($r) . '"' . $sel . '>' . e(ucfirst($r)) . '</option>';
}

$tableRows = '';
if ($rows) {
    foreach ($rows as $u) {
        $uid    = (int) $u['user_id'];
        $name   = e((string) ($u['name'] ?? ''));
        $uname  = e((string) ($u['username'] ?? ''));
        $email  = e((string) ($u['email'] ?? ''));
        $uRol   = (string) $u['role'];
        $active = (int) $u['is_active'] === 1;
        $lastL  = $u['last_login_at'] ? e(date('M j, g:i A', strtotime((string) $u['last_login_at']))) : '—';
        $init   = e(initials_of($u['name'] ?? 'U'));
        $av     = user_avatar($u);
        $avHtml = $av
            ? '<img src="' . e($av) . '" style="width:40px;height:40px;border-radius:50%;object-fit:cover">'
            : '<div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#e60000,#7a0000);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem">' . $init . '</div>';

        /* Role dropdown */
        $roleSel = '';
        foreach ($roles as $r) {
            $sel = ($r === $uRol) ? ' selected' : '';
            $roleSel .= '<option value="' . e($r) . '"' . $sel . '>' . e(ucfirst($r)) . '</option>';
        }

        $toggleLabel = $active ? 'Deactivate' : 'Activate';
        $toggleCls   = $active ? 'btn-outline-danger' : 'btn-outline-success';
        $statusBadge = $active
            ? '<span class="badge-status active">Active</span>'
            : '<span class="badge-status inactive">Disabled</span>';

        $tableRows .= <<<HTML
        <tr>
          <td>{$avHtml}</td>
          <td>
            <div>{$name}</div>
            <div class="small text-secondary">@{$uname} &middot; {$email}</div>
          </td>
          <td>
            <form method="POST" action="admin/users/{$uid}/role" class="d-inline">
              <input type="hidden" name="_token" value="{$token}">
              <select name="role" class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="this.form.submit()">
                {$roleSel}
              </select>
            </form>
          </td>
          <td>{$statusBadge}</td>
          <td class="text-end small text-secondary">{$lastL}</td>
          <td class="text-end">
            <form method="POST" action="admin/users/{$uid}/toggle" class="d-inline m-0" onsubmit="return confirm('{$toggleLabel} this user?');">
              <input type="hidden" name="_token" value="{$token}">
              <button class="btn btn-sm {$toggleCls}">{$toggleLabel}</button>
            </form>
          </td>
        </tr>
        HTML;
    }
} else {
    $tableRows = '<tr><td colspan="6" class="text-center text-secondary py-5">No users found.</td></tr>';
}

$pager = '';
if ($lastPage > 1) {
    $pager = '<div class="admin-pagination">';
    $mk = fn($i, $l) => '<a href="?' . e(http_build_query(['page' => $i, 'q' => $q, 'role' => $role])) . '">' . $l . '</a>';
    if ($page > 1) $pager .= $mk($page - 1, '&laquo;');
    for ($i = 1; $i <= $lastPage; $i++) {
        $pager .= $i === $page ? '<span class="active">' . $i . '</span>' : $mk($i, (string) $i);
    }
    if ($page < $lastPage) $pager .= $mk($page + 1, '&raquo;');
    $pager .= '</div>';
}

$body = <<<HTML
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <h2 class="h5 mb-0">Users <span class="text-secondary">({$total})</span></h2>
</div>

<form method="GET" action="admin/users" class="filter-bar">
  <input type="text" name="q" value="{$q}" placeholder="Search name, email, username..." class="form-control grow">
  <select name="role" class="form-select" style="max-width:180px">{$roleOptions}</select>
  <button class="btn btn-red">Filter</button>
  <a href="admin/users" class="btn btn-outline-light">Reset</a>
</form>

<div class="admin-table-wrap">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width:60px"></th>
          <th>User</th>
          <th>Role</th>
          <th>Status</th>
          <th class="text-end">Last Login</th>
          <th class="text-end"></th>
        </tr>
      </thead>
      <tbody>{$tableRows}</tbody>
    </table>
  </div>
</div>

{$pager}
HTML;

echo view('admin._layout', [
    'title'   => 'Users',
    'active'  => 'users',
    'content' => $body,
]);