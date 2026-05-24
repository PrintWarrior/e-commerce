<?php
require_once 'functions.php';

if (isLoggedIn()) {
    redirect('login.php');
}

$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
$hasEmailQuery = array_key_exists('email', $_GET);
$error = '';
$success = '';
$warning = '';

function findVerificationUser(PDO $pdo, string $email) {
    $stmt = $pdo->prepare("
        SELECT id, email, email_verified
        FROM users
        WHERE email = ? AND marked_for_deletion = 0 AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $hasEmailQuery) {
    if ($email === '') {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $user = findVerificationUser($pdo, $email);

        if (!$user) {
            $error = 'No active account was found with that email address.';
        } elseif ((int) $user['email_verified'] === 1) {
            $success = 'This email address is already verified. You can log in to your account.';
        } else {
            $warning = 'This account is not yet verified. Click the button below to send a new verification email.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($email === '') {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $user = findVerificationUser($pdo, $email);

        if (!$user) {
            $error = 'No active account was found with that email address.';
        } elseif ((int) $user['email_verified'] === 1) {
            $success = 'This email address is already verified. You can log in to your account.';
        } else {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);

            if (!sendVerificationEmail($user['email'], $token)) {
                $error = 'The verification email could not be sent right now. Please try again.';
            } else {
                $success = 'A new verification email has been sent. Please check your inbox and spam folder.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification | Beauty Mart</title>
    <link href="../css/login.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <style>
        .resend-note {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .success-box {
            background: #c6f6d5;
            border: 1px solid #9ae6b4;
            border-radius: 8px;
            color: #22543d;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 12px 16px;
        }

        .warning-box {
            background: #fefcbf;
            border: 1px solid #f6e05e;
            border-radius: 8px;
            color: #744210;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 12px 16px;
        }

        .resend-actions {
            margin-top: 16px;
            text-align: center;
        }

        .resend-actions a {
            color: #667eea;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .resend-actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="top-strip"></div>

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
            <span class="page-title">Verification</span>
            <div class="nav-spacer"></div>
        </div>
    </nav>

    <main>
        <div class="auth-wrap">
            <div class="banner">
                <div class="cosmetics">
                    <div class="c-perfume"></div>
                    <div class="c-lip1"></div>
                    <div class="c-lip2"></div>
                    <div class="c-jar"></div>
                    <div class="c-tube"></div>
                </div>
                <div class="banner-text">
                    <h2>Verify Your<br>Account</h2>
                    <p>Request another link<br>for your inbox.</p>
                </div>
            </div>

            <div class="form-card">
                <h3>Resend Verification</h3>
                <p class="resend-note">Enter the email address used to register and we will send a new verification link if the account still needs verification.</p>

                <?php if ($error): ?>
                    <div class="error-box"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-box"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($warning): ?>
                    <div class="warning-box"><?= htmlspecialchars($warning) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <div class="field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                               placeholder="Enter your registered email"
                               value="<?= htmlspecialchars($email) ?>"
                               required>
                    </div>

                    <button type="submit" class="btn-login">Send Verification Email</button>
                </form>

                <div class="resend-actions">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
