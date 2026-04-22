<?php
require 'db.php';

$success = false;
$error   = '';

// Get distinct courses for dropdown
$coursesStmt = $pdo->query("SELECT DISTINCT course FROM students ORDER BY course");
$courses = $coursesStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $course = trim($_POST['course'] ?? '');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$name || !$email || !$course) {
        $error = 'All fields are required.';
    } else {
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
        <select class="form-input" id="course" name="course" required>
          <option value="">-- Select a course or enter new --</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= (($_POST['course'] ?? '') === $c ? 'selected' : '') ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input class="form-input" type="text" id="course-new" placeholder="Or type a new course name"
               value="<?= !in_array(($_POST['course'] ?? ''), $courses) ? htmlspecialchars($_POST['course'] ?? '') : '' ?>">
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-accent" type="submit">+ Add Student</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
      </div>

    </form>
  </div>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?></footer>
</div>

<script>
// Course dropdown/input logic
const courseSelect = document.getElementById('course');
const courseNewInput = document.getElementById('course-new');

courseSelect.addEventListener('change', function() {
  if (this.value) {
    courseNewInput.value = '';
    courseNewInput.style.display = 'none';
  } else {
    courseNewInput.style.display = 'block';
  }
});

const form = document.querySelector('form');
form.addEventListener('submit', function(e) {
  if (!courseSelect.value && !courseNewInput.value) {
    e.preventDefault();
    alert('Please select or enter a course.');
  } else if (!courseSelect.value && courseNewInput.value) {
    courseSelect.value = courseNewInput.value;
  }
});

// Initialize on page load
if (courseSelect.value) {
  courseNewInput.style.display = 'none';
} else {
  courseNewInput.style.display = 'block';
}
</script>
</body>
</html>