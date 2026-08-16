<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('kitchen');

$pageTitle = 'Kitchen Menu Manager';

$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
while ($row = mysqli_fetch_assoc($catResult)) {
    $categories[] = $row;
}

$items = [];
$itemResult = mysqli_query($conn, "SELECT m.id, m.name, m.price, m.image, m.available, m.category_id, c.name AS category_name
    FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id
    ORDER BY m.name ASC");
while ($row = mysqli_fetch_assoc($itemResult)) {
    $items[] = $row;
}

include 'includes/header.php';
?>

<div class="km-header">
  <h1>Kitchen Menu Manager</h1>
  <p>Manage your live menu and add new dishes.</p>
</div>

<div class="km-tabs">
  <span class="km-tab active">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
    Live menu
  </span>
</div>

<div class="km-layout">
  <div class="panel">
    <h2>Live menu</h2>
    <p class="sub"><?php echo count($items); ?> items currently on your menu.</p>

    <?php if (empty($items)): ?>
      <p style="color: var(--gray); font-size: 0.9rem;">No items yet — add your first dish using the form.</p>
    <?php else: ?>
      <table class="menu-table" id="menuTable">
        <thead>
          <tr>
            <th>Item name</th>
            <th>Price</th>
            <th>Category</th>
            <th>Available</th>
            <th>Edit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr data-id="<?php echo $item['id']; ?>"
                data-name="<?php echo e($item['name']); ?>"
                data-price="<?php echo $item['price']; ?>"
                data-category-id="<?php echo (int) $item['category_id']; ?>"
                data-image="<?php echo e($item['image']); ?>"
                data-available="<?php echo $item['available']; ?>">
              <td>
                <div class="item-name-cell">
                  <?php if ($item['image']): ?>
                    <img src="<?php echo e($item['image']); ?>" class="item-thumb" alt="">
                  <?php else: ?>
                    <div class="item-thumb">🍽️</div>
                  <?php endif; ?>
                  <span><?php echo e($item['name']); ?></span>
                </div>
              </td>
              <td><span class="price"><?php echo formatPrice($item['price']); ?></span></td>
              <td><?php if ($item['category_name']): ?><span class="cat-chip"><?php echo e($item['category_name']); ?></span><?php endif; ?></td>
              <td>
                <label class="switch">
                  <input type="checkbox" class="avail-toggle" <?php echo $item['available'] ? 'checked' : ''; ?>>
                  <span class="slider"></span>
                </label>
              </td>
              <td>
                <button class="edit-icon-btn edit-item-btn" title="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <h2 id="formTitle">Add item</h2>
    <p class="sub" id="formSub">Create a new menu item.</p>

    <form id="itemForm" method="POST" action="api/add_menu_item.php" enctype="multipart/form-data">
      <input type="hidden" name="item_id" id="itemIdField" value="">

      <div class="field">
        <label for="itemName">Item name</label>
        <input type="text" class="plain-input" name="name" id="itemName" placeholder="e.g. Veg Momo" required>
      </div>

      <div class="field">
        <label for="itemPrice">Price</label>
        <input type="number" step="0.01" min="0" class="plain-input" name="price" id="itemPrice" placeholder="e.g. 150" required>
      </div>

      <div class="field">
        <label for="itemCategory">Category</label>
        <div class="input-wrap" style="position:static;">
          <select name="category_id" id="itemCategory" class="plain-input" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label>Food photo</label>
        <label class="upload-box" for="itemPhoto" id="uploadBox">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span id="uploadLabel">Drag &amp; drop food photo or click to upload</span>
          <input type="file" name="photo" id="itemPhoto" accept="image/*">
        </label>
      </div>

      <div class="toggle-row">
        <label for="itemAvailable">Available</label>
        <label class="switch">
          <input type="checkbox" name="available" id="itemAvailable" checked>
          <span class="slider"></span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block" id="saveItemBtn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Save item
      </button>
      <button type="button" class="btn btn-outline btn-block" id="cancelEditBtn" style="display:none; margin-top:10px;">Cancel edit</button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/kitchen-menu.js"></script>
