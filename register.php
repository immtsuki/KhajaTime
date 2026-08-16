<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: ' . (currentRole() === 'kitchen' ? 'kitchen-queue.php' : 'menu.php'));
    exit;
}

$error = '';
$role = $_POST['role'] ?? 'student';
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$collegeId = trim($_POST['college_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $pinConfirm = trim($_POST['pin_confirm'] ?? '');

    if ($fullName === '' || $email === '' || $pin === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^\d{4}$/', $pin)) {
        $error = 'PIN must be exactly 4 digits.';
    } elseif ($pin !== $pinConfirm) {
        $error = 'PINs do not match.';
    } elseif ($role === 'student' && $collegeId === '') {
        $error = 'Please enter your college ID.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashedPin = password_hash($pin, PASSWORD_DEFAULT);
            $collegeIdValue = $role === 'student' ? $collegeId : null;

            $insert = mysqli_prepare($conn, "INSERT INTO users (full_name, email, pin, role, college_id) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, 'sssss', $fullName, $email, $hashedPin, $role, $collegeIdValue);

            if (mysqli_stmt_execute($insert)) {
                $newId = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $newId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['college_id'] = $collegeIdValue;
                header('Location: ' . ($role === 'kitchen' ? 'kitchen-queue.php' : 'menu.php'));
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KhajaTime — Register</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
  <header class="topnav">
    <div class="topnav-inner">
      <a href="index.php" class="brand">
        <span class="brand-mark">🍲</span>
        <span class="brand-text"><span class="khaja">Khaja</span><span class="time">Time</span></span>
      </a>
    </div>
  </header>

  <main class="content">
    <div class="auth-wrap">
      <div class="auth-logo">🍲</div>
      <div class="auth-kicker">खाजा</div>
      <h1 class="auth-title"><span class="serif">Khaja</span><span class="time">Time</span></h1>
      <p class="auth-sub">create your account</p>

      <div class="auth-card">
        <?php if ($error): ?>
          <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registerForm">
          <div class="role-toggle">
            <input type="radio" name="role" id="role-student" value="student" <?php echo $role === 'student' ? 'checked' : ''; ?> onchange="toggleCollegeField()">
            <label for="role-student">🎓 I'm a student</label>
            <input type="radio" name="role" id="role-kitchen" value="kitchen" <?php echo $role === 'kitchen' ? 'checked' : ''; ?> onchange="toggleCollegeField()">
            <label for="role-kitchen">👨‍🍳 I'm kitchen staff</label>
          </div>

          <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" class="plain-input" name="full_name" id="full_name" placeholder="e.g. Anil Shrestha" required value="<?php echo e($fullName); ?>">
          </div>

          <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
              <input type="email" name="email" id="email" placeholder="anil.shrestha@swsc.edu.np" required value="<?php echo e($email); ?>">
            </div>
          </div>

          <div class="field" id="collegeIdField">
            <label for="college_id">College ID</label>
            <input type="text" class="plain-input" name="college_id" id="college_id" placeholder="e.g. SWC-2024-101" value="<?php echo e($collegeId); ?>">
          </div>

          <div class="field">
            <label for="pin">4-digit PIN</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="pin" id="pin" placeholder="Choose a 4-digit PIN" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" required>
            </div>
          </div>

          <div class="field">
            <label for="pin_confirm">Confirm PIN</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="pin_confirm" id="pin_confirm" placeholder="Re-enter PIN" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            Create account
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <div class="auth-footer">
          Already have an account? <a href="index.php">Log in</a>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function toggleCollegeField() {
  const kitchen = document.getElementById('role-kitchen').checked;
  const field = document.getElementById('collegeIdField');
  const input = document.getElementById('college_id');
  field.style.display = kitchen ? 'none' : 'block';
  input.required = !kitchen;
}
toggleCollegeField();
</script>
</body>
</html>
