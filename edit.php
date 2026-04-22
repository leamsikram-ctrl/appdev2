<?php
require 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch student with course info
$stmt = $pdo->prepare("SELECT s.*, c.name as course_name FROM students s 
                       LEFT JOIN courses c ON s.course_id = c.id WHERE s.id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit;
}

// Get all courses from courses table
$coursesStmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
$courses = $coursesStmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']   ?? '');
    $email     = trim($_POST['email']  ?? '');
    $courseId  = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    $courseName = trim($_POST['course_name'] ?? '');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$name || !$email || (!$courseId && !$courseName)) {
        $error = 'All fields are required.';
    } else {
        try {
            // If new course name provided, create it first
            if (!$courseId && $courseName) {
                $checkStmt = $pdo->prepare("SELECT id FROM courses WHERE name = ?");
                $checkStmt->execute([$courseName]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $courseId = $existing['id'];
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO courses (name) VALUES (?)");
                    $insertStmt->execute([$courseName]);
                    $courseId = $pdo->lastInsertId();
                }
            }
            
            $sql = "UPDATE students SET name = ?, email = ?, course_id = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$name, $email, $courseId, $id]);
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
    $student['course_id'] = $courseId;
    $student['course_name'] = $courseName ?: $student['course_name'];
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
        <select class="form-input" id="course" name="course_id">
          <option value="">-- Select a course --</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($student['course_id'] == $c['id'] ? 'selected' : '') ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input class="form-input" type="text" id="course-new" name="course_name" 
               placeholder="Or type a new course name"
               value="<?= !in_array($student['course_id'], array_column($courses, 'id')) ? htmlspecialchars($student['course_name'] ?? '') : '' ?>">
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