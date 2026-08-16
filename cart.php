<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('student');

$pageTitle = 'Your order';
$cart = getCart();

$lines = [];
$subtotal = 0;

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $idList = implode(',', $ids);
    $result = mysqli_query($conn, "SELECT id, name, price FROM menu_items WHERE id IN ($idList)");
    while ($row = mysqli_fetch_assoc($result)) {
        $qty = $cart[$row['id']];
        $lineTotal = $row['price'] * $qty;
        $subtotal += $lineTotal;
        $lines[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'qty' => $qty,
            'line_total' => $lineTotal,
        ];
    }
}

include 'includes/header.php';
?>

<div class="order-review-title">
  <a href="menu.php" class="back-btn">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
  </a>
  <h1>Your order</h1>
</div>

<?php if (empty($lines)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <p>Your cart is empty.</p>
    <a href="menu.php" class="btn btn-primary" style="margin-top:14px; display:inline-flex;">Browse menu</a>
  </div>
<?php else: ?>
  <div class="order-card" id="orderCard">
    <div id="orderLines">
      <?php foreach ($lines as $line): ?>
        <div class="order-line" data-id="<?php echo $line['id']; ?>" data-price="<?php echo $line['price']; ?>">
          <div class="order-line-name"><?php echo e($line['name']); ?></div>
          <div class="qty-control">
            <button class="qty-btn dec" aria-label="Decrease">−</button>
            <span class="qty-value"><?php echo $line['qty']; ?></span>
            <button class="qty-btn inc" aria-label="Increase">+</button>
          </div>
          <div class="order-line-price line-total"><?php echo formatPrice($line['line_total']); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="subtotal-row">
      <span class="label">Subtotal</span>
      <span class="amount" id="subtotalAmount"><?php echo formatPrice($subtotal); ?></span>
    </div>

    <button class="btn btn-primary btn-block" id="placeOrderBtn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Place order
    </button>

    <p class="order-hint">🎟️ Your pickup token will appear after placing the order.</p>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/cart.js"></script>
