<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperadmin()) redirect('../login.php');

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
            $log_message = "Verification email resent to {$user['username']} ({$email}) by superadmin";
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

// Get superadmin data for header
$stmt = $pdo->prepare("
    SELECT u.*,
           sa.id AS superadmin_id
    FROM users u
    JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$superadmin = $stmt->fetch();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Superadmin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --pink-light: #fce8ee;
            --pink-mid: #f9d0dc;
            --pink-accent: #e8728e;
            --pink-dark: #c75473;
            --text-dark: #2e2e2e;
            --text-mid: #555;
            --text-muted: #888;
            --radius: 14px;
            --shadow: 0 4px 18px rgba(200, 80, 110, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f2f6;
            color: var(--text-dark);
        }

        /* Shell Grid Layout */
        .shell {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            background: #fff;
            border-right: 1.5px solid var(--pink-mid);
            padding: 30px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1.5px solid var(--pink-mid);
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .brand-text {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }

        .brand-text span:first-child {
            color: var(--pink-dark);
        }

        .brand-text span:last-child {
            color: var(--pink-accent);
        }

        .brand-sub {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--pink-light);
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 30px;
        }

        .chip-av {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .chip-av img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .chip-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-dark);
        }

        .chip-role {
            font-size: 12px;
            color: var(--text-muted);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .nav-lbl {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            padding: 11px 15px;
            color: var(--text-mid);
            text-decoration: none;
            border-radius: 9px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover {
            background-color: var(--pink-light);
            color: var(--pink-dark);
            border-left-color: var(--pink-accent);
        }

        .sidebar-nav a.active {
            background-color: var(--pink-accent);
            color: #fff;
            border-left-color: var(--pink-dark);
        }

        .sidebar-nav a.logout {
            margin-top: auto;
            color: #d32f2f;
        }

        .sidebar-nav a.logout:hover {
            background-color: #ffebee;
            border-left-color: #d32f2f;
        }

        /* Main Content Area */
        .main {
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: linear-gradient(135deg, var(--pink-dark) 0%, var(--pink-accent) 100%);
            color: #fff;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .topbar-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 8px;
        }

        .topbar-left p {
            font-size: 14px;
            opacity: 0.9;
        }

        .topbar-right {
            text-align: right;
        }

        .topbar-date {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Content Area */
        .content {
            flex: 1;
            padding: 40px;
        }

        .notifications-page {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            font-weight: 600;
            border: 1.5px solid;
        }

        .alert-success {
            background-color: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }

        .alert-error {
            background-color: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #fff;
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--pink-accent);
            box-shadow: 0 6px 24px rgba(200, 80, 110, 0.2);
        }

        .stat-card.unread {
            background: linear-gradient(135deg, rgba(232, 114, 142, 0.08), rgba(199, 84, 115, 0.08));
            border-color: var(--pink-accent);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--pink-dark);
        }

        /* Filters Bar */
        .filters-bar {
            background: #fff;
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
        }

        .filter-buttons {
            display: flex;
            gap: 12px;
        }

        .filter-btn {
            padding: 9px 18px;
            border: 1.5px solid var(--pink-mid);
            background: #fff;
            border-radius: 20px;
            color: var(--text-mid);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover {
            background-color: var(--pink-light);
            border-color: var(--pink-accent);
            color: var(--pink-dark);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            color: #fff;
            border-color: var(--pink-dark);
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form form {
            display: flex;
            gap: 10px;
        }

        .search-form input[type="text"] {
            padding: 10px 16px;
            border: 1.5px solid var(--pink-mid);
            border-radius: 9px;
            font-family: 'Nunito', sans-serif;
            color: var(--text-dark);
            width: 250px;
            transition: all 0.3s ease;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: var(--pink-accent);
            box-shadow: 0 0 0 3px rgba(232, 114, 142, 0.1);
        }

        .search-form button {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            color: #fff;
            border: none;
            border-radius: 9px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-form button:hover {
            box-shadow: 0 4px 12px rgba(200, 80, 110, 0.3);
        }

        /* Notifications List */
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 30px;
        }

        .notification-item {
            background: #fff;
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            gap: 16px;
            align-items: flex-start;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .notification-item:hover {
            border-color: var(--pink-accent);
            box-shadow: 0 6px 24px rgba(200, 80, 110, 0.15);
        }

        .notification-item.unread {
            background: linear-gradient(135deg, rgba(232, 114, 142, 0.04), rgba(252, 232, 238, 0.4));
            border-color: var(--pink-accent);
        }

        .notification-icon {
            font-size: 24px;
            flex-shrink: 0;
            min-width: 30px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 12px;
            color: var(--text-muted);
        }

        .notification-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border: 1.5px solid var(--pink-mid);
            background: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: var(--pink-light);
            border-color: var(--pink-accent);
            color: var(--pink-dark);
        }

        .empty-state {
            background: var(--pink-light);
            border: 1.5px dashed var(--pink-accent);
            border-radius: var(--radius);
            padding: 40px;
            text-align: center;
            color: var(--text-mid);
            font-size: 16px;
        }

        /* Mark All As Read Button */
        .btn-mark-all {
            padding: 11px 24px;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            color: #fff;
            border: none;
            border-radius: 9px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-mark-all:hover {
            box-shadow: 0 4px 12px rgba(200, 80, 110, 0.3);
        }

        /* Verification Section */
        .verification-section {
            background: #fff;
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .section-header span {
            font-size: 28px;
        }

        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--text-dark);
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background-color: var(--pink-light);
            border-bottom: 1.5px solid var(--pink-mid);
        }

        .data-table th {
            padding: 15px 16px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--pink-mid);
        }

        .data-table tbody tr:hover {
            background-color: var(--pink-light);
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .role-badge.role-customer {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role-badge.role-admin {
            background-color: #fef3c7;
            color: #92400e;
        }

        .role-badge.role-seller {
            background-color: #d1fae5;
            color: #065f46;
        }

        .btn-resend {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-resend:hover {
            box-shadow: 0 4px 12px rgba(200, 80, 110, 0.3);
        }

        /* Footer */
        .admin-footer {
            background: #fff;
            border-top: 1.5px solid var(--pink-mid);
            padding: 25px 40px;
            margin-top: 40px;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-copy {
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-copy span {
            font-weight: 700;
            color: var(--pink-dark);
        }

        .footer-socials {
            display: flex;
            gap: 12px;
        }

        .footer-socials a {
            width: 36px;
            height: 36px;
            background: var(--pink-light);
            color: var(--pink-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .footer-socials a:hover {
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            color: #fff;
        }

        /* Responsive Design */
        @media (max-width: 1000px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1.5px solid var(--pink-mid);
                padding: 20px;
            }

            .sidebar-nav {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 10px;
            }

            .nav-lbl {
                display: none;
            }

            .topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .topbar-right {
                text-align: center;
            }

            .content {
                padding: 20px;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form input[type="text"] {
                width: 100%;
            }

            .footer-inner {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                width: 100%;
                flex-wrap: wrap;
            }

            .notification-item {
                flex-direction: column;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }

            .btn-resend {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="shell">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo">
            </div>
            <div>
                <div class="brand-text"><span>Beauty</span><span>Mart</span></div>
                <div class="brand-sub">Superadmin Panel</div>
            </div>
        </div>
        <div class="admin-chip">
            <div class="chip-av">
                <?php if (!empty($superadmin['profile_pic']) && file_exists("../uploads/profile_images/" . $superadmin['profile_pic'])): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($superadmin['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($superadmin['firstname'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($superadmin['firstname'] . ' ' . $superadmin['lastname']) ?></div>
                <span class="chip-role">Superadmin</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php">Dashboard</a>
            <a href="create_users.php">Create Users</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <div class="nav-lbl">Management</div>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php" class="active">Notifications</a>
            <div class="nav-lbl">Account</div>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Notifications</h1>
                <p>Manage system notifications and send verification emails with superadmin rights</p>
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
