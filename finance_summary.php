<?php
$page_title = 'Finance Summary';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

/* Range Filter */
$range = $_GET['range'] ?? 'Today';

$where_clause = "";
switch ($range) {
    case 'Yesterday':
        $where_clause = "WHERE DATE(created_at) = SUBDATE(CURDATE(), 1)";
        break;
    case 'Week':
        $where_clause = "WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'Month':
        $where_clause = "WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        break;
    case 'Year':
        $where_clause = "WHERE YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'All':
        $where_clause = "";
        break;
    case 'Today':
    default:
        $range = 'Today';
        $where_clause = "WHERE DATE(created_at) = CURDATE()";
        break;
}

/* Fetch Receipts Safely */
$sql = "SELECT * FROM receipts $where_clause";
$res = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$total = 0;
$paid  = 0;

foreach ($rows as $r) {
    $tot = (float)($r['total'] ?? $r['total_amount'] ?? $r['grand_total'] ?? $r['subtotal'] ?? 0);
    $pd  = (float)($r['paid'] ?? $r['paid_amount'] ?? $r['amount_paid'] ?? 0);
    
    $total += $tot;
    $paid  += $pd;
}

$remaining = max(0, $total - $paid);

include __DIR__.'/app/layout_header.php';
?>

<style>
.card { background:#fff; padding:24px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.card-title { font-size:22px; font-weight:700; color:#0f172a; margin-bottom:20px; }

.filter-box { margin-bottom:24px; }
.filter-select { padding:10px 14px; font-size:14px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; font-weight:600; color:#334155; cursor:pointer; min-width:160px; }

.finance-cards { display:flex; flex-wrap:wrap; gap:20px; margin-top:10px; }
.f-card { flex:1; min-width:220px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; }

.f-card.total { border-left:5px solid #2563eb; }
.f-card.paid { border-left:5px solid #16a34a; }
.f-card.remaining { border-left:5px solid #dc2626; }

.f-label { font-size:14px; font-weight:600; color:#64748b; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
.f-value { font-size:24px; font-weight:700; color:#0f172a; }

.f-card.total .f-value { color:#2563eb; }
.f-card.paid .f-value { color:#16a34a; }
.f-card.remaining .f-value { color:#dc2626; }
</style>

<div class="card">
  <h3 class="card-title">💰 Finance Summary</h3>

  <div class="filter-box">
    <form method="get" action="finance.php" id="filterForm">
      <select name="range" class="filter-select" onchange="document.getElementById('filterForm').submit();">
        <option value="Today" <?= $range === 'Today' ? 'selected' : '' ?>>Today</option>
        <option value="Yesterday" <?= $range === 'Yesterday' ? 'selected' : '' ?>>Yesterday</option>
        <option value="Week" <?= $range === 'Week' ? 'selected' : '' ?>>Week</option>
        <option value="Month" <?= $range === 'Month' ? 'selected' : '' ?>>Month</option>
        <option value="Year" <?= $range === 'Year' ? 'selected' : '' ?>>Year</option>
        <option value="All" <?= $range === 'All' ? 'selected' : '' ?>>All Time</option>
      </select>
    </form>
  </div>

  <div class="finance-cards">
    <div class="f-card total">
      <div class="f-label">Total Amount</div>
      <div class="f-value">Rs <?= number_format($total, 2) ?></div>
    </div>

    <div class="f-card paid">
      <div class="f-label">Paid Amount</div>
      <div class="f-value">Rs <?= number_format($paid, 2) ?></div>
    </div>

    <div class="f-card remaining">
      <div class="f-label">Remaining Amount</div>
      <div class="f-value">Rs <?= number_format($remaining, 2) ?></div>
    </div>
  </div>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>
