<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isApprovedSeller($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get seller data
$stmt = $pdo->prepare("
    SELECT u.*, s.id as seller_id, s.business_name, s.business_address, s.phone as business_phone, s.tax_id 
    FROM users u 
    JOIN sellers s ON u.id = s.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$seller = $stmt->fetch();

$seller_id = $seller['seller_id'];
ensureOrderItemFulfillmentColumns();

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetchColumn();

// Get pending orders count
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id) 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE p.seller_id = ? AND oi.status = 'pending'
");
$stmt->execute([$seller_id]);
$pending_orders = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_dashboard.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/seller_header.css">
</head>
<body>

 <div class="seller-wrapper">
     <!-- Mobile Hamburger Toggle -->
     <input type="checkbox" id="seller-menu-toggle" class="seller-menu-toggle">
     <label for="seller-menu-toggle" class="seller-hamburger">
         <span></span>
         <span></span>
         <span></span>
     </label>

     <!-- Sidebar -->
     <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="../images/logo.png" alt="Beauty Mart">
                <span>Seller Center</span>
            </div>
        </div>
        
        <div class="seller-info">
    <?php if (!empty($seller['profile_pic']) && file_exists("../uploads/profile_images/" . $seller['profile_pic'])): ?>
        <img src="../uploads/profile_images/<?= htmlspecialchars($seller['profile_pic']) ?>" alt="Shop Logo" class="shop-logo">
    <?php else: ?>
        <div class="shop-logo-placeholder">
            <?= strtoupper(substr($seller['firstname'] ?? $seller['username'], 0, 1)) ?>
        </div>
    <?php endif; ?>
    <h3><?= htmlspecialchars($seller['business_name'] ?? $seller['username']) ?></h3>
    <p><?= htmlspecialchars($seller['email']) ?></p>
</div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                <span class="icon">📦</span>
                <span>Orders</span>
                <?php if ($pending_orders > 0): ?>
                    <span class="badge"><?= $pending_orders ?></span>
                <?php endif; ?>
            </a>
            <a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                <span class="icon">🛍️</span>
                <span>Products</span>
            </a>
            <a href="earnings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'active' : '' ?>">
                <span class="icon">💰</span>
                <span>Earnings</span>
            </a>
            <!--<a href="reviews.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : '' ?>">
                <span class="icon">⭐</span>
                <span>Reviews</span>-->
            </a>
            <a href="shipping.php" class="<?= basename($_SERVER['PHP_SELF']) == 'shipping.php' ? 'active' : '' ?>">
                <span class="icon">🚚</span>
                <span>Shipping</span>
            </a>
            <a href="account.php" class="<?= basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span>Account</span>
            </a>
            <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
                <span class="icon">🔔</span>
                <span>Notifications</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <a href="../logout.php" class="logout">
                <span class="icon">🚪</span>
                <span>Logout</span>
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
