<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: ' . (currentRole() === 'kitchen' ? 'kitchen-queue.php' : 'menu.php'));
    exit;
}

$error = '';
$role = $_POST['role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    if ($email === '' || $pin === '') {
        $error = 'Please enter your email and PIN.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, pin, role, college_id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user || !password_verify($pin, $user['pin'])) {
            $error = 'Incorrect email or PIN.';
        } elseif ($user['role'] !== $role) {
            $error = 'No ' . ($role === 'kitchen' ? 'kitchen staff' : 'student') . ' account found with that email.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['college_id'] = $user['college_id'];
            header('Location: ' . ($user['role'] === 'kitchen' ? 'kitchen-queue.php' : 'menu.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KhajaTime — Login</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
  <header class="topnav">
    <div class="topnav-inner">
      <a href="index.php" class="brand">
        <!-- <span class="brand-mark">🍲</span> -->
        <span class="brand-text"><span class="khaja">Khaja</span><span class="time">Time</span></span>
      </a>
    </div>
  </header>

  <main class="content">
    <div class="auth-wrap">
      <!-- <div class="auth-logo">🍲</div> -->
      <!-- <div class="auth-kicker">खाजा</div> -->
      <h1 class="auth-title"><span class="serif">Khaja</span><span class="time">Time</span></h1>
      <p class="auth-sub">skip the line · order ahead</p>

      <div class="auth-card">
        <?php if ($error): ?>
          <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php" id="loginForm">
          <div class="role-toggle">
            <input type="radio" name="role" id="role-student" value="student" <?php echo $role === 'student' ? 'checked' : ''; ?>>
            <label for="role-student">🎓 I'm a student</label>
            <input type="radio" name="role" id="role-kitchen" value="kitchen" <?php echo $role === 'kitchen' ? 'checked' : ''; ?>>
            <label for="role-kitchen">👨‍🍳 I'm kitchen staff</label>
          </div>

          <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z" opacity="0"/><path d="M22 6l-10 7L2 6"/><path d="M2 6h20v12H2z"/></svg>
              <input type="email" name="email" id="email" placeholder="example@gmail.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
          </div>

          <div class="field">
            <label for="pin">PIN</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" name="pin" id="pin" placeholder="Enter 4-digit PIN" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            Continue
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <div class="auth-footer">
          New here? <a href="register.php">Register with your college ID</a>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
