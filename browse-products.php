<?php
require_once 'includes/functions.php';
// Public products page - no login required

$category_id = $_GET['category_id'] ?? null;
$category_name = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

// Build products query
$query = "
    SELECT p.*, 
           c.name as category_name,
           s.business_name as seller_name,
           u.username as seller_username
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE p.stock > 0
";

$params = [];

if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
} elseif ($category_name) {
    $query .= " AND c.name = ?";
    $params[] = $category_name;
}

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for the filter sidebar
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$all_categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category_name ? htmlspecialchars($category_name) : 'Products' ?> | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/index_browse.css">
    <link rel="icon" type="image/png" href="images/logo.png">
</head>
<body>

    <!-- ── Top Bar ──────────────────────────────────────────────────── -->
    <div class="top-bar">
        <div class="inner">
            <div class="left-links">
                <a href="includes/about.php">About Us</a>
                <span class="sep">|</span>
                <a href="includes/contact.php">Contact Us</a>
                <span class="sep">|</span>
                <a href="includes/sell.php">Start Selling</a>
            </div>
            <div class="right-links">
                <a href="includes/register.php">Sign Up</a>
                <span class="sep">|</span>
                <a href="includes/login.php">Login</a>
            </div>
        </div>
    </div>

    <!-- ── Navbar ───────────────────────────────────────────────────── -->
    <nav class="navbar">
        <div class="inner">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <img src="images/logo.png" alt="Beauty Mart">
                </div>
                <div class="logo-text">
                    <span>Beauty</span>
                    <span>Mart</span>
                </div>
            </a>

            <div class="search-wrap">
                <form method="get" style="display: flex; width: 100%;">
                    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search ?? '') ?>" style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px 0 0 4px;">
                    <button type="submit" style="padding: 8px 20px; background: #d4447e; color: white; border: none; cursor: pointer; border-radius: 0 4px 4px 0;">Search</button>
                </form>
            </div>

            <div class="nav-icons">
                <a href="index.php" title="Home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                        <polyline points="9 21 9 12 15 12 15 21"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- ── Main Content ────────────────────────────────────────────── -->
    <div class="section" style="padding: 40px 20px;">
        <div style="max-width: 1200px; margin: 0 auto;">

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Home</a> /
                <?php if ($category_name): ?>
                    <span><?= htmlspecialchars($category_name) ?></span>
                <?php elseif ($search): ?>
                    <span>Search: <?= htmlspecialchars($search) ?></span>
                <?php else: ?>
                    <span>All Products</span>
                <?php endif; ?>
            </div>

            <!-- Title -->
            <h1 style="margin-bottom: 30px;">
                <?php if ($category_name): ?>
                    <?= htmlspecialchars($category_name) ?>
                <?php elseif ($search): ?>
                    Search Results for "<?= htmlspecialchars($search) ?>"
                <?php else: ?>
                    Our Products
                <?php endif; ?>
            </h1>

            <!-- Category Filter -->
            <div class="filters-section">
                <strong>Filter by Category:</strong><br><br>
                <a href="browse-products.php" style="display: inline-block; padding: 8px 12px; margin-right: 10px; margin-bottom: 10px; background: <?= !$category_id && !$category_name ? '#d4447e' : '#e9e9e9' ?>; color: <?= !$category_id && !$category_name ? 'white' : 'black' ?>; border-radius: 4px; text-decoration: none;">All Products</a>

                <?php foreach ($all_categories as $cat): ?>
                    <a href="browse-products.php?category_id=<?= $cat['id'] ?>&category=<?= urlencode($cat['name']) ?>" 
                       style="display: inline-block; padding: 8px 12px; margin-right: 10px; margin-bottom: 10px; background: <?= $category_id == $cat['id'] ? '#d4447e' : '#e9e9e9' ?>; color: <?= $category_id == $cat['id'] ? 'white' : 'black' ?>; border-radius: 4px; text-decoration: none;">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="products-container">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?= !empty($product['image']) ? 'product_images/' . htmlspecialchars($product['image']) : 'images/placeholder.png' ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <!--<div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                            <div class="product-seller">By: <?= htmlspecialchars($product['seller_name'] ?? 'Unknown') ?></div>-->
                            <div class="product-stock">Stock: <?= $product['stock'] ?></div>
                            <a href="includes/login.php" class="cart-btn" title="Add to cart">
                        <!-- Cart icon -->
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-products">
                    No products found. <?php if ($search): ?>Try a different search term.<?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ── Footer ───────────────────────────────────────────────────── -->
    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2026 All Rights Reserved by <span>Beauty Mart</span>.</p>
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
