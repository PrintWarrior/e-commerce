<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');
$stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_record = $stmt->fetch();
if (!$admin_record) redirect('../login.php');

$admin_id = $admin_record['id'];

// Fetch admin data for sidebar
$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]); $admin = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]); $unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute(); $pending_apps_count = (int)$stmt->fetchColumn();

$success = ''; $error = '';

// ── Handle approve / decline ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id  = (int)$_POST['request_id'];
    $action      = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $status      = $action === 'approve' ? 'approved' : 'declined';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = ?");
        $stmt->execute([$request_id]); $req = $stmt->fetch();
        if (!$req) throw new Exception("Request not found.");

        $stmt = $pdo->prepare("SELECT u.*, CASE WHEN a.id IS NOT NULL THEN 'Admin' WHEN s.id IS NOT NULL THEN 'Seller' WHEN c.id IS NOT NULL THEN 'Customer' ELSE 'User' END AS user_role FROM users u LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN sellers s ON u.id=s.user_id LEFT JOIN customers c ON u.id=c.user_id WHERE u.id=?");
        $stmt->execute([$req['user_id']]); $user = $stmt->fetch();
        if (!$user) throw new Exception("User not found.");

        if ($status === 'approved') {
            $pdo->prepare("UPDATE deletion_requests SET status=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                ->execute([$status, $admin_notes, $admin_id, $request_id]);
            sendEmail($user['email'], "Account Deletion Request Approved",
                "<p>Dear {$user['firstname']},</p><p>Your account deletion request has been <strong>approved</strong>. Your account has now been permanently deleted.</p>");
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$req['user_id']]);
        } else {
            $pdo->prepare("UPDATE deletion_requests SET status=?, admin_notes=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
                ->execute([$status, $admin_notes, $admin_id, $request_id]);
            sendEmail($user['email'], "Account Deletion Request Declined",
                "<p>Dear {$user['firstname']},</p><p>Your deletion request was <strong>declined</strong>. Reason: " . htmlspecialchars($admin_notes) . "</p>");
        }

        createNotification($_SESSION['user_id'], "Deletion request #$request_id $status for {$user['username']}.", 'deletion_request');

        $pdo->commit();
        $_SESSION['flash_success'] = $status === 'approved'
            ? "Request #$request_id approved and the account was deleted immediately."
            : "Request #$request_id has been declined.";
        header('Location: manage_deletions.php'); exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed: " . $e->getMessage();
    }
}

$success = $_SESSION['flash_success'] ?? $success;
unset($_SESSION['flash_success']);

// Fetch pending
$stmt = $pdo->prepare("SELECT dr.*, u.username, u.email, u.firstname, u.lastname, u.created_at AS user_created_at, CASE WHEN a.id IS NOT NULL THEN 'Admin' WHEN s.id IS NOT NULL THEN 'Seller' WHEN c.id IS NOT NULL THEN 'Customer' ELSE 'User' END AS user_role FROM deletion_requests dr JOIN users u ON dr.user_id=u.id LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN sellers s ON u.id=s.user_id LEFT JOIN customers c ON u.id=c.user_id WHERE dr.status='pending' ORDER BY dr.created_at DESC");
$stmt->execute(); $pending = $stmt->fetchAll();

// Fetch processed
$stmt = $pdo->prepare("SELECT dr.*, u.username, u.email, u.firstname, u.lastname, admin_u.username AS admin_username, CASE WHEN a.id IS NOT NULL THEN 'Admin' WHEN s.id IS NOT NULL THEN 'Seller' WHEN c.id IS NOT NULL THEN 'Customer' ELSE 'User' END AS user_role FROM deletion_requests dr JOIN users u ON dr.user_id=u.id LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN sellers s ON u.id=s.user_id LEFT JOIN customers c ON u.id=c.user_id LEFT JOIN admins a_admin ON dr.reviewed_by=a_admin.id LEFT JOIN users admin_u ON a_admin.user_id=admin_u.id WHERE dr.status IN ('approved','declined','cancelled') ORDER BY dr.reviewed_at DESC LIMIT 30");
$stmt->execute(); $processed = $stmt->fetchAll();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deletion Requests | Beauty Mart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../css/admin_deletions.css">
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
                <span class="chip-role">⚙️ Administrator</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php"       class="<?= $current_page==='dashboard.php'?'active':'' ?>"><span class="ni">📊</span> Dashboard</a>
            <a href="manage_users.php"    class="<?= $current_page==='manage_users.php'?'active':'' ?>"><span class="ni">👥</span> Manage Users</a>
            <a href="manage_sellers.php"  class="<?= $current_page==='manage_sellers.php'?'active':'' ?>">
                <span class="ni">🏪</span> Manage Sellers
                <?php if ($pending_apps_count > 0): ?><span class="nbadge"><?= $pending_apps_count ?></span><?php endif; ?>
            </a>
            <a href="products.php" class="<?= $current_page==='products.php'?'active':'' ?>"><span class="ni">🛍️</span> Products</a>
            <a href="orders.php"   class="<?= $current_page==='orders.php'?'active':'' ?>"><span class="ni">📦</span> Orders</a>

            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page==='manage_deletions.php'?'active':'' ?>">
                <span class="ni">🗑️</span> Deletion Requests
                <?php if (count($pending) > 0): ?><span class="nbadge"><?= count($pending) ?></span><?php endif; ?>
            </a>
            <a href="notifications.php"   class="<?= $current_page==='notifications.php'?'active':'' ?>">
                <span class="ni">🔔</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>
            
            <a href="system_logs.php" class="<?= $current_page==='system_logs.php' ? 'active':'' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>

            <div class="nav-lbl">Account</div>
            <a href="profile.php" class="<?= $current_page==='profile.php' ? 'active':'' ?>"><span class="ni">👤</span> My Profile</a>
            <a href="about.php"           class="<?= $current_page==='about.php'?'active':'' ?>"><span class="ni">📝</span> About Menu</a>
            <a href="../logout.php" class="logout"><span class="ni">🚪</span> Logout</a>
        </nav>
    </aside>

    <!-- ── Main ─────────────────────────────────────────────── -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Deletion Requests</h1>
                <p>Review and process user account deletion requests</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if (count($pending) > 0): ?>
                    <span style="background:#fdecea;color:#c0303a;border:1.5px solid #f5b8be;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;">
                        ⚠️ <?= count($pending) ?> pending
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="content">

            <!-- Alerts -->
            <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Summary strip -->
            <?php
            $approved_count  = count(array_filter($processed, fn($r) => $r['status']==='approved'));
            $declined_count  = count(array_filter($processed, fn($r) => $r['status']==='declined'));
            ?>
            <div class="summary-strip">
                <div class="summary-chip">
                    <div class="sc-icon pend">⏳</div>
                    <div><div class="sc-val"><?= count($pending) ?></div><div class="sc-lbl">Pending</div></div>
                </div>
                <div class="summary-chip">
                    <div class="sc-icon appro">✅</div>
                    <div><div class="sc-val"><?= $approved_count ?></div><div class="sc-lbl">Approved</div></div>
                </div>
                <div class="summary-chip">
                    <div class="sc-icon decli">❌</div>
                    <div><div class="sc-val"><?= $declined_count ?></div><div class="sc-lbl">Declined</div></div>
                </div>
            </div>

            <!-- Warning banner -->
            <?php if (count($pending) > 0): ?>
            <div class="warning-banner">
                <span class="wi">⚠️</span>
                <div>
                    <strong>Action required:</strong> There <?= count($pending) === 1 ? 'is' : 'are' ?>
                    <strong><?= count($pending) ?></strong> pending deletion
                    request<?= count($pending) !== 1 ? 's' : '' ?> awaiting your review.
                    Approved requests will permanently delete the user's account immediately.
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Pending Requests ──────────────────────────── -->
            <div class="section-heading">
                🗑️ Pending Requests
                <?php if (count($pending) > 0): ?>
                    <span class="section-count"><?= count($pending) ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($pending)): ?>
                <div class="table-wrap">
                    <div class="empty-state">
                        <div class="ei">✨</div>
                        <h3>All clear!</h3>
                        <p>No pending account deletion requests at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pending as $req):
                    $days_ago = floor((time() - strtotime($req['created_at'])) / 86400);
                ?>
                <div class="req-card">
                    <!-- Header -->
                    <div class="req-card-head">
                        <div>
                            <div class="req-user-name">
                                <?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?>
                                <span class="role-badge role-<?= strtolower($req['user_role']) ?>">
                                    <?= $req['user_role'] ?>
                                </span>
                            </div>
                            <div class="req-user-meta">
                                <span class="uname">@<?= htmlspecialchars($req['username']) ?></span>
                                · <?= htmlspecialchars($req['email']) ?>
                            </div>
                        </div>
                        <div class="req-date">
                            📅 <?= date('M j, Y · g:i A', strtotime($req['created_at'])) ?>
                            <?php if ($days_ago > 0): ?>
                                <span class="days-ago"><?= $days_ago ?> day<?= $days_ago!==1?'s':'' ?> ago</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="req-card-body">
                        <div class="reason-block">
                            <div class="reason-label">📝 Reason for deletion</div>
                            <div class="reason-text"><?= nl2br(htmlspecialchars($req['reason'])) ?></div>
                        </div>

                        <form method="post">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <div class="notes-group">
                                <label for="notes_<?= $req['id'] ?>">
                                    Admin Notes
                                    <span style="color:var(--text-muted);font-weight:600;">(required when declining)</span>
                                </label>
                                <textarea id="notes_<?= $req['id'] ?>" name="admin_notes"
                                          placeholder="Provide reason for your decision…"></textarea>
                            </div>
                            <div class="btn-group">
                                <button type="submit" name="action" value="approve" class="btn-approve"
                                        onclick="return confirm('⚠️ Approve this deletion request?\n\nThe user\'s account will be deleted immediately.\n\nThis action cannot be undone. Continue?')">
                                    ✅ Approve Deletion
                                </button>
                                <button type="submit" name="action" value="decline" class="btn-decline"
                                        onclick="return confirm('Decline this deletion request?\n\nThe user will be notified via email with your admin notes.')">
                                    ❌ Decline Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- ── Processed Requests ────────────────────────── -->
            <div class="section-heading">
                📋 Processed Requests
                <?php if (count($processed) > 0): ?>
                    <span class="section-count" style="background:var(--text-muted);"><?= count($processed) ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($processed)): ?>
                <div class="table-wrap">
                    <div class="empty-state">
                        <div class="ei">📋</div>
                        <h3>No processed requests yet</h3>
                        <p>Approved and declined requests will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Admin Notes</th>
                                <th>Reviewed By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processed as $r): ?>
                            <tr>
                                <td>
                                    <div class="uname-cell"><?= htmlspecialchars($r['firstname'].' '.$r['lastname']) ?></div>
                                    <div class="sub">@<?= htmlspecialchars($r['username']) ?></div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?= strtolower($r['user_role']) ?>">
                                        <?= $r['user_role'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($r['email']) ?></td>
                                <td>
                                    <div class="truncate" title="<?= htmlspecialchars($r['reason']) ?>">
                                        <?= htmlspecialchars(substr($r['reason'], 0, 60)) ?>
                                        <?= strlen($r['reason']) > 60 ? '…' : '' ?>
                                    </div>
                                </td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td>
                                    <div class="truncate" title="<?= htmlspecialchars($r['admin_notes'] ?? '') ?>">
                                        <?= htmlspecialchars(substr($r['admin_notes'] ?? '—', 0, 55)) ?>
                                        <?= strlen($r['admin_notes'] ?? '') > 55 ? '…' : '' ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r['admin_username'] ?? 'System') ?></td>
                                <td>
                                    <?= $r['reviewed_at']
                                        ? date('M j, Y', strtotime($r['reviewed_at']))
                                        : '<span style="color:var(--text-muted)">—</span>' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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
