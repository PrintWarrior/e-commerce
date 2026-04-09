<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

// Get customer ID from user ID
$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    // If customer doesn't exist in customers table, create it
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

// Get cart_id from GET parameter
$cart_id = isset($_GET['cart_id']) ? (int)$_GET['cart_id'] : 0;

if ($cart_id > 0) {
    // Verify cart belongs to this customer before deleting
    $stmt = $pdo->prepare("SELECT id FROM carts WHERE id = ? AND customer_id = ?");
    $stmt->execute([$cart_id, $customer_id]);
    
    if ($stmt->rowCount() > 0) {
        // Delete the cart item
        $stmt = $pdo->prepare("DELETE FROM carts WHERE id = ? AND customer_id = ?");
        $stmt->execute([$cart_id, $customer_id]);
        
        // Optional: Set success message
        $_SESSION['cart_success'] = "Item removed from cart successfully.";
    } else {
        // Cart item doesn't belong to this customer
        $_SESSION['cart_error'] = "Cart item not found.";
    }
} else {
    $_SESSION['cart_error'] = "Invalid cart item.";
}

// Redirect back to cart page
redirect('cart.php');
?>