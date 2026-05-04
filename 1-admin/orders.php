<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdminOrSuperadmin()) redirect('../login.php');

$stmt = $pdo->prepare("
    SELECT u.*, a.id AS admin_id, sa.id AS superadmin_id
    FROM users u
    LEFT JOIN admins a ON u.id = a.user_id
    LEFT JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'pending'));
    $tracking = trim((string)($_POST['tracking_number'] ?? ''));
    $hidden = isset($_POST['hidden_from_customer']) ? 1 : 0;
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

    if ($orderId > 0 && in_array($status, $allowedStatuses, true)) {
        $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ?, hidden_from_customer = ? WHERE id = ?")
            ->execute([$status, $tracking ?: null, $hidden, $orderId]);
        $flash = ['type' => 'success', 'text' => 'Order updated successfully.'];
    } else {
        $flash = ['type' => 'error', 'text' => 'Invalid order update.'];
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$sql = "
    SELECT o.*, u.firstname, u.lastname, u.username, u.email,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN customers c ON c.id = o.customer_id
    JOIN users u ON u.id = c.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE 1 = 1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.id LIKE ?) ";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($statusFilter !== '') {
    $sql .= " AND o.status = ? ";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int)$stmt->fetchColumn();

$total_orders = count($orders);
$completed_orders = 0;
$active_orders = 0;
$total_value = 0;
foreach ($orders as $order) {
    $total_value += (float)$order['total_amount'];
    if (($order['status'] ?? '') === 'completed') $completed_orders++;
    if (in_array($order['status'] ?? '', ['pending', 'processing', 'shipped'], true)) $active_orders++;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Beauty Mart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../css/admin_orders.css">
     <link rel="stylesheet" href="../css/admin_dash.css">
     <link rel="stylesheet" href="../css/responsive.css">
</head>
 <body>
 <div class="shell">
     <!-- Mobile Hamburger Toggle -->
     <input type="checkbox" id="admin-menu-toggle" class="admin-menu-toggle">
     <label for="admin-menu-toggle" class="admin-hamburger">
         <span></span><span></span><span></span>
     </label>
 
     <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='*'">
            </div>
            <div>
                <div class="brand-text"><span>Beauty</span><span>Mart</span></div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>

        <div class="admin-chip">
            <div class="chip-av">
                <?php if (!empty($admin['profile_pic']) && file_exists("../uploads/profile_images/" . $admin['profile_pic'])): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($admin['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($admin['firstname'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']) ?></div>
                <span class="chip-role">Administrator</span>
            </div>
        </div>

         <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php" class="<?= $current_page==='dashboard.php' ? 'active':'' ?>">
                <span class="ni">📊</span> Dashboard
            </a>
            <a href="create_users.php" class="<?= $current_page==='create_users.php' ? 'active':'' ?>">
                <span class="ni">➕</span> Create User
            </a>
            <a href="manage_users.php" class="<?= $current_page==='manage_users.php' ? 'active':'' ?>">
                <span class="ni">👥</span> Manage Users
            </a>
            <a href="manage_sellers.php" class="<?= $current_page==='manage_sellers.php' ? 'active':'' ?>">
                <span class="ni">🏪</span> Manage Sellers
                <?php if ($pending_apps > 0): ?><span class="nbadge"><?= $pending_apps ?></span><?php endif; ?>
            </a>
            <a href="products.php" class="<?= $current_page==='products.php' ? 'active':'' ?>">
                <span class="ni">🛍️</span> Products
            </a>
            <a href="orders.php" class="<?= $current_page==='orders.php' ? 'active':'' ?>">
                <span class="ni">📦</span> Orders
            </a>

            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page==='manage_deletions.php' ? 'active':'' ?>">
                <span class="ni">🗑️</span> Deletion Requests
            </a>
            <a href="notifications.php" class="<?= $current_page==='notifications.php' ? 'active':'' ?>">
                <span class="ni">🔔</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>
            <a href="system_logs.php" class="<?= $current_page==='system_logs.php' ? 'active':'' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>
            

            <div class="nav-lbl">Account</div>
            <a href="profile.php" class="<?= $current_page==='profile.php' ? 'active':'' ?>">
                <span class="ni">👤</span> My Profile
            </a>
            <a href="about.php" class="<?= $current_page==='about.php' ? 'active':'' ?>">
                <span class="ni">📝</span> About Menu
            </a>
            <a href="../logout.php" class="logout">
                <span class="ni">🚪</span> Logout
            </a>
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Orders</h1>
                <p>Track customer orders, update statuses, and manage visibility.</p>
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
                <div class="stat-card"><div class="stat-icon">🔍</div><div><div class="stat-val"><?= number_format($total_orders) ?></div><div class="stat-lbl">Filtered Orders</div></div></div>
                <div class="stat-card"><div class="stat-icon">✅</div><div><div class="stat-val"><?= number_format($completed_orders) ?></div><div class="stat-lbl">Completed</div></div></div>
                <div class="stat-card warn"><div class="stat-icon">🟢</div><div><div class="stat-val"><?= number_format($active_orders) ?></div><div class="stat-lbl">Active Orders</div></div></div>
                <div class="stat-card"><div class="stat-icon">💵</div><div><div class="stat-val">PHP <?= number_format($total_value, 2) ?></div><div class="stat-lbl">Filtered Value</div></div></div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>Order Filters</h2></div>
                <div class="card-body">
                    <form method="get" class="filters-grid">
                        <div class="field">
                            <label>Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Customer, email, order id">
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All statuses</option>
                                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>Order Directory</h2></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">No orders matched the current filters.</div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Items</th>
                                    <th>Current Status</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <div class="order-title">#<?= (int)$order['id'] ?></div>
                                            <div class="muted"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at']))) ?></div>
                                        </td>
                                        <td>
                                            <div class="order-title"><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></div>
                                            <div class="muted">@<?= htmlspecialchars($order['username']) ?></div>
                                            <div class="muted"><?= htmlspecialchars($order['email']) ?></div>
                                        </td>
                                        <td>PHP <?= htmlspecialchars(number_format((float)$order['total_amount'], 2)) ?></td>
                                        <td><?= (int)$order['item_count'] ?></td>
                                        <td>
                                            <div style="margin-bottom:6px;"><span class="status-badge status-<?= htmlspecialchars((string)$order['status']) ?>"><?= htmlspecialchars(ucfirst((string)$order['status'])) ?></span></div>
                                            <div class="muted">Tracking: <?= htmlspecialchars((string)($order['tracking_number'] ?: 'None')) ?></div>
                                            <div class="muted">Visible to customer: <?= $order['hidden_from_customer'] ? 'No' : 'Yes' ?></div>
                                        </td>
                                        <td>
                                            <form method="post" class="mini-form">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                <select name="status">
                                                    <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="tracking_number" value="<?= htmlspecialchars((string)$order['tracking_number']) ?>" placeholder="Tracking number">
                                                <label>
                                                    <input type="checkbox" name="hidden_from_customer" value="1" <?= $order['hidden_from_customer'] ? 'checked' : '' ?>>
                                                    Hide from customer
                                                </label>
                                                <button type="submit" name="update_order" class="btn-secondary">Save Order</button>
                                            </form>
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
                <div class="footer-copy">Copyright &copy; 2025 <span>Beauty Mart</span>. Admin panel.</div>
                <div class="footer-copy">Order management console</div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
