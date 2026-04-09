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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = $emailVerified === 1 ? null : bin2hex(random_bytes(32));

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
                $stmt = $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            } elseif ($role === 'customer') {
                $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            } elseif ($role === 'seller') {
                $stmt = $pdo->prepare("
                    INSERT INTO sellers (user_id, business_name, business_address, phone, tax_id, approved_by, approved_at)
                    VALUES (?, ?, ?, ?, ?, NULL, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $businessName,
                    $businessAddress,
                    $businessPhone,
                    $businessTaxId !== '' ? $businessTaxId : null
                ]);
            }

            logSystemEvent(
                'superadmin_user_created',
                'users',
                $userId,
                "Superadmin created {$role} account for {$username} ({$email}) with email_verified={$emailVerified}."
            );

            $pdo->commit();

            $flash = [
                'type' => 'success',
                'text' => ucfirst($role) . " account for {$username} was created successfully."
            ];

            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $flash = [
                'type' => 'error',
                'text' => 'Failed to create user: ' . $e->getMessage()
            ];
        }
    } else {
        $flash = [
            'type' => 'error',
            'text' => implode(' ', $errors)
        ];
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Users | Beauty Mart Superadmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --cream: #fffaf2;
            --gold: #d4a24c;
            --gold-deep: #b98426;
            --ink: #2f2417;
            --muted: #7c6b56;
            --line: #ecdcc0;
            --card: #ffffff;
            --soft: #f7efe1;
            --danger-bg: #fdecea;
            --danger-border: #f2c6cc;
            --danger-text: #b4434f;
            --success-bg: #eaf7ee;
            --success-border: #cce7d4;
            --success-text: #2f7a4d;
            --shadow: 0 16px 35px rgba(120, 82, 29, 0.10);
            --radius: 18px;
            --sidebar: 270px;
        }
        body { min-height: 100vh; font-family: 'Nunito', sans-serif; background: radial-gradient(circle at top right, rgba(212,162,76,.18), transparent 28%), linear-gradient(180deg, #fffdf8, var(--cream)); color: var(--ink); }
        a { color: inherit; text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: var(--sidebar); background: rgba(255,255,255,.92); backdrop-filter: blur(10px);
            border-right: 1px solid var(--line); padding: 22px 16px; display: flex; flex-direction: column; gap: 18px;
        }
        .brand, .profile, .form-card, .help-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
        .brand { display: flex; align-items: center; gap: 12px; padding: 14px; }
        .brand-icon { width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(135deg, var(--gold), var(--gold-deep)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; overflow: hidden; }
        .brand-icon img { width: 100%; height: 100%; object-fit: cover; }
        .brand-title { font-family: 'Playfair Display', serif; font-size: 18px; }
        .brand-sub { color: var(--muted); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .profile { padding: 16px; display: flex; gap: 12px; align-items: center; }
        .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--gold), var(--gold-deep)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile small { display: inline-block; margin-top: 4px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; color: var(--gold-deep); background: var(--soft); border: 1px solid var(--line); }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav a { padding: 13px 14px; border-radius: 14px; font-weight: 700; color: var(--muted); border: 1px solid transparent; transition: .18s ease; }
        .nav a:hover, .nav a.active { background: var(--soft); border-color: var(--line); color: var(--ink); transform: translateX(2px); }
        .main { flex: 1; min-width: 0; padding: 26px; display: flex; flex-direction: column; gap: 22px; }
        .hero {
            background: linear-gradient(135deg, rgba(212,162,76,.18), rgba(255,255,255,.94));
            border: 1px solid var(--line); border-radius: 26px; padding: 28px; box-shadow: var(--shadow);
            display: flex; justify-content: space-between; gap: 18px; align-items: center;
        }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 34px; margin-bottom: 8px; }
        .hero p { color: var(--muted); max-width: 760px; line-height: 1.6; }
        .hero-badges { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-badges span { padding: 10px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--line); font-weight: 800; font-size: 13px; }
        .content-grid { display: grid; grid-template-columns: 1.3fr .7fr; gap: 20px; }
        .form-card { padding: 24px; }
        .help-card { padding: 22px; }
        .section-title { font-size: 18px; font-weight: 900; margin-bottom: 18px; }
        .flash { padding: 14px 16px; border-radius: 14px; margin-bottom: 18px; font-weight: 700; line-height: 1.5; }
        .flash.success { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
        .flash.error { background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger-text); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .field.full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 800; color: var(--ink); }
        input, select, textarea {
            width: 100%; padding: 13px 14px; border-radius: 14px; border: 1px solid var(--line);
            background: #fff; color: var(--ink); font: inherit;
        }
        textarea { min-height: 110px; resize: vertical; }
        .hint { margin-top: 6px; color: var(--muted); font-size: 12px; }
        .seller-fields {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            padding: 16px;
            border: 1px dashed var(--line);
            border-radius: 16px;
            background: #fffdfa;
        }
        .seller-fields.hidden { display: none; }
        .actions { margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-primary, .btn-secondary {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 13px 18px; border-radius: 14px; font-weight: 800; border: 1px solid var(--line);
            cursor: pointer; font: inherit;
        }
        .btn-primary { background: linear-gradient(135deg, var(--gold), var(--gold-deep)); color: #fff; border: none; }
        .btn-secondary { background: #fff; color: var(--ink); }
        .help-card ul { padding-left: 18px; color: var(--muted); line-height: 1.8; }
        .help-card strong { color: var(--ink); }
        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } }
        @media (max-width: 840px) {
            .shell { flex-direction: column; }
            .sidebar { width: 100%; }
            .main { padding: 18px; }
            .hero { flex-direction: column; align-items: flex-start; }
            .form-grid, .seller-fields { grid-template-columns: 1fr; }
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
            <a href="dashboard.php">Dashboard</a>
            <a href="create_users.php" class="<?= $current_page === 'create_users.php' ? 'active' : '' ?>">Create Users</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main">
        <section class="hero">
            <div>
                <h1>Create Users</h1>
                <p>Create new `admin`, `customer`, or `seller` accounts directly from the superadmin panel. Superadmin creation is intentionally not available here.</p>
            </div>
            <div class="hero-badges">
                <span><?= date('F j, Y') ?></span>
                <span>Email verified accepts `0` or `1`</span>
                <span><?= number_format($unread_count) ?> unread notifications</span>
            </div>
        </section>

        <section class="content-grid">
            <div class="form-card">
                <div class="section-title">New Account Form</div>

                <?php if ($flash): ?>
                    <div class="flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($flash['text']) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-grid">
                        <div class="field">
                            <label for="firstname">First Name</label>
                            <input id="firstname" name="firstname" type="text" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="lastname">Last Name</label>
                            <input id="lastname" name="lastname" type="text" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" required>
                            <div class="hint">Minimum 6 characters.</div>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Confirm Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" required>
                        </div>
                        <div class="field">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <?php $selectedRole = $_POST['role'] ?? 'customer'; ?>
                                <option value="customer" <?= $selectedRole === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="seller" <?= $selectedRole === 'seller' ? 'selected' : '' ?>>Seller</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="email_verified">Email Verified</label>
                            <select id="email_verified" name="email_verified" required>
                                <?php $selectedVerified = (string) ($_POST['email_verified'] ?? '0'); ?>
                                <option value="0" <?= $selectedVerified === '0' ? 'selected' : '' ?>>0 - Not Verified</option>
                                <option value="1" <?= $selectedVerified === '1' ? 'selected' : '' ?>>1 - Verified</option>
                            </select>
                        </div>

                        <div id="seller-fields" class="seller-fields <?= $selectedRole === 'seller' ? '' : 'hidden' ?>">
                            <div class="field">
                                <label for="business_name">Business Name</label>
                                <input id="business_name" name="business_name" type="text" value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label for="business_phone">Business Phone</label>
                                <input id="business_phone" name="business_phone" type="text" value="<?= htmlspecialchars($_POST['business_phone'] ?? '') ?>">
                            </div>
                            <div class="field full">
                                <label for="business_address">Business Address</label>
                                <textarea id="business_address" name="business_address"><?= htmlspecialchars($_POST['business_address'] ?? '') ?></textarea>
                            </div>
                            <div class="field full">
                                <label for="business_tax_id">Tax ID</label>
                                <input id="business_tax_id" name="business_tax_id" type="text" value="<?= htmlspecialchars($_POST['business_tax_id'] ?? '') ?>">
                                <div class="hint">Optional for seller creation.</div>
                            </div>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" name="create_user" class="btn-primary">Create User</button>
                        <a href="dashboard.php" class="btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>

            <aside class="help-card">
                <div class="section-title">How This Works</div>
                <ul>
                    <li><strong>Admin</strong> creates a row in `users` and `admins`.</li>
                    <li><strong>Customer</strong> creates a row in `users` and `customers`.</li>
                    <li><strong>Seller</strong> creates a row in `users` and `sellers` directly.</li>
                    <li><strong>Superadmin</strong> cannot be created from this page.</li>
                    <li>If `email_verified` is `0`, a verification token is generated automatically.</li>
                </ul>
            </aside>
        </section>
    </main>
</div>

<script>
    const roleSelect = document.getElementById('role');
    const sellerFields = document.getElementById('seller-fields');
    const sellerInputs = sellerFields.querySelectorAll('input, textarea');

    function toggleSellerFields() {
        const isSeller = roleSelect.value === 'seller';
        sellerFields.classList.toggle('hidden', !isSeller);

        sellerInputs.forEach((input) => {
            if (input.id === 'business_tax_id') {
                input.required = false;
                return;
            }
            input.required = isSeller;
        });
    }

    roleSelect.addEventListener('change', toggleSellerFields);
    toggleSellerFields();
</script>
</body>
</html>
