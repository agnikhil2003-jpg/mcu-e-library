<?php
// ============================================================
// recommend.php — AI Book Recommendation System
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'AI Book Recommendations';

// ── AJAX Request ─────────────────────────────────────────────
if (isset($_GET['ajax']) && isset($_GET['book_id'])) {
    header('Content-Type: application/json');

    $bookId = (int)$_GET['book_id'];
    $book   = $pdo->prepare("SELECT * FROM books WHERE id=?");
    $book->execute([$bookId]);
    $book = $book->fetch();

    if (!$book) { echo json_encode(['html' => '<p class="text-danger">Book not found.</p>']); exit; }

    // Get all other books for context
    $allBooks = $pdo->prepare("SELECT * FROM books WHERE id != ? ORDER BY category, title");
    $allBooks->execute([$bookId]);
    $allBooks = $allBooks->fetchAll();
    $booksList = implode("\n", array_map(fn($b) => "ID:{$b['id']} | {$b['title']} by {$b['author']} [{$b['category']}]", $allBooks));

    $apiKey = ANTHROPIC_API_KEY;
    $similar = [];

    if ($apiKey !== 'YOUR_ANTHROPIC_API_KEY_HERE') {
        $prompt = "I have a book: \"{$book['title']}\" by {$book['author']} in category \"{$book['category']}\".\n\nFrom this library collection:\n$booksList\n\nRecommend exactly 4 most similar/related books. Return ONLY a JSON array of IDs like: [3, 7, 12, 5]. No other text.";

        $payload = json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 100,
            'messages'   => [['role' => 'user', 'content' => $prompt]]
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: '.$apiKey, 'anthropic-version: 2023-06-01'],
            CURLOPT_TIMEOUT => 20,
        ]);
        $res  = curl_exec($ch); curl_close($ch);
        $data = json_decode($res, true);
        $text = $data['content'][0]['text'] ?? '[]';
        preg_match('/\[[\d,\s]+\]/', $text, $m);
        $ids = json_decode($m[0] ?? '[]', true);

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $similar = $stmt->fetchAll();
        }
    }

    // Fallback: same category
    if (empty($similar)) {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE category=? AND id!=? ORDER BY RAND() LIMIT 4");
        $stmt->execute([$book['category'], $bookId]);
        $similar = $stmt->fetchAll();
    }

    // Build HTML
    $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-top:1rem;">';
    foreach ($similar as $b) {
        $avl = $b['total_quantity'] - $b['issued_quantity'];
        $html .= '<div class="book-card" style="font-size:.85rem;">'
            . '<div class="book-cover" style="height:120px;"><i class="fas fa-book-open book-cover-icon" style="font-size:2rem;"></i><div class="book-cover-title">' . htmlspecialchars($b['category']) . '</div></div>'
            . '<div class="book-info"><div class="book-title" style="font-size:.9rem;">' . htmlspecialchars($b['title']) . '</div>'
            . '<div class="book-author">' . htmlspecialchars($b['author']) . '</div>'
            . '<div class="book-meta"><span class="book-availability ' . ($avl > 0 ? 'avail-yes' : 'avail-no') . '">' . ($avl > 0 ? '✓ Available' : '✕ Out of Stock') . '</span></div></div>'
            . '<div class="book-actions"><a href="/books.php?book=' . $b['id'] . '" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a></div></div>';
    }
    $html .= '</div>';

    echo json_encode(['html' => $html]); exit;
}

// ── Full Page Recommendations ────────────────────────────────
// Based on user's history (if logged in) or popular books
$recommendations = [];
$reason          = 'Popular Books';

if (isLoggedIn()) {
    // Find categories the user has borrowed
    $cats = $pdo->prepare("SELECT DISTINCT b.category FROM issued_books ib JOIN books b ON b.id=ib.book_id WHERE ib.user_id=? LIMIT 3");
    $cats->execute([$_SESSION['user_id']]);
    $userCats = $cats->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userCats)) {
        $placeholders = implode(',', array_fill(0, count($userCats), '?'));
        $stmt = $pdo->prepare("SELECT * FROM books WHERE category IN ($placeholders) AND (total_quantity-issued_quantity)>0 ORDER BY RAND() LIMIT 8");
        $stmt->execute($userCats);
        $recommendations = $stmt->fetchAll();
        $reason = 'Based on Your Reading History';
    }
}

if (empty($recommendations)) {
    // Most available books
    $recommendations = $pdo->query("SELECT * FROM books WHERE (total_quantity-issued_quantity)>0 ORDER BY RAND() LIMIT 8")->fetchAll();
    $reason = 'Recommended for You';
}

// Category picks
$catPicks = [];
$cats = $pdo->query("SELECT DISTINCT category FROM books ORDER BY RAND() LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
foreach ($cats as $cat) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE category=? AND (total_quantity-issued_quantity)>0 ORDER BY RAND() LIMIT 1");
    $stmt->execute([$cat]);
    $pick = $stmt->fetch();
    if ($pick) $catPicks[$cat] = $pick;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-hero" style="padding:2.5rem 2rem;">
  <span class="tag" style="background:rgba(201,168,76,.2);color:var(--gold);border:1px solid rgba(201,168,76,.3);display:inline-block;padding:.3rem .9rem;border-radius:99px;font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.8rem;">
    <i class="fas fa-robot"></i> &nbsp;AI Powered
  </span>
  <h1>Smart Book <span style="color:var(--gold);">Recommendations</span></h1>
  <p>Personalized suggestions based on your interests and reading history</p>
</div>

<div class="container section">

  <!-- Interest-based Recommendations -->
  <div class="section-header" style="text-align:left;margin-bottom:1.5rem;">
    <span class="tag"><i class="fas fa-lightbulb"></i> <?= htmlspecialchars($reason) ?></span>
    <h2>Books You Might Love</h2>
  </div>

  <div class="books-grid" style="margin-bottom:3rem;">
    <?php foreach ($recommendations as $book):
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
            <?= $avl > 0 ? '✓ Available' : '✕ Out of Stock' ?>
          </span>
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

  <!-- Category Spotlight -->
  <div class="section-header" style="text-align:left;margin-bottom:1.5rem;">
    <span class="tag"><i class="fas fa-star"></i> Category Spotlight</span>
    <h2>Explore by Subject</h2>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.2rem;margin-bottom:2rem;">
    <?php foreach ($catPicks as $cat => $book):
      $avl = $book['total_quantity'] - $book['issued_quantity'];
      $icons = ['Computer Science'=>'fas fa-laptop-code','Database'=>'fas fa-database','Networking'=>'fas fa-network-wired',
                'Artificial Intelligence'=>'fas fa-brain','Mathematics'=>'fas fa-calculator','Physics'=>'fas fa-atom',
                'Software Engineering'=>'fas fa-code','Web Development'=>'fas fa-globe','Electronics'=>'fas fa-microchip'];
      $icon = $icons[$cat] ?? 'fas fa-book';
    ?>
    <div class="card" style="display:flex;gap:1rem;align-items:flex-start;padding:1.2rem;">
      <div style="width:50px;height:50px;background:linear-gradient(135deg,var(--navy),var(--navy-light));border-radius:12px;display:grid;place-items:center;flex-shrink:0;">
        <i class="<?= $icon ?>" style="color:var(--gold);font-size:1.2rem;"></i>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem;"><?= htmlspecialchars($cat) ?></div>
        <h4 style="font-size:.95rem;margin-bottom:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($book['title']) ?></h4>
        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;"><?= htmlspecialchars($book['author']) ?></div>
        <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
          <span class="book-availability <?= $avl>0?'avail-yes':'avail-no' ?>" style="font-size:.72rem;"><?= $avl>0?'✓ Available':'✕ Out of Stock' ?></span>
          <a href="/books.php?book=<?= $book['id'] ?>" style="font-size:.78rem;color:var(--gold-dark);font-weight:600;">View →</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:1rem;">
    <a href="/books.php" class="btn btn-navy btn-lg"><i class="fas fa-books"></i> Browse All Books</a>
    <a href="/chatbot.php" class="btn btn-outline btn-lg" style="margin-left:.8rem;"><i class="fas fa-robot"></i> Ask AI Assistant</a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
