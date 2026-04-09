<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isSuperadmin()) {
    redirect('../login.php');
}

$stmt = $pdo->prepare("
    SELECT u.*,
           sa.id AS superadmin_id
    FROM users u
    JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$superadmin = $stmt->fetch();

$search = trim((string) ($_GET['search'] ?? ''));
$actionFilter = trim((string) ($_GET['action'] ?? ''));

$logSql = "
    SELECT sl.*, u.username, u.firstname, u.lastname
    FROM system_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    WHERE 1 = 1
";
$logParams = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $logSql .= " AND (
        sl.description LIKE ?
        OR sl.table_name LIKE ?
        OR sl.action LIKE ?
        OR u.username LIKE ?
        OR u.firstname LIKE ?
        OR u.lastname LIKE ?
    )";
    array_push($logParams, $like, $like, $like, $like, $like, $like);
}

if ($actionFilter !== '') {
    $logSql .= " AND sl.action = ?";
    $logParams[] = $actionFilter;
}

$logSql .= " ORDER BY sl.created_at DESC, sl.log_id DESC LIMIT 200";
$stmt = $pdo->prepare($logSql);
$stmt->execute($logParams);
$logs = $stmt->fetchAll();

$actionsStmt = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action ASC");
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

$notifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $notifStmt->fetchColumn();

$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status = 'pending'");
$pendingStmt->execute();
$pending_apps = (int) $pendingStmt->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs | Beauty Mart Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #fff8f8;
            --card: #ffffff;
            --text: #30212e;
            --muted: #786474;
            --accent: #d14d72;
            --accent-dark: #b33c5e;
            --line: #f0d9e0;
            --shadow: 0 20px 45px rgba(87, 32, 54, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Nunito', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(209, 77, 114, 0.13), transparent 30%),
                linear-gradient(180deg, #fff9fb 0%, #fff4f4 100%);
        }

        .shell {
            min-height: 100vh;
            padding: 32px;
        }

        .page {
            max-width: 1280px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            margin-bottom: 24px;
        }

        .topbar h1 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
        }

        .topbar p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .pill, .back-link {
            text-decoration: none;
            color: var(--text);
            background: rgba(255,255,255,0.92);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 800;
            box-shadow: var(--shadow);
        }

        .back-link {
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border: none;
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(209, 77, 114, 0.12);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .filters {
            display: grid;
            grid-template-columns: 1.5fr 1fr auto;
            gap: 16px;
            padding: 24px;
            border-bottom: 1px solid var(--line);
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 800;
        }

        .field input,
        .field select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid var(--line);
            font: inherit;
            background: #fff;
        }

        .filter-btn {
            align-self: end;
            border: none;
            border-radius: 14px;
            padding: 12px 20px;
            font: inherit;
            font-weight: 800;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        th {
            font-size: 0.85rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
            background: #fffafb;
        }

        .meta {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .empty {
            padding: 48px 24px;
            text-align: center;
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .shell { padding: 18px; }
            .topbar { flex-direction: column; align-items: flex-start; }
            .filters { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="page">
            <div class="topbar">
                <div>
                    <h1>System Logs</h1>
                    <p>Audit trail for superadmin, admin, seller, and customer activity across the platform.</p>
                </div>
                <div class="top-actions">
                    <span class="pill"><?= number_format(count($logs)) ?> visible logs</span>
                    <span class="pill"><?= number_format($pending_apps) ?> pending seller apps</span>
                    <span class="pill"><?= number_format($unread_count) ?> unread notifications</span>
                    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
                </div>
            </div>

            <div class="card">
                <form method="get" class="filters">
                    <div class="field">
                        <label for="search">Search</label>
                        <input id="search" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Description, table, action, or user">
                    </div>
                    <div class="field">
                        <label for="action">Action</label>
                        <select id="action" name="action">
                            <option value="">All actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= htmlspecialchars($action) ?>" <?= $actionFilter === $action ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($action) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="filter-btn">Apply Filter</button>
                </form>

                <div class="table-wrap">
                    <?php if (empty($logs)): ?>
                        <div class="empty">No system logs matched the current filters.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                                            <div class="meta">Log #<?= (int) $log['log_id'] ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $fullName = trim((string) (($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? '')));
                                            echo htmlspecialchars($fullName !== '' ? $fullName : ($log['username'] ?? 'System'));
                                            ?>
                                            <div class="meta">
                                                <?= htmlspecialchars($log['username'] ? '@' . $log['username'] : 'No linked user') ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                        <td><?= $log['record_id'] !== null ? (int) $log['record_id'] : '-' ?></td>
                                        <td><?= nl2br(htmlspecialchars($log['description'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
