document.addEventListener('DOMContentLoaded', function () {
  const menuTable = document.getElementById('menuTable');
  const itemForm = document.getElementById('itemForm');
  const itemIdField = document.getElementById('itemIdField');
  const itemName = document.getElementById('itemName');
  const itemPrice = document.getElementById('itemPrice');
  const itemCategory = document.getElementById('itemCategory');
  const itemAvailable = document.getElementById('itemAvailable');
  const formTitle = document.getElementById('formTitle');
  const formSub = document.getElementById('formSub');
  const saveItemBtn = document.getElementById('saveItemBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');
  const uploadLabel = document.getElementById('uploadLabel');
  const itemPhoto = document.getElementById('itemPhoto');

  // Availability toggle (instant, via AJAX)
  if (menuTable) {
    menuTable.addEventListener('change', function (e) {
      if (!e.target.classList.contains('avail-toggle')) return;

      const row = e.target.closest('tr');
      const itemId = row.dataset.id;
      const available = e.target.checked ? 1 : 0;

      fetch('api/toggle_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + encodeURIComponent(itemId) + '&available=' + available,
      });
    });

    // Edit button: populate form for update
    menuTable.addEventListener('click', function (e) {
      const btn = e.target.closest('.edit-item-btn');
      if (!btn) return;

      const row = btn.closest('tr');
      itemIdField.value = row.dataset.id;
      itemName.value = row.dataset.name;
      itemPrice.value = row.dataset.price;
      itemCategory.value = row.dataset.categoryId || '';
      itemAvailable.checked = row.dataset.available === '1';

      formTitle.textContent = 'Edit item';
      formSub.textContent = 'Update this menu item.';
      saveItemBtn.innerHTML = 'Update item';
      cancelEditBtn.style.display = 'block';

      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', function () {
      itemForm.reset();
      itemIdField.value = '';
      formTitle.textContent = 'Add item';
      formSub.textContent = 'Create a new menu item.';
      saveItemBtn.innerHTML = 'Save item';
      cancelEditBtn.style.display = 'none';
      uploadLabel.textContent = 'Drag & drop food photo or click to upload';
    });
  }

  if (itemPhoto) {
    itemPhoto.addEventListener('change', function () {
      if (itemPhoto.files && itemPhoto.files[0]) {
        uploadLabel.textContent = itemPhoto.files[0].name;
      }
    });
  }
});
