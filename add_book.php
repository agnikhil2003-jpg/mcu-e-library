<?php
// ============================================================
// add_book.php — Admin: Add New Book
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Add Book';
$errors    = [];
$data      = [];

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

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO books (title,author,isbn,category,publisher,publish_year,description,total_quantity,location) VALUES (?,?,?,?,?,?,?,?,?)");
            $ins->execute([
                $data['title'],$data['author'],$data['isbn'],$data['category'],
                $data['publisher'],$data['publish_year'],$data['description'],
                $data['total_quantity'],$data['location']
            ]);
            setFlash('success', 'Book "' . $data['title'] . '" added successfully!');
            header('Location: /admin/manage_books.php'); exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="max-width:800px;margin:0 auto;">
    <div class="page-header" style="padding:0 0 1.5rem;">
      <div>
        <h1><i class="fas fa-plus-circle" style="color:var(--gold);margin-right:.5rem;"></i> Add New Book</h1>
        <p>Fill in the details to add a book to the library collection.</p>
      </div>
      <a href="/admin/manage_books.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($errors): ?>
      <div class="flash-message flash-danger" style="border-radius:8px;margin-bottom:1.5rem;flex-direction:column;align-items:flex-start;gap:.3rem;">
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
              <input type="text" name="title" class="form-control"
                     value="<?= htmlspecialchars($data['title'] ?? '') ?>"
                     placeholder="e.g. Introduction to Algorithms" required>
            </div>

            <div class="form-group">
              <label class="form-label">Author *</label>
              <input type="text" name="author" class="form-control"
                     value="<?= htmlspecialchars($data['author'] ?? '') ?>"
                     placeholder="e.g. Thomas H. Cormen" required>
            </div>

            <div class="form-group">
              <label class="form-label">ISBN</label>
              <input type="text" name="isbn" class="form-control"
                     value="<?= htmlspecialchars($data['isbn'] ?? '') ?>"
                     placeholder="e.g. 978-0262033848">
            </div>

            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" class="form-control" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat ?>" <?= ($data['category'] ?? '') === $cat ? 'selected' : '' ?>>
                    <?= $cat ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Publisher</label>
              <input type="text" name="publisher" class="form-control"
                     value="<?= htmlspecialchars($data['publisher'] ?? '') ?>"
                     placeholder="e.g. MIT Press">
            </div>

            <div class="form-group">
              <label class="form-label">Publish Year</label>
              <input type="number" name="publish_year" class="form-control"
                     value="<?= $data['publish_year'] ?? '' ?>"
                     min="1900" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Total Quantity *</label>
              <input type="number" name="total_quantity" class="form-control"
                     value="<?= $data['total_quantity'] ?? 1 ?>"
                     min="1" required>
            </div>

            <div class="form-group">
              <label class="form-label">Shelf / Location</label>
              <input type="text" name="location" class="form-control"
                     value="<?= htmlspecialchars($data['location'] ?? '') ?>"
                     placeholder="e.g. Rack A-3, Shelf 2">
            </div>

            <div class="form-group" style="grid-column:1/-1;">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="5"
                        placeholder="Brief description of the book…"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
            </div>

          </div>

          <hr class="divider">
          <div style="display:flex;gap:.8rem;justify-content:flex-end;">
            <a href="/admin/manage_books.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-save"></i> Add Book
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
