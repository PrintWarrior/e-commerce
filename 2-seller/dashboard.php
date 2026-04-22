<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$seller_record = $stmt->fetch();
$seller_id = $seller_record['id'];

$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT SUM(oi.quantity * oi.price) as total_sales FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ? AND o.status = 'completed'");
$stmt->execute([$seller_id]);
$total_sales = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?");
$stmt->execute([$seller_id]);
$total_orders = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ? AND o.status = 'pending'");
$stmt->execute([$seller_id]);
$pending_orders = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_products = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT DISTINCT o.*, u.firstname, u.lastname FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id JOIN users u ON o.customer_id = (SELECT id FROM customers WHERE user_id = u.id) WHERE p.seller_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$seller_id]);
$recent_orders = $stmt->fetchAll();

$sales_data = [];
$sales_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = $i === 0 ? 'Today' : ($i === 1 ? 'Yesterday' : date('M d', strtotime("-$i days")));
    $sales_labels[] = $label;
    $stmt = $pdo->prepare("SELECT SUM(oi.quantity * oi.price) FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ? AND DATE(o.created_at) = ? AND o.status = 'completed'");
    $stmt->execute([$seller_id, $date]);
    $sales_data[] = (float)($stmt->fetchColumn() ?? 0);
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND stock < 10 AND stock > 0 ORDER BY stock ASC LIMIT 5");
$stmt->execute([$seller_id]);
$low_stock = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND stock = 0 LIMIT 5");
$stmt->execute([$seller_id]);
$out_of_stock = $stmt->fetchAll();

$recent_reviews = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/seller_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="shell">


    <!-- ── Main ─────────────────────────────────────────────────── -->
    <div class="main">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($seller['firstname']) ?>! Here's your store overview.</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <a href="products.php?action=add">
                    <button class="topbar-btn">+ Add Product</button>
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Stat cards -->
            <div class="stats-grid">
                <div class="stat-card highlight">
                    <div class="stat-icon-wrap">💰</div>
                    <div class="stat-info">
                        <h3>₱<?= number_format($total_sales, 2) ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrap">📦</div>
                    <div class="stat-info">
                        <h3><?= $total_orders ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrap">⏳</div>
                    <div class="stat-info">
                        <h3><?= $pending_orders ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrap">🛍️</div>
                    <div class="stat-info">
                        <h3><?= $total_products ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>

            <!-- Sales chart -->
            <div class="chart-card">
                <div class="card-header">
                    <h2>Sales Overview — Last 7 Days</h2>
                    <!--<a href="analytics.php" class="view-all">Full Analytics →</a>-->
                </div>
                <canvas id="salesChart" height="90"></canvas>
            </div>

            <!-- Lower grid: orders | stock | reviews -->
            <div class="lower-grid">

                <!-- Recent Orders -->
                <div class="dash-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="orders.php" class="view-all">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p class="empty-state">No orders yet.</p>
                        <?php else: ?>
                            <table class="simple-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td>#<?= $o['id'] ?></td>
                                        <td><?= htmlspecialchars($o['firstname'] . ' ' . $o['lastname']) ?></td>
                                        <td>₱<?= number_format($o['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $o['status'] ?>">
                                                <?= ucfirst($o['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Low Stock -->
                <div class="dash-card">
                    <div class="card-header">
                        <h2>Stock Alerts</h2>
                        <a href="products.php" class="view-all">Manage →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock) && empty($out_of_stock)): ?>
                            <p class="empty-state">All products have sufficient stock. ✓</p>
                        <?php else: ?>
                            <?php foreach ($out_of_stock as $p): ?>
                                <div class="stock-item">
                                    <span class="stock-name"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="stock-badge stock-out">Out of Stock</span>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($low_stock as $p): ?>
                                <div class="stock-item">
                                    <span class="stock-name"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="stock-badge stock-low">Stock: <?= $p['stock'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews 
                <div class="dash-card">
                    <div class="card-header">
                        <h2>Recent Reviews</h2>
                        <a href="reviews.php" class="view-all">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_reviews)): ?>
                            <p class="empty-state">No reviews yet.</p>
                        <?php else: ?>
                            <?php foreach ($recent_reviews as $r): ?>
                                <div class="review-item">
                                    <div class="review-top">
                                        <span class="reviewer"><?= htmlspecialchars($r['username']) ?></span>
                                        <span class="stars">
                                            <?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?>
                                        </span>
                                    </div>
                                    <div class="review-product"><?= htmlspecialchars($r['product_name']) ?></div>
                                    <div class="review-text"><?= htmlspecialchars(substr($r['review'], 0, 100)) ?>…</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div> -->

            </div>
        </div><!-- /content -->

        <!-- Footer -->
        <footer>
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

    </div><!-- /main -->
</div><!-- /shell -->

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($sales_labels) ?>,
            datasets: [{
                label: 'Sales (₱)',
                data: <?= json_encode($sales_data) ?>,
                borderColor: '#e8728e',
                backgroundColor: 'rgba(232,114,142,.10)',
                pointBackgroundColor: '#e8728e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                tension: 0.42,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#2e2e2e',
                    bodyColor: '#e8728e',
                    borderColor: '#f5c6d4',
                    borderWidth: 1.5,
                    padding: 10,
                    callbacks: {
                        label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#fce8ee' },
                    ticks: { color: '#999', font: { family: 'Nunito', size: 12 } }
                },
                y: {
                    grid: { color: '#fce8ee' },
                    ticks: {
                        color: '#999',
                        font: { family: 'Nunito', size: 12 },
                        callback: v => '₱' + v.toLocaleString()
                    }
                }
            }
        }
    });
</script>

</body>
</html>