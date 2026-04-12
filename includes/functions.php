<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../phpmailer/vendor/autoload.php'; // If using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Helper: check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: check if user is admin (checks admins table)
function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->rowCount() > 0;
}

// Helper: check if user is superadmin (checks superadmins table)
function isSuperadmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM superadmins WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->rowCount() > 0;
}

// Helper: allow either admin or superadmin on privileged pages
function isAdminOrSuperadmin() {
    return isAdmin() || isSuperadmin();
}

// Helper: check if user is seller (checks sellers table)
function isSeller() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->rowCount() > 0;
}

// Helper: check if user is customer (checks customers table)
function isCustomer() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->rowCount() > 0;
}

// Helper: get user type
function getUserType($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM superadmins WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) return 'superadmin';
    
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) return 'admin';
    
    $stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() > 0) return 'seller';
    
    return 'customer';
}

/* Get product image URL
function getProductImageUrl($image_name) {
    if (empty($image_name) || $image_name == 'default.jpg') {
        return '../product_images/default.jpg';
    }
    
    // Check if file exists
    $image_path = '../product_images/' . $image_name;
    if (file_exists($image_path)) {
        return $image_path;
    }
    
    return '../product_images/default.jpg';
}*/

// Helper: check if user is verified
function isVerified() {
    return isset($_SESSION['email_verified']) && $_SESSION['email_verified'] == 1;
}

// Send email using PHPMailer
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'beautymarkettalan@gmail.com';
        $mail->Password   = 'bowbbecaxhyqljwg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('beautymarkettalan@gmail.com', 'Beauty Mart');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

// Send verification email
function sendVerificationEmail($email, $token) {
    $link = "http://localhost/lume%20and%20co/includes/verify.php?token=$token";

    $subject = "Verify Your Email Address - Beauty Mart";

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
                    <p>Thank you for registering with Beauty Mart!</p>
                    <p>Please click the button below to verify your email address:</p>
                    <p style='text-align: center;'>
                        <a href='$link' class='button' style='color: white;'>Verify Email Address</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p><a href='$link'>$link</a></p>
                    <p>If you did not create an account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Beauty Mart. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Beauty Mart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    return sendEmail($email, $subject, $body);
}

// Send password reset email
function sendPasswordResetEmail($email, $token) {
    $link = "http://localhost/lume%20and%20co/includes/reset_password.php?token=$token";
    $subject = "Reset Your Password - Beauty Mart";
    $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reset Password</title>
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
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <p>You requested to reset your password.</p>
                    <p>Click the button below to reset your password:</p>
                    <p style='text-align: center;'>
                        <a href='$link' class='button' style='color: white;'>Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p><a href='$link'>$link</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request this, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Beauty Mart. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Beauty Mart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    return sendEmail($email, $subject, $body);
}

function getNotificationTypeId(string $code): ?int {
    global $pdo;

    static $cache = [];

    $code = trim($code);
    if ($code === '') {
        return null;
    }

    if (isset($cache[$code])) {
        return $cache[$code];
    }

    $stmt = $pdo->prepare("SELECT id FROM notification_types WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $typeId = $stmt->fetchColumn();

    if ($typeId !== false) {
        return $cache[$code] = (int) $typeId;
    }

    $label = ucwords(str_replace('_', ' ', $code));
    $stmt = $pdo->prepare("
        INSERT INTO notification_types (code, label, description)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$code, $label, null]);

    return $cache[$code] = (int) $pdo->lastInsertId();
}

function getNotificationCode(array $notification): string {
    return (string) ($notification['type_code'] ?? $notification['code'] ?? $notification['type'] ?? 'system');
}

function getNotificationMeta(string $code): array {
    return match ($code) {
        'order'              => ['icon' => '📦', 'color' => '#2255cc', 'bg' => '#dce8ff', 'label' => 'Order'],
        'new_user'           => ['icon' => '👤', 'color' => '#c75473', 'bg' => '#fce8ee', 'label' => 'New User'],
        'deletion_request'   => ['icon' => '⚠️', 'color' => '#c0303a', 'bg' => '#fdecea', 'label' => 'Deletion'],
        'seller_application' => ['icon' => '📝', 'color' => '#7322cc', 'bg' => '#f3e0ff', 'label' => 'Application'],
        'payout'             => ['icon' => '💰', 'color' => '#1a7f4b', 'bg' => '#d1f5e0', 'label' => 'Payout'],
        'system'             => ['icon' => '🔔', 'color' => '#856404', 'bg' => '#fff3cd', 'label' => 'System'],
        'new_seller'         => ['icon' => '🏪', 'color' => '#8a5a00', 'bg' => '#fff1d6', 'label' => 'Seller'],
        default              => ['icon' => '📌', 'color' => '#555', 'bg' => '#f5f5f5', 'label' => 'Notice'],
    };
}

function getPaymentMethodId($value): ?int {
    global $pdo;

    if ($value === null) {
        return null;
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    if (ctype_digit($raw)) {
        $stmt = $pdo->prepare("SELECT id FROM payment_methods WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([(int) $raw]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    $normalized = strtolower(str_replace(['_', '-', ' '], '', $raw));
    $map = [
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'paymaya' => 'Maya',
        'cod' => 'COD',
        'cashondelivery' => 'COD',
        'banktransfer' => 'Bank Transfer',
        'bank' => 'Bank Transfer',
        'creditcard' => 'Credit Card',
        'card' => 'Credit Card',
    ];
    $name = $map[$normalized] ?? $raw;

    $stmt = $pdo->prepare("SELECT id FROM payment_methods WHERE name = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    return $id === false ? null : (int) $id;
}

function getActivePaymentMethods(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT id, name FROM payment_methods WHERE is_active = 1 ORDER BY id ASC");
    return $stmt->fetchAll();
}

function getOrderPaymentStatus(array $order): string {
    return in_array((string) ($order['status'] ?? ''), ['completed', 'paid'], true) ? 'Paid' : 'Pending';
}

function getCustomerAddressByCustomerId(int $customerId): ?array {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT a.*
        FROM customers c
        LEFT JOIN addresses a ON c.default_address_id = a.id
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->execute([$customerId]);
    $address = $stmt->fetch();

    return $address ?: null;
}

function upsertCustomerDefaultAddress(int $customerId, array $addressData): ?int {
    global $pdo;

    $current = getCustomerAddressByCustomerId($customerId);
    $params = [
        $addressData['label'] ?? 'Home',
        $addressData['address_details'] ?? null,
        $addressData['barangay'] ?? null,
        $addressData['municipality'] ?? null,
        $addressData['province'] ?? null,
        $addressData['zip_code'] ?? null,
    ];

    if ($current && !empty($current['id'])) {
        $stmt = $pdo->prepare("
            UPDATE addresses
            SET label = ?, address_details = ?, barangay = ?, municipality = ?, province = ?, zip_code = ?
            WHERE id = ?
        ");
        $stmt->execute([...$params, $current['id']]);
        return (int) $current['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO addresses (customer_id, label, address_details, barangay, municipality, province, zip_code)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $customerId,
        ...$params,
    ]);
    $addressId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE customers SET default_address_id = ? WHERE id = ?");
    $stmt->execute([$addressId, $customerId]);

    return $addressId;
}

// Create notification for any user
function createNotification($user_id, $message, $type) {
    global $pdo;
    $typeId = getNotificationTypeId((string) $type);
    if ($typeId === null) {
        return false;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, message, notification_type_id)
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$user_id, $message, $typeId]);
}

// Get the best available client IP address for audit logs
function getClientIpAddress() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }

        $value = trim((string) $_SERVER[$key]);
        if ($value === '') {
            continue;
        }

        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = explode(',', $value);
            $value = trim($parts[0]);
        }

        return substr($value, 0, 45);
    }

    return null;
}

// Write an entry to system_logs without interrupting the main action
function logSystemEvent($action, $table_name = null, $record_id = null, $description = null, $user_id = null) {
    global $pdo;

    try {
        $resolved_user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, table_name, record_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $resolved_user_id,
            $action,
            $table_name,
            $record_id,
            $description,
            getClientIpAddress(),
        ]);
    } catch (Throwable $e) {
        error_log('System log write failed: ' . $e->getMessage());
        return false;
    }
}

// Create admin notification (legacy function - uses createNotification)
function createAdminNotification($admin_id, $message, $type) {
    return createNotification($admin_id, $message, $type);
}

// Get admin users (from admins table)
function getAdminIds() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN admins a ON u.id = a.user_id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get user profile image
function getUserProfileImage($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user['profile_pic'] ?? null;
}

// Display avatar HTML
function displayAvatar($user_id, $username) {
    $profile_pic = getUserProfileImage($user_id);
    ?>
    <div class="avatar">
        <?php if (!empty($profile_pic) && file_exists("../uploads/profile_images/" . $profile_pic)): ?>
            <img src="../uploads/profile_images/<?= htmlspecialchars($profile_pic) ?>" 
                 alt="Profile" 
                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
        <?php else: ?>
            <?= strtoupper(substr($username, 0, 1)) ?>
        <?php endif; ?>
    </div>
    <?php
}

// Get customer ID from user ID
function getCustomerId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

// Get seller ID from user ID
function getSellerId($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

// Get seller data by user ID
function getSellerData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, s.id as seller_id, s.business_name, s.business_address, s.phone as business_phone, s.tax_id 
        FROM users u 
        JOIN sellers s ON u.id = s.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get seller data by seller ID
function getSellerDataById($seller_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, s.id as seller_id, s.business_name, s.business_address, s.phone as business_phone, s.tax_id 
        FROM sellers s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$seller_id]);
    return $stmt->fetch();
}

// Check if user is seller and approved
function isApprovedSeller($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM sellers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->rowCount() > 0;
}

// Check if user has pending seller application
function hasPendingSellerApplication($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM seller_applications WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    return $stmt->rowCount() > 0;
}

// Get seller application status
function getSellerApplicationStatus($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT status FROM seller_applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result ? $result['status'] : null;
}

// Get all categories
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Get products by category
function getProductsByCategory($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ? AND p.stock > 0
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

// Get product by ID
function getProductById($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, s.business_name as seller_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN sellers s ON p.seller_id = s.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}

// Get user cart items
function getCartItems($customer_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll();
}

// Calculate cart total
function getCartTotal($customer_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) as total 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}
?>
