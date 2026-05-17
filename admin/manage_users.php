<?php
// ============================================================
// admin/manage_users.php — Admin: Manage Users
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Manage Users';

// Toggle user status
if (isset($_GET['toggle']) && isset($_GET['uid'])) {
    if (!verifyCsrf($_GET['csrf'] ?? '')) {
        setFlash('danger','Invalid token.'); header('Location: /admin/manage_users.php'); exit;
    }
    $uid  = (int)$_GET['uid'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
    $stmt->execute([$uid]);
    $cur  = $stmt->fetchColumn();
    $new  = $cur === 'active' ? 'suspended' : 'active';
    $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$new, $uid]);
    setFlash('success', "User " . ($new==='active'?'activated':'suspended') . " successfully.");
    header('Location: /admin/manage_users.php'); exit;
}

$q       = trim($_GET['q'] ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 15;

$cond   = ['1=1'];
$params = [];
if ($q) {
    $like = "%$q%";
    $cond[] = "(full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    array_push($params, $like, $like, $like);
}
if ($status) { $cond[] = "status=?"; $params[] = $status; }
$where = implode(' AND ', $cond);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page-1)*$perPage;

$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM issued_books WHERE user_id=u.id AND status='approved') AS issued_count FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

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
      <a href="/admin/manage_users.php"  class="sidebar-nav-item active"><i class="fas fa-users"></i> Manage Users</a>
      <a href="/return_book.php"         class="sidebar-nav-item"><i class="fas fa-undo"></i> Return Books</a>
      <a href="/admin/reports.php"       class="sidebar-nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
      <div class="sidebar-section-title">Account</div>
      <a href="/logout.php"              class="sidebar-nav-item" style="color:rgba(255,100,100,.7) !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="dashboard-main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
      <div>
        <h2>Manage Users</h2>
        <p style="margin:0;font-size:.88rem;"><?= number_format($total) ?> registered students</p>
      </div>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar" style="margin-bottom:1.5rem;">
      <div class="search-input-wrap" style="flex:1;">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, email, enrollment no…">
      </div>
      <select name="status" class="form-control" style="min-width:140px;">
        <option value="">All Status</option>
        <option value="active"    <?= $status==='active'   ?'selected':'' ?>>Active</option>
        <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <?php if ($q||$status): ?>
        <a href="/admin/manage_users.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
      <?php endif; ?>
    </form>

    <div class="card">
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Name</th><th>Enrollment</th><th>Department</th><th>Email</th><th>Sem</th><th>Issued</th><th>Joined</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted);">No users found.</td></tr>
              <?php else: ?>
              <?php foreach ($users as $i => $u): ?>
              <tr>
                <td><?= ($page-1)*$perPage + $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                <td><span class="pill pill-gold" style="font-size:.72rem;"><?= htmlspecialchars($u['enrollment_no']) ?></span></td>
                <td><?= htmlspecialchars($u['department'] ?: '—') ?></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                <td style="text-align:center;"><?= $u['semester'] ?></td>
                <td style="text-align:center;"><span class="pill pill-info"><?= $u['issued_count'] ?></span></td>
                <td style="font-size:.82rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <span class="pill <?= $u['status']==='active'?'pill-success':'pill-danger' ?>">
                    <?= ucfirst($u['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="/admin/manage_users.php?toggle=1&uid=<?= $u['id'] ?>&csrf=<?= csrfToken() ?>"
                     class="btn <?= $u['status']==='active'?'btn-danger':'btn-success' ?> btn-sm"
                     data-confirm="<?= $u['status']==='active'?'Suspend':'Activate' ?> this user?">
                    <i class="fas fa-<?= $u['status']==='active'?'ban':'check' ?>"></i>
                    <?= $u['status']==='active'?'Suspend':'Activate' ?>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:.4rem;margin-top:1.5rem;flex-wrap:wrap;">
      <?php for ($p=1;$p<=$totalPages;$p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"
           class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
