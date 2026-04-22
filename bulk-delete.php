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
}

header("Location: index.php?deleted=1");
exit;
