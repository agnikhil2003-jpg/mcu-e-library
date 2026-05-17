<?php
// ============================================================
// login.php — Student & Admin Login
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Redirect.
if (isLoggedIn())  { header('Location: /dashboard.php'); exit; }
if (isAdmin())     { header('Location: /admin_dashboard.php'); exit; }

$pageTitle = 'Login';
$errors = [];
$role   = $_GET['role'] ?? 'student'; // student | admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $role  = $_POST['role'] ?? 'student';
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if (empty($email) || empty($pass)) {
            $errors[] = 'All fields are required.';
        } else {
            if ($role === 'admin') {
                // Admin login
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$email, $email]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($pass, $admin['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin']    = $admin;
                    setFlash('success', 'Welcome back, ' . $admin['full_name'] . '!');
                    header('Location: /admin_dashboard.php'); exit;
                } else {
                    $errors[] = 'Invalid admin credentials.';
                }
            } else {
                // Student login
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($pass, $user['password'])) {
                    if ($user['status'] === 'suspended') {
                        $errors[] = 'Your account has been suspended. Contact admin.';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user']    = $user;
                        setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                        $redirect = $_GET['redirect'] ?? '/dashboard.php';
                        header('Location: ' . $redirect); exit;
                    }
                } else {
                    $errors[] = 'Invalid email or password.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo"><i class="fas fa-book-open"></i></div>
      <h2>Welcome Back</h2>
      <p>Sign in to MCU E-Library</p>
    </div>

    <!-- Role Tabs -->
    <div class="auth-tabs">
      <div class="auth-tab <?= $role==='student'?'active':'' ?>" onclick="switchRole('student')">
        <i class="fas fa-user-graduate"></i> Student
      </div>
      <div class="auth-tab <?= $role==='admin'?'active':'' ?>" onclick="switchRole('admin')">
        <i class="fas fa-user-shield"></i> Admin
      </div>
    </div>

    <div class="auth-body">
      <?php if ($errors): ?>
        <div class="flash-message flash-danger" style="border-radius:8px;margin-bottom:1rem;">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($errors[0]) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>">

        <div class="form-group">
          <label class="form-label">Email / Username</label>
          <div class="input-icon-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="text" name="email" class="form-control"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="Enter your email" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between;">
            Password
            <a href="#" style="font-size:.82rem;font-weight:400;">Forgot password?</a>
          </label>
          <div class="input-icon-wrap" style="display:flex;align-items:center;">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="passwordInput" class="form-control"
                   placeholder="Enter your password" required style="padding-right:3rem;">
            <button type="button" class="pw-toggle"
                    style="position:absolute;right:.9rem;background:none;color:var(--text-muted);font-size:.9rem;">
              <i class="fas fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.4rem;">
          <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;">
            <input type="checkbox" name="remember"> Remember me
          </label>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>
    </div>

    <div class="auth-footer">
      Don't have an account? <a href="/register.php">Create one free</a>
    </div>
  </div>
</div>

<script>
function switchRole(role) {
  document.getElementById('roleInput').value = role;
  document.querySelectorAll('.auth-tab').forEach((t, i) => {
    t.classList.toggle('active', (role==='student' && i===0)||(role==='admin' && i===1));
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
