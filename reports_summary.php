<?php
$page_title = 'Receipts Summary / History';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

// Fetch all receipts with patient details joined
$sql = "SELECT r.*, p.name AS patient_fullname, p.mr_no 
        FROM receipts r 
        LEFT JOIN patients p ON r.patient_id = p.id 
        ORDER BY r.id DESC";

$res = $conn->query($sql);
$RECEIPTS = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $RECEIPTS[] = $row;
    }
}

include __DIR__.'/app/layout_header.php';
?>

<style>
.card { background:#fff; padding:20px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:20px; }
.card-title { font-size:20px; font-weight:700; color:#0f172a; margin-bottom:16px; }
.table { width:100%; border-collapse:collapse; margin-top:10px; }
.table th { background:#f1f5f9; padding:10px; text-align:left; font-size:12px; font-weight:700; color:#475569; border-bottom:2px solid #cbd5e1; }
.table td { padding:10px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#334155; }
.table tr:hover { background:#f8fafc; }
.badge-paid { background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:12px; font-weight:600; font-size:11px; }
</style>

<div class="card">
  <h3 class="card-title">📜 Receipts Summary / History (Total: <?= count($RECEIPTS) ?>)</h3>

  <div style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr>
          <th># Receipt</th>
          <th>Date / Time</th>
          <th>Patient Name</th>
          <th>Subtotal</th>
          <th>Discount</th>
          <th>Total Amount</th>
          <th>Paid</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($RECEIPTS)): ?>
          <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:30px;">No receipt records found in Database. Please create a <b>+ New Receipt</b> first.</td></tr>
        <?php else: ?>
          <?php foreach ($RECEIPTS as $r): 
            $rec_no = $r['receipt_no'] ?? ('#'.$r['id']);
            $dt     = $r['created_at'] ?? $r['created_datetime'] ?? $r['date'] ?? 'N/A';
            $pname  = !empty($r['patient_fullname']) ? $r['patient_fullname'] : ($r['patient_name'] ?? 'Walk-in Patient');
            $sub    = (float)($r['subtotal'] ?? $r['total'] ?? 0);
            $disc   = (float)($r['discount'] ?? 0);
            $tot    = (float)($r['total'] ?? ($sub - $disc));
            $paid   = (float)($r['paid'] ?? $r['paid_amount'] ?? $tot);
          ?>
            <tr>
              <td><b><?= htmlspecialchars($rec_no) ?></b></td>
              <td><?= htmlspecialchars($dt) ?></td>
              <td><b><?= htmlspecialchars($pname) ?></b> <?= !empty($r['mr_no']) ? '('.htmlspecialchars($r['mr_no']).')' : '' ?></td>
              <td>Rs <?= number_format($sub, 2) ?></td>
              <td>Rs <?= number_format($disc, 2) ?></td>
              <td><b>Rs <?= number_format($tot, 2) ?></b></td>
              <td><span class="badge-paid">Rs <?= number_format($paid, 2) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>
