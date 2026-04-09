<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstname = trim((string)($_POST['firstname'] ?? ''));
        $lastname = trim((string)($_POST['lastname'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($firstname === '' || $lastname === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'text' => 'Please provide valid profile details.'];
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $flash = ['type' => 'error', 'text' => 'Username or email is already in use.'];
            } else {
                $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ?, email = ? WHERE id = ?")
                    ->execute([$firstname, $lastname, $username, $email, $_SESSION['user_id']]);
                $_SESSION['username'] = $username;
                $flash = ['type' => 'success', 'text' => 'Profile updated successfully.'];
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($currentPassword, $admin['password'])) {
            $flash = ['type' => 'error', 'text' => 'Current password is incorrect.'];
        } elseif (strlen($newPassword) < 6) {
            $flash = ['type' => 'error', 'text' => 'New password must be at least 6 characters.'];
        } elseif ($newPassword !== $confirmPassword) {
            $flash = ['type' => 'error', 'text' => 'Password confirmation does not match.'];
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $flash = ['type' => 'success', 'text' => 'Password changed successfully.'];
        }
    }

    if (isset($_POST['upload_profile_pic']) && isset($_FILES['profile_pic'])) {
        $dir = '../uploads/profile_images/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!empty($_FILES['profile_pic']['tmp_name'])) {
            $name = time() . '_' . basename($_FILES['profile_pic']['name']);
            $dest = $dir . $name;
            $ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
            if (getimagesize($_FILES['profile_pic']['tmp_name']) === false) {
                $flash = ['type' => 'error', 'text' => 'Uploaded file is not an image.'];
            } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $flash = ['type' => 'error', 'text' => 'Allowed image types: JPG, JPEG, PNG, GIF.'];
            } elseif (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                if (!empty($admin['profile_pic']) && file_exists($dir . $admin['profile_pic'])) {
                    @unlink($dir . $admin['profile_pic']);
                }
                $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$name, $_SESSION['user_id']]);
                $flash = ['type' => 'success', 'text' => 'Profile picture updated successfully.'];
            } else {
                $flash = ['type' => 'error', 'text' => 'Failed to upload profile picture.'];
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int)$stmt->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Beauty Mart Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_profile.css">
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <img src="../images/logo.png" alt="Logo" onerror="this.style.display='none';this.parentElement.textContent='*'">
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
                <span class="chip-role">Administrator</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php" class="<?= $current_page==='dashboard.php' ? 'active':'' ?>">
                <span class="ni">📊</span> Dashboard
            </a>
            <a href="manage_users.php" class="<?= $current_page==='manage_users.php' ? 'active':'' ?>">
                <span class="ni">👥</span> Manage Users
            </a>
            <a href="manage_sellers.php" class="<?= $current_page==='manage_sellers.php' ? 'active':'' ?>">
                <span class="ni">🏪</span> Manage Sellers
                <?php if ($pending_apps > 0): ?><span class="nbadge"><?= $pending_apps ?></span><?php endif; ?>
            </a>
            <a href="products.php" class="<?= $current_page==='products.php' ? 'active':'' ?>">
                <span class="ni">🛍️</span> Products
            </a>
            <a href="orders.php" class="<?= $current_page==='orders.php' ? 'active':'' ?>">
                <span class="ni">📦</span> Orders
            </a>

            <div class="nav-lbl">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page==='manage_deletions.php' ? 'active':'' ?>">
                <span class="ni">🗑️</span> Deletion Requests
            </a>
            <a href="notifications.php" class="<?= $current_page==='notifications.php' ? 'active':'' ?>">
                <span class="ni">🔔</span> Notifications
                <?php if ($unread_count > 0): ?><span class="nbadge"><?= $unread_count ?></span><?php endif; ?>
            </a>
            <a href="about.php" class="<?= $current_page==='about.php' ? 'active':'' ?>">
                <span class="ni">📝</span> About Menu
            </a>

            <div class="nav-lbl">Account</div>
            <a href="profile.php" class="<?= $current_page==='profile.php' ? 'active':'' ?>">
                <span class="ni">👤</span> My Profile
            </a>
            <a href="../logout.php" class="logout">
                <span class="ni">🚪</span> Logout
            </a>    
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>My Profile</h1>
                <p>Manage your admin identity, credentials, and profile image.</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--pink-accent);color:#fff;border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;"><?= $unread_count ?> unread</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['text']) ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                <div class="dash-card">
                    <div class="card-head"><h2>Profile Overview</h2></div>
                    <div class="card-body">
                        <div class="profile-avatar-wrap">
                            <?php if (!empty($admin['profile_pic']) && file_exists('../uploads/profile_images/' . $admin['profile_pic'])): ?>
                                <img class="avatar" src="../uploads/profile_images/<?= htmlspecialchars($admin['profile_pic']) ?>" alt="Profile picture">
                            <?php else: ?>
                                <div class="avatar"><?= strtoupper(substr($admin['firstname'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div class="profile-meta">
                                <div class="profile-name"><?= htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']) ?></div>
                                <div class="profile-email"><?= htmlspecialchars($admin['email']) ?></div>
                                <span class="profile-chip">Administrator</span>
                            </div>
                            <form method="post" enctype="multipart/form-data" style="width:100%;display:grid;gap:12px;">
                                <div class="field">
                                    <label>Profile Picture</label>
                                    <input type="file" name="profile_pic" accept=".jpg,.jpeg,.png,.gif" style="height:auto;padding:10px;">
                                </div>
                                <button type="submit" name="upload_profile_pic" class="btn-secondary">Upload Picture</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:24px;">
                    <div class="dash-card">
                        <div class="card-head"><h2>Update Profile</h2></div>
                        <div class="card-body">
                            <form method="post" class="form-grid">
                                <div class="field">
                                    <label>First Name</label>
                                    <input type="text" name="firstname" value="<?= htmlspecialchars($admin['firstname']) ?>" required>
                                </div>
                                <div class="field">
                                    <label>Last Name</label>
                                    <input type="text" name="lastname" value="<?= htmlspecialchars($admin['lastname']) ?>" required>
                                </div>
                                <div class="field">
                                    <label>Username</label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
                                </div>
                                <div class="field">
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                </div>
                                <div class="field full">
                                    <button type="submit" name="update_profile" class="btn-primary">Save Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div class="card-head"><h2>Change Password</h2></div>
                        <div class="card-body">
                            <form method="post" class="form-grid">
                                <div class="field full">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                <div class="field">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required>
                                </div>
                                <div class="field">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" required>
                                </div>
                                <div class="field full">
                                    <button type="submit" name="change_password" class="btn-secondary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="admin-footer">
            <div class="footer-inner">
                <div class="footer-copy">Copyright &copy; 2025 <span>Beauty Mart</span>. Admin panel.</div>
                <div class="footer-copy">Profile center</div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
