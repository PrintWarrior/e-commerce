<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

// Get user profile pic
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_pic = $stmt->fetchColumn();

// ── Handle actions (PRG) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);

    if (isset($_POST['cancel_order'])) {
        // Only allow cancel if pending
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
        $stmt->execute([$order_id, $customer_id]);
        if ($stmt->rowCount() > 0) {
            logSystemEvent(
                'customer_order_cancelled',
                'orders',
                $order_id,
                "Customer {$_SESSION['username']} cancelled order #{$order_id}."
            );
        }
        $_SESSION['flash_success'] = "Order #$order_id has been cancelled.";
    }

    if (isset($_POST['order_received'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
        $_SESSION['flash_success'] = "Order #$order_id marked as received. Thank you!";
    }

    if (isset($_POST['remove_from_view'])) {
        $stmt = $pdo->prepare("UPDATE orders SET hidden_from_customer = 1 WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
    }

    $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'search' => $_GET['search'] ?? '', 'page' => $_GET['page'] ?? '']));
    header('Location: orders.php' . ($qs ? "?$qs" : '')); exit;
}

$flash_success = $_SESSION['flash_success'] ?? ''; unset($_SESSION['flash_success']);

// ── Filters ───────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? 'all';
$search        = $_GET['search'] ?? '';
$orders_per_page = 10;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $orders_per_page;

// ── Query ─────────────────────────────────────────────────────
$query_base  = " FROM orders o
           LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
           JOIN order_items oi ON o.id = oi.order_id
           JOIN products p ON oi.product_id = p.id
           WHERE o.customer_id = ? AND (o.hidden_from_customer IS NULL OR o.hidden_from_customer = 0)";
$params = [$customer_id];

if ($status_filter !== 'all') { $query_base .= " AND o.status = ?"; $params[] = $status_filter; }
if ($search) {
    $query_base   .= " AND (o.id LIKE ? OR p.name LIKE ?)";
    $sp       = "%$search%";
    $params   = array_merge($params, [$sp, $sp]);
}

$count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id)" . $query_base);
$count_stmt->execute($params);
$filtered_total = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($filtered_total / $orders_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $orders_per_page;
}

$query  = "SELECT o.*, pm.name AS payment_method_name, COUNT(oi.id) AS item_count,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS product_names
           " . $query_base . "
           GROUP BY o.id ORDER BY o.created_at DESC
           LIMIT ? OFFSET ?";
$stmt   = $pdo->prepare($query);
$order_params = [...$params, $orders_per_page, $offset];
$param_index = 1;
foreach ($order_params as $value) {
    $stmt->bindValue($param_index++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $s = $pdo->prepare("SELECT oi.*, p.name, p.image, s.business_name AS seller_name
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        LEFT JOIN sellers s ON p.seller_id = s.id
                        WHERE oi.order_id = ?");
    $s->execute([$order['id']]);
    $order['items'] = $s->fetchAll();
}

// Status counts for filter tabs
$counts = [];
foreach (['pending','processing','shipped','delivered','completed','cancelled'] as $st) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o WHERE o.customer_id = ? AND o.status = ? AND (o.hidden_from_customer IS NULL OR o.hidden_from_customer=0)");
    $s->execute([$customer_id, $st]);
    $counts[$st] = (int)$s->fetchColumn();
}
$counts['all'] = array_sum($counts);
$pagination_params = array_filter([
    'status' => $status_filter !== 'all' ? $status_filter : '',
    'search' => $search,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_order.css">
    <link rel="stylesheet" href="../css/responsive.css">
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
                        <?php if (!empty($user_pic) && file_exists("../uploads/profile_images/$user_pic")): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($user_pic) ?>" alt="">
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
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><polyline points="9 21 9 12 15 12 15 21"/></svg>
                </a>
                <a href="orders.php" title="Orders" class="active">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                </a>
                <a href="wishlist.php" title="Wishlist">
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

            <!-- Title -->
            <div class="page-title-row">
                <h1>My Orders</h1>
                <a href="dashboard.php" class="btn-back">‹ Dashboard</a>
            </div>

            <?php if ($flash_success): ?>
                <div class="alert-success">✓ <?= htmlspecialchars($flash_success) ?></div>
            <?php endif; ?>

            <!-- Filter bar -->
            <div class="filters-bar">
                <div class="status-filters">
                    <?php
                    $tabs = ['all'=>'All Orders','pending'=>'Pending','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'];
                    foreach ($tabs as $val => $label):
                        $cnt = $counts[$val] ?? 0;
                    ?>
                    <a href="?status=<?= $val ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                       class="filter-btn <?= $status_filter === $val ? 'active' : '' ?>">
                        <?= $label ?>
                        <?php if ($cnt > 0): ?><span class="cnt"><?= $cnt ?></span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <form method="get" class="search-form">
                    <?php if ($status_filter !== 'all'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search"
                           placeholder="Search by order # or product…"
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Orders list -->
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <div class="empty-icon">📦</div>
                    <h3>No orders found</h3>
                    <p>
                        <?= $search
                            ? "No results for \"" . htmlspecialchars($search) . "\"."
                            : ($status_filter !== 'all'
                                ? "You have no " . ucfirst($status_filter) . " orders."
                                : "You haven't placed any orders yet.") ?>
                    </p>
                    <a href="dashboard.php" class="btn-shop">Continue Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">

                    <!-- Header -->
                    <div class="order-card-header">
                        <h3>Order Number <?= $order['id'] ?></h3>
                        <div class="order-meta-line">
                            <span class="label">Status:</span>
                            <span class="status-text status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="order-meta-line">
                            <span class="label">Payment:</span>
                            <span>
                                <?= htmlspecialchars(strtoupper($order['payment_method_name'] ?? 'COD')) ?> |
                                <?= htmlspecialchars(getOrderPaymentStatus($order)) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Items table -->
                    <div class="items-table-wrap">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image']) && file_exists("../product_images/".$item['image'])): ?>
                                            <img class="item-thumb"
                                                 src="../product_images/<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 onerror="this.style.opacity='.3'">
                                        <?php else: ?>
                                            <div class="item-thumb-placeholder">🛍️</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="item-name-cell"><?= htmlspecialchars($item['name']) ?></div>
                                        <?php if (!empty($item['seller_name'])): ?>
                                            <div class="item-seller-cell">Sold by <?= htmlspecialchars($item['seller_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>₱<?= number_format($item['price'], 2) ?></td>
                                    <td>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- Total row -->
                                <tr class="total-row">
                                    <td colspan="3"></td>
                                    <td style="text-align:right; font-weight:800; color:var(--text-dark);">Total:</td>
                                    <td class="total-val">₱<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="order-card-footer">
                        <?php if ($order['status'] === 'pending'): ?>
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="page" value="<?= $current_page ?>">
                                <button type="submit" name="cancel_order" class="btn-cancel"
                                        onclick="return confirm('Cancel order #<?= $order['id'] ?>?')">
                                    Cancel Order
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="page" value="<?= $current_page ?>">
                                <button type="submit" name="order_received" class="btn-received">
                                    Order Received
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="page" value="<?= $current_page ?>">
                            <button type="submit" name="remove_from_view" class="btn-ghost"
                                    onclick="return confirm('Remove this order from your view?')">
                                Remove from View
                            </button>
                        </form>

                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-ghost">
                            View Details
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page - 1])) ?>">‹</a>
                        <?php else: ?>
                            <span class="page-link disabled">‹</span>
                        <?php endif; ?>

                        <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                            <a class="page-link <?= $page === $current_page ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $page])) ?>"><?= $page ?></a>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page + 1])) ?>">›</a>
                        <?php else: ?>
                            <span class="page-link disabled">›</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
