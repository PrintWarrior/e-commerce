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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = $_POST['cart_id'] ?? 0;
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // First, get the product_id from cart to check stock
    $stmt = $pdo->prepare("
        SELECT c.product_id, p.stock 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.id = ? AND c.customer_id = ?
    ");
    $stmt->execute([$cart_id, $customer_id]);
    $cart_item = $stmt->fetch();
    
    if ($cart_item) {
        // Check if requested quantity exceeds available stock
        if ($quantity > $cart_item['stock']) {
            // Set to max available stock
            $quantity = $cart_item['stock'];
            $_SESSION['cart_warning'] = "Only {$cart_item['stock']} items available in stock.";
        }
        
        // Update cart quantity
        $stmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND customer_id = ?");
        $stmt->execute([$quantity, $cart_id, $customer_id]);
    }
}

// Redirect back to cart page
redirect('cart.php');
?>