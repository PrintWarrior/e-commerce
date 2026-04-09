<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$user_id   = $_SESSION['user_id'];
$seller_id = $seller['seller_id'];

// Fetch seller data
$stmt = $pdo->prepare("
    SELECT u.*, s.business_name, s.business_address,
           s.phone AS business_phone, s.tax_id
    FROM users u
    JOIN sellers s ON u.id = s.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$sd = $stmt->fetch();

$success = ''; 
$error = '';

// ── Handle profile picture upload ───────────────────────────────
if (isset($_POST['upload_image'])) {
    $target_dir = "../uploads/profile_images/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file = $_FILES['profile_pic'];
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $file_name;
    $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (getimagesize($file['tmp_name']) === false) {
        $error = "File is not an image.";
    } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif ($file['size'] > 5000000) {
        $error = "File is too large. Max 5MB allowed.";
    } else {
        // Delete old picture if exists
        if (!empty($sd['profile_pic']) && file_exists($target_dir . $sd['profile_pic'])) {
            unlink($target_dir . $sd['profile_pic']);
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$file_name, $user_id]);
            $_SESSION['profile_image'] = $file_name;
            $success = "Profile picture uploaded successfully!";
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT u.*, s.business_name, s.business_address, s.phone AS business_phone, s.tax_id FROM users u JOIN sellers s ON u.id = s.user_id WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $sd = $stmt->fetch();
        } else {
            $error = "Failed to upload image.";
        }
    }
}

// ── Handle profile picture deletion ─────────────────────────────
if (isset($_POST['delete_image'])) {
    $target_dir = "../uploads/profile_images/";
    if (!empty($sd['profile_pic']) && file_exists($target_dir . $sd['profile_pic'])) {
        unlink($target_dir . $sd['profile_pic']);
        $pdo->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?")->execute([$user_id]);
        unset($_SESSION['profile_image']);
        $success = "Profile picture deleted successfully!";
        
        // Refresh data
        $stmt = $pdo->prepare("SELECT u.*, s.business_name, s.business_address, s.phone AS business_phone, s.tax_id FROM users u JOIN sellers s ON u.id = s.user_id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $sd = $stmt->fetch();
    } else {
        $error = "No profile picture to delete.";
    }
}

// ── Handle shop info update ───────────────────────────────────
if (isset($_POST['update_shop'])) {
    $bn  = trim($_POST['business_name']);
    $ba  = trim($_POST['business_address']);
    $bp  = trim($_POST['business_phone']);
    $tax = trim($_POST['tax_id']);

    $errs = [];
    if (empty($bn)) $errs[] = "Business name is required.";
    if (empty($ba)) $errs[] = "Business address is required.";
    if (empty($bp)) $errs[] = "Business phone is required.";

    if (empty($errs)) {
        $pdo->prepare("UPDATE sellers SET business_name=?, business_address=?, phone=?, tax_id=? WHERE id=?")
            ->execute([$bn, $ba, $bp, $tax, $seller_id]);
        $_SESSION['flash_success'] = "Shop information updated successfully!";
        header('Location: account.php'); exit;
    } else { $error = implode("<br>", $errs); }
}

// ── Handle personal info update ───────────────────────────────
if (isset($_POST['update_personal'])) {
    $fn  = trim($_POST['firstname']);
    $ln  = trim($_POST['lastname']);
    $un  = trim($_POST['username']);
    $em  = trim($_POST['email']);

    $errs = [];
    if (empty($fn)) $errs[] = "First name is required.";
    if (empty($ln)) $errs[] = "Last name is required.";
    if (empty($un)) $errs[] = "Username is required.";
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errs[] = "Invalid email format.";

    if (empty($errs)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=?");
        $stmt->execute([$un, $em, $user_id]);
        if ($stmt->rowCount()) {
            $error = "Username or email is already taken.";
        } else {
            $pdo->prepare("UPDATE users SET firstname=?, lastname=?, username=?, email=? WHERE id=?")
                ->execute([$fn, $ln, $un, $em, $user_id]);
            $_SESSION['username'] = $un;
            $_SESSION['flash_success'] = "Personal information updated successfully!";
            header('Location: account.php'); exit;
        }
    } else { $error = implode("<br>", $errs); }
}

// ── Handle password change ────────────────────────────────────
if (isset($_POST['change_password'])) {
    $cur = $_POST['current_password'];
    $new = $_POST['new_password'];
    $con = $_POST['confirm_password'];

    if (!password_verify($cur, $sd['password']))  $error = "Current password is incorrect.";
    elseif (strlen($new) < 6)                      $error = "New password must be at least 6 characters.";
    elseif ($new !== $con)                         $error = "Passwords do not match.";
    else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
        $_SESSION['flash_success'] = "Password changed successfully!";
        header('Location: account.php'); exit;
    }
}

$success = $_SESSION['flash_success'] ?? $success;
unset($_SESSION['flash_success']);

// Refresh data after any successful POST
$stmt = $pdo->prepare("SELECT u.*, s.business_name, s.business_address, s.phone AS business_phone, s.tax_id FROM users u JOIN sellers s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$sd = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_account.css">
    <style>
        /* Profile Picture Styles */
        .profile-pic-section {
            background: var(--white);
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .profile-pic-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 22px;
            background: var(--pink-pale);
            border-bottom: 1.5px solid var(--pink-mid);
        }
        
        .profile-pic-header .section-icon {
            font-size: 20px;
        }
        
        .profile-pic-header h2 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
        }
        
        .profile-pic-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--pink-accent);
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 48px;
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(232,114,142,0.2);
            transition: transform 0.3s;
        }
        
        .avatar-large:hover {
            transform: scale(1.02);
        }
        
        .avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn-upload-pic {
            background: var(--pink-pale);
            color: var(--pink-accent);
            border: 1.5px solid var(--pink-mid);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-upload-pic:hover {
            background: var(--pink-accent);
            color: #fff;
            border-color: var(--pink-accent);
            transform: translateY(-1px);
        }
        
        .btn-delete-pic {
            background: #fdecea;
            color: #c0303a;
            border: 1.5px solid #f5b8be;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-delete-pic:hover {
            background: #c0303a;
            color: #fff;
            border-color: #c0303a;
            transform: translateY(-1px);
        }
        
        .upload-note {
            font-size: 11px;
            color: var(--text-muted);
            text-align: center;
            margin-top: 5px;
        }
        
        /* Seller hero section - updated to include profile pic */
        .seller-hero {
            background: var(--white);
            border: 1.5px solid var(--pink-mid);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .hero-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pink-accent), var(--pink-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 32px;
            font-weight: 800;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .hero-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .hero-info h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .hero-info p {
            color: var(--text-mid);
            margin: 2px 0;
            font-size: 13px;
        }
        
        .hero-badge {
            display: inline-block;
            background: var(--pink-pale);
            color: var(--pink-accent);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }
        
        @media (max-width: 600px) {
            .seller-hero {
                flex-direction: column;
                text-align: center;
            }
            
            .avatar-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-upload-pic, .btn-delete-pic {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="seller-wrapper">

    <?php /* Sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Account Settings</h1>
                <p>Manage your shop details, personal info, and security</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
                <a href="../logout.php" class="topbar-btn-outline">Logout</a>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">
        <div class="settings-wrap">

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">⚠ <?= $error ?></div>
            <?php endif; ?>

            <!-- Seller identity hero -->
            <div class="seller-hero">
                <div class="hero-avatar">
                    <?php if (!empty($sd['profile_pic']) && file_exists("../uploads/profile_images/" . $sd['profile_pic'])): ?>
                        <img src="../uploads/profile_images/<?= htmlspecialchars($sd['profile_pic']) ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($sd['firstname'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="hero-info">
                    <h2><?= htmlspecialchars($sd['business_name'] ?? $sd['username']) ?></h2>
                    <p><?= htmlspecialchars($sd['email']) ?></p>
                    <p><?= htmlspecialchars($sd['firstname'] . ' ' . $sd['lastname']) ?></p>
                    
                    <div class="avatar-buttons">
                        <form method="post" enctype="multipart/form-data" style="display:contents;">
                            <input type="file" name="profile_pic" id="profile_pic" 
                                   accept="image/*" style="display:none;"
                                   onchange="validateAndSubmit(this)">
                            <button type="button" class="btn-upload-pic" 
                                    onclick="document.getElementById('profile_pic').click()">
                                📸 Upload New Picture
                            </button>
                            <input type="hidden" name="upload_image" value="1">
                        </form>
                        
                        <?php if (!empty($sd['profile_pic'])): ?>
                            <form method="post" style="display:contents;"
                                  onsubmit="return confirm('Delete your profile picture?')">
                                <button type="submit" name="delete_image" class="btn-delete-pic">
                                    🗑️ Delete Picture
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Shop Information ──────────────────────── -->
            <div class="section-card">
                <div class="section-card-header">
                    <span class="section-icon">🏪</span>
                    <h2>Shop Information</h2>
                </div>
                <div class="section-card-body">
                    <form method="post" autocomplete="off">
                        <div class="form-group">
                            <label for="business_name">Business Name <span class="required-star">*</span></label>
                            <input type="text" id="business_name" name="business_name"
                                   placeholder="Your shop name"
                                   value="<?= htmlspecialchars($sd['business_name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="business_address">Business Address <span class="required-star">*</span></label>
                            <textarea id="business_address" name="business_address"
                                      placeholder="Full business address" rows="3" required><?= htmlspecialchars($sd['business_address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="business_phone">Business Phone <span class="required-star">*</span></label>
                                <input type="tel" id="business_phone" name="business_phone"
                                       placeholder="09xxxxxxxxx"
                                       value="<?= htmlspecialchars($sd['business_phone'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="tax_id">Tax ID / Business Registration</label>
                                <input type="text" id="tax_id" name="tax_id"
                                       placeholder="Optional"
                                       value="<?= htmlspecialchars($sd['tax_id'] ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" name="update_shop" class="btn-save">Save Shop Info</button>
                    </form>
                </div>
            </div>

            <!-- ── Personal Information ──────────────────── -->
            <div class="section-card">
                <div class="section-card-header">
                    <span class="section-icon">👤</span>
                    <h2>Personal Information</h2>
                </div>
                <div class="section-card-body">
                    <form method="post" autocomplete="off">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstname">First Name <span class="required-star">*</span></label>
                                <input type="text" id="firstname" name="firstname"
                                       value="<?= htmlspecialchars($sd['firstname']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lastname">Last Name <span class="required-star">*</span></label>
                                <input type="text" id="lastname" name="lastname"
                                       value="<?= htmlspecialchars($sd['lastname']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required-star">*</span></label>
                                <input type="text" id="username" name="username"
                                       value="<?= htmlspecialchars($sd['username']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address <span class="required-star">*</span></label>
                                <input type="email" id="email" name="email"
                                       value="<?= htmlspecialchars($sd['email']) ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="update_personal" class="btn-save">Save Personal Info</button>
                    </form>
                </div>
            </div>

            <!-- ── Change Password ───────────────────────── -->
            <div class="section-card">
                <div class="section-card-header">
                    <span class="section-icon">🔒</span>
                    <h2>Change Password</h2>
                </div>
                <div class="section-card-body">
                    <form method="post" autocomplete="off">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password"
                                   name="current_password" required autocomplete="current-password">
                        </div>

                        <hr class="form-divider">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password"
                                       name="new_password" required autocomplete="new-password">
                                <span class="info-note">Minimum 6 characters</span>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password"
                                       name="confirm_password" required autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" name="change_password" class="btn-save-outline">
                            Change Password
                        </button>
                    </form>
                </div>
            </div>

        </div>
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

<script>
    function validateAndSubmit(input) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        const fileSize = file.size / 1024 / 1024;
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (fileSize > 5) {
            alert('File is too large. Maximum size is 5MB.');
            input.value = '';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            alert('Only JPG, JPEG, PNG & GIF files are allowed.');
            input.value = '';
            return;
        }
        
        input.form.submit();
    }
</script>

</body>
</html>