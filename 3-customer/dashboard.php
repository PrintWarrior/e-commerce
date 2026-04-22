<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) {
    redirect('../login.php');
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Featured products (first 4)
$featured = array_slice($products, 0, 4);

// Recommended (next 4, or all if fewer)
$recommended = array_slice($products, 4, 4);
if (empty($recommended))
    $recommended = $featured;

$categories = [
    ['name' => 'Bath & Body', 'img' => '../images/category-img/cat-bath.png'],
    ['name' => 'Fragrances', 'img' => '../images/category-img/cat-frag.png'],
    ['name' => 'Haircare', 'img' => '../images/category-img/cat-hair.png'],
    ['name' => 'Makeup', 'img' => '../images/category-img/cat-makeup.png'],
    ['name' => "Men's Grooming", 'img' => '../images/category-img/cat-men.png'],
    ['name' => 'Skincare', 'img' => '../images/category-img/cat-skin.png'],
    ['name' => 'Tools & Accessories', 'img' => '../images/category-img/cat-tools.png'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Beauty Mart</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/customer_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>

<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="inner">
            <div class="left">
                <a href="../includes/about.php">About Us</a>
                <span class="sep">|</span>
                <a href="../includes/contact.php">Contact Us</a>
            </div>
            <div class="right">
                <a href="profile.php">
                    <?php displayAvatar($_SESSION['user_id'], $_SESSION['username']); ?>
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
                    <img src="../images/logo.png" alt="Logo">
                </div>
                <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
            </a>

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
                <a href="products.php">Products</a>
                <a href="orders.php">Orders</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="cart.php">Cart</a>
                <a href="profile.php">Profile</a>
                <a href="../logout.php">Logout</a>
            </div>

            <div class="nav-icons">
                <!-- Home -->
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z" />
                        <polyline points="9 21 9 12 15 12 15 21" />
                    </svg>
                </a>
                <!-- Orders -->
                <a href="orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" />
                        <path d="M8 21h8M12 17v4" />
                    </svg>
                </a>
                <!-- Wishlist -->
                <a href="wishlist.php" title="Wishlist">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </a>
                <!-- Cart -->
                <a href="cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <div class="page">

        <!-- Categories -->
        <div class="section">
            <h2 class="section-title">Categories</h2>
            <div class="cat-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="products.php?category=<?= urlencode($cat['name']) ?>" class="cat-card">
                        <div class="cat-img-wrap">
                            <img src="<?= htmlspecialchars($cat['img']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"
                                onerror="this.style.opacity='.3'">
                        </div>
                        <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Products -->
        <div class="section section-gap" style="padding-top:32px;">
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="products.php"><button class="see-more-btn">See More</button></a>
            </div>
            <div class="prod-grid">
                <?php foreach ($featured as $p): ?>
                    <div class="prod-card">
                        <div class="prod-img">
                            <img src="../product_images/<?= htmlspecialchars($p['image']) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.style.opacity='.3'">
                        </div>
                        <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="prod-footer">
                            <div class="prod-price-wrap">
                                <span class="prod-price">₱<?= number_format($p['price'], 0) ?></span>
                            </div>
                            <form method="post" action="add_to_cart.php" style="display:contents;">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to" value="dashboard.php">
                                <button type="submit" class="cart-btn" title="Add to wishlist" formaction="wishlist.php" name="add_to_wishlist">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                    </svg>
                                </button>
                                <button type="submit" class="cart-btn" title="Add to cart">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1" />
                                        <circle cx="20" cy="21" r="1" />
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recommended for You -->
        <div class="section section-gap" style="padding-top:28px;">
            <div class="section-header">
                <h2>Recommended for You</h2>
                <a href="products.php?sort=recommended"><button class="see-more-btn">See More</button></a>
            </div>
            <div class="prod-grid">
                <?php foreach ($recommended as $idx => $p): ?>
                    <?php
                    // Show "Sale" badge on 1st and 3rd cards (matching screenshot)
                    $showSale = in_array($idx, [0, 2]);
                    // Simulate an original price for items on sale
                    $origPrice = $showSale ? number_format($p['price'] * 1.2, 0) : null;
                    ?>
                    <div class="prod-card">
                        <?php if ($showSale): ?>
                            <span class="badge-sale">Sale</span>
                        <?php endif; ?>
                        <div class="prod-img">
                            <img src="../product_images/<?= htmlspecialchars($p['image']) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.style.opacity='.3'">
                        </div>
                        <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="prod-footer">
                            <div class="prod-price-wrap">
                                <?php if ($origPrice): ?>
                                    <span class="prod-price-orig">₱<?= $origPrice ?></span>
                                <?php endif; ?>
                                <span class="prod-price">₱<?= number_format($p['price'], 0) ?></span>
                            </div>
                            <form method="post" action="add_to_cart.php" style="display:contents;">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="redirect_to" value="dashboard.php">
                                <button type="submit" class="cart-btn" title="Add to wishlist" formaction="wishlist.php" name="add_to_wishlist">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                                    </svg>
                                </button>
                                <button type="submit" class="cart-btn" title="Add to cart">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="9" cy="21" r="1" />
                                        <circle cx="20" cy="21" r="1" />
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /page -->

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
