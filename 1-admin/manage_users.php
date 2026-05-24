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
$viewer_label = !empty($admin['superadmin_id']) ? 'Superadmin' : 'Administrator';

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $emailVerified = (int) ($_POST['email_verified'] ?? 0);
        $selectedRole = trim((string) ($_POST['user_role'] ?? ''));
        $businessName = trim((string) ($_POST['business_name'] ?? ''));
        $businessAddress = trim((string) ($_POST['business_address'] ?? ''));
        $businessPhone = trim((string) ($_POST['business_phone'] ?? ''));
        $businessTaxId = trim((string) ($_POST['business_tax_id'] ?? ''));
        $roleRanks = ['User' => 0, 'Customer' => 1, 'Seller' => 2, 'Admin' => 3, 'Superadmin' => 4];

        $errors = [];
        if ($userId <= 0) $errors[] = 'Invalid user selected.';
        if ($firstname === '') $errors[] = 'First name is required.';
        if ($lastname === '') $errors[] = 'Last name is required.';
        if ($username === '') $errors[] = 'Username is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!in_array($emailVerified, [0, 1], true)) $errors[] = 'Invalid email verification value.';
        if (!isset($roleRanks[$selectedRole]) || $selectedRole === 'Superadmin') $errors[] = 'Invalid role selected.';

        $stmt = $pdo->prepare("
            SELECT u.username,
                   a.id AS admin_id,
                   sa.id AS superadmin_id,
                   c.id AS customer_id,
                   s.id AS seller_id
            FROM users u
            LEFT JOIN admins a ON a.user_id = u.id
            LEFT JOIN superadmins sa ON sa.user_id = u.id
            LEFT JOIN customers c ON c.user_id = u.id
            LEFT JOIN sellers s ON s.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $targetRoleData = $stmt->fetch();
        $currentRole = 'User';
        if ($targetRoleData) {
            if (!empty($targetRoleData['superadmin_id'])) {
                $currentRole = 'Superadmin';
            } elseif (!empty($targetRoleData['admin_id'])) {
                $currentRole = 'Admin';
            } elseif (!empty($targetRoleData['seller_id'])) {
                $currentRole = 'Seller';
            } elseif (!empty($targetRoleData['customer_id'])) {
                $currentRole = 'Customer';
            }
        }
        $isSellerTarget = $selectedRole === 'Seller' || !empty($targetRoleData['seller_id']);

        if (!$targetRoleData) {
            $errors[] = 'User not found.';
        } elseif ($currentRole === 'Superadmin') {
            $errors[] = 'Superadmin accounts cannot be changed here.';
        } elseif ($userId === (int) $_SESSION['user_id'] && $selectedRole !== $currentRole) {
            $errors[] = 'You cannot change your own role here.';
        } elseif (isset($roleRanks[$selectedRole]) && $roleRanks[$selectedRole] < $roleRanks[$currentRole]) {
            $errors[] = 'Roles can only be promoted here, not downgraded.';
        }

        if ($selectedRole === 'Seller' || $currentRole === 'Seller') {
            if ($businessName === '') $errors[] = 'Business name is required for seller accounts.';
            if ($businessAddress === '') $errors[] = 'Business address is required for seller accounts.';
            if ($businessPhone === '') $errors[] = 'Business phone is required for seller accounts.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?");
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists on another account.';
            }
        }

        if (empty($errors)) {
            $pdo->beginTransaction();

            try {
                $verificationToken = $emailVerified === 1 ? null : bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET firstname = ?, lastname = ?, username = ?, email = ?, email_verified = ?, verification_token = ?
                    WHERE id = ?
                ");
                $stmt->execute([$firstname, $lastname, $username, $email, $emailVerified, $verificationToken, $userId]);

                if ($isSellerTarget) {
                    if (!empty($targetRoleData['seller_id'])) {
                        $pdo->prepare("
                            UPDATE sellers
                            SET business_name = ?, business_address = ?, phone = ?, tax_id = ?
                            WHERE user_id = ?
                        ")->execute([
                            $businessName,
                            $businessAddress,
                            $businessPhone,
                            $businessTaxId !== '' ? $businessTaxId : null,
                            $userId
                        ]);
                    } elseif ($selectedRole === 'Seller') {
                        $pdo->prepare("
                            INSERT INTO sellers (user_id, business_name, business_address, phone, tax_id, approved_by, approved_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ")->execute([
                            $userId,
                            $businessName,
                            $businessAddress,
                            $businessPhone,
                            $businessTaxId !== '' ? $businessTaxId : null,
                            (int) $_SESSION['user_id']
                        ]);
                    }
                }

                if ($selectedRole === 'Customer' && empty($targetRoleData['customer_id'])) {
                    $pdo->prepare("INSERT INTO customers (user_id, phone) VALUES (?, ?)")->execute([$userId, '']);
                }

                if ($selectedRole === 'Admin' && empty($targetRoleData['admin_id'])) {
                    $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")->execute([$userId]);
                }

                logSystemEvent(
                    'admin_user_updated',
                    'users',
                    $userId,
                    "{$viewer_label} updated account #{$userId} ({$username}) from {$currentRole} to {$selectedRole}.",
                    (int) $_SESSION['user_id']
                );

                $pdo->commit();
                $flash = ['type' => 'success', 'text' => 'User details updated successfully.'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = ['type' => 'error', 'text' => 'Failed to update user: ' . $e->getMessage()];
            }
        } else {
            $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
        }
    }

    if (isset($_POST['toggle_verify'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $verify = (int) ($_POST['verify'] ?? 0);

        if ($userId > 0 && in_array($verify, [0, 1], true)) {
            $verificationToken = $verify === 1 ? null : bin2hex(random_bytes(32));
            $pdo->prepare("UPDATE users SET email_verified = ?, verification_token = ? WHERE id = ?")
                ->execute([$verify, $verificationToken, $userId]);
            $flash = ['type' => 'success', 'text' => 'User verification updated.'];
        } else {
            $flash = ['type' => 'error', 'text' => 'Invalid verification update.'];
        }
    }

    if (isset($_POST['promote_admin'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT u.username, a.id AS admin_id, sa.id AS superadmin_id
            FROM users u
            LEFT JOIN admins a ON a.user_id = u.id
            LEFT JOIN superadmins sa ON sa.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if (!$target) {
            $flash = ['type' => 'error', 'text' => 'User not found.'];
        } elseif (!empty($target['superadmin_id'])) {
            $flash = ['type' => 'error', 'text' => 'Superadmin accounts cannot be changed here.'];
        } elseif (!empty($target['admin_id'])) {
            $flash = ['type' => 'error', 'text' => 'This user is already an admin.'];
        } else {
            $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")->execute([$userId]);

            logSystemEvent(
                'admin_user_promoted',
                'users',
                $userId,
                "{$viewer_label} promoted {$target['username']} to admin.",
                (int) $_SESSION['user_id']
            );

            $flash = ['type' => 'success', 'text' => "{$target['username']} was promoted to admin."];
        }
    }

    if (isset($_POST['reset_user_password'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $stmt = $pdo->prepare("
            SELECT u.username, sa.id AS superadmin_id
            FROM users u
            LEFT JOIN superadmins sa ON sa.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if (!$target) {
            $flash = ['type' => 'error', 'text' => 'User not found.'];
        } elseif ($userId === (int) $_SESSION['user_id']) {
            $flash = ['type' => 'error', 'text' => 'Use your profile page to change your own password.'];
        } elseif (!empty($target['superadmin_id'])) {
            $flash = ['type' => 'error', 'text' => 'Superadmin passwords cannot be reset here.'];
        } elseif (strlen($newPassword) < 6) {
            $flash = ['type' => 'error', 'text' => 'New password must be at least 6 characters.'];
        } elseif ($newPassword !== $confirmPassword) {
            $flash = ['type' => 'error', 'text' => 'New passwords do not match.'];
        } else {
            $pdo->beginTransaction();

            try {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);

                logSystemEvent(
                    'admin_password_reset',
                    'users',
                    $userId,
                    "{$viewer_label} reset the password for {$target['username']}.",
                    (int) $_SESSION['user_id']
                );

                $pdo->commit();
                $flash = ['type' => 'success', 'text' => "Password reset for {$target['username']}."];
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = ['type' => 'error', 'text' => 'Failed to reset password: ' . $e->getMessage()];
            }
        }
    }

    if (isset($_POST['delete_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT u.username, sa.id AS superadmin_id
            FROM users u
            LEFT JOIN superadmins sa ON sa.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if (!$target) {
            $flash = ['type' => 'error', 'text' => 'User not found.'];
        } elseif ($userId === (int) $_SESSION['user_id']) {
            $flash = ['type' => 'error', 'text' => 'You cannot delete your own account here.'];
        } elseif (!empty($target['superadmin_id'])) {
            $flash = ['type' => 'error', 'text' => 'Superadmin accounts cannot be deleted.'];
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

            logSystemEvent(
                'admin_user_deleted',
                'users',
                $userId,
                "{$viewer_label} deleted account {$target['username']}.",
                (int) $_SESSION['user_id']
            );

            $flash = ['type' => 'success', 'text' => 'User deleted successfully.'];
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$role = trim((string) ($_GET['role'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$selectedViewId = (int) ($_GET['view'] ?? 0);
$selectedEditId = (int) ($_GET['edit'] ?? 0);

$sql = "
    SELECT u.*,
           a.id AS admin_id,
           sa.id AS superadmin_id,
           c.id AS customer_id,
           s.id AS seller_id,
           s.business_name,
           s.business_address,
           s.phone AS seller_phone,
           s.tax_id,
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

if ($search !== '') {
    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($role !== '') {
    if ($role === 'Superadmin') {
        $sql .= " AND sa.id IS NOT NULL ";
    } elseif ($role === 'Admin') {
        $sql .= " AND a.id IS NOT NULL ";
    } elseif ($role === 'Seller') {
        $sql .= " AND s.id IS NOT NULL ";
    } elseif ($role === 'Customer') {
        $sql .= " AND c.id IS NOT NULL ";
    } elseif ($role === 'User') {
        $sql .= " AND sa.id IS NULL AND a.id IS NULL AND s.id IS NULL AND c.id IS NULL ";
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
$pagination = paginateArray($users, $page, 5);
$pagedUsers = $pagination['items'];
$baseQuery = [
    'search' => $search,
    'role' => $role,
    'status' => $status,
    'page' => $pagination['current_page'],
];

$viewUser = null;
$editUser = null;
foreach ($users as $user) {
    if ($selectedViewId > 0 && (int) $user['id'] === $selectedViewId) $viewUser = $user;
    if ($selectedEditId > 0 && (int) $user['id'] === $selectedEditId) $editUser = $user;
}
if (!$viewUser && !empty($pagedUsers)) $viewUser = $pagedUsers[0];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $stmt->fetchColumn();

$verified_count = 0;
$superadmin_count = 0;
foreach ($users as $user) {
    if ((int) $user['email_verified'] === 1) $verified_count++;
    if ($user['user_role'] === 'Superadmin') $superadmin_count++;
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
    <link rel="stylesheet" href="../css/admin_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
<div class="shell">
    <input type="checkbox" id="admin-menu-toggle" class="admin-menu-toggle">
    <label for="admin-menu-toggle" class="admin-hamburger">
        <span></span><span></span><span></span>
    </label>

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
                <span class="chip-role"><?= htmlspecialchars($viewer_label) ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <span class="ni">📊</span> Dashboard
            </a>
            <a href="create_users.php" class="<?= $current_page==='create_users.php' ? 'active':'' ?>">
                <span class="ni">➕</span> Create User
            </a>
            <a href="manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
                <span class="ni">👥</span> Manage Users
            </a>
            <a href="manage_sellers.php" class="<?= $current_page === 'manage_sellers.php' ? 'active' : '' ?>">
                <span class="ni">🏪</span> Manage Sellers
                <?php if ($pending_apps > 0): ?><span class="nbadge"><?= $pending_apps ?></span><?php endif; ?>
            </a>
            <a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">
                <span class="ni">🛍️</span> Products
            </a>
            <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : '' ?>">
                <span class="ni">📦</span> Orders
            </a>

            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page === 'manage_deletions.php' ? 'active' : '' ?>">
                <span class="ni">🗑️</span> Deletion Requests
            </a>
            <a href="notifications.php" class="<?= $current_page === 'notifications.php' ? 'active' : '' ?>">
                <span class="ni">🔔</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>
            <a href="system_logs.php" class="<?= $current_page === 'system_logs.php' ? 'active' : '' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>

            <div class="nav-lbl">Account</div>
            <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
                <span class="ni">👤</span> My Profile
            </a>
            <a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">
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
                <p>Review users, update account details, and keep the superadmin protected from deletion.</p>
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

            <section class="workspace-hero">
                <div class="hero-card">
                    <div class="hero-kicker">User Workspace</div>
                    <h2>Manage every account from one admin surface.</h2>
                    <p class="hero-copy">This page is now focused on discovery, review, and profile maintenance so you can work through accounts cleanly while keeping account creation in a separate dedicated screen.</p>
                    <div class="hero-pills">
                        <span class="hero-pill">Live filters for search, role, and verification</span>
                        <span class="hero-pill">Protected superadmin record</span>
                        <span class="hero-pill">Seller profile fields in the same editor</span>
                    </div>
                    <div class="hero-actions">
                        <a href="create_users.php" class="btn-primary">Create Account</a>
                        <?php if ($viewUser): ?>
                            <a href="#user-detail-panel" class="btn-secondary">Review Selected User</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-sidecard">
                    <h3>Control Notes</h3>
                    <p>Use the right-side workspace to create users, inspect protected accounts, and update seller business details without enabling suspend or ban actions.</p>
                    <div class="mini-stats">
                        <div class="mini-stat">
                            <strong><?= number_format(count($users)) ?></strong>
                            <span>Visible Accounts</span>
                        </div>
                        <div class="mini-stat">
                            <strong><?= number_format($verified_count) ?></strong>
                            <span>Verified</span>
                        </div>
                        <div class="mini-stat">
                            <strong><?= number_format($superadmin_count) ?></strong>
                            <span>Protected</span>
                        </div>
                        <div class="mini-stat">
                            <strong><?= number_format($pending_apps) ?></strong>
                            <span>Seller Requests</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🔍</div><div><div class="stat-val"><?= number_format(count($users)) ?></div><div class="stat-lbl">Filtered Users</div></div></div>
                <div class="stat-card"><div class="stat-icon">✅</div><div><div class="stat-val"><?= number_format($verified_count) ?></div><div class="stat-lbl">Verified</div></div></div>
                <div class="stat-card warn"><div class="stat-icon">✉️</div><div><div class="stat-val"><?= number_format(count($users) - $verified_count) ?></div><div class="stat-lbl">Unverified</div></div></div>
                <div class="stat-card"><div class="stat-icon">👑</div><div><div class="stat-val"><?= number_format($superadmin_count) ?></div><div class="stat-lbl">Superadmins</div></div></div>
            </div>

            <div class="panel-grid">
                <div class="stack-grid">
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
                                        <?php foreach (['Superadmin', 'Admin', 'Seller', 'Customer', 'User'] as $roleOption): ?>
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
                            <div class="filter-state">
                                <?php if ($search !== ''): ?><span class="filter-chip">Search: <?= htmlspecialchars($search) ?></span><?php endif; ?>
                                <?php if ($role !== ''): ?><span class="filter-chip">Role: <?= htmlspecialchars($role) ?></span><?php endif; ?>
                                <?php if ($status !== ''): ?><span class="filter-chip">Status: <?= htmlspecialchars(ucfirst($status)) ?></span><?php endif; ?>
                                <?php if ($search === '' && $role === '' && $status === ''): ?><span class="filter-chip">Showing all accounts</span><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div class="card-head">
                            <div>
                                <div class="section-label">Directory</div>
                                <h2>User Directory</h2>
                                <div class="section-copy">Select a row to inspect or edit the account.</div>
                            </div>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <?php if (empty($users)): ?>
                                <div class="empty-state">No users matched the current filters.</div>
                            <?php else: ?>
                                <div class="directory-wrap">
                                    <div class="table-toolbar">
                                        <div class="toolbar-copy">
                                            <strong>Showing <?= number_format($pagination['from']) ?>-<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total_items']) ?> accounts</strong>
                                            <span>Pick a record to load it into the detail and edit workspace.</span>
                                        </div>
                                        <div class="toolbar-metrics">
                                            <span class="toolbar-pill"><?= number_format($verified_count) ?> verified</span>
                                            <span class="toolbar-pill"><?= number_format(count($users) - $verified_count) ?> unverified</span>
                                            <span class="toolbar-pill"><?= number_format($superadmin_count) ?> protected</span>
                                        </div>
                                    </div>
                                    <div class="directory-table-scroll">
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Role</th>
                                                    <th>Verification</th>
                                                    <th>Status</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pagedUsers as $user): ?>
                                                    <?php $isProtectedSuperadmin = $user['user_role'] === 'Superadmin'; ?>
                                                    <tr class="<?= ($viewUser && (int) $viewUser['id'] === (int) $user['id']) ? 'row-active' : '' ?>">
                                                        <td>
                                                            <div class="stack">
                                                                <div class="user-name"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></div>
                                                                <div class="muted">@<?= htmlspecialchars($user['username']) ?></div>
                                                                <div class="muted"><?= htmlspecialchars($user['email']) ?></div>
                                                            </div>
                                                        </td>
                                                        <td><span class="role-badge role-<?= strtolower($user['user_role']) ?>"><?= htmlspecialchars($user['user_role']) ?></span></td>
                                                        <td><span class="status-badge <?= (int) $user['email_verified'] === 1 ? 'status-verified' : 'status-unverified' ?>"><?= (int) $user['email_verified'] === 1 ? 'Verified' : 'Unverified' ?></span></td>
                                                        <td><span class="status-badge <?= ($user['status'] ?? 'active') === 'active' ? 'status-active' : 'status-flagged' ?>"><?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?></span></td>
                                                        <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($user['created_at']))) ?></td>
                                                        <td>
                                                            <div class="actions">
                                                                <?php if (!$isProtectedSuperadmin): ?>
                                                                    <a class="btn-secondary" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['view' => (int) $user['id'], 'edit' => null])) ?>">View</a>
                                                                    <a class="btn-secondary" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['view' => (int) $user['id'], 'edit' => (int) $user['id']])) ?>">Edit</a>
                                                                    <form method="post">
                                                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                                        <input type="hidden" name="verify" value="<?= (int) $user['email_verified'] === 1 ? '0' : '1' ?>">
                                                                        <button type="submit" name="toggle_verify" class="btn-secondary"><?= (int) $user['email_verified'] === 1 ? 'Mark Unverified' : 'Verify User' ?></button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <span class="muted">Protected account</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($pagination['total_pages'] > 1): ?>
                                        <div class="pagination-bar">
                                            <div class="pagination-summary">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></div>
                                            <div class="pagination-links">
                                                <?php if ($pagination['has_prev']): ?>
                                                    <a class="page-link" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['page' => $pagination['current_page'] - 1, 'view' => $selectedViewId ?: null, 'edit' => $selectedEditId ?: null])) ?>">Previous</a>
                                                <?php endif; ?>
                                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                                    <a class="page-link <?= $i === $pagination['current_page'] ? 'is-active' : '' ?>" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['page' => $i, 'view' => $selectedViewId ?: null, 'edit' => $selectedEditId ?: null])) ?>"><?= $i ?></a>
                                                <?php endfor; ?>
                                                <?php if ($pagination['has_next']): ?>
                                                    <a class="page-link" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['page' => $pagination['current_page'] + 1, 'view' => $selectedViewId ?: null, 'edit' => $selectedEditId ?: null])) ?>">Next</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="stack-grid">
                    <div class="dash-card" id="user-detail-panel">
                        <div class="card-head">
                            <div>
                                <div class="section-label">Inspect</div>
                                <h2>User Details</h2>
                                <div class="section-copy">Editable account information without ban or suspend controls.</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!$viewUser): ?>
                                <div class="empty-state">Select a user from the directory to inspect the account.</div>
                            <?php else: ?>
                                <div class="detail-header">
                                    <div class="detail-persona">
                                        <div class="detail-avatar"><?= strtoupper(substr($viewUser['firstname'], 0, 1)) ?></div>
                                        <div>
                                            <div class="detail-heading"><?= htmlspecialchars($viewUser['firstname'] . ' ' . $viewUser['lastname']) ?></div>
                                            <div class="detail-subcopy">Currently viewing <?= htmlspecialchars(strtolower($viewUser['user_role'])) ?> account #<?= (int) $viewUser['id'] ?></div>
                                        </div>
                                    </div>
                                    <span class="role-badge role-<?= strtolower($viewUser['user_role']) ?>"><?= htmlspecialchars($viewUser['user_role']) ?></span>
                                </div>
                                <div class="detail-grid">
                                    <div class="detail-item"><span class="detail-label">Full Name</span><div class="detail-value"><?= htmlspecialchars($viewUser['firstname'] . ' ' . $viewUser['lastname']) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Role</span><div class="detail-value"><?= htmlspecialchars($viewUser['user_role']) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Username</span><div class="detail-value">@<?= htmlspecialchars($viewUser['username']) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Email</span><div class="detail-value"><?= htmlspecialchars($viewUser['email']) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Verification</span><div class="detail-value"><?= (int) $viewUser['email_verified'] === 1 ? 'Verified' : 'Unverified' ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Account Status</span><div class="detail-value"><?= htmlspecialchars(ucfirst($viewUser['status'] ?? 'active')) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">Created</span><div class="detail-value"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($viewUser['created_at']))) ?></div></div>
                                    <div class="detail-item"><span class="detail-label">User ID</span><div class="detail-value">#<?= (int) $viewUser['id'] ?></div></div>
                                    <?php if ($viewUser['user_role'] === 'Seller'): ?>
                                        <div class="detail-item"><span class="detail-label">Business Name</span><div class="detail-value"><?= htmlspecialchars($viewUser['business_name'] ?: 'Not set') ?></div></div>
                                        <div class="detail-item"><span class="detail-label">Business Phone</span><div class="detail-value"><?= htmlspecialchars($viewUser['seller_phone'] ?: 'Not set') ?></div></div>
                                        <div class="detail-item"><span class="detail-label">Business Address</span><div class="detail-value"><?= nl2br(htmlspecialchars($viewUser['business_address'] ?: 'Not set')) ?></div></div>
                                        <div class="detail-item"><span class="detail-label">Tax ID</span><div class="detail-value"><?= htmlspecialchars($viewUser['tax_id'] ?: 'Not set') ?></div></div>
                                    <?php endif; ?>
                                </div>

                                <div class="detail-actions">
                                    <a class="btn-secondary" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['view' => (int) $viewUser['id'], 'edit' => (int) $viewUser['id']])) ?>">Edit User</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                                        <input type="hidden" name="verify" value="<?= (int) $viewUser['email_verified'] === 1 ? '0' : '1' ?>">
                                        <button type="submit" name="toggle_verify" class="btn-secondary"><?= (int) $viewUser['email_verified'] === 1 ? 'Mark Unverified' : 'Verify User' ?></button>
                                    </form>
                                    <?php if ((int) $viewUser['id'] !== (int) $_SESSION['user_id']): ?>
                                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this user permanently? This action cannot be undone.');">
                                            <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn-danger" <?= $viewUser['user_role'] === 'Superadmin' ? 'disabled' : '' ?>>Delete User</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <?php if ($viewUser['user_role'] === 'Superadmin'): ?>
                                    <div class="protected-note">This account is protected and cannot be deleted.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($editUser): ?>
                        <div class="dash-card editor-shell">
                            <div class="card-head">
                                <div>
                                    <div class="section-label">Edit</div>
                                    <h2>Edit User</h2>
                                    <div class="section-copy">Update profile fields and seller details when applicable.</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="post" class="form-grid">
                                    <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                                    <div class="field">
                                        <label for="edit_firstname">First Name</label>
                                        <input id="edit_firstname" type="text" name="firstname" value="<?= htmlspecialchars($editUser['firstname']) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label for="edit_lastname">Last Name</label>
                                        <input id="edit_lastname" type="text" name="lastname" value="<?= htmlspecialchars($editUser['lastname']) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label for="edit_username">Username</label>
                                        <input id="edit_username" type="text" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label for="edit_email">Email</label>
                                        <input id="edit_email" type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label for="edit_email_verified">Verification</label>
                                        <select id="edit_email_verified" name="email_verified">
                                            <option value="0" <?= (int) $editUser['email_verified'] === 0 ? 'selected' : '' ?>>Unverified</option>
                                            <option value="1" <?= (int) $editUser['email_verified'] === 1 ? 'selected' : '' ?>>Verified</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="edit_user_role">Role</label>
                                        <?php
                                            $editableRoleOptions = ['User', 'Customer', 'Seller', 'Admin'];
                                            $editRoleRanks = ['User' => 0, 'Customer' => 1, 'Seller' => 2, 'Admin' => 3, 'Superadmin' => 4];
                                            $currentEditRoleRank = $editRoleRanks[$editUser['user_role']] ?? 0;
                                        ?>
                                        <select id="edit_user_role" name="user_role" <?= $editUser['user_role'] === 'Superadmin' ? 'disabled' : '' ?>>
                                            <?php if ($editUser['user_role'] === 'Superadmin'): ?>
                                                <option value="Superadmin" selected>Superadmin</option>
                                            <?php else: ?>
                                                <?php foreach ($editableRoleOptions as $roleOption): ?>
                                                    <?php if (($editRoleRanks[$roleOption] ?? 0) >= $currentEditRoleRank): ?>
                                                        <option value="<?= $roleOption ?>" <?= $editUser['user_role'] === $roleOption ? 'selected' : '' ?>><?= $roleOption ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <?php if ($editUser['user_role'] === 'Superadmin'): ?>
                                            <input type="hidden" name="user_role" value="Superadmin">
                                        <?php endif; ?>
                                        <div class="field-note"><?= $editUser['user_role'] === 'Superadmin' ? 'Superadmin accounts are protected.' : 'Choose the same role or promote this user to a higher role.' ?></div>
                                    </div>
                                    <?php $showSellerFields = $editUser['user_role'] === 'Seller'; ?>
                                    <div class="field full seller-role-field" style="<?= $showSellerFields ? '' : 'display:none;' ?>">
                                        <label for="edit_business_name">Business Name</label>
                                        <input id="edit_business_name" type="text" name="business_name" value="<?= htmlspecialchars($editUser['business_name'] ?? '') ?>" data-seller-required>
                                    </div>
                                    <div class="field seller-role-field" style="<?= $showSellerFields ? '' : 'display:none;' ?>">
                                        <label for="edit_business_phone">Business Phone</label>
                                        <input id="edit_business_phone" type="text" name="business_phone" value="<?= htmlspecialchars($editUser['seller_phone'] ?? '') ?>" data-seller-required>
                                    </div>
                                    <div class="field seller-role-field" style="<?= $showSellerFields ? '' : 'display:none;' ?>">
                                        <label for="edit_business_tax_id">Tax ID</label>
                                        <input id="edit_business_tax_id" type="text" name="business_tax_id" value="<?= htmlspecialchars($editUser['tax_id'] ?? '') ?>">
                                    </div>
                                    <div class="field full seller-role-field" style="<?= $showSellerFields ? '' : 'display:none;' ?>">
                                        <label for="edit_business_address">Business Address</label>
                                        <textarea id="edit_business_address" name="business_address" data-seller-required><?= htmlspecialchars($editUser['business_address'] ?? '') ?></textarea>
                                    </div>
                                    <div class="field full">
                                        <div class="detail-actions">
                                            <button type="submit" name="save_user" class="btn-primary">Save Changes</button>
                                            <a class="btn-secondary" href="<?= htmlspecialchars(buildQueryUrl('manage_users.php', $baseQuery, ['view' => (int) $editUser['id'], 'edit' => null])) ?>">Close Editor</a>
                                        </div>
                                    </div>
                                </form>
                                <?php if ((int) $editUser['id'] !== (int) $_SESSION['user_id'] && $editUser['user_role'] !== 'Superadmin'): ?>
                                    <form method="post" class="form-grid admin-tool-form" onsubmit="return confirm('Reset this user password?');">
                                        <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                                        <div class="field full">
                                            <div class="section-label">Admin Tool</div>
                                            <h3>Reset Password</h3>
                                            <div class="field-note">Set a new temporary password for this user.</div>
                                        </div>
                                        <div class="field">
                                            <label for="reset_new_password">New Password</label>
                                            <input id="reset_new_password" type="password" name="new_password" minlength="6" required>
                                        </div>
                                        <div class="field">
                                            <label for="reset_confirm_password">Confirm Password</label>
                                            <input id="reset_confirm_password" type="password" name="confirm_password" minlength="6" required>
                                        </div>
                                        <div class="field full">
                                            <button type="submit" name="reset_user_password" class="btn-secondary">Reset Password</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
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
<script>
    const roleSelect = document.getElementById('edit_user_role');
    const sellerFields = document.querySelectorAll('.seller-role-field');
    const sellerRequiredFields = document.querySelectorAll('[data-seller-required]');

    function syncSellerFields() {
        const shouldShow = roleSelect && roleSelect.value === 'Seller';
        sellerFields.forEach((field) => {
            field.style.display = shouldShow ? '' : 'none';
        });
        sellerRequiredFields.forEach((field) => {
            field.required = shouldShow;
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', syncSellerFields);
        syncSellerFields();
    }
</script>
</body>
</html>
