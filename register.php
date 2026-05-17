<?php
// ============================================================
// register.php — Student Signup
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: /dashboard.php'); exit; }

$pageTitle = 'Register';
$errors    = [];
$data      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $data = [
            'full_name'     => trim($_POST['full_name']     ?? ''),
            'email'         => trim($_POST['email']         ?? ''),
            'phone'         => trim($_POST['phone']         ?? ''),
            'enrollment_no' => trim($_POST['enrollment_no'] ?? ''),
            'department'    => trim($_POST['department']    ?? ''),
            'semester'      => (int)($_POST['semester']     ?? 1),
            'password'      => $_POST['password']           ?? '',
            'confirm_pass'  => $_POST['confirm_pass']       ?? '',
        ];

        // Validation
        if (empty($data['full_name']))     $errors[] = 'Full name is required.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($data['enrollment_no'])) $errors[] = 'Enrollment number is required.';
        if (strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($data['password'] !== $data['confirm_pass']) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            // Check uniqueness
            $check = $pdo->prepare("SELECT id FROM users WHERE email=? OR enrollment_no=?");
            $check->execute([$data['email'], $data['enrollment_no']]);
            if ($check->fetch()) {
                $errors[] = 'Email or Enrollment number already registered.';
            } else {
                $hash = password_hash($data['password'], PASSWORD_DEFAULT);
                $ins  = $pdo->prepare("INSERT INTO users (full_name,email,phone,enrollment_no,department,semester,password) VALUES (?,?,?,?,?,?,?)");
                $ins->execute([
                    $data['full_name'], $data['email'], $data['phone'],
                    $data['enrollment_no'], $data['department'], $data['semester'], $hash
                ]);
                $userId = $pdo->lastInsertId();

                // Welcome notification
                $pdo->prepare("INSERT INTO notifications (user_id,message,type) VALUES (?,?,?)")
                    ->execute([$userId, 'Welcome to MCU E-Library! You can now browse and request books.', 'success']);

                setFlash('success', 'Account created! Please log in.');
                header('Location: /login.php'); exit;
            }
        }
    }
}

$departments = ['Computer Science','Electronics','Mathematics','Physics','Commerce','MBA','Law','Journalism','Mass Communication','Political Science','English','Hindi','Other'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page" style="align-items:flex-start;padding-top:3rem;">
  <div class="auth-card" style="max-width:560px;">
    <div class="auth-header">
      <div class="auth-logo"><i class="fas fa-user-plus"></i></div>
      <h2>Create Account</h2>
      <p>Join MCU E-Library as a Student</p>
    </div>

    <div class="auth-body">
      <?php if ($errors): ?>
        <div class="flash-message flash-danger" style="border-radius:8px;margin-bottom:1.2rem;flex-direction:column;align-items:flex-start;gap:.3rem;">
          <?php foreach ($errors as $e): ?>
            <div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label">Full Name *</label>
            <div class="input-icon-wrap">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="full_name" class="form-control"
                     value="<?= htmlspecialchars($data['full_name'] ?? '') ?>"
                     placeholder="Your full name" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <div class="input-icon-wrap">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                     placeholder="student@mcu.ac.in" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <div class="input-icon-wrap">
              <i class="fas fa-phone input-icon"></i>
              <input type="tel" name="phone" class="form-control"
                     value="<?= htmlspecialchars($data['phone'] ?? '') ?>"
                     placeholder="+91 98765 43210">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Enrollment Number *</label>
            <div class="input-icon-wrap">
              <i class="fas fa-id-card input-icon"></i>
              <input type="text" name="enrollment_no" class="form-control"
                     value="<?= htmlspecialchars($data['enrollment_no'] ?? '') ?>"
                     placeholder="e.g. MCU2024CS001" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department" class="form-control">
              <option value="">Select Department</option>
              <?php foreach ($departments as $dep): ?>
                <option value="<?= $dep ?>" <?= ($data['department'] ?? '') === $dep ? 'selected' : '' ?>>
                  <?= $dep ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control">
              <?php for ($i=1; $i<=8; $i++): ?>
                <option value="<?= $i ?>" <?= ($data['semester'] ?? 1) == $i ? 'selected' : '' ?>>
                  Semester <?= $i ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Password *</label>
            <div class="input-icon-wrap" style="position:relative;">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="password" id="pw1" class="form-control"
                     placeholder="Min. 6 characters" required style="padding-right:3rem;">
              <button type="button" class="pw-toggle" style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Confirm Password *</label>
            <div class="input-icon-wrap" style="position:relative;">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="confirm_pass" id="pw2" class="form-control"
                     placeholder="Repeat password" required style="padding-right:3rem;">
              <button type="button" class="pw-toggle" style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Password strength bar -->
        <div class="progress-bar-wrap mb-3">
          <div class="progress-bar" id="pwStrength" style="width:0%;background:#e74c3c;"></div>
        </div>
        <p id="pwStrengthLabel" style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;"></p>

        <label style="display:flex;align-items:flex-start;gap:.6rem;font-size:.85rem;color:var(--text-muted);margin-bottom:1.4rem;cursor:pointer;">
          <input type="checkbox" required style="margin-top:.15rem;">
          I agree to the library <a href="#">terms and conditions</a> and <a href="#">usage policy</a>.
        </label>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>
    </div>

    <div class="auth-footer">
      Already have an account? <a href="/login.php">Sign in here</a>
    </div>
  </div>
</div>

<script>
// Password strength meter
document.getElementById('pw1')?.addEventListener('input', function() {
  const val = this.value;
  let strength = 0;
  if (val.length >= 6) strength++;
  if (val.length >= 10) strength++;
  if (/[A-Z]/.test(val)) strength++;
  if (/[0-9]/.test(val)) strength++;
  if (/[^A-Za-z0-9]/.test(val)) strength++;
  const pct = (strength / 5) * 100;
  const bar = document.getElementById('pwStrength');
  const lbl = document.getElementById('pwStrengthLabel');
  bar.style.width = pct + '%';
  const labels = ['','Very Weak','Weak','Fair','Strong','Very Strong'];
  const colors  = ['','#e74c3c','#e67e22','#f1c40f','#27ae60','#1abc9c'];
  bar.style.background = colors[strength] || '#e74c3c';
  lbl.textContent = labels[strength] ? 'Password strength: ' + labels[strength] : '';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
