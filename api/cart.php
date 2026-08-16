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

$action = $_POST['action'] ?? '';
$itemId = (int) ($_POST['item_id'] ?? 0);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        // Verify item exists and is available
        $stmt = mysqli_prepare($conn, "SELECT id, available FROM menu_items WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$item || !$item['available']) {
            http_response_code(400);
            echo json_encode(['error' => 'Item not available']);
            exit;
        }

        $_SESSION['cart'][$itemId] = ($_SESSION['cart'][$itemId] ?? 0) + 1;
        break;

    case 'increment':
        $_SESSION['cart'][$itemId] = ($_SESSION['cart'][$itemId] ?? 0) + 1;
        break;

    case 'decrement':
        if (isset($_SESSION['cart'][$itemId])) {
            $_SESSION['cart'][$itemId]--;
            if ($_SESSION['cart'][$itemId] <= 0) {
                unset($_SESSION['cart'][$itemId]);
            }
        }
        break;

    case 'remove':
        unset($_SESSION['cart'][$itemId]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
}

// Compute count + subtotal
$cart = getCart();
$count = array_sum($cart);
$subtotal = 0;

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $idList = implode(',', $ids);
    $result = mysqli_query($conn, "SELECT id, price FROM menu_items WHERE id IN ($idList)");
    while ($row = mysqli_fetch_assoc($result)) {
        $subtotal += $row['price'] * $cart[$row['id']];
    }
}

echo json_encode([
    'count' => $count,
    'subtotal' => $subtotal,
    'subtotal_formatted' => formatPrice($subtotal),
    'item_qty' => $cart[$itemId] ?? 0,
]);
