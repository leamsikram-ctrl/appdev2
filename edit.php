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

// Get distinct courses for dropdown
$coursesStmt = $pdo->query("SELECT DISTINCT course FROM students ORDER BY course");
$courses = $coursesStmt->fetchAll(PDO::FETCH_COLUMN);

$error = '';

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
            $sql = "UPDATE students SET name = ?, email = ?, course = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$name, $email, $course, $id]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = str_contains($e->getMessage(), 'Duplicate entry')
                ? 'That email address is already taken.'
                : 'Database error: ' . $e->getMessage();
        }
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
        <select class="form-input" id="course" name="course" required>
          <option value="">-- Select a course or enter new --</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($student['course'] === $c ? 'selected' : '') ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input class="form-input" type="text" id="course-new" placeholder="Or type a new course name"
               value="<?= !in_array($student['course'], $courses) ? htmlspecialchars($student['course']) : '' ?>">
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-blue" type="submit">✓ Save Changes</button>
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