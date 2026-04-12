<?php
date_default_timezone_set('Asia/Manila');

require_once 'functions.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    die("Invalid reset token. Please request a new password reset link.");
}

// Check token in database
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Token expired or invalid. Please <a href='forgot_password.php'>request a new password reset link</a>.");
}

// Get user details for the reset owner
$stmt = $pdo->prepare("SELECT id, firstname, username, email FROM users WHERE id = ?");
$stmt->execute([$reset['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found. Please <a href='forgot_password.php'>request a new password reset link</a>.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Update password in database
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed, $user['id']])) {
            // Delete the used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            // Delete any other reset tokens for this email (cleanup)
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ? AND token != ?");
            $stmt->execute([$reset['user_id'], $token]);
            
            $success = "Password changed successfully! You can now <a href='login.php'>login with your new password</a>.";
            
            // Optional: Send confirmation email
            $subject = "Password Changed Successfully - Beauty Mart";
            $body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Password Changed</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Beauty Mart</h2>
                            <p>Password Changed</p>
                        </div>
                        <div class='content'>
                            <p>Hello <strong>{$user['firstname']}</strong>,</p>
                            <p>Your password has been successfully changed.</p>
                            <p>If you did not make this change, please contact our support team immediately.</p>
                            <p>You can now <a href='http://" . $_SERVER['HTTP_HOST'] . "/lume%20and%20co/login.php'>login with your new password</a>.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message from Beauty Mart. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Beauty Mart. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            sendEmail($user['email'], $subject, $body);
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href=" ">
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

        .pwd-wrap {
            position: relative;
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
            padding-right: 40px;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            width: auto;
            margin: 0;
            color: #718096;
        }

        .pwd-toggle:hover {
            background: none;
            transform: translateY(-50%);
            color: #667eea;
        }

        button[type="submit"] {
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
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success a {
            color: #22543d;
            font-weight: 600;
            text-decoration: underline;
        }

        .info-note {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
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

        .requirements {
            background: #f7fafc;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: #718096;
        }

        .requirements ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .requirements li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <p class="subtitle">Create a new password for your account</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="pwd-wrap">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter new password" required>
                        <button type="button" class="pwd-toggle" onclick="togglePwd('password')" title="Show/hide password">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="info-note">Minimum 6 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="pwd-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required>
                        <button type="button" class="pwd-toggle" onclick="togglePwd('confirm_password')" title="Show/hide password">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit">Reset Password</button>
            </form>
            
            <div class="requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>Minimum 6 characters</li>
                    <li>Use a mix of letters, numbers, and symbols for better security</li>
                    <li>Avoid using common words or personal information</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="../index.php">← Back to Login</a>
        </div>
    </div>

    <script>
        function togglePwd(fieldId) {
            const el = document.getElementById(fieldId);
            const type = el.type === 'password' ? 'text' : 'password';
            el.type = type;
        }
    </script>
</body>
</html>
