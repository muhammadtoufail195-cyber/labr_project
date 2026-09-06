<?php
$page_title = 'New Receipt (Manual Patient)';
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

// Safe query for doctors
$doctors_res = $conn->query("SELECT * FROM doctors ORDER BY id DESC");
$doctors = $doctors_res ? $doctors_res->fetch_all(MYSQLI_ASSOC) : [];

// Safe query for tests
$tests_res = $conn->query("SELECT * FROM tests ORDER BY id DESC");
$tests = $tests_res ? $tests_res->fetch_all(MYSQLI_ASSOC) : [];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $age          = trim($_POST['age'] ?? '');
    $age_unit     = trim($_POST['age_unit'] ?? 'Years');
    $gender       = trim($_POST['gender'] ?? 'Male');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $doctor_id    = (int)($_POST['doctor_id'] ?? 0);
    
    $discount     = (float)($_POST['discount'] ?? 0);
    $paid         = (float)($_POST['paid'] ?? 0);
    $selected_tests = $_POST['tests'] ?? [];

    if (empty($patient_name)) {
        $error = 'Please enter Patient Full Name!';
    } elseif (empty($selected_tests)) {
        $error = 'Please select at least one test!';
    } else {
        // 1. Insert/Create Patient dynamically
        $mr_no = 'MR-' . date('YmdHis');
        $full_age = $age ? ($age . ' ' . $age_unit) : '';

        $p_stmt = $conn->prepare("INSERT INTO patients (name, age, gender, phone, address, mr_no, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($p_stmt) {
            $p_stmt->bind_param("ssssss", $patient_name, $full_age, $gender, $phone, $address, $mr_no);
            $p_stmt->execute();
            $patient_id = $p_stmt->insert_id;
        } else {
            // Fallback for custom patient schemas
            $p_res = $conn->query("INSERT INTO patients (name) VALUES ('" . $conn->real_escape_string($patient_name) . "')");
            $patient_id = $conn->insert_id;
        }

        if ($patient_id <= 0) {
            $error = 'Failed to create patient record.';
        } else {
            // 2. Calculate totals
            $subtotal = 0;
            $items_to_insert = [];

            foreach ($selected_tests as $t_id => $item) {
                $qty  = max(1, (int)($item['qty'] ?? 1));
                $rate = (float)($item['rate'] ?? 0);
                $amt  = $qty * $rate;
                $subtotal += $amt;
                $items_to_insert[] = ['test_id' => (int)$t_id, 'qty' => $qty, 'rate' => $rate, 'amount' => $amt];
            }

            $grand_total = max(0, $subtotal - $discount);

            // 3. Insert Receipt safely
            $rec_cols_res = $conn->query("SHOW COLUMNS FROM receipts");
            $rec_cols = [];
            if ($rec_cols_res) {
                while ($c = $rec_cols_res->fetch_assoc()) {
                    $rec_cols[] = $c['Field'];
                }
            }

            $fields = []; $params = []; $types = "";

            if (in_array('patient_id', $rec_cols)) { $fields[] = 'patient_id'; $params[] = $patient_id; $types .= "i"; }
            if (in_array('doctor_id', $rec_cols)) { $fields[] = 'doctor_id'; $params[] = $doctor_id; $types .= "i"; }
            if (in_array('subtotal', $rec_cols)) { $fields[] = 'subtotal'; $params[] = $subtotal; $types .= "d"; }
            if (in_array('discount', $rec_cols)) { $fields[] = 'discount'; $params[] = $discount; $types .= "d"; }

            if (in_array('grand_total', $rec_cols)) { $fields[] = 'grand_total'; $params[] = $grand_total; $types .= "d"; }
            elseif (in_array('total', $rec_cols)) { $fields[] = 'total'; $params[] = $grand_total; $types .= "d"; }

            if (in_array('paid_amount', $rec_cols)) { $fields[] = 'paid_amount'; $params[] = $paid; $types .= "d"; }
            elseif (in_array('paid', $rec_cols)) { $fields[] = 'paid'; $params[] = $paid; $types .= "d"; }

            if (in_array('created_at', $rec_cols)) { $fields[] = 'created_at'; $params[] = date('Y-m-d H:i:s'); $types .= "s"; }

            $sql = "INSERT INTO receipts (" . implode(', ', $fields) . ") VALUES (" . implode(', ', array_fill(0, count($fields), '?')) . ")";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $receipt_id = $stmt->insert_id;

                    // 4. Insert Receipt Items
                    $rec_item_cols_res = $conn->query("SHOW COLUMNS FROM receipt_items");
                    $rec_item_cols = [];
                    if ($rec_item_cols_res) {
                        while ($ic = $rec_item_cols_res->fetch_assoc()) { $rec_item_cols[] = $ic['Field']; }
                    }

                    $qty_col = in_array('quantity', $rec_item_cols) ? 'quantity' : (in_array('qty', $rec_item_cols) ? 'qty' : '');
                    $price_col = in_array('price', $rec_item_cols) ? 'price' : (in_array('rate', $rec_item_cols) ? 'rate' : '');
                    $subtotal_col = in_array('subtotal', $rec_item_cols) ? 'subtotal' : (in_array('amount', $rec_item_cols) ? 'amount' : '');

                    $i_fields = ['receipt_id', 'test_id'];
                    if ($qty_col) $i_fields[] = $qty_col;
                    if ($price_col) $i_fields[] = $price_col;
                    if ($subtotal_col) $i_fields[] = $subtotal_col;

                    $item_sql = "INSERT INTO receipt_items (" . implode(', ', $i_fields) . ") VALUES (" . implode(', ', array_fill(0, count($i_fields), '?')) . ")";
                    $item_stmt = $conn->prepare($item_sql);

                    foreach ($items_to_insert as $it) {
                        if ($item_stmt) {
                            $i_params = [$receipt_id, $it['test_id']];
                            $i_types  = "ii";
                            if ($qty_col) { $i_params[] = $it['qty']; $i_types .= "i"; }
                            if ($price_col) { $i_params[] = $it['rate']; $i_types .= "d"; }
                            if ($subtotal_col) { $i_params[] = $it['amount']; $i_types .= "d"; }

                            $item_stmt->bind_param($i_types, ...$i_params);
                            $item_stmt->execute();
                        }
                    }

                    header("Location: receipt.php?id=" . $receipt_id);
                    exit;
                } else {
                    $error = "Database Error: " . $conn->error;
                }
            } else {
                $error = "Prepare Error: " . $conn->error;
            }
        }
    }
}

include __DIR__.'/app/layout_header.php';
?>

<style>
.manual-card { background: #faf9f6; padding: 25px; border-radius: 8px; font-family: sans-serif; color: #4b5563; }
.manual-title { font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 20px; display: flex; align-items: center; gap: 6px; }
.grid-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; }
.form-group { flex: 1; min-width: 150px; }
.form-group label { display: block; font-size: 12px; font-weight: 500; color: #6b7280; margin-bottom: 5px; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #fff; box-sizing: border-box; outline: none; }
.form-control:focus { border-color: #3b82f6; }

.section-label { font-size: 13px; font-weight: 600; color: #4b5563; margin-top: 25px; margin-bottom: 10px; }

.test-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #e5e7eb; }
.test-table th { font-size: 12px; color: #6b7280; text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; font-weight: 500; }
.test-table td { padding: 10px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }

.summary-container { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 25px; gap: 20px; }
.inputs-left { display: flex; gap: 15px; flex: 1; }
.totals-right { text-align: right; min-width: 200px; font-size: 13px; line-height: 1.8; color: #4b5563; }

.btn-submit { width: 100%; background: #3b82f6; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 25px; display: flex; justify-content: center; align-items: center; gap: 8px; }
.btn-submit:hover { background: #2563eb; }

.alert-err { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:10px; border-radius:6px; font-size:13px; margin-bottom:15px; }
</style>

<div class="manual-card">
  <div class="manual-title">📋 New Receipt (Manual Patient)</div>

  <?php if ($error): ?><div class="alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" id="manualReceiptForm">
    <!-- Patient Info Row -->
    <div class="grid-row">
      <div class="form-group" style="flex:2;">
        <label>Full Name *</label>
        <input type="text" name="patient_name" class="form-control" placeholder="e.g. John Doe" required>
      </div>

      <div class="form-group">
        <label>Age</label>
        <input type="text" name="age" class="form-control" placeholder="e.g. 25">
      </div>

      <div class="form-group">
        <label>Unit</label>
        <select name="age_unit" class="form-control">
          <option value="Years">Years</option>
          <option value="Months">Months</option>
          <option value="Days">Days</option>
        </select>
      </div>

      <div class="form-group">
        <label>Gender</label>
        <select name="gender" class="form-control">
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="form-group" style="flex:1.5;">
        <label>Phone (optional)</label>
        <input type="text" name="phone" class="form-control" placeholder="03xx xxxxxx">
      </div>
    </div>

    <!-- Address Row -->
    <div class="grid-row">
      <div class="form-group" style="flex:1;">
        <label>Address (optional)</label>
        <input type="text" name="address" class="form-control" placeholder="Street / City">
      </div>
    </div>

    <!-- Referred By Doctor -->
    <div class="grid-row">
      <div class="form-group" style="flex:1;">
        <label>Referred by (Doctor)</label>
        <select name="doctor_id" class="form-control">
          <option value="">-- Select doctor (optional) --</option>
          <?php foreach ($doctors as $d): ?>
            <?php $d_name = $d['name'] ?? $d['doctor_name'] ?? 'Doctor'; ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Tests Section -->
    <div class="section-label">Tests</div>
    
    <div class="grid-row">
      <div class="form-group" style="flex:1;">
        <select id="testSelect" class="form-control" onchange="addTestRow()">
          <option value="">Search test name...</option>
          <?php foreach ($tests as $t): ?>
            <?php 
              $t_name = $t['test_name'] ?? $t['name'] ?? 'Test';
              $t_rate = $t['price'] ?? $t['rate'] ?? $t['fee'] ?? 0;
            ?>
            <option value="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t_name) ?>" data-rate="<?= $t_rate ?>">
              <?= htmlspecialchars($t_name) ?> (Rs <?= number_format($t_rate, 2) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <table class="test-table">
      <thead>
        <tr>
          <th>Test</th>
          <th style="width:120px;">Rate</th>
          <th style="width:120px;">Amount</th>
          <th style="width:50px;"></th>
        </tr>
      </thead>
      <tbody id="selectedTestsBody">
        <tr id="noTestRow"><td colspan="4" style="text-align:center; color:#9ca3af; padding:15px;">No tests added yet.</td></tr>
      </tbody>
    </table>

    <!-- Summary & Totals -->
    <div class="summary-container">
      <div class="inputs-left">
        <div class="form-group">
          <label>Discount (Rs)</label>
          <input type="number" name="discount" id="discountInput" value="0" min="0" step="any" class="form-control" oninput="calculateSummary()">
        </div>
        <div class="form-group">
          <label>Amount Paid (Rs)</label>
          <input type="number" name="paid" id="paidInput" value="0" min="0" step="any" class="form-control" oninput="calculateSummary()">
        </div>
      </div>

      <div class="totals-right">
        <div>Subtotal: <span style="font-weight:600;">Rs <span id="subtotalTxt">0.00</span></span></div>
        <div style="font-weight:700; color:#111827; margin-top:4px;">Grand Total: Rs <span id="grandTotalTxt">0.00</span></div>
        <div style="color:#ef4444; font-weight:600;">Remaining: Rs <span id="remainingTxt">0.00</span></div>
      </div>
    </div>

    <button type="submit" class="btn-submit">
      🖨️ Save & Print
    </button>
  </form>
</div>

<script>
let addedTests = {};

function addTestRow() {
    const select = document.getElementById('testSelect');
    const selectedOpt = select.options[select.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) return;

    const testId = selectedOpt.value;
    const testName = selectedOpt.getAttribute('data-name');
    const rate = parseFloat(selectedOpt.getAttribute('data-rate')) || 0;

    if (!addedTests[testId]) {
        addedTests[testId] = { name: testName, rate: rate, qty: 1 };
    }

    renderTests();
    select.value = '';
}

function removeTest(testId) {
    delete addedTests[testId];
    renderTests();
}

function renderTests() {
    const tbody = document.getElementById('selectedTestsBody');
    tbody.innerHTML = '';
    const keys = Object.keys(addedTests);

    if (keys.length === 0) {
        tbody.innerHTML = '<tr id="noTestRow"><td colspan="4" style="text-align:center; color:#9ca3af; padding:15px;">No tests added yet.</td></tr>';
        calculateSummary();
        return;
    }

    keys.forEach(id => {
        const item = addedTests[id];
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><b>${item.name}</b><input type="hidden" name="tests[${id}][rate]" value="${item.rate}"><input type="hidden" name="tests[${id}][qty]" value="1"></td>
            <td>Rs ${item.rate.toFixed(2)}</td>
            <td><b>Rs ${item.rate.toFixed(2)}</b></td>
            <td><button type="button" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:bold;" onclick="removeTest(${id})">✕</button></td>
        `;
        tbody.appendChild(tr);
    });

    calculateSummary();
}

function calculateSummary() {
    let subtotal = 0;
    Object.keys(addedTests).forEach(id => {
        subtotal += addedTests[id].rate;
    });

    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const paid = parseFloat(document.getElementById('paidInput').value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);
    const remaining = Math.max(0, grandTotal - paid);

    document.getElementById('subtotalTxt').innerText = subtotal.toFixed(2);
    document.getElementById('grandTotalTxt').innerText = grandTotal.toFixed(2);
    document.getElementById('remainingTxt').innerText = remaining.toFixed(2);
}
</script>

<?php include __DIR__.'/app/layout_footer.php'; ?>
