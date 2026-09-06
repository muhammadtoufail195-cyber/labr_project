<?php
// categories.php
$page_title = 'Test Categories';
require __DIR__ . '/config.php';
require_login();
require __DIR__ . '/app/helpers.php'; // expects helpers: post(), e()

/* -----------------------------------
   DELETE (CSRF سے بچاؤ کے لیے POST میں تبدیل کیا گیا)
   ----------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int) post('id');
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM test_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: categories.php?ok=deleted");
    exit;
}

/* -----------------------------------
   CREATE / UPDATE (ایڈ اور ایڈٹ کے لیے)
   ----------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int) (post('id') ?? 0);
    $name = trim((string) post('name'));

    if ($name === '') {
        header('Location: categories.php?err=' . urlencode('Name is required'));
        exit;
    }

    if ($id > 0) {
        // UPDATE
        $stmt = $conn->prepare('UPDATE test_categories SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $name, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // INSERT
        $stmt = $conn->prepare('INSERT INTO test_categories (name) VALUES (?)');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: categories.php?ok=1');
    exit;
}

/* -----------------------------------
   SEARCH (تلاش کرنے کا محفوظ طریقہ)
   ----------------------------------- */
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT id, name FROM test_categories WHERE name LIKE ? ORDER BY id DESC");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT id, name FROM test_categories ORDER BY id DESC");
}

include __DIR__ . '/app/layout_header.php';
?>

