<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get customer data from users, customers, and default address tables
$stmt = $pdo->prepare("
    SELECT u.*, c.id as customer_id, c.phone,
           a.id AS address_id, a.label, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
    FROM users u 
    LEFT JOIN customers c ON u.id = c.user_id 
    LEFT JOIN addresses a ON c.default_address_id = a.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If customer doesn't exist, create it with default values
if (!$user['customer_id']) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id, phone) VALUES (?, ?)");
    $stmt->execute([$user_id, '']);
    // Refresh user data
    $stmt = $pdo->prepare("
        SELECT u.*, c.id as customer_id, c.phone,
               a.id AS address_id, a.label, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
        FROM users u 
        LEFT JOIN customers c ON u.id = c.user_id 
        LEFT JOIN addresses a ON c.default_address_id = a.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Handle profile image upload
if (isset($_POST['upload_image']) && !empty($_FILES['profile_pic']['tmp_name'])) {
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
        if (!empty($user['profile_pic']) && file_exists($target_dir . $user['profile_pic']))
            unlink($target_dir . $user['profile_pic']);

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$file_name, $user_id]);
            $_SESSION['profile_image'] = $file_name;
            $success = "Profile picture uploaded successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("
                SELECT u.*, c.id as customer_id, c.phone,
                       a.id AS address_id, a.label, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
                FROM users u 
                LEFT JOIN customers c ON u.id = c.user_id 
                LEFT JOIN addresses a ON c.default_address_id = a.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = "Failed to upload image.";
        }
    }
}

// Handle profile image deletion
if (isset($_POST['delete_image'])) {
    $target_dir = "../uploads/profile_images/";
    if (!empty($user['profile_pic']) && file_exists($target_dir . $user['profile_pic'])) {
        unlink($target_dir . $user['profile_pic']);
        $pdo->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?")->execute([$user_id]);
        unset($_SESSION['profile_image']);
        $success = "Profile picture deleted.";
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT u.*, c.id as customer_id, c.phone,
                   a.id AS address_id, a.label, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
            FROM users u 
            LEFT JOIN customers c ON u.id = c.user_id 
            LEFT JOIN addresses a ON c.default_address_id = a.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = "No profile picture to delete.";
    }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone'] ?? '');
    
    // Address fields - now direct columns
    $barangay  = trim($_POST['barangay'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province  = trim($_POST['province'] ?? '');
    $zip_code  = trim($_POST['zip_code'] ?? '');
    $address_details = trim($_POST['address_details'] ?? '');

    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($lastname))  $errors[] = "Last name is required.";
    if (empty($username))  $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->rowCount() > 0) $errors[] = "Username or email already taken.";
    }

    if (empty($errors)) {
        // Update users table
        $pdo->prepare("UPDATE users SET firstname=?, lastname=?, username=?, email=? WHERE id=?")
            ->execute([$firstname, $lastname, $username, $email, $user_id]);
        
        $pdo->prepare("UPDATE customers SET phone=? WHERE user_id=?")
            ->execute([$phone, $user_id]);

        upsertCustomerDefaultAddress((int) $user['customer_id'], [
            'label' => 'Home',
            'barangay' => $barangay,
            'municipality' => $municipality,
            'province' => $province,
            'zip_code' => $zip_code,
            'address_details' => $address_details,
        ]);
        
        $_SESSION['username'] = $username;
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT u.*, c.id as customer_id, c.phone,
                   a.id AS address_id, a.label, a.barangay, a.municipality, a.province, a.zip_code, a.address_details
            FROM users u 
            LEFT JOIN customers c ON u.id = c.user_id 
            LEFT JOIN addresses a ON c.default_address_id = a.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Beauty Mart</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_profile.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
</head>

<body>

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
                        <?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_images/" . $user['profile_pic'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($user['profile_pic']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
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

            <div class="search-wrap"></div>

            <div class="nav-icons">
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z" />
                        <polyline points="9 21 9 12 15 12 15 21" />
                    </svg>
                </a>
                <a href="orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" />
                        <path d="M8 21h8M12 17v4" />
                    </svg>
                </a>
                <a href="wishlist.php" title="Wishlist">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                </a>
                <a href="cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page -->
    <div class="page">
        <div class="profile-card">
            <h1 class="card-title">Customer Profile</h1>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Profile Image Upload Form -->
            <form method="post" enctype="multipart/form-data">
                <span class="field-label">Profile Image</span>

                <div class="avatar-area">
                    <div class="avatar-lg">
                        <?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_images/" . $user['profile_pic'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($user['profile_pic']) ?>"
                                alt="Profile">
                        <?php else: ?>
                            <?= strtoupper(substr($user['firstname'], 0, 1)) . strtoupper(substr($user['lastname'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <div class="file-input-wrap">
                        <input type="file" name="profile_pic" id="profile_pic" accept="image/*"
                            onchange="this.form.querySelector('[name=upload_image]').value='1'; this.form.submit()">
                    </div>
                    <input type="hidden" name="upload_image" value="">

                    <?php if (!empty($user['profile_pic'])): ?>
                        <div class="img-actions">
                            <button type="submit" class="btn-danger" name="delete_image" value="1" 
                                onclick="return confirm('Delete your profile picture?');">
                                Delete Picture
                            </button>
                        </div>
                    <?php endif; ?>

                    <p class="info-note">Supported formats: JPG, JPEG, PNG, GIF. Max 5MB</p>
                </div>
            </form>

            <!-- Profile Information Update Form -->
            <form method="post" enctype="multipart/form-data" autocomplete="off">

                <!-- Full Name -->
                <div class="field-row" style="margin-bottom:16px;">
                    <div class="field" style="margin-bottom:0;">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" placeholder="First name"
                            value="<?= htmlspecialchars($user['firstname']) ?>" required>
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" placeholder="Last name"
                            value="<?= htmlspecialchars($user['lastname']) ?>" required>
                    </div>
                </div>

                <!-- Username -->
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                        required>
                </div>

                <!-- Email -->
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                        required>
                </div>

                <!-- Phone -->
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" placeholder="09xxxxxxxxx"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <!-- Address -->
                <h3 class="address-heading">Shipping Address</h3>

                <div class="field-row">
                    <div class="field" style="margin-bottom:0;">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay"
                            value="<?= htmlspecialchars($user['barangay'] ?? '') ?>">
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label for="municipality">Municipality</label>
                        <input type="text" id="municipality" name="municipality"
                            value="<?= htmlspecialchars($user['municipality'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-row" style="margin-top:16px;">
                    <div class="field" style="margin-bottom:0;">
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province"
                            value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                    </div>
                    <div class="field" style="margin-bottom:0;">
                        <label for="zip_code">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                            value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>">
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label for="address_details">Address Details</label>
                    <textarea id="address_details" name="address_details" rows="2"
                        placeholder="e.g. Purok 4, Unit no., Street..."><?= htmlspecialchars($user['address_details'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                <a href="account.php" class="btn-secondary">Account Settings</a>
            </form>

            <hr class="section-divider">

            <a href="../logout.php" class="logout-link">Logout</a>
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

</body>

</html>
