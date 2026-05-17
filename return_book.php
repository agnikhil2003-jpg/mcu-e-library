<?php
// ============================================================
// return_book.php — Admin: Process Book Return
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Return Book';

// Process return submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger','Invalid token.'); header('Location: /return_book.php'); exit;
    }

    $issueId   = (int)($_POST['issue_id'] ?? 0);
    $finePaid  = isset($_POST['fine_paid']) ? 1 : 0;

    $issue = $pdo->prepare("SELECT ib.*, b.title, b.id AS bid FROM issued_books ib JOIN books b ON b.id=ib.book_id WHERE ib.id=? AND ib.status='approved'");
    $issue->execute([$issueId]);
    $issue = $issue->fetch();

    if (!$issue) {
        setFlash('danger','Record not found or already returned.');
        header('Location: /return_book.php'); exit;
    }

    $returnDate = date('Y-m-d');
    $fine       = calculateFine(array_merge($issue, ['return_date' => $returnDate]));

    $pdo->prepare("UPDATE issued_books SET status='returned', return_date=?, fine_amount=?, fine_paid=? WHERE id=?")
        ->execute([$returnDate, $fine, $finePaid, $issueId]);

    $pdo->prepare("UPDATE books SET issued_quantity = GREATEST(issued_quantity-1, 0) WHERE id=?")
        ->execute([$issue['bid']]);

    // Notify student
    $msg = "You have returned \"{$issue['title']}\". " . ($fine > 0 ? "Fine: ₹$fine. " . ($finePaid ? "Paid." : "Please pay at desk.") : "No fine. Thank you!");
    $pdo->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)")
        ->execute([$issue['user_id'], $msg, $fine > 0 ? ($finePaid ? 'success' : 'warning') : 'success']);

    setFlash('success',"Book returned. Fine: ₹" . number_format($fine,2));
    header('Location: /return_book.php'); exit;
}

// Load pending returns (issued books)
$id = (int)($_GET['id'] ?? 0);
$selectedIssue = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT ib.*, b.title, b.author, u.full_name, u.enrollment_no FROM issued_books ib JOIN books b ON b.id=ib.book_id JOIN users u ON u.id=ib.user_id WHERE ib.id=? AND ib.status='approved'");
    $stmt->execute([$id]);
    $selectedIssue = $stmt->fetch();
}

// All currently issued
$allIssued = $pdo->query("
    SELECT ib.*, b.title, b.author, u.full_name, u.enrollment_no,
           DATEDIFF(CURDATE(), ib.due_date) AS days_over
    FROM issued_books ib
    JOIN books b ON b.id=ib.book_id
    JOIN users u ON u.id=ib.user_id
    WHERE ib.status='approved'
    ORDER BY ib.due_date ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">

  <div class="page-header">
    <div>
      <h1><i class="fas fa-undo" style="color:var(--gold);margin-right:.5rem;"></i> Return Book</h1>
      <p>Process book returns and calculate fines for late returns.</p>
    </div>
    <a href="/admin_dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:2rem;" class="return-grid">

    <!-- All Issued Books Table -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-book-reader" style="color:var(--gold);margin-right:.4rem;"></i> Currently Issued Books</h3>
        <span class="pill pill-info"><?= count($allIssued) ?> total</span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($allIssued)): ?>
          <div class="empty-state" style="padding:2rem;"><p>No books currently issued.</p></div>
        <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>#</th><th>Student</th><th>Book</th><th>Due Date</th><th>Fine</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($allIssued as $i => $r):
                  $fine = calculateFine(array_merge($r, ['return_date' => date('Y-m-d')]));
                  $isOver = $r['days_over'] > 0;
                ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($r['enrollment_no']) ?></small>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($r['title']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($r['author']) ?></small>
                  </td>
                  <td style="color:<?= $isOver ? 'var(--rust)' : 'inherit' ?>;font-weight:<?= $isOver?'700':'400' ?>">
                    <?= date('d M Y', strtotime($r['due_date'])) ?>
                    <?php if ($isOver): ?>
                      <br><small><?= $r['days_over'] ?> days overdue</small>
                    <?php endif; ?>
                  </td>
                  <td style="color:<?= $fine>0?'var(--rust)':'var(--teal)' ?>;font-weight:700;">
                    ₹<?= number_format($fine,2) ?>
                  </td>
                  <td>
                    <a href="/return_book.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-undo"></i> Return
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

    <!-- Return Form Panel -->
    <div>
      <div class="card" style="position:sticky;top:calc(var(--nav-h) + 1rem);">
        <div class="card-header" style="background:var(--navy);">
          <h3 style="color:#fff;"><i class="fas fa-clipboard-check" style="color:var(--gold);margin-right:.4rem;"></i> Process Return</h3>
        </div>
        <div class="card-body">
          <?php if ($selectedIssue):
            $fine = calculateFine(array_merge($selectedIssue, ['return_date' => date('Y-m-d')]));
            $isOver = date('Y-m-d') > $selectedIssue['due_date'];
          ?>
            <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.2rem;">
              <h4 style="margin-bottom:.4rem;font-size:.95rem;"><?= htmlspecialchars($selectedIssue['title']) ?></h4>
              <p style="font-size:.82rem;margin:0 0 .4rem;">by <?= htmlspecialchars($selectedIssue['author']) ?></p>
              <hr style="border:none;border-top:1px solid var(--border);margin:.6rem 0;">
              <p style="font-size:.82rem;margin:0;"><strong>Student:</strong> <?= htmlspecialchars($selectedIssue['full_name']) ?></p>
              <p style="font-size:.82rem;margin:.2rem 0;"><strong>Enrollment:</strong> <?= htmlspecialchars($selectedIssue['enrollment_no']) ?></p>
              <p style="font-size:.82rem;margin:.2rem 0;"><strong>Issued:</strong> <?= date('d M Y', strtotime($selectedIssue['issue_date'])) ?></p>
              <p style="font-size:.82rem;margin:.2rem 0;color:<?= $isOver?'var(--rust)':'var(--teal)' ?>;"><strong>Due:</strong> <?= date('d M Y', strtotime($selectedIssue['due_date'])) ?></p>
            </div>

            <div style="background:<?= $fine>0?'var(--danger-bg)':'var(--success-bg)' ?>;border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.2rem;text-align:center;">
              <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;">Fine Amount</div>
              <div style="font-size:2rem;font-weight:700;color:<?= $fine>0?'var(--rust)':'var(--teal)' ?>;font-family:var(--font-display);">
                ₹<?= number_format($fine,2) ?>
              </div>
              <?php if ($fine > 0): ?>
                <div style="font-size:.78rem;color:var(--rust);">
                  <?= (int)((strtotime('today') - strtotime($selectedIssue['due_date'])) / 86400) ?> days × ₹<?= FINE_PER_DAY ?>/day
                </div>
              <?php else: ?>
                <div style="font-size:.78rem;color:var(--teal);">On-time return — no fine</div>
              <?php endif; ?>
            </div>

            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="issue_id" value="<?= $selectedIssue['id'] ?>">

              <?php if ($fine > 0): ?>
              <label style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.2rem;cursor:pointer;font-size:.88rem;">
                <input type="checkbox" name="fine_paid" id="finePaid">
                Fine collected (₹<?= number_format($fine,2) ?> paid)
              </label>
              <?php endif; ?>

              <button type="submit" class="btn btn-success w-100 btn-lg"
                      data-confirm="Confirm return of '<?= addslashes($selectedIssue['title']) ?>'?">
                <i class="fas fa-check"></i> Confirm Return
              </button>
            </form>
          <?php else: ?>
            <div class="empty-state" style="padding:1.5rem;">
              <div class="empty-icon" style="font-size:2.5rem;"><i class="fas fa-hand-pointer"></i></div>
              <p>Select a book from the table to process its return.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<style>@media(max-width:900px){.return-grid{grid-template-columns:1fr!important}}</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
