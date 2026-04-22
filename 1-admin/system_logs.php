<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdminOrSuperadmin()) redirect('../login.php');

$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id, sa.id AS superadmin_id FROM users u LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN superadmins sa ON u.id=sa.user_id WHERE u.id=?");
$stmt->execute([$_SESSION['user_id']]); $admin = $stmt->fetch();
$is_superadmin = !empty($admin['superadmin_id']);

$search       = trim((string)($_GET['search'] ?? ''));
$actionFilter = trim((string)($_GET['action'] ?? ''));
$tableFilter  = trim((string)($_GET['table']  ?? ''));

$logSql    = "SELECT sl.*, u.username, u.firstname, u.lastname FROM system_logs sl LEFT JOIN users u ON u.id=sl.user_id LEFT JOIN superadmins sa ON sa.user_id=u.id WHERE 1=1";
$logParams = [];

if (!$is_superadmin) { $logSql .= " AND sa.id IS NULL"; }

if ($search !== '') {
    $like = '%' . $search . '%';
    $logSql .= " AND (sl.description LIKE ? OR sl.table_name LIKE ? OR sl.action LIKE ? OR u.username LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ?)";
    array_push($logParams, $like, $like, $like, $like, $like, $like);
}
if ($actionFilter !== '') { $logSql .= " AND sl.action = ?"; $logParams[] = $actionFilter; }
if ($tableFilter  !== '') { $logSql .= " AND sl.table_name = ?"; $logParams[] = $tableFilter; }

$logSql .= " ORDER BY sl.created_at DESC, sl.log_id DESC LIMIT 200";
$stmt = $pdo->prepare($logSql); $stmt->execute($logParams); $logs = $stmt->fetchAll();

$actSql = "SELECT DISTINCT sl.action FROM system_logs sl LEFT JOIN users u ON u.id=sl.user_id LEFT JOIN superadmins sa ON sa.user_id=u.id WHERE 1=1" . (!$is_superadmin?" AND sa.id IS NULL":"") . " ORDER BY sl.action ASC";
$actions = $pdo->query($actSql)->fetchAll(PDO::FETCH_COLUMN);

$tblSql = "SELECT DISTINCT sl.table_name FROM system_logs sl LEFT JOIN users u ON u.id=sl.user_id LEFT JOIN superadmins sa ON sa.user_id=u.id WHERE sl.table_name IS NOT NULL AND sl.table_name != ''" . (!$is_superadmin?" AND sa.id IS NULL":"") . " ORDER BY sl.table_name ASC";
$tables = $pdo->query($tblSql)->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $stmt->execute([$_SESSION['user_id']]); $unread_count = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'"); $stmt->execute(); $pending_apps = (int)$stmt->fetchColumn();

// Action color map
function action_style(string $action): array {
    $a = strtolower($action);
    if (str_contains($a,'delete') || str_contains($a,'remov'))  return ['bg'=>'#fdecea','color'=>'#c0303a'];
    if (str_contains($a,'creat') || str_contains($a,'add') || str_contains($a,'insert')) return ['bg'=>'#d1f5e0','color'=>'#1a7f4b'];
    if (str_contains($a,'updat') || str_contains($a,'edit') || str_contains($a,'modif')) return ['bg'=>'#dce8ff','color'=>'#2255cc'];
    if (str_contains($a,'login') || str_contains($a,'logout') || str_contains($a,'auth')) return ['bg'=>'#f3e0ff','color'=>'#7322cc'];
    if (str_contains($a,'approv') || str_contains($a,'accept')) return ['bg'=>'#d1f5e0','color'=>'#1a7f4b'];
    if (str_contains($a,'declin') || str_contains($a,'reject')) return ['bg'=>'#fdecea','color'=>'#c0303a'];
    return ['bg'=>'#fff3cd','color'=>'#856404'];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs | Beauty Mart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../css/admin_logs.css">
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
                <span class="chip-role"><?= $is_superadmin ? '👑 Superadmin' : '⚙️ Administrator' ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php"       class="<?= $current_page==='dashboard.php'?'active':'' ?>"><span class="ni">📊</span> Dashboard</a>
            <a href="manage_users.php"    class="<?= $current_page==='manage_users.php'?'active':'' ?>"><span class="ni">👥</span> Manage Users</a>
            <a href="manage_sellers.php"  class="<?= $current_page==='manage_sellers.php'?'active':'' ?>">
                <span class="ni">🏪</span> Manage Sellers
                <?php if ($pending_apps > 0): ?><span class="nbadge"><?= $pending_apps ?></span><?php endif; ?>
            </a>
            <a href="products.php"        class="<?= $current_page==='products.php'?'active':'' ?>"><span class="ni">🛍️</span> Products</a>
            <a href="orders.php"          class="<?= $current_page==='orders.php'?'active':'' ?>"><span class="ni">📦</span> Orders</a>

            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page==='manage_deletions.php'?'active':'' ?>"><span class="ni">🗑️</span> Deletion Requests</a>
            <a href="notifications.php"    class="<?= $current_page==='notifications.php'?'active':'' ?>">
                <span class="ni">🔔</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>
            
            <a href="system_logs.php"      class="<?= $current_page==='system_logs.php'?'active':'' ?>"><span class="ni">⚙️</span> System Logs</a>

            <div class="nav-lbl">Account</div>
            <a href="profile.php"           class="<?= $current_page==='profile.php'?'active':'' ?>"><span class="ni">👤</span> My Profile</a>
            <a href="about.php"            class="<?= $current_page==='about.php'?'active':'' ?>"><span class="ni">📝</span> About Menu</a>
            
            <a href="../logout.php" class="logout"><span class="ni">🚪</span> Logout</a>
        </nav>
    </aside>

    <!-- ── Main ─────────────────────────────────────────────── -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>System Logs</h1>
                <p>Audit trail for admin and seller actions across products, sellers, and payouts</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <span class="pill">📋 <?= number_format(count($logs)) ?> logs</span>
                <?php if ($unread_count > 0): ?>
                    <span class="pill">🔔 <?= $unread_count ?> unread</span>
                <?php endif; ?>
                <!--<a href="dashboard.php" class="btn-back-sm">‹ Dashboard</a>-->
            </div>
        </div>

        <div class="content">

            <!-- Summary strip -->
            <?php
            $creates = count(array_filter($logs, fn($l) => str_contains(strtolower($l['action']),'creat') || str_contains(strtolower($l['action']),'add') || str_contains(strtolower($l['action']),'insert')));
            $updates = count(array_filter($logs, fn($l) => str_contains(strtolower($l['action']),'updat') || str_contains(strtolower($l['action']),'edit')));
            $deletes = count(array_filter($logs, fn($l) => str_contains(strtolower($l['action']),'delet') || str_contains(strtolower($l['action']),'remov')));
            $others  = count($logs) - $creates - $updates - $deletes;
            ?>
            <!--<div class="summary-strip">
                <div class="sum-chip">
                    <div class="sum-icon">📋</div>
                    <div><div class="sum-val"><?= number_format(count($logs)) ?></div><div class="sum-lbl">Total Logs</div></div>
                </div>
                <div class="sum-chip">
                    <div class="sum-icon" style="background:#d1f5e0;border-color:#a8ddb8;">➕</div>
                    <div><div class="sum-val"><?= $creates ?></div><div class="sum-lbl">Create / Add</div></div>
                </div>
                <div class="sum-chip">
                    <div class="sum-icon" style="background:#dce8ff;border-color:#b0c8f5;">✏️</div>
                    <div><div class="sum-val"><?= $updates ?></div><div class="sum-lbl">Update / Edit</div></div>
                </div>
                <div class="sum-chip">
                    <div class="sum-icon" style="background:#fdecea;border-color:#f5b8be;">🗑️</div>
                    <div><div class="sum-val"><?= $deletes ?></div><div class="sum-lbl">Delete / Remove</div></div>
                </div>
            </div>-->

            <!-- Filter card -->
            <div class="filter-card">
                <div class="filter-head">🔍 Filter Logs</div>
                <form method="get">
                    <div class="filter-body">
                        <div class="filter-field">
                            <label for="search">Search</label>
                            <input id="search" type="text" name="search"
                                   value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Description, table, action, or user…">
                        </div>
                        <div class="filter-field">
                            <label for="f-action">Action</label>
                            <select id="f-action" name="action">
                                <option value="">All actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <?php $s = action_style($act); ?>
                                    <option value="<?= htmlspecialchars($act) ?>"
                                            <?= $actionFilter===$act?'selected':'' ?>>
                                        <?= htmlspecialchars($act) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($tables)): ?>
                      <!-- <div class="filter-field">
                            <label for="f-table">Table</label>
                            <select id="f-table" name="table">
                                <option value="">All tables</option>
                                <?php foreach ($tables as $tbl): ?>
                                    <option value="<?= htmlspecialchars($tbl) ?>"
                                            <?= $tableFilter===$tbl?'selected':'' ?>>
                                        <?= htmlspecialchars($tbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>-->
                        <?php endif; ?>
                        <button type="submit" class="btn-filter">Apply</button>
                        <a href="system_logs.php" class="btn-reset-filter">✕ Reset</a>
                    </div>

                    <!-- Active filter chips -->
                    <?php if ($search !== '' || $actionFilter !== '' || $tableFilter !== ''): ?>
                        <div class="active-filters">
                            <span style="font-size:11.5px;font-weight:800;color:var(--text-muted);">Active:</span>
                            <?php if ($search !== ''): ?>
                                <span class="af-chip">🔍 <?= htmlspecialchars($search) ?></span>
                            <?php endif; ?>
                            <?php if ($actionFilter !== ''): ?>
                                <span class="af-chip">⚡ <?= htmlspecialchars($actionFilter) ?></span>
                            <?php endif; ?>
                            <?php if ($tableFilter !== ''): ?>
                                <span class="af-chip">📂 <?= htmlspecialchars($tableFilter) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Logs table -->
            <div class="logs-card">
                <div class="logs-card-head">
                    <h2>⚙️ Audit Log</h2>
                    <span class="logs-count"><?= number_format(count($logs)) ?> record<?= count($logs)!==1?'s':'' ?> (max 200)</span>
                </div>
                <div class="table-wrap">
                    <?php if (empty($logs)): ?>
                        <div class="empty-state">
                            <div class="ei">🔍</div>
                            <h3>No logs found</h3>
                            <p>
                                <?= ($search || $actionFilter || $tableFilter)
                                    ? "No logs matched your filters. Try adjusting your search."
                                    : "No system activity has been logged yet." ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Date / Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <!--<th>Table</th>-->
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log):
                                    $aStyle   = action_style($log['action'] ?? '');
                                    $fullName = trim(($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? ''));
                                    $displayName = $fullName !== '' ? $fullName : ($log['username'] ?? 'System');
                                ?>
                                <tr>
                                    <!-- Date -->
                                    <td>
                                        <div class="date-main">
                                            <?= date('M j, Y', strtotime($log['created_at'])) ?>
                                        </div>
                                        <div class="log-id">
                                            <?= date('g:i A', strtotime($log['created_at'])) ?>
                                            · #<?= (int)$log['log_id'] ?>
                                        </div>
                                    </td>
                                    <!-- User -->
                                    <td>
                                        <div class="user-name"><?= htmlspecialchars($displayName) ?></div>
                                        <?php if ($log['username']): ?>
                                            <div class="user-handle">@<?= htmlspecialchars($log['username']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Action badge -->
                                    <td>
                                        <span class="action-badge"
                                              style="background:<?= $aStyle['bg'] ?>;color:<?= $aStyle['color'] ?>;">
                                            <?= htmlspecialchars($log['action'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <!-- Table -->
                                    <!--<td>
                                        <?php if (!empty($log['table_name'])): ?>
                                            <span class="table-pill"><?= htmlspecialchars($log['table_name']) ?></span>
                                            <?php if (!empty($log['record_id'])): ?>
                                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                                                    ID: <?= (int)$log['record_id'] ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>-->
                                    <!-- Description -->
                                    <td class="desc-cell">
                                        <?= nl2br(htmlspecialchars($log['description'] ?? '—')) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content -->

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