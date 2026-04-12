<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdminOrSuperadmin()) redirect('../login.php');

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
$is_superadmin_viewer = !empty($admin['superadmin_id']);

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_verify'])) {
        $userId = (int)($_POST['user_id'] ?? 0);
        $verify = (int)($_POST['verify'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT a.id AS admin_id, sa.id AS superadmin_id
            FROM users u
            LEFT JOIN admins a ON a.user_id = u.id
            LEFT JOIN superadmins sa ON sa.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if ($userId > 0 && ($is_superadmin_viewer || empty($target['superadmin_id']))) {
            $pdo->prepare("UPDATE users SET email_verified = ? WHERE id = ?")->execute([$verify, $userId]);
            $flash = ['type' => 'success', 'text' => 'User verification updated.'];
        } else {
            $flash = ['type' => 'error', 'text' => 'Superadmin accounts cannot be modified here.'];
        }
    }

    if (isset($_POST['delete_user'])) {
        $userId = (int)($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM admins WHERE user_id = ?) AS admin_count,
                (SELECT COUNT(*) FROM superadmins WHERE user_id = ?) AS superadmin_count
        ");
        $stmt->execute([$userId, $userId]);
        $targetFlags = $stmt->fetch();

        $isTargetAdmin = (int)$targetFlags['admin_count'] > 0;
        $isTargetSuperadmin = (int)$targetFlags['superadmin_count'] > 0;

        if (
            $userId > 0 &&
            !$isTargetAdmin &&
            !$isTargetSuperadmin &&
            $userId !== (int)$_SESSION['user_id']
        ) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $flash = ['type' => 'success', 'text' => 'User deleted successfully.'];
        } elseif ($isTargetSuperadmin) {
            $flash = ['type' => 'error', 'text' => 'Superadmin accounts cannot be viewed or modified here.'];
        } else {
            $flash = ['type' => 'error', 'text' => 'Admin accounts cannot be deleted here.'];
        }
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = "
    SELECT u.*,
           sa.id AS superadmin_id,
           CASE
               WHEN sa.id IS NOT NULL THEN 'Superadmin'
               WHEN a.id IS NOT NULL THEN 'Admin'
               WHEN s.id IS NOT NULL THEN 'Seller'
               WHEN c.id IS NOT NULL THEN 'Customer'
               ELSE 'User'
           END AS user_role
    FROM users u
    LEFT JOIN admins a ON a.user_id = u.id
    LEFT JOIN superadmins sa ON sa.user_id = u.id
    LEFT JOIN sellers s ON s.user_id = u.id
    LEFT JOIN customers c ON c.user_id = u.id
    WHERE 1 = 1
";
$params = [];

if (!$is_superadmin_viewer) {
    $sql .= " AND sa.id IS NULL ";
}

if ($search !== '') {
    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($role !== '') {
    if ($role === 'Admin') {
        $sql .= " AND a.id IS NOT NULL ";
    } elseif ($role === 'Seller') {
        $sql .= " AND s.id IS NOT NULL ";
    } elseif ($role === 'Customer') {
        $sql .= " AND c.id IS NOT NULL ";
    } elseif ($role === 'User') {
        $sql .= " AND a.id IS NULL AND s.id IS NULL AND c.id IS NULL ";
    }
}

if ($status === 'verified') {
    $sql .= " AND u.email_verified = 1 ";
} elseif ($status === 'unverified') {
    $sql .= " AND u.email_verified = 0 ";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int)$stmt->fetchColumn();

$verified_count = 0;
foreach ($users as $user) {
    if ((int)$user['email_verified'] === 1) $verified_count++;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_users.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='*'">
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

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Manage Users</h1>
                <p>Review roles, verification, and account access.</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;"><?= $unread_count ?> unread</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['text']) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🔍</div><div><div class="stat-val"><?= number_format(count($users)) ?></div><div class="stat-lbl">Filtered Users</div></div></div>
                <div class="stat-card"><div class="stat-icon">✔️</div><div><div class="stat-val"><?= number_format($verified_count) ?></div><div class="stat-lbl">Verified</div></div></div>
                <div class="stat-card warn"><div class="stat-icon">✉️</div><div><div class="stat-val"><?= number_format(count($users) - $verified_count) ?></div><div class="stat-lbl">Unverified</div></div></div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>User Filters</h2></div>
                <div class="card-body">
                    <form method="get" class="filters-grid">
                        <div class="field">
                            <label for="search">Search</label>
                            <input id="search" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, username, email">
                        </div>
                        <div class="field">
                            <label for="role">Role</label>
                            <select id="role" name="role">
                                <option value="">All roles</option>
                                <?php foreach (['Admin', 'Seller', 'Customer', 'User'] as $roleOption): ?>
                                    <option value="<?= $roleOption ?>" <?= $role === $roleOption ? 'selected' : '' ?>><?= $roleOption ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All statuses</option>
                                <option value="verified" <?= $status === 'verified' ? 'selected' : '' ?>>Verified</option>
                                <option value="unverified" <?= $status === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dash-card">
                <div class="card-head"><h2>User Directory</h2></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">No users matched the current filters.</div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Verification</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="stack">
                                                <div class="user-name"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></div>
                                                <div class="muted">@<?= htmlspecialchars($user['username']) ?></div>
                                                <div class="muted"><?= htmlspecialchars($user['email']) ?></div>
                                            </div>
                                        </td>
                                        <td><span class="role-badge role-<?= strtolower($user['user_role']) ?>"><?= htmlspecialchars($user['user_role']) ?></span></td>
                                        <td><span class="status-badge <?= (int)$user['email_verified'] === 1 ? 'status-verified' : 'status-unverified' ?>"><?= (int)$user['email_verified'] === 1 ? 'Verified' : 'Unverified' ?></span></td>
                                        <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($user['created_at']))) ?></td>
                                        <td>
                                            <div class="actions">
                                                <form method="post">
                                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                    <input type="hidden" name="verify" value="<?= (int)$user['email_verified'] === 1 ? '0' : '1' ?>">
                                                    <button type="submit" name="toggle_verify" class="btn-secondary"><?= (int)$user['email_verified'] === 1 ? 'Mark Unverified' : 'Verify User' ?></button>
                                                </form>
                                                <?php if ($user['user_role'] !== 'Admin' && (int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                                    <form method="post" onsubmit="return confirm('Delete this user permanently?');">
                                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                                        <button type="submit" name="delete_user" class="btn-danger">Delete User</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
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
                <div class="footer-copy">Copyright &copy; 2025 <span>Beauty Mart</span>. Admin panel.</div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
