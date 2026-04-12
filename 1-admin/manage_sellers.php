<?php
require_once '../includes/functions.php';

if (!isLoggedIn())
    redirect('../login.php');
$stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_record = $stmt->fetch();
if (!$admin_record)
    redirect('../login.php');

$admin_id = $admin_record['id'];

// Fetch admin data
$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $stmt->fetchColumn();

// Pending apps count (for sidebar badge)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps_count = (int) $stmt->fetchColumn();

$success = '';
$error = '';

// Handle approve / decline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $app_id = (int) $_POST['app_id'];
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $status = $action === 'approve' ? 'approved' : 'declined';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT sa.*, u.email, u.firstname, u.lastname, u.username FROM seller_applications sa JOIN users u ON sa.user_id = u.id WHERE sa.id = ?");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch();
        if (!$app)
            throw new Exception("Application not found.");

        $pdo->prepare("UPDATE seller_applications SET status=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$status, $admin_notes, $admin_id, $app_id]);

        if ($status === 'approved') {
            $stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
            $stmt->execute([$app['user_id']]);
            if ($stmt->rowCount())
                throw new Exception("User already has a seller account.");

            $pdo->prepare("INSERT INTO sellers (user_id, business_name, business_address, phone, tax_id, approved_by, approved_at) VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$app['user_id'], $app['business_name'], $app['business_address'], $app['phone'], $app['tax_id'], $admin_id]);
            $sellerId = (int) $pdo->lastInsertId();

            logSystemEvent(
                'seller_application_approved',
                'seller_applications',
                $app_id,
                "Admin approved seller application #{$app_id} for user {$app['username']} and created seller #{$sellerId}."
            );

            sendEmail($app['email'], "Seller Application Approved!", "<h2>Congratulations!</h2><p>Dear {$app['firstname']},</p><p>Your seller application on Beauty Mart has been <strong>approved</strong>! You can now log in and start selling.</p>");
            $success = "Application approved! {$app['firstname']} has been notified.";
        } else {
            logSystemEvent(
                'seller_application_declined',
                'seller_applications',
                $app_id,
                "Admin declined seller application #{$app_id} for user {$app['username']}. Notes: " . ($admin_notes !== '' ? $admin_notes : 'No notes provided.')
            );
            sendEmail($app['email'], "Seller Application Update", "<p>Dear {$app['firstname']},</p><p>Your application was <strong>declined</strong>. Reason: " . htmlspecialchars($admin_notes) . "</p>");
            $success = "Application declined. {$app['firstname']} has been notified.";
        }

        createNotification($_SESSION['user_id'], "Application #$app_id $status for {$app['username']}.", 'seller_application');

        $pdo->commit();
        $_SESSION['flash_success'] = $success;
        header('Location: manage_sellers.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payout_action'])) {
    $payout_id = (int) ($_POST['payout_id'] ?? 0);
    $payout_action = $_POST['payout_action'];
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    $allowed_actions = ['processing', 'completed', 'failed'];
    if (!in_array($payout_action, $allowed_actions, true)) {
        $error = "Invalid payout action.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                SELECT sp.*, s.user_id, s.business_name, u.firstname, u.lastname, u.username
                FROM seller_payouts sp
                JOIN sellers s ON sp.seller_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE sp.id = ?
            ");
            $stmt->execute([$payout_id]);
            $payout = $stmt->fetch();

            if (!$payout) {
                throw new Exception("Payout request not found.");
            }

            if (in_array($payout['status'], ['completed', 'failed'], true)) {
                throw new Exception("This payout request has already been finalized.");
            }

            $final_transaction_id = $transaction_id !== '' ? $transaction_id : ($payout['transaction_id'] ?? null);

            if ($payout_action === 'processing') {
                $stmt = $pdo->prepare("UPDATE seller_payouts SET status = 'processing', transaction_id = ? WHERE id = ?");
                $stmt->execute([$final_transaction_id, $payout_id]);
                $seller_message = "Your payout request for PHP " . number_format($payout['amount'], 2) . " is now being processed.";
                $success = "Payout request #{$payout_id} marked as processing.";
            } else {
                $stmt = $pdo->prepare("UPDATE seller_payouts SET status = ?, transaction_id = ?, processed_at = NOW() WHERE id = ?");
                $stmt->execute([$payout_action, $final_transaction_id, $payout_id]);

                if ($payout_action === 'completed') {
                    $seller_message = "Your payout request for PHP " . number_format($payout['amount'], 2) . " has been approved and completed.";
                    $success = "Payout request #{$payout_id} marked as completed.";
                } else {
                    $seller_message = "Your payout request for PHP " . number_format($payout['amount'], 2) . " could not be completed. Please contact admin for verification.";
                    $success = "Payout request #{$payout_id} marked as failed.";
                }
            }

            createNotification($payout['user_id'], $seller_message, 'payout');
            createNotification($_SESSION['user_id'], "Payout request #{$payout_id} updated to {$payout_action}.", 'payout');
            logSystemEvent(
                'seller_payout_updated',
                'seller_payouts',
                $payout_id,
                "Admin set payout #{$payout_id} for seller {$payout['business_name']} to {$payout_action}."
            );

            $pdo->commit();
            $_SESSION['flash_success'] = $success;
            header('Location: manage_sellers.php?tab=payouts');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed: " . $e->getMessage();
        }
    }
}

$success = $_SESSION['flash_success'] ?? $success;
unset($_SESSION['flash_success']);

// Fetch data
$stmt = $pdo->prepare("SELECT sa.*, u.username, u.email, u.firstname, u.lastname FROM seller_applications sa JOIN users u ON sa.user_id=u.id WHERE sa.status='pending' ORDER BY sa.created_at DESC");
$stmt->execute();
$pending = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT s.*, u.username, u.email, u.firstname, u.lastname, admin_u.username AS admin_username FROM sellers s JOIN users u ON s.user_id=u.id LEFT JOIN admins a ON s.approved_by=a.id LEFT JOIN users admin_u ON a.user_id=admin_u.id ORDER BY s.approved_at DESC LIMIT 30");
$stmt->execute();
$approved = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT sa.*, u.username, u.email, u.firstname, u.lastname, admin_u.username AS admin_username FROM seller_applications sa JOIN users u ON sa.user_id=u.id LEFT JOIN admins a ON sa.reviewed_by=a.id LEFT JOIN users admin_u ON a.user_id=admin_u.id WHERE sa.status='declined' ORDER BY sa.reviewed_at DESC LIMIT 30");
$stmt->execute();
$declined = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT sp.*, pm.name AS payment_method_name, s.business_name, s.phone, u.username, u.email, u.firstname, u.lastname
    FROM seller_payouts sp
    LEFT JOIN payment_methods pm ON sp.payment_method_id = pm.id
    JOIN sellers s ON sp.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sp.status IN ('pending', 'processing')
    ORDER BY FIELD(sp.status, 'pending', 'processing'), sp.requested_at DESC
");
$stmt->execute();
$open_payouts = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT sp.*, pm.name AS payment_method_name, s.business_name, u.username, u.firstname, u.lastname
    FROM seller_payouts sp
    LEFT JOIN payment_methods pm ON sp.payment_method_id = pm.id
    JOIN sellers s ON sp.seller_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sp.status IN ('completed', 'failed')
    ORDER BY sp.processed_at DESC, sp.requested_at DESC
    LIMIT 30
");
$stmt->execute();
$recent_payouts = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sellers | Beauty Mart Admin</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">
        <link rel="stylesheet" href="../css/admin_sellers.css">
</head>

<body>

    <div class="shell">

        <!--Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <img src="../images/logo.png" alt="Logo"
                        onerror="this.style.display='none';this.parentElement.textContent='ðŸŒ¸'">
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
            <a href="manage_users.php" class="<?= $current_page==='manage_users.php' ? 'active':'' ?>">
                <span class="ni">👥</span> Manage Users
            </a>
            <a href="manage_sellers.php" class="<?= $current_page === 'manage_sellers.php' ? 'active' : '' ?>">
                    <span class="ni">🏪</span> Manage Sellers
                    <?php if ($pending_apps_count > 0): ?><span
                            class="nbadge"><?= $pending_apps_count ?></span><?php endif; ?>
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

        <!--Main -->
        <div class="main">

            <!-- Topbar -->
            <div class="topbar">
                <div class="topbar-left">
                    <h1>Seller Management</h1>
                    <p>Review applications and manage approved sellers</p>
                </div>
                <div class="topbar-right">
                    <span class="topbar-date"><?= date('F j, Y') ?></span>
                    <?php if (count($pending) > 0): ?>
                        <span
                            style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;">
                             <?= count($pending) ?> pending
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content">

                <!-- Alerts -->
                <?php if ($success): ?>
                    <div class="alert alert-success"> <?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"> <?= htmlspecialchars($error) ?></div><?php endif; ?>

                <!-- Summary chips -->
                <div class="summary-strip">
                    <div class="summary-chip">
                        <div class="sc-icon pending">📝</div>
                        <div>
                            <div class="sc-val"><?= count($pending) ?></div>
                            <div class="sc-lbl">Pending Applications</div>
                        </div>
                    </div>
                    <div class="summary-chip">
                        <div class="sc-icon approved">✅</div>
                        <div>
                            <div class="sc-val"><?= count($approved) ?></div>
                            <div class="sc-lbl">Approved Sellers</div>
                        </div>
                    </div>
                    <div class="summary-chip">
                        <div class="sc-icon declined">🚫</div>
                        <div>
                            <div class="sc-val"><?= count($declined) ?></div>
                            <div class="sc-lbl">Declined Applications</div>
                        </div>
                    </div>
                    <div class="summary-chip">
                        <div class="sc-icon pending">📝</div>
                        <div>
                            <div class="sc-val"><?= count($open_payouts) ?></div>
                            <div class="sc-lbl">Open Payout Requests</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="pending" onclick="switchTab('pending', this)">
                        Pending <span class="tab-count"><?= count($pending) ?></span>
                    </button>
                    <button class="tab-btn" data-tab="approved" onclick="switchTab('approved', this)">
                        Approved <span class="tab-count"><?= count($approved) ?></span>
                    </button>
                    <button class="tab-btn" data-tab="declined" onclick="switchTab('declined', this)">
                        Declined <span class="tab-count"><?= count($declined) ?></span>
                    </button>
                    <button class="tab-btn" data-tab="payouts" onclick="switchTab('payouts', this)">
                        Request Payout <span class="tab-count"><?= count($open_payouts) ?></span>
                    </button>
                </div>

                <!--Pending tab -->
                <div id="pending-tab" class="tab-content active">
                    <?php if (empty($pending)): ?>
                        <div class="table-wrap">
                            <div class="empty-state">
                                <div class="ei"></div>
                                <h3>All clear!</h3>
                                <p>No pending seller applications at the moment.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending as $app): ?>
                            <div class="app-card">
                                <div class="app-card-head">
                                    <div>
                                        <div class="app-biz-name"> <?= htmlspecialchars($app['business_name']) ?></div>
                                        <div class="app-user-info">
                                            <?= htmlspecialchars($app['firstname'] . ' ' . $app['lastname']) ?>
                                            <span class="uname">@<?= htmlspecialchars($app['username']) ?></span>
                                             <?= htmlspecialchars($app['email']) ?>
                                        </div>
                                    </div>
                                    <div class="app-date"> Applied
                                        <?= date('M j, Y g:i A', strtotime($app['created_at'])) ?></div>
                                </div>

                                <div class="app-card-body">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <span class="detail-label">Business Address</span>
                                            <span class="detail-val"><?= htmlspecialchars($app['business_address']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Phone</span>
                                            <span class="detail-val"><?= htmlspecialchars($app['phone']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Tax ID</span>
                                            <span class="detail-val"><?= htmlspecialchars($app['tax_id'] ?: '') ?></span>
                                        </div>
                                    </div>

                                    <form method="post">
                                        <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                        <div class="notes-group">
                                            <label for="notes_<?= $app['id'] ?>">Admin Notes <span
                                                    style="color:var(--text-muted);font-weight:600;">(required when
                                                    declining)</span></label>
                                            <textarea id="notes_<?= $app['id'] ?>" name="admin_notes"
                                                placeholder="Provide reason for decision"></textarea>
                                        </div>
                                        <div class="btn-group">
                                            <button type="submit" name="action" value="approve" class="btn-approve"
                                                onclick="return confirm('Approve this seller application?\n\nThe seller will be notified by email.')">
                                                Approve Application
                                            </button>
                                            <button type="submit" name="action" value="decline" class="btn-decline"
                                                onclick="return confirm('Decline this seller application?\n\nThe applicant will be notified with your notes.')">
                                                Decline Application
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Approved tab-->
                <div id="approved-tab" class="tab-content">
                    <?php if (empty($approved)): ?>
                        <div class="table-wrap">
                            <div class="empty-state">
                                <div class="ei"></div>
                                <h3>No approved sellers yet</h3>
                                <p>Approved sellers will appear here.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap" style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Business</th>
                                        <th>Owner</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Approved By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="biz-name"><?= htmlspecialchars($s['business_name']) ?></div>
                                                <?php if ($s['tax_id']): ?>
                                                    <div class="tax-info">Tax ID: <?= htmlspecialchars($s['tax_id']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:var(--text-dark);">
                                                    <?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?></div>
                                                <div class="sub-info">@<?= htmlspecialchars($s['username']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($s['email']) ?></td>
                                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                                            <td><span class="approved-badge">✓ Approved</span></td>
                                            <td><?= htmlspecialchars($s['admin_username'] ?? 'System') ?></td>
                                            <td><?= $s['approved_at'] ? date('M j, Y', strtotime($s['approved_at'])) : '—' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Declined tab -->
                <div id="declined-tab" class="tab-content">
                    <?php if (empty($declined)): ?>
                        <div class="table-wrap">
                            <div class="empty-state">
                                <div class="ei">📋</div>
                                <h3>No declined applications</h3>
                                <p>Declined applications will appear here.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap" style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Business</th>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th>Reviewed By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($declined as $d): ?>
                                        <tr>
                                            <td>
                                                <div class="biz-name"><?= htmlspecialchars($d['business_name']) ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight:700;color:var(--text-dark);">
                                                    <?= htmlspecialchars($d['firstname'] . ' ' . $d['lastname']) ?></div>
                                                <div class="sub-info">@<?= htmlspecialchars($d['username']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($d['email']) ?></td>
                                            <td><span class="declined-badge"> Declined</span></td>
                                            <td>
                                                <div class="reason-cell"
                                                    title="<?= htmlspecialchars($d['admin_notes'] ?? '') ?>">
                                                    <?= htmlspecialchars(substr($d['admin_notes'] ?? 'No reason provided', 0, 80)) ?>
                                                    <?= strlen($d['admin_notes'] ?? '') > 80 ? '…' : '' ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($d['admin_username'] ?? 'System') ?></td>
                                            <td><?= $d['reviewed_at'] ? date('M j, Y', strtotime($d['reviewed_at'])) : '—' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="payouts-tab" class="tab-content">
                    <?php if (empty($open_payouts)): ?>
                        <div class="table-wrap">
                            <div class="empty-state">
                                <div class="ei">📋</div>
                                <h3>No open payout requests</h3>
                                <p>New seller payout requests will appear here for admin review.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($open_payouts as $payout): ?>
                            <div class="app-card">
                                <div class="app-card-head">
                                    <div>
                                        <div class="app-biz-name"><?= htmlspecialchars($payout['business_name']) ?></div>
                                        <div class="app-user-info">
                                            <?= htmlspecialchars($payout['firstname'] . ' ' . $payout['lastname']) ?>
                                            <span class="uname">@<?= htmlspecialchars($payout['username']) ?></span>
                                            Â· <?= htmlspecialchars($payout['email']) ?>
                                        </div>
                                    </div>
                                    <div class="app-date">
                                        Requested <?= date('M j, Y g:i A', strtotime($payout['requested_at'])) ?>
                                    </div>
                                </div>

                                <div class="app-card-body">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <span class="detail-label">Amount</span>
                                            <span class="detail-val">PHP <?= number_format($payout['amount'], 2) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Method</span>
                                            <span class="detail-val"><?= htmlspecialchars($payout['payment_method_name'] ?? 'N/A') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Status</span>
                                            <span class="payout-status <?= htmlspecialchars($payout['status']) ?>"><?= htmlspecialchars(ucfirst($payout['status'])) ?></span>
                                        </div>
                                    </div>

                                    <form method="post">
                                        <input type="hidden" name="payout_id" value="<?= (int) $payout['id'] ?>">
                                        <div class="payout-actions">
                                            <div class="notes-group">
                                                <label for="transaction_<?= (int) $payout['id'] ?>">Reference / Transaction Code</label>
                                                <input
                                                    type="text"
                                                    class="text-input"
                                                    id="transaction_<?= (int) $payout['id'] ?>"
                                                    name="transaction_id"
                                                    value="<?= htmlspecialchars($payout['transaction_id'] ?? '') ?>"
                                                    placeholder="Optional payout reference">
                                            </div>
                                            <div class="payout-action-buttons">
                                                <button type="submit" name="payout_action" value="processing" class="btn-process">Mark Processing</button>
                                                <button type="submit" name="payout_action" value="completed" class="btn-approve" onclick="return confirm('Mark this payout request as completed?')">Complete</button>
                                                <button type="submit" name="payout_action" value="failed" class="btn-decline" onclick="return confirm('Mark this payout request as failed?')">Fail</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="table-wrap" style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Business</th>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_payouts)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--text-muted);">No processed payout requests yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_payouts as $payout): ?>
                                        <tr>
                                            <td><div class="biz-name"><?= htmlspecialchars($payout['business_name']) ?></div></td>
                                            <td>
                                                <div style="font-weight:700;color:var(--text-dark);">
                                                    <?= htmlspecialchars($payout['firstname'] . ' ' . $payout['lastname']) ?>
                                                </div>
                                                <div class="sub-info">@<?= htmlspecialchars($payout['username']) ?></div>
                                            </td>
                                            <td>PHP <?= number_format($payout['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($payout['payment_method_name'] ?? 'N/A') ?></td>
                                            <td><span class="payout-status <?= htmlspecialchars($payout['status']) ?>"><?= htmlspecialchars(ucfirst($payout['status'])) ?></span></td>
                                            <td><?= htmlspecialchars($payout['transaction_id'] ?: '') ?></td>
                                            <td><?= $payout['processed_at'] ? date('M j, Y', strtotime($payout['processed_at'])) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /content -->

            <footer class="admin-footer">
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
        function switchTab(tab, btn) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
            btn.classList.add('active');
            const nextUrl = `${window.location.pathname}?tab=${tab}`;
            window.history.replaceState({}, '', nextUrl);
        }

        const requestedTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedTab) {
            const requestedButton = document.querySelector(`.tab-btn[data-tab="${requestedTab}"]`);
            if (requestedButton) {
                switchTab(requestedTab, requestedButton);
            }
        }
    </script>

</body>

</html>
