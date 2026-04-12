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
    <style>
        /* ── Reset & Base ─────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        :root {
          --pink-light: #fce8ee;
          --pink-mid: #f9d0dc;
          --pink-accent: #e8728e;
          --pink-dark: #c75473;
          --text-dark: #2e2e2e;
          --text-mid: #555;
          --text-muted: #888;
          --white: #ffffff;
          --radius: 14px;
          --shadow: 0 4px 18px rgba(200, 80, 110, 0.12);
          --success-bg: #e8f5e9;
          --success-border: #4caf50;
          --success-text: #2e7d32;
          --danger-bg: #ffebee;
          --danger-border: #f44336;
          --danger-text: #c62828;
        }

        html, body {
          font-family: "Nunito", sans-serif;
          background: #fdf5f7;
          color: var(--text-dark);
          min-height: 100vh;
        }

        a {
          text-decoration: none;
          color: inherit;
        }

        ul, li {
          list-style: none;
        }

        /* ── Layout Shell ────────────────────────────────────────────── */
        .shell {
          display: grid;
          grid-template-columns: 260px 1fr;
          min-height: 100vh;
          gap: 0;
        }

        /* ── Sidebar ──────────────────────────────────────────────────── */
        .sidebar {
          background: #fff;
          border-right: 1.5px solid var(--pink-mid);
          padding: 24px 18px;
          display: flex;
          flex-direction: column;
          gap: 28px;
          position: sticky;
          top: 0;
          height: 100vh;
          overflow-y: auto;
        }

        .sidebar-brand {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .brand-icon {
          width: 52px;
          height: 52px;
          background: var(--pink-light);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          line-height: 1;
          flex-shrink: 0;
        }

        .brand-icon img {
          width: 70%;
          height: 70%;
          object-fit: contain;
        }

        .brand-text {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--pink-accent);
        }

        .brand-sub {
          font-size: 12px;
          color: var(--text-muted);
          font-weight: 600;
        }

        .admin-chip {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 14px;
          background: var(--pink-light);
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
        }

        .chip-av {
          width: 48px;
          height: 48px;
          background: var(--pink-accent);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fff;
          font-weight: 700;
          font-size: 18px;
          flex-shrink: 0;
          overflow: hidden;
        }

        .chip-av img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .chip-name {
          font-weight: 800;
          font-size: 14px;
          color: var(--text-dark);
        }

        .chip-role {
          font-size: 12px;
          color: var(--text-muted);
        }

        .sidebar-nav {
          display: flex;
          flex-direction: column;
          gap: 6px;
          flex: 1;
        }

        .nav-lbl {
          font-size: 11px;
          font-weight: 800;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          padding: 0 14px;
          margin-bottom: 4px;
        }

        .sidebar-nav a {
          padding: 12px 14px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          color: var(--text-mid);
          transition: all 0.2s;
          border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover {
          background: var(--pink-light);
          color: var(--pink-accent);
          border-left-color: var(--pink-accent);
        }

        .sidebar-nav a.active {
          background: var(--pink-light);
          color: var(--pink-accent);
          border-left-color: var(--pink-accent);
        }

        /* ── Main Content ────────────────────────────────────────────── */
        .main {
          padding: 28px;
          display: flex;
          flex-direction: column;
          gap: 28px;
          overflow-y: auto;
        }

        /* ── Topbar ──────────────────────────────────────────────────── */
        .topbar {
          background: linear-gradient(135deg, #fce8ee 0%, #fdf5f7 60%, #fce8ee 100%);
          border-radius: var(--radius);
          padding: 28px;
          border: 1.5px solid var(--pink-mid);
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: 20px;
        }

        .topbar-left h1 {
          font-family: "Playfair Display", serif;
          font-size: 28px;
          font-weight: 700;
          color: var(--pink-dark);
          margin-bottom: 8px;
        }

        .topbar-left p {
          font-size: 14px;
          color: var(--text-mid);
          line-height: 1.6;
        }

        .topbar-right {
          display: flex;
          flex-direction: column;
          gap: 8px;
          min-width: 160px;
        }

        .topbar-date {
          background: #fff;
          padding: 10px 14px;
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
          font-size: 12px;
          font-weight: 700;
          color: var(--pink-accent);
          text-align: right;
        }

        /* ── Content Area ────────────────────────────────────────────– */
        .content {
          display: flex;
          flex-direction: column;
          gap: 22px;
        }

        /* ── Alert Messages ──────────────────────────────────────────– */
        .alert {
          padding: 14px 18px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          border-left: 4px solid;
        }

        .alert.alert-success {
          background: var(--success-bg);
          color: var(--success-text);
          border-left-color: var(--success-border);
        }

        .alert.alert-error {
          background: var(--danger-bg);
          color: var(--danger-text);
          border-left-color: var(--danger-border);
        }

        /* ── Stats Grid ──────────────────────────────────────────────– */
        .stats-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 16px;
        }

        @media (max-width: 1200px) {
          .stats-grid {
            grid-template-columns: repeat(2, 1fr);
          }
        }

        .stat-card {
          background: #fff;
          border-radius: var(--radius);
          padding: 18px;
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          display: flex;
          gap: 14px;
          align-items: center;
        }

        .stat-card.danger {
          background: var(--danger-bg);
          border-color: var(--danger-border);
        }

        .stat-icon {
          width: 48px;
          height: 48px;
          border-radius: 50%;
          background: var(--pink-light);
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 900;
          font-size: 20px;
          color: var(--pink-accent);
          flex-shrink: 0;
        }

        .stat-card.danger .stat-icon {
          background: var(--danger-bg);
          color: var(--danger-text);
        }

        .stat-val {
          font-size: 24px;
          font-weight: 800;
          color: var(--text-dark);
        }

        .stat-lbl {
          font-size: 12px;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          font-weight: 700;
        }

        /* ── Cards ───────────────────────────────────────────────────── */
        .dash-card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          overflow: hidden;
        }

        .card-head {
          border-bottom: 1.5px solid var(--pink-mid);
          padding: 18px 22px;
        }

        .card-head h2 {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--text-dark);
        }

        .card-body {
          padding: 22px;
        }

        /* ── Forms ───────────────────────────────────────────────────– */
        .form-grid,
        .search-grid {
          display: grid;
          gap: 14px;
        }

        .form-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .form-grid .field.full {
          grid-column: 1 / -1;
        }

        .form-grid .field:has(> .btn-primary) {
          align-self: flex-end;
        }

        .search-grid {
          grid-template-columns: 1fr auto;
          align-items: end;
        }

        .field {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        label {
          font-size: 13px;
          font-weight: 700;
          color: var(--text-dark);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        input,
        select,
        textarea {
          padding: 12px 14px;
          border: 1.5px solid var(--pink-mid);
          border-radius: 10px;
          font-family: "Nunito", sans-serif;
          font-size: 14px;
          color: var(--text-dark);
          background: #fdf5f7;
          outline: none;
          transition: border-color 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
          border-color: var(--pink-accent);
          background: #fff;
        }

        textarea {
          resize: vertical;
          min-height: 80px;
        }

        /* ── Buttons ──────────────────────────────────────────────────– */
        .btn-primary,
        .btn-secondary,
        .btn-danger {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 12px 18px;
          border-radius: 10px;
          border: none;
          cursor: pointer;
          font: inherit;
          font-weight: 700;
          transition: all 0.2s;
        }

        .btn-primary {
          background: var(--pink-accent);
          color: #fff;
        }

        .btn-primary:hover {
          background: var(--pink-dark);
        }

        .btn-secondary {
          background: var(--pink-light);
          color: var(--pink-accent);
          border: 1.5px solid var(--pink-mid);
        }

        .btn-secondary:hover {
          background: var(--pink-mid);
        }

        .btn-danger {
          background: var(--danger-bg);
          color: var(--danger-text);
          border: 1.5px solid var(--danger-border);
          font-size: 13px;
          padding: 10px 14px;
        }

        .btn-danger:hover {
          background: var(--danger-border);
          color: #fff;
        }

        /* ── Tables ──────────────────────────────────────────────────– */
        .table-wrap {
          overflow-x: auto;
        }

        .table-wrap .empty-state {
          padding: 32px;
        }

        .data-table {
          width: 100%;
          border-collapse: collapse;
          font-size: 13px;
        }

        .data-table thead th {
          font-size: 12px;
          font-weight: 700;
          color: var(--text-muted);
          text-align: left;
          padding: 14px 12px;
          border-bottom: 1.5px solid var(--pink-mid);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .data-table tbody tr {
          border-bottom: 1px solid #f0f0f0;
          transition: background 0.2s;
        }

        .data-table tbody tr:hover {
          background: var(--pink-light);
        }

        .data-table tbody td {
          padding: 14px 12px;
          color: var(--text-dark);
        }

        .thumb {
          width: 42px;
          height: 42px;
          border-radius: 8px;
          object-fit: cover;
          border: 1.5px solid var(--pink-mid);
        }

        .prod-name {
          font-weight: 700;
          margin-bottom: 4px;
        }

        .muted {
          font-size: 12px;
          color: var(--text-muted);
        }

        .actions {
          display: flex;
          gap: 8px;
          align-items: center;
        }

        .actions form {
          margin: 0;
        }

        .actions .btn-secondary,
        .actions .btn-danger {
          display: inline-flex;
          padding: 8px 12px;
          font-size: 12px;
        }

        .empty-state {
          padding: 32px;
          text-align: center;
          color: var(--text-muted);
        }

        /* ── Footer ──────────────────────────────────────────────────– */
        .admin-footer {
          background: #fff;
          border-top: 1.5px solid var(--pink-mid);
          padding: 14px 0;
          margin-top: auto;
        }

        .footer-inner {
          max-width: 1400px;
          margin: 0 auto;
          padding: 0 22px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          flex-wrap: wrap;
          gap: 12px;
        }

        .footer-copy {
          font-size: 13px;
          color: var(--text-muted);
        }

        .footer-copy span {
          color: var(--pink-accent);
          font-weight: 700;
        }

        /* ── Scrollbar ───────────────────────────────────────────────– */
        ::-webkit-scrollbar {
          width: 8px;
          height: 8px;
        }

        ::-webkit-scrollbar-track {
          background: transparent;
        }

        ::-webkit-scrollbar-thumb {
          background: var(--pink-mid);
          border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
          background: var(--pink-accent);
        }

        /* ── Responsive ──────────────────────────────────────────────– */
        @media (max-width: 768px) {
          .shell {
            grid-template-columns: 1fr;
          }

          .sidebar {
            height: auto;
            position: static;
            border-right: none;
            border-bottom: 1.5px solid var(--pink-mid);
            padding: 14px;
          }

          .main {
            padding: 14px;
          }

          .topbar {
            flex-direction: column;
          }

          .topbar-right {
            flex-direction: row;
            min-width: auto;
          }

          .topbar-date {
            text-align: center;
            flex: 1;
          }

          .stats-grid,
          .form-grid {
            grid-template-columns: 1fr;
          }

          .search-grid {
            grid-template-columns: 1fr;
          }

          .search-grid .field:last-child {
            align-self: stretch;
          }

          .search-grid button {
            width: 100%;
          }

          .actions {
            flex-direction: column;
          }

          .actions a,
          .actions button {
            width: 100%;
          }

          .footer-inner {
            flex-direction: column;
            text-align: center;
          }
        }
    </style>
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
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="create_users.php">Create Users</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="../logout.php">Logout</a>
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
                <div class="table-wrap">
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
                                                <a href="products.php?edit=<?= (int)$product['id'] ?>" class="btn-secondary">Edit</a>
                                                <form method="post" onsubmit="return confirm('Delete this product?');" style="margin:0;">
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
