<?php
require_once 'includes/functions.php';
// No login required, just show products

$products_per_page = 5;
$current_page = max(1, (int) ($_GET['featured_page'] ?? 1));
$offset = ($current_page - 1) * $products_per_page;

// Count available featured products for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock > 0");
$count_stmt->execute();
$total_products = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_products / $products_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $products_per_page;
}

// Fetch products with seller and category information
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.name as category_name,
           s.business_name as seller_name,
           u.username as seller_username
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE p.stock > 0
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $products_per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch categories from database
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$db_categories = $stmt->fetchAll();

// If there are categories in database, use them, otherwise fallback to sample categories
if (!empty($db_categories)) {
    // Map category names to their images (if you have images stored)
    $category_images = [
        'Bath & Body' => 'images/category-img/cat-bath.png',
        'Fragrances' => 'images/category-img/cat-frag.png',
        'Haircare' => 'images/category-img/cat-hair.png',
        'Makeup' => 'images/category-img/cat-makeup.png',
        "Men's Grooming" => 'images/category-img/cat-men.png',
        'Skincare' => 'images/category-img/cat-skin.png',
        'Tools & Accessories' => 'images/category-img/cat-tools.png',
    ];
    
    $categories = [];
    foreach ($db_categories as $cat) {
        $categories[] = [
            'name' => $cat['name'],
            'img' => $category_images[$cat['name']] ?? 'images/category-img/default.png',
            'id' => $cat['id']
        ];
    }
} else {
    // Fallback to sample categories if no categories in database
    $categories = [
        ['name' => 'Bath & Body', 'img' => 'images/category-img/cat-bath.png'],
        ['name' => 'Fragrances', 'img' => 'images/category-img/cat-frag.png'],
        ['name' => 'Haircare', 'img' => 'images/category-img/cat-hair.png'],
        ['name' => 'Makeup', 'img' => 'images/category-img/cat-makeup.png'],
        ['name' => "Men's Grooming", 'img' => 'images/category-img/cat-men.png'],
        ['name' => 'Skincare', 'img' => 'images/category-img/cat-skin.png'],
        ['name' => 'Tools & Accessories', 'img' => 'images/category-img/cat-tools.png'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/responsive.css">
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

            <!-- Logo -->
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <img src="images/logo.png" alt="Beauty Mart">
                </div>
                <div class="logo-text">
                    <span>Beauty</span>
                    <span>Mart</span>
                </div>
            </a>

            <!-- Search -->
            <div class="search-wrap">
                <input type="text" placeholder="Search...">
                <button type="button">Search</button>
            </div>

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
                <a href="includes/about.php">About Us</a>
                <a href="includes/contact.php">Contact Us</a>
                <a href="includes/sell.php">Start Selling</a>
                <a href="includes/register.php">Sign Up</a>
                <a href="includes/login.php">Login</a>
            </div>

            <!-- Icons -->
            <div class="nav-icons">
                <a href="index.php" title="Home">
                    <!-- Home icon -->
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                        <polyline points="9 21 9 12 15 12 15 21"/>
                    </svg>
                </a>
                <!--<a href="cart.php" title="Cart">
                    
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </a>-->
            </div>

        </div>
    </nav>

    <!-- ── Categories ───────────────────────────────────────────────── -->
    <div class="section">
        <h2 class="section-title">Categories</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="browse-products.php?category=<?= urlencode($cat['name']) ?>&category_id=<?= $cat['id'] ?? '' ?>" class="cat-card">
                <div class="cat-img">
                    <img src="<?= htmlspecialchars($cat['img']) ?>"
                         alt="<?= htmlspecialchars($cat['name']) ?>"
                         onerror="this.style.display='3'">
                </div>
                <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Featured Products ────────────────────────────────────────── -->
    <hr class="divider">

    <div class="featured-header">
        <h2>Featured Products</h2>
    </div>

    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p class="empty-products">No products available at the moment. Please check back later!</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="prod-img">
                    <img src="product_images/<?= htmlspecialchars($product['image']) ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         onerror="this.style.opacity='.4'">
                    <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                        <!--<span class="low-stock-badge">Low Stock</span>-->
                    <?php endif; ?>
                </div>
                <div class="prod-name"><?= htmlspecialchars($product['name']) ?></div>
                <?php if (!empty($product['description'])): ?>
                    <div class="prod-desc"><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</div>
                <?php endif; ?>
               <!-- <?php if (!empty($product['seller_name'])): ?>
                    <div class="prod-seller">by <?= htmlspecialchars($product['seller_name']) ?></div>
                <?php endif; ?> -->
                <div class="prod-footer">
                    <span class="prod-price">---</span>
                    <a href="includes/login.php" class="cart-btn" title="Add to cart">
                        <!-- Cart icon -->
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                    </a>
                </div>
                <!-- <p class="login-note"><em>Please <a href="login.php">login</a> to purchase.</em></p> -->
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Footer ───────────────────────────────────────────────────── -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="index.php?featured_page=<?= $current_page - 1 ?>" class="page-link">Previous</a>
            <?php endif; ?>

            <span class="page-status">Page <?= $current_page ?> of <?= $total_pages ?></span>

            <?php if ($current_page < $total_pages): ?>
                <a href="index.php?featured_page=<?= $current_page + 1 ?>" class="page-link">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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
