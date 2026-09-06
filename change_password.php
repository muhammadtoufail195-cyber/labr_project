<?php
// change_password.php — simplified: no current password required
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = 'Change Password';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== Determine user and password column ===== */
$user_id = (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: '.BASE_URL.'/login.php');
    exit;
}

// Detect correct password column
$pwd_col = 'password';
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
if ($col_check && $col_check->num_rows > 0) {
    $pwd_col = 'password_hash';
}

/* ===== CSRF protection ===== */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ===== Handle password update ===== */
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh the page.';
    } else {
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (!$errors) {
            // Hash securely
            $hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Update in DB
            $stmt = $conn->prepare("UPDATE users SET `$pwd_col`=? WHERE id=?");
            $stmt->bind_param('si', $hash, $user_id);
            $stmt->execute();

            // Rotate CSRF token
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $csrf = $_SESSION['csrf'];

            $success = 'Password updated successfully.';
        }
    }
}

include __DIR__.'/app/layout_header.php';
?>

<style>
.button-31{
  background:#222;border:0;border-radius:10px;color:#fff;
  display:inline-block;font-family:"Inter","Farfetch Basis","Helvetica Neue",Arial,sans-serif;
  font-weight:600;font-size:15px;line-height:1;padding:12px 16px;
  transition:transform .2s,opacity .2s,box-shadow .2s;
  box-shadow:0 6px 16px rgba(2,6,23,.15);cursor:pointer;user-select:none
}
.button-31:hover{opacity:.9;transform:translateY(-1px)}
.button-green{background:#16a34a}.button-green:hover{background:#15803d}
.button-gray{background:#4b5563}.button-gray:hover{background:#374151}

.form-wrap{max-width:540px}
label{display:block;font-size:14px;color:#374151;font-weight:600;margin:10px 0 6px}
.input{
  width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;
  transition:border-color .2s,box-shadow .2s
}
.input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
.alert{padding:10px 12px;border-radius:10px;margin-bottom:12px;font-size:14px}
.alert.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert.success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.header-row h3{margin:0}
.help{font-size:12px;color:#6b7280;margin-top:4px}
</style>

<div class="row">
  <div class="col-6">
    <div class="card form-wrap">
      <div class="header-row">
        <h3>Change Password</h3>
        <a class="button-31 button-gray" href="<?= BASE_URL ?>/index.php">Back</a>
      </div>

      <?php if ($success): ?>
        <div class="alert success"><?= e($success) ?></div>
      <?php endif; ?>

      <?php foreach ($errors as $e): ?>
        <div class="alert error"><?= e($e) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= BASE_URL ?>/change_password.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <label>New Password</label>
        <input class="input" type="password" name="new_password" minlength="8" required>
        <div class="help">Use at least 8 characters. Mix letters, numbers, and symbols.</div>

        <label>Confirm New Password</label>
        <input class="input" type="password" name="confirm_password" minlength="8" required>

        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
          <button class="button-31 button-green">Update Password</button>
          <a class="button-31 button-gray" href="<?= BASE_URL ?>/profile.php">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__.'/app/layout_footer.php'; ?>

