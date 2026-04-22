<?php
require 'db.php';

$stmt    = $pdo->query("SELECT * FROM students ORDER BY created_at DESC");
$students = $stmt->fetchAll();
$total   = count($students);

// Count distinct courses
$cStmt   = $pdo->query("SELECT COUNT(DISTINCT course) AS c FROM students");
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
      <div class="stat-value green"><?= $total ?></div>
    </div>
    <div class="stat-box">
      <div class="stat-label">Courses</div>
      <div class="stat-value blue"><?= $courses ?></div>
    </div>
  </div>

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Student removed successfully.</div>
  <?php endif; ?>

  <!-- Table -->
  <?php if ($total > 0): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Course</th>
          <th>Added</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
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
                 onclick="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>?')">✕ Delete</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <div class="icon">🎓</div>
    <p>No students yet. <a href="create.php" style="color:var(--accent)">Add the first one →</a></p>
  </div>
  <?php endif; ?>

  <footer class="site-footer">school.db &copy; <?= date('Y') ?> — Student Management System</footer>
</div>
</body>
</html>