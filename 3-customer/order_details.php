<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$order_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) redirect('dashboard.php');

// Fetch order
$stmt = $pdo->prepare("
    SELECT o.*, c.default_shipping_address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();
if (!$order) redirect('orders.php');

$shipping_address = json_decode($order['default_shipping_address'] ?? '{}', true);

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, s.business_name AS seller_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Fetch user profile pic
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();

// Timeline definition
$timeline = [
    ['key' => 'pending',    'label' => 'Order Placed',  'icon' => '🛒'],
    ['key' => 'processing', 'label' => 'Processing',    'icon' => '⚙️'],
    ['key' => 'shipped',    'label' => 'Shipped',       'icon' => '🚚'],
    ['key' => 'delivered',  'label' => 'Delivered',     'icon' => '📦'],
];

$status_keys   = array_column($timeline, 'key');
$current_index = array_search($order['status'], $status_keys);
if ($current_index === false) $current_index = -1; // cancelled / other
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> Details | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_details.css">
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
                        <?php if (!empty($u['profile_pic']) && file_exists("../uploads/profile_images/" . $u['profile_pic'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($u['profile_pic']) ?>" alt="">
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

            <!-- Title row -->
            <div class="page-title-row">
                <h1>Order Details</h1>
                <a href="orders.php" class="btn-back">‹ Back to Orders</a>
            </div>

            <!-- Order hero card -->
            <div class="order-hero">
                <div class="order-hero-left">
                    <h2>Order #<?= $order['id'] ?></h2>
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Order Date</span>
                            <span class="meta-val"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Time</span>
                            <span class="meta-val"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Payment</span>
                            <span class="meta-val"><?= strtoupper($order['payment_method'] ?? 'COD') ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Status</span>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Items</span>
                            <span class="meta-val"><?= count($order_items) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Total</span>
                            <span class="meta-val" style="color:var(--pink-accent);">₱<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($order['tracking_number'])): ?>
                        <div class="tracking-pill">
                            🔍 Tracking: <span><?= htmlspecialchars($order['tracking_number']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="timeline-card">
                <h3>📍 Order Timeline</h3>
                <?php if ($order['status'] === 'cancelled'): ?>
                    <div style="background:#fdecea;border:1.5px solid #f5b8be;border-radius:var(--radius-sm);padding:12px 16px;font-size:13.5px;color:#c0303a;font-weight:700;">
                        ✕ This order was cancelled.
                    </div>
                <?php else: ?>
                    <div class="timeline-steps">
                        <?php foreach ($timeline as $i => $step): ?>
                            <?php
                            $cls = '';
                            if ($i < $current_index)  $cls = 'completed';
                            elseif ($i === $current_index) $cls = 'active';
                            ?>
                            <div class="timeline-step <?= $cls ?>">
                                <div class="step-circle"><?= $step['icon'] ?></div>
                                <div class="step-label"><?= $step['label'] ?></div>
                                <?php if ($i === $current_index): ?>
                                    <div class="step-date"><?= date('M j', strtotime($order['created_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shipping address + Payment info -->
            <div class="info-grid">

                <!-- Shipping address -->
                <div class="info-card">
                    <div class="info-card-header">📍 Shipping Address</div>
                    <div class="info-card-body">
                        <?php if (!empty($shipping_address)): ?>
                            <?php if (!empty($shipping_address['address_details'])): ?>
                                <div class="info-row">
                                    <span class="il">Address:</span>
                                    <span class="iv"><?= htmlspecialchars($shipping_address['address_details']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($shipping_address['barangay'])): ?>
                                <div class="info-row">
                                    <span class="il">Barangay:</span>
                                    <span class="iv"><?= htmlspecialchars($shipping_address['barangay']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($shipping_address['municipality'])): ?>
                                <div class="info-row">
                                    <span class="il">Municipality:</span>
                                    <span class="iv"><?= htmlspecialchars($shipping_address['municipality']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($shipping_address['province'])): ?>
                                <div class="info-row">
                                    <span class="il">Province:</span>
                                    <span class="iv"><?= htmlspecialchars($shipping_address['province']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($shipping_address['zip_code'])): ?>
                                <div class="info-row">
                                    <span class="il">Zip Code:</span>
                                    <span class="iv"><?= htmlspecialchars($shipping_address['zip_code']) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="no-data">No shipping address on file.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment & order info -->
                <div class="info-card">
                    <div class="info-card-header">💳 Payment & Order Info</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="il">Method:</span>
                            <span class="iv"><?= strtoupper($order['payment_method'] ?? 'COD') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="il">Payment Status:</span>
                            <span class="iv"><?= ucfirst($order['payment_status'] ?? 'Pending') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="il">Order Status:</span>
                            <span class="iv">
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </span>
                        </div>
                        <?php if (!empty($order['tracking_number'])): ?>
                        <div class="info-row">
                            <span class="il">Tracking #:</span>
                            <span class="iv" style="color:var(--pink-accent);font-weight:800;">
                                <?= htmlspecialchars($order['tracking_number']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="il">Placed On:</span>
                            <span class="iv"><?= date('M j, Y · g:i A', strtotime($order['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Items table -->
            <div class="items-card">
                <div class="items-card-header">🛍️ Order Items (<?= count($order_items) ?>)</div>
                <div style="overflow-x:auto;">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="prod-cell">
                                        <?php if (!empty($item['image']) && file_exists("../product_images/" . $item['image'])): ?>
                                            <img class="prod-thumb"
                                                 src="../product_images/<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 onerror="this.style.opacity='.3'">
                                        <?php else: ?>
                                            <div class="prod-thumb-placeholder">🛍️</div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="prod-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <?php if (!empty($item['seller_name'])): ?>
                                                <div class="prod-seller">by <?= htmlspecialchars($item['seller_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>₱<?= number_format($item['price'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="subtotal-cell">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order summary -->
            <div class="summary-card">
                <div class="summary-card-header">Order Summary</div>
                <div class="summary-body">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping Fee</span>
                        <span style="color:#1a7f4b;font-weight:800;">Free</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="total-val">₱<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>

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