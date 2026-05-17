<?php
// ============================================================
// delete_book.php — Admin: Delete Book
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$id   = (int)($_GET['id']   ?? 0);
$csrf = $_GET['csrf'] ?? '';

if (!verifyCsrf($csrf)) {
    setFlash('danger', 'Invalid request token.');
    header('Location: /admin/manage_books.php'); exit;
}

// Check if any approved issues exist
$check = $pdo->prepare("SELECT COUNT(*) FROM issued_books WHERE book_id=? AND status='approved'");
$check->execute([$id]);
if ($check->fetchColumn() > 0) {
    setFlash('danger', 'Cannot delete: this book is currently issued to students.');
    header('Location: /admin/manage_books.php'); exit;
}

$del = $pdo->prepare("DELETE FROM books WHERE id=?");
$del->execute([$id]);

setFlash('success', 'Book deleted successfully.');
header('Location: /admin/manage_books.php'); exit;
