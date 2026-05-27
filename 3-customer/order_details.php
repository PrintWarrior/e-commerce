<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$order_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) redirect('dashboard.php');
ensureOrderItemFulfillmentColumns();

// Fetch order
$stmt = $pdo->prepare("
    SELECT o.*, pm.name AS payment_method_name, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
    FROM orders o
    LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();
if (!$order) redirect('orders.php');

$shipping_address = [
    'barangay' => $order['barangay'] ?? '',
    'municipality' => $order['municipality'] ?? '',
    'province' => $order['province'] ?? '',
    'zip_code' => $order['zip_code'] ?? '',
    'address_details' => $order['address_details'] ?? '',
];

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image, p.seller_id, s.business_name AS seller_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE oi.order_id = ?
    ORDER BY p.seller_id, p.name
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$seller_groups = [];
foreach ($order_items as $item) {
    $seller_key = $item['seller_id'] ?? 'unknown';

    if (!isset($seller_groups[$seller_key])) {
        $seller_groups[$seller_key] = [
            'seller_id' => $item['seller_id'],
            'seller_name' => $item['seller_name'] ?? 'Unknown Shop',
            'status' => $item['status'] ?? $order['status'],
            'items' => [],
            'subtotal' => 0,
        ];
    }

    $seller_groups[$seller_key]['items'][] = $item;
    $seller_groups[$seller_key]['subtotal'] += $item['price'] * $item['quantity'];
}

$group_status_counts = [];
foreach ($seller_groups as $group) {
    $group_status_counts[$group['status']] = ($group_status_counts[$group['status']] ?? 0) + 1;
}
$active_group_statuses = $group_status_counts;
unset($active_group_statuses['cancelled']);

if (empty($active_group_statuses)) {
    $display_order_status = 'cancelled';
} elseif (!empty($active_group_statuses['pending'])) {
    $display_order_status = 'pending';
} elseif (!empty($active_group_statuses['processing'])) {
    $display_order_status = 'processing';
} elseif (!empty($active_group_statuses['shipped'])) {
    $display_order_status = 'shipped';
} elseif (!empty($active_group_statuses['delivered'])) {
    $display_order_status = 'delivered';
} else {
    $display_order_status = 'completed';
}
$order['status'] = $display_order_status;

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
                            <span class="meta-val"><?= htmlspecialchars(strtoupper($order['payment_method_name'] ?? 'COD')) ?></span>
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
                            <span class="iv"><?= htmlspecialchars(strtoupper($order['payment_method_name'] ?? 'COD')) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="il">Payment Status:</span>
                            <span class="iv"><?= htmlspecialchars(getOrderPaymentStatus($order)) ?></span>
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

            <!-- Items by seller -->
            <div class="items-card">
                <div class="items-card-header">🛍️ Order Items (<?= count($order_items) ?>)</div>
                <div class="seller-group-list">
                    <?php foreach ($seller_groups as $group): ?>
                    <div class="seller-order-box">
                        <div class="seller-order-header">
                            <span><?= htmlspecialchars($group['seller_name']) ?></span>
                            <span class="status-badge status-<?= htmlspecialchars($group['status']) ?>"><?= ucfirst($group['status']) ?></span>
                            <span><?= count($group['items']) ?> item<?= count($group['items']) === 1 ? '' : 's' ?></span>
                        </div>
                        <?php foreach ($group['items'] as $item): ?>
                        <div class="seller-order-item">
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
                                    <div class="prod-meta">Qty: <?= $item['quantity'] ?> | Price: &#8369;<?= number_format($item['price'], 2) ?></div>
                                </div>
                            </div>
                            <div class="subtotal-cell">&#8369;<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <div class="seller-order-total">
                            <span>Store Total</span>
                            <span>&#8369;<?= number_format($group['subtotal'], 2) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                 <div class="summary-actions">
                     <button type="button" id="view-receipt-btn" class="btn-receipt">View Receipt</button>
                 </div>
             </div>

         </div>
     </div>

     <!-- Receipt Modal Styles -->
     <style>
     .receipt-modal-overlay {
         position: fixed; inset: 0;
         background: rgba(0,0,0,.5);
         z-index: 10000;
         display: flex; align-items: flex-start; justify-content: center;
         overflow-y: auto;
         padding: 40px 16px;
     }
     .receipt-modal {
         background: #fff;
         width: 100%; max-width: 700px;
         border-radius: 14px;
         box-shadow: 0 10px 50px rgba(0,0,0,.3);
         overflow: hidden;
         margin: 40px 0;
     }
     .receipt-modal-header {
         background: linear-gradient(135deg, #fce8ee, #f5c6d4);
         padding: 18px 24px;
         display: flex; justify-content: space-between; align-items: center;
         border-bottom: 2px solid var(--pink-mid);
     }
     .receipt-modal-header h2 {
         margin: 0;
         font-family: 'Playfair Display', serif;
         font-size: 24px; font-weight: 700;
         color: var(--pink-accent);
     }
     .receipt-close {
         background: none; border: none;
         font-size: 32px; line-height: 1;
         color: var(--pink-dark);
         cursor: pointer; width: 36px; height: 36px;
         display: flex; align-items: center; justify-content: center;
         border-radius: 50%; transition: background .15s;
     }
     .receipt-close:hover { background: rgba(200,80,110,.1); }

     .receipt-modal-body {
         padding: 28px 24px;
     }

     .receipt-store {
         text-align: center;
         margin-bottom: 28px;
         padding-bottom: 20px;
         border-bottom: 2px dashed var(--pink-mid);
     }
     .receipt-logo {
         width: 56px; height: 56px;
         border-radius: 50%;
         margin-bottom: 8px;
         object-fit: cover;
         border: 2px solid var(--pink-mid);
     }
     .receipt-store-name {
         font-family: 'Playfair Display', serif;
         font-size: 18px; font-weight: 700;
         color: var(--pink-accent);
     }
     .receipt-order-id {
         font-size: 13px; color: var(--text-muted);
         margin-top: 2px;
     }
     .receipt-date {
         font-size: 12.5px; color: var(--text-muted);
     }

     .receipt-section {
         margin-bottom: 24px;
     }
     .receipt-section h3 {
         font-size: 14px; font-weight: 800;
         color: var(--text-dark);
         letter-spacing: .5px;
         text-transform: uppercase;
         margin-bottom: 12px;
         padding-bottom: 6px;
         border-bottom: 1.5px solid var(--pink-mid);
     }

     .receipt-address-block {
         font-size: 13.5px;
         line-height: 1.6;
         color: var(--text-mid);
     }

     .receipt-info-grid {
         display: grid; grid-template-columns: 1fr 1fr;
         gap: 10px;
     }
     .receipt-info-row {
         display: flex; justify-content: space-between;
         font-size: 13.5px; color: var(--text-mid);
         padding: 4px 0;
     }

     .receipt-items-table {
         width: 100%;
         border-collapse: collapse;
         font-size: 13.5px;
     }
     .receipt-items-table th {
         text-align: left;
         font-size: 11px;
         font-weight: 800;
         color: var(--text-muted);
         text-transform: uppercase;
         letter-spacing: .4px;
         padding: 8px 10px 8px 0;
         border-bottom: 1.5px solid var(--pink-mid);
     }
     .receipt-items-table td {
         padding: 10px 10px 10px 0;
         border-bottom: 1px solid #f5eef0;
         vertical-align: top;
     }
     .receipt-items-table tbody tr:last-child td { border-bottom: none; }
     .receipt-item-name { font-weight: 700; color: var(--text-dark); }
     .receipt-item-seller {
         font-size: 11.5px; color: var(--text-muted);
         font-weight: 600;
     }

     .receipt-seller-groups {
         display: flex;
         flex-direction: column;
         gap: 14px;
     }
     .receipt-seller-box {
         border: 1.5px solid var(--pink-mid);
         border-radius: 10px;
         overflow: hidden;
     }
     .receipt-seller-header,
     .receipt-seller-total {
         display: flex;
         justify-content: space-between;
         gap: 12px;
         font-size: 13px;
         font-weight: 800;
     }
     .receipt-seller-header {
         background: var(--pink-pale);
         border-bottom: 1.5px solid var(--pink-mid);
         color: var(--text-dark);
         padding: 10px 12px;
     }
     .receipt-seller-header span:last-child {
         color: var(--text-muted);
         white-space: nowrap;
     }
     .receipt-seller-box .receipt-items-table {
         margin: 0 12px;
         width: calc(100% - 24px);
     }
     .receipt-seller-total {
         background: var(--pink-soft);
         color: var(--text-dark);
         padding: 10px 12px;
     }
     .receipt-seller-total span:last-child { color: var(--pink-accent); }

     .receipt-summary-section {
         background: var(--pink-pale);
         border-radius: 10px;
         padding: 16px;
         margin-top: 24px;
     }
     .receipt-summary-row {
         display: flex; justify-content: space-between;
         font-size: 14px; color: var(--text-mid);
         padding: 4px 0;
     }
     .receipt-summary-row.total {
         font-size: 16px; font-weight: 800;
         color: var(--text-dark);
         margin-top: 6px;
         padding-top: 8px;
         border-top: 1.5px solid var(--pink-mid);
     }
     .receipt-free { color: #1a7f4b; font-weight: 800; }

     .receipt-footer {
         text-align: center;
         margin-top: 20px;
         font-size: 13px;
         color: var(--text-muted);
         font-style: italic;
     }

     .receipt-modal-footer {
         padding: 16px 24px;
         background: var(--pink-soft);
         border-top: 1.5px solid var(--pink-mid);
         display: flex; justify-content: flex-end; gap: 12px;
     }
     .btn-print-receipt {
         height: 40px; padding: 0 22px;
         background: var(--pink-accent); color: #fff;
         border: none; border-radius: 20px;
         font-size: 14px; font-weight: 800;
         font-family: 'Nunito', sans-serif;
         cursor: pointer;
         transition: background .2s, transform .1s;
     }
     .btn-print-receipt:hover { background: var(--pink-dark); }
     .btn-close-modal {
         height: 40px; padding: 0 22px;
         background: #fff; color: var(--text-mid);
         border: 1.5px solid var(--pink-mid);
         border-radius: 20px;
         font-size: 14px; font-weight: 800;
         font-family: 'Nunito', sans-serif;
         cursor: pointer;
         transition: background .2s, color .2s;
     }
     .btn-close-modal:hover {
         background: var(--pink-pale);
         color: var(--pink-dark);
     }

     .receipt-no-data { font-size: 13.5px; color: var(--text-muted); font-style: italic; }

     @media (max-width: 480px) {
         .receipt-modal { margin: 0; border-radius: 0; max-height: 100%; }
         .receipt-modal-header { padding: 14px 18px; }
         .receipt-modal-body { padding: 18px; }
         .receipt-info-grid { grid-template-columns: 1fr; }
         .receipt-modal-footer { padding: 12px 18px; }
         .btn-print-receipt, .btn-close-modal { flex: 1; }
     }
     </style>

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

     <!-- Receipt Modal -->
     <div id="receipt-modal" class="receipt-modal-overlay" style="display:none;">
         <div class="receipt-modal">
             <div class="receipt-modal-header">
                 <h2>Order Receipt</h2>
                 <button type="button" class="receipt-close" id="receipt-close">&times;</button>
             </div>
             <div class="receipt-modal-body">
                 <!-- Logo & Store Info -->
                 <div class="receipt-store">
                     <img src="../images/logo.png" alt="Beauty Mart" class="receipt-logo"
                          onerror="this.style.display='none'">
                     <div class="receipt-store-name">Beauty Mart</div>
                     <div class="receipt-order-id">Order #<?= $order['id'] ?></div>
                     <div class="receipt-date"><?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></div>
                 </div>

                 <!-- Shipping Address -->
                 <div class="receipt-section">
                     <h3>Shipping Address</h3>
                     <?php if (!empty($shipping_address)): ?>
                         <div class="receipt-address-block">
                             <?php if (!empty($shipping_address['address_details'])): ?>
                                 <div><?= htmlspecialchars($shipping_address['address_details']) ?></div>
                             <?php endif; ?>
                             <?php if (!empty($shipping_address['barangay'])): ?>
                                 <div>Barangay <?= htmlspecialchars($shipping_address['barangay']) ?></div>
                             <?php endif; ?>
                             <?php if (!empty($shipping_address['municipality'])): ?>
                                 <div><?= htmlspecialchars($shipping_address['municipality']) ?></div>
                             <?php endif; ?>
                             <?php if (!empty($shipping_address['province'])): ?>
                                 <div><?= htmlspecialchars($shipping_address['province']) ?></div>
                             <?php endif; ?>
                             <?php if (!empty($shipping_address['zip_code'])): ?>
                                 <div>ZIP: <?= htmlspecialchars($shipping_address['zip_code']) ?></div>
                             <?php endif; ?>
                         </div>
                     <?php else: ?>
                         <p class="receipt-no-data">No shipping address on file.</p>
                     <?php endif; ?>
                 </div>

                 <!-- Payment & Order Info -->
                 <div class="receipt-section">
                     <h3>Payment & Order Info</h3>
                     <div class="receipt-info-grid">
                         <div class="receipt-info-row">
                             <span>Payment Method:</span>
                             <span><?= htmlspecialchars(strtoupper($order['payment_method_name'] ?? 'COD')) ?></span>
                         </div>
                         <div class="receipt-info-row">
                             <span>Payment Status:</span>
                             <span><?= htmlspecialchars(getOrderPaymentStatus($order)) ?></span>
                         </div>
                         <div class="receipt-info-row">
                             <span>Order Status:</span>
                             <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                         </div>
                         <?php if (!empty($order['tracking_number'])): ?>
                         <div class="receipt-info-row">
                             <span>Tracking #:</span>
                             <span style="font-weight:800;color:var(--pink-accent);"><?= htmlspecialchars($order['tracking_number']) ?></span>
                         </div>
                         <?php endif; ?>
                     </div>
                 </div>

                 <!-- Order Items -->
                 <div class="receipt-section">
                     <h3>Order Items (<?= count($order_items) ?>)</h3>
                     <div class="receipt-seller-groups">
                         <?php foreach ($seller_groups as $group): ?>
                         <div class="receipt-seller-box">
                             <div class="receipt-seller-header">
                                 <span><?= htmlspecialchars($group['seller_name']) ?></span>
                                 <span class="status-badge status-<?= htmlspecialchars($group['status']) ?>"><?= ucfirst($group['status']) ?></span>
                                 <span><?= count($group['items']) ?> item<?= count($group['items']) === 1 ? '' : 's' ?></span>
                             </div>
                             <table class="receipt-items-table">
                                 <thead>
                                     <tr>
                                         <th>Product</th>
                                         <th>Qty</th>
                                         <th>Price</th>
                                         <th>Subtotal</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach ($group['items'] as $item): ?>
                                     <tr>
                                         <td>
                                             <div class="receipt-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                         </td>
                                         <td><?= $item['quantity'] ?></td>
                                         <td>&#8369;<?= number_format($item['price'], 2) ?></td>
                                         <td>&#8369;<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                     </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                             <div class="receipt-seller-total">
                                 <span>Store Total</span>
                                 <span>&#8369;<?= number_format($group['subtotal'], 2) ?></span>
                             </div>
                         </div>
                         <?php endforeach; ?>
                     </div>
                 </div>

                 <!-- Order Summary -->
                 <div class="receipt-section receipt-summary-section">
                     <div class="receipt-summary-row">
                         <span>Subtotal</span>
                         <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                     </div>
                     <div class="receipt-summary-row">
                         <span>Shipping Fee</span>
                         <span class="receipt-free">Free</span>
                     </div>
                     <div class="receipt-summary-row receipt-total">
                         <span>Total</span>
                         <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                     </div>
                 </div>

                 <div class="receipt-footer">
                     <p>Thank you for shopping with Beauty Mart! 💖</p>
                 </div>
             </div>
             <div class="receipt-modal-footer">
                 <button type="button" class="btn-print-receipt" id="btn-print-receipt">Print Receipt</button>
                 <button type="button" class="btn-close-modal" id="btn-close-modal">Close</button>
             </div>
         </div>
     </div>

     <script>
     (function() {
         const modal = document.getElementById('receipt-modal');
         const openBtn = document.getElementById('view-receipt-btn');
         const closeBtn = document.getElementById('receipt-close');
         const closeBtn2 = document.getElementById('btn-close-modal');
         const printBtn = document.getElementById('btn-print-receipt');

         openBtn?.addEventListener('click', () => {
             modal.style.display = 'flex';
             document.body.style.overflow = 'hidden';
         });

         const closeModal = () => {
             modal.style.display = 'none';
             document.body.style.overflow = '';
         };

         closeBtn?.addEventListener('click', closeModal);
         closeBtn2?.addEventListener('click', closeModal);

         modal?.addEventListener('click', (e) => {
             if (e.target === modal) closeModal();
         });

         printBtn?.addEventListener('click', () => {
             // Temporarily show all modal content for printing
             modal.style.display = 'block';
             document.body.style.overflow = 'hidden';

             // Add print-specific styles if not present
             if (!document.getElementById('receipt-print-styles')) {
                 const styles = document.createElement('style');
                 styles.id = 'receipt-print-styles';
                 styles.innerHTML = `
                     @media print {
                         body * { visibility: hidden; }
                         #receipt-modal, #receipt-modal * { visibility: visible; }
                         #receipt-modal {
                             position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                             background: #fff; z-index: 999999;
                             display: flex !important;
                             align-items: flex-start;
                             justify-content: center;
                             overflow: auto;
                         }
                         .receipt-modal {
                             box-shadow: none;
                             margin: 0;
                             max-width: 100%;
                             width: 100%;
                         }
                         .receipt-modal-header,
                         .receipt-modal-footer,
                         .receipt-close { display: none !important; }
                         @page { margin: 12mm; }
                     }
                 `;
                 document.head.appendChild(styles);
             }

             window.print();
             // After print, hide modal again
             setTimeout(() => {
                 modal.style.display = 'none';
                 document.body.style.overflow = '';
             }, 100);
         });
     })();
     </script>

 </body>
 </html>
