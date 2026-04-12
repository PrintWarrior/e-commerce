<?php
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    $errors = [];
    if (empty($firstname)) $errors[] = "First name required";
    if (empty($lastname)) $errors[] = "Last name required";
    if (empty($username)) $errors[] = "Username required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm) $errors[] = "Passwords do not match";

    /* Handle profile picture upload
    $profile_pic = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profile_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . $file_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false && in_array($image_file_type, $allowed_types) && $_FILES['profile_image']['size'] <= 5000000) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_pic = $file_name;
            }
        }
    }*/

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already taken";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $token  = bin2hex(random_bytes(32));
            
            $pdo->beginTransaction();
            try {
                // Insert into users table
                $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, username, email, password, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstname, $lastname, $username, $email, $hashed, $token]);
                $user_id = $pdo->lastInsertId();
                
                // Insert into customers table
                $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                
                // Get admin user IDs for notifications
                $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN admins a ON u.id = a.user_id");
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Notify admins
                $message = "New customer registered: $username ($email) awaiting email verification from admin.";
                foreach ($admins as $admin_id) {
                    createNotification($admin_id, $message, 'new_user');
                }
                
                $pdo->commit();
                $success = "Registration successful! Please wait for an admin to send you a verification email. Check your email shortly.";
                
                // Clear form data
                $_POST = [];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Registration failed: " . $e->getMessage();
            }
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
    <title>Beauty Mart – Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/register.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
</head>
<body>

    <div class="top-strip"></div>

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
            <span class="page-title">Signup</span>
            <div class="nav-spacer"></div>
            <div class="nav-user">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </nav>

    <!-- Main -->
    <main>
        <div class="auth-wrap">

            <!-- Banner -->
            <div class="banner">
                <div class="cosmetics">
                    <div class="c-perfume"></div>
                    <div class="c-lip1"></div>
                    <div class="c-lip2"></div>
                    <div class="c-jar"></div>
                    <div class="c-tube"></div>
                </div>
                <div class="banner-text">
                    <h2>Glow Into<br>Every Moment</h2>
                    <p>Discover Exclusive Offers<br>Onea.in Your Favorite Essentials</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <h3>Welcome New User</h3>

                <?php if ($error): ?>
                    <div class="error-box"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-box"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" autocomplete="off">

                    <!--<div class="field">
                        <label for="profile_image">Profile Image (optional)</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <div class="info-note">Max size: 5MB. Allowed: JPG, JPEG, PNG, GIF</div>
                    </div>-->

                    <div class="field-row">
                        <div class="field">
                            <label for="firstname">First Name *</label>
                            <input type="text" id="firstname" name="firstname"
                                   placeholder="First name"
                                   value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="lastname">Last Name *</label>
                            <input type="text" id="lastname" name="lastname"
                                   placeholder="Last name"
                                   value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username"
                               placeholder="Choose a username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email"
                               placeholder="your@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label for="password">Password *</label>
                        <div class="pwd-wrap">
                            <input type="password" id="password" name="password"
                                   placeholder="••••••••" required>
                            <button type="button" class="pwd-toggle" onclick="togglePwd('password')" title="Show password">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <div class="info-note">Minimum 6 characters</div>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="pwd-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="••••••••" required>
                            <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_password')" title="Show password">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-signup">Signup</button>
                </form>

                <a href="login.php" class="login-link">Already have an account? Login</a>
            
            </div>

        </div>
    </main>

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
        function togglePwd(id) {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
        }
    </script>

</body>
</html>
