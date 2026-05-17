<?php
// ============================================================
// summary.php — AI Book Summary Generator
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// ── AJAX Summary Generation ──────────────────────────────────
if (isset($_GET['ajax']) && isset($_GET['book_id'])) {
    header('Content-Type: application/json');

    $bookId = (int)$_GET['book_id'];
    $stmt   = $pdo->prepare("SELECT * FROM books WHERE id=?");
    $stmt->execute([$bookId]);
    $book   = $stmt->fetch();

    if (!$book) { echo json_encode(['summary' => 'Book not found.']); exit; }

    $apiKey = ANTHROPIC_API_KEY;

    if ($apiKey === 'YOUR_ANTHROPIC_API_KEY_HERE') {
        // Fallback demo summary
        $summary = generateDemoSummary($book);
        echo json_encode(['summary' => $summary]); exit;
    }

    $prompt = "Generate a helpful academic summary (150-200 words) for the book:
Title: {$book['title']}
Author: {$book['author']}
Category: {$book['category']}
Publisher: {$book['publisher']}
Year: {$book['publish_year']}
Description: {$book['description']}

Write a structured summary covering:
1. What this book is about
2. Key topics covered
3. Who should read it
4. Why it's important in this field

Keep it informative and suitable for students deciding whether to read this book.";

    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 400,
        'messages'   => [['role' => 'user', 'content' => $prompt]]
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: '.$apiKey, 'anthropic-version: 2023-06-01'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) { echo json_encode(['summary' => 'Could not connect to AI service.']); exit; }

    $data    = json_decode($res, true);
    $summary = $data['content'][0]['text'] ?? 'Could not generate summary.';
    // Convert markdown to simple HTML
    $summary = nl2br(htmlspecialchars($summary));
    echo json_encode(['summary' => $summary]); exit;
}

function generateDemoSummary(array $book): string {
    $year  = $book['publish_year'] ? " ({$book['publish_year']})" : '';
    $desc  = $book['description'] ? htmlspecialchars($book['description']) . '<br><br>' : '';
    return "{$desc}<strong>{$book['title']}</strong>{$year} by <em>{$book['author']}</em> is a key text in the field of {$book['category']}. "
         . "Published by {$book['publisher']}, this book provides comprehensive coverage of core concepts that students and researchers in this domain will find invaluable. "
         . "It is particularly recommended for students who want a structured and in-depth understanding of the subject matter. "
         . "The book balances theory with practical examples, making it accessible for both beginners and advanced readers. "
         . "It is widely used in academic courses and is a valuable addition to any student's reading list in {$book['category']}.";
}

// ── Full Summary Page ────────────────────────────────────────
$pageTitle = 'AI Book Summary';
$bookId    = (int)($_GET['book_id'] ?? 0);

$book = null;
if ($bookId) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id=?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section" style="max-width:860px;">

  <div class="page-header" style="padding:0 0 1.5rem;">
    <div>
      <h1><i class="fas fa-magic" style="color:var(--gold);margin-right:.5rem;"></i> AI Book Summary</h1>
      <p>Get an instant AI-generated summary of any book in our collection.</p>
    </div>
    <a href="/books.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Browse Books</a>
  </div>

  <?php if ($book): ?>
  <div style="display:grid;grid-template-columns:200px 1fr;gap:2rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:2rem;" class="summary-grid">
    <div style="background:linear-gradient(135deg,var(--navy),var(--navy-light));display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1rem;gap:.8rem;">
      <i class="fas fa-book" style="font-size:3rem;color:var(--gold);"></i>
      <div style="text-align:center;">
        <div style="color:rgba(255,255,255,.5);font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Category</div>
        <div style="color:var(--gold);font-weight:600;font-size:.85rem;margin-top:.2rem;"><?= htmlspecialchars($book['category']) ?></div>
      </div>
    </div>
    <div style="padding:2rem;">
      <h2 style="margin-bottom:.4rem;font-size:1.5rem;"><?= htmlspecialchars($book['title']) ?></h2>
      <p style="color:var(--text-muted);margin-bottom:1rem;">by <strong><?= htmlspecialchars($book['author']) ?></strong>
        <?php if ($book['publish_year']): ?> · <?= $book['publish_year'] ?><?php endif; ?>
      </p>

      <button class="btn btn-primary btn-lg" id="getSummary" data-book-id="<?= $book['id'] ?>" style="margin-bottom:1.5rem;">
        <i class="fas fa-magic"></i> Generate AI Summary
      </button>

      <div id="summaryContainer"></div>
    </div>
  </div>
  <style>@media(max-width:600px){.summary-grid{grid-template-columns:1fr!important}}</style>
  <?php else: ?>

  <!-- Book Selector -->
  <div class="card">
    <div class="card-header"><h3>Select a Book</h3></div>
    <div class="card-body">
      <form method="GET" style="display:flex;gap:.8rem;flex-wrap:wrap;">
        <select name="book_id" class="form-control" style="flex:1;min-width:200px;">
          <option value="">-- Choose a Book --</option>
          <?php
          $allBooks = $pdo->query("SELECT id,title,author,category FROM books ORDER BY category,title")->fetchAll();
          $lastCat  = '';
          foreach ($allBooks as $b):
            if ($b['category'] !== $lastCat) {
              if ($lastCat) echo '</optgroup>';
              echo '<optgroup label="' . htmlspecialchars($b['category']) . '">';
              $lastCat = $b['category'];
            }
          ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['author']) ?></option>
          <?php endforeach; if ($lastCat) echo '</optgroup>'; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-magic"></i> Get Summary</button>
      </form>
    </div>
  </div>

  <?php endif; ?>

  <!-- How It Works -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:2rem;" class="how-grid">
    <div style="text-align:center;padding:1.5rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
      <div style="font-size:2rem;margin-bottom:.5rem;">🔍</div>
      <h4 style="font-size:.95rem;margin-bottom:.3rem;">1. Select Book</h4>
      <p style="font-size:.82rem;">Choose any book from our library collection.</p>
    </div>
    <div style="text-align:center;padding:1.5rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
      <div style="font-size:2rem;margin-bottom:.5rem;">✨</div>
      <h4 style="font-size:.95rem;margin-bottom:.3rem;">2. AI Generates</h4>
      <p style="font-size:.82rem;">Claude AI creates an instant academic summary.</p>
    </div>
    <div style="text-align:center;padding:1.5rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
      <div style="font-size:2rem;margin-bottom:.5rem;">📚</div>
      <h4 style="font-size:.95rem;margin-bottom:.3rem;">3. Decide & Borrow</h4>
      <p style="font-size:.82rem;">Use the summary to decide if the book suits you.</p>
    </div>
  </div>
  <style>@media(max-width:600px){.how-grid{grid-template-columns:1fr!important}}</style>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
