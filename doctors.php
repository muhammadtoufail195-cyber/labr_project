<?php
$page_title = 'Doctors Management';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

/* Handle Delete Request */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $conn->query("DELETE FROM doctors WHERE id = $del_id");
    header("Location: doctors.php?msg=deleted");
    exit;
}

/* Handle Add / Edit Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($name)) {
        if ($doc_id > 0) {
            $stmt = $conn->prepare("UPDATE doctors SET name=?, specialization=?, phone=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $specialization, $phone, $doc_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $specialization, $phone);
        }
        $stmt->execute();
    }
    header("Location: doctors.php?msg=saved");
    exit;
}

/* Edit Data Fetch */
$edit_doc = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM doctors WHERE id = $edit_id");
    $edit_doc = $res ? $res->fetch_assoc() : null;
}

/* Fetch All Doctors */
$doctors_res = $conn->query("SELECT * FROM doctors ORDER BY id DESC");
$doctors = $doctors_res ? $doctors_res->fetch_all(MYSQLI_ASSOC) : [];

include __DIR__.'/app/layout_header.php';
?>

<style>
.card { background:#fff; padding:20px; border-radius:10px; border:1px solid #e5e7eb; margin-bottom:20px; }
.card h3 { font-size:22px; font-weight:700; margin-bottom:16px; color:#1e293b; }
.row { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:15px; }
.col-4 { flex:1; min-width:220px; }

label { font-size:13px; font-weight:600; color:#475569; display:block; margin-bottom:5px; }
.input { width:100%; padding:10px; font-size:14px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; }
.input:focus { border-color:#2563eb; outline:none; }

.table { width:100%; border-collapse:collapse; margin-top:15px; }
.table th { background:#f8fafc; padding:12px; text-align:left; font-size:14px; color:#475569; border-bottom:2px solid #e2e8f0; }
.table td { padding:12px; border-bottom:1px solid #f1f5f9; font-size:14px; color:#334155; }

.btn { border:0; border-radius:6px; color:#fff; font-weight:600; font-size:13px; padding:8px 14px; cursor:pointer; text-decoration:none; display:inline-block; }
.btn-blue { background:#2563eb; }
.btn-amber { background:#f59e0b; }
.btn-red { background:#dc2626; }
</style>

<div class="card">
  <h3>Add Referring Refrence</h3>
  
  <form method="post" action="doctors.php" autocomplete="off">
    <input type="hidden" name="doc_id" value="<?= $edit_doc['id'] ?? 0 ?>">
    
    <div class="row">
      <div class="col-4">
        <label>Refrence Name *</label>
        <input class="input" name="name" required placeholder="Refrence Name" value="<?= htmlspecialchars($edit_doc['name'] ?? '') ?>">
      </div>
      <div class="col-4">
        <label>Specialization (optional)</label>
        <input class="input" name="specialization" placeholder="Specialization" value="<?= htmlspecialchars($edit_doc['specialization'] ?? '') ?>">
      </div>
      <div class="col-4">
        <label>Phone (optional)</label>
        <input class="input" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($edit_doc['phone'] ?? '') ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-blue">
      <?= $edit_doc ? 'Update Refrence' : 'Save Refrence' ?>
    </button>
    <?php if ($edit_doc): ?>
      <a href="doctors.php" class="btn btn-red">Cancel</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h4 style="font-size:16px; font-weight:600; color:#475569;">Existing Doctors</h4>

  <table class="table">
    <thead>
      <tr>
        <th style="width:60px;">#</th>
        <th>Name</th>
        <th>Specialization</th>
        <th>Phone</th>
        <th style="width:160px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($doctors)): ?>
        <tr><td colspan="5" style="text-align:center; color:#94a3b8;">No doctors / references added yet.</td></tr>
      <?php else: ?>
        <?php foreach ($doctors as $d): ?>
          <tr>
            <td><?= $d['id'] ?></td>
            <td><b><?= htmlspecialchars($d['name']) ?></b></td>
            <td><?= htmlspecialchars($d['specialization'] ?: '-') ?></td>
            <td><?= htmlspecialchars($d['phone'] ?: '-') ?></td>
            <td>
              <a href="doctors.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-amber" style="padding:5px 10px; font-size:12px;">✏ Edit</a>
              <a href="doctors.php?action=delete&id=<?= $d['id'] ?>" class="btn btn-red" style="padding:5px 10px; font-size:12px;" onclick="return confirm('Are you sure you want to delete this doctor?');">🗑 Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>
