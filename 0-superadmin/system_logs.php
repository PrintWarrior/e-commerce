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
          display: flex;
          min-height: 100vh;
        }

        .page {
          flex: 1;
          padding: 28px;
          display: flex;
          flex-direction: column;
          gap: 28px;
          overflow-y: auto;
        }

        /* ── Topbar ──────────────────────────────────────────────────── */
        .topbar {
          background: linear-gradient(135deg, #fce8ee 0%, #fdf5f7 60%, #fce8ee 100%);
          border-radius: var(--radius);
          padding: 28px;
          border: 1.5px solid var(--pink-mid);
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          gap: 20px;
        }

        .topbar h1 {
          font-family: "Playfair Display", serif;
          font-size: 28px;
          font-weight: 700;
          color: var(--pink-dark);
          margin-bottom: 8px;
        }

        .topbar p {
          font-size: 14px;
          color: var(--text-mid);
          line-height: 1.6;
        }

        .top-actions {
          display: flex;
          flex-direction: column;
          gap: 8px;
          min-width: 180px;
        }

        /* ── Pills/Badges ────────────────────────────────────────────– */
        .pill {
          background: #fff;
          padding: 10px 14px;
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
          font-size: 12px;
          font-weight: 700;
          color: var(--pink-accent);
          text-align: right;
        }

        .back-link {
          background: var(--pink-light);
          color: var(--pink-accent);
          padding: 10px 14px;
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
          font-size: 12px;
          font-weight: 700;
          transition: all 0.2s;
          text-align: center;
        }

        .back-link:hover {
          background: var(--pink-mid);
          border-color: var(--pink-accent);
        }

        /* ── Cards ───────────────────────────────────────────────────── */
        .card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          overflow: hidden;
        }

        /* ── Filters ──────────────────────────────────────────────────– */
        .filters {
          display: grid;
          grid-template-columns: 2fr 1fr auto;
          gap: 14px;
          align-items: end;
          padding: 22px;
          border-bottom: 1.5px solid var(--pink-mid);
        }

        .field {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        label {
          font-size: 13px;
          font-weight: 700;
          color: var(--text-dark);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        input,
        select {
          padding: 12px 14px;
          border: 1.5px solid var(--pink-mid);
          border-radius: 10px;
          font-family: "Nunito", sans-serif;
          font-size: 14px;
          color: var(--text-dark);
          background: #fdf5f7;
          outline: none;
          transition: border-color 0.2s;
        }

        input:focus,
        select:focus {
          border-color: var(--pink-accent);
          background: #fff;
        }

        /* ── Buttons ──────────────────────────────────────────────────– */
        .filter-btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 12px 18px;
          border-radius: 10px;
          border: none;
          background: var(--pink-accent);
          color: #fff;
          cursor: pointer;
          font: inherit;
          font-weight: 700;
          transition: all 0.2s;
          min-width: 120px;
        }

        .filter-btn:hover {
          background: var(--pink-dark);
        }

        /* ── Table Styles ────────────────────────────────────────────– */
        .table-wrap {
          overflow-x: auto;
        }

        table {
          width: 100%;
          border-collapse: collapse;
          font-size: 13px;
        }

        table thead th {
          font-size: 12px;
          font-weight: 700;
          color: var(--text-muted);
          text-align: left;
          padding: 14px 12px;
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
          padding: 14px 12px;
          color: var(--text-dark);
        }

        .meta {
          font-size: 12px;
          color: var(--text-muted);
          margin-top: 2px;
        }

        /* ── Empty State ──────────────────────────────────────────────– */
        .empty {
          padding: 32px;
          text-align: center;
          color: var(--text-muted);
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
        @media (max-width: 1000px) {
          .topbar {
            flex-direction: column;
          }

          .top-actions {
            flex-direction: row;
            min-width: auto;
            width: 100%;
          }

          .pill,
          .back-link {
            flex: 1;
            text-align: center;
          }

          .filters {
            grid-template-columns: 1fr;
          }

          .filter-btn {
            width: 100%;
          }
        }

        @media (max-width: 768px) {
          .page {
            padding: 14px;
          }

          .topbar {
            padding: 18px;
          }

          .topbar h1 {
            font-size: 22px;
          }

          .filters {
            grid-template-columns: 1fr;
            padding: 14px;
          }

          .filter-btn {
            width: 100%;
          }

          table thead th {
            font-size: 11px;
            padding: 10px 8px;
          }

          table tbody td {
            padding: 10px 8px;
            font-size: 12px;
          }
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
                        <input id="search" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Description, action, or user">
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
                                    <!--<th>Table</th>
                                    <th>Record</th>-->
                                    <th>Description</th>
                                    <!--<th>IP Address</th>-->
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
                                        <!--<td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                        <td><?= $log['record_id'] !== null ? (int) $log['record_id'] : '-' ?></td>-->
                                        <td><?= nl2br(htmlspecialchars($log['description'] ?? '-')) ?></td>
                                        <!--<td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>-->
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
