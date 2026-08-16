<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || currentRole() !== 'kitchen') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);
$checked = ((int) ($_POST['checked'] ?? 0)) ? 1 : 0;

$stmt = mysqli_prepare($conn, "UPDATE order_items SET is_checked = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $checked, $itemId);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true]);
