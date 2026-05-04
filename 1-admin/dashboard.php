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

// ── Flash messages ────────────────────────────────────────────
$flash = null;
if (isset($_SESSION['flash_message'])) {
    $flash = ['type' => $_SESSION['flash_type'] ?? 'success', 'text' => $_SESSION['flash_message']];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// ── Handle notifications ──────────────────────────────────────
if (isset($_POST['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: dashboard.php'); exit;
}
if (isset($_POST['mark_read'], $_POST['notification_id'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([$_POST['notification_id'], $_SESSION['user_id']]);
    header('Location: dashboard.php'); exit;
}

// ── Statistics ────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id NOT IN (SELECT user_id FROM admins)");
$stmt->execute(); $total_users = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM sellers");      $total_sellers   = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM customers");    $total_customers = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM products");     $total_products  = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");       $total_orders    = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status='completed'");
$total_revenue = (float)($stmt->fetchColumn() ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute(); $pending_apps = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email_verified=0");
$stmt->execute(); $unverified = (int)$stmt->fetchColumn();

// ── Unread notifications ──────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT n.*, nt.code AS type_code, nt.label AS type_label, u.username
    FROM notifications n
    LEFT JOIN notification_types nt ON n.notification_type_id = nt.id
    JOIN users u ON n.user_id=u.id
    WHERE n.user_id=? AND n.is_read=0
    ORDER BY n.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications  = $stmt->fetchAll();
$unread_count   = count($notifications);

// ── Recent orders ─────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT o.*, u.firstname, u.lastname FROM orders o JOIN customers c ON o.customer_id=c.id JOIN users u ON c.user_id=u.id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute(); $recent_orders = $stmt->fetchAll();

// ── Pending seller applications ───────────────────────────────
$stmt = $pdo->prepare("SELECT sa.*, u.firstname, u.lastname, u.email FROM seller_applications sa JOIN users u ON sa.user_id=u.id WHERE sa.status='pending' ORDER BY sa.created_at DESC LIMIT 5");
$stmt->execute(); $recent_apps = $stmt->fetchAll();

// ── Unverified users ──────────────────────────────────────────
$stmt = $pdo->prepare("SELECT u.*, CASE WHEN a.id IS NOT NULL THEN 'Admin' WHEN s.id IS NOT NULL THEN 'Seller' WHEN c.id IS NOT NULL THEN 'Customer' ELSE 'User' END AS user_role FROM users u LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN sellers s ON u.id=s.user_id LEFT JOIN customers c ON u.id=c.user_id WHERE u.email_verified=0 ORDER BY u.created_at DESC");
$stmt->execute(); $unverified_users = $stmt->fetchAll();

// ── Recent users ──────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT u.*, CASE WHEN a.id IS NOT NULL THEN 'Admin' WHEN s.id IS NOT NULL THEN 'Seller' WHEN c.id IS NOT NULL THEN 'Customer' ELSE 'User' END AS user_role FROM users u LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN sellers s ON u.id=s.user_id LEFT JOIN customers c ON u.id=c.user_id ORDER BY u.created_at DESC LIMIT 5");
$stmt->execute(); $recent_users = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

 <div class="shell">
     <!-- Mobile Hamburger Toggle -->
     <input type="checkbox" id="admin-menu-toggle" class="admin-menu-toggle">
     <label for="admin-menu-toggle" class="admin-hamburger">
         <span></span>
         <span></span>
         <span></span>
     </label>

     <!-- ── Sidebar ──────────────────────────────────────────── -->
     <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo"
                     onerror="this.style.display='none';this.parentElement.textContent='🌸'">
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
                <span class="chip-role">⚙️ Administrator</span>
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

    <!-- ── Main ─────────────────────────────────────────────── -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($admin['firstname']) ?>! Here's your platform overview.</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;">
                        🔔 <?= $unread_count ?> unread
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Flash -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['type']==='success'?'✓':'⚠' ?> <?= htmlspecialchars($flash['text']) ?>
                </div>
            <?php endif; ?>

            <!-- ── Stat cards ─────────────────────────────────── -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div><div class="stat-val"><?= number_format($total_users) ?></div><div class="stat-lbl">Total Users</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div><div class="stat-val"><?= number_format($total_customers) ?></div><div class="stat-lbl">Customers</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏪</div>
                    <div><div class="stat-val"><?= number_format($total_sellers) ?></div><div class="stat-lbl">Sellers</div></div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-icon">💰</div>
                    <div><div class="stat-val">₱<?= number_format($total_revenue, 2) ?></div><div class="stat-lbl">Total Revenue</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div><div class="stat-val"><?= number_format($total_orders) ?></div><div class="stat-lbl">Total Orders</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div><div class="stat-val"><?= number_format($total_products) ?></div><div class="stat-lbl">Products</div></div>
                </div>
                <div class="stat-card warn">
                    <div class="stat-icon">📝</div>
                    <div><div class="stat-val"><?= number_format($pending_apps) ?></div><div class="stat-lbl">Pending Apps</div></div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-icon">✉️</div>
                    <div><div class="stat-val"><?= number_format($unverified) ?></div><div class="stat-lbl">Unverified</div></div>
                </div>
            </div>

            <!-- ── Dashboard grid ─────────────────────────────── -->
            <div class="dash-grid">

                <!-- Notifications -->
                <div class="dash-card">
                    <div class="card-head">
                        <h2>🔔 Notifications</h2>
                        <?php if (!empty($notifications)): ?>
                            <form method="post" style="display:contents;">
                                <button type="submit" name="mark_all_read" class="btn-mark-all">✓ Mark all read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="empty-state">No unread notifications ✓</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $n):
                                $m = getNotificationMeta(getNotificationCode($n));
                            ?>
                            <div class="notif-item">
                                <div class="notif-bubble" style="background:<?= $m['bg'] ?>;border:1.5px solid <?= $m['color'] ?>33;">
                                    <?= $m['icon'] ?>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                                    <div class="notif-time">🕐 <?= date('M j, Y · g:i A', strtotime($n['created_at'])) ?></div>
                                </div>
                                <div class="notif-actions">
                                    <form method="post" style="display:contents;">
                                        <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                                        <button type="submit" name="mark_read" class="btn-mark">✓ Read</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dash-card">
                    <div class="card-head">
                        <h2>📦 Recent Orders</h2>
                        <a href="orders.php" class="view-all">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p class="empty-state">No orders yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td style="font-weight:800;color:var(--text-dark);">#<?= $o['id'] ?></td>
                                        <td><?= htmlspecialchars($o['firstname'].' '.$o['lastname']) ?></td>
                                        <td style="font-weight:800;">₱<?= number_format($o['total_amount'],2) ?></td>
                                        <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Seller Applications -->
                <div class="dash-card">
                    <div class="card-head">
                        <h2>📝 Pending Seller Applications</h2>
                        <a href="manage_sellers.php" class="view-all">Manage →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_apps)): ?>
                            <p class="empty-state">No pending applications ✓</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead><tr><th>Applicant</th><th>Business</th><th>Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recent_apps as $a): ?>
                                    <tr>
                                        <td style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($a['firstname'].' '.$a['lastname']) ?></td>
                                        <td><?= htmlspecialchars($a['business_name']) ?></td>
                                        <td><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
                                        <td><span class="badge badge-pending">Pending</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Unverified Users -->
                <div class="dash-card">
                    <div class="card-head">
                        <h2>✉️ Unverified Users</h2>
                        <a href="manage_users.php" class="view-all">Manage →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($unverified_users)): ?>
                            <p class="empty-state">All users are verified ✓</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php foreach ($unverified_users as $u): ?>
                                    <tr>
                                        <td style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($u['firstname'].' '.$u['lastname']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><span class="role-badge role-<?= strtolower($u['user_role']) ?>"><?= $u['user_role'] ?></span></td>
                                        <td>
                                            <form method="post" action="send_verification.php" style="display:contents;">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="email" value="<?= $u['email'] ?>">
                                                <button type="submit" class="btn-resend">Resend</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Users (full width) -->
                <div class="dash-card full">
                    <div class="card-head">
                        <h2>👥 Recent Users</h2>
                        <a href="manage_users.php" class="view-all">View All →</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_users)): ?>
                            <p class="empty-state">No users yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($u['firstname'].' '.$u['lastname']) ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><span class="role-badge role-<?= strtolower($u['user_role']) ?>"><?= $u['user_role'] ?></span></td>
                                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /dash-grid -->

        </div><!-- /content -->

        <!-- Footer -->
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

</body>
</html>
