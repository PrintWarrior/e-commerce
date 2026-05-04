<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdminOrSuperadmin()) {
    redirect('../login.php');
}

// Handle single notification mark as read
if (isset($_GET['notification_id']) && is_numeric($_GET['notification_id'])) {
    $notification_id = $_GET['notification_id'];
    
    // Verify the notification belongs to this user
    $stmt = $pdo->prepare("
        SELECT id FROM notifications 
        WHERE id = ? AND user_id = ? AND is_read = 0
    ");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    $notification = $stmt->fetch();
    
    if ($notification) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);
        
        $_SESSION['flash_message'] = "Notification marked as read.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Notification not found or already read.";
        $_SESSION['flash_type'] = "error";
    }
} else {
    // Mark all notifications as read for this admin user
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Optional: Log the action for audit trail
    createNotification($_SESSION['user_id'], 'All notifications marked as read', 'system');
    
    $_SESSION['flash_message'] = "All notifications have been marked as read.";
    $_SESSION['flash_type'] = "success";
}

// Redirect back to dashboard
redirect('dashboard.php');
?>
