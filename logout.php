<?php
// ============================================================
// logout.php
// ============================================================
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
header('Location: /login.php?logged_out=1');
exit;
