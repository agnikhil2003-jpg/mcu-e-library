<?php
// ============================================================
// admin/manage_books.php — Admin: Manage All Books
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Manage Books';

$q        = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;

$categories = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$conditions = ['1=1'];
$params     = [];
if ($q) {
    $conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like);
}
if ($category) {
    $conditions[] = "category=?";
    $params[] = $category;
}
$where = implode(' AND ', $conditions);

$total      = (int)$pdo->prepare("SELECT COUNT(*) FROM books WHERE $where")->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM books WHERE $where")->execute($params) : 0;
$countStmt  = $pdo->prepare("SELECT COUNT(*) FROM books WHERE $where");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM books WHERE $where ORDER BY added_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books = $stmt->fetchAll();

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
      <a href="/admin/manage_books.php"  class="sidebar-nav-item active"><i class="fas fa-books"></i> Manage Books</a>
      <a href="/add_book.php"            class="sidebar-nav-item"><i class="fas fa-plus-circle"></i> Add Book</a>
      <a href="/admin/manage_users.php"  class="sidebar-nav-item"><i class="fas fa-users"></i> Manage Users</a>
      <a href="/return_book.php"         class="sidebar-nav-item"><i class="fas fa-undo"></i> Return Books</a>
      <a href="/admin/reports.php"       class="sidebar-nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
      <div class="sidebar-section-title">Account</div>
      <a href="/logout.php"              class="sidebar-nav-item" style="color:rgba(255,100,100,.7) !important;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="dashboard-main">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
      <div>
        <h2>Manage Books</h2>
        <p style="margin:0;font-size:.88rem;"><?= number_format($total) ?> books total</p>
      </div>
      <a href="/add_book.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Book</a>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar" style="margin-bottom:1.5rem;">
      <div class="search-input-wrap" style="flex:1;">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search by title, author, ISBN…">
      </div>
      <select name="category" class="form-control" style="min-width:160px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $category===$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <?php if ($q || $category): ?>
        <a href="/admin/manage_books.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
      <?php endif; ?>
    </form>

    <!-- Books Table -->
    <div class="card">
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Total</th>
                <th>Issued</th>
                <th>Available</th>
                <th>Year</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($books)): ?>
                <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No books found.</td></tr>
              <?php else: ?>
                <?php foreach ($books as $i => $b):
                  $avl = $b['total_quantity'] - $b['issued_quantity'];
                ?>
                <tr>
                  <td><?= ($page-1)*$perPage + $i + 1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars(mb_strimwidth($b['title'],0,45,'…')) ?></strong>
                    <?php if ($b['isbn']): ?><br><small class="text-muted"><?= htmlspecialchars($b['isbn']) ?></small><?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($b['author']) ?></td>
                  <td><span class="pill pill-gold"><?= htmlspecialchars($b['category']) ?></span></td>
                  <td style="text-align:center;font-weight:700;"><?= $b['total_quantity'] ?></td>
                  <td style="text-align:center;color:var(--rust);"><?= $b['issued_quantity'] ?></td>
                  <td style="text-align:center;">
                    <span class="pill <?= $avl>0?'pill-success':'pill-danger' ?>"><?= $avl ?></span>
                  </td>
                  <td><?= $b['publish_year'] ?: '—' ?></td>
                  <td>
                    <div class="td-actions">
                      <a href="/books.php?book=<?= $b['id'] ?>" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                      <a href="/edit_book.php?id=<?= $b['id'] ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                      <a href="/delete_book.php?id=<?= $b['id'] ?>&csrf=<?= csrfToken() ?>"
                         class="btn btn-danger btn-sm" title="Delete"
                         data-confirm="Delete '<?= addslashes($b['title']) ?>'? This cannot be undone.">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:.4rem;margin-top:1.5rem;flex-wrap:wrap;">
      <?php for ($p=1; $p<=$totalPages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"
           class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
