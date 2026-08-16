<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || currentRole() !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$cart = getCart();

if (empty($cart)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your cart is empty.']);
    exit;
}

$ids = array_map('intval', array_keys($cart));
$idList = implode(',', $ids);
$result = mysqli_query($conn, "SELECT id, name, price, available FROM menu_items WHERE id IN ($idList)");

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[$row['id']] = $row;
}

// Re-validate availability at checkout time
foreach ($ids as $id) {
    if (!isset($items[$id]) || !$items[$id]['available']) {
        http_response_code(400);
        echo json_encode(['error' => 'One of the items in your cart is no longer available. Please review your order.']);
        exit;
    }
}

$subtotal = 0;
foreach ($items as $id => $item) {
    $subtotal += $item['price'] * $cart[$id];
}

mysqli_begin_transaction($conn);

try {
    $userId = $_SESSION['user_id'];
    $token = nextTokenNumber($conn);

    $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, token_number, status, total) VALUES (?, ?, 'preparing', ?)");
    mysqli_stmt_bind_param($stmt, 'iid', $userId, $token, $subtotal);
    mysqli_stmt_execute($stmt);
    $orderId = mysqli_insert_id($conn);

    $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, menu_item_id, item_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $id => $item) {
        $qty = $cart[$id];
        mysqli_stmt_bind_param($itemStmt, 'iisdi', $orderId, $id, $item['name'], $item['price'], $qty);
        mysqli_stmt_execute($itemStmt);
    }

    mysqli_commit($conn);
    clearCart();

    echo json_encode(['order_id' => $orderId, 'token' => $token]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'Could not place order. Please try again.']);
}
