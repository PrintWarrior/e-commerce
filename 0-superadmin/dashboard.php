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

$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['users'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM superadmins");
$stats['superadmins'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
$stats['admins'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$stats['products'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
$stats['logs'] = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT u.id, u.firstname, u.lastname, u.username, u.email, u.created_at,
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
    ORDER BY u.created_at DESC
    LIMIT 6
");
$stmt->execute();
$recent_users = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT log_id, action, description, created_at
    FROM system_logs
    ORDER BY created_at DESC, log_id DESC
    LIMIT 6
");
$stmt->execute();
$recent_logs = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --cream: #fffaf2;
            --gold: #d4a24c;
            --gold-deep: #b98426;
            --ink: #2f2417;
            --muted: #7c6b56;
            --line: #ecdcc0;
            --card: #ffffff;
            --soft: #f7efe1;
            --shadow: 0 16px 35px rgba(120, 82, 29, 0.10);
            --radius: 18px;
            --sidebar: 270px;
        }
        body { min-height: 100vh; font-family: 'Nunito', sans-serif; background: radial-gradient(circle at top right, rgba(212,162,76,.18), transparent 28%), linear-gradient(180deg, #fffdf8, var(--cream)); color: var(--ink); }
        a { color: inherit; text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: var(--sidebar); background: rgba(255,255,255,.92); backdrop-filter: blur(10px);
            border-right: 1px solid var(--line); padding: 22px 16px; display: flex; flex-direction: column; gap: 18px;
        }
        .brand, .profile, .panel, .stat, .table-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
        .brand { display: flex; align-items: center; gap: 12px; padding: 14px; }
        .brand-icon { width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(135deg, var(--gold), var(--gold-deep)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; overflow: hidden; }
        .brand-icon img { width: 100%; height: 100%; object-fit: cover; }
        .brand-title { font-family: 'Playfair Display', serif; font-size: 18px; }
        .brand-sub { color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .profile { padding: 16px; display: flex; gap: 12px; align-items: center; }
        .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--gold), var(--gold-deep)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile small { display: inline-block; margin-top: 4px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; color: var(--gold-deep); background: var(--soft); border: 1px solid var(--line); }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav a {
            padding: 13px 14px; border-radius: 14px; font-weight: 700; color: var(--muted);
            border: 1px solid transparent; transition: .18s ease;
        }
        .nav a:hover, .nav a.active { background: var(--soft); border-color: var(--line); color: var(--ink); transform: translateX(2px); }
        .main { flex: 1; min-width: 0; padding: 26px; display: flex; flex-direction: column; gap: 22px; }
        .hero {
            background: linear-gradient(135deg, rgba(212,162,76,.18), rgba(255,255,255,.94));
            border: 1px solid var(--line); border-radius: 26px; padding: 28px; box-shadow: var(--shadow);
            display: flex; justify-content: space-between; gap: 18px; align-items: center;
        }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 34px; margin-bottom: 8px; }
        .hero p { color: var(--muted); max-width: 760px; line-height: 1.6; }
        .hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-badges span { padding: 10px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--line); font-weight: 800; font-size: 13px; }
        .stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
        .stat { padding: 18px; }
        .stat .kicker { color: var(--muted); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
        .stat .value { margin-top: 8px; font-size: 28px; font-weight: 900; }
        .grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 18px; }
        .table-card { overflow: hidden; }
        .card-head { padding: 16px 18px; border-bottom: 1px solid var(--line); background: #fffdf9; display: flex; justify-content: space-between; align-items: center; }
        .card-head h2 { font-size: 16px; }
        .card-head a { color: var(--gold-deep); font-weight: 800; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #f2e7d3; font-size: 13px; vertical-align: top; }
        th { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .role { display: inline-block; padding: 4px 10px; border-radius: 999px; background: var(--soft); border: 1px solid var(--line); font-size: 11px; font-weight: 800; }
        .empty { padding: 22px 18px; color: var(--muted); }
        @media (max-width: 1180px) { .stats { grid-template-columns: repeat(2, 1fr); } .grid { grid-template-columns: 1fr; } }
        @media (max-width: 840px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; }
            .hero { flex-direction: column; align-items: flex-start; }
            .stats { grid-template-columns: 1fr; }
            .main { padding: 18px; }
        }
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
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="create_users.php">Create Users</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main">
        <section class="hero">
            <div>
                <h1>Superadmin Dashboard</h1>
                <p>Central oversight for users, products, notifications, and the audit trail. This dashboard is set up to route you into the existing management tools while keeping superadmin login separate from the regular admin flow.</p>
            </div>
            <div class="hero-badges">
                <span><?= date('F j, Y') ?></span>
                <span><?= number_format($unread_count) ?> unread notifications</span>
                <span><?= number_format($stats['logs']) ?> system logs</span>
            </div>
        </section>

        <section class="stats">
            <div class="stat"><div class="kicker">Total Users</div><div class="value"><?= number_format($stats['users']) ?></div></div>
            <div class="stat"><div class="kicker">Superadmins</div><div class="value"><?= number_format($stats['superadmins']) ?></div></div>
            <div class="stat"><div class="kicker">Admins</div><div class="value"><?= number_format($stats['admins']) ?></div></div>
            <div class="stat"><div class="kicker">Products</div><div class="value"><?= number_format($stats['products']) ?></div></div>
            <div class="stat"><div class="kicker">System Logs</div><div class="value"><?= number_format($stats['logs']) ?></div></div>
        </section>

        <section class="grid">
            <div class="table-card">
                <div class="card-head">
                    <h2>Recent Users</h2>
                    <a href="manage_users.php">Open Manage Users</a>
                </div>
                <?php if (empty($recent_users)): ?>
                    <div class="empty">No users found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
                                    <td>@<?= htmlspecialchars($user['username']) ?></td>
                                    <td><span class="role"><?= htmlspecialchars($user['user_role']) ?></span></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <div class="card-head">
                    <h2>Recent System Logs</h2>
                    <a href="../1-admin/system_logs.php">Open Logs</a>
                </div>
                <?php if (empty($recent_logs)): ?>
                    <div class="empty">No system logs found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($log['action']) ?></strong><br>
                                        <span style="color:var(--muted);"><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($log['description'] ?? 'No description') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
