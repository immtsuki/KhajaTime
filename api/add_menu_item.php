<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

if (!isLoggedIn() || currentRole() !== 'kitchen') {
    header('Location: ../index.php');
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$categoryId = (int) ($_POST['category_id'] ?? 0);
$available = isset($_POST['available']) ? 1 : 0;

if ($name === '' || $price <= 0 || $categoryId <= 0) {
    header('Location: ../kitchen-menu.php?error=1');
    exit;
}

// Handle optional photo upload
$imagePath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid('food_') . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = 'assets/uploads/' . $fileName;
        }
    }
}

if ($itemId > 0) {
    // Update existing item
    if ($imagePath) {
        $stmt = mysqli_prepare($conn, "UPDATE menu_items SET name = ?, price = ?, category_id = ?, available = ?, image = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sdiisi', $name, $price, $categoryId, $available, $imagePath, $itemId);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE menu_items SET name = ?, price = ?, category_id = ?, available = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sdiii', $name, $price, $categoryId, $available, $itemId);
    }
    mysqli_stmt_execute($stmt);
} else {
    // Insert new item
    $stmt = mysqli_prepare($conn, "INSERT INTO menu_items (name, price, category_id, image, available) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sdisi', $name, $price, $categoryId, $imagePath, $available);
    mysqli_stmt_execute($stmt);
}

header('Location: ../kitchen-menu.php?saved=1');
exit;
