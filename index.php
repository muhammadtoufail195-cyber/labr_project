<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title='Dashboard';
require __DIR__.'/config.php'; require_login();
require __DIR__.'/app/helpers.php';

/* ===== KPIs (unchanged logic) ===== */
$stats = [
  'patients'     => (int)($conn->query('SELECT COUNT(*) c FROM patients')->fetch_assoc()['c'] ?? 0),
'receipts' => (int)($conn->query("SELECT COUNT(*) AS c FROM receipts WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'] ?? 0),
  'today_sales'  => (float)($conn->query("SELECT COALESCE(SUM(subtotal),0) s FROM receipts WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['s'] ?? 0),
  'discount'     => (float)($conn->query("SELECT COALESCE(SUM(discount),0) d FROM receipts WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['d'] ?? 0),
  'today_paid'   => (float)($conn->query("SELECT COALESCE(SUM(grand_total),0) p FROM receipts WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['p'] ?? 0),
];

/* ===== Last 7 days sparkline (unchanged data) ===== */
$raw = $conn->query("
  SELECT DATE(created_at) d, SUM(grand_total) s
  FROM receipts
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(created_at)
  ORDER BY d ASC
");
$map = [];
while($r = $raw->fetch_assoc()){ $map[$r['d']] = (float)$r['s']; }
$days = [];
for($i=6;$i>=0;$i--){
  $d = date('Y-m-d', strtotime("-$i day"));
  $days[] = ['date'=>$d, 'sum'=>($map[$d] ?? 0.0)];
}
$w=260; $h=70; $pad=8;
$max = max(max(array_map(fn($x)=>$x['sum'],$days)),1);
$pts=[]; $n=count($days);
foreach ($days as $i=>$d){
  $x = $pad + ($i * (($w - 2*$pad) / max(1,$n-1)));
  $y = $pad + ($h - 2*$pad) * (1 - ($d['sum'] / $max));
  $pts[] = round($x,1).','.round($y,1);
}

/* ===== Recent ===== */
$recent = $conn->query("
  SELECT r.id, r.created_at, r.grand_total, r.paid_amount,
         (r.grand_total - r.paid_amount) remaining, p.name
  FROM receipts r
  JOIN patients p ON p.id=r.patient_id
  ORDER BY r.id DESC LIMIT 10
");
include __DIR__.'/app/layout_header.php';
?>

<style>
/* ===== Theme tokens ===== */
:root{
  --card-bg:#ffffff;
  --line:#e5e7eb;
  --text:#0f172a;
  --muted:#64748b;
  --muted-2:#94a3b8;
  --shadow:0 10px 30px rgba(15,23,42,.08);
}

/* ===== Buttons (Button-31) ===== */
.button-31{
  background:#222; border:0; border-radius:10px; color:#fff;
  display:inline-block; font-family:"Inter","Farfetch Basis","Helvetica Neue",Arial,sans-serif;
  font-weight:600; font-size:15px; line-height:1; padding:12px 16px;
  transition:transform .2s, opacity .2s, box-shadow .2s;
  box-shadow:0 6px 16px rgba(2,6,23,.15);
  user-select:none; cursor:pointer;
}
.button-31:hover{ opacity:.9; transform:translateY(-1px); }
.button-31.xs{ padding:8px 12px; font-size:13.5px; border-radius:9999px; }
.button-green{background:#16a34a}.button-green:hover{background:#15803d}
.button-blue{background:#2563eb}.button-blue:hover{background:#1d4ed8}
.button-gray{background:#4b5563}.button-gray:hover{background:#374151}
.button-purple{background:#7c3aed}.button-purple:hover{background:#6d28d9}
.button-orange{background:#ea580c}.button-orange:hover{background:#c2410c}

/* ===== Header strip (subtle gradient bar) ===== */
.dash-head{
  background:linear-gradient(120deg,#eef2ff 0%, #f8fafc 50%, #ecfeff 100%);
  border:1px solid var(--line); border-radius:16px; padding:16px 18px; margin-bottom:14px;
  display:flex; align-items:center; justify-content:space-between;
}
.dash-head h2{ margin:0; color:var(--text); font-size:18px; font-weight:800; letter-spacing:.2px; }
.dash-head .date{ color:var(--muted); font-size:13px; }

/* ===== KPI row ===== */
.kpi-row{
  display:grid; gap:14px; margin-bottom:16px;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}
.kpi{
  background:var(--card-bg); border:1px solid var(--line); border-radius:16px;
  box-shadow:var(--shadow); padding:16px;
  transition:transform .2s, box-shadow .2s;
}
.kpi:hover{ transform:translateY(-2px); box-shadow:0 16px 40px rgba(15,23,42,.12); }
.kpi .top{ display:flex; align-items:center; justify-content:space-between; }
.kpi .title{ font-size:12.5px; color:var(--muted); font-weight:700; letter-spacing:.25px; text-transform:uppercase; }
.kpi .value{ font-size:28px; font-weight:900; color:var(--text); margin:8px 0 2px; }
.kpi .foot{ font-size:12px; color:var(--muted-2); }
.kpi .badge{
  width:40px; height:40px; border-radius:12px; color:#fff; font-weight:800;
  display:flex; align-items:center; justify-content:center;
}
.bg-blue{background:#3b82f6}
.bg-green{background:#16a34a}
.bg-amber{background:#f59e0b}
.bg-rose{background:#e11d48}
.bg-teal{background:#14b8a6}

/* ===== Grid blocks ===== */
.grid-2{ display:grid; gap:16px; grid-template-columns: repeat(12, minmax(0, 1fr)); }

.card{
  background:var(--card-bg); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); padding:16px;
}
.card h3{ margin:0 0 10px; color:var(--text); font-size:16px; font-weight:800; }

/* Sales */
.sales{ grid-column: span 6 / span 6; }
.sales .sub{ color:var(--muted); font-size:12px; margin-top:-6px; margin-bottom:10px; }
.spark{ display:flex; gap:18px; align-items:center; flex-wrap:wrap; }
.legend{ font-size:12px; color:#475569; display:flex; gap:12px; flex-wrap:wrap; }
.legend b{ color:var(--text); }

/* Quick actions */
.quick{ grid-column: span 6 / span 6; }
.quick .rowbtn{ display:flex; flex-wrap:wrap; gap:10px; }

/* Table */
.table-wrap{ width:100%; overflow-x:auto; }
.table{ width:100%; border-collapse:separate; border-spacing:0; }
.table th, .table td{ padding:11px 12px; }
.table th{
  background:#f9fafb; border-bottom:1px solid var(--line); color:#111827; font-weight:700; text-align:left;
}
.table tbody tr+tr td{ border-top:1px solid #f3f4f6; }
.table tbody tr:hover td{ background:#f8fafc; }
.tactions{ text-align:right; white-space:nowrap; }

@media (max-width: 1024px){
  .sales, .quick{ grid-column: span 12 / span 12; }
}
</style>

<div class="dash-head">
  <h2>Dashboard Overview</h2>
<div class="date" id="liveDate"></div>
</div>

<!-- KPI Row -->
<div class="kpi-row">
  <div class="kpi">
    <div class="top"><div class="title">Patients</div><div class="badge bg-blue">👤</div></div>
    <div class="value"><?= e(number_format($stats['patients'])) ?></div>
    <div class="foot">Total registered</div>
  </div>

  <div class="kpi">
    <div class="top"><div class="title">Receipts</div><div class="badge bg-amber">🧾</div></div>
    <div class="value"><?= e(number_format($stats['receipts'])) ?></div>
    <div class="foot">Today</div>
  </div>

  <div class="kpi">
    <div class="top"><div class="title">Today’s Sales</div><div class="badge bg-green">₨</div></div>
    <div class="value">Rs <?= money($stats['today_sales']) ?></div>
    <div class="foot">Before discount</div>
  </div>

  <div class="kpi">
    <div class="top"><div class="title">Discount</div><div class="badge bg-rose">🏷️</div></div>
    <div class="value">Rs <?= money($stats['discount']) ?></div>
    <div class="foot">Given today</div>
  </div>

  <div class="kpi">
    <div class="top"><div class="title">Today Paid</div><div class="badge bg-teal">💰</div></div>
    <div class="value">Rs <?= money($stats['today_paid']) ?></div>
    <div class="foot">Paid = Total − Discount</div>
  </div>
</div>

<!-- Charts & Quick Actions -->
<div class="grid-2">
  <div class="card sales">
    <h3>Sales — Last 7 Days</h3>
    <div class="sub">Rolling daily totals</div>

    <div class="spark">
      <svg width="260" height="70" viewBox="0 0 260 70" xmlns="http://www.w3.org/2000/svg" aria-label="7-day sales">
        <defs>
          <linearGradient id="g" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.35"/>
            <stop offset="100%" stop-color="#2563eb" stop-opacity="0.02"/>
          </linearGradient>
        </defs>
        <rect x="0" y="0" width="260" height="70" fill="#ffffff"/>
        <polyline points="<?= e(implode(' ', $pts)) ?>" fill="none" stroke="#2563eb" stroke-width="2" />
        <!-- faint area fill under line -->
        <polygon points="<?= e(implode(' ', $pts)) ?> 252,62 8,62" fill="url(#g)"></polygon>
      </svg>

      <div class="legend">
        <?php $first = reset($days); $last = end($days); $mx=$max; ?>
        <div><b>Max:</b> Rs <?= money($mx) ?></div>
        <div><b>First:</b> Rs <?= money($first['sum'] ?? 0) ?></div>
        <div><b>Last:</b> Rs <?= money($last['sum'] ?? 0) ?></div>
      </div>
    </div>

    <div class="sub" style="margin-top:8px">
      <?php foreach($days as $d): ?>
        <span style="display:inline-block;margin-right:12px">
          <b><?= e(date('D', strtotime($d['date']))) ?></b>: Rs <?= money($d['sum']) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card quick">
    <h3>Quick Actions</h3>
    <div class="rowbtn">
      <a class="button-31 button-green"  href="<?= BASE_URL ?>/receipt_new.php">+ New Receipt</a>
      <a class="button-31 button-blue"   href="<?= BASE_URL ?>/patients.php">Patients</a>
      <a class="button-31 button-purple" href="<?= BASE_URL ?>/tests.php">Tests</a>
      <a class="button-31 button-orange" href="<?= BASE_URL ?>/categories.php">Categories</a>
    </div>
  </div>
</div>

<!-- Recent Receipts -->
<div class="card" style="margin-top:16px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <h3 style="margin:0">Recent Receipts</h3>
    <a class="button-31 button-gray" href="<?= BASE_URL ?>/reports_summary.php">View All</a>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date / Time</th>
          <th>Patient</th>
          <th>Total</th>
          <th>Paid</th>
          <th>Remaining</th>
          <th class="tactions">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $recent->fetch_assoc()): ?>
        <tr>
          <td><?= e($row['id']) ?></td>
          <td><?= e($row['created_at']) ?></td>
          <td><?= e($row['name']) ?></td>
          <td>Rs <?= money($row['grand_total']) ?></td>
          <td>Rs <?= money($row['paid_amount']) ?></td>
          <td>Rs <?= money($row['remaining']) ?></td>
          <td class="tactions">
            <a class="button-31 xs button-blue" href="<?= BASE_URL ?>/receipt_view.php?id=<?= e($row['id']) ?>">View</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function updateDateTime() {
  // Always use Pakistan timezone via toLocaleString
  const now = new Date().toLocaleString("en-PK", {
    timeZone: "Asia/Karachi",
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true
  });

  document.getElementById("liveDate").textContent = now;
}

// update every second
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
<?php include __DIR__.'/app/layout_footer.php'; ?>
