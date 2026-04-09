<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperadmin()) {
    redirect('../login.php');
}

$stmt = $pdo->prepare("
    SELECT u.*, sa.id AS superadmin_id
    FROM users u
    JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$superadmin = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $stmt->fetchColumn();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $emailVerified = (int) ($_POST['email_verified'] ?? 0);

        $errors = [];
        if ($userId <= 0) $errors[] = 'Invalid user selected.';
        if ($firstname === '') $errors[] = 'First name is required.';
        if ($lastname === '') $errors[] = 'Last name is required.';
        if ($username === '') $errors[] = 'Username is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (!in_array($emailVerified, [0, 1], true)) $errors[] = 'Invalid email verification value.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?");
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists on another account.';
            }
        }

        if (empty($errors)) {
            $verificationToken = $emailVerified === 1 ? null : bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("
                UPDATE users
                SET firstname = ?, lastname = ?, username = ?, email = ?, email_verified = ?, verification_token = ?
                WHERE id = ?
            ");
            $stmt->execute([$firstname, $lastname, $username, $email, $emailVerified, $verificationToken, $userId]);

            logSystemEvent(
                'superadmin_user_updated',
                'users',
                $userId,
                "Superadmin updated account #{$userId} ({$username})."
            );

            $flash = ['type' => 'success', 'text' => 'User details updated successfully.'];
        } else {
            $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
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
            $flash = ['type' => 'error', 'text' => 'Superadmin accounts cannot be deleted here.'];
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

            logSystemEvent(
                'superadmin_user_deleted',
                'users',
                $userId,
                "Superadmin deleted account {$target['username']}."
            );

            $flash = ['type' => 'success', 'text' => 'User deleted successfully.'];
        }
    }

    if (isset($_POST['activate_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId === (int) $_SESSION['user_id']) {
            $flash = ['type' => 'error', 'text' => 'You cannot change your own access status here.'];
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET status = 'active', suspended_until = NULL, banned_reason = NULL, action_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $userId]);

            logSystemEvent(
                'superadmin_user_activated',
                'users',
                $userId,
                "Superadmin restored account #{$userId} to active status."
            );

            $flash = ['type' => 'success', 'text' => 'User status set to active.'];
        }
    }

    if (isset($_POST['suspend_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $suspendUntil = trim((string) ($_POST['suspended_until'] ?? ''));
        $reason = trim((string) ($_POST['status_reason'] ?? ''));

        $stmt = $pdo->prepare("SELECT sa.id AS superadmin_id FROM users u LEFT JOIN superadmins sa ON sa.user_id = u.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if ($userId === (int) $_SESSION['user_id']) {
            $flash = ['type' => 'error', 'text' => 'You cannot suspend your own account.'];
        } elseif (!empty($target['superadmin_id'])) {
            $flash = ['type' => 'error', 'text' => 'You cannot suspend another superadmin here.'];
        } elseif ($suspendUntil === '') {
            $flash = ['type' => 'error', 'text' => 'Suspended until date is required.'];
        } else {
            $untilDate = date('Y-m-d H:i:s', strtotime($suspendUntil));
            if (strtotime($untilDate) <= time()) {
                $flash = ['type' => 'error', 'text' => 'Please choose a future suspension date and time.'];
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET status = 'suspended',
                        suspended_until = ?,
                        banned_reason = ?,
                        action_by = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $untilDate,
                    $reason !== '' ? $reason : null,
                    $_SESSION['user_id'],
                    $userId
                ]);

                logSystemEvent(
                    'superadmin_user_suspended',
                    'users',
                    $userId,
                    "Superadmin suspended account #{$userId} until {$untilDate}." . ($reason !== '' ? " Reason: {$reason}" : '')
                );

                $flash = ['type' => 'success', 'text' => 'User suspended successfully.'];
            }
        }
    }

    if (isset($_POST['ban_user'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $reason = trim((string) ($_POST['status_reason'] ?? ''));

        $stmt = $pdo->prepare("SELECT sa.id AS superadmin_id FROM users u LEFT JOIN superadmins sa ON sa.user_id = u.id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if ($userId === (int) $_SESSION['user_id']) {
            $flash = ['type' => 'error', 'text' => 'You cannot ban your own account.'];
        } elseif (!empty($target['superadmin_id'])) {
            $flash = ['type' => 'error', 'text' => 'You cannot ban another superadmin here.'];
        } elseif ($reason === '') {
            $flash = ['type' => 'error', 'text' => 'Ban reason is required.'];
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET status = 'banned',
                    suspended_until = NULL,
                    banned_reason = ?,
                    action_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$reason, $_SESSION['user_id'], $userId]);

            logSystemEvent(
                'superadmin_user_banned',
                'users',
                $userId,
                "Superadmin banned account #{$userId}. Reason: {$reason}"
            );

            $flash = ['type' => 'success', 'text' => 'User banned successfully.'];
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? ''));
$accountStatus = trim((string) ($_GET['account_status'] ?? ''));
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
           action_user.username AS action_by_username,
           CASE
               WHEN sa.id IS NOT NULL THEN 'Superadmin'
               WHEN a.id IS NOT NULL THEN 'Admin'
               WHEN s.id IS NOT NULL THEN 'Seller'
               WHEN c.id IS NOT NULL THEN 'Customer'
               ELSE 'User'
           END AS user_role
    FROM users u
    LEFT JOIN superadmins sa ON sa.user_id = u.id
    LEFT JOIN admins a ON a.user_id = u.id
    LEFT JOIN sellers s ON s.user_id = u.id
    LEFT JOIN customers c ON c.user_id = u.id
    LEFT JOIN users action_user ON action_user.id = u.action_by
    WHERE 1 = 1
";
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    array_push($params, $like, $like, $like, $like);
}

if ($roleFilter !== '') {
    if ($roleFilter === 'Superadmin') {
        $sql .= " AND sa.id IS NOT NULL ";
    } elseif ($roleFilter === 'Admin') {
        $sql .= " AND a.id IS NOT NULL ";
    } elseif ($roleFilter === 'Seller') {
        $sql .= " AND s.id IS NOT NULL ";
    } elseif ($roleFilter === 'Customer') {
        $sql .= " AND c.id IS NOT NULL ";
    } elseif ($roleFilter === 'User') {
        $sql .= " AND sa.id IS NULL AND a.id IS NULL AND s.id IS NULL AND c.id IS NULL ";
    }
}

if ($accountStatus !== '') {
    $sql .= " AND u.status = ? ";
    $params[] = $accountStatus;
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$viewUser = null;
$editUser = null;
foreach ($users as $user) {
    if ($selectedViewId > 0 && (int) $user['id'] === $selectedViewId) {
        $viewUser = $user;
    }
    if ($selectedEditId > 0 && (int) $user['id'] === $selectedEditId) {
        $editUser = $user;
    }
}

if (!$viewUser && !empty($users)) {
    $viewUser = $users[0];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Beauty Mart Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --cream:#fffaf2; --gold:#d4a24c; --gold-deep:#b98426; --ink:#2f2417; --muted:#7c6b56; --line:#ecdcc0; --card:#ffffff; --soft:#f7efe1; --danger-bg:#fdecea; --danger-border:#f2c6cc; --danger-text:#b4434f; --success-bg:#eaf7ee; --success-border:#cce7d4; --success-text:#2f7a4d; --blue-bg:#e8f0ff; --blue-border:#cad9ff; --blue-text:#2b57b5; --shadow:0 16px 35px rgba(120,82,29,.10); --radius:18px; --sidebar:270px; }
        body { min-height:100vh; font-family:'Nunito',sans-serif; background:radial-gradient(circle at top right, rgba(212,162,76,.18), transparent 28%), linear-gradient(180deg, #fffdf8, var(--cream)); color:var(--ink); }
        a { color:inherit; text-decoration:none; }
        .shell { display:flex; min-height:100vh; }
        .sidebar { width:var(--sidebar); background:rgba(255,255,255,.92); backdrop-filter:blur(10px); border-right:1px solid var(--line); padding:22px 16px; display:flex; flex-direction:column; gap:18px; }
        .brand,.profile,.card,.stat { background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); }
        .brand { display:flex; align-items:center; gap:12px; padding:14px; }
        .brand-icon { width:46px; height:46px; border-radius:14px; background:linear-gradient(135deg, var(--gold), var(--gold-deep)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; overflow:hidden; }
        .brand-icon img,.avatar img,.user-thumb img,.detail-pic img { width:100%; height:100%; object-fit:cover; }
        .brand-title { font-family:'Playfair Display',serif; font-size:18px; }
        .brand-sub { color:var(--muted); font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .profile { padding:16px; display:flex; gap:12px; align-items:center; }
        .avatar { width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, var(--gold), var(--gold-deep)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; overflow:hidden; }
        .profile small { display:inline-block; margin-top:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; color:var(--gold-deep); background:var(--soft); border:1px solid var(--line); }
        .nav { display:flex; flex-direction:column; gap:8px; }
        .nav a { padding:13px 14px; border-radius:14px; font-weight:700; color:var(--muted); border:1px solid transparent; transition:.18s ease; }
        .nav a:hover,.nav a.active { background:var(--soft); border-color:var(--line); color:var(--ink); transform:translateX(2px); }
        .main { flex:1; min-width:0; padding:26px; display:flex; flex-direction:column; gap:22px; }
        .hero { background:linear-gradient(135deg, rgba(212,162,76,.18), rgba(255,255,255,.94)); border:1px solid var(--line); border-radius:26px; padding:28px; box-shadow:var(--shadow); display:flex; justify-content:space-between; gap:18px; align-items:center; }
        .hero h1 { font-family:'Playfair Display',serif; font-size:34px; margin-bottom:8px; }
        .hero p { color:var(--muted); max-width:760px; line-height:1.6; }
        .hero-badges,.stack { display:flex; gap:8px; flex-wrap:wrap; }
        .hero-badges span,.pill { padding:10px 14px; border-radius:999px; background:#fff; border:1px solid var(--line); font-weight:800; font-size:13px; }
        .pill { padding:4px 10px; font-size:11px; background:var(--soft); }
        .flash { padding:14px 16px; border-radius:14px; font-weight:700; line-height:1.5; }
        .flash.success { background:var(--success-bg); border:1px solid var(--success-border); color:var(--success-text); }
        .flash.error { background:var(--danger-bg); border:1px solid var(--danger-border); color:var(--danger-text); }
        .filters { display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:14px; }
        .card { padding:20px; }
        label { display:block; margin-bottom:7px; font-size:13px; font-weight:800; }
        input,select,textarea { width:100%; padding:12px 14px; border-radius:14px; border:1px solid var(--line); background:#fff; color:var(--ink); font:inherit; }
        textarea { min-height:88px; resize:vertical; }
        .btn,.btn-danger,.btn-soft,.btn-warn { display:inline-flex; align-items:center; justify-content:center; padding:11px 15px; border-radius:12px; border:1px solid var(--line); background:#fff; cursor:pointer; font:inherit; font-weight:800; }
        .btn { background:linear-gradient(135deg, var(--gold), var(--gold-deep)); border:none; color:#fff; }
        .btn-soft { background:var(--soft); }
        .btn-warn { background:var(--blue-bg); border-color:var(--blue-border); color:var(--blue-text); }
        .btn-danger { background:var(--danger-bg); border-color:var(--danger-border); color:var(--danger-text); }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .stat { padding:18px; }
        .stat .kicker { color:var(--muted); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .stat .value { margin-top:8px; font-size:28px; font-weight:900; }
        .layout { display:grid; grid-template-columns:1.25fr .75fr; gap:18px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:14px 12px; text-align:left; border-bottom:1px solid #f2e7d3; font-size:13px; vertical-align:top; }
        th { font-size:11px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
        .user-cell,.detail-header { display:flex; gap:12px; align-items:center; }
        .user-thumb { width:44px; height:44px; border-radius:50%; background:var(--soft); border:1px solid var(--line); display:flex; align-items:center; justify-content:center; overflow:hidden; font-weight:800; }
        .meta { color:var(--muted); font-size:12px; }
        .status-active { background:var(--success-bg); border-color:var(--success-border); color:var(--success-text); }
        .status-suspended { background:var(--blue-bg); border-color:var(--blue-border); color:var(--blue-text); }
        .status-banned { background:var(--danger-bg); border-color:var(--danger-border); color:var(--danger-text); }
        .action-stack { display:flex; flex-direction:column; gap:10px; }
        .detail-pic { width:110px; height:110px; border-radius:24px; overflow:hidden; border:1px solid var(--line); background:var(--soft); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:900; }
        .detail-grid,.edit-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .detail-box { padding:14px; border-radius:14px; background:#fffdfa; border:1px solid var(--line); }
        .detail-box strong { display:block; margin-bottom:6px; font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
        .section-head { font-size:17px; font-weight:900; margin-bottom:14px; }
        .edit-form,.status-form { display:grid; gap:12px; }
        .empty { color:var(--muted); padding:12px 0; }
        @media (max-width:1180px) { .layout { grid-template-columns:1fr; } .stats { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:860px) { .shell { flex-direction:column; } .sidebar { width:100%; } .main { padding:18px; } .hero { flex-direction:column; align-items:flex-start; } .filters,.edit-grid,.detail-grid,.stats { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='S'">
            </div>
            <div>
                <div class="brand-title">Beauty Mart</div>
                <div class="brand-sub">Superadmin Panel</div>
            </div>
        </div>
        <div class="profile">
            <div class="avatar">
                <?php if (!empty($superadmin['profile_pic']) && file_exists("../uploads/profile_images/" . $superadmin['profile_pic'])): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($superadmin['profile_pic']) ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($superadmin['firstname'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:800;"><?= htmlspecialchars($superadmin['firstname'] . ' ' . $superadmin['lastname']) ?></div>
                <small>Superadmin</small>
            </div>
        </div>
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="create_users.php">Create Users</a>
            <a href="manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : '' ?>">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>
    <main class="main">
        <section class="hero">
            <div>
                <h1>Manage Users</h1>
                <p>View account details with profile picture, update core profile fields, delete accounts, or apply `suspended` and `banned` access states from the superadmin panel.</p>
            </div>
            <div class="hero-badges">
                <span><?= date('F j, Y') ?></span>
                <span><?= number_format(count($users)) ?> visible users</span>
                <span><?= number_format($unread_count) ?> unread notifications</span>
            </div>
        </section>
        <?php if ($flash): ?>
            <div class="flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['text']) ?></div>
        <?php endif; ?>
        <section class="stats">
            <div class="stat"><div class="kicker">Users</div><div class="value"><?= number_format(count($users)) ?></div></div>
            <div class="stat"><div class="kicker">Active</div><div class="value"><?= number_format(count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'active'))) ?></div></div>
            <div class="stat"><div class="kicker">Suspended</div><div class="value"><?= number_format(count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'suspended'))) ?></div></div>
            <div class="stat"><div class="kicker">Banned</div><div class="value"><?= number_format(count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'banned'))) ?></div></div>
        </section>
        <section class="card">
            <form method="get" class="filters">
                <div><label for="search">Search</label><input id="search" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, username, email"></div>
                <div><label for="role">Role</label><select id="role" name="role"><option value="">All roles</option><?php foreach (['Superadmin', 'Admin', 'Seller', 'Customer', 'User'] as $roleOption): ?><option value="<?= $roleOption ?>" <?= $roleFilter === $roleOption ? 'selected' : '' ?>><?= $roleOption ?></option><?php endforeach; ?></select></div>
                <div><label for="account_status">Account Status</label><select id="account_status" name="account_status"><option value="">All statuses</option><?php foreach (['active', 'suspended', 'banned'] as $statusOption): ?><option value="<?= $statusOption ?>" <?= $accountStatus === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option><?php endforeach; ?></select></div>
                <div style="display:flex;align-items:end;"><button type="submit" class="btn" style="width:100%;">Apply Filters</button></div>
            </form>
        </section>
        <section class="layout">
            <div class="card table-wrap">
                <?php if (empty($users)): ?>
                    <div class="empty">No users matched the current filters.</div>
                <?php else: ?>
                    <table>
                        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Email</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><div class="user-cell"><div class="user-thumb"><?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_images/" . $user['profile_pic'])): ?><img src="../uploads/profile_images/<?= htmlspecialchars($user['profile_pic']) ?>" alt=""><?php else: ?><?= strtoupper(substr($user['firstname'], 0, 1)) ?><?php endif; ?></div><div><div style="font-weight:800;"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></div><div class="meta">@<?= htmlspecialchars($user['username']) ?></div></div></div></td>
                                    <td><span class="pill"><?= htmlspecialchars($user['user_role']) ?></span></td>
                                    <td><span class="pill status-<?= htmlspecialchars($user['status'] ?? 'active') ?>"><?= htmlspecialchars(ucfirst($user['status'] ?? 'active')) ?></span></td>
                                    <td><?= htmlspecialchars($user['email']) ?><div class="meta">Verified: <?= (int) $user['email_verified'] === 1 ? 'Yes' : 'No' ?></div></td>
                                    <td><div class="stack"><a class="btn-soft" href="?<?= http_build_query(array_filter(['search' => $search, 'role' => $roleFilter, 'account_status' => $accountStatus, 'view' => $user['id']])) ?>">View</a><a class="btn-warn" href="?<?= http_build_query(array_filter(['search' => $search, 'role' => $roleFilter, 'account_status' => $accountStatus, 'view' => $user['id'], 'edit' => $user['id']])) ?>">Edit</a></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="action-stack">
                <div class="card">
                    <div class="section-head">User Details</div>
                    <?php if (!$viewUser): ?>
                        <div class="empty">Select a user to view details.</div>
                    <?php else: ?>
                        <div class="detail-header">
                            <div class="detail-pic"><?php if (!empty($viewUser['profile_pic']) && file_exists("../uploads/profile_images/" . $viewUser['profile_pic'])): ?><img src="../uploads/profile_images/<?= htmlspecialchars($viewUser['profile_pic']) ?>" alt=""><?php else: ?><?= strtoupper(substr($viewUser['firstname'], 0, 1)) ?><?php endif; ?></div>
                            <div><div style="font-size:20px;font-weight:900;"><?= htmlspecialchars($viewUser['firstname'] . ' ' . $viewUser['lastname']) ?></div><div class="meta">@<?= htmlspecialchars($viewUser['username']) ?></div><div class="stack" style="margin-top:8px;"><span class="pill"><?= htmlspecialchars($viewUser['user_role']) ?></span><span class="pill status-<?= htmlspecialchars($viewUser['status'] ?? 'active') ?>"><?= htmlspecialchars(ucfirst($viewUser['status'] ?? 'active')) ?></span></div></div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-box"><strong>Email</strong><?= htmlspecialchars($viewUser['email']) ?></div>
                            <div class="detail-box"><strong>Email Verified</strong><?= (int) $viewUser['email_verified'] === 1 ? '1' : '0' ?></div>
                            <div class="detail-box"><strong>Created</strong><?= date('M j, Y g:i A', strtotime($viewUser['created_at'])) ?></div>
                            <div class="detail-box"><strong>Last Status Action By</strong><?= htmlspecialchars($viewUser['action_by_username'] ?? 'None') ?></div>
                            <div class="detail-box"><strong>Suspended Until</strong><?= $viewUser['suspended_until'] ? date('M j, Y g:i A', strtotime($viewUser['suspended_until'])) : 'Not set' ?></div>
                            <div class="detail-box"><strong>Reason</strong><?= htmlspecialchars($viewUser['banned_reason'] ?? 'None') ?></div>
                            <?php if ($viewUser['user_role'] === 'Seller'): ?>
                                <div class="detail-box"><strong>Business Name</strong><?= htmlspecialchars($viewUser['business_name'] ?? 'N/A') ?></div>
                                <div class="detail-box"><strong>Business Phone</strong><?= htmlspecialchars($viewUser['seller_phone'] ?? 'N/A') ?></div>
                                <div class="detail-box" style="grid-column:1 / -1;"><strong>Business Address</strong><?= htmlspecialchars($viewUser['business_address'] ?? 'N/A') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($editUser): ?>
                    <div class="card">
                        <div class="section-head">Edit User</div>
                        <form method="post" class="edit-form">
                            <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                            <div class="edit-grid">
                                <div><label for="firstname">First Name</label><input id="firstname" type="text" name="firstname" value="<?= htmlspecialchars($editUser['firstname']) ?>" required></div>
                                <div><label for="lastname">Last Name</label><input id="lastname" type="text" name="lastname" value="<?= htmlspecialchars($editUser['lastname']) ?>" required></div>
                                <div><label for="username">Username</label><input id="username" type="text" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required></div>
                                <div><label for="email">Email</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required></div>
                                <div><label for="email_verified">Email Verified</label><select id="email_verified" name="email_verified"><option value="1" <?= (int) $editUser['email_verified'] === 1 ? 'selected' : '' ?>>1 - Verified</option><option value="0" <?= (int) $editUser['email_verified'] === 0 ? 'selected' : '' ?>>0 - Not Verified</option></select></div>
                            </div>
                            <button type="submit" name="save_user" class="btn">Save Changes</button>
                        </form>
                    </div>
                <?php endif; ?>
                <?php if ($viewUser): ?>
                    <div class="card">
                        <div class="section-head">Access Controls</div>
                        <div class="action-stack">
                            <form method="post" class="status-form">
                                <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                                <label for="status_reason_suspend">Suspend Reason</label>
                                <textarea id="status_reason_suspend" name="status_reason" placeholder="Optional reason for suspension"></textarea>
                                <label for="suspended_until">Suspend Until</label>
                                <input id="suspended_until" type="datetime-local" name="suspended_until">
                                <button type="submit" name="suspend_user" class="btn-warn" <?= (int) $viewUser['id'] === (int) $_SESSION['user_id'] ? 'disabled' : '' ?>>Suspend User</button>
                            </form>
                            <form method="post" class="status-form">
                                <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                                <label for="status_reason_ban">Ban Reason</label>
                                <textarea id="status_reason_ban" name="status_reason" placeholder="Reason for ban" required></textarea>
                                <button type="submit" name="ban_user" class="btn-danger" <?= (int) $viewUser['id'] === (int) $_SESSION['user_id'] ? 'disabled' : '' ?>>Ban User</button>
                            </form>
                            <form method="post"><input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>"><button type="submit" name="activate_user" class="btn-soft" <?= (int) $viewUser['id'] === (int) $_SESSION['user_id'] ? 'disabled' : '' ?>>Set Active / Unsuspend</button></form>
                            <form method="post" onsubmit="return confirm('Delete this user permanently? This action cannot be undone.');"><input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>"><button type="submit" name="delete_user" class="btn-danger" <?= (int) $viewUser['id'] === (int) $_SESSION['user_id'] || $viewUser['user_role'] === 'Superadmin' ? 'disabled' : '' ?>>Delete User</button></form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
