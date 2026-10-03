<?php
/** @var string $range */
/** @var array $salesByDay, $byStatus, $topProducts, $topCustomers, $byCategory */
/** @var array $salesTotal */

$money = fn($n) => 'M ' . number_format((float) $n, 2);

/* Summary cards */
$totRev = (float) ($salesTotal['rev'] ?? 0);
$totOrd = (int)   ($salesTotal['cnt'] ?? 0);
$avg    = (float) ($salesTotal['avg'] ?? 0);

/* Bar chart */
$maxRev = 0.0;
foreach ($salesByDay as $d) $maxRev = max($maxRev, (float) $d['rev']);
$bars = '';
if ($salesByDay) {
    foreach ($salesByDay as $d) {
        $h = $maxRev > 0 ? max(4, (int) round(((float) $d['rev'] / $maxRev) * 180)) : 4;
        $lbl = date('M j', strtotime((string) $d['d']));
        $v   = number_format((float) $d['rev'], 0);
        $bars .= '<div class="bar" style="height:' . $h . 'px" title="' . e($lbl . ': M ' . $v) . '"></div>';
    }
} else {
    $bars = '<div style="color:#666;padding:70px 0;text-align:center;width:100%">No sales in this period.</div>';
}

/* Status breakdown */
$statRows = '';
foreach ($byStatus as $s) {
    $st = e((string) $s['order_status']);
    $cnt = (int) $s['cnt'];
    $rev = $money($s['rev']);
    $statRows .= "<tr><td><span class=\"badge-status {$st}\">{$st}</span></td><td class=\"text-end\">{$cnt}</td><td class=\"text-end price-tag\">{$rev}</td></tr>";
}
if (!$statRows) $statRows = '<tr><td colspan="3" class="text-center text-secondary py-4">No data.</td></tr>';

/* Top products */
$prodRows = '';
foreach ($topProducts as $p) {
    $n = e($p['name']);
    $u = (int) $p['units_sold'];
    $r = $money($p['revenue']);
    $prodRows .= "<tr><td>{$n}</td><td class=\"text-end\">{$u}</td><td class=\"text-end price-tag\">{$r}</td></tr>";
}
if (!$prodRows) $prodRows = '<tr><td colspan="3" class="text-center text-secondary py-4">No data.</td></tr>';

/* Top customers */
$custRows = '';
foreach ($topCustomers as $c) {
    $n = e($c['name']);
    $em= e($c['email']);
    $cnt = (int) $c['order_count'];
    $lv = $money($c['lifetime_value']);
    $custRows .= "<tr><td><div>{$n}</div><div class=\"small text-secondary\">{$em}</div></td><td class=\"text-end\">{$cnt}</td><td class=\"text-end price-tag\">{$lv}</td></tr>";
}
if (!$custRows) $custRows = '<tr><td colspan="3" class="text-center text-secondary py-4">No data.</td></tr>';

/* By category */
$catRows = '';
foreach ($byCategory as $c) {
    $n = e($c['category_name']);
    $o = (int) $c['orders'];
    $r = $money($c['revenue']);
    $catRows .= "<tr><td>{$n}</td><td class=\"text-end\">{$o}</td><td class=\"text-end price-tag\">{$r}</td></tr>";
}
if (!$catRows) $catRows = '<tr><td colspan="3" class="text-center text-secondary py-4">No data.</td></tr>';

$body = <<<HTML
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
  <h2 class="h5 mb-0">Reports</h2>
  <form method="GET" action="admin/reports" class="d-flex gap-2">
    <select name="range" class="form-select" onchange="this.form.submit()">
      <option value="7"   {$sel7}>Last 7 days</option>
      <option value="30"  {$sel30}>Last 30 days</option>
      <option value="90"  {$sel90}>Last 90 days</option>
      <option value="365" {$sel365}>Last 365 days</option>
    </select>
  </form>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-money"></i></div>
      <div class="kpi-value">{$money($totRev)}</div>
      <div class="kpi-label">Revenue ({$range}d)</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-shopping-bag"></i></div>
      <div class="kpi-value">{$totOrd}</div>
      <div class="kpi-label">Orders</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa fa-line-chart"></i></div>
      <div class="kpi-value">{$money($avg)}</div>
      <div class="kpi-label">Avg Order Value</div>
    </div>
  </div>
</div>

<div class="admin-card mb-4">
  <h3 class="admin-card-title">Daily Revenue</h3>
  <div class="bar-chart">{$bars}</div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="admin-card">
      <h3 class="admin-card-title">Orders by Status</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Status</th><th class="text-end">Count</th><th class="text-end">Revenue</th></tr></thead>
          <tbody>{$statRows}</tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <h3 class="admin-card-title">Top Products</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Product</th><th class="text-end">Sold</th><th class="text-end">Revenue</th></tr></thead>
          <tbody>{$prodRows}</tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <h3 class="admin-card-title">Top Customers</h3>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Customer</th><th class="text-end">Orders</th><th class="text-end">Lifetime</th></tr></thead>
          <tbody>{$custRows}</tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="admin-card">
  <h3 class="admin-card-title">Sales by Category</h3>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Category</th><th class="text-end">Orders</th><th class="text-end">Revenue</th></tr></thead>
      <tbody>{$catRows}</tbody>
    </table>
  </div>
</div>
HTML;

/* Inject the selected range */
$body = str_replace(
    ['{$sel7}', '{$sel30}', '{$sel90}', '{$sel365}'],
    [
        $range === '7'   ? 'selected' : '',
        $range === '30'  ? 'selected' : '',
        $range === '90'  ? 'selected' : '',
        $range === '365' ? 'selected' : '',
    ],
    $body
);

echo view('admin._layout', [
    'title'   => 'Reports',
    'active'  => 'reports',
    'content' => $body,
]);