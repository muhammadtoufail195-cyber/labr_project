<?php
$page_title = 'Patients List';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

$search = trim($_GET['search'] ?? '');

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        header("Location: patients.php?msg=deleted");
        exit;
    }
}

// Fetch Patients with search query
if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT * FROM patients WHERE name LIKE ? OR phone LIKE ? OR mr_no LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT * FROM patients ORDER BY id DESC");
}

$PATIENTS = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $PATIENTS[] = $row;
    }
}

include __DIR__.'/app/layout_header.php';
?>

<style>
.card { background:#fff; padding:20px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.card-title { font-size:20px; font-weight:700; color:#0f172a; margin-bottom:16px; }

.search-box { display:flex; gap:8px; margin-bottom:20px; }
.search-input { flex:1; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; outline:none; }
.search-btn { background:#2563eb; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; }

.table { width:100%; border-collapse:collapse; margin-top:10px; }
.table th { background:#f1f5f9; padding:10px; text-align:left; font-size:12px; font-weight:700; color:#475569; border-bottom:2px solid #cbd5e1; }
.table td { padding:10px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#334155; vertical-align:middle; }
.table tr:hover { background:#f8fafc; }

.btn-group { display:flex; gap:4px; flex-wrap:wrap; }
.btn-action { padding:6px 10px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:3px; }
.btn-edit { background:#2563eb; }
.btn-edit:hover { background:#1d4ed8; }
.btn-print { background:#10b981; }
.btn-print:hover { background:#059669; }
.btn-delete { background:#ef4444; }
.btn-delete:hover { background:#dc2626; }

.alert-ok { background:#f0fdf4; border:1px solid #86efac; color:#166534; padding:10px; border-radius:8px; font-size:13px; margin-bottom:15px; }
</style>

<div class="card">
  <h3 class="card-title">👥 Patients List (Total: <?= count($PATIENTS) ?>)</h3>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert-ok">Patient deleted successfully!</div>
  <?php endif; ?>

  <form method="GET" action="patients.php" class="search-box">
    <input type="text" name="search" class="search-input" placeholder="Search by name, mobile or MR.." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="search-btn">Search</button>
  </form>

  <div style="overflow-x:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>MR No</th>
          <th>Patient Name</th>
          <th>Mobile</th>
          <th>Gender / Age</th>
          <th style="min-width:180px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($PATIENTS)): ?>
          <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:30px;">No patient records found.</td></tr>
        <?php else: ?>
          <?php foreach ($PATIENTS as $p): 
            $mr    = $p['mr_no'] ?? ('#'.$p['id']);
            $name  = $p['name'] ?? 'N/A';
            $phone = $p['phone'] ?? 'N/A';
            $g     = !empty($p['gender']) ? ucfirst($p['gender']) : 'N/A';
            $age   = !empty($p['age']) ? $p['age'] . ' yrs' : '';
            $ga    = trim("$g ($age)");
          ?>
            <tr>
              <td><b><?= htmlspecialchars($mr) ?></b></td>
              <td><b><?= htmlspecialchars($name) ?></b></td>
              <td><?= htmlspecialchars($phone) ?></td>
              <td><?= htmlspecialchars($ga) ?></td>
              <td>
                <div class="btn-group">
                  <a href="edit_patient.php?id=<?= $p['id'] ?>" class="btn-action btn-edit">Edit</a>
                  <a href="print_patient_card.php?id=<?= $p['id'] ?>" class="btn-action btn-print" target="_blank">🖨️ Print</a>
                  <a href="patients.php?action=delete&id=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this patient?')">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>
