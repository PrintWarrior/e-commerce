<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];
$success = '';
$error   = '';

$earnings_stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END), 0) AS total_earned,
        COALESCE(SUM(amount), 0) AS total_revenue
    FROM seller_earnings
    WHERE seller_id = ?
");

$payout_summary_stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN status IN ('pending', 'processing') THEN amount ELSE 0 END), 0) AS active_payouts,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS completed_payouts,
        COUNT(CASE WHEN status IN ('pending', 'processing') THEN 1 END) AS active_request_count
    FROM seller_payouts
    WHERE seller_id = ?
");

// Handle payout request (PRG)
if (isset($_POST['request_payout'])) {
    $amount         = floatval($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } elseif (empty($payment_method)) {
        $error = "Please select a payment method.";
    } else {
        $earnings_stmt->execute([$seller_id]);
        $earning_totals = $earnings_stmt->fetch();

        $payout_summary_stmt->execute([$seller_id]);
        $payout_totals = $payout_summary_stmt->fetch();

        $available_balance = max(
            0,
            (float) $earning_totals['total_earned']
            - (float) $payout_totals['active_payouts']
            - (float) $payout_totals['completed_payouts']
        );

        if ($amount < 100) {
            $error = "Minimum payout is ₱100.00.";
        } elseif ($amount > $available_balance) {
            $error = "Insufficient balance. Available: ₱" . number_format($available_balance, 2);
        } else {
            $stmt = $pdo->prepare("INSERT INTO seller_payouts (seller_id, amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
            if ($stmt->execute([$seller_id, $amount, $payment_method])) {
                foreach (getAdminIds() as $admin_user_id) {
                    createNotification(
                        $admin_user_id,
                        $seller['business_name'] . " requested a payout of ₱" . number_format($amount, 2) . ".",
                        'payout'
                    );
                }

                $_SESSION['flash_success'] = "Payout request of ₱" . number_format($amount, 2) . " submitted successfully!";
                header('Location: earnings.php');
                exit;
            } else {
                $error = "Failed to submit payout request. Please try again.";
            }
        }
    }
}

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// Earnings summary
$earnings_stmt->execute([$seller_id]);
$es = $earnings_stmt->fetch();

$payout_summary_stmt->execute([$seller_id]);
$payout_summary = $payout_summary_stmt->fetch();

$active_payout_amount = (float) $payout_summary['active_payouts'];
$active_payout_count = (int) $payout_summary['active_request_count'];
$completed_payout_amount = (float) $payout_summary['completed_payouts'];
$available_balance = max(0, (float) $es['total_earned'] - $active_payout_amount - $completed_payout_amount);
// Monthly earnings (last 6 months)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
           DATE_FORMAT(created_at,'%b %Y')  AS label,
           SUM(amount) AS monthly_earnings
    FROM seller_earnings
    WHERE seller_id = ? AND status = 'paid'
    GROUP BY month ORDER BY month DESC LIMIT 6
");
$stmt->execute([$seller_id]);
$monthly_earnings = array_reverse($stmt->fetchAll());

// Recent transactions
$stmt = $pdo->prepare("
    SELECT se.*, o.id AS order_id
    FROM seller_earnings se
    JOIN orders o ON se.order_id = o.id
    WHERE se.seller_id = ?
    ORDER BY se.created_at DESC LIMIT 20
");
$stmt->execute([$seller_id]);
$transactions = $stmt->fetchAll();

// Payout history
$stmt = $pdo->prepare("SELECT * FROM seller_payouts WHERE seller_id = ? ORDER BY requested_at DESC");
$stmt->execute([$seller_id]);
$payouts = $stmt->fetchAll();

// Chart data
$chart_labels   = array_column($monthly_earnings, 'label');
$chart_data     = array_map('floatval', array_column($monthly_earnings, 'monthly_earnings'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_earnings.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="seller-wrapper">

    <?php /* Sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Earnings & Payments</h1>
                <p>Track your revenue and manage payout requests</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Available balance hero card -->
            <div class="balance-card">
                <div class="balance-label">Available Balance</div>
                <div class="balance-amount">₱<?= number_format($available_balance, 2) ?></div>
                <div class="balance-meta">
                    <div class="balance-meta-item">
                        <span class="bm-label">Total Revenue</span>
                        <span class="bm-val">₱<?= number_format($es['total_revenue'], 2) ?></span>
                    </div>
                    <div class="balance-meta-item">
                        <span class="bm-label">Total Earned</span>
                        <span class="bm-val">₱<?= number_format($es['total_earned'], 2) ?></span>
                    </div>
                    <div class="balance-meta-item">
                        <span class="bm-label">Open Payout Requests</span>
                        <span class="bm-val">₱<?= number_format($active_payout_amount, 2)?></span>
                    </div>
                </div>
            </div>

            <!-- Stat cards --
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon-wrap">ðŸ’°</div>
                    <div class="stat-info">
                        <h3>â‚±<?= number_format($es['total_revenue'], 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrap">âœ…</div>
                    <div class="stat-info">
                        <h3>â‚±<?= number_format($es['total_earned'], 2) ?></h3>
                        <p>Total Earned</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon-wrap">â³</div>
                    <div class="stat-info">
                        <h3><?= $active_payout_count ?></h3>
                        <p>Open Payout Requests</p>
                    </div>
                </div>
            </div>-->

            <!-- Chart + Payout form side by side -->
            <div class="two-col">

                <!-- Payout request form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Request Payout</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" class="payout-form">

                            <div class="form-group">
                                <label for="amount">Amount </label>
                                <input type="number" id="amount" name="amount"
                                       step="0.01" min="100"
                                       max="<?= $available_balance ?>"
                                       placeholder="e.g. 500.00"
                                       required>
                                <span class="info-note">
                                    <?= number_format($available_balance, 2) ?>
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Payment Method</label>
                                <div class="method-options">
                                    <label class="method-option">
                                        <input type="radio" name="payment_method" value="gcash" required>
                                        <span class="method-icon"></span>
                                        <span class="method-name">GCash</span>
                                    </label>
                                    <!--<label class="method-option">
                                        <input type="radio" name="payment_method" value="paymaya">
                                        <span class="method-icon">ðŸ’œ</span>
                                        <span class="method-name">Maya (PayMaya)</span>
                                    </label>
                                    <label class="method-option">
                                        <input type="radio" name="payment_method" value="bank_transfer">
                                        <span class="method-icon">ðŸ¦</span>
                                        <span class="method-name">Bank Transfer</span>
                                    </label>-->
                                </div>
                            </div>

                            <button type="submit" name="request_payout" class="btn-payout">
                                Request Payout
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Monthly earnings chart -->
                <div class="card chart-card">
                    <div class="card-header">
                        <h2>Monthly Earnings</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly_earnings)): ?>
                            <p class="empty-state">No earnings data yet.</p>
                        <?php else: ?>
                            <canvas id="earningsChart" height="220"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h2>Recent Transactions</h2>
                </div>
                <?php if (empty($transactions)): ?>
                    <p class="empty-state">No transactions yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td>#<?= $t['order_id'] ?></td>
                                    <td class="amount-cell amount-positive">
                                        ₱<?= number_format($t['amount'], 2) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $t['status'] ?>">
                                            <?= ucfirst($t['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payout History -->
            <div class="card">
                <div class="card-header">
                    <h2>Payout History</h2>
                </div>
                <?php if (empty($payouts)): ?>
                    <p class="empty-state">No payout requests yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payouts as $p): ?>
                                <tr>
                                    <td class="amount-cell">₱<?= number_format($p['amount'], 2) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($p['requested_at'])) ?></td>
                                    <td>
                                        <?= $p['processed_at']
                                            ? date('M j, Y', strtotime($p['processed_at']))
                                            : '<span style="color:var(--text-muted)">”</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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

<?php if (!empty($monthly_earnings)): ?>
<script>
    const ctx = document.getElementById('earningsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Earnings (₱)',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(232,114,142,.25)',
                borderColor: '#e8728e',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(232,114,142,.45)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
<?php endif; ?>

</body>
</html>
