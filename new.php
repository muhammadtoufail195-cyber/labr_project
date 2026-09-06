<?php
// new.php (styled login with right-side image) — robust password handling

require __DIR__.'/config.php';
require __DIR__.'/app/helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// If already logged in, go home
if (!empty($_SESSION['user_id']) || !empty($_SESSION['user']['id'])) {
  header('Location: '.BASE_URL.'/index.php'); exit;
}

/* ------- Fetch Lab Name (optional) ------- */
$lab_name = 'Lab Management System';
if (isset($conn)) {
  $res = $conn->query("SELECT name FROM lab_profile LIMIT 1");
  if ($res && $res->num_rows) { $lab_name = $res->fetch_assoc()['name']; }
}

/* ------- Helpers for password formats ------- */
function users_has_column(mysqli $conn, string $col): bool {
  $res = $conn->query("SHOW COLUMNS FROM users LIKE '".$conn->real_escape_string($col)."'");
  return $res && $res->num_rows > 0;
}
function resolve_pwd_column(mysqli $conn): string {
  foreach (['password_hash','password','pass','pwd'] as $c) {
    if (users_has_column($conn, $c)) return $c;
  }
  // fallback: assume 'password'
  return 'password';
}
function looks_like_modern_hash(string $v): bool {
  return str_starts_with($v, '$2y$') || str_starts_with($v, '$2a$') || str_starts_with($v, '$2b$') || str_starts_with($v, '$argon2');
}
function looks_like_md5_hex(string $v): bool {
  return (bool)preg_match('/^[a-f0-9]{32}$/i', $v);
}

/* ------- Handle Login ------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim(post('email'));
  $pass  = (string)post('password'); // do not trim passwords

  $pwdCol = resolve_pwd_column($conn);
  // Select id, name, and the detected password column as 'pwd'
  $sql = "SELECT id, name, `$pwdCol` AS pwd FROM users WHERE email=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    $stored = (string)($row['pwd'] ?? '');

    $ok = false;
    if ($stored !== '') {
      if (looks_like_modern_hash($stored)) {
        $ok = password_verify($pass, $stored);
      } elseif (looks_like_md5_hex($stored)) {
        $ok = hash_equals(strtolower($stored), strtolower(md5($pass)));
      } else {
        // plain (legacy)
        $ok = hash_equals(rtrim($stored), $pass);
      }
    }

    if ($ok) {
      // set both styles so the rest of the app is happy
      $_SESSION['user_id']   = (int)$row['id'];
      $_SESSION['user_name'] = (string)$row['name'];
      $_SESSION['user']      = ['id'=>(int)$row['id'], 'name'=>(string)$row['name'], 'email'=>$email];

      header('Location: '.BASE_URL.'/index.php'); exit;
    }
  }
  $error = '❌ Invalid email or password!';
}

// Image path like old.php
define('BACKGROUND_IMAGE', 'img/1w.jpg');  // adjust if your image is elsewhere
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login — <?= e($lab_name) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

<style>
  /* ---------- Layout ---------- */
  html, body { height: 100%; }
  body { margin:0; font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif; background:#f5f7fb; }

  .main-container{
    display:flex; height:100vh; width:100%;
    background:#f5f7fb;
  }
  .login-left{
    width:100%; max-width:520px; background:#fff;
    padding:40px 38px; display:flex; flex-direction:column; justify-content:center;
    box-shadow: 5px 0 20px rgba(0,0,0,.05);
  }
  .login-right{
    flex:1; background-image:url('<?= e(BACKGROUND_IMAGE) ?>');
    background-size:cover; background-position:center; min-height:320px;
    filter: saturate(1.05) contrast(1.02);
  }

  /* ---------- Branding ---------- */
  .brand{
    display:flex; align-items:center; gap:12px; justify-content:center; margin-bottom:6px;
  }
  .brand-badge{
    width:46px; height:46px; border-radius:50%; display:grid; place-items:center;
    background: radial-gradient(closest-side, #22c55e 0%, #10b981 60%, #0c7a57 100%);
    color:#fff; font-weight:900; letter-spacing:.6px; box-shadow:0 6px 16px rgba(16,185,129,.35);
  }
  .brand-name{ font-weight:800; color:#0c1421; }

  .login-title{
    text-align:center; font-weight:800; font-size:28px; letter-spacing:.6px; margin:2px 0 22px 0;
    color:#0c1421;
  }
  .muted{ color:#6b7280; }

  /* ---------- Form ---------- */
  label{ font-weight:600; color:#0f172a; margin-bottom:6px; }
  .form-control{
    border-radius:12px; padding:10px 12px; border:1px solid #d8dee6; background:#fff;
  }
  .form-control:focus{
    border-color:#22c55e; box-shadow:0 0 0 4px rgba(34,197,94,.15);
  }
  .btn-primary{
    background:linear-gradient(90deg, #22c55e, #16a34a);
    border:none; border-radius:999px; padding:11px 14px; font-weight:700; letter-spacing:.3px;
    box-shadow:0 10px 18px rgba(34,197,94,.25);
  }
  .btn-primary:hover{ filter:brightness(.98); box-shadow:0 12px 22px rgba(34,197,94,.35); }

  .divider{ height:1px; background:#eef1f5; margin:16px 0 20px; }

  .alert{ border-radius:10px; font-size:14px; }

  /* ---------- Responsive ---------- */
  @media (max-width: 900px){
    .login-left{ max-width:100%; width:100%; }
    .login-right{ display:none; }
  }
</style>
</head>
<body>

<div class="main-container">
  <!-- Left: Login Form -->
  <div class="login-left">
    <div class="brand">
      <div class="brand-badge">W</div>
      <div>
        <div class="brand-name"><?= e($lab_name) ?></div>
        <div class="muted" style="font-size:12px">Lab Management — Secure Access</div>
      </div>
    </div>
    <div class="login-title"><i class="fas fa-lock text-success me-2"></i>Login</div>

    <?php if (!empty($_GET['logged_out'])): ?>
      <div class="alert alert-success">✅ You have been logged out.</div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="mb-3">
        <label for="email">📧 Email</label>
        <input id="email" type="email" name="email" class="form-control" required placeholder="you@lab.com" autocomplete="username">
      </div>

      <div class="mb-2">
        <label for="password">🔑 Password</label>
        <input id="password" type="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary w-100 mt-2">
        <i class="fas fa-sign-in-alt me-2"></i> Sign in
      </button>
    </form>

    <div class="divider"></div>
    <div class="text-center muted" style="font-size:12px">
      © <?= date('Y') ?> <?= e($lab_name) ?>. All rights reserved.
    </div>
  </div>

  <!-- Right: Full-height background image -->
  <div class="login-right" role="img" aria-label="Laboratory illustration background"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
