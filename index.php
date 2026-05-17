<?php
// ============================================================
// index.php — Home Page
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';

// Stats for hero section
$totalBooks    = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalIssued   = $pdo->query("SELECT COUNT(*) FROM issued_books WHERE status='approved'")->fetchColumn();
$totalAvail    = $pdo->query("SELECT SUM(total_quantity - issued_quantity) FROM books")->fetchColumn();

// Featured / recent books
$featured = $pdo->query("SELECT * FROM books ORDER BY added_at DESC LIMIT 8")->fetchAll();

// Categories for quick filter
$categories = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="hero-section">
  <div class="hero-content">
    <div class="hero-text" data-aos="fade-right">
      <p class="tag" style="display:inline-block;margin-bottom:1rem;background:rgba(201,168,76,.15);color:var(--gold);padding:.3rem .9rem;border-radius:99px;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border:1px solid rgba(201,168,76,.3);">
        <i class="fas fa-university"></i> &nbsp;MCU — Bhopal
      </p>
      <h1>
        Your Digital
        <span class="gold">Knowledge Hub</span>
      </h1>
      <p>Access thousands of academic books, research papers, and resources from Makhanlal Chaturvedi National University's curated digital library — anytime, anywhere.</p>
      <div class="hero-btns">
        <a href="/books.php" class="btn btn-primary btn-lg">
          <i class="fas fa-search"></i> Browse Books
        </a>
        <?php if (!isLoggedIn() && !isAdmin()): ?>
        <a href="/register.php" class="btn btn-lg" style="background:rgba(255,255,255,.1);color:#fff;border:2px solid rgba(255,255,255,.25);">
          <i class="fas fa-user-plus"></i> Join Free
        </a>
        <?php else: ?>
        <a href="/dashboard.php" class="btn btn-lg" style="background:rgba(255,255,255,.1);color:#fff;border:2px solid rgba(255,255,255,.25);">
          <i class="fas fa-tachometer-alt"></i> My Dashboard
        </a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="num count-up" data-target="<?= $totalBooks ?>"><?= $totalBooks ?></div>
          <div class="label">Total Books</div>
        </div>
        <div class="hero-stat">
          <div class="num count-up" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
          <div class="label">Students</div>
        </div>
        <div class="hero-stat">
          <div class="num count-up" data-target="<?= (int)$totalAvail ?>"><?= (int)$totalAvail ?></div>
          <div class="label">Available</div>
        </div>
        <div class="hero-stat">
          <div class="num count-up" data-target="<?= $totalIssued ?>"><?= $totalIssued ?></div>
          <div class="label">Issued</div>
        </div>
      </div>
    </div>

    <div class="hero-visual">
      <?php foreach (array_slice($featured, 0, 4) as $b): ?>
      <div class="hero-book-card">
        <div class="book-icon"><i class="fas fa-book"></i></div>
        <h4><?= htmlspecialchars(mb_strimwidth($b['title'], 0, 40, '…')) ?></h4>
        <p><?= htmlspecialchars($b['author']) ?></p>
        <?php $avail = $b['total_quantity'] - $b['issued_quantity']; ?>
        <span style="font-size:.7rem;color:<?= $avail>0?'#4ade80':'#f87171' ?>;margin-top:.4rem;display:block;">
          <?= $avail > 0 ? "✓ $avail available" : '✕ Out of Stock' ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Quick Search ─────────────────────────────────────────── -->
<section style="background:var(--bg-card);border-bottom:1px solid var(--border);padding:1.8rem 2rem;">
  <div style="max-width:800px;margin:0 auto;">
    <form action="/books.php" method="GET" style="display:flex;gap:.8rem;flex-wrap:wrap;">
      <div style="flex:1;min-width:240px;position:relative;">
        <i class="fas fa-search" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
        <input type="text" name="q" placeholder="Search by title, author…" class="form-control" style="padding-left:2.5rem;" autocomplete="off">
      </div>
      <select name="category" class="form-control" style="max-width:200px;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
    </form>
  </div>
</section>

<!-- ── Featured Books ───────────────────────────────────────── -->
<section class="features-section">
  <div class="container">
    <div class="section-header">
      <span class="tag">New Arrivals</span>
      <h2>Recently Added Books</h2>
      <p>Explore our latest additions across all academic disciplines</p>
    </div>

    <div class="books-grid">
      <?php foreach ($featured as $book):
        $avail = $book['total_quantity'] - $book['issued_quantity'];
      ?>
      <div class="book-card">
        <div class="book-cover">
          <i class="fas fa-book-open book-cover-icon"></i>
          <div class="book-cover-title"><?= htmlspecialchars($book['category']) ?></div>
        </div>
        <div class="book-info">
          <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
          <div class="book-author"><i class="fas fa-user-pen" style="font-size:.75rem;margin-right:.3rem;"></i><?= htmlspecialchars($book['author']) ?></div>
          <span class="book-category"><?= htmlspecialchars($book['category']) ?></span>
          <div class="book-meta">
            <span class="book-availability <?= $avail > 0 ? 'avail-yes' : 'avail-no' ?>">
              <?= $avail > 0 ? '✓ Available' : '✕ Out of Stock' ?>
            </span>
            <span class="book-qty"><?= $avail ?>/<?= $book['total_quantity'] ?></span>
          </div>
        </div>
        <div class="book-actions">
          <a href="/books.php?book=<?= $book['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
          <?php if (isLoggedIn() && $avail > 0): ?>
            <a href="/issue_book.php?book_id=<?= $book['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-hand-holding-heart"></i> Issue</a>
          <?php elseif (!isLoggedIn()): ?>
            <a href="/login.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:2.5rem;">
      <a href="/books.php" class="btn btn-navy btn-lg"><i class="fas fa-books"></i> View All Books</a>
    </div>
  </div>
</section>

<!-- ── Features / Why Use ───────────────────────────────────── -->
<section style="background:var(--bg-card);padding:5rem 2rem;border-top:1px solid var(--border);">
  <div class="container">
    <div class="section-header">
      <span class="tag">Features</span>
      <h2>Why MCU E-Library?</h2>
      <p>Everything you need for your academic journey, powered by AI</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-robot"></i></div>
        <h3>AI Chatbot</h3>
        <p>Get instant answers about books, subjects, and library services from our intelligent assistant.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-lightbulb"></i></div>
        <h3>Smart Recommendations</h3>
        <p>AI-powered book suggestions based on your reading history and academic interests.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
        <h3>AI Summaries</h3>
        <p>Generate instant summaries of books to decide if they suit your research needs.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-search"></i></div>
        <h3>Smart Search</h3>
        <p>Typo-tolerant search that understands spelling mistakes and finds the right books.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-bell"></i></div>
        <h3>Notifications</h3>
        <p>Automatic reminders for due dates, approvals, and return alerts.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
        <h3>Secure & Fast</h3>
        <p>Secure session handling, CSRF protection and lightning-fast performance.</p>
      </div>
    </div>
  </div>
</section>

<!-- ── Categories ───────────────────────────────────────────── -->
<section style="padding:4rem 2rem;">
  <div class="container">
    <div class="section-header">
      <span class="tag">Explore</span>
      <h2>Browse by Category</h2>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.8rem;justify-content:center;">
      <?php foreach ($categories as $cat):
        $count = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category=?");
        $count->execute([$cat]);
        $cnt = $count->fetchColumn();
      ?>
      <a href="/books.php?category=<?= urlencode($cat) ?>"
         style="padding:.7rem 1.4rem;background:var(--bg-card);border:1.5px solid var(--border);border-radius:99px;font-size:.88rem;font-weight:600;color:var(--text);transition:all .2s;display:flex;align-items:center;gap:.5rem;">
        <i class="fas fa-tag" style="color:var(--gold);font-size:.8rem;"></i>
        <?= htmlspecialchars($cat) ?>
        <span style="background:var(--gold-pale);color:var(--gold-dark);padding:.1rem .5rem;border-radius:99px;font-size:.72rem;"><?= $cnt ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
