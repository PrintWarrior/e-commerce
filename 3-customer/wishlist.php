<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

function wishlistReturnUrl(): string {
    $redirect = trim((string)($_POST['redirect_to'] ?? ''));
    if ($redirect !== '' && preg_match('/^[a-zA-Z0-9_\-\/?.=&]+$/', $redirect)) {
        return $redirect;
    }
    return 'wishlist.php';
}

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

// Create table if needed
$pdo->exec("CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

// Add to wishlist (POST)
if (isset($_POST['add_to_wishlist'])) {
    $returnUrl = wishlistReturnUrl();
    $pdo->prepare("INSERT IGNORE INTO wishlists (customer_id, product_id) VALUES (?, ?)")
        ->execute([$customer_id, (int)$_POST['product_id']]);
    $_SESSION['wishlist_msg'] = ['type' => 'success', 'text' => "Product added to wishlist!"];
    header('Location: ' . $returnUrl); exit;
}

// Remove single item (GET)
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?")
        ->execute([$customer_id, (int)$_GET['remove']]);
    $_SESSION['wishlist_msg'] = ['type' => 'success', 'text' => "Item removed from wishlist."];
    header('Location: wishlist.php'); exit;
}

// Clear wishlist (POST)
if (isset($_POST['clear_wishlist'])) {
    $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ?")
        ->execute([$customer_id]);
    $_SESSION['wishlist_msg'] = ['type' => 'success', 'text' => "Wishlist cleared."];
    header('Location: wishlist.php'); exit;
}

// Move to cart (POST)
if (isset($_POST['move_to_cart'])) {
    $pid = (int)$_POST['product_id'];
    // Add to cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM carts WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$customer_id, $pid]);
    $existing = $stmt->fetch();
    if ($existing) {
        $pdo->prepare("UPDATE carts SET quantity = quantity + 1 WHERE id = ?")
            ->execute([$existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO carts (customer_id, product_id, quantity) VALUES (?, ?, 1)")
            ->execute([$customer_id, $pid]);
    }
    // Remove from wishlist
    $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?")
        ->execute([$customer_id, $pid]);
    $_SESSION['wishlist_msg'] = ['type' => 'success', 'text' => "Item moved to cart!"];
    header('Location: wishlist.php'); exit;
}

$flash = $_SESSION['wishlist_msg'] ?? null;
unset($_SESSION['wishlist_msg']);

// Fetch wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, p.id AS product_id, p.name, p.price, p.image, p.stock, p.description,
           cat.name AS category_name, s.business_name AS seller_name
    FROM wishlists w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE w.customer_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$customer_id]);
$wishlist_items = $stmt->fetchAll();

// User profile pic
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_wishlist.css">
    <link rel="icon" href="../images/logo.png" type="image/png">
</head>
<body>

    <!-- Top strip -->
    <div class="top-strip"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="inner">
            <div class="left">
                <a href="../about.php">About Us</a>
                <span class="sep">|</span>
                <a href="../contact.php">Contact Us</a>
            </div>
            <div class="right">
                <a href="profile.php">
                    <div class="avatar-sm">
                        <?php if (!empty($u['profile_pic']) && file_exists("../uploads/profile_images/" . $u['profile_pic'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($u['profile_pic']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </a>
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="inner">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <img src="../images/logo.png" alt="Logo"
                         onerror="this.style.display='none';this.parentElement.textContent='🌸'">
                </div>
                <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
            </a>
            <div class="search-wrap">
                <input type="text" placeholder="Search...">
                <button type="button">Search</button>
            </div>
            <div class="nav-icons">
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 12 15 12 15 21"/></svg>
                </a>
                <a href="orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </a>
                <a href="wishlist.php" title="Wishlist" class="active">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </a>
                <a href="cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page -->
    <div class="page">
        <div class="page-inner">

            <!-- Title row -->
            <div class="page-title-row">
                <h1>My Wishlist 🩷</h1>
                <div class="title-right">
                    <?php if (!empty($wishlist_items)): ?>
                        <form method="post" style="display:contents;"
                              onsubmit="return confirm('Clear all items from your wishlist?')">
                            <button type="submit" name="clear_wishlist" class="btn-clear">
                                🗑 Clear All
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn-back">‹ Continue Shopping</a>
                </div>
            </div>

            <!-- Flash message -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['type'] === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($flash['text']) ?>
                </div>
            <?php endif; ?>

            <!-- Count chip -->
            <?php if (!empty($wishlist_items)): ?>
                <div class="wishlist-count">
                    ❤️ <span class="cnt-val"><?= count($wishlist_items) ?></span>
                    saved item<?= count($wishlist_items) !== 1 ? 's' : '' ?>
                </div>
            <?php endif; ?>

            <!-- Items -->
            <?php if (empty($wishlist_items)): ?>
                <div class="empty-wishlist">
                    <div class="ei">🩷</div>
                    <h3>Your wishlist is empty</h3>
                    <p>Save products you love and come back to them anytime.</p>
                    <a href="dashboard.php" class="btn-shop">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                    <div class="wish-card">

                        <!-- Remove button -->
                        <a href="wishlist.php?remove=<?= $item['product_id'] ?>"
                           class="btn-remove-wish"
                           title="Remove from wishlist"
                           onclick="return confirm('Remove from wishlist?')">×</a>

                        <!-- Image -->
                        <div class="wish-img-wrap">
                            <?php if (!empty($item['image']) && file_exists("../product_images/" . $item['image'])): ?>
                                <img src="../product_images/<?= htmlspecialchars($item['image']) ?>"
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     onerror="this.style.opacity='.3'">
                            <?php else: ?>
                                <div class="wish-img-placeholder">🛍️</div>
                            <?php endif; ?>
                        </div>

                        <!-- Out of stock overlay -->
                        <?php if ((int)$item['stock'] === 0): ?>
                            <div class="oos-overlay">
                                <span class="oos-tag">Out of Stock</span>
                            </div>
                        <?php endif; ?>

                        <!-- Card body -->
                        <div class="wish-card-body">
                            <?php if (!empty($item['category_name'])): ?>
                                <div class="wish-category"><?= htmlspecialchars($item['category_name']) ?></div>
                            <?php endif; ?>
                            <div class="wish-name"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if (!empty($item['seller_name'])): ?>
                                <div class="wish-seller">by <?= htmlspecialchars($item['seller_name']) ?></div>
                            <?php endif; ?>
                            <div class="wish-price">₱<?= number_format($item['price'], 2) ?></div>
                            <div class="wish-stock">
                                <?php if ((int)$item['stock'] > 0): ?>
                                    <span class="in-stock">✓ In Stock (<?= $item['stock'] ?>)</span>
                                <?php else: ?>
                                    <span class="out-stock">✗ Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="wish-actions">
                            <!-- Add to cart -->
                            <form method="post" action="add_to_cart.php" style="display:contents;">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit"
                                        class="btn-add-cart"
                                        <?= (int)$item['stock'] === 0 ? 'disabled' : '' ?>>
                                    🛒 Add to Cart
                                </button>
                            </form>

                            <!-- Move to cart (adds + removes from wishlist) -->
                            <?php if ((int)$item['stock'] > 0): ?>
                            <form method="post" style="display:contents;">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" name="move_to_cart" class="btn-move-cart">
                                    ↗ Move to Cart
                                </button>
                            </form>
                            <?php endif; ?>

                            <!-- View details -->
                            <a href="products_details.php?id=<?= $item['product_id'] ?>"
                               class="btn-view-details">
                                View Details
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
            <div class="socials">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Website">🌐</a>
                <a href="#" title="LinkedIn">in</a>
            </div>
        </div>
    </footer>

</body>
</html>
