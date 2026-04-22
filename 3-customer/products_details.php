<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    $_SESSION['cart_error'] = 'Invalid product.';
    redirect('products.php');
}

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.description, p.price, p.stock, p.image, p.created_at,
           c.name AS category_name,
           s.business_name AS seller_name,
           CASE WHEN w.id IS NULL THEN 0 ELSE 1 END AS in_wishlist
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN sellers s ON s.id = p.seller_id
    LEFT JOIN wishlists w ON w.product_id = p.id AND w.customer_id = ?
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$customer_id, $product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['cart_error'] = 'Product not found.';
    redirect('products.php');
}

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.stock, p.image
    FROM products p
    WHERE p.id != ? AND (p.category_id <=> (SELECT category_id FROM products WHERE id = ?))
    ORDER BY p.created_at DESC
    LIMIT 4
");
$stmt->execute([$product_id, $product_id]);
$related_products = $stmt->fetchAll();

if (count($related_products) < 4) {
    $exclude = array_merge([$product_id], array_column($related_products, 'id'));
    $placeholders = implode(',', array_fill(0, count($exclude), '?'));
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock, image
        FROM products
        WHERE id NOT IN ($placeholders)
        ORDER BY created_at DESC
        LIMIT " . (4 - count($related_products))
    );
    $stmt->execute($exclude);
    $related_products = array_merge($related_products, $stmt->fetchAll());
}

$flash = null;
foreach (['cart_success' => 'success', 'cart_warning' => 'warning', 'cart_error' => 'error'] as $key => $type) {
    if (isset($_SESSION[$key])) {
        $flash = ['type' => $type, 'text' => $_SESSION[$key]];
        unset($_SESSION[$key]);
        break;
    }
}
if (!$flash && isset($_SESSION['wishlist_msg'])) {
    $flash = $_SESSION['wishlist_msg'];
    unset($_SESSION['wishlist_msg']);
}

$returnUrl = 'products_details.php?id=' . $product_id;
$maxQty = max(1, (int)$product['stock']);
$qtyValue = $product['stock'] > 0 ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/customer_products.css">
    <link rel="stylesheet" href="../css/customer_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
    <div class="top-bar">
        <div class="inner">
            <div class="left">
                <a href="../includes/about.php">About Us</a>
                <span>|</span>
                <a href="../includes/contact.php">Contact Us</a>
            </div>
            <div class="right">
                <a href="profile.php"><?php displayAvatar($_SESSION['user_id'], $_SESSION['username']); ?></a>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>
    </div>

    <nav class="navbar">
        <div class="inner">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon"><img src="../images/logo.png" alt="Logo"></div>
                <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
            </a>
            <form method="get" action="products.php" class="search-wrap">
                <input type="text" name="search" placeholder="Search products...">
                <button type="submit">Search</button>
             </form>
             
             <!-- Hamburger Menu Toggle (hidden checkbox) -->
             <input type="checkbox" id="menu-toggle">
             
             <!-- Hamburger Button -->
             <label for="menu-toggle" class="hamburger">
                 <span></span>
                 <span></span>
                 <span></span>
             </label>
             
             <!-- Mobile Navigation Menu -->
             <div class="nav-menu">
                 <a href="products.php">Products</a>
                 <a href="orders.php">Orders</a>
                 <a href="wishlist.php">Wishlist</a>
                 <a href="cart.php">Cart</a>
                 <a href="profile.php">Profile</a>
                 <a href="../logout.php">Logout</a>
             </div>

             <div class="nav-icons">
                <a href="dashboard.php" title="Home"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 12 15 12 15 21"/></svg></a>
                <a href="orders.php" title="Orders"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></a>
                <a href="wishlist.php" title="Wishlist"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></a>
                <a href="cart.php" title="Cart"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></a>
            </div>
        </div>
    </nav>

    <main class="page">
        <div class="page-inner">
            <div class="page-title-row">
                <h1>Product Details</h1>
                
            </div>

            <?php if ($flash): ?>
                <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['text']) ?></div>
            <?php endif; ?>

            <section class="panel" style="padding:24px;">
                <div style="display:grid;grid-template-columns:minmax(280px,420px) 1fr;gap:24px;align-items:start;">
                    <div class="product-image" style="aspect-ratio:1/1;">
                        <img src="../product_images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.opacity='.35'">
                    </div>

                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <div class="product-top">
                            <span class="category-tag"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                            <span class="stock-badge <?= (int)$product['stock'] > 0 ? 'in' : 'out' ?>">
                                <?= (int)$product['stock'] > 0 ? 'In stock' : 'Out of stock' ?>
                            </span>
                        </div>

                        <div>
                            <h2 style="font-size:34px;line-height:1.1;margin-bottom:10px;"><?= htmlspecialchars($product['name']) ?></h2>
                            <div class="price">₱<?= number_format((float)$product['price'], 2) ?></div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
                            <div class="hero-stat">
                                <strong><?= (int)$product['stock'] ?></strong>
                                <span>items in stock</span>
                            </div>
                            <div class="hero-stat">
                                <strong><?= htmlspecialchars($product['seller_name'] ?: 'Beauty Mart') ?></strong>
                                <span>seller</span>
                            </div>
                        </div>

                        <div class="panel" style="padding:18px;background:#fff8fa;">
                            <h3 style="margin-bottom:8px;">Description</h3>
                            <p class="product-description" style="min-height:auto;"><?= nl2br(htmlspecialchars($product['description'] ?: 'No description available for this product yet.')) ?></p>
                        </div>

                        <div class="card-actions" style="flex-wrap:wrap;">
                            <form method="post" action="wishlist.php">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnUrl) ?>">
                                <button type="submit" name="add_to_wishlist" class="icon-btn <?= (int)$product['in_wishlist'] === 1 ? 'heart-active' : '' ?>" title="Add to wishlist">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="<?= (int)$product['in_wishlist'] === 1 ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </form>

                            <form method="post" action="add_to_cart.php" class="add-cart-wide" style="display:flex;gap:10px;align-items:center;flex:1;">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnUrl) ?>">
                                <input type="number" name="quantity" min="1" max="<?= $maxQty ?>" value="<?= $qtyValue ?>" <?= (int)$product['stock'] > 0 ? '' : 'disabled' ?> style="width:84px;height:42px;border:1.5px solid var(--pink-mid);border-radius:999px;padding:0 14px;">
                                <button type="submit" <?= (int)$product['stock'] > 0 ? '' : 'disabled' ?>><?= (int)$product['stock'] > 0 ? 'Add to Cart' : 'Unavailable' ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <?php if (!empty($related_products)): ?>
                <section>
                    <div class="results-bar" style="margin-bottom:14px;">
                        <div>
                            <h2>You Might Also Like</h2>
                            <p>More items from the catalog.</p>
                        </div>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($related_products as $related): ?>
                            <article class="product-card">
                                <a href="products_details.php?id=<?= (int)$related['id'] ?>" class="product-image">
                                    <img src="../product_images/<?= htmlspecialchars($related['image']) ?>" alt="<?= htmlspecialchars($related['name']) ?>" onerror="this.style.opacity='.35'">
                                </a>
                                <a href="products_details.php?id=<?= (int)$related['id'] ?>" class="product-name"><?= htmlspecialchars($related['name']) ?></a>
                                <div class="product-meta">
                                    <div>
                                        <div class="price">₱<?= number_format((float)$related['price'], 2) ?></div>
                                        <div class="stock-text">Stock: <?= (int)$related['stock'] ?></div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
            <div class="socials">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Website">o</a>
                <a href="#" title="LinkedIn">in</a>
            </div>
        </div>
    </footer>
</body>
</html>
