<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperadmin()) {
    redirect('../login.php');
}

$stmt = $pdo->prepare("SELECT u.*, sa.id AS superadmin_id FROM users u JOIN superadmins sa ON u.id = sa.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$superadmin = $stmt->fetch();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_product'])) {
        $id = (int)($_POST['product_id'] ?? 0);
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $image = trim((string)($_POST['image'] ?? 'default.jpg'));

        if ($name === '' || $sellerId <= 0 || $price < 0 || $stock < 0) {
            $flash = ['type' => 'error', 'text' => 'Please fill in the required product fields.'];
        } else {
            if ($id > 0) {
                $pdo->prepare("UPDATE products SET seller_id = ?, category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?")
                    ->execute([$sellerId, $categoryId ?: null, $name, $description, $price, $stock, $image ?: 'default.jpg', $id]);
                logSystemEvent('superadmin_product_updated', 'products', $id, "Superadmin updated product '{$name}' for seller #{$sellerId}.");
                $flash = ['type' => 'success', 'text' => 'Product updated successfully.'];
            } else {
                $pdo->prepare("INSERT INTO products (seller_id, category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$sellerId, $categoryId ?: null, $name, $description, $price, $stock, $image ?: 'default.jpg']);
                $newProductId = (int) $pdo->lastInsertId();
                logSystemEvent('superadmin_product_created', 'products', $newProductId, "Superadmin created product '{$name}' for seller #{$sellerId}.");
                $flash = ['type' => 'success', 'text' => 'Product created successfully.'];
            }
        }
    }

    if (isset($_POST['delete_product'])) {
        $id = (int)($_POST['product_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $productToDelete = $stmt->fetch();

            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            logSystemEvent('superadmin_product_deleted', 'products', $id, "Superadmin deleted product '" . ($productToDelete['name'] ?? 'Unknown product') . "'.");
            $flash = ['type' => 'success', 'text' => 'Product deleted successfully.'];
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editProduct = [
    'id' => 0,
    'seller_id' => '',
    'category_id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'image' => 'default.jpg',
];
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch() ?: $editProduct;
}

$search = trim((string)($_GET['search'] ?? ''));

$stmt = $pdo->query("SELECT id, business_name FROM sellers ORDER BY business_name ASC");
$sellers = $stmt->fetchAll();
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$sql = "
    SELECT p.*, c.name AS category_name, s.business_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN sellers s ON s.id = p.seller_id
    WHERE 1 = 1
";
$params = [];
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR s.business_name LIKE ? OR c.name LIKE ?) ";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int)$stmt->fetchColumn();

$total_products = count($products);
$out_of_stock = 0;
$total_stock = 0;
foreach ($products as $product) {
    $total_stock += (int)$product['stock'];
    if ((int)$product['stock'] <= 0) $out_of_stock++;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Beauty Mart Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_products.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='S'">
            </div>
            <div>
                <div class="brand-text"><span>Beauty</span><span>Mart</span></div>
                <div class="brand-sub">Superadmin Panel</div>
            </div>
        </div>

        <div class="admin-chip">
            <div class="chip-av">
                <?php if (!empty($superadmin['profile_pic']) && file_exists("../uploads/profile_images/" . $superadmin['profile_pic'])): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($superadmin['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($superadmin['firstname'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($superadmin['firstname'] . ' ' . $superadmin['lastname']) ?></div>
                <span class="chip-role">Superadmin</span>
            </div>
        </div>

         <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php" class="<?= $current_page==='dashboard.php' ? 'active':'' ?>">
                <span class="ni">D</span> Dashboard
            </a>
            <a href="create_users.php" class="<?= $current_page==='create_users.php' ? 'active':'' ?>">
                <span class="ni">+</span> Create Users
            </a>
            <a href="manage_users.php" class="<?= $current_page==='manage_users.php' ? 'active':'' ?>">
                <span class="ni">U</span> Manage Users
            </a>
            <a href="products.php" class="<?= $current_page==='products.php' ? 'active':'' ?>">
                <span class="ni">P</span> Manage Products
            </a>

            <div class="nav-lbl">Management</div>
            <a href="../system_logs.php">
                <span class="ni">L</span> System Logs
            </a>
            <a href="../notifications.php">
                <span class="ni">N</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>

            <div class="nav-lbl">Account</div>
            <a href="../logout.php" class="logout">
                <span class="ni">O</span> Logout
            </a>
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Products</h1>
                <p>Create, edit, and manage catalog listings with superadmin rights.</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;"><?= $unread_count ?> unread</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['text']) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">S</div><div><div class="stat-val"><?= number_format($total_products) ?></div><div class="stat-lbl">Filtered Products</div></div></div>
                <div class="stat-card"><div class="stat-icon">C</div><div><div class="stat-val"><?= number_format(count($categories)) ?></div><div class="stat-lbl">Categories</div></div></div>
                <div class="stat-card"><div class="stat-icon">R</div><div><div class="stat-val"><?= number_format(count($sellers)) ?></div><div class="stat-lbl">Sellers</div></div></div>
                <div class="stat-card danger"><div class="stat-icon">!</div><div><div class="stat-val"><?= number_format($out_of_stock) ?></div><div class="stat-lbl">Out of Stock</div></div></div>
            </div>

            <div class="dash-card">
                <div class="card-head">
                    <h2><?= (int)$editProduct['id'] > 0 ? 'Edit Product' : 'Create Product' ?></h2>
                </div>
                <div class="card-body">
                    <form method="post" class="form-grid">
                        <input type="hidden" name="product_id" value="<?= (int)$editProduct['id'] ?>">
                        <div class="field">
                            <label>Seller</label>
                            <select name="seller_id" required>
                                <option value="">Select seller</option>
                                <?php foreach ($sellers as $seller): ?>
                                    <option value="<?= (int)$seller['id'] ?>" <?= (string)$editProduct['seller_id'] === (string)$seller['id'] ? 'selected' : '' ?>><?= htmlspecialchars($seller['business_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= (string)$editProduct['category_id'] === (string)$category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars((string)$editProduct['name']) ?>" required>
                        </div>
                        <div class="field">
                            <label>Image Filename</label>
                            <input type="text" name="image" value="<?= htmlspecialchars((string)$editProduct['image']) ?>" placeholder="example.png">
                        </div>
                        <div class="field">
                            <label>Price</label>
                            <input type="number" name="price" value="<?= htmlspecialchars((string)$editProduct['price']) ?>" step="0.01" min="0" required>
                        </div>
                        <div class="field">
                            <label>Stock</label>
                            <input type="number" name="stock" value="<?= htmlspecialchars((string)$editProduct['stock']) ?>" min="0" required>
                        </div>
                        <div class="field full">
                            <label>Description</label>
                            <textarea name="description"><?= htmlspecialchars((string)$editProduct['description']) ?></textarea>
                        </div>
                        <div class="field">
                            <label>&nbsp;</label>
                            <button type="submit" name="save_product" class="btn-primary"><?= (int)$editProduct['id'] > 0 ? 'Update Product' : 'Create Product' ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>Search Products</h2></div>
                <div class="card-body">
                    <form method="get" class="search-grid">
                        <div class="field">
                            <label>Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Product, seller, category">
                        </div>
                        <div class="field">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-secondary">Apply Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>Product Directory</h2></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">No products matched the current search.</div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><img class="thumb" src="../product_images/<?= htmlspecialchars($product['image']) ?>" alt="" onerror="this.style.display='none'"></td>
                                        <td>
                                            <div class="prod-name"><?= htmlspecialchars($product['name']) ?></div>
                                            <div class="muted"><?= htmlspecialchars(substr((string)$product['description'], 0, 90)) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($product['business_name'] ?? 'Unknown') ?></td>
                                        <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                        <td>PHP <?= htmlspecialchars(number_format((float)$product['price'], 2)) ?></td>
                                        <td><?= (int)$product['stock'] ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="products.php?edit=<?= (int)$product['id'] ?>" class="btn-secondary" style="display:inline-flex;align-items:center;">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this product?');">
                                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                                    <button type="submit" name="delete_product" class="btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <footer class="admin-footer">
            <div class="footer-inner">
                <div class="footer-copy">Copyright &copy; 2025 <span>Beauty Mart</span>. Superadmin panel.</div>
                <div class="footer-copy">Total visible stock: <?= number_format($total_stock) ?></div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
