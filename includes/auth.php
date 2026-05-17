<?php
// ============================================================
// includes/auth.php — Session & Role Guard
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /login.php?admin=1');
        exit;
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function currentAdmin(): ?array {
    return $_SESSION['admin'] ?? null;
}

// CSRF helpers
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
