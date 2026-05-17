<?php
// ============================================================
// includes/header.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

// Unread notification count
$notifCount = 0;
if (isLoggedIn()) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->execute([$_SESSION['user_id']]);
    $notifCount = (int)$stmt->fetchColumn();
}

$flash = getFlash();
$pageTitle = $pageTitle ?? 'MCU E-Library';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — MCU E-Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/mcu-e-library/assets/css/style.css">
</head>
<body>

<!-- ── Navigation ─────────────────────────────────────────── -->
<nav class="navbar" id="navbar">
  <div class="nav-container">

    <a href="/index.php" class="nav-brand">
      <span class="brand-icon"><i class="fas fa-book-open"></i></span>
      <span class="brand-text">MCU<em>Library</em></span>
    </a>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <ul class="nav-links" id="navLinks">
      <li><a href="/index.php" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="/books.php" class="nav-link"><i class="fas fa-books"></i> Books</a></li>

      <?php if (isAdmin()): ?>
        <li><a href="/admin_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Admin</a></li>
        <li><a href="/admin/manage_books.php" class="nav-link"><i class="fas fa-cog"></i> Manage</a></li>
      <?php elseif (isLoggedIn()): ?>
        <li><a href="/dashboard.php" class="nav-link"><i class="fas fa-user-circle"></i> Dashboard</a></li>
        <li>
          <a href="/dashboard.php#notifications" class="nav-link notif-bell">
            <i class="fas fa-bell"></i>
            <?php if ($notifCount > 0): ?>
              <span class="badge"><?= $notifCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php else: ?>
        <li><a href="/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
        <li><a href="/register.php" class="btn btn-primary btn-sm">Sign Up</a></li>
      <?php endif; ?>

      <?php if (isLoggedIn() || isAdmin()): ?>
        <li><a href="/logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      <?php endif; ?>

      <li>
        <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
          <i class="fas fa-moon"></i>
        </button>
      </li>
    </ul>
  </div>
</nav>

<!-- ── Flash Message ──────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
  <i class="fas fa-<?= $flash['type']==='success'?'check-circle':($flash['type']==='danger'?'exclamation-circle':'info-circle') ?>"></i>
  <?= htmlspecialchars($flash['msg']) ?>
  <button class="flash-close" onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>

<main class="main-content">
