<?php
require_once 'functions.php';

if (isLoggedIn()) {
    // Redirect based on user type - go up one level to root, then to respective folders
    if ($_SESSION['user_type'] == 'superadmin') {
        redirect('../1-admin/dashboard.php');
    } elseif ($_SESSION['user_type'] == 'admin') {
        redirect('../1-admin/dashboard.php');
    } elseif ($_SESSION['user_type'] == 'seller') {
        redirect('../2-seller/dashboard.php');
    } else {
        redirect('../3-customer/dashboard.php');
    }
}

$error = '';
$warning = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login    = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $userStatus = $user['status'] ?? 'active';

        if ($userStatus === 'banned') {
            $error = "Your account has been banned." . (!empty($user['banned_reason']) ? " Reason: {$user['banned_reason']}" : '');
        } elseif ($userStatus === 'suspended') {
            $suspendedUntil = $user['suspended_until'] ?? null;

            if ($suspendedUntil && strtotime($suspendedUntil) <= time()) {
                $pdo->prepare("UPDATE users SET status = 'active', suspended_until = NULL, banned_reason = NULL WHERE id = ?")
                    ->execute([$user['id']]);
                $user['status'] = 'active';
                $user['suspended_until'] = null;
                $user['banned_reason'] = null;
            } else {
                $error = "Your account is suspended" . ($suspendedUntil ? " until " . date('F j, Y g:i A', strtotime($suspendedUntil)) : '') . "." . (!empty($user['banned_reason']) ? " Reason: {$user['banned_reason']}" : '');
            }
        }

        if ($error === '' && !$user['email_verified']) {
            $error = "Please verify your email before logging in. Check your inbox.";
        } elseif ($error === '') {
            // Check if user is superadmin
            $stmt = $pdo->prepare("SELECT id FROM superadmins WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $is_superadmin = $stmt->fetch();

            // Check if user is admin
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $is_admin = $stmt->fetch();
            
            // Check if user is seller (has approved seller record)
            $stmt = $pdo->prepare("SELECT id, user_id FROM sellers WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $is_seller = $stmt->fetch();
            
            // Determine user type
            if ($is_superadmin) {
                $user_type = 'superadmin';
            } elseif ($is_admin) {
                $user_type = 'admin';
            } elseif ($is_seller) {
                $user_type = 'seller';
            } else {
                $user_type = 'customer';
            }
            
            // For sellers, check if they have a pending/declined application
            if ($user_type == 'seller') {
                // Check if there's a seller application (for pending/declined cases)
                $stmt = $pdo->prepare("SELECT status FROM seller_applications WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $application = $stmt->fetch();
                
                if ($application && $application['status'] == 'pending') {
                    $warning = "Your seller application is still pending approval. You will be notified via email once approved. Please wait for admin verification.";
                } elseif ($application && $application['status'] == 'declined') {
                    $error = "Your seller application has been declined. Please contact support for more information.";
                } else {
                    // Seller is approved and has a sellers record
                    $_SESSION['user_id']        = $user['id'];
                    $_SESSION['username']       = $user['username'];
                    $_SESSION['user_type']      = $user_type;
                    $_SESSION['email_verified'] = $user['email_verified'];
                    $_SESSION['seller_id']      = $is_seller['id'];
                    
                    // Get profile picture
                    $_SESSION['profile_image'] = $user['profile_pic'] ?? null;
                    
                    redirect('../2-seller/dashboard.php');
                }
            } else {
                // Customer or Admin
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['username']       = $user['username'];
                $_SESSION['user_type']      = $user_type;
                $_SESSION['email_verified'] = $user['email_verified'];
                
                // Get profile picture
                $_SESSION['profile_image'] = $user['profile_pic'] ?? null;
                
                if ($user_type == 'superadmin') {
                    redirect('../1-admin/dashboard.php');
                } elseif ($user_type == 'admin') {
                    redirect('../1-admin/dashboard.php');
                } else {
                    logSystemEvent(
                        'customer_logged_in',
                        'users',
                        (int) $user['id'],
                        "Customer {$user['username']} logged in successfully.",
                        (int) $user['id']
                    );
                    redirect('../3-customer/dashboard.php');
                }
            }
        }
    } else {
        $error = "Invalid credentials. Please check your username/email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Beauty Mart</title>
    <link href="../css/login.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="icon" type="image/png" href="../images/logo.png"> 
    <style>
        .warning-box {
            background: #feebc8;
            color: #7b341e;
            border: 1px solid #fbd38d;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-box svg {
            flex-shrink: 0;
        }
        
        .seller-note {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 13px;
            color: #718096;
        }
        
        .seller-note a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .seller-note a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Top strip -->
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
            <span class="page-title">Login</span>
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
                <h3>Welcome Back Beautiful!</h3>

                <?php if ($error): ?>
                    <div class="error-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($warning): ?>
                    <div class="warning-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 9v4M12 17h.01"/>
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        </svg>
                        <?= htmlspecialchars($warning) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" autocomplete="off">

                    <div class="field">
                        <label for="login">Username or Email</label>
                        <input type="text" id="login" name="login"
                               placeholder="Enter your username or email"
                               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                               required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="pwd-wrap">
                            <input type="password" id="password" name="password"
                                   placeholder="••••••••" required>
                            <button type="button" class="pwd-toggle" onclick="togglePwd()" title="Show/hide password">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-login">Login</button>

                </form>

                <a href="register.php" class="signup-link">Don't have an account? Signup</a>
                
                <div class="seller-note">
                    🛍️ Want to sell with us? <a href="../sell.php">Start Selling</a>
                </div>
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
        function togglePwd() {
            const el  = document.getElementById('password');
            el.type   = el.type === 'password' ? 'text' : 'password';
        }
    </script>

</body>
</html>
