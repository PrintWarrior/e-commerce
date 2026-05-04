<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');
$stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ? UNION SELECT id FROM superadmins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
if ($stmt->rowCount() == 0) redirect('../login.php');

$success = '';
$error = '';

// Handle sending verification email
if (isset($_POST['send_verification'])) {
    $user_id = (int)$_POST['user_id'];
    $email = trim($_POST['email']);
    
    // Get user details
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN a.id IS NOT NULL THEN 'Admin'
                   WHEN s.id IS NOT NULL THEN 'Seller'
                   WHEN c.id IS NOT NULL THEN 'Customer'
                   ELSE 'User'
               END as user_role
        FROM users u
        LEFT JOIN admins a ON u.id = a.user_id
        LEFT JOIN sellers s ON u.id = s.user_id
        LEFT JOIN customers c ON u.id = c.user_id
        WHERE u.id = ? AND u.email = ?
    ");
    $stmt->execute([$user_id, $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "User not found.";
    } elseif ($user['email_verified'] == 1) {
        $error = "User's email is already verified.";
    } else {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$token, $user_id]);
        
        // Send verification email
        if (sendVerificationEmail($email, $token)) {
            $success = "Verification email sent to {$user['firstname']} {$user['lastname']} ({$email}).";
            
            // Log the action
            $log_message = "Verification email resent to {$user['username']} ({$email}) by admin";
            createNotification($_SESSION['user_id'], $log_message, 'system');
        } else {
            $error = "Failed to send verification email.";
        }
    }
}

// Handle mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $_SESSION['user_id']]);
    $success = "Notification marked as read.";
    redirect('notifications.php');
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $success = "All notifications marked as read.";
    redirect('notifications.php');
}

// Handle delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notif_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $_SESSION['user_id']]);
    $success = "Notification deleted.";
    redirect('notifications.php');
}

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build notifications query
$query = "SELECT n.*, nt.code AS type_code, nt.label AS type_label, u.username FROM notifications n LEFT JOIN notification_types nt ON n.notification_type_id = nt.id JOIN users u ON n.user_id = u.id WHERE n.user_id = ?";
$params = [$_SESSION['user_id']];

if ($filter == 'unread') {
    $query .= " AND n.is_read = 0";
} elseif ($filter == 'read') {
    $query .= " AND n.is_read = 1";
}

if ($search) {
    $query .= " AND (n.message LIKE ? OR nt.code LIKE ? OR nt.label LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Get counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 1");
$stmt->execute([$_SESSION['user_id']]);
$read_count = $stmt->fetchColumn();

// Get unverified users for verification section
$stmt = $pdo->prepare("
    SELECT u.*, 
           CASE 
               WHEN a.id IS NOT NULL THEN 'Admin'
               WHEN s.id IS NOT NULL THEN 'Seller'
               WHEN c.id IS NOT NULL THEN 'Customer'
               ELSE 'User'
           END as user_role
    FROM users u
    LEFT JOIN admins a ON u.id = a.user_id
    LEFT JOIN sellers s ON u.id = s.user_id
    LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.email_verified = 0
    ORDER BY u.created_at DESC
");
$stmt->execute();
$unverified_users = $stmt->fetchAll();

// Get admin data for header
$stmt = $pdo->prepare("
    SELECT u.*,
           a.id AS admin_id,
           sa.id AS superadmin_id
    FROM users u
    LEFT JOIN admins a ON u.id = a.user_id
    LEFT JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../css/admin_noti.css">
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
 
     <!-- Sidebar -->
     <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo">
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
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="create_users.php">➕ Create User</a>
            <a href="manage_users.php">👥 Manage Users</a>
            <a href="manage_sellers.php">🏪 Manage Sellers</a>
            <a href="products.php">🛍️ Products</a>
            <a href="orders.php">📦 Orders</a>
            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php">🗑️ Deletion Requests</a>
            <a href="notifications.php" class="active">🔔 Notifications</a>
            <a href="system_logs.php" class="<?= $current_page==='system_logs.php' ? 'active':'' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>
            
            <div class="nav-lbl">Account</div>
            <a href="profile.php">👤 My Profile</a>
            <a href="about.php">📝 About Menu</a>
            <a href="../logout.php" class="logout">🚪 Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Notifications</h1>
                <p>Manage system notifications and send verification emails</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
            </div>
        </div>

        <div class="content">
            <div class="notifications-page">
                <?php if ($success): ?>
                    <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <span class="stat-label">Total Notifications</span>
                        <span class="stat-number"><?= $unread_count + $read_count ?></span>
                    </div>
                    <div class="stat-card unread">
                        <span class="stat-label">Unread</span>
                        <span class="stat-number"><?= $unread_count ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Read</span>
                        <span class="stat-number"><?= $read_count ?></span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <div class="filter-buttons">
                        <a href="?filter=all" class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>">All</a>
                        <a href="?filter=unread" class="filter-btn <?= $filter == 'unread' ? 'active' : '' ?>">Unread</a>
                        <a href="?filter=read" class="filter-btn <?= $filter == 'read' ? 'active' : '' ?>">Read</a>
                    </div>
                    <div class="search-form">
                        <form method="get" style="display: flex; gap: 10px;">
                            <input type="hidden" name="filter" value="<?= $filter ?>">
                            <input type="text" name="search" placeholder="Search notifications..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit">Search</button>
                        </form>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <p>📭 No notifications found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                                <div class="notification-icon">
                                    <?= htmlspecialchars(getNotificationMeta(getNotificationCode($notif))['icon']) ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                                    <div class="notification-time"><?= date('F j, Y g:i A', strtotime($notif['created_at'])) ?></div>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!$notif['is_read']): ?>
                                        <a href="?mark_read=<?= $notif['id'] ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="btn-icon" title="Mark as read">✓</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $notif['id'] ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" class="btn-icon" title="Delete" onclick="return confirm('Delete this notification?')">🗑️</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($unread_count > 0): ?>
                    <form method="post" style="text-align: right; margin-bottom: 30px;">
                        <button type="submit" name="mark_all_read" class="btn-mark-all">✓ Mark all as read</button>
                    </form>
                <?php endif; ?>

                <!-- Send Verification Email Section -->
                <div class="verification-section">
                    <div class="section-header">
                        <span>✉️</span>
                        <h2>Send Verification Email</h2>
                    </div>
                    <?php if (empty($unverified_users)): ?>
                        <div class="empty-state">
                            <p>✅ All users are verified!</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unverified_users as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="role-badge role-<?= strtolower($user['user_role']) ?>"><?= $user['user_role'] ?></span></td>
                                        <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <form method="post" style="display: contents;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="email" value="<?= $user['email'] ?>">
                                                <button type="submit" name="send_verification" class="btn-resend">Send Verification</button>
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
                <p class="footer-copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
                <div class="footer-socials">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Twitter">t</a>
                    <a href="#" title="Website">🌐</a>
                    <a href="#" title="LinkedIn">in</a>
                </div>
            </div>
        </footer>
    </div>
</div>

</body>
</html>
