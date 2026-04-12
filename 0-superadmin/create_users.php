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

        /* ── Content Grid ────────────────────────────────────────────── */
        .content-grid {
          display: grid;
          grid-template-columns: 2fr 1fr;
          gap: 20px;
        }

        @media (max-width: 1000px) {
          .content-grid {
            grid-template-columns: 1fr;
          }
        }

        /* ── Form Card ───────────────────────────────────────────────── */
        .form-card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          padding: 28px;
          display: flex;
          flex-direction: column;
          gap: 20px;
        }

        .section-title {
          font-family: "Playfair Display", serif;
          font-size: 18px;
          font-weight: 700;
          color: var(--text-dark);
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
          background: #e8f5e9;
          color: #2e7d32;
          border-left-color: #4caf50;
        }

        .flash.error {
          background: #ffebee;
          color: #c62828;
          border-left-color: #f44336;
        }

        /* ── Form Grid ───────────────────────────────────────────────– */
        .form-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 16px;
        }

        .field.full {
          grid-column: 1 / -1;
        }

        .field {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        .field label {
          font-size: 13px;
          font-weight: 700;
          color: var(--text-dark);
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }

        .field input,
        .field select,
        .field textarea {
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

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
          border-color: var(--pink-accent);
          background: #fff;
        }

        .field textarea {
          resize: vertical;
          min-height: 80px;
        }

        .field input::placeholder {
          color: var(--text-muted);
        }

        .hint {
          font-size: 11px;
          color: var(--text-muted);
          margin-top: 2px;
        }

        /* ── Seller Fields ───────────────────────────────────────────– */
        .seller-fields {
          grid-column: 1 / -1;
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 16px;
          padding: 16px;
          background: var(--pink-light);
          border-radius: 10px;
          border: 1.5px solid var(--pink-mid);
        }

        .seller-fields.hidden {
          display: none;
        }

        .seller-fields .field.full {
          grid-column: 1 / -1;
        }

        /* ── Actions ──────────────────────────────────────────────────– */
        .actions {
          display: flex;
          gap: 12px;
          margin-top: 12px;
          border-top: 1.5px solid var(--pink-mid);
          padding-top: 18px;
        }

        .btn-primary,
        .btn-secondary {
          padding: 12px 20px;
          border-radius: 10px;
          font-size: 14px;
          font-weight: 700;
          border: none;
          cursor: pointer;
          font-family: "Nunito", sans-serif;
          transition: all 0.2s;
          text-align: center;
          display: inline-block;
        }

        .btn-primary {
          background: var(--pink-accent);
          color: #fff;
          border: 1.5px solid var(--pink-accent);
        }

        .btn-primary:hover {
          background: var(--pink-dark);
          border-color: var(--pink-dark);
        }

        .btn-secondary {
          background: #fff;
          color: var(--pink-accent);
          border: 1.5px solid var(--pink-accent);
        }

        .btn-secondary:hover {
          background: var(--pink-light);
        }

        /* ── Help Card ───────────────────────────────────────────────– */
        .help-card {
          background: #fff;
          border-radius: var(--radius);
          border: 1.5px solid var(--pink-mid);
          box-shadow: var(--shadow);
          padding: 28px;
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .help-card ul {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .help-card li {
          font-size: 13px;
          color: var(--text-mid);
          line-height: 1.6;
          padding-left: 6px;
          border-left: 3px solid var(--pink-accent);
          padding: 0 0 0 12px;
        }

        .help-card li strong {
          color: var(--pink-accent);
          font-weight: 700;
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

          .form-grid {
            grid-template-columns: 1fr;
          }

          .seller-fields {
            grid-template-columns: 1fr;
          }

          .actions {
            flex-direction: column;
          }

          .btn-primary,
          .btn-secondary {
            width: 100%;
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
            <a href="create_users.php" class="<?= $current_page === 'create_users.php' ? 'active' : '' ?>">Create Users</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="products.php">Manage Products</a>
            <a href="system_logs.php">System Logs</a>
            <a href="notifications.php">Notifications<?= $unread_count > 0 ? ' (' . $unread_count . ')' : '' ?></a>
            <a href="../logout.php">Logout</a>
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
                <h2 class="section-title">New Account Form</h2>

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
                <h2 class="section-title">How This Works</h2>
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
