<?php
require 'db.php';

$success = false;
$successMessage = '';
$error   = '';
$form_data = ['name' => '', 'email' => '', 'course_id' => 0, 'course_name' => '', 'course_mode' => 'existing'];

// Get all courses from courses table
$coursesStmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
$courses = $coursesStmt->fetchAll();

// Get distinct courses for dropdown
$coursesStmt = $pdo->query("SELECT DISTINCT course FROM students ORDER BY course");
$courses = $coursesStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']   ?? '');
    $email      = trim($_POST['email']  ?? '');
    $courseMode = $_POST['course_mode'] ?? 'existing';
    $courseId   = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
    $courseName = trim($_POST['course_name'] ?? '');

    // Store form data for re-display on error
    $form_data = compact('name', 'email', 'courseMode', 'courseId', 'courseName');

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
    // Check if email already exists
    else {
        $emailCheckStmt = $pdo->prepare("SELECT id FROM students WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $emailCheckStmt->execute([$email]);
        if ($emailCheckStmt->fetch()) {
            $error = 'This email address is already registered. Each student must have a unique email address.';
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
            
            // Only proceed with student insertion if no course errors
            if (!$error && $courseId > 0) {
                // Insert student
                $sql  = "INSERT INTO students (name, email, course_id) VALUES (:name, :email, :course_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['name' => $name, 'email' => $email, 'course_id' => $courseId]);
                $success = true;
                $successMessage = "$name has been enrolled successfully!";
            } elseif (!$error) {
                $error = 'Invalid course selection. Please try again.';
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Parse specific database errors
            if (str_contains($errorMsg, 'Duplicate entry') && str_contains($errorMsg, 'email')) {
                $error = 'This email address is already registered. Each student must have a unique email.';
            } elseif (str_contains($errorMsg, 'Duplicate entry')) {
                $error = 'A student with this information already exists in the system.';
            } elseif (str_contains($errorMsg, 'FOREIGN KEY')) {
                $error = 'The selected course is no longer available. Please select another course.';
            } elseif (str_contains($errorMsg, 'no such column') || str_contains($errorMsg, 'Unknown column')) {
                $error = 'Database configuration error. Please contact support.';
            } else {
                $error = 'An error occurred while enrolling the student: ' . htmlspecialchars(substr($errorMsg, 0, 50)) . '...';
            }
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
    <div style="display:flex;gap:0.5rem">
      <a href="index.php" class="nav-link">← Back to students</a>
      <a href="courses.php" class="nav-link">📚 Manage Courses</a>
    </div>
  </header>

  <h1 class="page-title">Add <span>Student</span></h1>
  <p class="page-sub">Enrol a new student into the system.</p>

  <?php if ($success): ?>
    <div class="alert alert-success">
      ✓ <?= htmlspecialchars($successMessage) ?> <a href="index.php" style="color:inherit;text-decoration:underline;font-weight:600">View all students →</a>
    </div>
    <div class="card" style="text-align:center;padding:2rem">
      <p style="margin:0;color:var(--muted)">Add another student?</p>
      <button type="button" onclick="location.reload()" class="btn btn-accent" style="margin-top:1rem">+ Add Another</button>
    </div>
  <?php else: ?>
  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" class="form-grid" id="addStudentForm">

      <div class="form-group">
        <label class="form-label" for="name">Full Name *</label>
        <input class="form-input" type="text" id="name" name="name"
               placeholder="e.g. Maria Santos"
               value="<?= htmlspecialchars($form_data['name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address *</label>
        <input class="form-input" type="email" id="email" name="email"
               placeholder="e.g. maria@school.edu"
               value="<?= htmlspecialchars($form_data['email']) ?>" required>
      </div>

      <!-- Course Selection with Toggle -->
      <div class="form-group">
<<<<<<< HEAD
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
=======
        <label class="form-label">Course *</label>
        
        <!-- Course Mode Toggle -->
        <div style="display:flex;gap:0.5rem;margin-bottom:1rem;background:var(--surface);padding:0.4rem;border-radius:var(--radius);border:1px solid var(--border)">
          <input type="radio" id="mode-existing" name="course_mode" value="existing" 
                 <?= $form_data['course_mode'] === 'existing' ? 'checked' : '' ?>
                 style="display:none">
          <label for="mode-existing" class="course-mode-btn active" style="flex:1;text-align:center;cursor:pointer;padding:0.5rem;border-radius:6px;transition:all .2s;background:transparent;border:none;color:var(--text);font-weight:500">
            Select Existing
          </label>
          
          <input type="radio" id="mode-new" name="course_mode" value="new" 
                 <?= $form_data['course_mode'] === 'new' ? 'checked' : '' ?>
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
                      <?= ($form_data['course_id'] == $c['id'] ? 'selected' : '') ?>>
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
                 value="<?= htmlspecialchars($form_data['course_name']) ?>"
                 style="margin-bottom:0.4rem">
          <p style="font-size:0.75rem;color:var(--muted);margin:0">Enter a new course name</p>
        </div>
>>>>>>> bug-fix
      </div>

      <div style="display:flex;gap:.75rem;margin-top:1rem">
        <button class="btn btn-accent" type="submit">✓ Enrol Student</button>
        <a class="btn btn-ghost" href="index.php">Cancel</a>
      </div>

    </form>
  </div>
  <?php endif; ?>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?></footer>
</div>

<<<<<<< HEAD
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
=======
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
document.getElementById('addStudentForm').addEventListener('submit', function(e) {
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
>>>>>>> bug-fix
</script>
</body>
</html>