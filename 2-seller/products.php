<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];

// ── FIX: Post → Redirect → Get (PRG) pattern ─────────────────
// Store messages in session, then redirect to avoid re-submission on refresh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
    $stmt->execute([$product_id]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_error'] = "Cannot delete a product with existing orders. Mark it as inactive instead.";
    } else {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$product_id, $seller_id]);
        $product = $stmt->fetch();

        if ($product) {
            $nameStmt = $pdo->prepare("SELECT name FROM products WHERE id = ? AND seller_id = ?");
            $nameStmt->execute([$product_id, $seller_id]);
            $productMeta = $nameStmt->fetch();

            if (!empty($product['image']) && $product['image'] !== 'default.jpg') {
                $img = "../product_images/" . $product['image'];
                if (file_exists($img)) unlink($img);
            }
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
            if ($stmt->execute([$product_id, $seller_id])) {
                logSystemEvent(
                    'seller_product_deleted',
                    'products',
                    $product_id,
                    "Seller {$seller['business_name']} deleted product '" . ($productMeta['name'] ?? 'Unknown product') . "'."
                );
                $_SESSION['flash_success'] = "Product deleted successfully!";
            } else {
                $_SESSION['flash_error'] = "Failed to delete product.";
            }
        } else {
            $_SESSION['flash_error'] = "Product not found.";
        }
    }

    // ── PRG redirect: prevents re-submission on browser refresh ──
    $qs = http_build_query(array_filter([
        'category' => $_POST['category_filter'] ?? '',
        'search'   => $_POST['search_val']      ?? '',
    ]));
    header('Location: products.php' . ($qs ? "?$qs" : ''));
    exit;
}

// Pick up flash messages (set before redirect)
$success = $_SESSION['flash_success'] ?? '';
$error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Categories for filter tabs ────────────────────────────────
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// ── Filters ───────────────────────────────────────────────────
$category_filter = $_GET['category'] ?? '';
$search          = $_GET['search']   ?? '';

// ── Build product query ───────────────────────────────────────
$query  = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.seller_id = ?";
$params = [$seller_id];

if ($category_filter) { $query .= " AND p.category_id = ?"; $params[] = $category_filter; }
if ($search)          { $query .= " AND p.name LIKE ?";      $params[] = "%$search%"; }

$query .= " ORDER BY p.created_at DESC";
$stmt   = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Counts for stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?"); $stmt->execute([$seller_id]);
$total_products = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND stock = 0"); $stmt->execute([$seller_id]);
$out_of_stock = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND stock > 0 AND stock < 10"); $stmt->execute([$seller_id]);
$low_stock = (int)$stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_products.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="seller-wrapper">

    <?php /* sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Products</h1>
                <p>Manage your store's product catalogue</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <a href="add_product.php" class="btn-add">+ Add Product</a>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Mini stats -->
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="ms-icon">🛍️</div>
                    <div>
                        <div class="ms-val"><?= $total_products ?></div>
                        <div class="ms-label">Total Products</div>
                    </div>
                </div>
                <div class="mini-stat warn">
                    <div class="ms-icon">⚠️</div>
                    <div>
                        <div class="ms-val"><?= $low_stock ?></div>
                        <div class="ms-label">Low Stock</div>
                    </div>
                </div>
                <div class="mini-stat danger">
                    <div class="ms-icon">❌</div>
                    <div>
                        <div class="ms-val"><?= $out_of_stock ?></div>
                        <div class="ms-label">Out of Stock</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar: category filters + search -->
            <div class="toolbar">
                <div class="cat-filters">
                    <a href="products.php"
                       class="filter-pill <?= !$category_filter ? 'active' : '' ?>">
                        All
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= $cat['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="filter-pill <?= $category_filter == $cat['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <form method="get" class="search-form">
                    <?php if ($category_filter): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search"
                           placeholder="Search products…"
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Result count -->
            <?php if ($search || $category_filter): ?>
            <p class="result-count">
                Showing <strong><?= count($products) ?></strong>
                result<?= count($products) !== 1 ? 's' : '' ?>
                <?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
                <?php if ($category_filter):
                    $cat_name = '';
                    foreach ($categories as $c) if ($c['id'] == $category_filter) { $cat_name = $c['name']; break; }
                    echo ' in <strong>' . htmlspecialchars($cat_name) . '</strong>';
                endif; ?>
            </p>
            <?php endif; ?>

            <!-- Products grid -->
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-products">
                        <div class="empty-icon">🛍️</div>
                        <h3>No products found</h3>
                        <p>
                            <?= ($search || $category_filter)
                                ? "Try adjusting your filters or search term."
                                : "You haven't added any products yet." ?>
                        </p>
                        <?php if (!$search && !$category_filter): ?>
                            <a href="add_product.php" class="btn-add">+ Add Your First Product</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <div class="product-card">

                        <!-- Image -->
                        <div class="prod-img-wrap">
                            <img src="../product_images/<?= htmlspecialchars($p['image']) ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 onerror="this.src='../images/no-image.png'">
                            <?php if ((int)$p['stock'] === 0): ?>
                                <span class="stock-badge badge-out">Out of Stock</span>
                            <?php elseif ((int)$p['stock'] < 10): ?>
                                <span class="stock-badge badge-low">Low: <?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="prod-info">
                            <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="prod-category"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></div>
                            <div class="prod-meta">
                                <span class="prod-price">₱<?= number_format($p['price'], 2) ?></span>
                                <span class="prod-stock <?= (int)$p['stock'] === 0 ? 'out' : ((int)$p['stock'] < 10 ? 'low' : '') ?>">
                                    Stock: <?= (int)$p['stock'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="prod-actions">
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn-edit">✏ Edit</a>
                            <form method="post"
                                  onsubmit="return confirm('Delete \'<?= addslashes(htmlspecialchars($p['name'])) ?>\'? This cannot be undone.');">
                                <!-- Pass current filter state so redirect preserves it -->
                                <input type="hidden" name="product_id"      value="<?= $p['id'] ?>">
                                <input type="hidden" name="category_filter" value="<?= htmlspecialchars($category_filter) ?>">
                                <input type="hidden" name="search_val"      value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" name="delete_product" class="btn-delete">🗑 Delete</button>
                            </form>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /page-content -->

        <!-- Footer -->
        <footer class="seller-footer">
            <div class="footer-inner">
                <p class="footer-copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
                <div class="footer-socials">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Twitter">t</a>
                    <a href="#" title="Website">🌐</a>
                    <a href="#" title="LinkedIn">in</a>
                </div>
            </div>
        </footer>

    </div><!-- /main-content -->
</div><!-- /seller-wrapper -->

</body>
</html>
