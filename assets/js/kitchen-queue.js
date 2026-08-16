document.addEventListener('DOMContentLoaded', function () {
  const queueGrid = document.getElementById('queueGrid');
  if (!queueGrid) return;

  queueGrid.addEventListener('change', function (e) {
    if (!e.target.classList.contains('item-check')) return;

    const row = e.target.closest('.queue-item-row');
    const itemId = row.dataset.itemId;
    const checked = e.target.checked;

    row.classList.toggle('checked', checked);

    fetch('api/toggle_item_check.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'item_id=' + encodeURIComponent(itemId) + '&checked=' + (checked ? 1 : 0),
    });
  });

  queueGrid.addEventListener('click', function (e) {
    const btn = e.target.closest('.mark-ready-btn');
    if (!btn) return;

    const card = btn.closest('.queue-card');
    const orderId = card.dataset.orderId;

    btn.disabled = true;
    btn.textContent = 'Marking ready...';

    fetch('api/mark_ready.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'order_id=' + encodeURIComponent(orderId),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.error) {
          alert(data.error);
          btn.disabled = false;
          btn.textContent = 'Mark as ready';
          return;
        }
        btn.className = 'btn btn-green btn-block ready-btn';
        btn.textContent = '✓ Ready ✓';
      });
  });

  // Live refresh every 20s so new incoming orders show up
  setInterval(function () {
    window.location.reload();
  }, 20000);
});
