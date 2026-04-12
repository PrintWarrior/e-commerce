<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];
$success = '';
$error   = '';

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

    if (!in_array($new_status, $allowed_statuses, true)) {
        $error = "Invalid order status.";
    } else {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE orders o
                JOIN order_items oi ON oi.order_id = o.id
                JOIN products p ON p.id = oi.product_id
                SET o.status = ?
                WHERE o.id = ? AND p.seller_id = ?
            ");
            $stmt->execute([$new_status, $order_id, $seller_id]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Order not found or not assigned to this seller.');
            }

            if ($new_status === 'completed') {
                $getOrder = $pdo->prepare("
                    SELECT p.seller_id, SUM(oi.quantity * oi.price) AS seller_total
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.id = ? AND p.seller_id = ?
                    GROUP BY p.seller_id
                ");
                $getOrder->execute([$order_id, $seller_id]);
                $orderData = $getOrder->fetch();

                if ($orderData) {
                    $check = $pdo->prepare("SELECT COUNT(*) FROM seller_earnings WHERE order_id = ?");
                    $check->execute([$order_id]);

                    if ((int)$check->fetchColumn() === 0) {
                        $insert = $pdo->prepare("
                            INSERT INTO seller_earnings (seller_id, order_id, amount, status)
                            VALUES (?, ?, ?, 'paid')
                        ");
                        $insert->execute([$orderData['seller_id'], $order_id, $orderData['seller_total']]);
                    }
                }
            }

            $pdo->commit();
            $success = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update order status.";
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$search        = $_GET['search'] ?? '';

$query  = "SELECT DISTINCT o.*, u.firstname, u.lastname, u.email FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id JOIN customers c ON o.customer_id = c.id JOIN users u ON c.user_id = u.id WHERE p.seller_id = ?";
$params = [$seller_id];

if ($status_filter !== 'all') { $query .= " AND o.status = ?"; $params[] = $status_filter; }
if ($search) {
    $query  .= " AND (o.id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ?)";
    $sp      = "%$search%";
    $params  = array_merge($params, [$sp, $sp, $sp]);
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $s = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.seller_id = ?");
    $s->execute([$order['id'], $seller_id]);
    $order['items'] = $s->fetchAll();
}

// Count per status for tab badges
$counts = ['all' => count($orders)];
foreach (['pending','completed','cancelled','processing','shipped'] as $st) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id=oi.order_id JOIN products p ON oi.product_id=p.id WHERE p.seller_id=? AND o.status=?");
    $s->execute([$seller_id, $st]);
    $counts[$st] = (int)$s->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_orders.css">
</head>
<body>

<div class="seller-wrapper">

    <?php /* sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Orders Management</h1>
                <p>Manage and track all your customer orders</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">

            <?php if ($success): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">  <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Filters bar -->
            <div class="filters-bar">
                <div class="status-filters">
                    <?php
                    $tabs = [
                        'all'        => 'All',
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ];
                    foreach ($tabs as $val => $label):
                        $cnt = $counts[$val] ?? 0;
                    ?>
                    <a href="?status=<?= $val ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="filter-btn <?= $status_filter === $val ? 'active' : '' ?>">
                        <?= $label ?>
                        <?php if ($cnt > 0): ?>
                            <span class="count"><?= $cnt ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <form method="get" class="search-form">
                    <?php if ($status_filter !== 'all'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <?php endif; ?>
                    <input type="text" name="search"
                           placeholder="Search by order # or customer"
                           value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Orders list -->
            <div class="orders-list">
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <div class="empty-icon"> </div>
                        <h3>No orders found</h3>
                        <p>
                            <?= $search
                                ? "No results for \"" . htmlspecialchars($search) . "\". Try a different search."
                                : ($status_filter !== 'all'
                                    ? "No " . ucfirst($status_filter) . " orders at the moment."
                                    : "You haven't received any orders yet.") ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">

                        <!-- Header -->
                        <div class="order-header">
                            <div class="order-meta">
                                <h3>Order #<?= $order['id'] ?></h3>
                                <p>Placed on <?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></p>
                                <p>Customer: <span class="customer-name"><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></span></p>
                                <p><?= htmlspecialchars($order['email']) ?></p>
                            </div>
                            <div class="order-header-right">
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                                <span class="order-total-chip">
                                    <?= number_format($order['total_amount'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Items table -->
                        <div class="order-items">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format($item['price'], 2) ?></td>
                                        <td><?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="total-label">Order Total</td>
                                        <td class="total-amount"><?= number_format($order['total_amount'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Actions -->
                        <div class="order-actions">
                            <?php if (!in_array($order['status'], ['cancelled', 'completed'])): ?>
                                <form method="post" class="status-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" required>
                                        <option value="">Update status</option>
                                        <option value="pending"    <?= $order['status'] === 'pending'    ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped"    <?= $order['status'] === 'shipped'    ? 'selected' : '' ?>>Shipped</option>
                                        <option value="completed"  <?= $order['status'] === 'completed'  ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled"  <?= $order['status'] === 'cancelled'  ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">Update</button>
                                </form>
                            <?php else: ?>
                                <span class="action-note">
                                    This order is <?= $order['status'] === 'completed' ? '“ completed' : ' cancelled' ?> and cannot be modified.
                                </span>
                            <?php endif; ?>
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
                    <a href="#" title="Website"> </a>
                    <a href="#" title="LinkedIn">in</a>
                </div>
            </div>
        </footer>

    </div><!-- /main-content -->
</div><!-- /seller-wrapper -->

</body>
</html>
