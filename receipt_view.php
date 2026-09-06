<?php
$page_title='Receipt';
require __DIR__.'/config.php'; require_login();
require __DIR__.'/app/helpers.php';

$id = (int)($_GET['id'] ?? 0);
$r = $conn->query('SELECT r.*, p.name, p.gender, p.age, p.address, p.mr_no FROM receipts r JOIN patients p ON p.id=r.patient_id WHERE r.id='.$id)->fetch_assoc();
if(!$r){ die('Receipt not found'); }
$items = $conn->query('SELECT * FROM receipt_items WHERE receipt_id='.$id);

include __DIR__.'/app/layout_header.php'; ?>
<div class="card">
  <h3>Receipt #<?= e($id) ?></h3>
  <div class="row">
    <div class="col-6">
      <b>Patient:</b> <?= e($r['name']) ?><br>
      <b>MR No:</b> <?= e($r['mr_no']) ?><br>
      <b>Gender:</b> <?= e($r['gender']) ?>, <b>Age:</b> <?= e($r['age']) ?><br>
      <b>Address:</b> <?= e($r['address']) ?>
    </div>
    <div class="col-6" style="text-align:right">
      <b>Date/Time:</b> <?= e($r['created_at']) ?><br>
      <b>Subtotal:</b> Rs <?= money($r['subtotal']) ?><br>
      <b>Discount:</b> Rs <?= money($r['discount']) ?><br>
      <b>Grand Total:</b> Rs <?= money($r['grand_total']) ?><br>
      <b>Paid:</b> Rs <?= money($r['paid_amount']) ?> — <b>Remaining:</b> Rs <?= money($r['grand_total']-$r['paid_amount']) ?>
    </div>
  </div>
  <table class="table" style="margin-top:10px">
    <tr><th>Test</th><th>Rate</th><th>Qty</th><th>Amount</th></tr>
    <?php while($it=$items->fetch_assoc()): ?>
      <tr>
        <td><?= e($it['test_name']) ?></td>
        <td>Rs <?= money($it['rate']) ?></td>
        <td><?= e($it['qty']) ?></td>
        <td>Rs <?= money($it['amount']) ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
  <div class="no-print" style="margin-top:8px">
    <a class="btn" href="<?= BASE_URL ?>/print_receipt.php?id=<?= e($id) ?>" target="_blank">Print 80mm (2 Copies)</a>
  </div>
</div>
<?php include __DIR__.'/app/layout_footer.php'; ?>
