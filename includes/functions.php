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
    $link = "http://localhost/beautymart/includes/verify.php?token=$token";

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
    $link = "http://localhost/beautymart/includes/reset_password.php?token=$token";
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

function orderStatusConsumesStock(string $status): bool {
    return in_array($status, ['processing', 'shipped', 'delivered', 'completed'], true);
}

function ensureOrderItemFulfillmentColumns(): void {
    global $pdo;

    static $checked = false;
    if ($checked) {
        return;
    }

    $statusExists = false;
    $trackingExists = false;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'status'");
        $statusExists = (bool) $stmt->fetch();

        $stmt = $pdo->query("SHOW COLUMNS FROM order_items LIKE 'tracking_number'");
        $trackingExists = (bool) $stmt->fetch();

        if (!$statusExists) {
            $pdo->exec("ALTER TABLE order_items ADD status ENUM('pending','processing','shipped','delivered','completed','cancelled') DEFAULT 'pending' AFTER price");
            $pdo->exec("
                UPDATE order_items oi
                JOIN orders o ON o.id = oi.order_id
                SET oi.status = CASE WHEN o.status = 'paid' THEN 'completed' ELSE o.status END
            ");
        }

        if (!$trackingExists) {
            $pdo->exec("ALTER TABLE order_items ADD tracking_number VARCHAR(100) DEFAULT NULL AFTER status");
            $pdo->exec("
                UPDATE order_items oi
                JOIN orders o ON o.id = oi.order_id
                SET oi.tracking_number = o.tracking_number
                WHERE o.tracking_number IS NOT NULL
            ");
        }
    } catch (Throwable $e) {
        throw new RuntimeException('Unable to prepare per-seller order status columns: ' . $e->getMessage());
    }

    $checked = true;
}

function deriveOrderStatusFromItemStatuses(int $orderId): string {
    global $pdo;

    ensureOrderItemFulfillmentColumns();

    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS status_count
        FROM order_items
        WHERE order_id = ?
        GROUP BY status
    ");
    $stmt->execute([$orderId]);
    $counts = [];

    foreach ($stmt->fetchAll() as $row) {
        $counts[(string) $row['status']] = (int) $row['status_count'];
    }

    if (empty($counts)) {
        return 'pending';
    }

    if (count($counts) === 1) {
        return array_key_first($counts);
    }

    $activeCounts = $counts;
    unset($activeCounts['cancelled']);

    if (empty($activeCounts)) {
        return 'cancelled';
    }

    if (!empty($activeCounts['pending'])) {
        return 'pending';
    }

    if (!empty($activeCounts['processing'])) {
        return 'processing';
    }

    if (!empty($activeCounts['shipped'])) {
        return 'shipped';
    }

    if (!empty($activeCounts['delivered'])) {
        return 'delivered';
    }

    if (!empty($activeCounts['completed'])) {
        return 'completed';
    }

    return 'pending';
}

function syncOrderStatusFromItems(int $orderId): void {
    global $pdo;

    $status = deriveOrderStatusFromItemStatuses($orderId);
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
}

// Deduct product stock once when an order first enters fulfillment.
function applyOrderStockForStatusTransition(int $orderId, string $currentStatus, string $newStatus): void {
    global $pdo;

    $stockAlreadyConsumed = orderStatusConsumesStock($currentStatus);
    $willConsumeStock = orderStatusConsumesStock($newStatus);

    if ($stockAlreadyConsumed && !$willConsumeStock) {
        throw new RuntimeException('Orders already in fulfillment cannot return to a non-fulfillment status.');
    }

    if (!$willConsumeStock || $stockAlreadyConsumed) {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT product_id, SUM(quantity) AS ordered_quantity
        FROM order_items
        WHERE order_id = ?
        GROUP BY product_id
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        throw new RuntimeException('This order has no items to fulfill.');
    }

    $stockUpdate = $pdo->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($items as $item) {
        $quantity = (int) $item['ordered_quantity'];
        $stockUpdate->execute([$quantity, (int) $item['product_id'], $quantity]);

        if ($stockUpdate->rowCount() === 0) {
            throw new RuntimeException('Not enough stock is available to complete this order.');
        }
    }
}

function applyOrderStockForSellerStatusTransition(int $orderId, int $sellerId, string $currentStatus, string $newStatus): void {
    global $pdo;

    $stockAlreadyConsumed = orderStatusConsumesStock($currentStatus);
    $willConsumeStock = orderStatusConsumesStock($newStatus);

    if ($stockAlreadyConsumed && !$willConsumeStock) {
        throw new RuntimeException('Orders already in fulfillment cannot return to a non-fulfillment status.');
    }

    if (!$willConsumeStock || $stockAlreadyConsumed) {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT oi.product_id, SUM(oi.quantity) AS ordered_quantity
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ? AND p.seller_id = ?
        GROUP BY oi.product_id
    ");
    $stmt->execute([$orderId, $sellerId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        throw new RuntimeException('This order has no items for this seller.');
    }

    $stockUpdate = $pdo->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($items as $item) {
        $quantity = (int) $item['ordered_quantity'];
        $stockUpdate->execute([$quantity, (int) $item['product_id'], $quantity]);

        if ($stockUpdate->rowCount() === 0) {
            throw new RuntimeException('Not enough stock is available to process this order.');
        }
    }
}

// Pull cart feedback once after add_to_cart.php redirects back to a page.
function pullCartPrompt(): ?array {
    foreach (['cart_success' => 'success', 'cart_warning' => 'warning', 'cart_error' => 'error'] as $key => $type) {
        if (!isset($_SESSION[$key])) {
            continue;
        }

        $prompt = ['type' => $type, 'text' => (string) $_SESSION[$key]];
        unset($_SESSION[$key]);
        return $prompt;
    }

    return null;
}

function renderCartPrompt(?array $prompt): void {
    if (!$prompt) {
        return;
    }

    $type = in_array($prompt['type'] ?? '', ['success', 'warning', 'error'], true)
        ? $prompt['type']
        : 'success';
    $title = $type === 'success' ? 'Cart Updated' : ($type === 'warning' ? 'Cart Notice' : 'Cart Update Failed');
    ?>
    <style>
        .cart-prompt {
            background: #fff;
            border: 0;
            border-radius: 8px;
            box-shadow: 0 24px 80px rgba(32, 25, 42, .28);
            color: #2d3748;
            inset: 0;
            letter-spacing: 0;
            margin: auto;
            max-height: calc(100vh - 32px);
            max-width: min(420px, calc(100vw - 32px));
            overflow: auto;
            padding: 0;
            position: fixed;
            text-align: center;
            width: 100%;
        }

        .cart-prompt::backdrop {
            background: rgba(27, 24, 34, .48);
        }

        .cart-prompt-card {
            padding: 28px;
        }

        .cart-prompt-mark {
            align-items: center;
            background: #eaf8ef;
            border: 2px solid #b6e3c6;
            border-radius: 50%;
            color: #207245;
            display: inline-flex;
            font-size: 28px;
            font-weight: 800;
            height: 64px;
            justify-content: center;
            line-height: 1;
            margin-bottom: 16px;
            width: 64px;
        }

        .cart-prompt.warning .cart-prompt-mark {
            background: #fff7e5;
            border-color: #f1d38a;
            color: #8b6100;
        }

        .cart-prompt.error .cart-prompt-mark {
            background: #fdecec;
            border-color: #efb5b5;
            color: #b53a3a;
        }

        .cart-prompt h2 {
            font-size: 25px;
            line-height: 1.2;
            margin: 0 0 10px;
        }

        .cart-prompt p {
            color: #556070;
            line-height: 1.5;
            margin: 0 0 22px;
        }

        .cart-prompt-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .cart-prompt-actions a,
        .cart-prompt-actions button {
            align-items: center;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-weight: 800;
            justify-content: center;
            min-height: 42px;
            min-width: 128px;
            padding: 0 16px;
            text-decoration: none;
        }

        .cart-prompt-actions a {
            background: #df3d78;
            border: 1px solid #df3d78;
            color: #fff;
        }

        .cart-prompt-actions button {
            background: #fff;
            border: 1px solid #d7dce4;
            color: #374151;
        }
    </style>
    <dialog class="cart-prompt <?= htmlspecialchars($type) ?>" id="cart-feedback-prompt" aria-labelledby="cart-feedback-title">
        <div class="cart-prompt-card">
            <div class="cart-prompt-mark" aria-hidden="true"><?= $type === 'success' ? '&#10003;' : '!' ?></div>
            <h2 id="cart-feedback-title"><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars((string) $prompt['text']) ?></p>
            <div class="cart-prompt-actions">
                <a href="cart.php">View Cart</a>
                <form method="dialog">
                    <button type="submit">Continue Shopping</button>
                </form>
            </div>
        </div>
    </dialog>
    <script>
        (() => {
            const prompt = document.getElementById('cart-feedback-prompt');
            if (prompt && typeof prompt.showModal === 'function') {
                prompt.showModal();
            } else if (prompt) {
                prompt.setAttribute('open', 'open');
            }
        })();
    </script>
    <?php
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

function paginateArray(array $items, int $currentPage = 1, int $perPage = 5): array {
    $perPage = max(1, $perPage);
    $totalItems = count($items);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'items' => array_slice($items, $offset, $perPage),
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'from' => $totalItems > 0 ? $offset + 1 : 0,
        'to' => min($offset + $perPage, $totalItems),
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

function buildQueryUrl(string $path, array $params = [], array $overrides = []): string {
    $merged = array_merge($params, $overrides);

    foreach ($merged as $key => $value) {
        if ($value === null || $value === '') {
            unset($merged[$key]);
        }
    }

    $query = http_build_query($merged);
    return $query === '' ? $path : $path . '?' . $query;
}
?>
