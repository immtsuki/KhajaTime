<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('kitchen');

$pageTitle = 'Order Queue';

$result = mysqli_query($conn, "SELECT o.id, o.token_number, o.status, o.created_at, u.full_name
    FROM orders o JOIN users u ON o.user_id = u.id
    WHERE o.status IN ('preparing','ready')
    ORDER BY o.created_at ASC");

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $itemsStmt = mysqli_prepare($conn, "SELECT id, item_name, quantity, is_checked FROM order_items WHERE order_id = ?");
    mysqli_stmt_bind_param($itemsStmt, 'i', $row['id']);
    mysqli_stmt_execute($itemsStmt);
    $row['items'] = mysqli_stmt_get_result($itemsStmt)->fetch_all(MYSQLI_ASSOC);
    $orders[] = $row;
}

include 'includes/header.php';
?>

<div class="queue-header">
  <h1>Order Queue</h1>
  <span class="queue-count"><?php echo count($orders); ?> active orders</span>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <p>No active orders right now.</p>
  </div>
<?php else: ?>
  <div class="queue-grid" id="queueGrid">
    <?php foreach ($orders as $order): ?>
      <?php $urgent = isUrgent($order['created_at'], $order['status']); ?>
      <div class="queue-card" data-order-id="<?php echo $order['id']; ?>">
        <div class="queue-card-top">
          <?php if ($urgent): ?><span class="badge badge-urgent">⚠ Urgent</span><?php else: ?><span></span><?php endif; ?>
          <span class="token-chip">#<?php echo e($order['token_number']); ?></span>
        </div>
        <div class="queue-name"><?php echo e($order['full_name']); ?></div>
        <div class="queue-time">Ordered · <?php echo timeAgo($order['created_at']); ?></div>

        <div class="queue-items">
          <?php foreach ($order['items'] as $item): ?>
            <label class="queue-item-row <?php echo $item['is_checked'] ? 'checked' : ''; ?>" data-item-id="<?php echo $item['id']; ?>">
              <input type="checkbox" class="item-check" <?php echo $item['is_checked'] ? 'checked' : ''; ?>>
              <span><?php echo e($item['item_name']); ?><?php echo $item['quantity'] > 1 ? ' ×' . $item['quantity'] : ''; ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if ($order['status'] === 'ready'): ?>
          <button class="btn btn-green btn-block ready-btn" disabled>✓ Ready ✓</button>
        <?php else: ?>
          <button class="btn btn-orange btn-block mark-ready-btn">Mark as ready</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/kitchen-queue.js"></script>
