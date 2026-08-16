<?php
// Expects $pageTitle to optionally be set before include.
$role = currentRole();
$initial = isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 1)) : 'U';
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? e($pageTitle) . ' — KhajaTime' : 'KhajaTime'; ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
  <header class="topnav">
    <div class="topnav-inner">
      <a href="<?php echo $role === 'kitchen' ? 'kitchen-queue.php' : 'menu.php'; ?>" class="brand">
        <span class="brand-mark">🍲</span>
        <span class="brand-text"><span class="khaja">Khaja</span><span class="time">Time</span></span>
      </a>

      <button class="nav-toggle" onclick="document.getElementById('navLinks').classList.toggle('open')" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>

      <nav class="nav-links" id="navLinks">
        <?php if ($role === 'kitchen'): ?>
          <a href="kitchen-menu.php" class="nav-link <?php echo $current === 'kitchen-menu.php' ? 'active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16M4 15h16M4 11h10"/></svg>
            Menu Manager
          </a>
          <a href="kitchen-queue.php" class="nav-link <?php echo $current === 'kitchen-queue.php' ? 'active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Order Queue
          </a>
        <?php else: ?>
          <a href="menu.php" class="nav-link <?php echo $current === 'menu.php' ? 'active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
            Menu
          </a>
          <a href="orders.php" class="nav-link <?php echo $current === 'orders.php' ? 'active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Orders
          </a>
        <?php endif; ?>
        <a href="account.php" class="nav-link <?php echo $current === 'account.php' ? 'active' : ''; ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Account
        </a>
      </nav>

      <div class="nav-right">
        <div class="avatar"><?php echo e($initial); ?></div>
      </div>
    </div>
  </header>

  <main class="content">
    <div class="container">
