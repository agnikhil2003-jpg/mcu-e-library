<?php
// ============================================================
// issue_book.php — Issue Request (Student) + Approve/Reject (Admin)
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// ── Admin: Approve / Reject ──────────────────────────────────
if (isAdmin() && isset($_GET['action'])) {
    if (!verifyCsrf($_GET['csrf'] ?? '')) {
        setFlash('danger','Invalid request token.');
        header('Location: /admin_dashboard.php'); exit;
    }

    $action = $_GET['action'];
    $issueId = (int)($_GET['id'] ?? 0);

    $issue = $pdo->prepare("SELECT * FROM issued_books WHERE id=?");
    $issue->execute([$issueId]);
    $issue = $issue->fetch();

    if (!$issue) {
        setFlash('danger','Request not found.');
        header('Location: /admin_dashboard.php'); exit;
    }

    if ($action === 'approve' && $issue['status'] === 'pending') {
        // Check availability
        $book = $pdo->prepare("SELECT * FROM books WHERE id=?");
        $book->execute([$issue['book_id']]);
        $book = $book->fetch();

        if (($book['total_quantity'] - $book['issued_quantity']) <= 0) {
            setFlash('danger','Cannot approve: book is out of stock.');
            header('Location: /admin_dashboard.php'); exit;
        }

        $issueDate = date('Y-m-d');
        $dueDate   = date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days'));

        $pdo->prepare("UPDATE issued_books SET status='approved', issue_date=?, due_date=? WHERE id=?")
            ->execute([$issueDate, $dueDate, $issueId]);

        $pdo->prepare("UPDATE books SET issued_quantity = issued_quantity + 1 WHERE id=?")
            ->execute([$issue['book_id']]);

        // Notify user
        $msg = "Your request for \"{$book['title']}\" has been approved. Due date: " . date('d M Y', strtotime($dueDate));
        $pdo->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'success')")
            ->execute([$issue['user_id'], $msg]);

        setFlash('success','Book request approved. Due: ' . date('d M Y', strtotime($dueDate)));

    } elseif ($action === 'reject' && $issue['status'] === 'pending') {
        $pdo->prepare("UPDATE issued_books SET status='rejected' WHERE id=?")->execute([$issueId]);

        // Get book title for notification
        $bStmt = $pdo->prepare("SELECT title FROM books WHERE id=?");
        $bStmt->execute([$issue['book_id']]);
        $bTitle = $bStmt->fetchColumn();

        $pdo->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'danger')")
            ->execute([$issue['user_id'], "Your request for \"$bTitle\" was rejected. Contact the library for more info."]);

        setFlash('warning','Request rejected.');
    }

    header('Location: /admin_dashboard.php'); exit;
}

// ── Student: Request a Book ──────────────────────────────────
requireLogin();

$pageTitle = 'Request Book';
$userId    = $_SESSION['user_id'];
$bookId    = (int)($_GET['book_id'] ?? 0);
$errors    = [];

$book = $pdo->prepare("SELECT * FROM books WHERE id=?");
$book->execute([$bookId]);
$book = $book->fetch();

if (!$book) {
    setFlash('danger','Book not found.');
    header('Location: /books.php'); exit;
}

$avail = $book['total_quantity'] - $book['issued_quantity'];

// Check duplicate pending/approved request
$dup = $pdo->prepare("SELECT id FROM issued_books WHERE user_id=? AND book_id=? AND status IN ('pending','approved') LIMIT 1");
$dup->execute([$userId, $bookId]);
if ($dup->fetch()) {
    setFlash('warning','You already have an active request or issue for this book.');
    header('Location: /dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } elseif ($avail <= 0) {
        $errors[] = 'Sorry, this book is currently out of stock.';
    } else {
        $pdo->prepare("INSERT INTO issued_books (user_id, book_id, status) VALUES (?,?, 'pending')")
            ->execute([$userId, $bookId]);

        // Notify admin (via a simple flag — could be email)
        // Notify student
        $pdo->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,'info')")
            ->execute([$userId, "Your request for \"{$book['title']}\" has been submitted. Awaiting admin approval."]);

        setFlash('success','Book request submitted! You will be notified once approved.');
        header('Location: /dashboard.php'); exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:640px;margin:0 auto;">

    <a href="/books.php" class="btn btn-outline btn-sm" style="margin-bottom:1.5rem;">
      <i class="fas fa-arrow-left"></i> Back to Books
    </a>

    <div class="card">
      <div class="card-header" style="background:var(--navy);">
        <h3 style="color:#fff;"><i class="fas fa-hand-holding-heart" style="color:var(--gold);margin-right:.5rem;"></i> Request Book</h3>
      </div>
      <div class="card-body">

        <?php if ($errors): ?>
          <div class="flash-message flash-danger" style="border-radius:8px;margin-bottom:1rem;">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors[0]) ?>
          </div>
        <?php endif; ?>

        <!-- Book Info -->
        <div style="display:flex;gap:1.2rem;align-items:flex-start;background:var(--bg-input);border-radius:var(--radius);padding:1.2rem;margin-bottom:1.5rem;">
          <div style="width:60px;height:80px;background:var(--navy);border-radius:8px;display:grid;place-items:center;flex-shrink:0;">
            <i class="fas fa-book" style="color:var(--gold);font-size:1.5rem;"></i>
          </div>
          <div>
            <h3 style="margin:0 0 .3rem;font-size:1.2rem;"><?= htmlspecialchars($book['title']) ?></h3>
            <p style="margin:0 0 .5rem;font-size:.88rem;">by <?= htmlspecialchars($book['author']) ?></p>
            <span class="book-category"><?= htmlspecialchars($book['category']) ?></span>
            <div style="margin-top:.6rem;">
              <span class="book-availability <?= $avail > 0 ? 'avail-yes' : 'avail-no' ?>">
                <?= $avail > 0 ? "✓ $avail Available" : '✕ Out of Stock' ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Loan Info -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;margin-bottom:1.5rem;text-align:center;">
          <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:.9rem;">
            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Issue Date</div>
            <div style="font-weight:700;margin-top:.2rem;"><?= date('d M Y') ?></div>
          </div>
          <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:.9rem;">
            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Due Date</div>
            <div style="font-weight:700;margin-top:.2rem;color:var(--teal);"><?= date('d M Y', strtotime('+'.LOAN_DAYS.' days')) ?></div>
          </div>
          <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:.9rem;">
            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Loan Period</div>
            <div style="font-weight:700;margin-top:.2rem;"><?= LOAN_DAYS ?> days</div>
          </div>
        </div>

        <div style="background:var(--warning-bg);border:1px solid var(--gold);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.5rem;font-size:.85rem;color:#92400e;">
          <i class="fas fa-info-circle"></i>
          <strong>Fine Policy:</strong> A fine of <strong>₹<?= FINE_PER_DAY ?>/day</strong> will be charged for late returns after the due date.
        </div>

        <?php if ($avail > 0): ?>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div style="display:flex;gap:.8rem;">
            <a href="/books.php" class="btn btn-outline" style="flex:1;justify-content:center;">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg" style="flex:2;justify-content:center;">
              <i class="fas fa-paper-plane"></i> Submit Request
            </button>
          </div>
        </form>
        <?php else: ?>
          <div class="flash-message flash-danger" style="border-radius:8px;">
            <i class="fas fa-times-circle"></i> This book is currently out of stock. Please check back later.
          </div>
          <a href="/books.php" class="btn btn-outline w-100" style="margin-top:1rem;justify-content:center;">Browse Other Books</a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
