<?php
// patients_search.php
require __DIR__.'/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$rows = [];

if ($q !== '') {
    $like = '%'.$q.'%';
    $stmt = $conn->prepare(
        'SELECT id, name, mr_no, mobile, gender, age
         FROM patients
         WHERE name LIKE ? OR mobile LIKE ?
         ORDER BY id DESC
         LIMIT 200'
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query(
        'SELECT id, name, mr_no, mobile, gender, age
         FROM patients
         ORDER BY id DESC
         LIMIT 200'
    );
}

while ($r = $res->fetch_assoc()) { $rows[] = $r; }

echo json_encode(['ok'=>true, 'rows'=>$rows], JSON_UNESCAPED_UNICODE);
