<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$ids = $_POST['ids'] ?? [];
$ids = array_filter($ids, fn($id) => is_numeric($id));

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM students WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    // Reset AUTO_INCREMENT to prevent ID gaps
    $maxId = $pdo->query("SELECT MAX(id) as max_id FROM students")->fetch()['max_id'];
    $nextId = ($maxId ?? 0) + 1;
    $pdo->exec("ALTER TABLE students AUTO_INCREMENT = $nextId");
}

header("Location: index.php?deleted=1");
exit;
