<?php
// receipts_summary.php — compact UI + clean print/PDF
$page_title = 'Receipts Summary';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

/* -------- Lab header for PDF/print -------- */
$lab = $conn->query("
  SELECT name, address, phone, COALESCE(whatsapp,'') AS whatsapp, COALESCE(email,'') AS email
  FROM lab_profile LIMIT 1
")->fetch_assoc();
if (!$lab) {
  $lab = [
    'name' => 'Welfare MEDICAL LABORATORY',
    'address' => 'Opp Emergency Exit Gate Golden Tower',
    'phone' => '091-2580810 / 2560810',
    'whatsapp' => '0345-3119085',
    'email' => 'info@lab.local'
  ];
}

/* -------- Inputs -------- */
$allowed_ranges = ['today','week','month','year','custom'];
$range = $_GET['range'] ?? 'today';
if (!in_array($range, $allowed_ranges, true)) $range = 'today';

$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort'] ?? 'date';   // date|patient|total|paid|remaining
$dir    = strtolower($_GET['dir'] ?? 'desc'); // asc|desc
$export = ($_GET['export'] ?? '') === 'print';

$sortMap = [
  'date'      => 'r.created_at',
  'patient'   => 'p.name',
  'total'     => 'r.grand_total',
  'paid'      => 'r.paid_amount',
  'remaining' => '(r.grand_total - r.paid_amount)'
];
$sortCol = $sortMap[$sort] ?? $sortMap['date'];
$dir = $dir === 'asc' ? 'ASC' : 'DESC';

/* -------- Range where clause -------- */
$where = '1';
$desc = 'All time';
$isDate = fn($s) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/',$s);

switch ($range) {
  case 'today':
    $where = 'DATE(r.created_at)=CURDATE()';
    $desc  = 'Today';
    break;
  case 'week':
    $where = 'YEARWEEK(r.created_at,1)=YEARWEEK(CURDATE(),1)';
    $desc  = 'This week';
    break;
  case 'month':
    $where = 'YEAR(r.created_at)=YEAR(CURDATE()) AND MONTH(r.created_at)=MONTH(CURDATE())';
    $desc  = 'This month';
    break;
  case 'year':
    $where = 'YEAR(r.created_at)=YEAR(CURDATE())';
    $desc  = 'This year';
    break;
  case 'custom':
    $from = trim($_GET['from'] ?? '');
    $to   = trim($_GET['to'] ?? '');
    if ($isDate($from) && $isDate($to)) {
      $fromEsc = $conn->real_escape_string($from);
      $toEsc   = $conn->real_escape_string($to);
      $where   = "DATE(r.created_at) BETWEEN '{$fromEsc}' AND '{$toEsc}'";
      $desc    = "Custom: {$from} → {$to}";
    } else {
      $where = 'DATE(r.created_at)=CURDATE()';
      $desc  = 'Today (invalid custom dates)';
    }
    break;
}

/* -------- Search (patient/test) -------- */
$search_sql = '';
if ($search !== '') {
  $q = $conn->real_escape_string($search);
  $search_sql = " AND (p.name LIKE '%{$q}%' OR EXISTS (
                  SELECT 1 FROM receipt_items ri2
                  WHERE ri2.receipt_id = r.id AND ri2.test_name LIKE '%{$q}%'
                ))";
}

/* -------- Main query (with tests list) -------- */
$sql = "
  SELECT
    r.id,
    r.created_at,
    r.subtotal,
    r.discount,
    r.grand_total,
    r.paid_amount,
    (r.grand_total - r.paid_amount) AS remaining,
    p.name AS patient_name,
    COALESCE(
      (SELECT GROUP_CONCAT(DISTINCT ri.test_name ORDER BY ri.test_name SEPARATOR ', ')
         FROM receipt_items ri WHERE ri.receipt_id = r.id),
      ''
    ) AS tests_list
  FROM receipts r
  JOIN patients p ON p.id = r.patient_id
  WHERE $where $search_sql
  ORDER BY $sortCol $dir
";
$res = $conn->query($sql);
if (!$res) { $friendly = "Query failed."; $detail = $conn->error; }

/* -------- Page -------- */
include __DIR__.'/app/layout_header.php';
?>
<style>
/* ===== Compact, clean, professional ===== */
:root{
  --ink:#0f172a; --muted:#475569; --line:#e5e7eb; --bg:#f8fafc; --accent:#2563eb;
}
.page-wrap{font-size:13px;}
h3{margin:0; font-size:15px; font-weight:700; color:var(--ink)}
.card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px}
.controls{display:flex;gap:8px;flex-wrap:wrap;align-items:end}
.label{font-size:11px;color:#64748b;margin-bottom:4px}
.input,.select,.btn{
  height:34px; padding:6px 10px; font-size:12.5px; border:1px solid var(--line); border-radius:8px; background:#fff;
}
.select{padding-right:28px}
.input:focus,.select:focus{outline:none;border-color:#94a3b8;box-shadow:0 0 0 3px rgba(148,163,184,.25)}
.btn{background:#fff;cursor:pointer}
.btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
.btn.link{border-color:#cbd5e1;background:#f1f5f9}
.badge{display:inline-block;padding:3px 8px;border:1px solid #dbe3ea;border-radius:999px;font-size:11px;color:#334155;background:#f8fafc}

/* Table */
.table{width:100%;border-collapse:separate;border-spacing:0}
.table th,.table td{padding:8px 10px;font-size:12px}
.table thead th{background:#f1f5f9;color:#111827;font-weight:700;border-bottom:1px solid #e2e8f0;position:sticky;top:0}
.table tbody tr:nth-child(odd) td{background:#fafafa}
.table tbody tr:hover td{background:#f6faff}
.table .num{text-align:right;white-space:nowrap}

/* Print header/footer */
.print-header,.print-footer{display:none}
@media print{
  @page{ size:A4; margin: 10mm; }
  .no-print{display:none !important}
  .print-header{
    display:block;text-align:center;
    border-bottom:1px dashed #cbd5e1;padding-bottom:6px;margin-bottom:8px
  }
  .ph-title{font-weight:900;font-size:18px}
  .ph-meta{font-size:12px;color:#334155;margin-top:2px}
  .print-footer{
    display:flex;justify-content:space-between;align-items:center;
    border-top:1px dashed #cbd5e1;padding-top:6px;margin-top:10px;font-size:12px;color:#334155
  }
}
</style>

<?php if ($export): ?>
  <div class="print-header">
    <div class="ph-title"><?= e($lab['name']) ?></div>
    <div class="ph-meta">
      <?= e($lab['address']) ?> • 📞 <?= e($lab['phone']) ?>
      <?php if($lab['whatsapp']): ?> • 💬 <?= e($lab['whatsapp']) ?><?php endif; ?>
      <?php if($lab['email']): ?> • ✉️ <?= e($lab['email']) ?><?php endif; ?>
    </div>
    <div class="ph-meta"><b>Receipts Summary</b> — <?= e($desc) ?><?= $search? ' — Filter: “'.e($search).'”' : '' ?></div>
  </div>
<?php endif; ?>

<div class="page-wrap">
  <div class="no-print" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <h3>Receipts Summary</h3>
    <span class="badge">Range: <?= e($desc) ?><?= $search? ' · Filter: “'.e($search).'”' : '' ?></span>
  </div>

  <form method="get" class="card controls no-print" style="margin-bottom:10px">
    <div>
      <div class="label">Range</div>
      <select class="select" name="range" onchange="toggleCustom(this.value);">
        <?php foreach ($allowed_ranges as $opt): ?>
          <option value="<?= e($opt) ?>" <?= $range===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="fromWrap" style="<?= $range==='custom'?'':'display:none' ?>">
      <div class="label">From</div>
      <input class="input" type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>">
    </div>
    <div id="toWrap" style="<?= $range==='custom'?'':'display:none' ?>">
      <div class="label">To</div>
      <input class="input" type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>">
    </div>
    <div style="min-width:220px;flex:1">
      <div class="label">Search (patient/test)</div>
      <input class="input" type="text" name="q" value="<?= e($search) ?>" placeholder="e.g. Ali, CBC, LFT…">
    </div>
    <div>
      <div class="label">Sort</div>
      <select class="select" name="sort">
        <option value="date"      <?= $sort==='date'?'selected':'' ?>>Date/Time</option>
        <option value="patient"   <?= $sort==='patient'?'selected':'' ?>>Patient</option>
        <option value="total"     <?= $sort==='total'?'selected':'' ?>>Grand Total</option>
        <option value="paid"      <?= $sort==='paid'?'selected':'' ?>>Paid</option>
        <option value="remaining" <?= $sort==='remaining'?'selected':'' ?>>Remaining</option>
      </select>
    </div>
    <div>
      <div class="label">Dir</div>
      <select class="select" name="dir">
        <option value="desc" <?= strtolower($_GET['dir'] ?? 'desc')==='desc'?'selected':'' ?>>DESC</option>
        <option value="asc"  <?= strtolower($_GET['dir'] ?? '')==='asc'?'selected':'' ?>>ASC</option>
      </select>
    </div>
    <div style="display:flex;gap:6px;align-items:end">
      <button class="btn primary">Apply</button>
      <a class="btn link" href="?<?= http_build_query(array_merge($_GET,['export'=>'print'])) ?>">Export PDF</a>
    </div>
  </form>

  <script>
    function toggleCustom(val){
      const on = val==='custom';
      document.getElementById('fromWrap').style.display = on?'':'none';
      document.getElementById('toWrap').style.display   = on?'':'none';
    }
  </script>

  <?php if (isset($friendly)): ?>
    <div class="card" style="background:#fff7ed;border-color:#fed7aa;color:#7c2d12">
      <?= e($friendly) ?><br><small><code><?= e($detail) ?></code></small>
    </div>
  <?php else: ?>
    <div class="card" style="padding:0; overflow:auto">
      <table class="table">
        <thead>
          <tr>
            <th style="width:70px">#</th>
            <th style="width:160px">Date/Time</th>
            <th style="width:220px">Patient</th>
            <th>Tests</th>
            <th class="num" style="width:110px">Subtotal</th>
            <th class="num" style="width:100px">Discount</th>
            <th class="num" style="width:110px">Total</th>
            <th class="num" style="width:100px">Paid</th>
            <th class="num" style="width:110px">Remaining</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sum_sub=0; $sum_dis=0; $sum_tot=0; $sum_paid=0; $sum_rem=0;
          while ($row = $res->fetch_assoc()):
            $sum_sub += (float)$row['subtotal'];
            $sum_dis += (float)$row['discount'];
            $sum_tot += (float)$row['grand_total'];
            $sum_paid+= (float)$row['paid_amount'];
            $sum_rem += (float)$row['remaining'];
          ?>
            <tr>
              <td>
                <a href="<?= BASE_URL ?>/print_receipt.php?id=<?= e($row['id']) ?>" target="_blank"
                   style="text-decoration:none;color:#111827">#<?= e($row['id']) ?></a>
              </td>
              <td><?= e($row['created_at']) ?></td>
              <td><?= e($row['patient_name']) ?></td>
              <td style="color:#1f2937"><?= e($row['tests_list']) ?></td>
              <td class="num">Rs <?= money($row['subtotal']) ?></td>
              <td class="num">Rs <?= money($row['discount']) ?></td>
              <td class="num"><b>Rs <?= money($row['grand_total']) ?></b></td>
              <td class="num">Rs <?= money($row['paid_amount']) ?></td>
              <td class="num">Rs <?= money($row['remaining']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" style="text-align:right;padding-right:10px">Totals</th>
            <th class="num">Rs <?= money($sum_sub) ?></th>
            <th class="num">Rs <?= money($sum_dis) ?></th>
            <th class="num">Rs <?= money($sum_tot) ?></th>
            <th class="num">Rs <?= money($sum_paid) ?></th>
            <th class="num">Rs <?= money($sum_rem) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if ($export): ?>
  <div class="print-footer">
    <div>Generated: <?= date('Y-m-d H:i:s') ?></div>
    <div>Software by: Mohammad Akif — WhatsApp <?= e($lab['whatsapp'] ?: '0345-3119085') ?></div>
  </div>
  <script>window.addEventListener('load',()=>window.print());</script>
<?php endif; ?>

<?php include __DIR__.'/app/layout_footer.php';
