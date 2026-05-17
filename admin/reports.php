<?php
// ============================================================
// admin/reports.php — Admin: Reports & Analytics
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Reports & Analytics';

// ── Summary Stats ────────────────────────────────────────────
$stats = [
    'total_books'       => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'total_copies'      => $pdo->query("SELECT COALESCE(SUM(total_quantity),0) FROM books")->fetchColumn(),
    'total_users'       => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users'      => $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
    'total_issues'      => $pdo->query("SELECT COUNT(*) FROM issued_books")->fetchColumn(),
    'current_issued'    => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='approved'")->fetchColumn(),
    'total_returned'    => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='returned'")->fetchColumn(),
    'total_pending'     => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='pending'")->fetchColumn(),
    'total_fine'        => $pdo->query("SELECT COALESCE(SUM(fine_amount),0) FROM issued_books")->fetchColumn(),
    'fine_collected'    => $pdo->query("SELECT COALESCE(SUM(fine_amount),0) FROM issued_books WHERE fine_paid=1")->fetchColumn(),
    'fine_due'          => $pdo->query("SELECT COALESCE(SUM(fine_amount),0) FROM issued_books WHERE fine_paid=0 AND status IN ('approved','returned')")->fetchColumn(),
    'overdue_count'     => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='approved' AND due_date < CURDATE()")->fetchColumn(),
];

// Most issued books
$mostIssued = $pdo->query("
    SELECT b.title, b.author, b.category, COUNT(ib.id) AS issue_count
    FROM issued_books ib JOIN books b ON b.id=ib.book_id
    GROUP BY ib.book_id ORDER BY issue_count DESC LIMIT 10
")->fetchAll();

// Category-wise distribution
$catStats = $pdo->query("
    SELECT category,
           COUNT(*) AS book_count,
           SUM(total_quantity) AS total_copies,
           SUM(issued_quantity) AS issued_copies
    FROM books GROUP BY category ORDER BY book_count DESC
")->fetchAll();

// Monthly issue trend (last 6 months)
$trend = $pdo->query("
    SELECT DATE_FORMAT(issue_date,'%b %Y') AS month,
           COUNT(*) AS issues
    FROM issued_books
    WHERE status IN ('approved','returned') AND issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date,'%Y-%m')
    ORDER BY issue_date
")->fetchAll();

// Most active students
$topStudents = $pdo->query("
    SELECT u.full_name, u.enrollment_no, u.department,
           COUNT(ib.id) AS borrow_count
    FROM issued_books ib JOIN users u ON u.id=ib.user_id
    GROUP BY ib.user_id ORDER BY borrow_count DESC LIMIT 5
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="background:var(--rust);"><i class="fas fa-user-shield" style="font-size:1.1rem;"></i></div>
      <div class="sidebar-name"><?= htmlspecialchars(currentAdmin()['full_name']) ?></div>
      <div class="sidebar-role" style="color:#f87171;"><i class="fas fa-crown"></i> Administrator</div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Management</div>
      <a href="/admin_dashboard.php"     class="sidebar-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="/admin/manage_books.php"  class="sidebar-nav-item"><i class="fas fa-books"></i> Manage Books</a>
      <a href="/add_book.php"            class="sidebar-nav-item"><i class="fas fa-plus-circle"></i> Add Book</a>
      <a href="/admin/manage_users.php"  class="sidebar-nav-item"><i class="fas fa-users"></i> Manage Users</a>
      <a href="/return_book.php"         class="sidebar-nav-item"><i class="fas fa-undo"></i> Return Books</a>
      <a href="/admin/reports.php"       class="sidebar-nav-item active"><i class="fas fa-chart-bar"></i> Reports</a>
      <div class="sidebar-section-title">Account</div>
      <a href="/logout.php"              class="sidebar-nav-item" style="color:rgba(255,100,100,.7) !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="dashboard-main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
      <div><h2>Reports & Analytics</h2><p style="margin:0;font-size:.88rem;">Library performance overview</p></div>
      <div style="font-size:.85rem;color:var(--text-muted);"><i class="fas fa-calendar"></i> Generated: <?= date('d M Y, h:i A') ?></div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:2rem;">
      <div class="stat-card" style="--stat-color:#c9a84c;--stat-bg:#f5edda;">
        <div class="stat-icon"><i class="fas fa-books"></i></div>
        <div class="stat-info"><div class="stat-label">Total Books</div><div class="stat-value"><?= $stats['total_books'] ?></div><div class="stat-sub"><?= $stats['total_copies'] ?> copies</div></div>
      </div>
      <div class="stat-card" style="--stat-color:#2563eb;--stat-bg:#eff6ff;">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info"><div class="stat-label">Total Users</div><div class="stat-value"><?= $stats['total_users'] ?></div><div class="stat-sub"><?= $stats['active_users'] ?> active</div></div>
      </div>
      <div class="stat-card" style="--stat-color:#1a7a6e;--stat-bg:#e0f4f1;">
        <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
        <div class="stat-info"><div class="stat-label">Total Issues</div><div class="stat-value"><?= $stats['total_issues'] ?></div><div class="stat-sub"><?= $stats['current_issued'] ?> active</div></div>
      </div>
      <div class="stat-card" style="--stat-color:#7c3aed;--stat-bg:#f5f3ff;">
        <div class="stat-icon"><i class="fas fa-undo"></i></div>
        <div class="stat-info"><div class="stat-label">Returned</div><div class="stat-value"><?= $stats['total_returned'] ?></div></div>
      </div>
      <div class="stat-card" style="--stat-color:#e67e22;--stat-bg:#fff7ed;">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><div class="stat-label">Pending</div><div class="stat-value"><?= $stats['total_pending'] ?></div></div>
      </div>
      <div class="stat-card" style="--stat-color:#c0392b;--stat-bg:#fde8e6;">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info"><div class="stat-label">Overdue</div><div class="stat-value"><?= $stats['overdue_count'] ?></div></div>
      </div>
    </div>

    <!-- Fine Summary -->
    <div class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-rupee-sign" style="color:var(--gold);margin-right:.5rem;"></i> Fine Summary</h3>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;text-align:center;">
          <div style="padding:1.2rem;background:var(--bg-input);border-radius:var(--radius);">
            <div style="font-size:.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">Total Fine Generated</div>
            <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:var(--text);">₹<?= number_format((float)$stats['total_fine'],2) ?></div>
          </div>
          <div style="padding:1.2rem;background:var(--success-bg);border-radius:var(--radius);">
            <div style="font-size:.78rem;color:var(--teal);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">Fine Collected</div>
            <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:var(--teal);">₹<?= number_format((float)$stats['fine_collected'],2) ?></div>
          </div>
          <div style="padding:1.2rem;background:var(--danger-bg);border-radius:var(--radius);">
            <div style="font-size:.78rem;color:var(--rust);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;">Fine Pending</div>
            <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:var(--rust);">₹<?= number_format((float)$stats['fine_due'],2) ?></div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;" class="report-grid">

      <!-- Most Issued Books -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-trophy" style="color:var(--gold);margin-right:.5rem;"></i> Most Issued Books</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <table>
            <thead>
              <tr><th>#</th><th>Book</th><th>Category</th><th>Issues</th></tr>
            </thead>
            <tbody>
              <?php foreach ($mostIssued as $i => $b): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <strong style="font-size:.85rem;"><?= htmlspecialchars(mb_strimwidth($b['title'],0,30,'…')) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($b['author']) ?></small>
                </td>
                <td><span class="pill pill-gold" style="font-size:.7rem;"><?= htmlspecialchars($b['category']) ?></span></td>
                <td style="font-weight:700;color:var(--gold-dark);"><?= $b['issue_count'] ?>×</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Students -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-user-graduate" style="color:#2563eb;margin-right:.5rem;"></i> Most Active Students</h3>
        </div>
        <div class="card-body" style="padding:0;">
          <table>
            <thead>
              <tr><th>#</th><th>Student</th><th>Department</th><th>Books</th></tr>
            </thead>
            <tbody>
              <?php foreach ($topStudents as $i => $s): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <strong style="font-size:.85rem;"><?= htmlspecialchars($s['full_name']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($s['enrollment_no']) ?></small>
                </td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($s['department'] ?: '—') ?></td>
                <td style="font-weight:700;color:#2563eb;"><?= $s['borrow_count'] ?>×</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
    <style>@media(max-width:900px){.report-grid{grid-template-columns:1fr!important}}</style>

    <!-- Category Stats -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-chart-pie" style="color:var(--teal);margin-right:.5rem;"></i> Category-wise Distribution</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Category</th><th>Titles</th><th>Total Copies</th><th>Issued</th><th>Available</th><th>Utilization</th></tr>
            </thead>
            <tbody>
              <?php foreach ($catStats as $i => $c):
                $util = $c['total_copies'] > 0 ? round(($c['issued_copies']/$c['total_copies'])*100) : 0;
              ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="pill pill-gold"><?= htmlspecialchars($c['category']) ?></span></td>
                <td style="font-weight:700;"><?= $c['book_count'] ?></td>
                <td><?= $c['total_copies'] ?></td>
                <td style="color:var(--rust);"><?= $c['issued_copies'] ?></td>
                <td style="color:var(--teal);"><?= $c['total_copies'] - $c['issued_copies'] ?></td>
                <td style="min-width:140px;">
                  <div style="display:flex;align-items:center;gap:.6rem;">
                    <div class="progress-bar-wrap" style="flex:1;">
                      <div class="progress-bar" style="width:<?= $util ?>%;background:<?= $util>70?'var(--rust)':($util>40?'#e67e22':'var(--teal)') ?>;"></div>
                    </div>
                    <span style="font-size:.78rem;font-weight:700;color:var(--text-muted);min-width:30px;"><?= $util ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
