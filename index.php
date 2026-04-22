<?php
require 'db.php';

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchFilter = '';
$searchParams = [];
if ($search) {
    $searchFilter = " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $searchParams = ["%$search%", "%$search%", "%$search%"];
}

// Sort
$validSortColumns = ['id', 'name', 'email', 'course', 'created_at'];
$sortBy = isset($_GET['sort']) && in_array($_GET['sort'], $validSortColumns) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
$sortToggleOrder = $sortOrder === 'ASC' ? 'desc' : 'asc';

// Get total count for pagination
$countSql = "SELECT COUNT(*) as cnt FROM students WHERE 1=1" . $searchFilter;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($searchParams);
$totalStudents = (int) $countStmt->fetch()['cnt'];
$totalPages = ceil($totalStudents / $perPage);

// Fetch students
$sql = "SELECT * FROM students WHERE 1=1" . $searchFilter . " ORDER BY $sortBy $sortOrder LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params = array_merge($searchParams, [$perPage, $offset]);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Count distinct courses
$cStmt = $pdo->query("SELECT COUNT(DISTINCT course) AS c FROM students");
$courses = (int) $cStmt->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Students – School DB</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper">

  <!-- Header -->
  <header class="site-header">
    <div class="logo">school<span>.</span>db</div>
    <a href="create.php" class="nav-link primary">+ Add Student</a>
  </header>

  <!-- Page title -->
  <h1 class="page-title">Student <span>Records</span></h1>
  <p class="page-sub">All enrolled students in the system.</p>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-box">
      <div class="stat-label">Total Students</div>
      <div class="stat-value green"><?= $totalStudents ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Courses</div>
      <div class="stat-value blue"><?= $courses ?></div>
    </div>
  </div>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Student removed successfully.</div>
  <?php endif; ?>

  <!-- Search Bar -->
  <div class="search-bar-container">
    <form method="GET" class="search-bar">
      <input type="text" name="search" class="search-input" placeholder="Search by name, email, or course..." 
             value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-accent" style="padding: .75rem 1.2rem">Search</button>
      <?php if ($search): ?>
        <a href="index.php" class="btn btn-ghost" style="padding: .75rem 1.2rem">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <?php if ($totalStudents > 0): ?>
  <div class="table-wrap">
    <form method="POST" action="bulk-delete.php" class="bulk-form">
      <table>
        <thead>
          <tr>
            <th style="width: 40px"><input type="checkbox" id="select-all" class="select-all-checkbox"></th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'id', 'order' => ($sortBy === 'id' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'id' ? 'active' : '' ?>">
                # <?php if ($sortBy === 'id') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => ($sortBy === 'name' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'name' ? 'active' : '' ?>">
                Name <?php if ($sortBy === 'name') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'email', 'order' => ($sortBy === 'email' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'email' ? 'active' : '' ?>">
                Email <?php if ($sortBy === 'email') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
              </a>
            </th>
            <th>
              <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'course', 'order' => ($sortBy === 'course' ? $sortToggleOrder : 'asc')])) ?>" 
                 class="sort-link <?= $sortBy === 'course' ? 'active' : '' ?>">
                Course <?php if ($sortBy === 'course') echo $sortOrder === 'ASC' ? '↑' : '↓'; ?>
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
          <?php foreach ($students as $s): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>" class="student-checkbox"></td>
            <td class="id-cell"><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td class="course-badge"><span><?= htmlspecialchars($s['course']) ?></span></td>
            <td style="color:var(--muted);font-size:.78rem">
              <?= date('M j, Y', strtotime($s['created_at'])) ?>
            </td>
            <td>
              <div class="action-group">
                <a class="action-btn edit" href="edit.php?id=<?= $s['id'] ?>">✏ Edit</a>
                <a class="action-btn delete"
                   href="delete.php?id=<?= $s['id'] ?>"
                   onclick="return confirm('Delete <?= htmlspecialchars($s['name'], ENT_QUOTES) ?>?')">✕ Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <!-- Bulk Actions -->
      <div class="bulk-actions" style="display: none;" id="bulk-actions">
        <div style="padding: 1rem 1.25rem; background: var(--surface); border-top: 1px solid var(--border); display: flex; gap: 1rem; align-items: center;">
          <span id="selected-count">0 selected</span>
          <button type="submit" class="btn btn-danger" onclick="return confirm('Delete selected students? This cannot be undone.')">🗑 Delete Selected</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pag-btn">« First</a>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pag-btn">‹ Prev</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
      <?php if ($i === $page): ?>
        <span class="pag-btn active"><?= $i ?></span>
      <?php else: ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pag-btn"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pag-btn">Next ›</a>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="pag-btn">Last »</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <div class="empty-state">
    <div class="icon">🎓</div>
    <p><?= $search ? 'No students found. ' : 'No students yet. ' ?><a href="<?= $search ? 'index.php' : 'create.php' ?>" style="color:var(--accent)">
      <?= $search ? 'Clear search' : 'Add the first one' ?> →</a></p>
  </div>
  <?php endif; ?>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?> — Student Management System</footer>
</div>

<script>
// Bulk select functionality
const selectAllCheckbox = document.getElementById('select-all');
const studentCheckboxes = document.querySelectorAll('.student-checkbox');
const bulkActionsDiv = document.getElementById('bulk-actions');
const selectedCountSpan = document.getElementById('selected-count');

function updateBulkActions() {
  const checked = document.querySelectorAll('.student-checkbox:checked').length;
  bulkActionsDiv.style.display = checked > 0 ? 'block' : 'none';
  selectedCountSpan.textContent = checked + (checked === 1 ? ' selected' : ' selected');
}

selectAllCheckbox.addEventListener('change', function() {
  studentCheckboxes.forEach(cb => cb.checked = this.checked);
  updateBulkActions();
});

studentCheckboxes.forEach(cb => {
  cb.addEventListener('change', function() {
    selectAllCheckbox.checked = Array.from(studentCheckboxes).every(checkbox => checkbox.checked);
    updateBulkActions();
  });
});
</script>
</body>
</html>