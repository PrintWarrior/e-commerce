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
        /* ── Reset & Base ─────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        :root {
          --pink-light: #fce8ee;
          --pink-mid: #f9d0dc;
          --pink-accent: #e8728e;
          --pink-dark: #c75473;
          --text-dark: #2e2e2e;
          --text-mid: #555;
          --text-muted: #888;
          --white: #ffffff;
          --radius: 14px;
          --shadow: 0 4px 18px rgba(200, 80, 110, 0.12);
        }

        html, body {
          font-family: "Nunito", sans-serif;
          background: #fdf5f7;
          color: var(--text-dark);
          min-height: 100vh;
        }

        a {
          text-decoration: none;
          color: inherit;
        }

        ul, li {
          list-style: none;
        }

        /* ── Layout Shell ────────────────────────────────────────────── */
        .shell {
          display: grid;
          grid-template-columns: 260px 1fr;
          min-height: 100vh;
          gap: 0;
        }

        /* ── Sidebar ──────────────────────────────────────────────────── */
        .sidebar {
          background: #fff;
          border-right: 1.5px solid var(--pink-mid);
          padding: 24px 18px;
          display: flex;
          flex-direction: column;
          gap: 28px;
          position: sticky;
          top: 0;
          height: 100vh;
          overflow-y: auto;
        }

        .brand {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .brand-icon {
          width: 52px;
          height: 52px;
          background: var(--pink-light);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          line-height: 1;
          flex-shrink: 0;
        }

        .brand-icon img {
          width: 70%;
          height: 70%;
          object-fit: contain;
        }

        .brand-title {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--pink-accent);
        }

        .brand-sub {
          font-size: 12px;
          color: var(--text-muted);
          font-weight: 600;
        }

        .profile {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 14px;
          background: var(--pink-light);
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
        }

        .avatar {
          width: 48px;
          height: 48px;
          background: var(--pink-accent);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #fff;
          font-weight: 700;
          font-size: 18px;
          flex-shrink: 0;
          overflow: hidden;
        }

        .avatar img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .profile > div {
          min-width: 0;
        }

        .profile div:first-child {
          font-weight: 800;
          font-size: 14px;
          color: var(--text-dark);
        }

        .profile small {
          font-size: 12px;
          color: var(--text-muted);
        }

        .nav {
          display: flex;
          flex-direction: column;
          gap: 6px;
          flex: 1;
        }

        .nav a {
          padding: 12px 14px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          color: var(--text-mid);
          transition: all 0.2s;
          border-left: 3px solid transparent;
        }

        .nav a:hover {
          background: var(--pink-light);
          color: var(--pink-accent);
          border-left-color: var(--pink-accent);
        }

        .nav a.active {
          background: var(--pink-light);
          color: var(--pink-accent);
          border-left-color: var(--pink-accent);
        }

        /* ── Main Content ────────────────────────────────────────────── */
        .main {
          padding: 28px;
          display: flex;
          flex-direction: column;
          gap: 28px;
          overflow-y: auto;
        }

        /* ── Hero Section ────────────────────────────────────────────── */
        .hero {
          background: linear-gradient(135deg, #fce8ee 0%, #fdf5f7 60%, #fce8ee 100%);
          border-radius: var(--radius);
          padding: 28px;
          border: 1.5px solid var(--pink-mid);
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: 20px;
        }

        .hero h1 {
          font-family: "Playfair Display", serif;
          font-size: 28px;
          font-weight: 700;
          color: var(--pink-dark);
          margin-bottom: 8px;
        }

        .hero p {
          font-size: 14px;
          color: var(--text-mid);
          line-height: 1.6;
        }

        .hero-badges {
          display: flex;
          flex-direction: column;
          gap: 8px;
          min-width: 180px;
        }

        .hero-badges span {
          background: #fff;
          padding: 10px 14px;
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
          font-size: 12px;
          font-weight: 700;
          color: var(--pink-accent);
          text-align: right;
        }

        /* ── Stats Grid ──────────────────────────────────────────────– */
        .stats {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
          gap: 16px;
        }

        @media (max-width: 1200px) {
          .stats {
            grid-template-columns: repeat(3, 1fr);
          }
        }

        @media (max-width: 700px) {
          .stats {
            grid-template-columns: repeat(2, 1fr);
          }
        }

        .stat {
          background: #fff;
          border-radius: var(--radius);
          padding: 18px;
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          text-align: center;
        }

        .stat .kicker {
          font-size: 11px;
          font-weight: 700;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 8px;
        }

        .stat .value {
          font-size: 24px;
          font-weight: 800;
          color: var(--pink-accent);
        }

        /* ── Grid Layout ──────────────────────────────────────────────– */
        .grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
        }

        @media (max-width: 1000px) {
          .grid {
            grid-template-columns: 1fr;
          }
        }

        /* ── Cards ───────────────────────────────────────────────────── */
        .table-card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          padding: 22px;
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .card-head {
          display: flex;
          justify-content: space-between;
          align-items: center;
          border-bottom: 1.5px solid var(--pink-mid);
          padding-bottom: 14px;
        }

        .card-head h2 {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--text-dark);
        }

        .card-head a {
          font-size: 12px;
          font-weight: 700;
          color: var(--pink-accent);
          padding: 8px 14px;
          background: var(--pink-light);
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
          transition: all 0.2s;
        }

        .card-head a:hover {
          background: var(--pink-accent);
          color: #fff;
          border-color: var(--pink-accent);
        }

        /* ── Table Styles ────────────────────────────────────────────– */
        table {
          width: 100%;
          border-collapse: collapse;
        }

        table thead th {
          font-size: 12px;
          font-weight: 700;
          color: var(--text-muted);
          text-align: left;
          padding: 12px 0;
          border-bottom: 1.5px solid var(--pink-mid);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        table tbody tr {
          border-bottom: 1px solid #f0f0f0;
          transition: background 0.2s;
        }

        table tbody tr:hover {
          background: var(--pink-light);
        }

        table tbody td {
          padding: 14px 0;
          font-size: 13px;
          color: var(--text-dark);
        }

        table tbody td strong {
          font-weight: 700;
          display: block;
          margin-bottom: 4px;
        }

        table tbody td span {
          display: inline-block;
        }

        .role {
          background: var(--pink-light);
          color: var(--pink-accent);
          padding: 4px 10px;
          border-radius: 6px;
          font-size: 11px;
          font-weight: 700;
          text-transform: capitalize;
        }

        .empty {
          text-align: center;
          padding: 32px;
          color: var(--text-muted);
          font-size: 14px;
        }

        /* ── Scrollbar ───────────────────────────────────────────────– */
        ::-webkit-scrollbar {
          width: 8px;
          height: 8px;
        }

        ::-webkit-scrollbar-track {
          background: transparent;
        }

        ::-webkit-scrollbar-thumb {
          background: var(--pink-mid);
          border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
          background: var(--pink-accent);
        }

        /* ── Responsive ──────────────────────────────────────────────– */
        @media (max-width: 768px) {
          .shell {
            grid-template-columns: 1fr;
          }

          .sidebar {
            height: auto;
            position: static;
            border-right: none;
            border-bottom: 1.5px solid var(--pink-mid);
            padding: 14px;
          }

          .main {
            padding: 14px;
          }

          .hero {
            flex-direction: column;
          }

          .hero-badges {
            flex-direction: row;
            min-width: auto;
          }

          .hero-badges span {
            text-align: center;
            flex: 1;
          }
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
