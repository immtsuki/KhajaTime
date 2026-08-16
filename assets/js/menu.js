document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('searchInput');
  const pills = document.querySelectorAll('#categoryPills .pill');
  const cards = document.querySelectorAll('#menuGrid .food-card');
  const cartBar = document.getElementById('cartBar');
  const cartBadge = document.getElementById('cartBadge');
  const cartLabel = document.getElementById('cartLabel');

  let activeCategory = 'all';

  function applyFilters() {
    const query = (searchInput?.value || '').toLowerCase().trim();
    cards.forEach(function (card) {
      const matchesSearch = card.dataset.name.includes(query);
      const matchesCategory = activeCategory === 'all' || card.dataset.cat === activeCategory;
      card.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      pills.forEach(function (p) { p.classList.remove('active'); });
      pill.classList.add('active');
      activeCategory = pill.dataset.cat;
      applyFilters();
    });
  });

  document.querySelectorAll('.add-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      const itemId = btn.dataset.id;

      btn.disabled = true;
      const originalText = btn.innerHTML;
      btn.textContent = 'Adding...';

      fetch('api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=add&item_id=' + encodeURIComponent(itemId),
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          btn.disabled = false;
          btn.innerHTML = originalText;

          if (data.error) {
            alert(data.error);
            return;
          }

          cartBadge.textContent = data.count;
          cartLabel.textContent = 'View order · ' + data.subtotal_formatted;

          if (data.count > 0) {
            cartBar.classList.add('visible');
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.innerHTML = originalText;
        });
    });
  });
});
