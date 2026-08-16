document.addEventListener('DOMContentLoaded', function () {
  const orderLines = document.getElementById('orderLines');
  const subtotalAmount = document.getElementById('subtotalAmount');
  const placeOrderBtn = document.getElementById('placeOrderBtn');

  function formatRs(amount) {
    const isWhole = amount === Math.round(amount);
    return 'Rs. ' + amount.toLocaleString('en-IN', {
      minimumFractionDigits: isWhole ? 0 : 2,
      maximumFractionDigits: 2,
    });
  }

  function updateLine(row, qty, subtotal) {
    if (qty <= 0) {
      row.remove();
      if (!orderLines.querySelector('.order-line')) {
        window.location.reload();
        return;
      }
    } else {
      row.querySelector('.qty-value').textContent = qty;
      const price = parseFloat(row.dataset.price);
      row.querySelector('.line-total').textContent = formatRs(price * qty);
    }
    subtotalAmount.textContent = formatRs(subtotal);
  }

  if (orderLines) {
    orderLines.addEventListener('click', function (e) {
      const btn = e.target.closest('.qty-btn');
      if (!btn) return;

      const row = btn.closest('.order-line');
      const itemId = row.dataset.id;
      const action = btn.classList.contains('inc') ? 'increment' : 'decrement';

      fetch('api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=' + action + '&item_id=' + encodeURIComponent(itemId),
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          updateLine(row, data.item_qty, data.subtotal);
        });
    });
  }

  if (placeOrderBtn) {
    placeOrderBtn.addEventListener('click', function () {
      placeOrderBtn.disabled = true;
      placeOrderBtn.textContent = 'Placing order...';

      fetch('api/place_order.php', { method: 'POST' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.error) {
            alert(data.error);
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = 'Place order';
            return;
          }
          window.location.href = 'order-status.php?id=' + data.order_id;
        })
        .catch(function () {
          placeOrderBtn.disabled = false;
          placeOrderBtn.textContent = 'Place order';
        });
    });
  }
});
