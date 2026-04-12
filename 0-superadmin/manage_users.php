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
          --success-bg: #e8f5e9;
          --success-border: #4caf50;
          --success-text: #2e7d32;
          --warning-bg: #e3f2fd;
          --warning-border: #2196f3;
          --warning-text: #1565c0;
          --danger-bg: #ffebee;
          --danger-border: #f44336;
          --danger-text: #c62828;
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

        /* ── Flash Messages ──────────────────────────────────────────– */
        .flash {
          padding: 14px 18px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 600;
          border-left: 4px solid;
        }

        .flash.success {
          background: var(--success-bg);
          color: var(--success-text);
          border-left-color: var(--success-border);
        }

        .flash.error {
          background: var(--danger-bg);
          color: var(--danger-text);
          border-left-color: var(--danger-border);
        }

        /* ── Stats Grid ──────────────────────────────────────────────– */
        .stats {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 16px;
        }

        @media (max-width: 1200px) {
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

        /* ── Cards ───────────────────────────────────────────────────── */
        .card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          padding: 22px;
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        /* ── Filter Form ──────────────────────────────────────────────– */
        .filters {
          display: grid;
          grid-template-columns: 2fr 1fr 1fr auto;
          gap: 14px;
          grid-auto-flow: dense;
        }

        .filters > div {
          display: flex;
          flex-direction: column;
        }

        .filters > div:last-child {
          align-self: flex-end;
        }

        label {
          font-size: 13px;
          font-weight: 700;
          color: var(--text-dark);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 6px;
        }

        input,
        select,
        textarea {
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
        select:focus,
        textarea:focus {
          border-color: var(--pink-accent);
          background: #fff;
        }

        textarea {
          resize: vertical;
          min-height: 80px;
        }

        /* ── Buttons ──────────────────────────────────────────────────── */
        .btn,
        .btn-soft,
        .btn-warn,
        .btn-danger {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 11px 15px;
          border-radius: 10px;
          border: 1.5px solid;
          cursor: pointer;
          font: inherit;
          font-weight: 700;
          transition: all 0.2s;
        }

        .btn {
          background: var(--pink-accent);
          color: #fff;
          border-color: var(--pink-accent);
        }

        .btn:hover:not(:disabled) {
          background: var(--pink-dark);
          border-color: var(--pink-dark);
        }

        .btn-soft {
          background: var(--pink-light);
          color: var(--pink-accent);
          border-color: var(--pink-mid);
        }

        .btn-soft:hover:not(:disabled) {
          background: var(--pink-mid);
          border-color: var(--pink-accent);
        }

        .btn-warn {
          background: var(--warning-bg);
          color: var(--warning-text);
          border-color: var(--warning-border);
        }

        .btn-warn:hover:not(:disabled) {
          background: var(--warning-border);
          color: #fff;
        }

        .btn-danger {
          background: var(--danger-bg);
          color: var(--danger-text);
          border-color: var(--danger-border);
        }

        .btn-danger:hover:not(:disabled) {
          background: var(--danger-border);
          color: #fff;
        }

        /* ── Stack Layouts ───────────────────────────────────────────– */
        .stack {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          align-items: center;
        }

        .action-stack {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        /* ── Pills/Badges ────────────────────────────────────────────– */
        .pill {
          display: inline-block;
          padding: 6px 12px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: 700;
          background: var(--pink-light);
          color: var(--pink-accent);
          border: 1px solid var(--pink-mid);
        }

        .pill.status-active {
          background: var(--success-bg);
          color: var(--success-text);
          border-color: var(--success-border);
        }

        .pill.status-suspended {
          background: var(--warning-bg);
          color: var(--warning-text);
          border-color: var(--warning-border);
        }

        .pill.status-banned {
          background: var(--danger-bg);
          color: var(--danger-text);
          border-color: var(--danger-border);
        }

        /* ── Table Styles ────────────────────────────────────────────– */
        .table-wrap {
          overflow-x: auto;
        }

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

        /* ── User Cell ───────────────────────────────────────────────– */
        .user-cell {
          display: flex;
          gap: 12px;
          align-items: center;
        }

        .user-thumb {
          width: 44px;
          height: 44px;
          border-radius: 50%;
          background: var(--pink-light);
          border: 1px solid var(--pink-mid);
          display: flex;
          align-items: center;
          justify-content: center;
          overflow: hidden;
          font-weight: 800;
          flex-shrink: 0;
        }

        .user-thumb img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .meta {
          color: var(--text-muted);
          font-size: 12px;
        }

        /* ── Layout Grid for Tables & Details ────────────────────────– */
        .layout {
          display: grid;
          grid-template-columns: 1.5fr 1fr;
          gap: 20px;
        }

        @media (max-width: 1000px) {
          .layout {
            grid-template-columns: 1fr;
          }
        }

        /* ── Detail Header ───────────────────────────────────────────– */
        .detail-header {
          display: flex;
          gap: 14px;
          align-items: flex-start;
          margin-bottom: 14px;
        }

        .detail-pic {
          width: 110px;
          height: 110px;
          border-radius: 14px;
          overflow: hidden;
          border: 1.5px solid var(--pink-mid);
          background: var(--pink-light);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 28px;
          font-weight: 900;
          flex-shrink: 0;
        }

        .detail-pic img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        /* ── Detail Grids ────────────────────────────────────────────– */
        .detail-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 14px;
          margin-top: 14px;
        }

        .detail-grid.full {
          grid-template-columns: 1fr;
        }

        .detail-box {
          padding: 14px;
          border-radius: 10px;
          background: #fff;
          border: 1.5px solid var(--pink-mid);
          font-size: 13px;
        }

        .detail-box strong {
          display: block;
          margin-bottom: 6px;
          font-size: 11px;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        /* ── Edit Grid ───────────────────────────────────────────────– */
        .edit-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 14px;
        }

        .edit-grid .field {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        .edit-form,
        .status-form {
          display: grid;
          gap: 12px;
        }

        .edit-form > div,
        .status-form > div {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        /* ── Section Heading ──────────────────────────────────────────– */
        .section-head {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--text-dark);
        }

        .empty {
          color: var(--text-muted);
          padding: 12px 0;
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

          .filters {
            grid-template-columns: 1fr;
          }

          .filters > div:last-child {
            align-self: stretch;
          }

          .filters .btn {
            width: 100%;
          }

          .edit-grid,
          .detail-grid {
            grid-template-columns: 1fr;
          }

          .stats {
            grid-template-columns: 1fr;
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
            <a href="manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : '' ?>">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="../logout.php">Logout</a>
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
                <div><button type="submit" class="btn">Apply Filters</button></div>
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
                            <div><div style="font-size:20px;font-weight:900;margin-bottom:6px;"><?= htmlspecialchars($viewUser['firstname'] . ' ' . $viewUser['lastname']) ?></div><div class="meta">@<?= htmlspecialchars($viewUser['username']) ?></div><div class="stack" style="margin-top:12px;"><span class="pill"><?= htmlspecialchars($viewUser['user_role']) ?></span><span class="pill status-<?= htmlspecialchars($viewUser['status'] ?? 'active') ?>"><?= htmlspecialchars(ucfirst($viewUser['status'] ?? 'active')) ?></span></div></div>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-box"><strong>Email</strong><div><?= htmlspecialchars($viewUser['email']) ?></div></div>
                            <div class="detail-box"><strong>Email Verified</strong><div><?= (int) $viewUser['email_verified'] === 1 ? '1 - Yes' : '0 - No' ?></div></div>
                            <div class="detail-box"><strong>Created</strong><div><?= date('M j, Y g:i A', strtotime($viewUser['created_at'])) ?></div></div>
                            <div class="detail-box"><strong>Last Status Action By</strong><div><?= htmlspecialchars($viewUser['action_by_username'] ?? 'None') ?></div></div>
                            <div class="detail-box"><strong>Suspended Until</strong><div><?= $viewUser['suspended_until'] ? date('M j, Y g:i A', strtotime($viewUser['suspended_until'])) : 'Not set' ?></div></div>
                            <div class="detail-box"><strong>Reason</strong><div><?= htmlspecialchars($viewUser['banned_reason'] ?? 'None') ?></div></div>
                            <?php if ($viewUser['user_role'] === 'Seller'): ?>
                                <div class="detail-box"><strong>Business Name</strong><div><?= htmlspecialchars($viewUser['business_name'] ?? 'N/A') ?></div></div>
                                <div class="detail-box"><strong>Business Phone</strong><div><?= htmlspecialchars($viewUser['seller_phone'] ?? 'N/A') ?></div></div>
                                <div class="detail-box" style="grid-column:1 / -1;"><strong>Business Address</strong><div><?= htmlspecialchars($viewUser['business_address'] ?? 'N/A') ?></div></div>
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
                                <div class="field"><label for="firstname">First Name</label><input id="firstname" type="text" name="firstname" value="<?= htmlspecialchars($editUser['firstname']) ?>" required></div>
                                <div class="field"><label for="lastname">Last Name</label><input id="lastname" type="text" name="lastname" value="<?= htmlspecialchars($editUser['lastname']) ?>" required></div>
                                <div class="field"><label for="username">Username</label><input id="username" type="text" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required></div>
                                <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required></div>
                                <div class="field"><label for="email_verified">Email Verified</label><select id="email_verified" name="email_verified"><option value="1" <?= (int) $editUser['email_verified'] === 1 ? 'selected' : '' ?>>1 - Verified</option><option value="0" <?= (int) $editUser['email_verified'] === 0 ? 'selected' : '' ?>>0 - Not Verified</option></select></div>
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
                                <div><label for="status_reason_suspend">Suspend Reason</label><textarea id="status_reason_suspend" name="status_reason" placeholder="Optional reason for suspension"></textarea></div>
                                <div><label for="suspended_until">Suspend Until</label><input id="suspended_until" type="datetime-local" name="suspended_until"></div>
                                <button type="submit" name="suspend_user" class="btn-warn" <?= (int) $viewUser['id'] === (int) $_SESSION['user_id'] ? 'disabled' : '' ?>>Suspend User</button>
                            </form>
                            <form method="post" class="status-form">
                                <input type="hidden" name="user_id" value="<?= (int) $viewUser['id'] ?>">
                                <div><label for="status_reason_ban">Ban Reason</label><textarea id="status_reason_ban" name="status_reason" placeholder="Reason for ban" required></textarea></div>
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
