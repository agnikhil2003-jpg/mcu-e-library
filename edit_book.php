<?php
// ============================================================
// edit_book.php — Admin: Edit Book
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Edit Book';
$id        = (int)($_GET['id'] ?? 0);
$errors    = [];

$book = $pdo->prepare("SELECT * FROM books WHERE id=?");
$book->execute([$id]);
$book = $book->fetch();
if (!$book) { setFlash('danger','Book not found.'); header('Location: /admin/manage_books.php'); exit; }

$categories = ['Computer Science','Database','Networking','Artificial Intelligence','Software Engineering',
               'Web Development','Electronics','Mathematics','Physics','Chemistry','Commerce','MBA',
               'Law','Journalism','Mass Communication','English','Hindi','Political Science','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $data = [
            'title'          => trim($_POST['title']          ?? ''),
            'author'         => trim($_POST['author']         ?? ''),
            'isbn'           => trim($_POST['isbn']           ?? ''),
            'category'       => trim($_POST['category']       ?? ''),
            'publisher'      => trim($_POST['publisher']      ?? ''),
            'publish_year'   => (int)($_POST['publish_year']  ?? 0),
            'description'    => trim($_POST['description']    ?? ''),
            'total_quantity' => max(1,(int)($_POST['total_quantity'] ?? 1)),
            'location'       => trim($_POST['location']       ?? ''),
        ];

        if (empty($data['title']))    $errors[] = 'Title is required.';
        if (empty($data['author']))   $errors[] = 'Author is required.';
        if (empty($data['category'])) $errors[] = 'Category is required.';

        // Ensure total >= issued
        if ($data['total_quantity'] < $book['issued_quantity']) {
            $errors[] = "Total quantity cannot be less than issued quantity ({$book['issued_quantity']}).";
        }

        if (empty($errors)) {
            $upd = $pdo->prepare("UPDATE books SET title=?,author=?,isbn=?,category=?,publisher=?,publish_year=?,description=?,total_quantity=?,location=? WHERE id=?");
            $upd->execute([$data['title'],$data['author'],$data['isbn'],$data['category'],
                           $data['publisher'],$data['publish_year'],$data['description'],
                           $data['total_quantity'],$data['location'],$id]);
            setFlash('success','Book updated successfully!');
            header('Location: /admin/manage_books.php'); exit;
        }
        // Merge for display
        $book = array_merge($book, $data);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:800px;margin:0 auto;">
    <div class="page-header" style="padding:0 0 1.5rem;">
      <div>
        <h1><i class="fas fa-edit" style="color:var(--gold);margin-right:.5rem;"></i> Edit Book</h1>
        <p>Editing: <strong><?= htmlspecialchars($book['title']) ?></strong></p>
      </div>
      <a href="/admin/manage_books.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($errors): ?>
      <div class="flash-message flash-danger" style="border-radius:8px;margin-bottom:1.5rem;flex-direction:column;align-items:flex-start;">
        <?php foreach ($errors as $e): ?><div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">

            <div class="form-group" style="grid-column:1/-1;">
              <label class="form-label">Book Title *</label>
              <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($book['title']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Author *</label>
              <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">ISBN</label>
              <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($book['isbn'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" class="form-control" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat ?>" <?= $book['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Publisher</label>
              <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Publish Year</label>
              <input type="number" name="publish_year" class="form-control" value="<?= $book['publish_year'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Total Quantity *
                <small style="font-weight:400;color:var(--text-muted);">(Currently issued: <?= $book['issued_quantity'] ?>)</small>
              </label>
              <input type="number" name="total_quantity" class="form-control" value="<?= $book['total_quantity'] ?>" min="<?= $book['issued_quantity'] ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Shelf / Location</label>
              <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($book['location'] ?? '') ?>">
            </div>

            <div class="form-group" style="grid-column:1/-1;">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
            </div>

          </div>

          <div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1.5rem;display:flex;gap:2rem;flex-wrap:wrap;">
            <div><strong>Available:</strong> <?= $book['total_quantity'] - $book['issued_quantity'] ?> copies</div>
            <div><strong>Issued:</strong> <?= $book['issued_quantity'] ?> copies</div>
            <div><strong>Total:</strong> <?= $book['total_quantity'] ?> copies</div>
          </div>

          <div style="display:flex;gap:.8rem;justify-content:flex-end;">
            <a href="/admin/manage_books.php" class="btn btn-outline">Cancel</a>
            <a href="/delete_book.php?id=<?= $id ?>&csrf=<?= csrfToken() ?>"
               class="btn btn-danger"
               data-confirm="Delete this book permanently?">
              <i class="fas fa-trash"></i> Delete
            </a>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
