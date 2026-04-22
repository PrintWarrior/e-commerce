<?php
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->rowCount() == 0) redirect('../login.php');

// Fetch admin data
$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Stats - Fixed: Get counts from the subtables instead of user_type column
$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM sellers");
$total_sellers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $stmt->fetchColumn();

// Notifications
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]); 
$unread_notifs = (int)$stmt->fetchColumn();

$success = ''; 
$error = '';

// ── Profile picture upload ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $dir  = '../uploads/profile_images/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = time() . '_' . basename($_FILES['profile_pic']['name']);
    $dest = $dir . $name;
    $ext  = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
    if (getimagesize($_FILES['profile_pic']['tmp_name']) === false) {
        $error = "File is not an image.";
    } elseif (!in_array($ext, ['jpg','jpeg','png','gif'])) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif ($_FILES['profile_pic']['size'] > 5000000) {
        $error = "File is too large. Max 5MB allowed.";
    } elseif (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
        if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.jpg' && file_exists($dir . $admin['profile_pic']))
            unlink($dir . $admin['profile_pic']);
        $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$name, $_SESSION['user_id']]);
        $_SESSION['profile_image'] = $name;
        $_SESSION['flash_success'] = "Profile picture updated!";
        header('Location: update.php'); exit;
    } else { $error = "Upload failed. Check folder permissions."; }
}

// ── Delete picture ────────────────────────────────────────────
if (isset($_POST['delete_pic'])) {
    $dir = '../uploads/profile_images/';
    if (!empty($admin['profile_pic']) && $admin['profile_pic'] !== 'default.jpg' && file_exists($dir . $admin['profile_pic'])) {
        unlink($dir . $admin['profile_pic']);
    }
    $pdo->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
    unset($_SESSION['profile_image']);
    $_SESSION['flash_success'] = "Profile picture removed.";
    header('Location: update.php'); exit;
}

// ── Update profile ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fn = trim($_POST['firstname']); 
    $ln = trim($_POST['lastname']);
    $un = trim($_POST['username']);  
    $em = trim($_POST['email']);
    $errs = [];
    if (empty($fn)) $errs[] = "First name is required.";
    if (empty($ln)) $errs[] = "Last name is required.";
    if (empty($un)) $errs[] = "Username is required.";
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errs[] = "Valid email is required.";
    if (empty($errs)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=?");
        $stmt->execute([$un, $em, $_SESSION['user_id']]);
        if ($stmt->rowCount()) $errs[] = "Username or email already taken.";
    }
    if (empty($errs)) {
        $pdo->prepare("UPDATE users SET firstname=?, lastname=?, username=?, email=? WHERE id=?")
            ->execute([$fn, $ln, $un, $em, $_SESSION['user_id']]);
        $_SESSION['username'] = $un;
        $_SESSION['flash_success'] = "Profile updated successfully!";
        header('Location: update.php'); exit;
    } else { $error = implode('<br>', $errs); }
}

$success = $_SESSION['flash_success'] ?? $success;
unset($_SESSION['flash_success']);

// Refresh
$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id FROM users u JOIN admins a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]); 
$admin = $stmt->fetch();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../css/admin_about.css">
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

        <!-- Admin identity -->
        <div class="admin-chip">
            <div class="chip-avatar">
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
            <div class="nav-section-label">Main</div>
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
                <span class="nav-icon">👥</span> Manage Users
            </a>
            <a href="manage_sellers.php" class="<?= $current_page === 'manage_sellers.php' ? 'active' : '' ?>">
                <span class="nav-icon">🏪</span> Manage Sellers
            </a>
            <a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">
                <span class="nav-icon">🛍️</span> Products
            </a>
            <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span> Orders
            </a>

            <div class="nav-section-label">Management</div>
            <a href="manage_deletions.php" class="<?= $current_page === 'manage_deletions.php' ? 'active' : '' ?>">
                <span class="nav-icon">🗑️</span> Deletion Requests
            </a>
            <a href="notifications.php" class="<?= $current_page === 'notifications.php' ? 'active' : '' ?>">
                <span class="nav-icon">🔔</span> Notifications
                <?php if ($unread_notifs > 0): ?>
                    <span class="nav-badge"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </a>
            <a href="system_logs.php" class="<?= $current_page==='system_logs.php' ? 'active':'' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>
            

            <div class="nav-section-label">Account</div>
            <a href="profile.php" class="<?= $current_page==='profile.php' ? 'active':'' ?>">
                <span class="nav-icon">👤</span> My Profile
            </a>
            <a href="about.php" class="<?= $current_page === 'about.php' ? 'active' : '' ?>">
                <span class="nav-icon">📝</span> About Menu
            </a>
            <a href="../logout.php" class="logout">
                <span class="nav-icon">🚪</span> Logout
            </a>
        </nav>
    </aside>

    <!-- ── Main ─────────────────────────────────────────────── -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Admin Profile</h1>
                <p>Manage your profile information and picture</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
        <div class="content-wrap">

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= $error ?></div>
            <?php endif; ?>

            <!-- ── Profile Picture ──────────────────────────── -->
            <div class="sec-card">
                <div class="sec-header">
                    
                    <h2> </h2>
                </div>
                <div class="sec-body">
                    <div class="avatar-section">
                        <div class="avatar-lg">
                            <?php if (!empty($admin['profile_pic']) && file_exists("../uploads/profile_images/" . $admin['profile_pic'])): ?>
                                <img src="../uploads/profile_images/<?= htmlspecialchars($admin['profile_pic']) ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($admin['firstname'], 0, 1)) . strtoupper(substr($admin['lastname'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <h2>Jayle Luza Talan</h2>
                        <div class="about-body">
                    <p class="about-text">
                        Hi! im Jayle, from Tangub City studied at NMSCST a BSIT course, a CEO of Beauty Mart
                    </p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <span style="background:var(--pink-pale);border:1.5px solid var(--pink-mid);border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;color:var(--pink-accent);">PHP</span>
                        <span style="background:var(--pink-pale);border:1.5px solid var(--pink-mid);border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;color:var(--pink-accent);">MySQL</span>
                        <span style="background:var(--pink-pale);border:1.5px solid var(--pink-mid);border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;color:var(--pink-accent);">HTML / CSS</span>
                        <span style="background:var(--pink-pale);border:1.5px solid var(--pink-mid);border-radius:20px;padding:4px 14px;font-size:12.5px;font-weight:800;color:var(--pink-accent);">JavaScript</span>
                    </div>
                </div>
                        <!--<div class="avatar-btns">
                            <form method="post" enctype="multipart/form-data" style="display:contents;">
                                <input type="file" name="profile_pic" id="profile_pic"
                                       accept="image/*" style="display:none;"
                                       onchange="validateAndSubmit(this)">
                                <button type="button" class="btn-upload"
                                        onclick="document.getElementById('profile_pic').click()">
                                    📸 Upload New Picture
                                </button>
                            </form>
                            <?php if (!empty($admin['profile_pic'])): ?>
                                <form method="post" style="display:contents;"
                                      onsubmit="return confirm('Delete your profile picture?')">
                                    <button type="submit" name="delete_pic" class="btn-del-pic">
                                        🗑 Delete Picture
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <p class="upload-note">Supported: JPG, JPEG, PNG, GIF · Max 5MB</p>-->
                    </div>
                </div>
            </div>

            <!-- ── Personal Information ─────────────────────── -->
            <!--<div class="sec-card">
                <div class="sec-header">
                    <span>👤</span>
                    <h2>Personal Information</h2>
                </div>
                <div class="sec-body">
                    <form method="post" autocomplete="off">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstname">First Name <span class="req">*</span></label>
                                <input type="text" id="firstname" name="firstname"
                                       value="<?= htmlspecialchars($admin['firstname']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lastname">Last Name <span class="req">*</span></label>
                                <input type="text" id="lastname" name="lastname"
                                       value="<?= htmlspecialchars($admin['lastname']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="username">Username <span class="req">*</span></label>
                            <input type="text" id="username" name="username"
                                   value="<?= htmlspecialchars($admin['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span class="req">*</span></label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>
                </div>
            </div>-->

            <!-- ── Admin Statistics ─────────────────────────── -->
            <!--<div class="sec-card">
                <div class="sec-header">
                    <span>📊</span>
                    <h2>Platform Statistics</h2>
                </div>
                <div class="sec-body">
                    <div class="stats-grid">
                        <div class="stat-chip">
                            <div class="sc-icon">👥</div>
                            <div class="sc-val"><?= number_format($total_customers) ?></div>
                            <div class="sc-label">Customers</div>
                        </div>
                        <div class="stat-chip">
                            <div class="sc-icon">🏪</div>
                            <div class="sc-val"><?= number_format($total_sellers) ?></div>
                            <div class="sc-label">Sellers</div>
                        </div>
                        <div class="stat-chip">
                            <div class="sc-icon">🛍️</div>
                            <div class="sc-val"><?= number_format($total_products) ?></div>
                            <div class="sc-label">Products</div>
                        </div>
                        <div class="stat-chip">
                            <div class="sc-icon">📦</div>
                            <div class="sc-val"><?= number_format($total_orders) ?></div>
                            <div class="sc-label">Orders</div>
                        </div>
                    </div>
                </div>
            </div>-->

            <!-- ── Account Info ──────────────────────────────── -->
            <!--<div class="sec-card">
                <div class="sec-header">
                    <span>🔐</span>
                    <h2>Account Info</h2>
                </div>
                <div class="sec-body" style="padding:0;">
                    <table class="meta-table">
                        <tr>
                            <td>Admin ID</td>
                            <td>#<?= $admin['admin_id'] ?></td>
                        </tr>
                        <tr>
                            <td>Account Created</td>
                            <td><?= date('F j, Y', strtotime($admin['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td>Last Updated</td>
                            <td><?= date('F j, Y', strtotime($admin['updated_at'])) ?></td>
                        </tr>
                        <tr>
                            <td>Email Verified</td>
                            <td>
                                <?php if ($admin['email_verified']): ?>
                                    <span class="meta-verified">✓ Verified</span>
                                <?php else: ?>
                                    <span class="meta-not-verified">✗ Not Verified</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Role</td>
                            <td>⚙️ Administrator</td>
                        </tr>
                    </table>
                </div>
            </div>-->

            <!-- ── About the Developer ──────────────────────── -->
            <!--<div class="sec-card">
                <div class="sec-header">
                    <span>💻</span>
                    
                </div>
                
            </div>-->

        </div>
        </div><!-- /content -->

        <!-- Footer -->
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

<script>
    function validateAndSubmit(input) {
        if (!input.files || !input.files[0]) return;
        const size = input.files[0].size / 1024 / 1024;
        if (size > 5) { alert('File is too large. Maximum size is 5MB.'); input.value = ''; return; }
        input.form.submit();
    }
</script>

</body>
</html>
