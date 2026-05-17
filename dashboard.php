<?php
// ============================================================
// dashboard.php — Student Dashboard
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'My Dashboard';
$user      = currentUser();
$userId    = $_SESSION['user_id'];

// AJAX: notification count
if (isset($_GET['notif_count'])) {
    $c = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $c->execute([$userId]);
    header('Content-Type: application/json');
    echo json_encode(['count' => (int)$c->fetchColumn()]);
    exit;
}

// Mark all notifications read when tab opened
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
    header('Location: /dashboard.php'); exit;
}

// Stats
$totalIssued   = $pdo->prepare("SELECT COUNT(*) FROM issued_books WHERE user_id=? AND status='approved'");
$totalIssued->execute([$userId]);
$totalIssued   = (int)$totalIssued->fetchColumn();

$totalPending  = $pdo->prepare("SELECT COUNT(*) FROM issued_books WHERE user_id=? AND status='pending'");
$totalPending->execute([$userId]);
$totalPending  = (int)$totalPending->fetchColumn();

$totalReturned = $pdo->prepare("SELECT COUNT(*) FROM issued_books WHERE user_id=? AND status='returned'");
$totalReturned->execute([$userId]);
$totalReturned = (int)$totalReturned->fetchColumn();

// Fine total
$fineQ = $pdo->prepare("SELECT COALESCE(SUM(fine_amount),0) FROM issued_books WHERE user_id=? AND fine_paid=0 AND status IN ('approved','returned')");
$fineQ->execute([$userId]);
$totalFine = (float)$fineQ->fetchColumn();

// Currently issued books (approved)
$issued = $pdo->prepare("
    SELECT ib.*, b.title, b.author, b.category
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.user_id = ? AND ib.status = 'approved'
    ORDER BY ib.due_date ASC
");
$issued->execute([$userId]);
$issuedBooks = $issued->fetchAll();

// Update fines
foreach ($issuedBooks as &$row) {
    $fine = calculateFine($row);
    if ($fine != $row['fine_amount']) {
        $pdo->prepare("UPDATE issued_books SET fine_amount=? WHERE id=?")->execute([$fine, $row['id']]);
        $row['fine_amount'] = $fine;
    }
}
unset($row);

// Pending requests
$pending = $pdo->prepare("
    SELECT ib.*, b.title, b.author, b.category
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.user_id = ? AND ib.status = 'pending'
    ORDER BY ib.requested_at DESC
");
$pending->execute([$userId]);
$pendingBooks = $pending->fetchAll();

// History (returned)
$history = $pdo->prepare("
    SELECT ib.*, b.title, b.author
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    WHERE ib.user_id = ? AND ib.status IN ('returned','rejected')
    ORDER BY ib.return_date DESC LIMIT 10
");
$history->execute([$userId]);
$historyBooks = $history->fetchAll();

// Notifications
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$notifs->execute([$userId]);
$notifications  = $notifs->fetchAll();
$unreadNotifs   = array_filter($notifications, fn($n) => !$n['is_read']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
  <!-- ── Sidebar ──────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <div class="sidebar-name"><?= htmlspecialchars($user['full_name']) ?></div>
      <div class="sidebar-role">
        <i class="fas fa-graduation-cap"></i>
        <?= htmlspecialchars($user['department'] ?: 'Student') ?> · Sem <?= $user['semester'] ?>
      </div>
      <div style="font-size:.75rem;color:rgba(255,255,255,.35);margin-top:.3rem;">
        <?= htmlspecialchars($user['enrollment_no']) ?>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-title">Main</div>
      <a href="#overview"       class="sidebar-nav-item active"><i class="fas fa-tachometer-alt"></i> Overview</a>
      <a href="#issued"         class="sidebar-nav-item"><i class="fas fa-book-reader"></i> Issued Books</a>
      <a href="#pending"        class="sidebar-nav-item"><i class="fas fa-clock"></i> Pending Requests</a>
      <a href="#history"        class="sidebar-nav-item"><i class="fas fa-history"></i> History</a>

      <div class="sidebar-section-title">Library</div>
      <a href="/books.php"      class="sidebar-nav-item"><i class="fas fa-books"></i> Browse Books</a>
      <a href="/chatbot.php"    class="sidebar-nav-item"><i class="fas fa-robot"></i> AI Assistant</a>
      <a href="/recommend.php"  class="sidebar-nav-item"><i class="fas fa-lightbulb"></i> Recommendations</a>

      <div class="sidebar-section-title">Account</div>
      <a href="#notifications"  class="sidebar-nav-item">
        <i class="fas fa-bell"></i> Notifications
        <?php if (count($unreadNotifs) > 0): ?>
          <span class="badge" style="position:static;transform:none;margin-left:auto;"><?= count($unreadNotifs) ?></span>
        <?php endif; ?>
      </a>
      <a href="/logout.php"     class="sidebar-nav-item" style="color:rgba(255,100,100,.7) !important;">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </nav>
  </aside>

  <!-- ── Main Content ─────────────────────────────────────── -->
  <div class="dashboard-main">

    <!-- Overview Stats -->
    <div id="overview" style="margin-bottom:2rem;">
      <h2 style="margin-bottom:1.2rem;">
        Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>,
        <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> 👋
      </h2>
      <div class="stats-grid">
        <div class="stat-card" style="--stat-color:#c9a84c;--stat-bg:#f5edda;">
          <div class="stat-icon"><i class="fas fa-book-open"></i></div>
          <div class="stat-info">
            <div class="stat-label">Currently Issued</div>
            <div class="stat-value count-up" data-target="<?= $totalIssued ?>"><?= $totalIssued ?></div>
            <div class="stat-sub">books in hand</div>
          </div>
        </div>
        <div class="stat-card" style="--stat-color:#2563eb;--stat-bg:#eff6ff;">
          <div class="stat-icon"><i class="fas fa-clock"></i></div>
          <div class="stat-info">
            <div class="stat-label">Pending Requests</div>
            <div class="stat-value count-up" data-target="<?= $totalPending ?>"><?= $totalPending ?></div>
            <div class="stat-sub">awaiting approval</div>
          </div>
        </div>
        <div class="stat-card" style="--stat-color:#1a7a6e;--stat-bg:#e0f4f1;">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info">
            <div class="stat-label">Books Returned</div>
            <div class="stat-value count-up" data-target="<?= $totalReturned ?>"><?= $totalReturned ?></div>
            <div class="stat-sub">all time</div>
          </div>
        </div>
        <div class="stat-card" style="--stat-color:<?= $totalFine > 0 ? '#c0392b' : '#1a7a6e' ?>;--stat-bg:<?= $totalFine > 0 ? '#fde8e6' : '#e0f4f1' ?>;">
          <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
          <div class="stat-info">
            <div class="stat-label">Total Fine Due</div>
            <div class="stat-value">₹<?= number_format($totalFine, 2) ?></div>
            <div class="stat-sub"><?= $totalFine > 0 ? 'please pay at desk' : 'no dues' ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Currently Issued Books -->
    <div id="issued" class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-book-reader" style="color:var(--gold);margin-right:.5rem;"></i> Currently Issued Books</h3>
        <a href="/books.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Request Book</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($issuedBooks)): ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-book"></i></div>
            <h3>No Books Issued</h3>
            <p>You haven't borrowed any books yet. Browse our collection and request a book!</p>
            <a href="/books.php" class="btn btn-primary"><i class="fas fa-search"></i> Browse Books</a>
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Category</th>
                  <th>Issue Date</th>
                  <th>Due Date</th>
                  <th>Fine</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($issuedBooks as $i => $b):
                  $isOverdue = date('Y-m-d') > $b['due_date'];
                  $daysLeft  = ceil((strtotime($b['due_date']) - time()) / 86400);
                ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                  <td><?= htmlspecialchars($b['author']) ?></td>
                  <td><span class="pill pill-gold"><?= htmlspecialchars($b['category']) ?></span></td>
                  <td><?= date('d M Y', strtotime($b['issue_date'])) ?></td>
                  <td>
                    <span style="color:<?= $isOverdue ? 'var(--rust)' : ($daysLeft <= 3 ? '#e67e22' : 'inherit') ?>;font-weight:<?= $isOverdue ? '700' : '400' ?>">
                      <?= date('d M Y', strtotime($b['due_date'])) ?>
                      <?php if ($isOverdue): ?>
                        <small style="display:block;color:var(--rust);"><?= abs($daysLeft) ?> days overdue</small>
                      <?php elseif ($daysLeft <= 3): ?>
                        <small style="display:block;color:#e67e22;">Due in <?= $daysLeft ?> day(s)</small>
                      <?php endif; ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($b['fine_amount'] > 0): ?>
                      <span class="text-danger font-weight-bold">₹<?= number_format($b['fine_amount'],2) ?></span>
                    <?php else: ?>
                      <span class="text-success">₹0.00</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isOverdue): ?>
                      <span class="pill pill-danger"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                    <?php elseif ($daysLeft <= 3): ?>
                      <span class="pill pill-warning"><i class="fas fa-clock"></i> Due Soon</span>
                    <?php else: ?>
                      <span class="pill pill-success"><i class="fas fa-check"></i> On Time</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pending Requests -->
    <div id="pending" class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-hourglass-half" style="color:#2563eb;margin-right:.5rem;"></i> Pending Book Requests</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($pendingBooks)): ?>
          <div class="empty-state" style="padding:2rem;">
            <p>No pending requests.</p>
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>#</th><th>Title</th><th>Author</th><th>Category</th><th>Requested On</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingBooks as $i => $b): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                  <td><?= htmlspecialchars($b['author']) ?></td>
                  <td><span class="pill pill-gold"><?= htmlspecialchars($b['category']) ?></span></td>
                  <td><?= date('d M Y, h:i A', strtotime($b['requested_at'])) ?></td>
                  <td><span class="pill pill-info"><i class="fas fa-clock"></i> Pending</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- History -->
    <div id="history" class="card mb-4">
      <div class="card-header">
        <h3><i class="fas fa-history" style="color:var(--teal);margin-right:.5rem;"></i> Borrowing History</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($historyBooks)): ?>
          <div class="empty-state" style="padding:2rem;"><p>No history yet.</p></div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>#</th><th>Title</th><th>Author</th><th>Issued</th><th>Returned</th><th>Fine</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($historyBooks as $i => $b): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                  <td><?= htmlspecialchars($b['author']) ?></td>
                  <td><?= $b['issue_date'] ? date('d M Y', strtotime($b['issue_date'])) : '—' ?></td>
                  <td><?= $b['return_date'] ? date('d M Y', strtotime($b['return_date'])) : '—' ?></td>
                  <td><?= $b['fine_amount'] > 0 ? '₹'.number_format($b['fine_amount'],2) : '₹0.00' ?></td>
                  <td>
                    <?php if ($b['status'] === 'returned'): ?>
                      <span class="pill pill-success">Returned</span>
                    <?php else: ?>
                      <span class="pill pill-danger">Rejected</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notifications -->
    <div id="notifications" class="card">
      <div class="card-header">
        <h3><i class="fas fa-bell" style="color:var(--gold);margin-right:.5rem;"></i> Notifications</h3>
        <?php if (count($unreadNotifs) > 0): ?>
          <a href="/dashboard.php?mark_read=1" class="btn btn-outline btn-sm">Mark All Read</a>
        <?php endif; ?>
      </div>
      <?php if (empty($notifications)): ?>
        <div class="empty-state" style="padding:2rem;"><p>No notifications yet.</p></div>
      <?php else: ?>
        <?php foreach ($notifications as $n):
          $icons = ['info'=>'info-circle','success'=>'check-circle','warning'=>'exclamation-triangle','danger'=>'exclamation-circle'];
          $colors = ['info'=>'#2563eb','success'=>'var(--teal)','warning'=>'#e67e22','danger'=>'var(--rust)'];
          $icon  = $icons[$n['type']] ?? 'info-circle';
          $color = $colors[$n['type']] ?? '#2563eb';
        ?>
        <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
          <div class="notif-icon" style="background:<?= $color ?>22;color:<?= $color ?>;">
            <i class="fas fa-<?= $icon ?>"></i>
          </div>
          <div class="notif-text">
            <p><?= htmlspecialchars($n['message']) ?></p>
            <time><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></time>
          </div>
          <?php if (!$n['is_read']): ?>
            <span style="width:8px;height:8px;border-radius:50%;background:#2563eb;flex-shrink:0;margin-top:.4rem;"></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- /.dashboard-main -->
</div><!-- /.dashboard-layout -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
