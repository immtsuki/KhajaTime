<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireRole('student');


/* AJAX quantity update — keeps + / − working directly through cart.php */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_quantity') {
    header('Content-Type: application/json; charset=utf-8');

    $itemId = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($itemId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item.']);
        exit;
    }

    $cart = getCart();

    if ($quantity <= 0) {
        unset($cart[$itemId]);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM menu_items WHERE id = ? AND available = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$exists) {
            unset($cart[$itemId]);
        } else {
            $cart[$itemId] = $quantity;
        }
    }

    if (function_exists('setCart')) {
        setCart($cart);
    } else {
        $_SESSION['cart'] = $cart;
    }

    $subtotal = 0;
    $cartCount = array_sum($cart);

    if (!empty($cart)) {
        $ids = array_map('intval', array_keys($cart));
        $idList = implode(',', $ids);
        $result = mysqli_query($conn, "SELECT id, price FROM menu_items WHERE id IN ($idList)");

        while ($row = mysqli_fetch_assoc($result)) {
            $subtotal += (float)$row['price'] * (int)$cart[$row['id']];
        }
    }

    echo json_encode([
        'success' => true,
        'quantity' => isset($cart[$itemId]) ? (int)$cart[$itemId] : 0,
        'subtotal' => $subtotal,
        'cartCount' => $cartCount
    ]);
    exit;
}

$pageTitle = 'Your order';
$cart = getCart();

$lines = [];
$subtotal = 0;
$error = '';

/*
 * Create the order from the current cart.
 * This keeps cash/takeaway orders and successful eSewa payments on the same flow.
 */
function createOrderFromCart($conn, $cart, $userId) {
    if (empty($cart)) {
        return 0;
    }

    $ids = array_map('intval', array_keys($cart));
    $idList = implode(',', $ids);
    $result = mysqli_query($conn, "SELECT id, name, price, available FROM menu_items WHERE id IN ($idList)");

    $items = [];
    $total = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $qty = max(0, (int)($cart[$row['id']] ?? 0));
        if ($qty <= 0) {
            continue;
        }

        // Do not create an order with an item that was made unavailable.
        if (!(int)$row['available']) {
            continue;
        }

        $items[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'qty' => $qty
        ];
        $total += (float)$row['price'] * $qty;
    }

    if (empty($items)) {
        return 0;
    }

    // Tokens reset daily.
    $tokenStmt = mysqli_prepare($conn,
        "SELECT COALESCE(MAX(token_number), 0) + 1 AS next_token
         FROM orders
         WHERE DATE(created_at) = CURDATE()"
    );
    mysqli_stmt_execute($tokenStmt);
    $tokenRow = mysqli_fetch_assoc(mysqli_stmt_get_result($tokenStmt));
    $token = (int)$tokenRow['next_token'];

    mysqli_begin_transaction($conn);

    try {
        $orderStmt = mysqli_prepare($conn,
            "INSERT INTO orders (user_id, token_number, status, total, created_at)
             VALUES (?, ?, 'preparing', ?, NOW())"
        );
        mysqli_stmt_bind_param($orderStmt, 'iid', $userId, $token, $total);
        if (!mysqli_stmt_execute($orderStmt)) {
            throw new Exception('Could not create order.');
        }

        $orderId = mysqli_insert_id($conn);

        $itemStmt = mysqli_prepare($conn,
            "INSERT INTO order_items (order_id, item_name, quantity, is_checked)
             VALUES (?, ?, ?, 0)"
        );

        foreach ($items as $item) {
            mysqli_stmt_bind_param($itemStmt, 'isi', $orderId, $item['name'], $item['qty']);
            if (!mysqli_stmt_execute($itemStmt)) {
                throw new Exception('Could not save order items.');
            }
        }

        mysqli_commit($conn);

        // getCart() is backed by the normal session cart in this project.
        $_SESSION['cart'] = [];

        return $orderId;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return 0;
    }
}

/*
 * Successful eSewa return.
 * eSewa sends the response as a Base64 encoded JSON payload in "data".
 * Verify the signature before creating the order.
 */
if (isset($_GET['esewa']) && $_GET['esewa'] === 'success' && !empty($_GET['data'])) {
    $decoded = base64_decode($_GET['data'], true);
    $response = $decoded ? json_decode($decoded, true) : null;

    $secretKey = '8gBm/:&EnhH.1/q';
    $expected = '';

    if ($response && isset(
        $response['transaction_code'],
        $response['status'],
        $response['total_amount'],
        $response['transaction_uuid'],
        $response['product_code'],
        $response['signed_field_names'],
        $response['signature']
    )) {
        $signedMessage = 'transaction_code=' . $response['transaction_code']
            . ',status=' . $response['status']
            . ',total_amount=' . $response['total_amount']
            . ',transaction_uuid=' . $response['transaction_uuid']
            . ',product_code=' . $response['product_code']
            . ',signed_field_names=' . $response['signed_field_names'];

        $expected = base64_encode(hash_hmac('sha256', $signedMessage, $secretKey, true));
    }

    $sessionTxn = $_SESSION['esewa_transaction_uuid'] ?? '';
    $sessionAmount = isset($_SESSION['esewa_amount']) ? (float)$_SESSION['esewa_amount'] : 0;
    $returnedAmount = isset($response['total_amount']) ? (float)$response['total_amount'] : 0;

    if (
        $response &&
        hash_equals($expected, $response['signature']) &&
        $response['status'] === 'COMPLETE' &&
        $response['product_code'] === 'EPAYTEST' &&
        $sessionTxn !== '' &&
        hash_equals($sessionTxn, $response['transaction_uuid']) &&
        abs($sessionAmount - $returnedAmount) < 0.01
    ) {
        $orderId = createOrderFromCart($conn, $_SESSION['cart'] ?? [], $_SESSION['user_id']);

        unset($_SESSION['esewa_transaction_uuid'], $_SESSION['esewa_amount']);

        if ($orderId) {
            header('Location: order-status.php?id=' . $orderId);
            exit;
        }

        $error = 'Payment was successful, but the order could not be created. Please contact the kitchen staff.';
    } else {
        $error = 'The eSewa payment could not be verified.';
    }
}

/* Cash / takeaway order */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cash_order') {
    $orderId = createOrderFromCart($conn, $cart, $_SESSION['user_id']);

    if ($orderId) {
        header('Location: order-status.php?id=' . $orderId);
        exit;
    }

    $error = 'Could not place the order. Please check that all selected items are still available.';
}

/* eSewa sandbox checkout */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'esewa_order') {
    if (empty($cart)) {
        $error = 'Your cart is empty.';
    } else {
        $ids = array_map('intval', array_keys($cart));
        $idList = implode(',', $ids);
        $checkResult = mysqli_query($conn, "SELECT id, price, available FROM menu_items WHERE id IN ($idList)");

        $paymentTotal = 0;
        while ($row = mysqli_fetch_assoc($checkResult)) {
            $qty = max(0, (int)($cart[$row['id']] ?? 0));
            if ($qty > 0 && (int)$row['available']) {
                $paymentTotal += (float)$row['price'] * $qty;
            }
        }

        if ($paymentTotal <= 0) {
            $error = 'Your cart contains no available items.';
        } else {
            $transactionUuid = date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $_SESSION['esewa_transaction_uuid'] = $transactionUuid;
            $_SESSION['esewa_amount'] = $paymentTotal;

            $amount = number_format($paymentTotal, 2, '.', '');
            $taxAmount = '0';
            $serviceCharge = '0';
            $deliveryCharge = '0';
            $totalAmount = $amount;
            $productCode = 'EPAYTEST';

            $signedFieldNames = 'total_amount,transaction_uuid,product_code';
            $signedMessage = 'total_amount=' . $totalAmount
                . ',transaction_uuid=' . $transactionUuid
                . ',product_code=' . $productCode;
            $signature = base64_encode(hash_hmac('sha256', $signedMessage, $secretKey, true));

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $successUrl = $scheme . '://' . $host . $basePath . '/cart.php?esewa=success';
            $failureUrl = $scheme . '://' . $host . $basePath . '/cart.php?esewa=failure';

            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Redirecting to eSewa</title>
              <link rel="stylesheet" href="assets/css/style.css">
            </head>
            <body>
              <div class="page">
                <main class="content">
                  <div class="auth-wrap" style="padding-top:80px;">
                    <div class="auth-card" style="text-align:center;">
                      <h2>Opening eSewa sandbox…</h2>
                      <p class="auth-sub">Amount: <?php echo formatPrice($paymentTotal); ?></p>
                      <form id="esewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
                        <input type="hidden" name="amount" value="<?php echo e($amount); ?>">
                        <input type="hidden" name="tax_amount" value="<?php echo e($taxAmount); ?>">
                        <input type="hidden" name="total_amount" value="<?php echo e($totalAmount); ?>">
                        <input type="hidden" name="transaction_uuid" value="<?php echo e($transactionUuid); ?>">
                        <input type="hidden" name="product_code" value="<?php echo e($productCode); ?>">
                        <input type="hidden" name="product_service_charge" value="<?php echo e($serviceCharge); ?>">
                        <input type="hidden" name="product_delivery_charge" value="<?php echo e($deliveryCharge); ?>">
                        <input type="hidden" name="success_url" value="<?php echo e($successUrl); ?>">
                        <input type="hidden" name="failure_url" value="<?php echo e($failureUrl); ?>">
                        <input type="hidden" name="signed_field_names" value="<?php echo e($signedFieldNames); ?>">
                        <input type="hidden" name="signature" value="<?php echo e($signature); ?>">
                        <button class="btn btn-primary" type="submit">Continue to eSewa</button>
                      </form>
                    </div>
                  </div>
                </main>
              </div>
              <script>document.getElementById('esewaForm').submit();</script>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

if (isset($_GET['esewa']) && $_GET['esewa'] === 'failure') {
    unset($_SESSION['esewa_transaction_uuid'], $_SESSION['esewa_amount']);
    $error = 'eSewa payment was cancelled or failed. Your cart is still saved.';
}

$cart = getCart();
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

<?php if ($error): ?>
  <div class="alert alert-error"><?php echo e($error); ?></div>
<?php endif; ?>

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
            <button type="button" class="qty-btn dec" aria-label="Decrease">−</button>
            <span class="qty-value"><?php echo $line['qty']; ?></span>
            <button type="button" class="qty-btn inc" aria-label="Increase">+</button>
          </div>
          <div class="order-line-price line-total"><?php echo formatPrice($line['line_total']); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="subtotal-row">
      <span class="label">Subtotal</span>
      <span class="amount" id="subtotalAmount"><?php echo formatPrice($subtotal); ?></span>
    </div>

    <div class="field" style="margin-top:20px;">
      <label>Payment</label>

      <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px solid #ddd;border-radius:10px;margin-top:8px;cursor:pointer;">
        <input type="radio" name="payment_choice" value="cash" checked>
        <span>
          <strong>Cash / Takeaway</strong><br>
          <small>Pay at the counter when you pick up your order.</small>
        </span>
      </label>

      <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px solid #ddd;border-radius:10px;margin-top:8px;cursor:pointer;">
        <input type="radio" name="payment_choice" value="esewa">
        <span>
          <strong>eSewa</strong><br>
          <small>Pay online using the eSewa sandbox.</small>
        </span>
      </label>
    </div>

    <form method="POST" id="cashForm" style="margin-top:14px;">
      <input type="hidden" name="action" value="cash_order">
      <button class="btn btn-primary btn-block" type="submit" id="placeOrderBtn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Place order · Cash / Takeaway
      </button>
    </form>

    <form method="POST" id="esewaFormStart" style="display:none;margin-top:14px;">
      <input type="hidden" name="action" value="esewa_order">
      <button class="btn btn-primary btn-block" type="submit">
        Pay with eSewa · <?php echo formatPrice($subtotal); ?>
      </button>
    </form>

    <p class="order-hint">🎟️ Your pickup token will appear after the order is placed.</p>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>


<script>
/* Cart quantity controls */
(function () {
  const orderLines = document.getElementById('orderLines');
  if (!orderLines) return;

  const money = new Intl.NumberFormat('en-NP', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  async function updateQuantity(line, newQuantity) {
    const itemId = line.dataset.id;
    const buttons = line.querySelectorAll('.qty-btn');
    buttons.forEach(function (btn) { btn.disabled = true; });

    try {
      const body = new URLSearchParams();
      body.set('action', 'update_quantity');
      body.set('item_id', itemId);
      body.set('quantity', String(newQuantity));

      const response = await fetch('cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: body.toString()
      });

      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Could not update cart.');

      if (data.quantity <= 0) {
        line.remove();
      } else {
        line.querySelector('.qty-value').textContent = data.quantity;
        const price = Number(line.dataset.price);
        line.querySelector('.line-total').textContent =
          'Rs. ' + money.format(price * data.quantity);
      }

      document.getElementById('subtotalAmount').textContent =
        'Rs. ' + money.format(Number(data.subtotal));

      /* Update eSewa button amount too */
      const esewaButton = document.querySelector('#esewaFormStart button');
      if (esewaButton) {
        esewaButton.textContent = 'Pay with eSewa · Rs. ' + money.format(Number(data.subtotal));
      }

      if (!document.querySelector('.order-line')) {
        window.location.reload();
      }
    } catch (error) {
      console.error(error);
      alert('Unable to update the quantity. Please try again.');
    } finally {
      buttons.forEach(function (btn) { btn.disabled = false; });
    }
  }

  orderLines.addEventListener('click', function (event) {
    const button = event.target.closest('.qty-btn');
    if (!button) return;

    event.preventDefault();

    const line = button.closest('.order-line');
    if (!line) return;

    const current = parseInt(line.querySelector('.qty-value').textContent, 10) || 0;
    const next = button.classList.contains('inc') ? current + 1 : current - 1;

    updateQuantity(line, Math.max(0, next));
  });
})();
</script>

<script>
(function () {
  const cashForm = document.getElementById('cashForm');
  const esewaForm = document.getElementById('esewaFormStart');
  if (!cashForm || !esewaForm) return;

  document.querySelectorAll('input[name="payment_choice"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      const isEsewa = this.value === 'esewa';
      cashForm.style.display = isEsewa ? 'none' : 'block';
      esewaForm.style.display = isEsewa ? 'block' : 'none';
    });
  });
})();
</script>
