<?php
$page_title = 'Tests';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

/* ==== PIN security (server-side) ==== */
$PIN = '0345';
function valid_pin($p){ return isset($p) && $p === '0345'; }

/* ==== Delete (requires PIN) ==== */
if (isset($_GET['delete'])) {
  $id  = (int) $_GET['delete'];
  $pin = $_GET['pin'] ?? '';
  if (!valid_pin($pin)) {
    header("Location: tests.php?error=pin"); exit;
  }
  $stmt = $conn->prepare("DELETE FROM tests WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  header("Location: tests.php?ok=deleted"); exit;
}

/* ==== Save / Update ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id   = post('id');
  $name = trim(post('name'));
  $rate = (float)post('rate');
  $cat  = (int)post('category_id');

  // For updates, enforce PIN; for new adds, no PIN required
  if ($id) {
    $pin = post('pin');
    if (!valid_pin($pin)) {
      header('Location: tests.php?error=pin'); exit;
    }
    $stmt = $conn->prepare('UPDATE tests SET name=?, rate=?, category_id=? WHERE id=?');
    $stmt->bind_param('sdii', $name, $rate, $cat, $id);
    $stmt->execute();
  } else {
    $stmt = $conn->prepare('INSERT INTO tests (name, rate, category_id) VALUES (?, ?, ?)');
    $stmt->bind_param('sdi', $name, $rate, $cat);
    $stmt->execute();
  }
  header('Location: tests.php?ok=saved'); exit;
}

/* ==== Edit (requires PIN) ==== */
$edit = null;
if (isset($_GET['edit'])) {
  $id  = (int)$_GET['edit'];
  $pin = $_GET['pin'] ?? '';
  if (!valid_pin($pin)) {
    header("Location: tests.php?error=pin"); exit;
  }
  $edit = $conn->query('SELECT * FROM tests WHERE id=' . $id)->fetch_assoc();
}

/* ==== Data ==== */
$cats = $conn->query('SELECT * FROM test_categories ORDER BY name ASC');

/* ==== Search ==== */
$search = trim($_GET['search'] ?? '');
$search_sql = $search ? "WHERE t.name LIKE '%" . $conn->real_escape_string($search) . "%'" : '';
$res = $conn->query("SELECT t.*, c.name cat
                     FROM tests t
                     LEFT JOIN test_categories c ON c.id=t.category_id
                     $search_sql
                     ORDER BY t.id DESC");

include __DIR__.'/app/layout_header.php';
?>

<style>
/* === Button-31 Base === */
.button-31 {
  background-color: #222;
  border-radius: 4px;
  border-style: none;
  box-sizing: border-box;
  color: #fff;
  cursor: pointer;
  display: inline-block;
  font-family: "Inter","Farfetch Basis","Helvetica Neue",Arial,sans-serif;
  font-size: 15px;
  font-weight: 600;
  line-height: 1.5;
  margin: 2px 0;
  min-height: 40px;
  padding: 9px 18px 8px;
  text-align: center;
  transition: all 0.2s ease-in-out;
  user-select: none;
  width: auto;
}
.button-31:hover,.button-31:focus { opacity:.85; transform: translateY(-1px); }
.button-green { background-color:#16a34a; } .button-green:hover { background:#15803d; }
.button-blue  { background-color:#2563eb; } .button-blue:hover  { background:#1d4ed8; }
.button-red   { background-color:#dc2626; } .button-red:hover   { background:#b91c1c; }
.button-gray  { background-color:#4b5563; } .button-gray:hover  { background:#374151; }

/* Form and Table Styling */
.card h3 { font-weight: 600; color: #111827; margin-bottom: 10px; }
form label { font-size: 14px; color: #374151; font-weight: 500; margin-bottom: 4px; display:block; }
.input {
  width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); outline:none; }

/* Search row */
.search-bar { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.search-bar .input { flex: 1; }

/* Table */
.table { width:100%; border-collapse:separate; border-spacing:0; }
.table th, .table td { padding:12px 14px; }
.table th { background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:600; color:#111827; text-align:left; }
.table tr + tr td { border-top:1px solid #f1f5f9; }
.table tbody tr:hover td { background:#f9fafb; }
.col-rate { text-align:right; white-space:nowrap; color:#111827; }
.col-actions { text-align:right; }
.actions { display:inline-flex; gap:8px; align-items:center; justify-content:flex-end; }

/* Banner */
.banner {
  border:1px solid #fecaca; background:#fef2f2; color:#991b1b;
  padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:14px;
}
.banner.ok {
  border-color:#bbf7d0; background:#f0fdf4; color:#166534;
}
</style>

<div class="row">
  <!-- LEFT: Add/Edit Test -->
  <div class="col-4">
    <div class="card">
      <?php if (isset($_GET['error']) && $_GET['error']==='pin'): ?>
        <div class="banner">PIN not valid.</div>
      <?php elseif (isset($_GET['ok']) && $_GET['ok']==='deleted'): ?>
        <div class="banner ok">Test deleted successfully.</div>
      <?php elseif (isset($_GET['ok']) && $_GET['ok']==='saved'): ?>
        <div class="banner ok">Saved successfully.</div>
      <?php endif; ?>

      <h3><?= $edit ? 'Edit' : 'Add' ?> Test</h3>
      <form method="post" action="tests.php" autocomplete="off">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

        <label>Test Name</label>
        <input class="input" name="name" required value="<?= e($edit['name'] ?? '') ?>">

        <label style="margin-top:8px">Rate</label>
        <input class="input" name="rate" type="number" step="0.01" required value="<?= e($edit['rate'] ?? '') ?>">

        <label style="margin-top:8px">Category</label>
        <select class="input" name="category_id">
          <?php while($c = $cats->fetch_assoc()):
            $sel = ($edit['category_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>
            <option value="<?= e($c['id']) ?>" <?= $sel ?>><?= e($c['name']) ?></option>
          <?php endwhile; ?>
        </select>

        <?php if ($edit): ?>
          <!-- Require PIN when updating an existing test -->
          <label style="margin-top:8px">PIN (required to update)</label>
          <input class="input" name="pin" type="password" inputmode="numeric" pattern="\d*" placeholder="Enter PIN" required>
        <?php endif; ?>

        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
          <button class="button-31 button-green"><?= $edit ? 'Update' : 'Save' ?></button>
          <?php if ($edit): ?>
            <a href="tests.php" class="button-31 button-gray">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- RIGHT: List Tests -->
  <div class="col-8">
    <div class="card">
      <h3>Tests</h3>

      <!-- Search -->
      <form method="get" action="tests.php" class="search-bar">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="🔍 Search test by name…" class="input">
        <button class="button-31 button-blue">Search</button>
        <?php if ($search): ?>
          <a href="tests.php" class="button-31 button-gray">Clear</a>
        <?php endif; ?>
      </form>

      <table class="table" id="testsTable">
        <thead>
          <tr>
            <th style="width:80px">ID</th>
            <th>Name</th>
            <th class="col-rate">Rate</th>
            <th>Category</th>
            <th class="col-actions" style="width:220px">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php while($t = $res->fetch_assoc()): ?>
          <tr>
            <td><?= e($t['id']) ?></td>
            <td><?= e($t['name']) ?></td>
            <td class="col-rate">Rs <?= money($t['rate']) ?></td>
            <td><?= e($t['cat']) ?></td>
            <td class="col-actions">
              <div class="actions">
                <!-- Edit with PIN -->
                <a class="button-31 button-blue" href="#"
                   onclick="return editWithPin(<?= (int)$t['id'] ?>)">Edit</a>

                <!-- Delete with PIN -->
                <a class="button-31 button-red" href="#"
                   onclick="return deleteWithPin(<?= (int)$t['id'] ?>,'<?= e(addslashes($t['name'])) ?>')">Delete</a>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const PIN_CONST = '<?= $PIN ?>';

// Ask for PIN, compare client-side (quick UX), then include it in URL/query.
// Server still verifies too, so this is just for convenience.
function promptPin(msg='Enter PIN'){
  const pin = prompt(msg);
  if (pin === null) return null; // canceled
  return pin.trim();
}

function editWithPin(id){
  const pin = promptPin('Enter PIN to edit');
  if (pin === null) return false;
  if (!pin) { alert('PIN not valid'); return false; }
  // Optional quick check to avoid a round trip (server still enforces)
  if (pin !== PIN_CONST) { alert('PIN not valid'); return false; }
  location.href = 'tests.php?edit=' + id + '&pin=' + encodeURIComponent(pin);
  return false;
}

function deleteWithPin(id, name){
  if (!confirm('Delete “‘ + name + ‘” ?')) return false;
  const pin = promptPin('Enter PIN to delete');
  if (pin === null) return false;
  if (!pin) { alert('PIN not valid'); return false; }
  if (pin !== PIN_CONST) { alert('PIN not valid'); return false; }
  location.href = 'tests.php?delete=' + id + '&pin=' + encodeURIComponent(pin);
  return false;
}
</script>

<?php include __DIR__.'/app/layout_footer.php'; ?>
