<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$user_id = $_SESSION['user_id'];

// Mark single as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([$_GET['mark_read'], $user_id]);
    $redirect_params = array_filter([
        'filter' => $_GET['filter'] ?? 'all',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? '',
    ]);
    header('Location: notifications.php' . ($redirect_params ? '?' . http_build_query($redirect_params) : '')); exit;
}

// Delete single
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")
        ->execute([$_GET['delete'], $user_id]);
    $redirect_params = array_filter([
        'filter' => $_GET['filter'] ?? 'all',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? '',
    ]);
    header('Location: notifications.php' . ($redirect_params ? '?' . http_build_query($redirect_params) : '')); exit;
}

// Mark all read (PRG)
if (isset($_POST['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
        ->execute([$user_id]);
    $_SESSION['flash_success'] = "All notifications marked as read.";
    $redirect_params = array_filter([
        'filter' => $_GET['filter'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? '',
    ]);
    header('Location: notifications.php' . ($redirect_params ? '?' . http_build_query($redirect_params) : '')); exit;
}

// Delete all read (PRG)
if (isset($_POST['delete_read'])) {
    $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1")
        ->execute([$user_id]);
    $_SESSION['flash_success'] = "Read notifications cleared.";
    $redirect_params = array_filter([
        'filter' => $_GET['filter'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? '',
    ]);
    header('Location: notifications.php' . ($redirect_params ? '?' . http_build_query($redirect_params) : '')); exit;
}

$success = $_SESSION['flash_success'] ?? ''; unset($_SESSION['flash_success']);

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$notifications_per_page = 10;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $notifications_per_page;

$query_base  = " FROM notifications n LEFT JOIN notification_types nt ON n.notification_type_id = nt.id WHERE n.user_id = ?";
$params = [$user_id];
if ($filter === 'unread') { $query_base .= " AND n.is_read = 0"; }
elseif ($filter === 'read') { $query_base .= " AND n.is_read = 1"; }
if ($search) {
    $query_base  .= " AND (n.message LIKE ? OR nt.code LIKE ? OR nt.label LIKE ?)";
    $sp      = "%$search%";
    $params  = array_merge($params, [$sp, $sp, $sp]);
}

$count_stmt = $pdo->prepare("SELECT COUNT(*)" . $query_base);
$count_stmt->execute($params);
$filtered_total = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($filtered_total / $notifications_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $notifications_per_page;
}

$query  = "SELECT n.*, nt.code AS type_code, nt.label AS type_label" . $query_base . " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
$stmt   = $pdo->prepare($query);
$notification_params = [...$params, $notifications_per_page, $offset];
$param_index = 1;
foreach ($notification_params as $value) {
    $stmt->bindValue($param_index++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$notifications = $stmt->fetchAll();

// Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]); $unread_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]); $total_count = (int)$stmt->fetchColumn();

$read_count = $total_count - $unread_count;
$pagination_params = array_filter([
    'filter' => $filter !== 'all' ? $filter : '',
    'search' => $search,
]);

// Icon + color map per type
function notif_meta(string $type): array {
    return match($type) {
        'order'              => ['icon' => '📦', 'color' => '#2255cc', 'bg' => '#dce8ff', 'label' => 'Order'],
        'payout'             => ['icon' => '💰', 'color' => '#1a7f4b', 'bg' => '#d1f5e0', 'label' => 'Payout'],
        'seller_application' => ['icon' => '📝', 'color' => '#7322cc', 'bg' => '#f3e0ff', 'label' => 'Application'],
        'new_user'           => ['icon' => '👤', 'color' => '#c75473', 'bg' => '#fce8ee', 'label' => 'New User'],
        'system'             => ['icon' => '🔔', 'color' => '#856404', 'bg' => '#fff3cd', 'label' => 'System'],
        'deletion_request'   => ['icon' => '⚠️', 'color' => '#c0303a', 'bg' => '#fdecea', 'label' => 'Deletion'],
        default              => ['icon' => '📌', 'color' => '#555',    'bg' => '#f5f5f5', 'label' => 'Notice'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_notifications.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="seller-wrapper">

    <?php /* Sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Notifications</h1>
                <p>Stay updated with your store activity</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;">
                        <?= $unread_count ?> unread
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">

            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Summary strip -->
            <div class="notif-summary">
                <div class="summary-chip">
                    <div class="chip-icon all">🔔</div>
                    <div>
                        <div class="chip-val"><?= $total_count ?></div>
                        <div class="chip-label">Total</div>
                    </div>
                </div>
                <div class="summary-chip">
                    <div class="chip-icon unread">🟡</div>
                    <div>
                        <div class="chip-val"><?= $unread_count ?></div>
                        <div class="chip-label">Unread</div>
                    </div>
                </div>
                <div class="summary-chip">
                    <div class="chip-icon read">✅</div>
                    <div>
                        <div class="chip-val"><?= $read_count ?></div>
                        <div class="chip-label">Read</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="notif-toolbar">
                <!-- Filter pills -->
                <div class="filter-pills">
                    <a href="?filter=all<?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">
                        All <span class="cnt"><?= $total_count ?></span>
                    </a>
                    <a href="?filter=unread<?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="filter-pill <?= $filter === 'unread' ? 'active' : '' ?>">
                        Unread <span class="cnt"><?= $unread_count ?></span>
                    </a>
                    <a href="?filter=read<?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="filter-pill <?= $filter === 'read' ? 'active' : '' ?>">
                        Read <span class="cnt"><?= $read_count ?></span>
                    </a>
                </div>

                <!-- Actions + search -->
                <div class="toolbar-actions">
                    <?php if ($unread_count > 0): ?>
                        <form method="post" style="display:contents;">
                            <button type="submit" name="mark_all_read" class="btn-mark-all">
                                ✓ Mark all read
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($read_count > 0): ?>
                        <form method="post" style="display:contents;"
                              onsubmit="return confirm('Clear all read notifications?')">
                            <button type="submit" name="delete_read" class="btn-clear-read">
                                🗑 Clear read
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="get" class="search-form">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <input type="text" name="search"
                               placeholder="Search notifications…"
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
            </div>

            <!-- Notifications list -->
            <div class="notif-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-notif">
                        <div class="ei">🔕</div>
                        <h3>
                            <?= $filter === 'unread' ? 'No unread notifications'
                                : ($filter === 'read' ? 'No read notifications'
                                : 'No notifications yet') ?>
                        </h3>
                        <p>
                            <?= $search
                                ? "No results for \"" . htmlspecialchars($search) . "\"."
                                : "You're all caught up!" ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $n):
                        $meta = notif_meta(getNotificationCode($n));
                    ?>
                    <div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">

                        <!-- Icon bubble -->
                        <div class="notif-icon-bubble"
                             style="background:<?= $meta['bg'] ?>; border:1.5px solid <?= $meta['color'] ?>33;">
                            <?= $meta['icon'] ?>
                        </div>

                        <!-- Body -->
                        <div class="notif-body">
                            <div class="notif-row-top">
                                <span class="notif-type-badge"
                                      style="background:<?= $meta['bg'] ?>; color:<?= $meta['color'] ?>;">
                                    <?= $meta['label'] ?>
                                </span>
                                <?php if (!$n['is_read']): ?>
                                    <span class="notif-unread-dot" title="Unread"></span>
                                <?php endif; ?>
                            </div>
                            <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
                            <div class="notif-time">
                                🕐 <?= date('F j, Y · g:i A', strtotime($n['created_at'])) ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="notif-actions">
                            <?php if (!$n['is_read']): ?>
                                <a href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page, 'mark_read' => $n['id']])) ?>"
                                   class="notif-action-btn mark-read"
                                   title="Mark as read">✓</a>
                            <?php endif; ?>
                            <a href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page, 'delete' => $n['id']])) ?>"
                               class="notif-action-btn delete"
                               title="Delete"
                               onclick="return confirm('Delete this notification?')">🗑</a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page - 1])) ?>">‹</a>
                    <?php else: ?>
                        <span class="page-link disabled">‹</span>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                        <a class="page-link <?= $page === $current_page ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $page])) ?>"><?= $page ?></a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page + 1])) ?>">›</a>
                    <?php else: ?>
                        <span class="page-link disabled">›</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div><!-- /page-content -->

        <!-- Footer -->
        <footer class="seller-footer">
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

    </div><!-- /main-content -->
</div><!-- /seller-wrapper -->

</body>
</html>
