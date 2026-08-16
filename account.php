<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
requireLogin();

$pageTitle = 'Account';
include 'includes/header.php';
?>

<h1 class="page-title">Account</h1>

<div class="account-card">
  <div class="account-row">
    <span class="k">Name</span>
    <span class="v"><?php echo e($_SESSION['full_name']); ?></span>
  </div>
  <div class="account-row">
    <span class="k">Email</span>
    <span class="v"><?php echo e($_SESSION['email']); ?></span>
  </div>
  <div class="account-row">
    <span class="k">Role</span>
    <span class="v"><span class="role-chip"><?php echo e($_SESSION['role']); ?></span></span>
  </div>
  <?php if (!empty($_SESSION['college_id'])): ?>
  <div class="account-row">
    <span class="k">College ID</span>
    <span class="v"><?php echo e($_SESSION['college_id']); ?></span>
  </div>
  <?php endif; ?>

  <a href="logout.php" class="btn btn-outline btn-block" style="margin-top:20px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Log out
  </a>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/theme-toggle.js"></script>
