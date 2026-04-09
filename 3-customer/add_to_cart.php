<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

function getReturnUrl(): string {
    $redirect = trim((string)($_POST['redirect_to'] ?? ''));
    if ($redirect !== '' && preg_match('/^[a-zA-Z0-9_\-\/?.=&]+$/', $redirect)) {
        return $redirect;
    }
    return 'dashboard.php';
}

// Get customer ID from user ID
$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    // If customer doesn't exist in customers table, create it
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $returnUrl = getReturnUrl();
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate inputs
    if ($product_id <= 0) {
        $_SESSION['cart_error'] = "Invalid product.";
        redirect($returnUrl);
    }
    
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Check if product exists and has stock
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND stock > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['cart_error'] = "Product not available or out of stock.";
        redirect($returnUrl);
    }
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM carts WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$customer_id, $product_id]);
    $cart_item = $stmt->fetch();
    
    if ($cart_item) {
        // Item exists, update quantity
        $new_qty = $cart_item['quantity'] + $quantity;
        
        // Check if new quantity exceeds available stock
        if ($new_qty > $product['stock']) {
            $new_qty = $product['stock'];
            $_SESSION['cart_warning'] = "Only {$product['stock']} items of '{$product['name']}' available. Quantity adjusted.";
        }
        
        $stmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_qty, $cart_item['id']]);
        $_SESSION['cart_success'] = "Cart updated. {$product['name']} quantity increased to {$new_qty}.";
        
    } else {
        // New item, insert into cart
        // Check if requested quantity exceeds stock
        if ($quantity > $product['stock']) {
            $quantity = $product['stock'];
            $_SESSION['cart_warning'] = "Only {$product['stock']} items of '{$product['name']}' available.";
        }
        
        $stmt = $pdo->prepare("INSERT INTO carts (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$customer_id, $product_id, $quantity]);
        $_SESSION['cart_success'] = "{$product['name']} added to cart!";
    }
    
    redirect($returnUrl);
}
?>
