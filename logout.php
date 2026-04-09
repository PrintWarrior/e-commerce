<?php
require_once 'includes/functions.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown user';
$userType = $_SESSION['user_type'] ?? null;

if ($userType === 'customer' && $userId) {
    logSystemEvent(
        'customer_logged_out',
        'users',
        (int) $userId,
        "Customer {$username} logged out.",
        (int) $userId
    );
}

session_destroy();
header('Location: index.php');
exit;
?>
