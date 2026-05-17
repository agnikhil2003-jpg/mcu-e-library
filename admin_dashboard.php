<?php
// ============================================================
// admin_dashboard.php — Admin Control Panel
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
$admin     = currentAdmin();

// Stats
$stats = [
    'total_books'   => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'total_users'   => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'issued_now'    => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='approved'")->fetchColumn(),
    'pending_req'   => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='pending'")->fetchColumn(),
    'total_avail'   => $pdo->query("SELECT COALESCE(SUM(total_quantity-issued_quantity),0) FROM books")->fetchColumn(),
    'total_fine'    => $pdo->query("SELECT COALESCE(SUM(fine_amount),0) FROM issued_books WHERE fine_paid=0 AND status IN ('approved','returned')")->fetchColumn(),
    'overdue'       => $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='approved' AND due_date < CURDATE()")->fetchColumn(),
];

// Recent pending requests
$pendingReqs = $pdo->query("
    SELECT ib.*, b.title, b.author, u.full_name, u.enrollment_no
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN users u ON u.id = ib.user_id
    WHERE ib.status = 'pending'
    ORDER BY ib.requested_at DESC LIMIT 10
")->fetchAll();

// Recent users
$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Overdue books
$overdueBooks = $pdo->query("
    SELECT ib.*, b.title, u.full_name, u.email,
           DATEDIFF(CURDATE(), ib.due_date) AS days_overdue
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN users u ON u.id = ib.user_id
    WHERE ib.status = 'approved' AND ib.due_date < CURDATE()
    ORDER BY days_overdue DESC LIMIT 10
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
  <!-- ── Admin Sidebar ────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="background:var(--rust);"><i class="fas fa-user-shield" style="font-size:1.1rem;"></i></div>
      <div class="sidebar-name"><?= htmlspecialchars($admin['full_name']) ?></div>
      <div class="sidebar-role" style="color:#f87171;"><i class="fas fa-crown"></i> Administrator</div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Dashboard</div>
      <a href="/admin_dashboard.php"      class="sidebar-nav-item active"><i class="fas fa-tachometer-alt"></i> Overview</a>
      <a href="#pending"                  class="sidebar-nav-item">
        <i class="fas fa-clock"></i> Pending Requests
        <?php if ($stats['pending_req'] > 0): ?>
          <span class="badge" style="position:static;transform:none;margin-left:auto;background:var(--rust);"><?= $stats['pending_req'] ?></span>
        <?php endif; ?>
      </a>
      <a href="#overdue"                  class="sidebar-nav-item"><i class="fas fa-exclamation-triangle"></i> Overdue Books</a>

      <div class="sidebar-section-title">Management</div>
      <a href="/admin/manage_books.php"   class="sidebar-nav-item"><i class="fas fa-books"></i> Manage Books</a>
      <a href="/add_book.php"             class="sidebar-nav-item"><i class="fas fa-plus-circle"></i> Add Book</a>
      <a href="/admin/manage_users.php"   class="sidebar-nav-item"><i class="fas fa-users"></i> Manage Users</a>
      <a href="/admin/reports.php"        class="sidebar-nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
      <a href="/return_book.php"          class="sidebar-nav-item"><i class="fas fa-undo"></i> Return Books</a>

      <div class="sidebar-section-title">System</div>
      <a href="/books.php"                class="sidebar-nav-item"><i class="fas fa-eye"></i> View Library</a>
      <a href="/logout.php"               class="sidebar-nav-item" style="color:rgba(255,100,100,.7) !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <!-- ── Admin Main ───────────────────────────────────────── -->
  <div class="dashboard-main">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
      <h2>Admin Overview</h2>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <a href="/add_book.php"   class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Book</a>
        <a href="/admin/reports.php" class="btn btn-outline btn-sm"><i class="fas fa-chart-bar"></i> Reports</a>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
      <div class="stat-card" style="--stat-color:#c9a84c;--stat-bg:#f5edda;">
        <div class="stat-icon"><i class="fas fa-books"></i></div>
        <div class="stat-info">
          <div class="stat-label">Total Books</div>
          <div class="stat-value count-up" data-target="<?= $stats['total_books'] ?>"><?= $stats['total_books'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#2563eb;--stat-bg:#eff6ff;">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
          <div class="stat-label">Registered Users</div>
          <div class="stat-value count-up" data-target="<?= $stats['total_users'] ?>"><?= $stats['total_users'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#1a7a6e;--stat-bg:#e0f4f1;">
        <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
        <div class="stat-info">
          <div class="stat-label">Currently Issued</div>
          <div class="stat-value count-up" data-target="<?= $stats['issued_now'] ?>"><?= $stats['issued_now'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#7c3aed;--stat-bg:#f5f3ff;">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
          <div class="stat-label">Available Books</div>
          <div class="stat-value count-up" data-target="<?= $stats['total_avail'] ?>"><?= $stats['total_avail'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#e67e22;--stat-bg:#fff7ed;">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
          <div class="stat-label">Pending Requests</div>
          <div class="stat-value count-up" data-target="<?= $stats['pending_req'] ?>"><?= $stats['pending_req'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#c0392b;--stat-bg:#fde8e6;">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
          <div class="stat-label">Overdue Books</div>
          <div class="stat-value count-up" data-target="<?= $stats['overdue'] ?>"><?= $stats['overdue'] ?></div>
        </div>
      </div>
      <div class="stat-card" style="--stat-color:#c0392b;--stat-bg:#fde8e6;">
        <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
        <div class="stat-info">
          <div class="stat-label">Total Fine Due</div>
          <div class="stat-value">₹<?= number_format((float)$stats['total_fine'], 0) ?></div>
        </div>
      </div>
    </div>

    <!-- Pending Requests -->
    <div id="pending" class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-clock" style="color:#e67e22;margin-right:.5rem;"></i> Pending Book Requests</h3>
        <a href="/admin/manage_books.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($pendingReqs)): ?>
          <div class="empty-state" style="padding:2rem;">
            <p><i class="fas fa-check-circle" style="color:var(--teal);"></i> All caught up! No pending requests.</p>
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>#</th><th>Student</th><th>Enrollment</th><th>Book</th><th>Requested</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingReqs as $i => $req): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($req['full_name']) ?></td>
                  <td><span class="pill pill-gold"><?= htmlspecialchars($req['enrollment_no']) ?></span></td>
                  <td><strong><?= htmlspecialchars($req['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($req['author']) ?></small></td>
                  <td><?= date('d M Y', strtotime($req['requested_at'])) ?></td>
                  <td>
                    <div class="td-actions">
                      <a href="/issue_book.php?action=approve&id=<?= $req['id'] ?>&csrf=<?= csrfToken() ?>"
                         class="btn btn-success btn-sm"
                         data-confirm="Approve this request?">
                        <i class="fas fa-check"></i> Approve
                      </a>
                      <a href="/issue_book.php?action=reject&id=<?= $req['id'] ?>&csrf=<?= csrfToken() ?>"
                         class="btn btn-danger btn-sm"
                         data-confirm="Reject this request?">
                        <i class="fas fa-times"></i> Reject
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Overdue Books -->
    <div id="overdue" class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-exclamation-triangle" style="color:var(--rust);margin-right:.5rem;"></i> Overdue Books</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($overdueBooks)): ?>
          <div class="empty-state" style="padding:2rem;"><p>No overdue books. 🎉</p></div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>#</th><th>Student</th><th>Book</th><th>Due Date</th><th>Overdue Days</th><th>Fine</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($overdueBooks as $i => $b):
                  $fine = $b['days_overdue'] * FINE_PER_DAY;
                ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($b['full_name']) ?><br><small class="text-muted"><?= htmlspecialchars($b['email']) ?></small></td>
                  <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                  <td style="color:var(--rust);font-weight:700;"><?= date('d M Y', strtotime($b['due_date'])) ?></td>
                  <td><span class="pill pill-danger"><?= $b['days_overdue'] ?> days</span></td>
                  <td style="color:var(--rust);font-weight:700;">₹<?= number_format($fine, 2) ?></td>
                  <td>
                    <a href="/return_book.php?id=<?= $b['id'] ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-undo"></i> Process Return
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-users" style="color:#2563eb;margin-right:.5rem;"></i> Recent Registrations</h3>
        <a href="/admin/manage_users.php" class="btn btn-outline btn-sm">Manage Users</a>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Name</th><th>Email</th><th>Enrollment</th><th>Department</th><th>Joined</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentUsers as $i => $u): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['enrollment_no']) ?></td>
                <td><?= htmlspecialchars($u['department'] ?: '—') ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <span class="pill <?= $u['status']==='active' ? 'pill-success' : 'pill-danger' ?>">
                    <?= ucfirst($u['status']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /.dashboard-main -->
</div><!-- /.dashboard-layout -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
