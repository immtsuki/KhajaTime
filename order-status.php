<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('student');

$pageTitle = 'Order status';
$orderId = (int) ($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id, token_number, status, total, created_at FROM orders WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $orderId, $userId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    header('Location: orders.php');
    exit;
}

$itemsStmt = mysqli_prepare($conn, "SELECT item_name, quantity FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($itemsStmt, 'i', $orderId);
mysqli_stmt_execute($itemsStmt);
$orderItems = mysqli_stmt_get_result($itemsStmt);

include 'includes/header.php';
?>

<div class="status-grid">
  <div class="status-card <?php echo $order['status'] !== 'preparing' ? 'ready' : ''; ?>" id="statusCard">
    <div class="status-heading" id="statusHeading">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span id="statusHeadingText"><?php echo $order['status'] === 'preparing' ? 'Order placed!' : 'Order ready!'; ?></span>
    </div>

    <div class="token-label">YOUR TOKEN</div>
    <div class="token-box"><?php echo '#' . e($order['token_number']); ?></div>

    <div class="order-summary-box">
      <div class="label">ORDER SUMMARY</div>
      <?php while ($item = mysqli_fetch_assoc($orderItems)): ?>
        <div class="summary-item">
          <span><?php echo e($item['item_name']); ?></span>
          <span>×<?php echo (int) $item['quantity']; ?></span>
        </div>
      <?php endwhile; ?>
    </div>

    <div id="statusPillWrap">
      <?php if ($order['status'] === 'preparing'): ?>
        <span class="status-pill preparing">● Preparing...</span>
        <p class="status-note">We'll notify you when it's ready</p>
      <?php else: ?>
        <div class="ready-banner">🔔 Ready! Head to counter 🎉</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php if ($order['status'] === 'preparing'): ?>
<script>
  const orderId = <?php echo (int) $orderId; ?>;
  const poll = setInterval(function () {
    fetch('api/order_status.php?id=' + orderId)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.status && data.status !== 'preparing') {
          clearInterval(poll);
          document.getElementById('statusCard').classList.add('ready');
          document.getElementById('statusHeadingText').textContent = 'Order ready!';
          document.getElementById('statusPillWrap').innerHTML =
            '<div class="ready-banner">🔔 Ready! Head to counter 🎉</div>';
        }
      });
  }, 4000);
</script>
<?php endif; ?>

<script src="assets/js/theme-toggle.js"></script>
