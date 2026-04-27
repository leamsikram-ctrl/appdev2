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
$courseMode = 'existing'; // default mode for display

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']   ?? '');
    $email      = trim($_POST['email']  ?? '');
    $courseMode = $_POST['course_mode'] ?? 'existing';
    $courseId   = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    $courseName = trim($_POST['course_name'] ?? '');

    // ===== VALIDATION LAYER =====
    // Check for empty name
    if (!$name) {
        $error = 'Full name is required. Please enter a valid name.';
    } 
    // Check for empty email
    elseif (!$email) {
        $error = 'Email address is required. Please enter a valid email.';
    } 
    // Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format. Please enter a valid email address (e.g., student@school.edu).';
    }
    // Check if email already exists (excluding current student)
    else {
        $emailCheckStmt = $pdo->prepare("SELECT id FROM students WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1");
        $emailCheckStmt->execute([$email, $id]);
        if ($emailCheckStmt->fetch()) {
            $error = 'This email address is already registered to another student. Each student must have a unique email.';
        }
    }
    
    // Check course selection
    if (!$error && $courseMode === 'existing' && !$courseId) {
        $error = 'Please select an existing course from the dropdown.';
    } elseif (!$error && $courseMode === 'new' && !$courseName) {
        $error = 'Please enter a course name or select an existing course.';
    }

    // ===== PROCESSING LAYER =====
    if (!$error) {
        try {
            // Handle course creation/selection
            if ($courseMode === 'new') {
                // Check if course already exists (case-insensitive)
                $checkStmt = $pdo->prepare("SELECT id FROM courses WHERE LOWER(name) = LOWER(?) LIMIT 1");
                $checkStmt->execute([$courseName]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $courseId = $existing['id'];
                } else {
                    // Validate course name length
                    if (strlen($courseName) > 100) {
                        $error = 'Course name is too long. Please use a name with 100 characters or fewer.';
                    } else {
                        // Create new course
                        $insertStmt = $pdo->prepare("INSERT INTO courses (name) VALUES (?)");
                        $insertStmt->execute([$courseName]);
                        $courseId = $pdo->lastInsertId();
                    }
                }
            }
            
            // Only proceed with student update if no course errors
            if (!$error && $courseId > 0) {
                // Update student
                $sql = "UPDATE students SET name = ?, email = ?, course_id = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$name, $email, $courseId, $id]);
                header("Location: index.php");
                exit;
            } elseif (!$error) {
                $error = 'Invalid course selection. Please try again.';
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Parse specific database errors
            if (str_contains($errorMsg, 'Duplicate entry') && str_contains($errorMsg, 'email')) {
                $error = 'This email address is already registered to another student. Each student must have a unique email.';
            } elseif (str_contains($errorMsg, 'Duplicate entry')) {
                $error = 'A student with this information already exists in the system.';
            } elseif (str_contains($errorMsg, 'FOREIGN KEY')) {
                $error = 'The selected course is no longer available. Please select another course.';
            } elseif (str_contains($errorMsg, 'no such column') || str_contains($errorMsg, 'Unknown column')) {
                $error = 'Database configuration error. Please contact support.';
            } else {
                $error = 'An error occurred while updating the student: ' . htmlspecialchars(substr($errorMsg, 0, 50)) . '...';
            }
        }
    }
    
    // Re-populate form on error
    if ($error) {
        $student['name']   = $name;
        $student['email']  = $email;
        $student['course_id'] = $courseId;
        $student['course_name'] = $courseName;
    }
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
    <div style="display:flex;gap:0.5rem">
      <a href="index.php" class="nav-link">← Back to students</a>
      <a href="courses.php" class="nav-link">📚 Manage Courses</a>
    </div>
  </header>

  <h1 class="page-title">Edit <span>Student</span></h1>
  <p class="page-sub">Update details for student #<?= $id ?>.</p>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" class="form-grid" id="editStudentForm">

      <div class="form-group">
        <label class="form-label" for="name">Full Name *</label>
        <input class="form-input" type="text" id="name" name="name"
               value="<?= htmlspecialchars($student['name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address *</label>
        <input class="form-input" type="email" id="email" name="email"
               value="<?= htmlspecialchars($student['email']) ?>" required>
      </div>

      <!-- Course Selection with Toggle -->
      <div class="form-group">
        <label class="form-label">Course *</label>
        
        <!-- Determine current mode based on whether course_id is in the list -->
        <?php 
          $courseIds = array_column($courses, 'id');
          $isCustomCourse = $student['course_id'] && !in_array($student['course_id'], $courseIds);
          $currentMode = ($courseMode === 'new' || $isCustomCourse) ? 'new' : 'existing';
        ?>
        
        <!-- Course Mode Toggle -->
        <div style="display:flex;gap:0.5rem;margin-bottom:1rem;background:var(--surface);padding:0.4rem;border-radius:var(--radius);border:1px solid var(--border)">
          <input type="radio" id="mode-existing" name="course_mode" value="existing" 
                 <?= $currentMode === 'existing' ? 'checked' : '' ?>
                 style="display:none">
          <label for="mode-existing" class="course-mode-btn active" style="flex:1;text-align:center;cursor:pointer;padding:0.5rem;border-radius:6px;transition:all .2s;background:transparent;border:none;color:var(--text);font-weight:500">
            Select Existing
          </label>
          
          <input type="radio" id="mode-new" name="course_mode" value="new" 
                 <?= $currentMode === 'new' ? 'checked' : '' ?>
                 style="display:none">
          <label for="mode-new" class="course-mode-btn" style="flex:1;text-align:center;cursor:pointer;padding:0.5rem;border-radius:6px;transition:all .2s;background:transparent;border:none;color:var(--muted);font-weight:500">
            Create New
          </label>
        </div>

        <!-- Existing Course Dropdown -->
        <div id="existing-course-section" style="display:none">
          <select class="form-input" id="course" name="course_id">
            <option value="">-- Select a course --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= htmlspecialchars($c['id']) ?>" 
                      <?= ($student['course_id'] == $c['id'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p style="font-size:0.75rem;color:var(--muted);margin-top:0.4rem">Select from existing courses</p>
        </div>

        <!-- New Course Input -->
        <div id="new-course-section" style="display:none">
          <input class="form-input" type="text" id="course-new" name="course_name" 
                 placeholder="e.g. Business Administration"
                 value="<?= htmlspecialchars($student['course_name'] ?? '') ?>"
                 style="margin-bottom:0.4rem">
          <p style="font-size:0.75rem;color:var(--muted);margin:0">Enter a new course name</p>
        </div>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:1rem">
        <button class="btn btn-blue" type="submit">✓ Save Changes</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
      </div>

    </form>
  </div>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?></footer>
</div>

<style>
.course-mode-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.course-mode-btn.active {
  background: var(--accent);
  color: var(--bg);
}
</style>

<script>
// Course mode selection logic
const modeExisting = document.getElementById('mode-existing');
const modeNew = document.getElementById('mode-new');
const existingSection = document.getElementById('existing-course-section');
const newSection = document.getElementById('new-course-section');
const existingCourseSelect = document.getElementById('course');
const newCourseInput = document.getElementById('course-new');
const modeButtons = document.querySelectorAll('.course-mode-btn');

function updateCourseMode() {
  if (modeExisting.checked) {
    existingSection.style.display = 'block';
    newSection.style.display = 'none';
    newCourseInput.value = '';
    newCourseInput.removeAttribute('required');
    existingCourseSelect.setAttribute('required', 'required');
    modeButtons[0].classList.add('active');
    modeButtons[1].classList.remove('active');
  } else {
    existingSection.style.display = 'none';
    newSection.style.display = 'block';
    existingCourseSelect.value = '';
    existingCourseSelect.removeAttribute('required');
    newCourseInput.setAttribute('required', 'required');
    modeButtons[0].classList.remove('active');
    modeButtons[1].classList.add('active');
  }
}

modeExisting.addEventListener('change', updateCourseMode);
modeNew.addEventListener('change', updateCourseMode);

// Initialize on page load
updateCourseMode();

// Form submission handler
document.getElementById('editStudentForm').addEventListener('submit', function(e) {
  if (modeExisting.checked && !existingCourseSelect.value) {
    e.preventDefault();
    alert('Please select a course.');
    existingCourseSelect.focus();
  } else if (modeNew.checked && !newCourseInput.value.trim()) {
    e.preventDefault();
    alert('Please enter a course name.');
    newCourseInput.focus();
  }
});
</script>
</body>
</html>