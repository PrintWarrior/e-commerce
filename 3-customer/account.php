<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Get user data with customer information
$stmt = $pdo->prepare("
    SELECT u.*, c.id as customer_id 
    FROM users u 
    LEFT JOIN customers c ON u.id = c.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If customer doesn't exist, create it
if (!$user['customer_id']) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    // Refresh user data
    $stmt = $pdo->prepare("
        SELECT u.*, c.id as customer_id 
        FROM users u 
        LEFT JOIN customers c ON u.id = c.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Get pending deletion request
$stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_request = $stmt->fetch();

// Handle password change
if (isset($_POST['change_password'])) {
    $cur = $_POST['current_password'];
    $new = $_POST['new_password'];
    $con = $_POST['confirm_password'];

    if (!password_verify($cur, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $con) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
        $success = "Password updated successfully!";
    }
}

// Handle account deletion request
if (isset($_POST['request_deletion'])) {
    $reason = trim($_POST['deletion_reason'] ?? '');
    $other  = trim($_POST['deletion_reason_other'] ?? '');
    if ($reason === 'Other' && $other) $reason = $other;

    if (empty($reason)) {
        $error = "Please provide a reason for account deletion.";
    } elseif ($pending_request) {
        $error = "You already have a pending deletion request.";
    } else {
        $pdo->prepare("INSERT INTO deletion_requests (user_id, reason, status) VALUES (?, ?, 'pending')")
            ->execute([$user_id, $reason]);
        
        // Get admin user IDs from admins table
        $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN admins a ON u.id = a.user_id");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $message = "User {$user['username']} ({$user['email']}) has requested account deletion. Reason: $reason";
        foreach ($admins as $admin_id) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'deletion_request')");
            $stmt->execute([$admin_id, $message]);
        }
        
        $success = "Your account deletion request has been submitted. An admin will review it shortly.";
        
        // Refresh pending request
        $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $pending_request = $stmt->fetch();
    }
}

// Handle cancel deletion
if (isset($_POST['cancel_deletion'])) {
    $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'cancelled' WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $success = "Your deletion request has been cancelled.";
    $pending_request = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/customer_account.css">
    <link rel="icon" href="../images/logo.png" type="image/png">
</head>
<body>

    <!-- Top accent strip -->
    <div class="top-strip"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="inner">
            <div class="left">
                <a href="../about.php">About Us</a>
                <span class="sep">|</span>
                <a href="../contact.php">Contact Us</a>
            </div>
            <div class="right">
                <a href="profile.php">
                    <div class="avatar-sm">
                        <?php if (!empty($_SESSION['profile_image']) && file_exists("../uploads/profile_images/" . $_SESSION['profile_image'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </a>
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="inner">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <img src="../images/logo.png" alt="Logo"
                         onerror="this.style.display='none';this.parentElement.textContent='🌸'">
                </div>
                <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
            </a>

            <div class="nav-spacer"></div>

            <div class="nav-icons">
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                        <polyline points="9 21 9 12 15 12 15 21"/>
                    </svg>
                </a>
                <a href="orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <path d="M8 21h8M12 17v4"/>
                    </svg>
                </a>
                <a href="wishlist.php" title="Wishlist">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>
                <a href="cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page -->
    <div class="page">
        <div class="settings-card">
            <h1 class="card-title">Customer Account Settings</h1>
            <hr class="title-line">

            <?php if ($success): ?>
                <div class="alert alert-success"><span>✓</span><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><span>⚠</span><?= $error ?></div>
            <?php endif; ?>

            <?php if ($pending_request): ?>
                <div class="pending-banner">
                    <h4>⚠ Pending Account Deletion Request</h4>
                    <p>Submitted on <?= date('F j, Y', strtotime($pending_request['created_at'])) ?>.</p>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($pending_request['reason']) ?></p>
                    <p>Your request is under admin review. You'll be notified by email once a decision is made.</p>
                    <form method="post" onsubmit="return confirm('Cancel your deletion request?')">
                        <button type="submit" name="cancel_deletion" class="btn-outline-danger">Cancel Deletion Request</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ── Update Password ─────────────────────────────── -->
            <h2 class="section-heading">Update Password</h2>

            <form method="post" autocomplete="off">
                <div class="field">
                    <input type="password" name="current_password"
                           placeholder="Current password" required autocomplete="off">
                </div>
                <div class="field">
                    <input type="password" name="new_password"
                           placeholder="New password" required autocomplete="off">
                </div>
                <div class="field">
                    <input type="password" name="confirm_password"
                           placeholder="Re-type new password" required autocomplete="off">
                </div>
                <button type="submit" name="change_password" class="btn-pink">Update Password</button>
            </form>

            <hr class="section-divider">

            <!-- ── Delete Account ─────────────────────────────── -->
            <h2 class="section-heading">Delete Account</h2>

            <p class="delete-note">
                Deleted accounts are reviewed by the admin and can be restored within 60 days.
            </p>

            <?php if (!$pending_request): ?>
            <form method="post" onsubmit="return confirmDeletion()">
                <div class="field" style="display:none;">
                    <select name="deletion_reason" id="deletion_reason">
                        <option value="No longer need the account" selected>No longer need the account</option>
                        <option value="Found a better alternative">Found a better alternative</option>
                        <option value="Privacy concerns">Privacy concerns</option>
                        <option value="Not satisfied with service">Not satisfied with service</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <button type="submit" name="request_deletion" class="btn-danger">Request Account Deletion</button>
            </form>
            <?php endif; ?>

        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
            <div class="socials">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Website">🌐</a>
                <a href="#" title="LinkedIn">in</a>
            </div>
        </div>
    </footer>

    <script>
        function confirmDeletion() {
            return confirm('⚠️ WARNING: This action is irreversible!\n\nAre you sure you want to request account deletion?\n\nClick OK to proceed.');
        }
    </script>

</body>
</html>