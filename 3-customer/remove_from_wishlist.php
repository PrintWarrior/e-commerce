<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) {
    redirect('../login.php');
}

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    redirect('dashboard.php');
}

if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$customer_id, $product_id]);
    $_SESSION['wishlist_success'] = "Item removed from wishlist.";
}

redirect('wishlist.php');
?>