<?php
require 'db.php';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $course = trim($_POST['course'] ?? '');

    if ($name && $email && $course) {
        try {
            $sql  = "INSERT INTO students (name, email, course) VALUES (:name, :email, :course)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['name' => $name, 'email' => $email, 'course' => $course]);
            $success = true;
        } catch (PDOException $e) {
            $error = str_contains($e->getMessage(), 'Duplicate entry')
                ? 'That email address is already registered.'
                : 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'All fields are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Student – School DB</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">

  <header class="site-header">
    <div class="logo">school<span>.</span>db</div>
    <a href="index.php" class="nav-link">← Back to list</a>
  </header>

  <h1 class="page-title">Add <span>Student</span></h1>
  <p class="page-sub">Enrol a new student into the system.</p>

  <?php if ($success): ?>
    <div class="alert alert-success">
      Student added successfully! <a href="index.php" style="color:inherit;text-decoration:underline">View all →</a>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" class="form-grid">

      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input class="form-input" type="text" id="name" name="name"
               placeholder="e.g. Maria Santos"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email"
               placeholder="e.g. maria@school.edu"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="course">Course</label>
        <input class="form-input" type="text" id="course" name="course"
               placeholder="e.g. Computer Science"
               value="<?= htmlspecialchars($_POST['course'] ?? '') ?>" required>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-accent" type="submit">+ Add Student</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
      </div>

    </form>
  </div>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?></footer>
</div>
</body>
</html>