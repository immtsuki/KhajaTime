<?php
/**
 * Shared helper functions
 */

// Cart lives in the session: [ menu_item_id => qty ]
function getCart() {
    return $_SESSION['cart'] ?? [];
}

function cartCount() {
    $cart = getCart();
    return array_sum($cart);
}

function clearCart() {
    $_SESSION['cart'] = [];
}

// Generates the next token number for today (resets daily, starts at 1)
function nextTokenNumber($conn) {
    $result = mysqli_query($conn, "SELECT COALESCE(MAX(token_number), 0) + 1 AS next_token
        FROM orders WHERE DATE(created_at) = CURDATE()");
    $row = mysqli_fetch_assoc($result);
    return (int) $row['next_token'];
}

// Formats a price as "Rs. 120"
function formatPrice($amount) {
    return 'Rs. ' . number_format((float) $amount, ($amount == (int)$amount) ? 0 : 2);
}

// Returns true if an order is older than $minutes and still preparing (used for "Urgent" badge)
function isUrgent($createdAt, $status, $minutes = 15) {
    if ($status !== 'preparing') return false;
    $created = strtotime($createdAt);
    return (time() - $created) > ($minutes * 60);
}

function timeAgo($datetime) {
    return date('g:i A', strtotime($datetime));
}
