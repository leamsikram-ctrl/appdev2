<?php
require 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    
    // Reset AUTO_INCREMENT to prevent ID gaps
    $maxId = $pdo->query("SELECT MAX(id) as max_id FROM students")->fetch()['max_id'];
    $nextId = ($maxId ?? 0) + 1;
    $pdo->exec("ALTER TABLE students AUTO_INCREMENT = $nextId");
}

header("Location: index.php?deleted=1");
exit;