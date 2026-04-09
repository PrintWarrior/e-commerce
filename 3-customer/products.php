<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

$category = trim((string)($_GET['category'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));
$sort = $_GET['sort'] ?? 'newest';
$allowedSorts = [
    'newest' => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'stock_desc' => 'p.stock DESC',
    'recommended' => 'p.stock DESC, p.created_at DESC',
];
$orderBy = $allowedSorts[$sort] ?? $allowedSorts['newest'];

$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$sql = "
    SELECT p.id, p.name, p.description, p.price, p.stock, p.image, p.created_at,
           c.name AS category_name,
           CASE WHEN w.id IS NULL THEN 0 ELSE 1 END AS in_wishlist
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN wishlists w ON w.product_id = p.id AND w.customer_id = ?
    WHERE 1=1
";
$params = [$customer_id];

if ($category !== '') {
    $sql .= " AND c.name = ? ";
    $params[] = $category;
}
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?) ";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY {$orderBy}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

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

$returnQuery = http_build_query(array_filter([
    'category' => $category,
    'search' => $search,
    'sort' => $sort !== 'newest' ? $sort : null,
]));
$returnUrl = 'products.php' . ($returnQuery ? '?' . $returnQuery : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/customer_products.css">
    <link rel="stylesheet" href="../css/customer_dash.css">
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
            <form method="get" class="search-wrap">
                <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products...">
                <button type="submit">Search</button>
            </form>
            <div class="nav-icons">
                <a href="dashboard.php" title="Home"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 12 15 12 15 21"/></svg></a>
                <a href="orders.php" title="Orders"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></a>
                <a href="wishlist.php" title="Wishlist"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></a>
                <a href="cart.php" title="Cart"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></a>
            </div>
        </div>
    </nav>

    <main class="page">
        <section class="hero">
            <div class="panel hero-copy">
                <h1>All Products</h1>
                <p>This page pulls directly from the `products` table and shows each product's name, description, price, stock, and image. The new heart button adds products into your wishlist flow.</p>
                <div class="hero-stats">
                    <div class="hero-stat"><strong><?= count($products) ?></strong><span>matching products</span></div>
                    <div class="hero-stat"><strong><?= count($categories) ?></strong><span>categories</span></div>
                    <div class="hero-stat"><strong><?= count(array_filter($products, fn($item) => (int)$item['stock'] > 0)) ?></strong><span>in stock</span></div>
                </div>
            </div>
            <form method="get" class="panel filters">
                <h2>Filter Products</h2>
                <div class="field">
                    <label for="category">Category</label>
                    <select name="category" id="category">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $category === $cat['name'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, description, or category">
                </div>
                <div class="field">
                    <label for="sort">Sort by</label>
                    <select name="sort" id="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Recommended</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stock: High to Low</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="products.php" class="btn-secondary">Reset</a>
                </div>
            </form>
        </section>

        <?php if ($flash): ?>
            <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['text']) ?></div>
        <?php endif; ?>

        <section class="results-bar">
            <div>
                <h2>Products</h2>
                <p><?= count($products) ?> item<?= count($products) !== 1 ? 's' : '' ?> found</p>
            </div>
            <div class="chips">
                <?php if ($category !== ''): ?><span class="chip">Category: <?= htmlspecialchars($category) ?></span><?php endif; ?>
                <?php if ($search !== ''): ?><span class="chip">Search: <?= htmlspecialchars($search) ?></span><?php endif; ?>
                <span class="chip">Sort: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $sort))) ?></span>
            </div>
        </section>

        <?php if (empty($products)): ?>
            <section class="empty-state">
                <h3>No products matched your filters.</h3>
                <p>Try another category or search term.</p>
            </section>
        <?php else: ?>
            <section class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php $outOfStock = (int)$product['stock'] <= 0; ?>
                    <article class="product-card">
                        <a href="products_details.php?id=<?= (int)$product['id'] ?>" class="product-image">
                            <img src="../product_images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.opacity='.35'">
                        </a>
                        <div class="product-top">
                            <span class="category-tag"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                            <span class="stock-badge <?= $outOfStock ? 'out' : 'in' ?>"><?= $outOfStock ? 'Out of stock' : 'In stock' ?></span>
                        </div>
                        <a href="products_details.php?id=<?= (int)$product['id'] ?>" class="product-name"><?= htmlspecialchars($product['name']) ?></a>
                        <p class="product-description"><?= htmlspecialchars($product['description'] ?: 'No description available for this product yet.') ?></p>
                        <div class="product-meta">
                            <div>
                                <div class="price">₱<?= number_format((float)$product['price'], 2) ?></div>
                                <div class="stock-text">Stock: <?= (int)$product['stock'] ?></div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <form method="post" action="wishlist.php">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnUrl) ?>">
                                <button type="submit" name="add_to_wishlist" class="icon-btn <?= (int)$product['in_wishlist'] === 1 ? 'heart-active' : '' ?>" title="Add to wishlist">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="<?= (int)$product['in_wishlist'] === 1 ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </form>
                            <form method="post" action="add_to_cart.php" class="add-cart-wide">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnUrl) ?>">
                                <button type="submit" <?= $outOfStock ? 'disabled' : '' ?>><?= $outOfStock ? 'Unavailable' : 'Add to Cart' ?></button>
                            </form>
                            <form method="post" action="add_to_cart.php">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnUrl) ?>">
                                <button type="submit" class="icon-btn" title="Add to cart" <?= $outOfStock ? 'disabled' : '' ?>>
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

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
