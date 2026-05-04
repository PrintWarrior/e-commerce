<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdminOrSuperadmin()) redirect('../login.php');

$stmt = $pdo->prepare("
    SELECT u.*,
           a.id AS admin_id,
           sa.id AS superadmin_id
    FROM users u
    LEFT JOIN admins a ON u.id = a.user_id
    LEFT JOIN superadmins sa ON u.id = sa.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
$viewer_label = !empty($admin['superadmin_id']) ? 'Superadmin' : 'Administrator';
$acting_admin_id = $admin['admin_id'] ?: null;

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $firstname = trim((string) ($_POST['firstname'] ?? ''));
    $lastname = trim((string) ($_POST['lastname'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? 'customer'));
    $emailVerified = (int) ($_POST['email_verified'] ?? 0);
    $businessName = trim((string) ($_POST['business_name'] ?? ''));
    $businessAddress = trim((string) ($_POST['business_address'] ?? ''));
    $businessPhone = trim((string) ($_POST['business_phone'] ?? ''));
    $businessTaxId = trim((string) ($_POST['business_tax_id'] ?? ''));

    $errors = [];
    if ($firstname === '') $errors[] = 'First name is required.';
    if ($lastname === '') $errors[] = 'Last name is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['admin', 'customer', 'seller'], true)) $errors[] = 'Invalid role selected.';
    if (!in_array($emailVerified, [0, 1], true)) $errors[] = 'Invalid email verification value.';

    if ($role === 'seller') {
        if ($businessName === '') $errors[] = 'Business name is required for seller accounts.';
        if ($businessAddress === '') $errors[] = 'Business address is required for seller accounts.';
        if ($businessPhone === '') $errors[] = 'Business phone is required for seller accounts.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            $verificationToken = $emailVerified === 1 ? null : bin2hex(random_bytes(32));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (firstname, lastname, username, email, password, email_verified, verification_token)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $firstname,
                $lastname,
                $username,
                $email,
                $hashedPassword,
                $emailVerified,
                $verificationToken
            ]);

            $userId = (int) $pdo->lastInsertId();

            if ($role === 'admin') {
                $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")->execute([$userId]);
            } elseif ($role === 'customer') {
                $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)")->execute([$userId]);
            } else {
                $pdo->prepare("
                    INSERT INTO sellers (user_id, business_name, business_address, phone, tax_id, approved_by, approved_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ")->execute([
                    $userId,
                    $businessName,
                    $businessAddress,
                    $businessPhone,
                    $businessTaxId !== '' ? $businessTaxId : null,
                    $acting_admin_id
                ]);
            }

            logSystemEvent(
                'admin_user_created',
                'users',
                $userId,
                "{$viewer_label} created {$role} account for {$username} ({$email}).",
                (int) $_SESSION['user_id']
            );

            $pdo->commit();
            $flash = ['type' => 'success', 'text' => ucfirst($role) . " account for {$username} was created successfully."];
            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $flash = ['type' => 'error', 'text' => 'Failed to create user: ' . $e->getMessage()];
        }
    } else {
        $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
    }
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM seller_applications WHERE status='pending'");
$stmt->execute();
$pending_apps = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = (int) $stmt->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_users.css">
    <link rel="stylesheet" href="../css/admin_dash.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
<div class="shell">
    <input type="checkbox" id="admin-menu-toggle" class="admin-menu-toggle">
    <label for="admin-menu-toggle" class="admin-hamburger">
        <span></span><span></span><span></span>
    </label>

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
                <span class="chip-role"><?= htmlspecialchars($viewer_label) ?></span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-lbl">Main</div>
            <a href="dashboard.php" class="<?= $current_page==='dashboard.php' ? 'active':'' ?>">
                <span class="ni">📊</span> Dashboard
            </a>
            <a href="create_users.php" class="<?= $current_page==='create_users.php' ? 'active':'' ?>">
                <span class="ni">➕</span> Create User
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
            <a href="system_logs.php" class="<?= $current_page==='system_logs.php' ? 'active':'' ?>">
                <span class="ni">⚙️</span> System Logs
            </a>

            <div class="nav-lbl">Account</div>
            <a href="profile.php" class="<?= $current_page==='profile.php' ? 'active':'' ?>">
                <span class="ni">👤</span> My Profile
            </a>
            
            <a href="about.php" class="<?= $current_page==='about.php' ? 'active':'' ?>">
                <span class="ni">📝</span> About Menu
            </a>
            <a href="../logout.php" class="logout">
                <span class="ni">🚪</span> Logout
            </a>    
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>Create User</h1>
                <p>Use a dedicated page for new admin, customer, and seller accounts.</p>
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

            <section class="workspace-hero">
                <div class="hero-card">
                    <div class="hero-kicker">Create Flow</div>
                    <h2>Open a new account without leaving the admin workspace.</h2>
                    <p class="hero-copy">This page keeps account creation separate from user maintenance. Use it for onboarding admins, customers, and approved seller accounts.</p>
                    <div class="hero-pills">
                        <span class="hero-pill">Separate create workflow</span>
                        <span class="hero-pill">Seller business fields included</span>
                        <span class="hero-pill">Verification status set on creation</span>
                    </div>
                    <div class="hero-actions">
                        <a href="manage_users.php" class="btn-secondary">Back To Manage Users</a>
                    </div>
                </div>
                <div class="hero-sidecard">
                    <h3>Before You Save</h3>
                    <p>Choose the correct role first. Seller accounts require business name, address, and phone. Admin and customer accounts only need core profile fields.</p>
                    <div class="mini-stats">
                        <div class="mini-stat">
                            <strong><?= number_format($pending_apps) ?></strong>
                            <span>Seller Requests</span>
                        </div>
                        <div class="mini-stat">
                            <strong><?= number_format($unread_count) ?></strong>
                            <span>Unread Alerts</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="dash-card editor-shell">
                <div class="card-head">
                    <div>
                        <div class="section-label">Create</div>
                        <h2>New Account</h2>
                        <div class="section-copy">Create one user at a time with the required role details.</div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" class="form-grid">
                        <div class="field">
                            <label for="create_firstname">First Name</label>
                            <input id="create_firstname" type="text" name="firstname" value="<?= htmlspecialchars((string) ($_POST['firstname'] ?? '')) ?>" required>
                        </div>
                        <div class="field">
                            <label for="create_lastname">Last Name</label>
                            <input id="create_lastname" type="text" name="lastname" value="<?= htmlspecialchars((string) ($_POST['lastname'] ?? '')) ?>" required>
                        </div>
                        <div class="field">
                            <label for="create_username">Username</label>
                            <input id="create_username" type="text" name="username" value="<?= htmlspecialchars((string) ($_POST['username'] ?? '')) ?>" required>
                        </div>
                        <div class="field">
                            <label for="create_email">Email</label>
                            <input id="create_email" type="email" name="email" value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>" required>
                        </div>
                        <div class="field">
                            <label for="create_password">Password</label>
                            <input id="create_password" type="password" name="password" required>
                        </div>
                        <div class="field">
                            <label for="create_confirm_password">Confirm Password</label>
                            <input id="create_confirm_password" type="password" name="confirm_password" required>
                        </div>
                        <div class="field">
                            <label for="create_role">Role</label>
                            <select id="create_role" name="role">
                                <?php foreach (['customer' => 'Customer', 'seller' => 'Seller', 'admin' => 'Admin'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= ($_POST['role'] ?? 'customer') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="create_email_verified">Verification</label>
                            <select id="create_email_verified" name="email_verified">
                                <option value="0" <?= ((string) ($_POST['email_verified'] ?? '0')) === '0' ? 'selected' : '' ?>>Unverified</option>
                                <option value="1" <?= ((string) ($_POST['email_verified'] ?? '0')) === '1' ? 'selected' : '' ?>>Verified</option>
                            </select>
                        </div>
                        <div class="field full">
                            <label for="create_business_name">Business Name</label>
                            <input id="create_business_name" type="text" name="business_name" value="<?= htmlspecialchars((string) ($_POST['business_name'] ?? '')) ?>" placeholder="Seller only">
                        </div>
                        <div class="field">
                            <label for="create_business_phone">Business Phone</label>
                            <input id="create_business_phone" type="text" name="business_phone" value="<?= htmlspecialchars((string) ($_POST['business_phone'] ?? '')) ?>" placeholder="Seller only">
                        </div>
                        <div class="field">
                            <label for="create_business_tax_id">Tax ID</label>
                            <input id="create_business_tax_id" type="text" name="business_tax_id" value="<?= htmlspecialchars((string) ($_POST['business_tax_id'] ?? '')) ?>" placeholder="Optional">
                        </div>
                        <div class="field full">
                            <label for="create_business_address">Business Address</label>
                            <textarea id="create_business_address" name="business_address" placeholder="Seller only"><?= htmlspecialchars((string) ($_POST['business_address'] ?? '')) ?></textarea>
                            <div class="field-note">Seller fields are only required when creating a seller account.</div>
                        </div>
                        <div class="field full">
                            <div class="detail-actions">
                                <button type="submit" name="create_user" class="btn-primary">Create User</button>
                                <a href="manage_users.php" class="btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer class="admin-footer">
            <div class="footer-inner">
                <div class="footer-copy">Copyright &copy; 2025 <span>Beauty Mart</span>. Admin panel.</div>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
