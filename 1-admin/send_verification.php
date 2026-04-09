<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Please log in to access this page.";
    $_SESSION['flash_type'] = "error";
    redirect('../login.php');
}

// Check if user is admin (exists in admins table)
$stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_record = $stmt->fetch();
if (!$admin_record) {
    $_SESSION['flash_message'] = "Access denied. Admin privileges required.";
    $_SESSION['flash_type'] = "error";
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validate input
    if (!$user_id || empty($email)) {
        $_SESSION['flash_message'] = "Invalid user data provided.";
        $_SESSION['flash_type'] = "error";
        redirect('dashboard.php');
    }
    
    // Verify user exists and get their details
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN a.id IS NOT NULL THEN 'Admin'
                   WHEN s.id IS NOT NULL THEN 'Seller'
                   WHEN c.id IS NOT NULL THEN 'Customer'
                   ELSE 'User'
               END as user_role
        FROM users u
        LEFT JOIN admins a ON u.id = a.user_id
        LEFT JOIN sellers s ON u.id = s.user_id
        LEFT JOIN customers c ON u.id = c.user_id
        WHERE u.id = ? AND u.email = ?
    ");
    $stmt->execute([$user_id, $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['flash_message'] = "User not found.";
        $_SESSION['flash_type'] = "error";
        redirect('dashboard.php');
    }
    
    // Check if user is already verified
    if ($user['email_verified'] == 1) {
        $_SESSION['flash_message'] = "User's email is already verified.";
        $_SESSION['flash_type'] = "info";
        redirect('dashboard.php');
    }
    
    // Generate new verification token
    $token = bin2hex(random_bytes(32));
    
    // Save token in database
    $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
    $result = $stmt->execute([$token, $user_id]);
    
    if (!$result) {
        $_SESSION['flash_message'] = "Failed to generate verification token.";
        $_SESSION['flash_type'] = "error";
        redirect('dashboard.php');
    }
    
    // Prepare email content
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $token;
    
    $subject = "Email Verification - Beauty Mart";
    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verify Your Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Beauty Mart</h2>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$user['firstname']} {$user['lastname']}</strong>,</p>
                    <p>An administrator has requested that you verify your email address for your Beauty Mart account.</p>
                    <p>Please click the button below to verify your email address:</p>
                    <p style='text-align: center;'>
                        <a href='{$verification_link}' class='button' style='color: white;'>Verify Email Address</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p><a href='{$verification_link}'>{$verification_link}</a></p>
                    <p>If you did not request this verification, please ignore this email.</p>
                    <p><strong>Account Details:</strong><br>
                    Username: {$user['username']}<br>
                    Account Type: {$user['user_role']}<br>
                    Registered: " . date('F j, Y', strtotime($user['created_at'])) . "</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Beauty Mart. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Beauty Mart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    // Send verification email
    if (sendEmail($email, $subject, $body)) {
        // Log the action
        $log_message = "Verification email sent to {$user['username']} ({$email}) by admin " . $_SESSION['username'];
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, type, is_read) 
            VALUES (?, ?, 'system', 1)
        ");
        $stmt->execute([$_SESSION['user_id'], $log_message]);
        
        $_SESSION['flash_message'] = "Verification email has been sent to {$user['firstname']} {$user['lastname']} ({$email}).";
        $_SESSION['flash_type'] = "success";
    } else {
        // Log the failure
        error_log("Failed to send verification email to $email for user ID $user_id");
        $_SESSION['flash_message'] = "Failed to send verification email. Please check email configuration.";
        $_SESSION['flash_type'] = "error";
    }
    
    // Redirect back to dashboard
    redirect('dashboard.php');
} else {
    // If not POST request, redirect to dashboard
    redirect('dashboard.php');
}
?>