<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('student');

$pageTitle = 'Menu';

// Categories for the pill filter
$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
while ($row = mysqli_fetch_assoc($catResult)) {
    $categories[] = $row;
}

// Menu items
$items = [];
$itemResult = mysqli_query($conn, "SELECT m.id, m.name, m.price, m.image, m.available, c.name AS category_name
    FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id
    ORDER BY m.available DESC, m.name ASC");
while ($row = mysqli_fetch_assoc($itemResult)) {
    $items[] = $row;
}

$cart = getCart();
$cartCount = array_sum($cart);
$cartSubtotal = 0;
foreach ($items as $item) {
    if (isset($cart[$item['id']])) {
        $cartSubtotal += $item['price'] * $cart[$item['id']];
    }
}

include 'includes/header.php';
?>

<div class="search-wrap">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  <input type="text" id="searchInput" placeholder="Search for momos, chiya, rice...">
</div>

<div class="category-pills" id="categoryPills">
  <button class="pill active" data-cat="all">All</button>
  <?php foreach ($categories as $cat): ?>
    <button class="pill" data-cat="<?php echo e($cat['name']); ?>"><?php echo e($cat['name']); ?></button>
  <?php endforeach; ?>
</div>

<?php if (empty($items)): ?>
  <div class="empty-state">
    <p>No menu items yet. Check back soon!</p>
  </div>
<?php else: ?>
  <div class="menu-grid" id="menuGrid">
    <?php foreach ($items as $item): ?>
      <div class="food-card" data-name="<?php echo e(strtolower($item['name'])); ?>" data-cat="<?php echo e($item['category_name'] ?? ''); ?>">
        <?php if ($item['image']): ?>
          <img src="<?php echo e($item['image']); ?>" class="food-card-img" alt="<?php echo e($item['name']); ?>">
        <?php else: ?>
          <div class="food-card-img placeholder">🍽️</div>
        <?php endif; ?>
        <div class="food-card-body">
          <div class="food-card-top">
            <div class="food-card-name"><?php echo e($item['name']); ?></div>
            <span class="badge <?php echo $item['available'] ? 'badge-available' : 'badge-soldout'; ?>">
              <?php echo $item['available'] ? 'Available' : 'Sold out'; ?>
            </span>
          </div>
          <div class="food-card-bottom">
            <span class="price"><?php echo formatPrice($item['price']); ?></span>
            <button class="add-btn" data-id="<?php echo $item['id']; ?>" <?php echo $item['available'] ? '' : 'disabled'; ?>>
              Add
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="cart-bar <?php echo $cartCount > 0 ? 'visible' : ''; ?>" id="cartBar">
  <div class="cart-bar-inner">
    <div class="cart-icon-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      <span class="cart-badge" id="cartBadge"><?php echo $cartCount; ?></span>
    </div>
    <button class="btn btn-primary" onclick="window.location.href='cart.php'">
      <span id="cartLabel">View order · <?php echo formatPrice($cartSubtotal); ?></span>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </button>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/menu.js"></script>
