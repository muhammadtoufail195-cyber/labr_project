<?php
// seed_admin.php — robust admin bootstrap
require __DIR__.'/config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ======= CONFIGURE HERE =======
$name  = 'Admin';
$email = 'admin@lab.com';
$pass  = 'admin123';
// ==============================

/* Helpers */
function hasColumn(mysqli $conn, string $table, string $column): bool {
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '".$conn->real_escape_string($column)."'");
  return $res && $res->num_rows > 0;
}
function hasUniqueOnEmail(mysqli $conn, string $table): bool {
  $res = $conn->query("SHOW INDEX FROM `$table` WHERE Column_name='email' AND Non_unique=0");
  return $res && $res->num_rows > 0;
}
function resolvePasswordColumn(mysqli $conn, string $table): string {
  foreach (['password_hash','password','pass','pwd'] as $c) {
    if (hasColumn($conn, $table, $c)) return $c;
  }
  return ''; // none found
}

try {
  // 1) Ensure users table exists (light sanity check)
  $conn->query("SELECT 1 FROM `users` LIMIT 1");

  // 2) Resolve or create password column
  $pwdCol = resolvePasswordColumn($conn, 'users');
  if ($pwdCol === '') {
    // Prefer creating password_hash if nothing exists
    $conn->query("ALTER TABLE `users` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `email`");
    $pwdCol = 'password_hash';
  }

  // 3) Ensure email has a UNIQUE index so upsert works
  if (!hasUniqueOnEmail($conn, 'users')) {
    // Best-effort add; if a duplicate exists, this will throw and we fallback later
    try {
      $conn->query("ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_email` (`email`)");
    } catch (mysqli_sql_exception $e) {
      // ignore; we'll fallback to update/insert path
    }
  }

  // 4) Prepare values
  $hash = password_hash($pass, PASSWORD_DEFAULT);

  // 5) Try upsert using ON DUPLICATE KEY (works if email is unique)
  $sql = "INSERT INTO `users` (`name`,`email`,`$pwdCol`) VALUES (?,?,?)
          ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `$pwdCol`=VALUES(`$pwdCol`)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('sss', $name, $email, $hash);
  try {
    $stmt->execute();
    echo "✅ Admin ready (upsert): {$email} / {$pass}\n";
    exit;
  } catch (mysqli_sql_exception $e) {
    // Fallback below
  }

  // 6) Fallback path (no unique index): UPDATE then INSERT if needed
  $upd = $conn->prepare("UPDATE `users` SET `name`=?, `$pwdCol`=? WHERE `email`=?");
  $upd->bind_param('sss', $name, $hash, $email);
  $upd->execute();

  if ($upd->affected_rows === 0) {
    // Insert
    $ins = $conn->prepare("INSERT INTO `users` (`name`,`email`,`$pwdCol`) VALUES (?,?,?)");
    $ins->bind_param('sss', $name, $email, $hash);
    $ins->execute();
    echo "✅ Admin created: {$email} / {$pass}\n";
  } else {
    echo "✅ Admin updated: {$email} / {$pass}\n";
  }

} catch (mysqli_sql_exception $ex) {
  http_response_code(500);
  echo "❌ Error: ".$ex->getMessage()."\n";
}
