<?php
date_default_timezone_set('Asia/Manila');

require_once 'functions.php';

$error = '';
$success = '';
$warning = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Validate email format
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if user exists with this email
            $stmt = $pdo->prepare("SELECT id, firstname, username, email_verified FROM users WHERE email = ? AND marked_for_deletion = 0 AND deleted_at IS NULL");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Email not found in database
                $error = "This email address does not exist in our records.";
            } elseif (!$user['email_verified']) {
                // Account exists but email is not verified
                $warning = "Your account is not yet verified. Please check your email and verify your account before resetting your password.";
            } else {
                // Account exists and is verified — proceed with reset
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Delete any existing reset tokens for this user
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->execute([$user['id']]);

                // Insert new reset token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                if ($stmt->execute([$user['id'], $token, $expires])) {
                    if (!sendPasswordResetEmail($email, $token)) {
                        error_log('Password reset email failed to send for user ID ' . (int) $user['id']);
                    }
                } else {
                    error_log('Password reset token insert failed for user ID ' . (int) $user['id']);
                }

                $success = "A password reset link has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox (and spam folder).";
            }
        } catch (Throwable $e) {
            error_log('Forgot password request failed: ' . $e->getMessage());
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }

        h2 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-icon {
            font-size: 18px;
            flex-shrink: 0;
            line-height: 1.3;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .alert-warning {
            background: #fefcbf;
            color: #744210;
            border: 1px solid #f6e05e;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-warning a {
            color: #744210;
            font-weight: 700;
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .info-text {
            text-align: center;
            font-size: 12px;
            color: #718096;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password?</h2>
        <p class="subtitle">Enter your email address and we'll send you a link to reset your password.</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">✕</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($warning): ?>
            <div class="alert alert-warning">
                <span class="alert-icon">⚠</span>
                <span>
                    <?= htmlspecialchars($warning) ?>
                    <br><br>
                    Didn't receive the verification email?
                    <a href="resend_verification.php?email=<?= urlencode($_POST['email'] ?? '') ?>">Resend verification email</a>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="Enter your registered email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>
            <button type="submit">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="../index.php">← Back to Login</a>
        </div>

        <div class="info-text">
            <p>Don't have an account? <a href="../register.php">Sign up here</a></p>
        </div>
    </div>
</body>
</html> 
