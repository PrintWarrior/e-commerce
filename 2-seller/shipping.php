<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];

$status_labels = [
    'pending' => '⏳ Pending',
    'processing' => '⚙️ Processing',
    'shipped' => '🚚 Shipped',
    'delivered' => '✅ Delivered',
    'completed' => '✔ Completed',
    'cancelled' => '❌ Cancelled',
];


// Handle shipping update (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shipping'])) {
    $order_id = (int) $_POST['order_id'];
    $tracking_number = trim($_POST['tracking_number']);
    $shipping_status = $_POST['shipping_status'];

    $stmt = $pdo->prepare("
        UPDATE orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        SET o.tracking_number = ?, o.status = ?
        WHERE o.id = ? AND p.seller_id = ?
    ");
    if ($stmt->execute([$tracking_number, $shipping_status, $order_id, $seller_id])) {
        $_SESSION['flash_success'] = "Shipping info for Order #$order_id updated successfully!";
    } else {
        $_SESSION['flash_error'] = "Failed to update shipping information.";
    }
    header('Location: shipping.php');
    exit;
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

// Fetch active shipping orders
$stmt = $pdo->prepare("
    SELECT DISTINCT o.*, u.firstname, u.lastname, u.email,
           c.default_shipping_address
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN customers c ON o.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE p.seller_id = ?
      AND o.status IN ('pending','processing','shipped','delivered')
    ORDER BY o.created_at DESC
");
$stmt->execute([$seller_id]);
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $order['shipping_address'] = json_decode($order['default_shipping_address'] ?? '{}', true);
    $s = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND p.seller_id = ?
    ");
    $s->execute([$order['id'], $seller_id]);
    $order['items'] = $s->fetchAll();
}

// Summary counts
$counts = [];
foreach (['pending', 'processing', 'shipped'] as $st) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id=oi.order_id JOIN products p ON oi.product_id=p.id WHERE p.seller_id=? AND o.status=?");
    $s->execute([$seller_id, $st]);
    $counts[$st] = (int) $s->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping | Beauty Mart Seller</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_shipping.css">
</head>

<body>

    <div class="seller-wrapper">

        <?php /* Sidebar injected by seller_header.php */ ?>

        <div class="main-content">

            <!-- Top bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <h1>Shipping Management</h1>
                    <p>Manage order dispatch, tracking numbers, and delivery status</p>
                </div>
                <div class="topbar-right">
                    <span class="topbar-date"><?= date('F j, Y') ?></span>
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

                <!-- Mini stat strip -->
                <div class="mini-stats">
                    <div class="mini-stat">
                        <div class="ms-icon pending">⏳</div>
                        <div>
                            <div class="ms-val"><?= $counts['pending'] ?></div>
                            <div class="ms-label">Pending</div>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <div class="ms-icon processing">⚙️</div>
                        <div>
                            <div class="ms-val"><?= $counts['processing'] ?></div>
                            <div class="ms-label">Processing</div>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <div class="ms-icon shipped">🚚</div>
                        <div>
                            <div class="ms-val"><?= $counts['shipped'] ?></div>
                            <div class="ms-label">Shipped</div>
                        </div>
                    </div>
                </div>

                <!-- Orders list -->
                <?php if (empty($orders)): ?>
                    <div class="empty-shipping">
                        <div class="ei">🚚</div>
                        <h3>All caught up!</h3>
                        <p>There are no pending or in-transit orders to manage right now.</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order):
                            $addr = $order['shipping_address'] ?? [];
                            ?>
                            <div class="ship-card">

                                <!-- Header -->
                                <div class="ship-card-header">
                                    <div class="ship-order-meta">
                                        <h3>Order #<?= $order['id'] ?></h3>
                                        <p>Placed <?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></p>
                                        <p>Customer: <span
                                                class="customer-name"><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></span>
                                        </p>
                                        <p><?= htmlspecialchars($order['email']) ?></p>
                                    </div>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>

                                <!-- Body: address + items -->
                                <div class="ship-card-body">

                                    <!-- Shipping address -->
                                    <div class="ship-section">
                                        <div class="ship-section-title">📍 Shipping Address</div>
                                        <?php if (!empty($addr)): ?>
                                            <div class="address-block">
                                                <?php if (!empty($addr['address_details'])): ?>
                                                    <?= htmlspecialchars($addr['address_details']) ?><br>
                                                <?php endif; ?>
                                                <?php
                                                $parts = array_filter([
                                                    $addr['barangay'] ?? '',
                                                    $addr['municipality'] ?? '',
                                                ]);
                                                if ($parts)
                                                    echo htmlspecialchars(implode(', ', $parts)) . '<br>';

                                                $parts2 = array_filter([
                                                    $addr['province'] ?? '',
                                                    $addr['zip_code'] ?? '',
                                                ]);
                                                if ($parts2)
                                                    echo htmlspecialchars(implode(', ', $parts2));
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="address-block">
                                                <span class="no-address">No shipping address on file</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($order['tracking_number'])): ?>
                                            <div
                                                style="margin-top:12px; padding: 9px 12px; background: var(--pink-pale); border: 1.5px solid var(--pink-mid); border-radius: var(--radius-sm); font-size: 13px;">
                                                <span style="font-weight:800; color:var(--text-dark);">Tracking #</span>
                                                <span
                                                    style="color:var(--pink-accent); font-weight:700; margin-left:6px;"><?= htmlspecialchars($order['tracking_number']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Items -->
                                    <div class="ship-section">
                                        <div class="ship-section-title">📦 Order Items</div>
                                        <div class="items-list">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="item-row">
                                                    <?php if (!empty($item['image']) && file_exists("../../product_images/" . $item['image'])): ?>
                                                        <img class="item-thumb"
                                                            src="../../product_images/<?= htmlspecialchars($item['image']) ?>"
                                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                                            onerror="this.style.opacity='.3'">
                                                    <?php else: ?>
                                                        <div class="item-thumb-placeholder">🛍️</div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                                        <div class="item-qty">Qty: <?= $item['quantity'] ?> ·
                                                            ₱<?= number_format($item['price'], 2) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                </div>

                                <!-- Shipping update form -->
                                <div class="ship-card-form">
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="tracking_<?= $order['id'] ?>">Tracking Number</label>
                                                <div class="tracking-wrap">
                                                    <span class="tracking-icon">🔍</span>
                                                    <input type="text" id="tracking_<?= $order['id'] ?>" name="tracking_number"
                                                        value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>"
                                                        placeholder="Enter tracking number">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Shipping Status</label>
                                                <h4>
                                                    <?= $status_labels[$order['status']] ?? ucfirst($order['status']) ?>
                                                </h4>
                                            </div>
                                        </div>
                                        <button type="submit" name="update_shipping" class="btn-update">
                                            Update Shipping
                                        </button>
                                    </form>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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