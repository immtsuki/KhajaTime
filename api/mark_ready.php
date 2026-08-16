<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || currentRole() !== 'kitchen') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);

$stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'ready' WHERE id = ? AND status = 'preparing'");
mysqli_stmt_bind_param($stmt, 'i', $orderId);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not update order.']);
}
