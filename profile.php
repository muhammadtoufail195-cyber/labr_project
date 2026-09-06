<?php
$page_title = 'Profile';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

/* ---- one-time auto-migrate for new columns ---- */
$needCols = [];
$cols = $conn->query("SHOW COLUMNS FROM lab_profile");
$have = [];
while ($c = $cols->fetch_assoc()) $have[$c['Field']] = true;
if (!isset($have['email']))   $needCols[] = "ADD COLUMN email VARCHAR(255) NULL AFTER phone";
if (!isset($have['whatsapp']))$needCols[] = "ADD COLUMN whatsapp VARCHAR(50) NULL AFTER email";
if ($needCols) { $conn->query("ALTER TABLE lab_profile ".implode(", ", $needCols)); }

/* ---- Save ---- */
$msg='';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  $name     = trim(post('name'));
  $phone    = trim(post('phone'));
  $email    = trim(post('email'));
  $whatsapp = trim(post('whatsapp'));
  $address  = trim(post('address'));

  $row = $conn->query('SELECT id FROM lab_profile LIMIT 1')->fetch_assoc();
  if ($row){
    $stmt = $conn->prepare('UPDATE lab_profile SET name=?, phone=?, email=?, whatsapp=?, address=? WHERE id=?');
    $stmt->bind_param('sssssi', $name, $phone, $email, $whatsapp, $address, $row['id']);
  } else {
    $stmt = $conn->prepare('INSERT INTO lab_profile(name, phone, email, whatsapp, address) VALUES(?,?,?,?,?)');
    $stmt->bind_param('sssss', $name, $phone, $email, $whatsapp, $address);
  }
  $stmt->execute();
  $msg = '✅ Profile saved.';
}
$row = $conn->query('SELECT * FROM lab_profile LIMIT 1')->fetch_assoc();

include __DIR__.'/app/layout_header.php';
?>

<style>
/* ====== shell ====== */
.profile-wrap{
  max-width: 880px; margin: 18px auto 0;
}
.card-profile{
  background:#fff; border:1px solid #e5e7eb; border-radius:16px;
  box-shadow:0 10px 24px rgba(0,0,0,.06);
  overflow:hidden;
}
.card-head{
  display:flex; align-items:center; gap:12px;
  padding:16px 18px; border-bottom:1px solid #eef2f7;
  background: linear-gradient(180deg,#ffffff 0%, #fbfdff 100%);
}
.badge-dot{
  height:12px;width:12px;border-radius:9999px;background:#22c55e;
  box-shadow:0 0 0 6px rgba(34,197,94,.2);
}
.card-head h3{ margin:0; font-weight:800; color:#0f172a; font-size:18px; }

/* ====== form ====== */
.card-body{ padding:18px; }
.grid{
  display:grid; gap:14px;
  grid-template-columns: repeat(12,minmax(0,1fr));
}
.col-6{ grid-column: span 6 / span 6; }
.col-12{ grid-column: span 12 / span 12; }

.label{ font-size:13px; font-weight:700; color:#0f172a; margin:2px 0 6px; display:block; }
.field{
  display:flex; align-items:center; gap:10px;
  border:1px solid #d1d5db; border-radius:12px; background:#fff; padding:10px 12px;
  transition:border-color .2s, box-shadow .2s;
}
.field:focus-within{ border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
.field i{ color:#64748b; }
.field input, .field textarea{
  border:0; outline:0; width:100%; font-size:14px; background:transparent; color:#0f172a;
}
.field textarea{ resize:vertical; min-height:72px; }

/* helper */
.help{ font-size:12px; color:#94a3b8; margin-top:6px; }

/* buttons: reuse your system */
.button-31{
  background-color:#2563eb;border-radius:8px;border:none;box-sizing:border-box;color:#fff;cursor:pointer;
  display:inline-block;font-family:"Inter","Farfetch Basis","Helvetica Neue",Arial,sans-serif;font-size:15px;
  font-weight:700;line-height:1.5;margin:2px 0;min-height:42px;padding:10px 18px;text-align:center;
  transition:all .2s ease-in-out;user-select:none;width:auto
}
.button-31:hover,.button-31:focus{opacity:.92;transform:translateY(-1px)}
.button-green{ background:#16a34a; }
.actions{ display:flex; justify-content:flex-end; gap:10px; padding:12px 18px 18px; }

.alert-lite{
  background:#ecfeff; border:1px solid #7dd3fc; color:#0369a1;
  border-radius:12px; padding:10px 14px; margin:14px 18px 0; font-weight:600;
}

/* responsive */
@media (max-width: 840px){
  .col-6{ grid-column: span 12 / span 12; }
}
</style>

<div class="profile-wrap">
  <div class="card-profile">
    <div class="card-head">
      <span class="badge-dot"></span>
      <h3>Lab Profile</h3>
    </div>

    <?php if($msg): ?>
      <div class="alert-lite"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="card-body">
        <div class="grid">
          <div class="col-6">
            <label class="label">Lab Name</label>
            <div class="field">
              <i class="fa-solid fa-flask"></i>
              <input name="name" required value="<?= e($row['name'] ?? '') ?>" placeholder="Your Lab Name">
            </div>
          </div>

          <div class="col-6">
            <label class="label">Phone</label>
            <div class="field">
              <i class="fa-solid fa-phone"></i>
              <input name="phone" value="<?= e($row['phone'] ?? '') ?>" placeholder="e.g. 091-xxxxxxx">
            </div>
          </div>

          <div class="col-6">
            <label class="label">Email</label>
            <div class="field">
              <i class="fa-solid fa-envelope"></i>
              <input type="email" name="email" value="<?= e($row['email'] ?? '') ?>" placeholder="lab@email.com">
            </div>
            <div class="help">Used on receipts & contact sections.</div>
          </div>

          <div class="col-6">
            <label class="label">WhatsApp Number</label>
            <div class="field">
              <i class="fa-brands fa-whatsapp"></i>
              <input name="whatsapp" value="<?= e($row['whatsapp'] ?? '') ?>" placeholder="03XXXXXXXXX">
            </div>
            <div class="help">Shown for quick patient support.</div>
          </div>

          <div class="col-12">
            <label class="label">Address</label>
            <div class="field">
              <i class="fa-solid fa-location-dot"></i>
              <textarea name="address" rows="2" placeholder="Street, Area, City"><?= e($row['address'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="button-31">Save Profile</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>
