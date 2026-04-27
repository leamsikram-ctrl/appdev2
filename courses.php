<?php
require 'db.php';

$success = false;
$error   = '';
$view_course = null;

// Handle add course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        
        if (!$name) {
            $error = 'Course name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO courses (name) VALUES (?)");
                $stmt->execute([$name]);
                $success = true;
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(), 'Duplicate entry')
                    ? 'That course name already exists.'
                    : 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'update') {
        $id   = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        
        if (!$name) {
            $error = 'Course name is required.';
        } elseif ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE courses SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $success = true;
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(), 'Duplicate entry')
                    ? 'That course name already exists.'
                    : 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid course ID.';
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$id]);
                $success = true;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
                    $error = 'Cannot delete course with enrolled students.';
                } else {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'Invalid course ID.';
        }
    } elseif ($_POST['action'] === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        $ids = array_filter($ids, fn($id) => is_numeric($id));
        
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            try {
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $success = true;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'FOREIGN KEY')) {
                    $error = 'Cannot delete courses with enrolled students.';
                } else {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        } else {
            $error = 'No courses selected.';
        }
    }
}

// Search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) && in_array($_GET['sort'], ['name', 'students', 'created_at']) ? $_GET['sort'] : 'name';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
$sortToggleOrder = $sortOrder === 'ASC' ? 'desc' : 'asc';

$searchFilter = '';
$searchParams = [];
if ($search) {
    $searchFilter = " WHERE name LIKE ?";
    $searchParams = ["%$search%"];
}

// Fetch all courses with student counts
$sql = "SELECT c.*, COUNT(s.id) as student_count FROM courses c 
        LEFT JOIN students s ON c.id = s.course_id
        $searchFilter
        GROUP BY c.id";

// Handle sorting
if ($sortBy === 'students') {
    $sql .= " ORDER BY student_count $sortOrder";
} else {
    $sql .= " ORDER BY c.$sortBy $sortOrder";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($searchParams);
$courses = $stmt->fetchAll();

// Get totals
$totalCourses = count($courses);
$totalStudents = $pdo->query("SELECT COUNT(*) as cnt FROM students")->fetch()['cnt'];
$avgStudents = $totalCourses > 0 ? round($totalStudents / $totalCourses, 1) : 0;

// View course students
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $stmt = $pdo->prepare("SELECT c.*, COUNT(s.id) as student_count FROM courses c 
                           LEFT JOIN students s ON c.id = s.course_id 
                           WHERE c.id = ? GROUP BY c.id");
    $stmt->execute([$viewId]);
    $view_course = $stmt->fetch();
    
    if ($view_course) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE course_id = ? ORDER BY name");
        $stmt->execute([$viewId]);
        $view_course['students'] = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Courses – School DB</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">

  <header class="site-header">
    <div class="logo">school<span>.</span>db</div>
    <a href="index.php" class="nav-link">← Back to students</a>
  </header>

  <h1 class="page-title">Manage <span>Courses</span></h1>
  <p class="page-sub">Add, edit, or remove courses from the system.</p>

  <?php if ($success): ?>
    <div class="alert alert-success">Course updated successfully!</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-box">
      <div class="stat-label">Total Courses</div>
      <div class="stat-value blue"><?= $totalCourses ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Total Students</div>
      <div class="stat-value green"><?= $totalStudents ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Avg. Per Course</div>
      <div class="stat-value purple"><?= $avgStudents ?></div>
    </div>
  </div>

  <?php if (!$view_course): ?>
  
  <!-- Add Course Form -->
  <div class="card">
    <h2 style="margin-top:0;color:var(--text)">Add New Course</h2>
    <form method="POST" class="form-grid">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label class="form-label" for="course-name">Course Name</label>
        <input class="form-input" type="text" id="course-name" name="name"
               placeholder="e.g. Business Administration"
               value="<?= $_POST['action'] === 'add' ? htmlspecialchars($_POST['name'] ?? '') : '' ?>" required>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-accent" type="submit">+ Add Course</button>
      </div>
    </form>
  </div>

  <!-- Search and Filter -->
  <div class="search-bar-container">
    <form method="GET" class="search-bar">
      <input type="text" name="search" class="search-input" placeholder="Search courses..." 
             value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-accent" style="padding: .75rem 1.2rem">Search</button>
      <?php if ($search): ?>
        <a href="courses.php" class="btn btn-ghost" style="padding: .75rem 1.2rem">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Courses Table -->
  <?php if (!empty($courses)): ?>
  <div class="table-wrap">
    <form method="POST" class="bulk-form">
      <input type="hidden" name="action" value="bulk_delete">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all" class="select-all-checkbox"></th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => ($sortBy === 'name' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'name' ? 'active' : '' ?>">
                Course Name <?php if ($sortBy === 'name') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'students', 'order' => ($sortBy === 'students' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'students' ? 'active' : '' ?>">
                Students <?php if ($sortBy === 'students') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => ($sortBy === 'created_at' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'created_at' ? 'active' : '' ?>">
                Added <?php if ($sortBy === 'created_at') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $course): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $course['id'] ?>" class="course-checkbox"></td>
            <td><?= htmlspecialchars($course['name']) ?></td>
            <td><span class="badge"><?= $course['student_count'] ?></span></td>
            <td style="color:var(--muted);font-size:.78rem">
              <?= date('M j, Y', strtotime($course['created_at'])) ?>
            </td>
            <td>
              <div class="action-group">
                <a class="action-btn info" href="?view=<?= $course['id'] ?>">👁 View</a>
                <button type="button" class="action-btn edit" onclick="editCourse(<?= $course['id'] ?>, '<?= htmlspecialchars(addslashes($course['name'])) ?>')">✏ Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($course['name'])) ?>?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $course['id'] ?>">
                  <button type="submit" class="action-btn delete" style="border:none;background:none;cursor:pointer;padding:0.5rem 0.75rem">✕ Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Bulk Delete Button -->
      <div style="margin-top:1rem;padding:1rem;background:var(--surface);border-radius:0.5rem;display:flex;gap:1rem;align-items:center">
        <span style="color:var(--muted);font-size:0.9rem">Selected: <strong id="selected-count">0</strong></span>
        <button type="submit" class="btn btn-ghost" id="bulk-delete-btn" disabled 
                onclick="return confirm('Delete selected courses?')">🗑 Delete Selected</button>
      </div>
    </form>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="icon">📚</div>
    <p><?= $search ? 'No courses found matching your search.' : 'No courses yet.' ?> <a href="#" onclick="document.querySelector('form').scrollIntoView(); return false;" style="color:var(--accent)">Add one above →</a></p>
  </div>
  <?php endif; ?>

  <?php else: ?>
  
  <!-- View Course Details -->
  <div class="card" style="margin-bottom:2rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <h2 style="margin:0;color:var(--text)"><?= htmlspecialchars($view_course['name']) ?></h2>
      <a href="courses.php" class="btn btn-ghost">← Back to Courses</a>
    </div>
    
    <div class="stats-row">
      <div class="stat-box">
        <div class="stat-label">Students Enrolled</div>
        <div class="stat-value green"><?= $view_course['student_count'] ?></div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Course Created</div>
        <div class="stat-value blue"><?= date('M j, Y', strtotime($view_course['created_at'])) ?></div>
      </div>
    </div>
  </div>

  <!-- Enrolled Students -->
  <div class="card">
    <h3 style="margin-top:0;color:var(--text)">Enrolled Students</h3>
    
    <?php if (!empty($view_course['students'])): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Enrolled</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($view_course['students'] as $student): ?>
          <tr>
            <td class="id-cell"><?= $student['id'] ?></td>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td><?= htmlspecialchars($student['email']) ?></td>
            <td style="color:var(--muted);font-size:.78rem">
              <?= date('M j, Y', strtotime($student['created_at'])) ?>
            </td>
            <td>
              <a class="action-btn edit" href="edit.php?id=<?= $student['id'] ?>">✏ Edit</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p style="color:var(--muted);text-align:center;padding:2rem">No students enrolled in this course yet.</p>
    <?php endif; ?>
  </div>

  <?php endif; ?>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?> — Student Management System</footer>
</div>

<!-- Edit Course Modal -->
<div id="editModal" class="modal">
  <div class="card" style="width:90%;max-width:500px">
    <h2 style="margin-top:0;color:var(--text)">Edit Course</h2>
    <form method="POST" class="form-grid">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="editId" value="">
      <div class="form-group">
        <label class="form-label" for="edit-name">Course Name</label>
        <input class="form-input" type="text" id="edit-name" name="name" required>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="btn btn-blue" type="submit">✓ Save Changes</button>
        <button class="btn btn-ghost" type="button" onclick="closeEditModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<style>
.sort-link {
  color: var(--text);
  text-decoration: none;
  cursor: pointer;
}

.sort-link.active {
  color: var(--accent);
  font-weight: 600;
}

.badge {
  display: inline-block;
  background: var(--accent);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.action-btn.info {
  color: var(--info-color, #0066cc);
  border: 1px solid var(--info-color, #0066cc);
}

.action-btn.info:hover {
  background: rgba(0, 102, 204, 0.1);
}

.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  justify-content: center;
  align-items: center;
}

.modal.show {
  display: flex;
}

.select-all-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.course-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

#bulk-delete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

#bulk-delete-btn:not(:disabled) {
  cursor: pointer;
}
</style>

<script>
function editCourse(id, name) {
  document.getElementById('editId').value = id;
  document.getElementById('edit-name').value = name;
  document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
  document.getElementById('editModal').classList.remove('show');
}

// Select all checkbox logic
document.getElementById('select-all').addEventListener('change', function() {
  const checkboxes = document.querySelectorAll('.course-checkbox');
  checkboxes.forEach(checkbox => checkbox.checked = this.checked);
  updateBulkDeleteButton();
});

// Individual checkbox logic
document.querySelectorAll('.course-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', updateBulkDeleteButton);
});

function updateBulkDeleteButton() {
  const checkedCount = document.querySelectorAll('.course-checkbox:checked').length;
  document.getElementById('selected-count').textContent = checkedCount;
  document.getElementById('bulk-delete-btn').disabled = checkedCount === 0;
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeEditModal();
  }
});
</script>
</body>
</html>
