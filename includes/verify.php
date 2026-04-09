<?php
require_once 'functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';

if (empty($token)) {
    $message = "Invalid verification token. Please check your email for the correct link.";
    $message_type = 'error';
} else {
    // Check if token exists and user is not verified
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.firstname, u.lastname, u.username, u.email_verified,
               CASE 
                   WHEN a.id IS NOT NULL THEN 'admin'
                   WHEN s.id IS NOT NULL THEN 'seller'
                   WHEN c.id IS NOT NULL THEN 'customer'
                   ELSE 'user'
               END as user_role
        FROM users u
        LEFT JOIN admins a ON u.id = a.user_id
        LEFT JOIN sellers s ON u.id = s.user_id
        LEFT JOIN customers c ON u.id = c.user_id
        WHERE u.verification_token = ? AND u.email_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update user as verified
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Check if this is a seller application that needs approval notification
            if ($user['user_role'] == 'seller') {
                // Check if there's a pending seller application
                $stmt = $pdo->prepare("
                    SELECT id, status FROM seller_applications 
                    WHERE user_id = ? AND status = 'pending'
                ");
                $stmt->execute([$user['id']]);
                $application = $stmt->fetch();
                
                if ($application) {
                    // Notify admins about verified seller awaiting approval
                    $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN admins a ON u.id = a.user_id");
                    $stmt->execute();
                    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $message_body = "Seller {$user['username']} ({$user['email']}) has verified their email and is awaiting account approval.";
                    foreach ($admins as $admin_id) {
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'seller_application')");
                        $stmt->execute([$admin_id, $message_body]);
                    }
                }
            } elseif ($user['user_role'] == 'customer') {
                // Create welcome notification for customer (optional)
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, message, type, is_read) 
                    VALUES (?, 'Welcome to Beauty Mart! Your email has been verified.', 'system', 1)
                ");
                $stmt->execute([$user['id']]);
            }
            
            $pdo->commit();
            
            $message = "Email verified successfully! You can now login to your account.";
            $message_type = 'success';
            
            // Store success message in session for login page
            $_SESSION['verification_success'] = "Your email has been verified. Please login to continue.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "An error occurred while verifying your email. Please try again.";
            $message_type = 'error';
            error_log("Verification error: " . $e->getMessage());
        }
    } else {
        // Check if token exists but user is already verified
        $stmt = $pdo->prepare("SELECT id, email_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user && $existing_user['email_verified'] == 1) {
            $message = "This email has already been verified. You can login to your account.";
            $message_type = 'info';
        } else {
            $message = "Invalid or expired verification token. Please request a new verification email.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
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
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }

        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            line-height: 1.5;
        }

        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .message.info {
            background: #feebc8;
            color: #7b341e;
            border: 1px solid #fbd38d;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .resend-link {
            margin-top: 20px;
            font-size: 14px;
            color: #718096;
        }

        .resend-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message_type == 'success'): ?>
            <div class="icon">✅</div>
        <?php elseif ($message_type == 'error'): ?>
            <div class="icon">❌</div>
        <?php else: ?>
            <div class="icon">ℹ️</div>
        <?php endif; ?>
        
        <h1>Email Verification</h1>
        
        <div class="message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <?php if ($message_type == 'success'): ?>
            <a href="../index.php" class="button">Login Now</a>
        <?php elseif ($message_type == 'error'): ?>
            <div class="resend-link">
                <p>Didn't receive the email? <a href="resend_verification.php?token=<?= urlencode($token) ?>">Resend verification email</a></p>
            </div>
            <a href="login.php" class="button">Back to Login</a>
        <?php else: ?>
            <a href="login.php" class="button">Go to Login</a>
        <?php endif; ?>
        
        <div class="footer">
            <p>Need help? <a href="contact.php">Contact our support team</a></p>
            <p>&copy; <?= date('Y') ?> Beauty Mart. All rights reserved.</p>
        </div>
    </div>
</body>
</html>