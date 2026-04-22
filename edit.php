<?php
require 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $course = trim($_POST['course'] ?? '');

    if ($name && $email && $course) {
        try {
            $sql = "UPDATE students SET name = ?, email = ?, course = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$name, $email, $course, $id]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = str_contains($e->getMessage(), 'Duplicate entry')
                ? 'That email address is already taken.'
                : 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'All fields are required.';
    }
    // Re-populate with submitted values on error
    $student['name']   = $name;
    $student['email']  = $email;
    $student['course'] = $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Student – School DB</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">

  <header class="site-header">
    <div class="logo">school<span>.</span>db</div>
    <a href="index.php" class="nav-link">← Back to list</a>
  </header>

  <h1 class="page-title">Edit <span>Student</span></h1>
  <p class="page-sub">Update details for student #<?= $id ?>.</p>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" class="form-grid">

      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input class="form-input" type="text" id="name" name="name"
               value="<?= htmlspecialchars($student['name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" name="email"
               value="<?= htmlspecialchars($student['email']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="course">Course</label>
        <input class="form-input" type="text" id="course" name="course"
               value="<?= htmlspecialchars($student['course']) ?>" required>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-blue" type="submit">✓ Save Changes</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
      </div>

    </form>
  </div>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?></footer>
</div>
</body>
</html>