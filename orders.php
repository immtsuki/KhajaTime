<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('student');

$pageTitle = 'Orders';
$userId = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id, token_number, status, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);

$orderRows = [];
while ($row = mysqli_fetch_assoc($orders)) {
    $itemsStmt = mysqli_prepare($conn, "SELECT item_name, quantity FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($itemsStmt, 'i', $row['id']);
    mysqli_stmt_execute($itemsStmt);
    $itemsResult = mysqli_stmt_get_result($itemsStmt);
    $itemNames = [];
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $itemNames[] = $item['item_name'] . ' ×' . $item['quantity'];
    }
    $row['items_text'] = implode(', ', $itemNames);
    $orderRows[] = $row;
}

include 'includes/header.php';
?>

<h1 class="page-title">Your orders</h1>

<?php if (empty($orderRows)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <p>You haven't placed any orders yet.</p>
    <a href="menu.php" class="btn btn-primary" style="margin-top:14px; display:inline-flex;">Browse menu</a>
  </div>
<?php else: ?>
  <div class="history-list">
    <?php foreach ($orderRows as $order): ?>
      <a href="order-status.php?id=<?php echo $order['id']; ?>" class="history-item">
        <div class="h-left">
          <span class="history-token">Token #<?php echo e($order['token_number']); ?></span>
          <span class="history-items"><?php echo e($order['items_text']); ?></span>
          <span class="history-meta"><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?> · <?php echo formatPrice($order['total']); ?></span>
        </div>
        <span class="status-pill <?php echo $order['status'] === 'preparing' ? 'preparing' : 'ready'; ?>">
          <?php echo $order['status'] === 'preparing' ? '● Preparing' : ($order['status'] === 'ready' ? '✓ Ready' : '✓ Completed'); ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/theme-toggle.js"></script>
