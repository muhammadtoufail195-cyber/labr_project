<?php
mysqli_report(MYSQLI_REPORT_OFF);

require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid access.');
}

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$patient_name = isset($_POST['patient_name']) ? trim($_POST['patient_name']) : '';
$age = isset($_POST['age']) ? (int)$_POST['age'] : 0;
$gender = isset($_POST['gender']) ? $_POST['gender'] : 'Male';
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$doctor_id = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;

$discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
$paid = isset($_POST['paid']) ? (float)$_POST['paid'] : 0;
$items_json = isset($_POST['items_json']) ? $_POST['items_json'] : '';
$items = json_decode($items_json, true) ?: [];

if (empty($items)) {
    die('Error: No tests selected.');
}

/* 1. Insert Patient */
if (!$patient_id) {
    if (empty($patient_name)) die('Error: Patient name required.');
    $mr_no = 'MR-' . date('Ymd') . '-' . rand(100, 999);
    $stmtP = $conn->prepare("INSERT INTO patients (mr_no, name, age, gender, mobile) VALUES (?, ?, ?, ?, ?)");
    if ($stmtP) {
        $stmtP->bind_param("ssiss", $mr_no, $patient_name, $age, $gender, $mobile);
        $stmtP->execute();
        $patient_id = $conn->insert_id;
    }
}

/* 2. Calculate Totals */
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += ((float)($it['rate'] ?? 0) * (int)($it['qty'] ?? 1));
}
$total = max(0, $subtotal - $discount);

/* 3. Insert Receipt */
$sql = "INSERT INTO receipts (patient_id, doctor_id, subtotal, discount, paid, total) VALUES ($patient_id, $doctor_id, $subtotal, $discount, $paid, $total)";
if (!$conn->query($sql)) {
    $conn->query("INSERT INTO receipts (patient_id, total) VALUES ($patient_id, $total)");
}
$receipt_id = $conn->insert_id;

/* 4. Insert Items */
foreach ($items as $it) {
    $t_id = (int)($it['id'] ?? 0);
    $t_name = $conn->real_escape_string($it['name'] ?? 'Test');
    $rate = (float)($it['rate'] ?? 0);
    $qty = (int)($it['qty'] ?? 1);
    $amt = $rate * $qty;
    $conn->query("INSERT INTO receipt_items (receipt_id, test_id, test_name, rate, qty, amount) VALUES ($receipt_id, $t_id, '$t_name', $rate, $qty, $amt)");
}

/* 5. Direct Redirect to Reports Summary Page */
if (file_exists(__DIR__ . '/reports_summary.php')) {
    header("Location: reports_summary.php?ok=saved");
} else {
    header("Location: index.php?ok=saved");
}
exit;
