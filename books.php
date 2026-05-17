<?php
// ============================================================
// books.php — Browse Books with Search, Filter & AI Smart Search
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Browse Books';

// Input
$q        = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? '');
$avail    = trim($_GET['avail']    ?? '');
$sort     = trim($_GET['sort']     ?? 'newest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;

// View single book
$bookView = null;
if (!empty($_GET['book'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id=?");
    $stmt->execute([(int)$_GET['book']]);
    $bookView = $stmt->fetch();
}

// Categories
$categories = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Build query with smart search (LIKE + FULLTEXT fallback)
$conditions = ["1=1"];
$params     = [];

if ($q !== '') {
    $conditions[] = "(title LIKE ? OR author LIKE ? OR description LIKE ? OR isbn LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like);
}
if ($category !== '') {
    $conditions[] = "category = ?";
    $params[] = $category;
}
if ($avail === 'yes') {
    $conditions[] = "(total_quantity - issued_quantity) > 0";
} elseif ($avail === 'no') {
    $conditions[] = "(total_quantity - issued_quantity) <= 0";
}

$orderMap = [
    'newest'  => 'added_at DESC',
    'oldest'  => 'added_at ASC',
    'title'   => 'title ASC',
    'author'  => 'author ASC',
    'avail'   => '(total_quantity - issued_quantity) DESC',
];
$orderBy = $orderMap[$sort] ?? 'added_at DESC';

$where = implode(' AND ', $conditions);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE $where");
$countStmt->execute($params);
$total     = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch
$offset = ($page - 1) * $perPage;
$stmt   = $pdo->prepare("SELECT * FROM books WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$books  = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page Hero ─────────────────────────────────────────── -->
<div class="page-hero" style="padding:2.5rem 2rem;">
  <h1>📚 <span>Library Collection</span></h1>
  <p>Explore <?= number_format($total) ?> books across <?= count($categories) ?> categories</p>
</div>

<div class="container section">

  <?php if ($bookView): ?>
  <!-- ── Single Book Detail View ───────────────────────── -->
  <div style="margin-bottom:1.5rem;">
    <a href="/books.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Books</a>
  </div>
  <div style="display:grid;grid-template-columns:280px 1fr;gap:2rem;margin-bottom:3rem;" class="book-detail-grid">
    <div>
      <div class="book-cover" style="height:340px;border-radius:var(--radius);box-shadow:var(--shadow-lg);">
        <i class="fas fa-book-open book-cover-icon" style="font-size:5rem;"></i>
        <div class="book-cover-title"><?= htmlspecialchars($bookView['category']) ?></div>
      </div>
    </div>
    <div>
      <span class="book-category"><?= htmlspecialchars($bookView['category']) ?></span>
      <h1 style="margin:.8rem 0 .4rem;font-size:2rem;"><?= htmlspecialchars($bookView['title']) ?></h1>
      <p style="font-size:1rem;color:var(--text-muted);margin-bottom:1rem;">by <strong style="color:var(--text);"><?= htmlspecialchars($bookView['author']) ?></strong></p>

      <?php $avl = $bookView['total_quantity'] - $bookView['issued_quantity']; ?>
      <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem;">
        <span class="book-availability <?= $avl > 0 ? 'avail-yes' : 'avail-no' ?>" style="font-size:.9rem;padding:.3rem .9rem;">
          <?= $avl > 0 ? "✓ $avl Available" : '✕ Out of Stock' ?>
        </span>
        <span class="text-muted" style="font-size:.85rem;"><?= $avl ?> of <?= $bookView['total_quantity'] ?> copies available</span>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem;margin-bottom:1.5rem;">
        <?php
        $meta = [
          ['fas fa-barcode','ISBN', $bookView['isbn'] ?: '—'],
          ['fas fa-building','Publisher', $bookView['publisher'] ?: '—'],
          ['fas fa-calendar','Year', $bookView['publish_year'] ?: '—'],
          ['fas fa-map-marker-alt','Location', $bookView['location'] ?: '—'],
        ];
        foreach ($meta as [$ico,$label,$val]): ?>
        <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:.7rem 1rem;">
          <div style="font-size:.72rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem;"><i class="<?= $ico ?>"></i> <?= $label ?></div>
          <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($bookView['description']): ?>
      <h4 style="margin-bottom:.5rem;">Description</h4>
      <p style="line-height:1.8;"><?= nl2br(htmlspecialchars($bookView['description'])) ?></p>
      <?php endif; ?>

      <div style="display:flex;gap:.8rem;margin-top:1.5rem;flex-wrap:wrap;">
        <?php if (isLoggedIn() && $avl > 0): ?>
          <a href="/issue_book.php?book_id=<?= $bookView['id'] ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-hand-holding-heart"></i> Request Book
          </a>
        <?php elseif (!isLoggedIn()): ?>
          <a href="/login.php" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Login to Request</a>
        <?php else: ?>
          <button class="btn btn-outline btn-lg" disabled><i class="fas fa-times"></i> Unavailable</button>
        <?php endif; ?>
        <button class="btn btn-outline btn-lg" id="getSummary" data-book-id="<?= $bookView['id'] ?>">
          <i class="fas fa-magic"></i> AI Summary
        </button>
      </div>

      <!-- AI Summary Container -->
      <div id="summaryContainer" style="margin-top:1.5rem;"></div>

      <!-- AI Recommendations -->
      <div style="margin-top:2rem;">
        <button class="btn btn-navy" id="getRecommendations" data-book-id="<?= $bookView['id'] ?>">
          <i class="fas fa-lightbulb"></i> Get AI Recommendations
        </button>
        <div id="recommendationsContainer" style="margin-top:1rem;"></div>
      </div>
    </div>
  </div>
  <style>@media(max-width:700px){.book-detail-grid{grid-template-columns:1fr!important}}</style>
  <?php endif; ?>

  <!-- ── Search & Filter Bar ──────────────────────────────── -->
  <form method="GET" class="search-bar">
    <div class="search-input-wrap">
      <i class="fas fa-search search-icon"></i>
      <input type="text" name="q" class="form-control" id="liveSearch"
             value="<?= htmlspecialchars($q) ?>"
             placeholder="Search by title, author, ISBN…">
    </div>
    <select name="category" class="form-control" id="categoryFilter" style="min-width:180px;">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="avail" class="form-control" style="min-width:150px;">
      <option value="">All Status</option>
      <option value="yes" <?= $avail==='yes'?'selected':'' ?>>Available Only</option>
      <option value="no"  <?= $avail==='no' ?'selected':'' ?>>Out of Stock</option>
    </select>
    <select name="sort" class="form-control" style="min-width:150px;">
      <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
      <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
      <option value="title"  <?= $sort==='title' ?'selected':'' ?>>Title A–Z</option>
      <option value="author" <?= $sort==='author'?'selected':'' ?>>Author A–Z</option>
      <option value="avail"  <?= $sort==='avail' ?'selected':'' ?>>Most Available</option>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    <?php if ($q || $category || $avail): ?>
      <a href="/books.php" class="btn btn-outline"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
  </form>

  <!-- Results count -->
  <div style="margin-bottom:1.2rem;color:var(--text-muted);font-size:.88rem;">
    <?php if ($q || $category): ?>
      Showing <strong><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
      <?php if ($q): ?> for "<strong><?= htmlspecialchars($q) ?></strong>"<?php endif; ?>
      <?php if ($category): ?> in <strong><?= htmlspecialchars($category) ?></strong><?php endif; ?>
    <?php else: ?>
      Showing <strong><?= $total ?></strong> books (Page <?= $page ?> of <?= max(1,$totalPages) ?>)
    <?php endif; ?>
    <?php if (isAdmin()): ?>
      &nbsp;|&nbsp; <a href="/add_book.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Book</a>
    <?php endif; ?>
  </div>

  <!-- ── Books Grid ────────────────────────────────────────── -->
  <?php if (empty($books)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-search"></i></div>
      <h3>No Books Found</h3>
      <p>Try different search terms or browse all categories.</p>
      <a href="/books.php" class="btn btn-primary">View All Books</a>
    </div>
  <?php else: ?>
    <div class="books-grid">
      <?php foreach ($books as $book):
        $avl = $book['total_quantity'] - $book['issued_quantity'];
      ?>
      <div class="book-card">
        <div class="book-cover">
          <i class="fas fa-book-open book-cover-icon"></i>
          <div class="book-cover-title"><?= htmlspecialchars($book['category']) ?></div>
        </div>
        <div class="book-info">
          <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
          <div class="book-author"><i class="fas fa-user-pen" style="font-size:.7rem;"></i> <?= htmlspecialchars($book['author']) ?></div>
          <span class="book-category"><?= htmlspecialchars($book['category']) ?></span>
          <div class="book-meta">
            <span class="book-availability <?= $avl > 0 ? 'avail-yes' : 'avail-no' ?>">
              <?= $avl > 0 ? "✓ Available" : '✕ Out of Stock' ?>
            </span>
            <span class="book-qty"><?= $avl ?>/<?= $book['total_quantity'] ?></span>
          </div>
          <?php if ($book['publish_year']): ?>
            <div style="font-size:.75rem;color:var(--text-light);margin-top:.2rem;"><i class="fas fa-calendar"></i> <?= $book['publish_year'] ?></div>
          <?php endif; ?>
        </div>
        <div class="book-actions">
          <a href="/books.php?book=<?= $book['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Details</a>
          <?php if (isAdmin()): ?>
            <a href="/edit_book.php?id=<?= $book['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
          <?php elseif (isLoggedIn() && $avl > 0): ?>
            <a href="/issue_book.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-hand-holding-heart"></i> Request</a>
          <?php elseif (!isLoggedIn()): ?>
            <a href="/login.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
          <?php else: ?>
            <button class="btn btn-outline btn-sm" disabled>Unavailable</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Pagination ─────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;align-items:center;gap:.4rem;margin-top:2.5rem;flex-wrap:wrap;">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-left"></i> Prev</a>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
           class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="btn btn-outline btn-sm">Next <i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

</div><!-- /.container -->

<style>
.ai-summary-box {
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
  border: 1px solid var(--gold);
  border-radius: var(--radius);
  padding: 1.4rem;
  color: #fff;
  line-height: 1.8;
  position: relative;
}
.ai-summary-box::before {
  content: '✨ AI Summary';
  display: block;
  color: var(--gold);
  font-size: .78rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-bottom: .6rem;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
