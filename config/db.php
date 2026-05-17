<?php
// ============================================================
// config/db.php — Database Connection (PDO)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // change to your MySQL user
define('DB_PASS', '');              // change to your MySQL password
define('DB_NAME', 'mcu_library');

// Fine rate: ₹ per day after due date
define('FINE_PER_DAY', 2.00);

// Default loan period in days
define('LOAN_DAYS', 14);

// Anthropic API key for AI features
define('ANTHROPIC_API_KEY', 'YOUR_ANTHROPIC_API_KEY_HERE');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// ── Utility helpers ──────────────────────────────────────────

/**
 * Calculate fine for an issued book record.
 */
function calculateFine(array $record): float {
    if ($record['status'] === 'returned') {
        $checkDate = $record['return_date'];
    } else {
        $checkDate = date('Y-m-d');
    }
    if ($checkDate > $record['due_date']) {
        $diff = (strtotime($checkDate) - strtotime($record['due_date'])) / 86400;
        return round($diff * FINE_PER_DAY, 2);
    }
    return 0.00;
}

/**
 * Flash message helper.
 */
function setFlash(string $type, string $msg): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
