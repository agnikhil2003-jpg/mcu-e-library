<?php
// ============================================================
// search.php — Smart Search (typo-tolerant, AJAX-ready)
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// ── AJAX Autocomplete ────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $q    = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit; }

    $like = "%$q%";
    $stmt = $pdo->prepare("SELECT id, title, author, category, (total_quantity-issued_quantity) AS avail
                            FROM books
                            WHERE title LIKE ? OR author LIKE ?
                            ORDER BY avail DESC, title
                            LIMIT 8");
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
    echo json_encode($results); exit;
}

// ── Soundex / Fuzzy Search helper ───────────────────────────
function fuzzySearch(PDO $pdo, string $q): array {
    // 1. Direct LIKE match
    $like  = "%$q%";
    $stmt  = $pdo->prepare("SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR description LIKE ? ORDER BY (total_quantity-issued_quantity) DESC LIMIT 20");
    $stmt->execute([$like, $like, $like]);
    $direct = $stmt->fetchAll();
    if (!empty($direct)) return $direct;

    // 2. Soundex match on individual words (handles spelling mistakes)
    $words   = preg_split('/\s+/', $q);
    $results = [];
    foreach ($words as $word) {
        if (strlen($word) < 3) continue;
        $sdx  = soundex($word);
        $stmt = $pdo->prepare("SELECT * FROM books WHERE SOUNDEX(title) LIKE ? OR SOUNDEX(author) LIKE ? LIMIT 10");
        $stmt->execute(["$sdx%", "$sdx%"]);
        $results = array_merge($results, $stmt->fetchAll());
    }

    // Deduplicate
    $seen = []; $unique = [];
    foreach ($results as $r) {
        if (!isset($seen[$r['id']])) { $seen[$r['id']] = true; $unique[] = $r; }
    }
    return $unique;
}

$pageTitle = 'Search Results';
$q         = trim($_GET['q'] ?? '');
$results   = [];
$didYouMean = '';

if ($q !== '') {
    $results = fuzzySearch($pdo, $q);

    // If no results, check if it might be AI-generated smart query
    if (empty($results) && defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== 'YOUR_ANTHROPIC_API_KEY_HERE') {
        // Use AI to suggest corrected query
        $payload = json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 50,
            'messages'   => [['role' => 'user', 'content' => "A user searched for: \"$q\" in a library. Suggest one corrected/alternative search query in 1-5 words only. Reply ONLY with the query, nothing else."]]
        ]);
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.ANTHROPIC_API_KEY,'anthropic-version: 2023-06-01'],CURLOPT_TIMEOUT=>10]);
        $res  = curl_exec($ch); curl_close($ch);
        $data = json_decode($res, true);
        $suggested = trim($data['content'][0]['text'] ?? '');
        if ($suggested && strtolower($suggested) !== strtolower($q)) {
            $didYouMean = $suggested;
            $results    = fuzzySearch($pdo, $suggested);
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero" style="padding:2rem;">
  <h1>🔍 Smart Search</h1>
  <p>Typo-tolerant search across all books</p>
</div>

<div class="container section">

  <form method="GET" class="search-bar" style="margin-bottom:2rem;">
    <div class="search-input-wrap" style="flex:1;">
      <i class="fas fa-search search-icon"></i>
      <input type="text" name="q" id="searchInput" class="form-control"
             value="<?= htmlspecialchars($q) ?>"
             placeholder="Search books by title, author, subject…"
             autocomplete="off">
      <div id="autocompleteDropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--bg-card);border:1px solid var(--border);border-radius:0 0 var(--radius-sm) var(--radius-sm);z-index:100;box-shadow:var(--shadow);max-height:300px;overflow-y:auto;"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-search"></i> Search</button>
  </form>

  <?php if ($q): ?>

    <?php if ($didYouMean): ?>
    <div class="flash-message flash-info" style="border-radius:8px;margin-bottom:1.5rem;">
      <i class="fas fa-lightbulb"></i>
      No results for "<strong><?= htmlspecialchars($q) ?></strong>". Showing results for
      "<a href="/search.php?q=<?= urlencode($didYouMean) ?>" style="font-weight:700;color:var(--gold-dark);"><?= htmlspecialchars($didYouMean) ?></a>" instead.
    </div>
    <?php endif; ?>

    <p style="margin-bottom:1.2rem;color:var(--text-muted);">
      Found <strong><?= count($results) ?></strong> result<?= count($results)!==1?'s':'' ?>
      for "<strong><?= htmlspecialchars($didYouMean ?: $q) ?></strong>"
    </p>

    <?php if (empty($results)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-search"></i></div>
        <h3>No Results Found</h3>
        <p>Try different keywords, check spelling, or browse all categories.</p>
        <a href="/books.php" class="btn btn-primary">Browse All Books</a>
      </div>
    <?php else: ?>
      <div class="books-grid">
        <?php foreach ($results as $book):
          $avl = $book['total_quantity'] - $book['issued_quantity'];
        ?>
        <div class="book-card">
          <div class="book-cover"><i class="fas fa-book-open book-cover-icon"></i><div class="book-cover-title"><?= htmlspecialchars($book['category']) ?></div></div>
          <div class="book-info">
            <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
            <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
            <span class="book-category"><?= htmlspecialchars($book['category']) ?></span>
            <div class="book-meta">
              <span class="book-availability <?= $avl>0?'avail-yes':'avail-no' ?>"><?= $avl>0?'✓ Available':'✕ Out of Stock' ?></span>
              <span class="book-qty"><?= $avl ?>/<?= $book['total_quantity'] ?></span>
            </div>
          </div>
          <div class="book-actions">
            <a href="/books.php?book=<?= $book['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
            <?php if (isLoggedIn() && $avl > 0): ?>
              <a href="/issue_book.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-hand-holding-heart"></i> Request</a>
            <?php elseif (!isLoggedIn()): ?>
              <a href="/login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-search"></i></div>
      <h3>Start Searching</h3>
      <p>Enter a book title, author name, or subject above to find books in our collection.</p>
    </div>
  <?php endif; ?>

</div>

<script>
// Autocomplete dropdown
const searchInput = document.getElementById('searchInput');
const dropdown    = document.getElementById('autocompleteDropdown');

searchInput?.addEventListener('input', async function () {
  const q = this.value.trim();
  if (q.length < 2) { dropdown.style.display='none'; return; }

  try {
    const res  = await fetch('/search.php?ajax=1&q=' + encodeURIComponent(q));
    const data = await res.json();

    if (!data.length) { dropdown.style.display='none'; return; }

    dropdown.innerHTML = data.map(b => `
      <a href="/books.php?book=${b.id}" style="display:flex;align-items:center;gap:.8rem;padding:.7rem 1rem;border-bottom:1px solid var(--border);color:var(--text);">
        <i class="fas fa-book" style="color:var(--gold);flex-shrink:0;"></i>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${b.title}</div>
          <div style="font-size:.78rem;color:var(--text-muted);">${b.author} · ${b.category}</div>
        </div>
        <span style="font-size:.72rem;color:${b.avail>0?'var(--teal)':'var(--rust)'};font-weight:600;flex-shrink:0;">
          ${b.avail>0?'✓ Available':'Out of Stock'}
        </span>
      </a>`).join('');

    dropdown.style.display = 'block';
  } catch {}
});

document.addEventListener('click', e => {
  if (!searchInput?.contains(e.target)) dropdown.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
