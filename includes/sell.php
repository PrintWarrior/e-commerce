<?php
require_once 'functions.php';


$error = '';
$success = '';

// Handle Seller Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_seller'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $business_name = trim($_POST['business_name']);
    $business_address = trim($_POST['business_address']);
    $business_phone = trim($_POST['business_phone']);
    $business_tax_id = trim($_POST['business_tax_id']);
    $agree_terms = isset($_POST['agree_terms']);
    
    $errors = [];
    
    // Personal info validation
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($lastname)) $errors[] = "Last name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    
    // Business info validation
    if (empty($business_name)) $errors[] = "Business name is required.";
    if (empty($business_address)) $errors[] = "Business address is required.";
    if (empty($business_phone)) $errors[] = "Business phone number is required.";
    if (!$agree_terms) $errors[] = "You must agree to the Terms & Conditions.";
    
    // Check if username or email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists.";
        }
    }
    
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            
            // Insert as user
            $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, username, email, password, verification_token) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstname, $lastname, $username, $email, $hashed, $token]);
            $user_id = $pdo->lastInsertId();
            
            // Insert seller application (pending approval)
            $stmt = $pdo->prepare("INSERT INTO seller_applications (user_id, business_name, business_address, phone, tax_id, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $business_name, $business_address, $business_phone, $business_tax_id]);
            
            // Get admin user IDs for notifications
            $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN admins a ON u.id = a.user_id");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Notify admins
            $message = "New seller registration: $username ($email) - Business: $business_name. Awaiting email verification from admin and seller application review.";
            foreach ($admins as $admin_id) {
                createNotification($admin_id, $message, 'new_seller');
            }
            
            $pdo->commit();
            $success = "Registration successful! Please wait for an admin to send you a verification email. Once verified, we will review your seller application and notify you of approval.";
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Selling | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/sell.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="inner">
            <div class="left-links">
                <a href="about.php">About Us</a>
                <span class="sep">|</span>
                <a href="contact.php">Contact Us</a>
                <span class="sep">|</span>
                <a href="sell.php">Start Selling</a>
            </div>
            <div class="right-links">
                <a href="register.php">Sign Up</a>
                <span class="sep">|</span>
                <a href="login.php">Login</a>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="inner">
            <a href="../index.php" class="logo">
                <div class="logo-icon">
                    <img src="../images/logo.png" alt="Beauty Mart">
                </div>
                <div class="logo-text">
                    <span>Beauty</span>
                    <span>Mart</span>
                </div>
            </a>
            <div class="search-wrap">
                <!-- Search removed for sell page -->
            </div>
            <div class="nav-icons">
                <a href="../index.php" title="Home">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                        <polyline points="9 21 9 12 15 12 15 21"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <div class="sell-container">
        <div class="sell-header">
            <h1>Start Your Selling Journey</h1>
            <p>Join thousands of successful sellers on Beauty Mart</p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <h3 style="margin-bottom: 20px; color: #333;">Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname" required value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name *</label>
                        <input type="text" id="lastname" name="lastname" required value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <div class="info-note">Minimum 6 characters</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <h3 style="margin: 30px 0 20px; color: #333;">Business Information</h3>
                
                <div class="form-group">
                    <label for="business_name">Business Name *</label>
                    <input type="text" id="business_name" name="business_name" required value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="business_address">Business Address *</label>
                    <textarea id="business_address" name="business_address" rows="3" required><?= htmlspecialchars($_POST['business_address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="business_phone">Business Phone *</label>
                        <input type="tel" id="business_phone" name="business_phone" required value="<?= htmlspecialchars($_POST['business_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="business_tax_id">Tax ID / Business Registration</label>
                        <input type="text" id="business_tax_id" name="business_tax_id" value="<?= htmlspecialchars($_POST['business_tax_id'] ?? '') ?>">
                        <div class="info-note">Optional but recommended</div>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label for="agree_terms">I agree to the Terms & Conditions for sellers *</label>
                </div>
                
                <button type="submit" name="register_seller" class="btn-submit">Register as Seller</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2026 All Rights Reserved by <span>Beauty Mart</span>.</p>
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
